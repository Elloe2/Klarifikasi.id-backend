#!/usr/bin/env bash
# Laravel Production Deployment Script
# Jalankan dengan: bash deploy.sh

echo "🚀 Starting Laravel Production Deployment..."
echo "============================================="

# Colors untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function untuk logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check jika direktori benar
if [ ! -f "artisan" ]; then
    log_error "File artisan tidak ditemukan. Pastikan Anda berada di direktori Laravel."
    exit 1
fi

log_info "✅ Laravel project detected"

# Backup .env jika belum di-backup
if [ ! -f ".env.backup" ]; then
    log_info "📋 Creating .env backup..."
    cp .env .env.backup
    log_success "✅ .env backup created"
else
    log_info "📋 .env backup already exists"
fi

# Generate production key
log_info "🔑 Generating production application key..."
php artisan key:generate
log_success "✅ Production key generated"

# Clear dan cache untuk production
log_info "⚡ Optimizing for production..."

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
log_success "✅ Configuration cached"

php artisan route:cache
log_success "✅ Routes cached"

php artisan view:cache
log_success "✅ Views cached"

# Install production dependencies
log_info "📦 Installing production dependencies..."
composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    log_success "✅ Production dependencies installed"
else
    log_error "❌ Failed to install dependencies"
    exit 1
fi

# Set permissions
log_info "🔒 Setting correct permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs storage/framework
log_success "✅ Permissions set"

# Create deployment info
log_info "📝 Creating deployment info..."
echo "Deployment Date: $(date)" > deployment_info.txt
echo "Laravel Version: $(php artisan --version)" >> deployment_info.txt
echo "PHP Version: $(php -v | head -n 1)" >> deployment_info.txt
log_success "✅ Deployment info created"

log_success "🎉 Laravel backend siap untuk production deployment!"
echo ""
echo "============================================="
echo "📋 Next Steps:"
echo "1. Upload semua files ke hosting (public_html)"
echo "2. Create database di hosting (klarifikasi_production)"
echo "3. Update .env dengan kredensial database production"
echo "4. Run: php artisan migrate"
echo "5. Install SSL certificate"
echo "6. Test API endpoints"
echo ""
echo "🔗 Repository: https://github.com/Elloe2/Klarifikasi.id-backend"
echo "============================================="
