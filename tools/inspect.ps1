param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Email   = 'admin@example.com',
    [string]$Pass    = 'admin123',
    [string]$Path    = '/admin/dashboard'
)

$ErrorActionPreference = 'Stop'

$resp = Invoke-WebRequest -Uri "$BaseUrl/admin/login" -SessionVariable session -UseBasicParsing
if ($resp.Content -notmatch 'name="_token"\s+value="([^"]+)"') { throw 'no csrf' }
$token = $Matches[1]
$body = @{ _token = $token; email = $Email; password = $Pass }
[void](Invoke-WebRequest -Uri "$BaseUrl/admin/login" -Method POST -Body $body `
    -WebSession $session -UseBasicParsing -MaximumRedirection 5)

$page = Invoke-WebRequest -Uri "$BaseUrl$Path" -WebSession $session -UseBasicParsing

$size = $page.Content.Length
$hasDebugbar = $page.Content -match 'phpdebugbar'
$hasViteDev  = $page.Content -match '@vite/client|http://localhost:5173|127\.0\.0\.1:5173'
$assetUrls   = [regex]::Matches($page.Content, 'href="[^"]+\.css[^"]*"|src="[^"]+\.js[^"]*"') | ForEach-Object { $_.Value } | Select-Object -Unique

[pscustomobject]@{
    url            = "$BaseUrl$Path"
    bytes          = $size
    has_debugbar   = $hasDebugbar
    has_vite_dev   = $hasViteDev
    asset_count    = $assetUrls.Count
}

Write-Host "--- first 5 assets ---"
$assetUrls | Select-Object -First 5
