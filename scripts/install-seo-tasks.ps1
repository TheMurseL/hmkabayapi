param([string]$ProjectRoot = (Split-Path $PSScriptRoot -Parent), [string]$Php = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$resolvedRoot = (Resolve-Path -LiteralPath $ProjectRoot).Path
$auditScript = Join-Path $resolvedRoot 'scripts\seo-audit.php'
if (-not (Test-Path -LiteralPath $Php) -or -not (Test-Path -LiteralPath $auditScript)) {
    throw 'PHP veya SEO tarama dosyası bulunamadı.'
}
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 20) -MultipleInstances IgnoreNew
$principal = New-ScheduledTaskPrincipal -UserId ([Security.Principal.WindowsIdentity]::GetCurrent().Name) -LogonType Interactive -RunLevel Limited
$definitions = @(
    @{ Name = 'HMKabaYapi-SEO-Critical'; Arguments = ('"' + $auditScript + '" --critical'); Trigger = (New-ScheduledTaskTrigger -Daily -At '10:00') },
    @{ Name = 'HMKabaYapi-SEO-Weekly'; Arguments = ('"' + $auditScript + '"'); Trigger = (New-ScheduledTaskTrigger -Weekly -DaysOfWeek Monday -At '10:15') }
)
foreach ($definition in $definitions) {
    $existing = Get-ScheduledTask -TaskName $definition.Name -ErrorAction SilentlyContinue
    if ($existing) {
        if ($existing.Actions.Execute -ne $Php -or $existing.Actions.Arguments -ne $definition.Arguments) {
            throw ('Aynı isimde başka bir görev var; korunuyor: ' + $definition.Name)
        }
        Write-Output ('Görev zaten kurulu: ' + $definition.Name)
        continue
    }
    $action = New-ScheduledTaskAction -Execute $Php -Argument $definition.Arguments -WorkingDirectory $resolvedRoot
    Register-ScheduledTask -TaskName $definition.Name -Action $action -Trigger $definition.Trigger -Settings $settings -Principal $principal -Description 'H&M Kaba Yapı yerel HTTP/SEO kontrolü. Apache ve MySQL açık olmalıdır; sonuçlar panelde görünür.' | Out-Null
    Write-Output ('Kuruldu: ' + $definition.Name)
}
