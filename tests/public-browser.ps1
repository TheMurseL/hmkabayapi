param([string]$BaseUrl = 'http://localhost/insaat', [switch]$CalculatorsOnly, [switch]$NavigationOnly)
$ErrorActionPreference = 'Stop'
$chromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$profilePath = Join-Path $env:TEMP ('insaat-public-browser-' + [guid]::NewGuid().ToString('N'))
$artifactPath = Join-Path $env:TEMP ('insaat-public-preview-' + [guid]::NewGuid().ToString('N'))
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
    Invoke-Cdp 'Emulation.setFocusEmulationEnabled' @{ enabled = $true } | Out-Null
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 1440; height = 1000; deviceScaleFactor = 1; mobile = $false } | Out-Null
    foreach ($contactPath in @('', 'hakkimizda', 'projeler', 'iletisim')) {
        Open-Page "$BaseUrl/$contactPath"
        if (-not (Invoke-Js @'
(async () => {
    const assert = (condition, message) => { if (!condition) throw new Error(message); };
    const images = [...document.images].filter(image => image.src.includes('/uploads/'));
    await Promise.all(images.map(image => image.decode()));
    assert(images.every(image => image.naturalWidth > 0), 'Uploaded images must load');
    const floating = document.querySelector('.whatsapp-floating');
    const links = [...document.querySelectorAll('a[href^="https://wa.me/"]')];
    if (floating) {
        assert(getComputedStyle(floating).position === 'fixed', 'WhatsApp must stay accessible while scrolling');
        assert(links.length >= 2 && links.every(link => link.href === floating.href), 'WhatsApp destinations must use one setting');
        assert(floating.target === '_blank' && floating.rel.includes('noopener') && floating.rel.includes('noreferrer'), 'Safe WhatsApp new tab');
        assert(floating.getAttribute('aria-label'), 'Accessible WhatsApp label');
        const link = new URL(floating.href);
        assert(/^\/[1-9][0-9]{9,14}$/.test(link.pathname) && link.searchParams.get('text').includes('fotoğraf'), 'Phone and ready message');
        floating.focus();
        assert(document.activeElement === floating, 'Keyboard can focus WhatsApp');
    } else {
        assert(links.length === 0, 'Disabled WhatsApp leaves no stale links');
    }
    const districts = document.querySelector('.service-districts');
    if (districts) {
        districts.open = true;
        assert(districts.querySelectorAll('li').length > 0, 'Districts are available on demand');
        assert(districts.getBoundingClientRect().width <= document.querySelector('.service-areas .container').getBoundingClientRect().width, 'Regions fit container');
        districts.open = false;
    }
    assert(document.documentElement.scrollWidth <= innerWidth, 'Contact content must not overflow');
    return true;
})()
'@)) { throw "Contact content failed: $contactPath" }
    }
    Open-Page "$BaseUrl/"
    Save-Preview 'formwork-home-desktop'
    Invoke-Js 'document.querySelector(".service-districts").open = true; document.querySelector(".service-areas").scrollIntoView({block:"center",behavior:"instant"})' | Out-Null
    Save-Preview 'service-areas-desktop'
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
    Open-Page "$BaseUrl/iletisim"
    if (-not (Invoke-Js @'
(() => {
    const floating = document.querySelector('.whatsapp-floating');
    const input = document.querySelector('.contact-form input[name="name"]');
    if (floating) {
        if (getComputedStyle(floating).visibility !== 'visible') throw new Error('Mobile WhatsApp must be visible');
        input.focus();
        if (getComputedStyle(floating).visibility !== 'hidden') throw new Error('WhatsApp must not cover focused mobile form');
        input.blur();
        if (getComputedStyle(floating).visibility !== 'visible') throw new Error('WhatsApp returns after form focus');
        const rect = floating.getBoundingClientRect();
        if (rect.left < 0 || rect.right > innerWidth || rect.bottom > innerHeight) throw new Error('WhatsApp exceeds mobile viewport');
    }
    return document.documentElement.scrollWidth <= innerWidth;
})()
'@)) { throw 'Mobile contact content overflow' }
    Save-Preview 'contact-whatsapp-mobile'
    Open-Page "$BaseUrl/"
    Save-Preview 'formwork-home-mobile'
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 1440; height = 1000; deviceScaleFactor = 1; mobile = $false } | Out-Null
    Write-Output 'Uploaded photos, dynamic WhatsApp destinations, keyboard access, district list and mobile form visibility passed.'
    foreach ($path in @('', 'hakkimizda', 'istanbul-kaba-insaat', 'blog/kaba-insaat-teklifi-karsilastirma', 'iletisim', 'hesaplamalar/kaba-insaat-maliyeti', 'hesaplamalar/tugla-fiyat-hesaplama')) {
        Open-Page "$BaseUrl/$path"
        if (-not (Invoke-Js "document.querySelectorAll('h1').length === 1 && document.documentElement.scrollWidth <= innerWidth && !/Fatal error|Warning:|Notice:/.test(document.body.innerText)")) { throw "Desktop public page failed: $path" }
    }
    Open-Page "$BaseUrl/"
    $navigation = Invoke-Js @'
(async () => {
    const nav = document.querySelector('[data-nav]');
    const groups = [...nav.querySelectorAll('[data-nav-dropdown]')];
    const assert = (condition, message) => { if (!condition) throw new Error(message); };
    const settle = () => new Promise(resolve => setTimeout(resolve, 30));
    const base = new URL(document.querySelector('link[rel="canonical"]').href);
    const expected = ['', 'hakkimizda', 'projeler', 'blog', 'iletisim', 'istanbul-kaba-insaat', 'radye-temel-kalip-isciligi', 'subasman-kalip-isciligi', 'perde-istinat-duvari-kalip-isciligi', 'villa-kaba-insaat', 'duvar-ve-cati-uygulamalari', 'hesaplamalar/kaba-insaat-maliyeti', 'hesaplamalar/tugla-fiyat-hesaplama'];
    const links = [...nav.querySelectorAll('a')];
    assert(links.length === expected.length, 'All public landing pages must be in the main menu');
    for (const path of expected) {
        const href = new URL(path, base).href;
        assert(links.some(link => link.href === href), 'Missing main menu page: ' + path);
        assert([...document.querySelectorAll('.site-footer a')].some(link => link.href === href), 'Missing footer page: ' + path);
    }
    const responses = await Promise.all(links.map(link => fetch(link.href)));
    assert(responses.every(response => response.status === 200), 'A menu link does not return HTTP 200');
    assert(groups.length === 2 && groups[0].querySelectorAll('a').length === 6 && groups[1].querySelectorAll('a').length === 2, 'Service and calculator groups');
    assert(groups.every(group => !group.open), 'Dropdowns start closed');
    groups[0].querySelector('summary').click(); await settle();
    assert(groups[0].open && groups[0].querySelector('a').getBoundingClientRect().width > 0, 'Service dropdown opens');
    groups[1].querySelector('summary').click(); await settle();
    assert(!groups[0].open && groups[1].open, 'Opening calculators closes services');
    groups[1].querySelector('a').focus();
    document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
    assert(!groups[1].open && document.activeElement === groups[1].querySelector('summary'), 'Escape restores focus to the dropdown control');
    groups[0].querySelector('summary').click(); await settle();
    document.querySelector('main').click(); await settle();
    assert(groups.every(group => !group.open), 'Outside click closes dropdowns');
    groups[0].querySelector('summary').click(); await settle();
    groups[0].querySelector('a').focus(); await settle();
    assert(document.activeElement === groups[0].querySelector('a'), 'Dropdown link receives keyboard focus');
    nav.querySelector('.nav-cta').focus(); await settle();
    assert(!groups[0].open, 'Leaving a dropdown with keyboard focus closes it; focused: ' + document.activeElement.outerHTML);
    return true;
})()
'@
    if (-not $navigation) { throw 'Desktop navigation failed.' }
    # Exercise native summary controls with actual keyboard input.
    Invoke-Js "document.querySelector('[data-nav-dropdown] summary').focus()" | Out-Null
    Invoke-Cdp 'Input.dispatchKeyEvent' @{ type = 'keyDown'; key = 'Enter'; code = 'Enter'; windowsVirtualKeyCode = 13; text = "`r"; unmodifiedText = "`r" } | Out-Null
    Invoke-Cdp 'Input.dispatchKeyEvent' @{ type = 'keyUp'; key = 'Enter'; code = 'Enter'; windowsVirtualKeyCode = 13 } | Out-Null
    if (-not (Invoke-Js "document.querySelector('[data-nav-dropdown]').open")) { throw 'Dropdown keyboard activation failed.' }
    Invoke-Js "document.activeElement.blur(); document.querySelector('main').click()" | Out-Null
    Invoke-Cdp 'Input.dispatchMouseEvent' @{ type = 'mouseMoved'; x = 10; y = 10 } | Out-Null
    for ($groupIndex = 0; $groupIndex -lt 2; $groupIndex++) {
        $summary = Invoke-Js "(() => { const r = document.querySelectorAll('[data-nav-dropdown] summary')[$groupIndex].getBoundingClientRect(); return {x: r.x + r.width / 2, y: r.y + r.height / 2}; })()"
        Invoke-Cdp 'Input.dispatchMouseEvent' @{ type = 'mouseMoved'; x = $summary.x; y = $summary.y } | Out-Null
        if (-not (Invoke-Js "document.querySelectorAll('[data-nav-dropdown]')[$groupIndex].open")) { throw 'Desktop dropdown does not open on hover.' }
        Invoke-Cdp 'Input.dispatchMouseEvent' @{ type = 'mousePressed'; x = $summary.x; y = $summary.y; button = 'left'; clickCount = 1 } | Out-Null
        Invoke-Cdp 'Input.dispatchMouseEvent' @{ type = 'mouseReleased'; x = $summary.x; y = $summary.y; button = 'left'; clickCount = 1 } | Out-Null
        if (-not (Invoke-Js "document.querySelectorAll('[data-nav-dropdown]')[$groupIndex].open")) { throw 'Mouse click closes a hovered desktop dropdown.' }
        $gap = Invoke-Js "(() => { const group = document.querySelectorAll('[data-nav-dropdown]')[$groupIndex]; const summary = group.querySelector('summary').getBoundingClientRect(); const submenu = group.querySelector('.nav-submenu').getBoundingClientRect(); return {x: summary.x + summary.width / 2, y: (summary.bottom + submenu.top) / 2}; })()"
        Invoke-Cdp 'Input.dispatchMouseEvent' @{ type = 'mouseMoved'; x = $gap.x; y = $gap.y } | Out-Null
        if (-not (Invoke-Js "document.querySelectorAll('[data-nav-dropdown]')[$groupIndex].open")) { throw 'Desktop dropdown closes while crossing the gap.' }
        $submenuLink = Invoke-Js "(() => { const r = document.querySelectorAll('[data-nav-dropdown]')[$groupIndex].querySelector('a').getBoundingClientRect(); return {x: r.x + r.width / 2, y: r.y + r.height / 2}; })()"
        Invoke-Cdp 'Input.dispatchMouseEvent' @{ type = 'mouseMoved'; x = $submenuLink.x; y = $submenuLink.y } | Out-Null
        if (-not (Invoke-Js "document.querySelectorAll('[data-nav-dropdown]')[$groupIndex].open")) { throw 'Desktop submenu cannot be reached with the mouse.' }
        Save-Preview "navigation-hover-$groupIndex"
        Invoke-Cdp 'Input.dispatchMouseEvent' @{ type = 'mouseMoved'; x = 10; y = 600 } | Out-Null
        if (-not (Invoke-Js "!document.querySelectorAll('[data-nav-dropdown]')[$groupIndex].open")) { throw 'Desktop dropdown does not close on mouse leave.' }
    }
    Invoke-Js "document.querySelector('[data-nav-dropdown] summary').click()" | Out-Null
    Save-Preview 'navigation-desktop'
    foreach ($path in @('istanbul-kaba-insaat', 'radye-temel-kalip-isciligi', 'subasman-kalip-isciligi', 'perde-istinat-duvari-kalip-isciligi', 'villa-kaba-insaat', 'duvar-ve-cati-uygulamalari', 'hesaplamalar/kaba-insaat-maliyeti', 'hesaplamalar/tugla-fiyat-hesaplama')) {
        Open-Page "$BaseUrl/$path"
        if (-not (Invoke-Js "document.querySelectorAll('[data-nav] [aria-current=page]').length === 1 && document.querySelector('[data-nav] [aria-current=page]').href === document.querySelector('link[rel=canonical]').href && (document.querySelector('[data-nav] [aria-current=page]').parentElement.matches('[data-nav]') ? document.querySelector('[data-nav] summary.active') === null : document.querySelector('[data-nav] summary.active') !== null)")) { throw "Active menu link failed: $path" }
    }
    Open-Page "$BaseUrl/"
    foreach ($width in @(1201, 1200, 1060, 900, 390, 320)) {
        Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = $width; height = 844; deviceScaleFactor = 1; mobile = ($width -le 760) } | Out-Null
        $layout = Invoke-Js @'
(async () => {
    const nav = document.querySelector('[data-nav]');
    const toggle = document.querySelector('[data-menu]');
    const groups = [...nav.querySelectorAll('[data-nav-dropdown]')];
    const assert = (condition, message) => { if (!condition) throw new Error(message); };
    const settle = () => new Promise(resolve => setTimeout(resolve, 50));
    await settle();
    assert(document.documentElement.scrollWidth <= innerWidth, 'Horizontal page overflow');
    if (innerWidth > 1200) {
        assert(getComputedStyle(toggle).display === 'none' && getComputedStyle(nav).display !== 'none', 'Desktop navigation visibility');
        const brand = document.querySelector('.header-inner .brand').getBoundingClientRect();
        assert(brand.right < nav.getBoundingClientRect().left, 'Desktop menu overlaps the logo');
        for (const group of groups) {
            group.querySelector('summary').click(); await settle();
            const rect = group.querySelector('.nav-submenu').getBoundingClientRect();
            assert(rect.left >= 0 && rect.right <= innerWidth, 'Desktop submenu leaves the viewport');
        }
        document.querySelector('main').click();
    } else {
        assert(getComputedStyle(toggle).display !== 'none' && getComputedStyle(nav).display === 'none', 'Mobile menu starts closed');
        toggle.click();
        assert(nav.classList.contains('is-open') && toggle.getAttribute('aria-expanded') === 'true', 'Mobile menu opens');
        for (const group of groups) {
            group.querySelector('summary').click(); await settle();
            assert(group.open && group.querySelector('a').getBoundingClientRect().width > 0, 'Mobile submenu opens');
            assert(document.documentElement.scrollWidth <= innerWidth, 'Open mobile submenu overflows');
        }
        document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
        assert(groups.every(group => !group.open) && nav.classList.contains('is-open'), 'First Escape closes the submenu');
        document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
        assert(!nav.classList.contains('is-open') && toggle.getAttribute('aria-expanded') === 'false' && document.activeElement === toggle, 'Second Escape closes the mobile menu');
        toggle.click(); groups[0].querySelector('summary').click(); await settle();
        document.querySelector('main').click();
        assert(!nav.classList.contains('is-open') && groups.every(group => !group.open), 'Outside click closes the mobile menu');
    }
    return true;
})()
'@
        if (-not $layout) { throw "Navigation layout failed at $width px." }
    }
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
    Invoke-Js "document.querySelector('[data-menu]').click(); document.querySelector('[data-nav-dropdown] summary').click()" | Out-Null
    Save-Preview 'navigation-mobile'
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 1440; height = 1000; deviceScaleFactor = 1; mobile = $false } | Out-Null
    Write-Output 'All menu destinations, active links, mouse hover, dropdown keyboard controls and responsive navigation passed.'
    if ($NavigationOnly) { return }
    Open-Page "$BaseUrl/hesaplamalar/kaba-insaat-maliyeti"
    $cost = Invoke-Js @'
(() => {
    const root = document.querySelector('[data-labor-calculator]');
    const assert = (condition, message) => { if (!condition) throw new Error(message); };
    assert(root, 'Labor calculator is present');
    const form = root.querySelector('[data-labor-form]');
    const field = name => form.elements.namedItem(name);
    const output = name => root.querySelector(`[data-labor-out="${name}"]`).textContent;
    const quoteArea = () => new URL(root.querySelector('[data-labor-quote]').href).searchParams.get('area');
    const set = (name, value) => {
        const control = field(name);
        if (control.type === 'checkbox') control.checked = value;
        else control.value = value;
        control.dispatchEvent(new Event(control.matches('select, input[type="checkbox"]') ? 'change' : 'input', {bubbles: true}));
    };
    let events = 0;
    document.addEventListener('site:measurement', event => { if (event.detail.name === 'calculator_use') events++; });
    assert(field('formworkRate').value === '' && field('ironRate').value === '', 'Labor rates start empty');
    set('mode', 'building');
    set('apartments', '8'); set('perFloor', '2'); set('apartmentArea', '100');
    set('commonArea', '120'); set('basementEnabled', true); set('basementArea', '80');
    set('formworkBasis', 'building'); set('formworkRate', '500');
    set('ironEnabled', true); set('ironBasis', 'area'); set('ironRate', '200');
    set('vatEnabled', true); set('vatRate', '20');
    assert(output('area') === '1.000 m²' && output('building') === '4 kat / 8 daire', 'Apartment and common areas are added once');
    assert(output('formworkCost').includes('500.000') && output('ironCost').includes('200.000'), 'Separate labor costs');
    assert(output('subtotal').includes('700.000') && output('vat').includes('140.000'), 'Labor subtotal and VAT');
    assert(output('total').includes('840.000') && output('perApartment').includes('105.000'), 'Building estimate total and per-apartment cost');
    assert(quoteArea() === '1000', 'Quote uses the total building area');
    assert(events === 0, 'Input recalculation does not emit calculator_use');
    form.requestSubmit();
    assert(events === 1, 'Valid form submission emits one calculator_use');
    set('mode', 'manual'); set('area', '500');
    set('ironEnabled', false); set('vatEnabled', false); set('formworkRate', '1000');
    assert(output('total').includes('500.000') && quoteArea() === '500', 'Manual area without iron or added VAT');
    assert(root.querySelector('[data-labor-iron-line]').hidden && field('ironRate').disabled, 'Disabled iron work is excluded');
    assert(root.querySelector('[data-labor-apartment-fact]').hidden && output('perApartment') === '—', 'Manual mode does not invent an apartment count');
    assert(events === 1, 'Mode and option changes do not emit another use event');
    set('formworkBasis', 'surface');
    assert(field('formworkRate').value === '', 'Changing the metraj basis clears its previous unit price');
    assert(!field('formworkArea').disabled && output('total') === '—' && quoteArea() === null, 'Missing surface metraj clears the stale estimate and quote area');
    set('formworkRate', '400'); set('formworkArea', '0');
    form.requestSubmit();
    assert(output('total') === '—' && quoteArea() === null, 'Invalid surface metraj cannot retain a previous estimate');
    assert(!root.querySelector('[data-labor-error]').hidden && field('formworkArea').getAttribute('aria-invalid') === 'true', 'Invalid submission identifies the surface field');
    assert(events === 1, 'Invalid submission does not emit calculator_use');
    set('formworkArea', '1500');
    assert(output('total').includes('600.000') && quoteArea() === '500', 'Explicit surface metraj is independent from the building quote area');
    assert(events === 1, 'Recovering through input recalculation does not emit another use event');
    return true;
})()
'@
    if (-not $cost) { throw 'Labor calculator formulas, modes, quote context or measurement event failed.' }
    foreach ($width in @(390, 320)) {
        Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = $width; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
        if (-not (Invoke-Js 'document.documentElement.scrollWidth <= innerWidth')) { throw "Labor calculator overflows at $width px." }
    }
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 1440; height = 1000; deviceScaleFactor = 1; mobile = $false } | Out-Null
    Open-Page "$BaseUrl/hesaplamalar/tugla-fiyat-hesaplama"
    $brick = Invoke-Js @'
