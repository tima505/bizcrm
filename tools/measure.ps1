param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Email   = 'admin@example.com',
    [string]$Pass    = 'admin123',
    [int]$Runs       = 3,
    [string]$Label   = 'run'
)

$ErrorActionPreference = 'Stop'

function Get-TokenAndXsrf([ref]$Session, [string]$Url) {
    $resp = Invoke-WebRequest -Uri $Url -SessionVariable sv -UseBasicParsing
    $Session.Value = $sv
    if ($resp.Content -match 'name="_token"\s+value="([^"]+)"') {
        return $Matches[1]
    }
    throw "no csrf token at $Url"
}

$session = $null
$token = Get-TokenAndXsrf ([ref]$session) "$BaseUrl/admin/login"

$body = @{ _token = $token; email = $Email; password = $Pass }
$login = Invoke-WebRequest -Uri "$BaseUrl/admin/login" -Method POST -Body $body `
    -WebSession $session -UseBasicParsing -MaximumRedirection 5
if ($login.Content -match 'name="email"' -and $login.Content -notmatch 'logout') {
    throw "login failed, got login page back"
}

$targets = [ordered]@{
    'dashboard' = "$BaseUrl/admin/dashboard"
    'leads'     = "$BaseUrl/admin/leads"
    'contacts'  = "$BaseUrl/admin/contacts/persons"
    'messages'  = "$BaseUrl/admin/messages"
}

$results = @()
foreach ($name in $targets.Keys) {
    $url = $targets[$name]
    # Warm one (so file cache gets written, routes compiled, etc.)
    try { [void](Invoke-WebRequest -Uri $url -WebSession $session -UseBasicParsing -TimeoutSec 60) } catch {}

    $ms = @()
    for ($i = 0; $i -lt $Runs; $i++) {
        $sw = [System.Diagnostics.Stopwatch]::StartNew()
        $r = Invoke-WebRequest -Uri $url -WebSession $session -UseBasicParsing -TimeoutSec 60
        $sw.Stop()
        $ms += [int]$sw.ElapsedMilliseconds
    }
    $avg = [int](($ms | Measure-Object -Average).Average)
    $min = ($ms | Measure-Object -Minimum).Minimum
    $max = ($ms | Measure-Object -Maximum).Maximum
    $results += [pscustomobject]@{
        label = $Label
        page  = $name
        avg_ms= $avg
        min_ms= $min
        max_ms= $max
        runs  = ($ms -join ',')
    }
}

$results | Format-Table -AutoSize
