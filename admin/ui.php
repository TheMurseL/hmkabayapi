<?php

declare(strict_types=1);

function admin_icon(string $name): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'building' => '<path d="M4 21V7l8-4 8 4v14M9 21v-4h6v4M8 9h1m6 0h1M8 13h1m6 0h1M2 21h20"/>',
        'layers' => '<path d="m12 3 10 6-10 6L2 9l10-6ZM2 13l10 6 10-6M2 17l10 6 10-6"/>',
        'file' => '<path d="M14 2H5v20h14V7l-5-5Zm0 0v5h5M8 12h8M8 16h8"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'settings' => '<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3"/><circle cx="15" cy="17" r="3"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'external' => '<path d="M14 3h7v7m0-7L10 14M10 3H3v18h18v-7"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'logout' => '<path d="M9 3H3v18h6M9 12h12m-5-5 5 5-5 5"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
    ];

    return '<svg class="admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['grid']) . '</svg>';
}

function admin_asset(string $path): string
{
    return url($path) . '?v=' . filemtime(__DIR__ . '/../' . $path);
}