(() => {
    const field = selector => document.querySelector(selector);
    const set = (selector, value, event = 'input') => {
        field(selector).value = value;
        field(selector).dispatchEvent(new Event(event, {bubbles: true}));
    };
    const assert = (condition, message) => { if (!condition) throw new Error(message); };
    const contains = (selector, text) => field(selector).textContent.includes(text);
    const quantity = (expected, message) => assert(field('[data-calc-total]').textContent === expected + ' adet', message);
    const invalid = message => {
        assert(!field('[data-brick-error]').hidden && field('[data-calc-total]').textContent === '—', message);
        assert(!new URL(field('[data-quote-link]').href).searchParams.has('area'), message + ': stale quote area');
    };
    let events = 0;
    document.addEventListener('site:measurement', e => { if (e.detail.name === 'calculator_use') events++; });
    set('[data-brick-price]', ''); set('[data-brick-labor]', '');
    quantity('394', 'Default 5 x 3 wall, 25/m2, 5% waste without prices');
    assert(contains('[data-brick-cost]', 'İki birim fiyatı'), 'Missing prices cannot imply a zero total');
    field('[data-brick-has-openings]').click();
    quantity('339', 'One 1 x 2.1 door is deducted from 15 m2');
    assert(contains('[data-brick-net]', '12,9') && contains('[data-brick-base]', '323') && contains('[data-brick-extra]', '16'), 'Area and quantity breakdown');
    set('[data-brick-price]', '10');
    assert(contains('[data-brick-material-cost]', '3.390') && contains('[data-brick-cost]', 'İki birim fiyatı'), 'Material-only cost is not a total');
    set('[data-brick-labor]', '20');
    assert(contains('[data-brick-labor-cost]', '258') && contains('[data-brick-cost]', '3.648'), 'Labor uses net area without waste');
    field('[data-brick-add-opening]').click();
    const second = document.querySelectorAll('.calc-opening')[1];
    for (const [selector, value] of [['[data-opening-width]', '1.2'], ['[data-opening-height]', '1.5'], ['[data-opening-count]', '2']]) {
        second.querySelector(selector).value = value;
        second.querySelector(selector).dispatchEvent(new Event('input', {bubbles: true}));
    }
    quantity('245', 'Door plus two windows: areas must be summed individually');
    assert(contains('[data-brick-net]', '9,3') && contains('[data-brick-cost]', '2.636'), 'Multiple opening costs');
    second.querySelector('[data-brick-remove-opening]').click();
    quantity('339', 'Removing an opening recalculates');
    set('[data-opening-count]', '10'); invalid('Openings larger than the wall');
    set('[data-opening-count]', '1.5'); invalid('Fractional opening counts');
    set('[data-opening-count]', '1');
    set('[data-opening-width]', '0'); invalid('Zero opening widths');
    set('[data-opening-width]', '5'); set('[data-opening-height]', '3');
    quantity('0', 'An opening equal to the wall gives zero bricks');
    assert(contains('[data-brick-cost]', '0 ₺'), 'Zero net area has zero cost');
    set('[data-brick-width]', '0.1'); set('[data-brick-height]', '9');
    set('[data-opening-width]', '0.3'); set('[data-opening-height]', '3');
    quantity('0', 'Equivalent decimal areas must not leave one phantom brick');
    set('[data-brick-width]', '5'); set('[data-brick-height]', '3');
    field('[data-brick-remove-opening]').click();
    quantity('394', 'Removing the last opening resets its toggle');
    assert(!field('[data-brick-has-openings]').checked && field('#brick-openings').hidden, 'Last opening cleanup');
    set('[data-brick-width]', ''); invalid('Empty wall width');
    set('[data-brick-width]', '-5'); invalid('Negative wall width');
    set('[data-brick-width]', '5');
    set('[data-brick-mode]', 'area', 'change');
    set('[data-brick-type]', 'custom', 'change');
    assert(!field('[data-brick-size]').readOnly, 'Custom consumption is editable');
    set('[data-brick-size]', '16'); set('[data-brick-area]', '100');
    quantity('1.680', '100 m2, 16/m2 and 5% waste');
    assert(contains('[data-brick-cost]', '18.800'), 'Original 18,800 TRY scenario');
    assert(new URL(field('[data-quote-link]').href).searchParams.get('area') === '100', 'Quote uses net area');
    assert(events === 0, 'Automatic recalculation does not emit a use event');
    field('[data-calculate]').click();
    assert(events === 1, 'Calculate click emits one use event');
    set('[data-brick-type]', '13.5', 'change');
    quantity('2.625', '13.5 standard consumption is 25/m2');
    assert(field('[data-brick-size]').readOnly, 'Preset consumption cannot silently change');
    set('[data-brick-bond]', 'alternate', 'change');
    quantity('3.675', '13.5 alternate consumption is 35/m2');
    set('[data-brick-waste]', '0'); quantity('3.500', 'Zero waste');
    for (const [type, standard, alternate] of [['8.5','2.500','6.200'], ['10','2.500','4.000'], ['10-long','2.000','4.000']]) {
        set('[data-brick-bond]', 'standard', 'change');
        set('[data-brick-type]', type, 'change');
        quantity(standard, type + ' standard preset');
        set('[data-brick-bond]', 'alternate', 'change');
        quantity(alternate, type + ' alternate preset');
    }
    set('[data-brick-type]', 'custom', 'change');
    set('[data-brick-size]', '25'); set('[data-brick-area]', '3.2'); set('[data-brick-waste]', '5');
    quantity('84', 'Floating point rounding must not buy an 85th brick');
    set('[data-brick-area]', '1.01'); set('[data-brick-waste]', '0');
    quantity('26', 'Fractional bricks are rounded up');
    set('[data-brick-price]', '0.25'); set('[data-brick-labor]', '0.5');
    assert(contains('[data-brick-material-cost]', '6,5') && contains('[data-brick-labor-cost]', '0,51') && contains('[data-brick-cost]', '7,01'), 'Decimal prices and final monetary rounding');
    set('[data-brick-price]', '-1');
    quantity('26', 'Invalid price does not invalidate brick quantity');
    assert(!field('[data-brick-error]').hidden && contains('[data-brick-material-cost]', '—'), 'Invalid price has no material cost');
    set('[data-brick-price]', '0'); set('[data-brick-labor]', '0');
    assert(contains('[data-brick-cost]', '0 ₺') && field('[data-brick-error]').hidden, 'Explicit free prices are valid');
    set('[data-brick-size]', ''); invalid('Missing consumption');
    set('[data-brick-size]', '25');
    set('[data-brick-waste]', '51'); invalid('Waste above 50%');
    set('[data-brick-waste]', '-1'); invalid('Negative waste');
    set('[data-brick-waste]', ''); invalid('Missing waste');
    set('[data-brick-waste]', '5');
    set('[data-brick-area]', '0'); invalid('Zero direct area');
    set('[data-brick-area]', '1000001'); invalid('Oversized direct area');
    set('[data-brick-area]', '100');
    field('[data-brick-has-openings]').checked = true;
    field('[data-brick-has-openings]').dispatchEvent(new Event('change', {bubbles:true}));
    quantity('2.625', 'Direct net area never deducts hidden openings a second time');
    set('[data-brick-mode]', 'dimensions', 'change');
    set('[data-brick-type]', '13.5', 'change');
    set('[data-brick-bond]', 'standard', 'change');
    set('[data-brick-price]', '10'); set('[data-brick-labor]', '20');
    quantity('339', 'Dimensions restored with the original door');
    assert(new URL(field('[data-quote-link]').href).searchParams.get('area') === '12.9', 'Quote area has no floating point residue');
    return true;
})()
'@
    if (-not $brick) { throw 'Brick formula failed.' }
    Save-Preview 'brick-calculator-desktop'
    Invoke-Js "document.querySelector('.calc-result').scrollIntoView({block: 'center'})" | Out-Null
    Save-Preview 'brick-calculator-result-desktop'
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
    if (-not (Invoke-Js "document.documentElement.scrollWidth <= innerWidth")) { throw 'Mobile brick calculator overflow.' }
    Invoke-Js "document.querySelector('.calculator-card').scrollIntoView({block: 'start'})" | Out-Null
    Save-Preview 'brick-calculator-mobile'
    Invoke-Js "document.querySelector('.calc-result').scrollIntoView({block: 'center'})" | Out-Null
    Save-Preview 'brick-calculator-result-mobile'
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 320; height = 740; deviceScaleFactor = 1; mobile = $true } | Out-Null
    if (-not (Invoke-Js "document.documentElement.scrollWidth <= innerWidth")) { throw 'Narrow mobile brick calculator overflow.' }
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 1440; height = 1000; deviceScaleFactor = 1; mobile = $false } | Out-Null
    Write-Output 'Brick dimensions, openings, presets, rounding, prices, validation and mobile checks passed.'
    if ($CalculatorsOnly) { return }
    Open-Page "$BaseUrl/"
    $consent = Invoke-Js @'
