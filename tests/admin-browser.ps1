param(
    [string]$BaseUrl = 'http://localhost/insaat',
    [string]$Email = 'admin@kalfaciyapi.com',
    [string]$Password = $env:INSAAT_TEST_PASSWORD
)

$ErrorActionPreference = 'Stop'
if (-not $Password) {
    throw 'Set INSAAT_TEST_PASSWORD or pass -Password for the local test account.'
}

$chromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$profilePath = Join-Path $env:TEMP ('insaat-admin-browser-' + [guid]::NewGuid().ToString('N'))
$artifactPath = Join-Path $env:TEMP ('insaat-admin-preview-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $profilePath, $artifactPath | Out-Null
$listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, 0)
$listener.Start()
$port = $listener.LocalEndpoint.Port
$listener.Stop()
$chrome = Start-Process -FilePath $chromePath -WindowStyle Hidden -PassThru -ArgumentList @(
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    "--remote-debugging-port=$port",
    "--user-data-dir=`"$profilePath`"",
    'about:blank'
) -RedirectStandardError (Join-Path $artifactPath 'chrome-error.log')
Write-Output "Browser started; diagnostics: $artifactPath"
$socket = [Net.WebSockets.ClientWebSocket]::new()
$script:commandId = 0

function Invoke-Cdp([string]$Method, [hashtable]$Parameters = @{}) {
    $script:commandId++
    $id = $script:commandId
    $json = @{ id = $id; method = $Method; params = $Parameters } | ConvertTo-Json -Depth 30 -Compress
    $bytes = [Text.Encoding]::UTF8.GetBytes($json)
    $socket.SendAsync([ArraySegment[byte]]::new($bytes), [Net.WebSockets.WebSocketMessageType]::Text, $true, [Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    do {
        $stream = [IO.MemoryStream]::new()
        do {
            $buffer = [byte[]]::new(65536)
            $receive = $socket.ReceiveAsync([ArraySegment[byte]]::new($buffer), [Threading.CancellationToken]::None)
            if (-not $receive.Wait(10000)) {
                throw 'Browser response timed out.'
            }
            $chunk = $receive.Result
            $stream.Write($buffer, 0, $chunk.Count)
        } while (-not $chunk.EndOfMessage)
        $message = [Text.Encoding]::UTF8.GetString($stream.ToArray()) | ConvertFrom-Json
        $stream.Dispose()
    } while ($message.id -ne $id)
    if ($message.error) {
        throw ($message.error | ConvertTo-Json -Compress)
    }
    return $message.result
}

function Invoke-Js([string]$Expression) {
    $result = Invoke-Cdp 'Runtime.evaluate' @{ expression = $Expression; returnByValue = $true; awaitPromise = $true }
    if ($result.exceptionDetails) {
        throw ($result.exceptionDetails | ConvertTo-Json -Depth 10 -Compress)
    }
    return $result.result.value
}

function Open-Page([string]$Url) {
    Invoke-Cdp 'Page.navigate' @{ url = $Url } | Out-Null
    for ($attempt = 0; $attempt -lt 50; $attempt++) {
        Start-Sleep -Milliseconds 100
        if (Invoke-Js "location.href.replace('/admin/?', '/admin?') === '$Url'.replace('/admin/?', '/admin?') && document.readyState === 'complete'") {
            return
        }
    }
    $pageState = Invoke-Js 'JSON.stringify({url: location.href, state: document.readyState, title: document.title})'
    throw "Page did not finish loading: $Url; browser state: $pageState"
}

function Save-Preview([string]$Name) {
    Invoke-Js 'new Promise(resolve => setTimeout(resolve, 250))' | Out-Null
    $screenshot = Invoke-Cdp 'Page.captureScreenshot' @{ format = 'png'; captureBeyondViewport = $false }
    $target = Join-Path $artifactPath ($Name + '.png')
    [IO.File]::WriteAllBytes($target, [Convert]::FromBase64String($screenshot.data))
    Write-Output "Screenshot: $target"
}

try {
    $tabs = $null
    for ($attempt = 0; $attempt -lt 50; $attempt++) {
        try {
            if ($chrome.HasExited) {
                throw "Chrome exited with code $($chrome.ExitCode)."
            }
            $tabs = Invoke-RestMethod "http://127.0.0.1:$port/json/list" -TimeoutSec 1
            break
        } catch {
            if ($chrome.HasExited) {
                throw
            }
            Start-Sleep -Milliseconds 100
        }
    }
    if (-not $tabs) {
        throw 'Headless Chrome did not start.'
    }
    $pageTab = $tabs | Where-Object { $_.type -eq 'page' } | Select-Object -First 1
    $socket.ConnectAsync([uri]$pageTab.webSocketDebuggerUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    Invoke-Cdp 'Page.enable' | Out-Null
    Invoke-Cdp 'Runtime.enable' | Out-Null
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 1440; height = 1000; deviceScaleFactor = 1; mobile = $false } | Out-Null
    Open-Page "$BaseUrl/admin/"
    Save-Preview 'login-desktop'
    $passwordToggle = Invoke-Js "document.querySelector('[data-password-toggle]').click(); document.getElementById('password').type === 'text'"
    if (-not $passwordToggle) {
        throw 'Password visibility toggle failed.'
    }
    Invoke-Js "document.querySelector('[data-password-toggle]').click()" | Out-Null
    $emailJson = ConvertTo-Json $Email -Compress
    $passwordJson = ConvertTo-Json $Password -Compress
    Invoke-Js "document.getElementById('email').value = $emailJson; document.getElementById('password').value = $passwordJson; document.querySelector('form').requestSubmit()" | Out-Null
    for ($attempt = 0; $attempt -lt 50; $attempt++) {
        Start-Sleep -Milliseconds 100
        if (Invoke-Js "Boolean(document.querySelector('.sidebar')) && document.readyState === 'complete'") {
            break
        }
    }
    if (-not (Invoke-Js "Boolean(document.querySelector('.sidebar'))")) {
        throw 'Admin login failed.'
    }
    Save-Preview 'dashboard-desktop'
    foreach ($section in @('dashboard', 'projects', 'services', 'blog', 'messages', 'settings', 'seo')) {
        Open-Page "$BaseUrl/admin?section=$section"
        $valid = Invoke-Js "document.querySelector('h1') !== null && document.querySelectorAll('nav .nav-item.active').length === 1 && !/Fatal error|Warning:|Notice:/.test(document.body.innerText) && document.documentElement.scrollWidth <= innerWidth"
        if (-not $valid) {
            throw "Desktop layout failed: $section"
        }
        Write-Output "Desktop section passed: $section"
    }
    Open-Page "$BaseUrl/admin?section=projects"
    if (Invoke-Js "Boolean(document.getElementById('project-form'))") {
        throw 'Project list unexpectedly contains a create form.'
    }
    Save-Preview 'projects-desktop'
    $search = Invoke-Js @'
(() => {
    const input = document.querySelector('[data-table-search]');
    input.value = 'no-such-project-9384';
    input.dispatchEvent(new Event('input'));
    const filtered = Array.from(document.querySelectorAll('.admin-table tr')).filter(row => row.querySelector('td')).every(row => row.hidden);
    input.value = '';
    input.dispatchEvent(new Event('input'));
    return filtered;
})()
'@
    if (-not $search) {
        throw 'Project table search failed.'
    }
    Open-Page "$BaseUrl/admin?section=projects&new_project=1"
    if (-not (Invoke-Js "Boolean(document.querySelector('input[name=title]')) && Boolean(document.querySelector('textarea[name=work_scope]'))")) {
        throw 'New project form did not open.'
    }
    Open-Page "$BaseUrl/admin?section=blog&new_post=1"
    if (-not (Invoke-Js "Boolean(document.getElementById('post-form'))")) {
        throw 'New blog form did not open.'
    }
    Open-Page "$BaseUrl/admin?section=services"
    $serviceEdit = Invoke-Js "document.querySelector('a[href*=edit_service]').href"
    Open-Page $serviceEdit
    $repeaters = Invoke-Js @'
(() => {
    const group = document.querySelector('[data-repeater="questions"]');
    const rows = group.querySelector('[data-repeater-rows]');
    const before = rows.children.length;
    group.querySelector('[data-add-row]').click();
    const added = rows.children.length === before + 1;
    const nameCorrect = rows.lastElementChild.querySelector('input').name === `questions[${before}][0]`;
    rows.lastElementChild.querySelector('[data-remove-row]').click();
    return added && nameCorrect && rows.children.length === before;
})()
'@
    if (-not $repeaters) {
        throw 'Service repeater controls failed.'
    }
    Write-Output 'Password toggle, search, create forms and service repeaters passed.'
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
    foreach ($section in @('dashboard', 'projects', 'services', 'blog', 'messages', 'settings', 'seo')) {
        Open-Page "$BaseUrl/admin?section=$section"
        if (-not (Invoke-Js 'document.documentElement.scrollWidth <= innerWidth')) {
            throw "Mobile overflow: $section"
        }
        if (-not (Invoke-Js "document.querySelector('[data-menu-toggle]').click(); document.body.classList.contains('menu-open') && document.querySelector('[data-menu-toggle]').getAttribute('aria-expanded') === 'true'")) {
            throw 'Mobile menu did not open.'
        }
        Invoke-Js "document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}))" | Out-Null
        if (Invoke-Js "document.body.classList.contains('menu-open')") {
            throw 'Mobile menu did not close with Escape.'
        }
        Write-Output "Mobile section passed: $section"
    }
    Open-Page "$BaseUrl/admin?section=dashboard"
    Save-Preview 'dashboard-mobile'
    Invoke-Js "document.querySelector('[data-menu-toggle]').click()" | Out-Null
    Save-Preview 'sidebar-mobile'
    Invoke-Cdp 'Page.navigate' @{ url = "$BaseUrl/admin?logout=1" } | Out-Null
    for ($attempt = 0; $attempt -lt 50; $attempt++) {
        Start-Sleep -Milliseconds 100
        if (Invoke-Js "Boolean(document.getElementById('password'))") {
            break
        }
    }
} finally {
    $socket.Dispose()
    if (-not $chrome.HasExited) {
        Stop-Process -Id $chrome.Id -ErrorAction SilentlyContinue
    }
    # Only this test's isolated browser profile is eligible for cleanup.
    $resolvedProfile = [IO.Path]::GetFullPath($profilePath)
    $tempRoot = [IO.Path]::GetFullPath($env:TEMP).TrimEnd('\') + '\'
    if ($resolvedProfile.StartsWith($tempRoot, [StringComparison]::OrdinalIgnoreCase) -and (Split-Path $resolvedProfile -Leaf).StartsWith('insaat-admin-browser-')) {
        Remove-Item -LiteralPath $resolvedProfile -Recurse -Force -ErrorAction SilentlyContinue
    }
}
