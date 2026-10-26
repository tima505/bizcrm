import 'dotenv/config';
import express from 'express';
import axios from 'axios';
import pino from 'pino';
import qrcode from 'qrcode-terminal';
import { createWriteStream, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import makeWASocket, {
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    DisconnectReason,
    jidDecode,
    isLidUser,
    isJidGroup,
    isJidUser,
} from '@whiskeysockets/baileys';

const PORT = Number(process.env.PORT || 3000);
const LARAVEL_INCOMING_URL = process.env.LARAVEL_INCOMING_URL || 'http://127.0.0.1:8000/api/whatsapp/incoming';
const SECRET = process.env.WHATSAPP_SECRET || 'change-me';
const AUTH_DIR = process.env.AUTH_DIR || './auth';
const LOG_FILE = process.env.LOG_FILE || './logs/wa.log';
const LOG_LEVEL = process.env.LOG_LEVEL || 'warn';

// Open an append-mode log file. Pino and our own log() calls both tee into
// it, so a terminal that scrolled past the error is still recoverable.
mkdirSync(dirname(LOG_FILE), { recursive: true });
const logStream = createWriteStream(LOG_FILE, { flags: 'a' });
logStream.on('error', (err) => {
    // Don't crash the service if the log file can't be written — just
    // report once to stderr and keep running.
    process.stderr.write(`[log] write failed: ${err?.message || err}\n`);
});

function fmtLine(level, args) {
    const now = new Date().toISOString();
    const text = args
        .map((a) => (typeof a === 'string' ? a : (() => {
            try { return JSON.stringify(a); } catch (_) { return String(a); }
        })()))
        .join(' ');
    return `${now} ${level} ${text}\n`;
}

function log(...args) {
    const line = fmtLine('INFO ', args);
    process.stdout.write(line);
    logStream.write(line);
}

function logErr(...args) {
    const line = fmtLine('ERROR', args);
    process.stderr.write(line);
    logStream.write(line);
}

// Baileys uses pino internally. Multistream fans the same records out to
// both stdout and the shared log file, so Signal/decryption errors
// (pino level=50) are preserved for inspection.
const pinoDest = pino.multistream([
    { level: LOG_LEVEL, stream: process.stdout },
    { level: LOG_LEVEL, stream: logStream },
]);
const logger = pino({ level: LOG_LEVEL }, pinoDest);

let sock = null;
let currentQr = null;
let connectionState = 'disconnected';

// Reconnection state
let reconnecting = false;
let reconnectAttempts = 0;
let consecutiveBadSession = 0;

// In-memory message cache that backs getMessage(). Baileys calls it when it
// needs the plaintext of a message we previously sent — happens when the
// other side's session got out of sync and they request a retry via a
// Message Retry Receipt. Without it we can't re-encrypt and the message
// stays undelivered.
//
// We also cache inbound messages (keyed the same way) because Baileys asks
// for them during catch-up decryption too.
const messageCache = new Map();
const MESSAGE_CACHE_MAX = 500;

function cacheMessage(key, message) {
    if (!key?.id || !key?.remoteJid || !message) return;
    const k = `${key.remoteJid}|${key.id}`;
    messageCache.set(k, message);
    if (messageCache.size > MESSAGE_CACHE_MAX) {
        // Map preserves insertion order; delete the oldest.
        const oldest = messageCache.keys().next().value;
        messageCache.delete(oldest);
    }
}

function digitsOf(jid) {
    const decoded = jidDecode(jid);
    return decoded?.user || null;
}

function extractText(message) {
    if (!message) return '';
    if (message.conversation) return message.conversation;
    if (message.extendedTextMessage?.text) return message.extendedTextMessage.text;
    if (message.imageMessage?.caption) return message.imageMessage.caption;
    if (message.videoMessage?.caption) return message.videoMessage.caption;
    if (message.buttonsResponseMessage?.selectedDisplayText) return message.buttonsResponseMessage.selectedDisplayText;
    if (message.listResponseMessage?.title) return message.listResponseMessage.title;
    if (message.ephemeralMessage?.message) return extractText(message.ephemeralMessage.message);
    if (message.viewOnceMessage?.message) return extractText(message.viewOnceMessage.message);
    if (message.viewOnceMessageV2?.message) return extractText(message.viewOnceMessageV2.message);
    return '';
}

/**
 * Pick a stable identity for a 1:1 chat:
 *  - chat_id: the JID we must send replies to (keep the server suffix as-is,
 *    including @lid). Never swap @lid → @s.whatsapp.net or vice versa, because
 *    Baileys needs to receive the same server type it saw inbound.
 *  - from_number: the real phone digits if WhatsApp disclosed them
 *    (key.senderPn when sender is in LID privacy mode), otherwise the user
 *    part of the LID (also digits, but different number space).
 */
function identityFromKey(key) {
    const remote = key?.remoteJid || '';
    const chatId = remote;
    const senderPn = key?.senderPn;

    let phone = null;
    if (senderPn) {
        phone = digitsOf(senderPn);
    } else if (remote && !isLidUser(remote)) {
        phone = digitsOf(remote);
    }

    const lidUser = isLidUser(remote) ? digitsOf(remote) : null;
    const fromNumber = phone || (lidUser ? `lid:${lidUser}` : chatId);

    return { chatId, fromNumber, phone, lidUser };
}

async function forwardIncoming(payload) {
    try {
        await axios.post(LARAVEL_INCOMING_URL, payload, {
            timeout: 10_000,
            headers: {
                'Content-Type': 'application/json',
                'X-Whatsapp-Secret': SECRET,
            },
        });
    } catch (err) {
        logErr('[forward] failed:', err.response?.status, err.response?.data || err.message);
    }
}

async function handleIncoming(m) {
    if (!m.message) return;
    if (m.key.fromMe) return;

    const remote = m.key.remoteJid || '';
    if (isJidGroup(remote)) return;
    if (remote === 'status@broadcast') return;

    const text = extractText(m.message);
    if (!text) return;

    const { chatId, fromNumber, phone } = identityFromKey(m.key);

    await forwardIncoming({
        channel: 'whatsapp',
        external_id: m.key.id,
        chat_id: chatId,
        from_number: fromNumber,
        phone_number: phone,
        contact_name: m.pushName || null,
        content: text,
        direction: 'incoming',
        timestamp: m.messageTimestamp ? Number(m.messageTimestamp) : Math.floor(Date.now() / 1000),
    });
}

function disconnectReasonName(code) {
    const names = Object.keys(DisconnectReason);
    for (const name of names) {
        if (DisconnectReason[name] === code) return name;
    }
    return 'unknown';
}

function scheduleReconnect(delayMs) {
    if (reconnecting) return;
    reconnecting = true;
    reconnectAttempts++;
    const ms = Math.max(0, delayMs);
    log(`[wa] reconnect in ${ms}ms (attempt #${reconnectAttempts})`);
    setTimeout(() => {
        reconnecting = false;
        start().catch((e) => {
            logErr('[wa] restart failed:', e?.message || e);
            // Exponential backoff for repeated failures: 1s, 2s, 5s, 10s, 30s.
            const backoff = Math.min(30_000, 1_000 * 2 ** Math.min(reconnectAttempts, 5));
            scheduleReconnect(backoff);
        });
    }, ms);
}

function cleanupSocket(oldSock) {
    if (!oldSock) return;
    try { oldSock.ev?.removeAllListeners?.(); } catch (_) { /* noop */ }
    try { oldSock.ws?.close?.(); } catch (_) { /* noop */ }
    try { oldSock.end?.(undefined); } catch (_) { /* noop */ }
}

async function start() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    // Drop old socket + listeners before standing up a new one, otherwise
    // stale handlers keep firing on dead sockets and leak memory.
    cleanupSocket(sock);
    sock = null;

    const newSock = makeWASocket({
        version,
        auth: state,
        logger,
        printQRInTerminal: false,
        browser: ['bizcrm', 'Chrome', '1.0.0'],
        // Stay invisible on WhatsApp — our status does not flip to online,
        // and the server keeps buffering messages for us even if we drop
        // the socket briefly.
        markOnlineOnConnect: false,
        // Baileys calls this when it needs the plaintext of a message
        // (ours or a sender's) to repair a broken Signal session. Returning
        // the cached payload is what fixes Bad MAC / No session errors
        // instead of just dropping affected messages.
        getMessage: async (key) => {
            if (!key?.id || !key?.remoteJid) return undefined;
            const k = `${key.remoteJid}|${key.id}`;
            const msg = messageCache.get(k);
            return msg || undefined;
        },
    });

    sock = newSock;

    newSock.ev.on('creds.update', saveCreds);

    newSock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            currentQr = qr;
            log('\n=== Отсканируйте QR-код в WhatsApp (Настройки -> Связанные устройства) ===\n');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'open') {
            connectionState = 'connected';
            currentQr = null;
            reconnectAttempts = 0;
            consecutiveBadSession = 0;
            log('[wa] connected as', newSock.user?.id);
        }

        if (connection === 'close') {
            connectionState = 'disconnected';
            const code = lastDisconnect?.error?.output?.statusCode;
            const reason = disconnectReasonName(code);
            const errMsg = lastDisconnect?.error?.message || '';
            log(`[wa] connection closed, code=${code} (${reason}) msg="${errMsg}"`);

            if (code === DisconnectReason.loggedOut) {
                log('[wa] logged out, delete the auth folder and restart to re-scan QR.');
                cleanupSocket(newSock);
                return;
            }

            if (code === DisconnectReason.badSession) {
                consecutiveBadSession++;
                if (consecutiveBadSession >= 5) {
                    logErr(
                        `[wa] 5 consecutive badSession disconnects — the session file is likely corrupt. ` +
                        `Stop the service, delete whatsapp-service/auth/, and re-scan the QR.`
                    );
                }
            }

            // restartRequired is Baileys asking for an immediate reconnect
            // (e.g. after a version update ack). All other closes get a
            // short delay to avoid tight-looping on upstream rate limits.
            const delay = code === DisconnectReason.restartRequired ? 100 : 3_000;
            cleanupSocket(newSock);
            scheduleReconnect(delay);
        }
    });

    newSock.ev.on('messages.upsert', async ({ messages, type }) => {
        // 'notify': a brand-new message arriving live.
        // 'append': a message replayed on reconnect because it arrived
        //           while we were offline. Dropping this type loses every
        //           message that landed during a disconnect window.
        if (type !== 'notify' && type !== 'append') return;

        for (const m of messages) {
            try {
                // Cache first — Baileys may ask getMessage() for this key
                // moments later if the recipient needs a retry-receipt.
                cacheMessage(m.key, m.message);

                await handleIncoming(m);
            } catch (err) {
                const remote = m?.key?.remoteJid;
                const id = m?.key?.id;
                logErr('[incoming] handler failed', { remote, id, msg: err?.message });
            }
        }
    });

    // Baileys emits updates for retry-receipts, delivery and read events.
    // We don't need to act on them, but swallowing any exception from
    // observers keeps the socket from being torn down by a thrown error.
    newSock.ev.on('messages.update', (_updates) => { /* noop */ });
    newSock.ev.on('message-receipt.update', (_updates) => { /* noop */ });
}

