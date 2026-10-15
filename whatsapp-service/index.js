import 'dotenv/config';
import express from 'express';
import axios from 'axios';
import pino from 'pino';
import qrcode from 'qrcode-terminal';
import makeWASocket, {
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    DisconnectReason,
} from '@whiskeysockets/baileys';

const PORT = Number(process.env.PORT || 3000);
const LARAVEL_INCOMING_URL = process.env.LARAVEL_INCOMING_URL || 'http://bizcrm.test/api/whatsapp/incoming';
const SECRET = process.env.WHATSAPP_SECRET || 'change-me';
const AUTH_DIR = process.env.AUTH_DIR || './auth';

const logger = pino({ level: 'warn' });

let sock = null;
let currentQr = null;
let connectionState = 'disconnected';

function normaliseJid(raw) {
    if (!raw) return null;
    const digits = String(raw).replace(/\D/g, '');
    if (!digits) return null;
    return `${digits}@s.whatsapp.net`;
}

function extractText(message) {
    if (!message) return '';
    if (message.conversation) return message.conversation;
    if (message.extendedTextMessage?.text) return message.extendedTextMessage.text;
    if (message.imageMessage?.caption) return message.imageMessage.caption;
    if (message.videoMessage?.caption) return message.videoMessage.caption;
    if (message.buttonsResponseMessage?.selectedDisplayText) return message.buttonsResponseMessage.selectedDisplayText;
    if (message.listResponseMessage?.title) return message.listResponseMessage.title;
    return '';
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
        console.error('[forward] failed:', err.response?.status, err.response?.data || err.message);
    }
}

async function start() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        auth: state,
        logger,
        printQRInTerminal: false,
        browser: ['bizcrm', 'Chrome', '1.0.0'],
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            currentQr = qr;
            console.log('\n=== Отсканируйте QR-код в WhatsApp (Настройки -> Связанные устройства) ===\n');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'open') {
            connectionState = 'connected';
            currentQr = null;
            console.log('[wa] connected as', sock.user?.id);
        }

        if (connection === 'close') {
            connectionState = 'disconnected';
            const code = lastDisconnect?.error?.output?.statusCode;
            const loggedOut = code === DisconnectReason.loggedOut;
            console.log('[wa] connection closed, code=', code, 'loggedOut=', loggedOut);
            if (!loggedOut) {
                setTimeout(() => start().catch((e) => console.error('[wa] restart failed:', e)), 3_000);
            } else {
                console.log('[wa] logged out, delete the auth folder and restart to re-scan QR.');
            }
        }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;
        for (const m of messages) {
            if (!m.message) continue;
            if (m.key.fromMe) continue;

            const text = extractText(m.message);
            if (!text) continue;

            const jid = m.key.remoteJid || '';
            const number = jid.split('@')[0];

            await forwardIncoming({
                channel: 'whatsapp',
                external_id: m.key.id,
                chat_id: jid,
                from_number: number,
                contact_name: m.pushName || null,
                content: text,
                direction: 'incoming',
                timestamp: m.messageTimestamp ? Number(m.messageTimestamp) : Math.floor(Date.now() / 1000),
            });
        }
    });
}

function requireSecret(req, res, next) {
    const got = req.header('x-whatsapp-secret') || req.header('X-Whatsapp-Secret');
    if (got !== SECRET) {
        return res.status(401).json({ ok: false, error: 'bad_secret' });
    }
    next();
}

const app = express();
app.use(express.json({ limit: '1mb' }));

app.get('/health', (_req, res) => {
    res.json({ ok: true, state: connectionState, hasQr: Boolean(currentQr) });
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

        const jid = String(number).includes('@') ? String(number) : normaliseJid(number);
        if (!jid) {
            return res.status(422).json({ ok: false, error: 'bad_number' });
        }

        const sent = await sock.sendMessage(jid, { text: String(text) });
        return res.json({
            ok: true,
            external_id: sent?.key?.id || null,
            chat_id: jid,
        });
    } catch (err) {
        console.error('[send] failed:', err.message);
        return res.status(500).json({ ok: false, error: err.message });
    }
});

app.listen(PORT, '127.0.0.1', () => {
    console.log(`[http] listening on http://127.0.0.1:${PORT}`);
});

start().catch((err) => {
    console.error('[wa] fatal start error:', err);
    process.exit(1);
});