(async () => {
    localStorage.removeItem('site-measurement-consent');
    window.siteMeasurement = {id: 'G-TEST0000', path: '/fixture'};
    const panel = document.createElement('div');
    panel.setAttribute('data-measurement-choice', '');
    panel.innerHTML = '<button data-measurement-accept>accept</button><button data-measurement-reject>reject</button>';
    const preference = document.createElement('button'); preference.setAttribute('data-measurement-preference', '');
    document.body.append(panel, preference);
    const originalAppend = document.head.append.bind(document.head);
    let remoteCount = 0;
    document.head.append = (...nodes) => { if(nodes.some(n => n.src?.includes('googletagmanager.com'))) { remoteCount++; return; } originalAppend(...nodes); };
    try {
        const code = await (await fetch('assets/js/measurement.js')).text();
        (0, eval)(code);
        if(window.gtag || remoteCount !== 0 || panel.hidden) return false;
        panel.querySelector('[data-measurement-reject]').click();
        if(remoteCount !== 0) return false;
        preference.click(); panel.querySelector('[data-measurement-accept]').click();
        if(remoteCount !== 1 || typeof window.gtag !== 'function') return false;
        document.dispatchEvent(new CustomEvent('site:measurement', {detail:{name:'calculator_use',parameters:{calculator_type:'test'}}}));
        if(!window.dataLayer.some(a => a[0] === 'event' && a[1] === 'calculator_use')) return false;
        preference.click(); panel.querySelector('[data-measurement-reject]').click();
        const before = window.dataLayer.length;
        document.dispatchEvent(new CustomEvent('site:measurement', {detail:{name:'generate_lead',parameters:{lead_type:'test'}}}));
        return window['ga-disable-G-TEST0000'] === true && before === window.dataLayer.length;
    } finally {
        document.head.append = originalAppend;
        localStorage.removeItem('site-measurement-consent'); panel.remove(); preference.remove();
    }
})()
'@
    if (-not $consent) { throw 'Measurement consent or withdrawal failed.' }
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
    foreach ($path in @('', 'hakkimizda', 'istanbul-kaba-insaat', 'blog/kaba-insaat-teklifi-karsilastirma', 'iletisim', 'hesaplamalar/kaba-insaat-maliyeti', 'hesaplamalar/tugla-fiyat-hesaplama')) {
        Open-Page "$BaseUrl/$path"
        if (-not (Invoke-Js "document.documentElement.scrollWidth <= innerWidth")) { throw "Mobile overflow: $path" }
        Write-Output "Mobile page passed: /$path"
    }
    Open-Page "$BaseUrl/"
    Invoke-Js "document.querySelector('[data-menu]').click()" | Out-Null
    if (-not (Invoke-Js "document.querySelector('[data-nav]').classList.contains('is-open') && document.querySelector('[data-menu]').getAttribute('aria-expanded') === 'true'")) { throw 'Public mobile menu failed.' }
    Invoke-Js "document.querySelector('[data-menu]').click()" | Out-Null
    Save-Preview 'home-mobile'
    Open-Page "$BaseUrl/hesaplamalar/kaba-insaat-maliyeti"
    Save-Preview 'calculator-mobile'
    Open-Page "$BaseUrl/iletisim"
    Save-Preview 'contact-mobile'
    $performance = Invoke-Js "JSON.stringify({navigation: performance.getEntriesByType('navigation').map(n => ({ttfb_ms: Math.round(n.responseStart-n.requestStart), dom_content_loaded_ms: Math.round(n.domContentLoadedEventEnd-n.startTime)})), width: innerWidth, local_lab_only: true})"
    Write-Output "Local browser timing (not field CWV): $performance"
    Write-Output 'Public desktop/mobile, calculators, quote context and event checks passed.'
    Invoke-Cdp 'Network.enable' | Out-Null
    Invoke-Cdp 'Network.setCacheDisabled' @{ cacheDisabled = $true } | Out-Null
    Invoke-Cdp 'Network.emulateNetworkConditions' @{ offline = $false; latency = 150; downloadThroughput = 200000; uploadThroughput = 93750; connectionType = 'cellular4g' } | Out-Null
    Invoke-Cdp 'Emulation.setCPUThrottlingRate' @{ rate = 4 } | Out-Null
    Invoke-Cdp 'Page.addScriptToEvaluateOnNewDocument' @{ source = @'
window.__localVitals = {lcp_ms: null, cls: 0};
new PerformanceObserver(list => { for(const e of list.getEntries()) window.__localVitals.lcp_ms = Math.round(e.startTime); }).observe({type:'largest-contentful-paint', buffered:true});
new PerformanceObserver(list => { for(const e of list.getEntries()) if(!e.hadRecentInput) window.__localVitals.cls += e.value; }).observe({type:'layout-shift', buffered:true});
'@ } | Out-Null
    $labResults = @()
    foreach ($labPage in @('', 'istanbul-kaba-insaat', 'hesaplamalar/kaba-insaat-maliyeti')) {
        Open-Page "$BaseUrl/$labPage"
        Invoke-Js 'new Promise(resolve => setTimeout(resolve, 2000))' | Out-Null
        $metrics = Invoke-Js '({...window.__localVitals, ttfb_ms: Math.round(performance.getEntriesByType("navigation")[0].responseStart), cls: Number(window.__localVitals.cls.toFixed(4))})'
        $labResults += @{ path = $labPage; metrics = $metrics }
    }
    $labReport = @{ measured_at = (Get-Date).ToString('o'); viewport = '390x844'; cpu_slowdown = 4; latency_ms = 150; download_bytes_per_second = 200000; scope = 'Local laboratory observation; not field Core Web Vitals; INP not measured'; pages = $labResults }
    $reportDirectory = Join-Path (Split-Path $PSScriptRoot -Parent) '.private\reports'
    New-Item -ItemType Directory -Path $reportDirectory -Force | Out-Null
    $labReport | ConvertTo-Json -Depth 10 | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $reportDirectory 'performance-local.json')
    Write-Output ('Throttled local laboratory: ' + ($labResults | ConvertTo-Json -Depth 6 -Compress))

} finally {
    $socket.Dispose()
    if (-not $chrome.HasExited) {
        Stop-Process -Id $chrome.Id -ErrorAction SilentlyContinue
    }
    # Only this test's isolated browser profile is eligible for cleanup.
    $resolvedProfile = [IO.Path]::GetFullPath($profilePath)
    $tempRoot = [IO.Path]::GetFullPath($env:TEMP).TrimEnd('\') + '\'
    if ($resolvedProfile.StartsWith($tempRoot, [StringComparison]::OrdinalIgnoreCase) -and (Split-Path $resolvedProfile -Leaf).StartsWith('insaat-public-browser-')) {
        Remove-Item -LiteralPath $resolvedProfile -Recurse -Force -ErrorAction SilentlyContinue
    }
}
