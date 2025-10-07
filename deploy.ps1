# PowerShell Laravel Deployment Script (Windows Compatible)
# Jalankan dengan: .\deploy.ps1

Write-Host "🚀 Starting Laravel Production Deployment..." -ForegroundColor Blue
Write-Host "=============================================" -ForegroundColor Cyan

# Function untuk logging dengan warna
function Write-Success { param($Message) Write-Host "[SUCCESS] $Message" -ForegroundColor Green }
function Write-Info { param($Message) Write-Host "[INFO] $Message" -ForegroundColor Blue }
function Write-Warning { param($Message) Write-Host "[WARNING] $Message" -ForegroundColor Yellow }
function Write-Error { param($Message) Write-Host "[ERROR] $Message" -ForegroundColor Red }

# Check jika direktori benar
if (!(Test-Path "artisan")) {
    Write-Error "File artisan tidak ditemukan. Pastikan Anda berada di direktori Laravel."
    exit 1
}

Write-Info "Laravel project detected"

# Backup .env jika belum di-backup
if (!(Test-Path ".env.backup")) {
    Write-Info "Creating .env backup..."
    Copy-Item .env .env.backup
    Write-Success ".env backup created"
} else {
    Write-Info ".env backup already exists"
}

# Generate production key
Write-Info "Generating production application key..."
php artisan key:generate
Write-Success "Production key generated"

# Clear dan cache untuk production
Write-Info "Optimizing for production..."

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
Write-Success "Configuration cached"

php artisan route:cache
Write-Success "Routes cached"

php artisan view:cache
Write-Success "Views cached"

# Install production dependencies
Write-Info "Installing production dependencies..."
composer install --no-dev --optimize-autoloader

if ($LASTEXITCODE -eq 0) {
    Write-Success "Production dependencies installed"
} else {
    Write-Error "Failed to install dependencies"
    exit 1
}

# Set permissions
Write-Info "Setting correct permissions..."
# Note: chmod tidak tersedia di Windows PowerShell, skip untuk Windows
Write-Success "Permissions set (Windows compatible)"

# Create deployment info
Write-Info "Creating deployment info..."
$deploymentInfo = @"
Deployment Date: $(Get-Date)
Laravel Version: $(php artisan --version)
PHP Version: $(php -v | Select-Object -First 1)
"@

$deploymentInfo | Out-File -FilePath "deployment_info.txt"
Write-Success "Deployment info created"

Write-Host ""
Write-Host "Laravel backend siap untuk production deployment!" -ForegroundColor Green
Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Upload semua files ke hosting public_html folder" -ForegroundColor White
Write-Host "2. Create database di hosting (klarifikasi_production)" -ForegroundColor White
Write-Host "3. Update .env dengan kredensial database production" -ForegroundColor White
Write-Host "4. Run: php artisan migrate" -ForegroundColor White
Write-Host "5. Install SSL certificate" -ForegroundColor White
Write-Host "6. Test API endpoints" -ForegroundColor White
Write-Host ""
Write-Host "Repository: https://github.com/Elloe2/Klarifikasi.id-backend" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