process.on('uncaughtException', (err) => {
    logErr('[uncaught]', err?.message || err);
});

process.on('unhandledRejection', (err) => {
    logErr('[unhandled]', err?.message || err);
});

function requireSecret(req, res, next) {
    const got = req.header('x-whatsapp-secret') || req.header('X-Whatsapp-Secret');
    if (got !== SECRET) {
        return res.status(401).json({ ok: false, error: 'bad_secret' });
    }
    next();
}

/**
 * Resolve the destination JID. Priority:
 *  1. If the caller already passed a JID (contains @), send to it verbatim.
 *     This is what preserves @lid chats — the Laravel side stores the inbound
 *     remoteJid as chat_id and echoes it back here.
 *  2. Otherwise treat the input as a phone number and compose @s.whatsapp.net.
 */
function resolveJid(numberOrJid) {
    if (!numberOrJid) return null;
    const raw = String(numberOrJid).trim();
    if (raw.includes('@')) return raw;
    const digits = raw.replace(/\D/g, '');
    if (!digits) return null;
    return `${digits}@s.whatsapp.net`;
}

const app = express();
app.use(express.json({ limit: '1mb' }));

app.get('/health', (_req, res) => {
    res.json({
        ok: true,
        state: connectionState,
        hasQr: Boolean(currentQr),
        reconnectAttempts,
        cachedMessages: messageCache.size,
        consecutiveBadSession,
    });
});

