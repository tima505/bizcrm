param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Email   = 'admin@example.com',
    [string]$Pass    = 'admin123'
)

$ErrorActionPreference = 'Stop'

function Check([string]$name, [scriptblock]$block) {
    try {
        $out = & $block
        "{0,-45} OK  {1}" -f $name, $out
    } catch {
        "{0,-45} FAIL {1}" -f $name, $_.Exception.Message
    }
}

$session = $null
$resp = Invoke-WebRequest "$BaseUrl/admin/login" -SessionVariable session -UseBasicParsing
if ($resp.Content -notmatch 'name="_token"\s+value="([^"]+)"') { throw 'no csrf' }
$token = $Matches[1]
$body = @{ _token = $token; email = $Email; password = $Pass }
[void](Invoke-WebRequest "$BaseUrl/admin/login" -Method POST -Body $body `
    -WebSession $session -UseBasicParsing -MaximumRedirection 5)

$pages = [ordered]@{
    'GET /admin/dashboard'          = '/admin/dashboard'
    'GET /admin/leads'              = '/admin/leads'
    'GET /admin/contacts/persons'   = '/admin/contacts/persons'
    'GET /admin/contacts/organizations' = '/admin/contacts/organizations'
    'GET /admin/activities'         = '/admin/activities'
    'GET /admin/mail/inbox'         = '/admin/mail/inbox'
    'GET /admin/settings'           = '/admin/settings/settings'
    'GET /admin/configuration'      = '/admin/configuration'
    'GET /admin/help'               = '/admin/help'
    'GET /admin/messages'           = '/admin/messages'
}

foreach ($name in $pages.Keys) {
    Check $name {
        $r = Invoke-WebRequest "$BaseUrl$($pages[$name])" -WebSession $session -UseBasicParsing -TimeoutSec 20
        "HTTP $($r.StatusCode) size=$([int]($r.Content.Length / 1024))KB"
    }
}

# Unread badge: run BEFORE the thread endpoint (which marks chat as read)
Check 'GET /admin/dashboard (unread badge wiring)' {
    $r = Invoke-WebRequest "$BaseUrl/admin/dashboard" -WebSession $session -UseBasicParsing
    $start = $r.Content.IndexOf('id="admin-sidebar"')
    $end   = if ($start -ge 0) { $r.Content.IndexOf('v-sidebar-collapse', $start) } else { -1 }
    if ($start -lt 0 -or $end -lt 0) { throw "sidebar block not found" }
    $side = $r.Content.Substring($start, $end - $start)
    # Badge markup is present only when unread > 0; otherwise just verify markup absent cleanly.
    $hasBadge = $side -match 'bg-red-500'
    "badgePresent=$hasBadge"
}

# WhatsApp thread endpoint w/o chat_id (returns chat list)
Check 'GET /admin/messages/thread (no chat)' {
    $r = Invoke-WebRequest "$BaseUrl/admin/messages/thread" -WebSession $session -UseBasicParsing -TimeoutSec 10
    $json = $r.Content | ConvertFrom-Json
    "chats=$($json.chats.Count) messages=$($json.messages.Count)"
}

# WhatsApp thread with chat_id (uses existing @lid chat from DB)
Check 'GET /admin/messages/thread (existing @lid)' {
    $url = "$BaseUrl/admin/messages/thread?chat_id=" + [uri]::EscapeDataString('225400638689361@lid')
    $r = Invoke-WebRequest $url -WebSession $session -UseBasicParsing -TimeoutSec 10
    $json = $r.Content | ConvertFrom-Json
    "messages=$($json.messages.Count) chats=$($json.chats.Count)"
}

# Also inspect messages HTML — chat card must render name/initial/time/unread
Check 'GET /admin/messages (HTML sanity)' {
    $r = Invoke-WebRequest "$BaseUrl/admin/messages" -WebSession $session -UseBasicParsing
    $hasAvatar = $r.Content -match 'wa-chat-item'
    $hasTime   = $r.Content -match 'wa-chat-time'
    $hasSidebarWidth = $r.Content -match 'w-\[320px\]'
    $hasSpec   = $r.Content -match 'speculationrules'
    "avatar=$hasAvatar time=$hasTime sidebar320=$hasSidebarWidth spec=$hasSpec"
}

# Sidebar sanity: menu order by icon class + no top-level help/configuration
Check 'GET /admin/dashboard (menu order)' {
    $r = Invoke-WebRequest "$BaseUrl/admin/dashboard" -WebSession $session -UseBasicParsing
    $html = $r.Content

    # Isolate desktop sidebar to avoid false matches from mobile template / megasearch
    $start = $html.IndexOf('id="admin-sidebar"')
    $end   = if ($start -ge 0) { $html.IndexOf('v-sidebar-collapse', $start) } else { -1 }
    if ($start -lt 0 -or $end -lt 0) { throw "sidebar block not found" }
    $side = $html.Substring($start, $end - $start)

    $icons = @('icon-dashboard','icon-mail','icon-leads','icon-contact','icon-activity','icon-quote','icon-product','icon-setting')
    $positions = @()
    foreach ($i in $icons) { $positions += $side.IndexOf($i) }

    $sorted = $true
    for ($k = 1; $k -lt $positions.Count; $k++) {
        if ($positions[$k] -lt 0 -or $positions[$k] -le $positions[$k-1]) { $sorted = $false; break }
    }

    # Help and Configuration must NOT be top-level in the sidebar anymore
    $noHelp = ($side -notmatch 'icon-help')
    $noConf = ($side -notmatch 'icon-configuration')

    "order=$sorted noHelp=$noHelp noConf=$noConf"
}

Check 'GET /admin/dashboard (logo viewBox)' {
    $r = Invoke-WebRequest "$BaseUrl/admin/dashboard" -WebSession $session -UseBasicParsing
    if ($r.Content -match 'src="([^"]+logo-[^"]+\.svg)"') {
        $logoHref = $Matches[1]
        if ($logoHref -notmatch '^https?://') { $logoHref = $BaseUrl + $logoHref }
        $lr = Invoke-WebRequest $logoHref -WebSession $session -UseBasicParsing
        $ok = $lr.Content -match 'viewBox="0 0 156 40"'
        "ok=$ok"
    } else {
        throw "logo svg not found in HTML"
    }
}
