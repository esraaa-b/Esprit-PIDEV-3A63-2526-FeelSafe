# Demarrer le serveur PHP Symfony
Set-Location $PSScriptRoot
Write-Host "Demarrage du serveur sur http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Arreter avec Ctrl+C" -ForegroundColor Yellow
php -S 127.0.0.1:8000 -t public
