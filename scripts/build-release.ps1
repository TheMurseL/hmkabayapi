[CmdletBinding()]
param(
    [string]$OutputDirectory = '.private\releases'
)

# Build a code/media archive only. This script does not deploy, export the
# database, change configuration, remove backups, or delete/move any files.
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression

$workspaceRoot = [System.IO.Path]::GetFullPath((Split-Path $PSScriptRoot -Parent)).TrimEnd('\', '/')
$workspacePrefix = $workspaceRoot + [System.IO.Path]::DirectorySeparatorChar
$pathComparison = [System.StringComparison]::OrdinalIgnoreCase

function Assert-WorkspacePath {
    param([Parameter(Mandatory = $true)][string]$Path)

    $fullPath = [System.IO.Path]::GetFullPath($Path)
    if (-not $fullPath.Equals($workspaceRoot, $pathComparison) -and -not $fullPath.StartsWith($workspacePrefix, $pathComparison)) {
        throw ('Path must stay inside the project workspace: ' + $fullPath)
    }

    # Check every existing component before following or creating a path.
    $components = @($workspaceRoot)
    if ($fullPath.Length -gt $workspaceRoot.Length) {
        $relativePath = $fullPath.Substring($workspacePrefix.Length)
        $currentPath = $workspaceRoot
        foreach ($segment in ($relativePath -split '[\\/]')) {
            if ($segment -eq '') { continue }
            $currentPath = Join-Path $currentPath $segment
            $components += $currentPath
        }
    }
    foreach ($component in $components) {
        $item = Get-Item -LiteralPath $component -Force -ErrorAction SilentlyContinue
        if ($null -ne $item -and ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
            throw ('Reparse points are not allowed in release paths: ' + $component)
        }
    }
    return $fullPath
}

function Get-ReleaseRelativePath {
    param([Parameter(Mandatory = $true)][string]$Path)
    return $Path.Substring($workspacePrefix.Length).Replace('\', '/')
}

function Test-ExcludedReleasePath {
    param([Parameter(Mandatory = $true)][string]$RelativePath)

    foreach ($segment in ($RelativePath -split '/')) {
        if ($segment.StartsWith('.') -and $RelativePath -ne '.htaccess') { return $true }
        if ($segment -match '^(?:tests?|scripts|node_modules|vendor|releases)$') { return $true }
        if ($segment -match '(?:^|[-_. ])(?:backups?|bak|old|orig|original|cop(?:y|ies)|archives?|dumps?|credentials?|secrets?|passwords?|restore|recovery|recovered|yedek(?:ler)?|kurtarma)(?:[-_. ]|$)') { return $true }
        if ($segment -match '\.(?:sql|md|csv|log|json|ini|env|bak|old|orig|tmp|temp|zip|7z|gz|tar)(?:\.|$)') { return $true }
    }
    return $false
}

function Add-ReleaseDirectoryFiles {
    param(
        [Parameter(Mandatory = $true)][string]$Directory,
        [Parameter(Mandatory = $true)][string[]]$Extensions,
        [Parameter(Mandatory = $true)][System.Collections.Generic.List[object]]$Files
    )

    $pending = New-Object 'System.Collections.Generic.Stack[string]'
    $pending.Push((Assert-WorkspacePath $Directory))
    while ($pending.Count -gt 0) {
        $currentDirectory = Assert-WorkspacePath ($pending.Pop())
        foreach ($item in (Get-ChildItem -LiteralPath $currentDirectory -Force)) {
            # Do not recurse through junctions/symlinks, even when their names
            # would otherwise match the allowlist.
            if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
                throw ('Reparse point found; release was stopped: ' + $item.FullName)
            }
            $relativePath = Get-ReleaseRelativePath $item.FullName
            if (Test-ExcludedReleasePath $relativePath) { continue }
            if ($item.PSIsContainer) {
                $pending.Push($item.FullName)
            } elseif ($Extensions -contains $item.Extension.ToLowerInvariant()) {
                $Files.Add([PSCustomObject]@{ FullPath = $item.FullName; RelativePath = $relativePath })
            }
        }
    }
}

Assert-WorkspacePath $workspaceRoot | Out-Null
if ([string]::IsNullOrWhiteSpace($OutputDirectory)) { throw 'OutputDirectory cannot be empty.' }
$outputPath = if ([System.IO.Path]::IsPathRooted($OutputDirectory)) {
    Assert-WorkspacePath $OutputDirectory
} else {
    Assert-WorkspacePath (Join-Path $workspaceRoot $OutputDirectory)
}

$releaseFiles = New-Object 'System.Collections.Generic.List[object]'
foreach ($name in @('index.php', '.htaccess')) {
    $sourcePath = Assert-WorkspacePath (Join-Path $workspaceRoot $name)
    if (-not [System.IO.File]::Exists($sourcePath)) { throw ('Required release file is missing: ' + $name) }
    $releaseFiles.Add([PSCustomObject]@{ FullPath = $sourcePath; RelativePath = $name })
}
foreach ($name in @('admin', 'app', 'config', 'partials', 'views')) {
    $directoryPath = Assert-WorkspacePath (Join-Path $workspaceRoot $name)
    if (-not [System.IO.Directory]::Exists($directoryPath)) { throw ('Required release directory is missing: ' + $name) }
    Add-ReleaseDirectoryFiles -Directory $directoryPath -Extensions @('.php') -Files $releaseFiles
}
Add-ReleaseDirectoryFiles -Directory (Join-Path $workspaceRoot 'assets') -Extensions @('.css', '.js', '.svg', '.png', '.jpg', '.jpeg', '.webp', '.woff', '.woff2', '.ttf', '.ico') -Files $releaseFiles
$uploadsPath = Assert-WorkspacePath (Join-Path $workspaceRoot 'uploads')
if ([System.IO.Directory]::Exists($uploadsPath)) {
    Add-ReleaseDirectoryFiles -Directory $uploadsPath -Extensions @('.jpg', '.jpeg', '.png', '.webp') -Files $releaseFiles
}

$stamp = [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ')
$archivePath = Assert-WorkspacePath (Join-Path $outputPath ('insaat-site-' + $stamp + '.zip'))
$manifestPath = Assert-WorkspacePath (Join-Path $outputPath ('insaat-site-' + $stamp + '.manifest.json'))
if ([System.IO.File]::Exists($archivePath) -or [System.IO.File]::Exists($manifestPath)) {
    throw 'A release with this timestamp already exists; existing files will not be overwritten.'
}
[System.IO.Directory]::CreateDirectory($outputPath) | Out-Null
Assert-WorkspacePath $outputPath | Out-Null

$fileManifest = New-Object 'System.Collections.Generic.List[object]'
$sourceBytes = [long]0
$archiveStream = $null
$archive = $null
try {
    # CreateNew also prevents overwriting a file created after the check above.
    $archiveStream = [System.IO.File]::Open($archivePath, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::Write, [System.IO.FileShare]::None)
    $archive = New-Object System.IO.Compression.ZipArchive($archiveStream, [System.IO.Compression.ZipArchiveMode]::Create, $true)
    foreach ($file in ($releaseFiles | Sort-Object RelativePath)) {
        $safeSourcePath = Assert-WorkspacePath $file.FullPath
        $source = $null
        $entryStream = $null
        $hasher = $null
        try {
            # Holding this read-only handle disallows concurrent writes while
            # hashing and adding the file directly to ZipArchive.
            $source = [System.IO.File]::Open($safeSourcePath, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::Read)
            $hasher = [System.Security.Cryptography.SHA256]::Create()
            $fileHash = [BitConverter]::ToString($hasher.ComputeHash($source)).Replace('-', '').ToLowerInvariant()
            $source.Position = 0
            $entry = $archive.CreateEntry($file.RelativePath, [System.IO.Compression.CompressionLevel]::Optimal)
            $entryStream = $entry.Open()
            $source.CopyTo($entryStream)
            $sourceBytes += $source.Length
            $fileManifest.Add([PSCustomObject]@{ path = $file.RelativePath; bytes = $source.Length; sha256 = $fileHash })
        } finally {
            if ($null -ne $entryStream) { $entryStream.Dispose() }
            if ($null -ne $source) { $source.Dispose() }
            if ($null -ne $hasher) { $hasher.Dispose() }
        }
    }
} finally {
    if ($null -ne $archive) { $archive.Dispose() }
    if ($null -ne $archiveStream) { $archiveStream.Dispose() }
}

$archiveHash = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant()
$archiveBytes = (Get-Item -LiteralPath $archivePath).Length
$manifest = [ordered]@{
    generated_at_utc = [DateTime]::UtcNow.ToString('o')
    path = Get-ReleaseRelativePath $archivePath
    file_count = $fileManifest.Count
    source_bytes = $sourceBytes
    archive_bytes = $archiveBytes
    sha256 = $archiveHash
    scope = 'Code and media only; no database, backups, credentials dumps, tests, scripts, or deployment.'
    files = @($fileManifest.ToArray())
}
$manifestBytes = (New-Object System.Text.UTF8Encoding($false)).GetBytes(($manifest | ConvertTo-Json -Depth 5))
$manifestStream = $null
try {
    Assert-WorkspacePath $manifestPath | Out-Null
    $manifestStream = [System.IO.File]::Open($manifestPath, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::Write, [System.IO.FileShare]::None)
    $manifestStream.Write($manifestBytes, 0, $manifestBytes.Length)
} finally {
    if ($null -ne $manifestStream) { $manifestStream.Dispose() }
}

[PSCustomObject]@{
    Archive = $archivePath
    Manifest = $manifestPath
    FileCount = $fileManifest.Count
    SourceBytes = $sourceBytes
    ArchiveBytes = $archiveBytes
    SHA256 = $archiveHash
}
