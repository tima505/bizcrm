param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Email   = 'admin@example.com',
    [string]$Pass    = 'admin123',
    [string]$DevTools = ''
)

$ErrorActionPreference = 'Stop'

if (-not $DevTools) {
    $tabs = Invoke-RestMethod 'http://127.0.0.1:9222/json'
    $tab = $tabs | Where-Object { $_.type -eq 'page' } | Select-Object -First 1
    if (-not $tab) { throw 'No chrome tab' }
    $DevTools = $tab.webSocketDebuggerUrl
}

$ws = New-Object System.Net.WebSockets.ClientWebSocket
$cancel = New-Object System.Threading.CancellationToken
$ws.ConnectAsync([Uri]::new($DevTools), $cancel).Wait()

$idCounter = 0
function Send-CdpCommand($method, $params = @{}) {
    $script:idCounter++
    $msg = @{ id = $script:idCounter; method = $method; params = $params } | ConvertTo-Json -Depth 10 -Compress
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($msg)
    $seg = New-Object System.ArraySegment[byte] -ArgumentList @(,$bytes)
    $script:ws.SendAsync($seg, [System.Net.WebSockets.WebSocketMessageType]::Text, $true, $cancel).Wait()

    # Read until we get a response with our id
    $buf = New-Object byte[] 65536
    $sb = New-Object System.Text.StringBuilder
    while ($true) {
        $sb.Clear() | Out-Null
        do {
            $seg2 = New-Object System.ArraySegment[byte] -ArgumentList @(,$buf)
            $recv = $script:ws.ReceiveAsync($seg2, $cancel)
            $recv.Wait()
            $r = $recv.Result
            $sb.Append([System.Text.Encoding]::UTF8.GetString($buf, 0, $r.Count)) | Out-Null
        } while (-not $r.EndOfMessage)
        $obj = $sb.ToString() | ConvertFrom-Json
        if ($obj.id -eq $script:idCounter) {
            return $obj
        }
    }
}

function Navigate($url) {
    [void](Send-CdpCommand 'Page.navigate' @{ url = $url })
    Start-Sleep -Milliseconds 1200
}

function Eval($expr) {
    $r = Send-CdpCommand 'Runtime.evaluate' @{
        expression = $expr
        returnByValue = $true
        awaitPromise = $true
    }
    return $r.result.result.value
}

function Report($label, $value) {
    "{0,-45} {1}" -f $label, $value
}

# Enable Page and Runtime
[void](Send-CdpCommand 'Page.enable')
[void](Send-CdpCommand 'Runtime.enable')

# Login
Navigate "$BaseUrl/admin/login"
$csrf = Eval @"
(() => {
    const el = document.querySelector('input[name=\"_token\"]');
    return el ? el.value : null;
})()
"@

Eval "document.querySelector('input[name=`"email`"]').value = '$Email'" | Out-Null
Eval "document.querySelector('input[name=`"password`"]').value = '$Pass'" | Out-Null
Eval "document.querySelector('form').submit()" | Out-Null
Start-Sleep -Milliseconds 1500

Navigate "$BaseUrl/admin/dashboard"
Start-Sleep -Milliseconds 800

Report 'dashboard URL' (Eval 'location.pathname')
Report 'sidebar menu items' (Eval @"
Array.from(document.querySelectorAll('#admin-sidebar nav > div a[href]'))
    .filter(a => a.querySelector('span[class*=icon-]'))
    .map(a => a.querySelector('p')?.textContent.trim())
    .filter(Boolean)
    .join(' | ')
"@)

# Check logo loads
$logoCheck = Eval @"
(async () => {
    const img = document.querySelector('header img#logo-image, header a img');
    if (!img) return 'no-img';
    const r = await fetch(img.src);
    const t = await r.text();
    const vb = t.match(/viewBox=\x22([^\x22]+)\x22/);
    return 'natural=' + img.naturalWidth + 'x' + img.naturalHeight + ' viewBox=' + (vb ? vb[1] : '?');
})()
"@
Report 'logo' $logoCheck

# Navigate to /admin/messages and test chat click behaviour
Navigate "$BaseUrl/admin/messages"
Start-Sleep -Milliseconds 1500

Report 'messages URL' (Eval 'location.pathname')
Report 'chat items count' (Eval 'document.querySelectorAll(".wa-chat-item").length')

# Simulate chat click
Eval 'const b = document.querySelector(".wa-chat-item"); if (b) b.click();' | Out-Null
Start-Sleep -Milliseconds 1200

Report 'thread title text' (Eval 'document.getElementById("wa-thread-title")?.textContent.trim().slice(0, 60)')
Report 'input enabled' (Eval '!document.getElementById("wa-send-text")?.disabled')
Report 'send btn enabled' (Eval '!document.getElementById("wa-send-btn")?.disabled')
Report 'thread has bubbles' (Eval 'document.querySelectorAll("#wa-thread .max-w-\\[75%\\]").length > 0')

# Switch to mobile viewport (iPhone-ish 390x844)
[void](Send-CdpCommand 'Emulation.setDeviceMetricsOverride' @{
    width = 390
    height = 844
    deviceScaleFactor = 2
    mobile = $true
})

Navigate "$BaseUrl/admin/messages"
Start-Sleep -Milliseconds 1500

Report 'mobile: aside visible' (Eval '!document.getElementById("wa-chat-aside")?.classList.contains("hidden")')
Report 'mobile: section hidden before pick' (Eval 'document.getElementById("wa-thread-section")?.classList.contains("hidden")')

Eval 'const b = document.querySelector(".wa-chat-item"); if (b) b.click();' | Out-Null
Start-Sleep -Milliseconds 1200

Report 'mobile: aside hidden after pick' (Eval 'document.getElementById("wa-chat-aside")?.classList.contains("hidden")')
Report 'mobile: section visible after pick' (Eval '!document.getElementById("wa-thread-section")?.classList.contains("hidden")')
Report 'mobile: back btn visible' (Eval @"
(() => {
    const el = document.getElementById('wa-back');
    if (!el) return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
})()
"@)

# Click back
Eval 'document.getElementById("wa-back")?.click()' | Out-Null
Start-Sleep -Milliseconds 500

Report 'mobile: back → aside visible' (Eval '!document.getElementById("wa-chat-aside")?.classList.contains("hidden")')
Report 'mobile: back → section hidden' (Eval 'document.getElementById("wa-thread-section")?.classList.contains("hidden")')

# Reset viewport, test sidebar order on desktop again
[void](Send-CdpCommand 'Emulation.clearDeviceMetricsOverride')
Navigate "$BaseUrl/admin/dashboard"
Start-Sleep -Milliseconds 800

Report 'desktop again: menu order' (Eval @"
(() => {
    const items = Array.from(document.querySelectorAll('#admin-sidebar nav > div > a span[class*=icon-]'));
    return items.map(s => s.className.match(/icon-[a-z]+/)?.[0]).filter(Boolean).join(',');
})()
"@)

$ws.CloseAsync([System.Net.WebSockets.WebSocketCloseStatus]::NormalClosure, 'done', $cancel).Wait()
