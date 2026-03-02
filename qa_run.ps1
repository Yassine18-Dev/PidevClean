# QA Full Suite - ArenaMind
# Usage: .\qa_run.ps1

$script:passCount = 0
$script:failCount = 0

function Print-Header($title) {
    Write-Host ""
    Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray
    Write-Host "  $title" -ForegroundColor Cyan
    Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray
}

function Print-Result($label, $exitCode) {
    if ($exitCode -eq 0) {
        Write-Host "  [OK]  $label" -ForegroundColor Green
        $script:passCount++
    } else {
        Write-Host "  [FAIL] $label (exit $exitCode)" -ForegroundColor Red
        $script:failCount++
    }
}

Write-Host ""
Write-Host "*** ARENAMIND QA SUITE ***" -ForegroundColor Magenta
Write-Host "    $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor DarkGray


Print-Header "1. TESTS UNITAIRES (PHPUnit)"
php bin/phpunit --testdox
Print-Result "PHPUnit" $LASTEXITCODE


Print-Header "2. ANALYSE STATIQUE (PHPStan - niveau 5)"
vendor/bin/phpstan analyse src --level=5 --no-progress
Print-Result "PHPStan" $LASTEXITCODE


Print-Header "3. DOCTRINE - Validation du schema"
php bin/console doctrine:schema:validate
Print-Result "Doctrine Schema" $LASTEXITCODE


Print-Header "4. DOCTRINE - Inventaire des entites"
php bin/console doctrine:mapping:info
Print-Result "Doctrine Mapping" $LASTEXITCODE


Write-Host ""
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray
Write-Host "  RESUME FINAL" -ForegroundColor Yellow
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray
Write-Host "  [OK]   Suites reussies : $($script:passCount)" -ForegroundColor Green
if ($script:failCount -gt 0) {
    Write-Host "  [FAIL] Suites echouees : $($script:failCount)" -ForegroundColor Red
} else {
    Write-Host "  Tous les tests sont passes !" -ForegroundColor Magenta
}
Write-Host ""