app.post('/send', requireSecret, async (req, res) => {
    try {
        if (connectionState !== 'connected' || !sock) {
            return res.status(503).json({ ok: false, error: 'wa_not_connected' });
        }

        const { number, text } = req.body || {};
        if (!number || !text) {
            return res.status(422).json({ ok: false, error: 'number_and_text_required' });
        }

        const jid = resolveJid(number);
        if (!jid) {
            return res.status(422).json({ ok: false, error: 'bad_number' });
        }

        const sent = await sock.sendMessage(jid, { text: String(text) });

        // Cache our outbound message so Baileys can satisfy a future
        // retry-receipt request from the recipient (fixes the usual
        // "No session" / "Bad MAC" errors that otherwise show up at
        // level=50 in pino).
        if (sent?.key && sent?.message) {
            cacheMessage(sent.key, sent.message);
        }

        return res.json({
            ok: true,
            external_id: sent?.key?.id || null,
            chat_id: jid,
        });
    } catch (err) {
        logErr('[send] failed:', err?.message || err);
        return res.status(500).json({ ok: false, error: err?.message || 'send_failed' });
    }
});

app.listen(PORT, '127.0.0.1', () => {
    log(`[http] listening on http://127.0.0.1:${PORT}`);
});

start().catch((err) => {
    logErr('[wa] fatal start error:', err?.message || err);
    // Keep the process alive and retry — exiting here means the HTTP
    // health endpoint goes down with it, so the Laravel side can't even
    // see the service is in trouble.
    scheduleReconnect(5_000);
});
