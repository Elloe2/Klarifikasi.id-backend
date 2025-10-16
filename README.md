# 🌟 Klarifikasi.id

[![Flutter](https://img.shields.io/badge/Flutter-3.9.2-blue.svg)](https://flutter.dev)
[![Laravel](https://img.shields.io/badge/Laravel-12.0-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://mysql.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> **Aplikasi web fact-checking modern** yang dibangun dengan Flutter frontend dan Laravel backend untuk membantu pengguna memverifikasi kebenaran informasi dan klaim secara real-time.

<p align="center">
  <img src="https://via.placeholder.com/800x400/1a1a2e/ffffff?text=Klarifikasi.id+Dashboard" alt="Klarifikasi.id Screenshot" width="800"/>
</p>

## ✨ Fitur Unggulan

### 🔍 **Smart Fact-Checking**
- **Real-time Search**: Pencarian informasi dengan Google Custom Search Engine
- **Search History**: Riwayat pencarian dengan pagination lengkap
- **Rate Limiting**: Pembatasan pencarian untuk mencegah spam
- **Rich Results**: Preview hasil pencarian dengan thumbnail dan snippet

### 👤 **User Management System**
- **Secure Authentication**: Token-based auth dengan Laravel Sanctum
- **Profile Management**: Update profil dengan data pendidikan dan institusi
- **Password Security**: Password hashing dengan bcrypt
- **Session Management**: Automatic token refresh dan cleanup

### 🎨 **Modern UI/UX**
- **Responsive Design**: Optimized untuk desktop dan mobile
- **Dark Theme**: Elegant dark theme dengan gradient backgrounds
- **Loading Animations**: Smooth loading states dengan custom animations
- **Error Handling**: Comprehensive error dialogs dan feedback

### 🚀 **Production Ready**
- **MySQL Database**: Robust relational database dengan migrations
- **SSL Support**: HTTPS-ready dengan security headers
- **Error Monitoring**: Comprehensive logging dan error tracking
- **Scalable Architecture**: Clean code structure untuk easy maintenance

## 🌐 Production URLs

- Backend (Laravel Cloud): https://klarifikasiid-backend-main-ki47jp.laravel.cloud/
- Frontend (Cloudhebat): https://www.klarifikasi.rj22d.my.id/

## 🏗️ Arsitektur Aplikasi

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Flutter       │    │     Laravel      │    │     MySQL       │
│   Frontend      │◄──►│     Backend      │◄──►│    Database     │
│                 │    │                  │    │                 │
│ • Loading UI    │    │ • Auth API       │    │ • Users         │
│ • Error Dialogs │    │ • Search API     │    │ • Search History│
│ • Responsive    │    │ • Sanctum Token  │    │ • Access Tokens │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 🛠️ Tech Stack

### **Frontend (Flutter)**
- **Framework**: Flutter 3.9.2 🚀
- **State Management**: Provider Pattern 📱
- **HTTP Client**: http package dengan timeout & retry (custom) 🔄
- **Storage**: Flutter Secure Storage 🔐
- **UI Framework**: Material 3 dengan custom theming 🎨

### **Backend (Laravel)**
- **Framework**: Laravel 12.0 ⚡
- **Authentication**: Laravel Sanctum 🛡️
- **Database**: MySQL 8.0+ 🗄️
- **Search Engine**: Google Custom Search Engine 🔍
- **Caching**: Redis/Memcached 📋

### **Development Tools**
- **Version Control**: Git & GitHub
- **Code Quality**: PHPStan, ESLint
- **Testing**: PHPUnit, Flutter Test
- **Deployment**: Docker, CI/CD Ready

## 📋 Prerequisites

Sebelum memulai, pastikan Anda memiliki:

- **Flutter SDK** (3.9.2+) - [Download](https://flutter.dev/docs/get-started/install)
- **PHP** (8.2+) - [Download](https://php.net/downloads.php)
- **Composer** - [Download](https://getcomposer.org/download/)
- **MySQL** (8.0+) - [Download](https://dev.mysql.com/downloads/mysql/)
- **Google Custom Search API Key** - [Get Key](https://console.cloud.google.com/)

## 🚀 Quick Start

### **1. Clone Repositories**

```bash
# Clone frontend
git clone https://github.com/Elloe2/Klarifikasi.id-frontend.git
cd Klarifikasi.id-frontend

# Clone backend (di terminal terpisah)
git clone https://github.com/Elloe2/Klarifikasi.id-backend.git
cd Klarifikasi.id-backend
```

### **2. Backend Setup**

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database
# Edit .env dengan kredensial MySQL Anda
nano .env

# Run database migrations
php artisan migrate

# (Optional) Seed dengan data testing
php artisan db:seed

# Start Laravel server
php artisan serve --host=0.0.0.0 --port=8000
```

### **3. Frontend Setup**

```bash
# Install Flutter dependencies
flutter pub get

# Run web development server
flutter run -d chrome --web-port 3000

# Atau build untuk production
flutter build web --release
```

### **4. Configure Google CSE API**

```bash
# Edit .env backend dengan API credentials
GOOGLE_CSE_KEY=your_api_key_here
GOOGLE_CSE_CX=your_cx_id_here
```

## 🔑 Environment Configuration

### **Backend (.env)**
```env
# Application
APP_NAME=Klarifikasi.id
APP_ENV=local
APP_KEY=base64:your_app_key
APP_DEBUG=true
APP_URL=https://klarifikasiid-backend-main-ki47jp.laravel.cloud

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=klarifikasi_id
DB_USERNAME=root
DB_PASSWORD=your_password

# Google Custom Search (gunakan ENV di server - jangan commit key)
GOOGLE_CSE_KEY=your_api_key_here
GOOGLE_CSE_CX=your_cx_id_here
GOOGLE_CSE_VERIFY_SSL=true

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### **Frontend (lib/config.dart)**
```dart
String get apiBaseUrl {
  if (kDebugMode) {
    return 'http://localhost:8000';
  }
  return 'https://your-production-domain.com';
}
```

## 🔗 API Endpoints

### **Authentication Routes**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/register` | User registration | ❌ |
| POST | `/api/auth/login` | User login | ❌ |
| GET | `/api/auth/profile` | Get user profile | ✅ |
| POST | `/api/auth/profile` | Update profile | ✅ |
| POST | `/api/auth/logout` | User logout | ✅ |

### **Search Routes**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/search` | Perform fact-checking search | ❌ |
| GET | `/api/history` | Get search history | ✅ |
| DELETE | `/api/history` | Clear search history | ✅ |

> Catatan: `POST /api/search` saat ini tidak memerlukan autentikasi (throttle diterapkan). Jika ingin diwajibkan autentikasi, pindahkan route ke grup `auth:sanctum` di `routes/api.php`.

## 📱 Screenshots

<div align="center">

### **Login Screen**
<img src="https://via.placeholder.com/400x600/1a1a2e/ffffff?text=Login+Screen" alt="Login Screen" width="300"/>

### **Dashboard**
<img src="https://via.placeholder.com/400x600/16213e/ffffff?text=Dashboard" alt="Dashboard" width="300"/>

### **Search Results**
<img src="https://via.placeholder.com/400x600/0f3460/ffffff?text=Search+Results" alt="Search Results" width="300"/>

</div>

## 🌐 Deployment

### **Backend Deployment (Laravel)**

**Laravel Cloud (Production):**
```bash
# Set environment variables di Laravel Cloud Dashboard
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://klarifikasiid-backend-main-ki47jp.laravel.cloud
# GOOGLE_CSE_KEY=... (isi API Key)
# GOOGLE_CSE_CX=... (isi CX)
# GOOGLE_CSE_VERIFY_SSL=true

# Refresh configuration cache
php artisan config:clear && php artisan config:cache
```

**VPS/Cloud Server:**
### **CORS Configuration**

Sesuaikan `config/cors.php` agar hanya origin frontend produksi yang diizinkan:

```php
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['https://www.klarifikasi.rj22d.my.id'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,
```

Setelah mengubah CORS:

```bash
php artisan config:clear && php artisan config:cache
```
```bash
# Clone repository
git clone https://github.com/Elloe2/Klarifikasi.id-backend.git
cd Klarifikasi.id-backend

# Setup environment
composer install --no-dev
php artisan migrate
php artisan config:cache

# Setup web server (Nginx)
sudo nginx -t
sudo systemctl reload nginx
```

### **Frontend Deployment (Flutter)**

**Netlify/Vercel:**
```bash
# Build Flutter web
flutter build web --release

# Upload build/web/ ke Netlify
# Configure SPA redirect rules
```

**Traditional Hosting:**
```bash
# Upload build/web/ contents
# Configure server untuk SPA routing
```

### **Database Setup**
```sql
-- Create production database
CREATE DATABASE klarifikasi_id_production;
CREATE USER 'klarifikasi_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON klarifikasi_id_production.* TO 'klarifikasi_user'@'localhost';
FLUSH PRIVILEGES;
```

## 📊 Database Schema

### **Users Table**
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    birth_date DATE NULL,
    education_level ENUM('sd', 'smp', 'sma', 'kuliah') NULL,
    institution VARCHAR(255) NULL,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Search Histories Table**
```sql
CREATE TABLE search_histories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    query VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    top_title VARCHAR(255) NULL,
    top_link TEXT NULL,
    top_thumbnail VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🤝 Contributing

Kami sangat welcome kontribusi dari komunitas!

### **Cara Kontribusi:**

1. **Fork** repository
2. **Create feature branch**: `git checkout -b feature/amazing-feature`
3. **Commit changes**: `git commit -m 'Add amazing feature'`
4. **Push branch**: `git push origin feature/amazing-feature`
5. **Open Pull Request**

### **Development Guidelines:**

- **Code Style**: Ikuti PSR-12 untuk PHP, Effective Dart untuk Flutter
- **Testing**: Tulis tests untuk fitur baru
- **Documentation**: Update README untuk perubahan API
- **Review**: Semua PR perlu review sebelum merge

### **Issue Reporting:**
- Gunakan template issue yang disediakan
- Sertakan steps untuk reproduce bug
- Tambahkan screenshots jika relevan
- Tag dengan label yang sesuai

## 📝 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for more information.

## 👥 Authors & Contributors

- **Elloe** - *Project Creator & Maintainer*
- **Community Contributors** - *All contributors welcome!*

## 🙏 Acknowledgments

- **Google Custom Search API** - Untuk search functionality
- **Laravel Community** - Excellent documentation dan packages
- **Flutter Team** - Amazing cross-platform framework
- **Indonesian Fact-Checking Community** - Inspiration dan support
- **Open Source Contributors** - Tools dan libraries yang digunakan

## 📞 Support & Contact

### **Issues & Bugs**
- GitHub Issues: [Report Bug](https://github.com/Elloe2/Klarifikasi.id/issues)
- Feature Requests: [Request Feature](https://github.com/Elloe2/Klarifikasi.id/issues)

### **Documentation**
- **API Documentation**: Available in `/docs` folder
- **Deployment Guide**: See deployment section above
- **Development Guide**: Contributing guidelines above

### **Community**
- **Discussions**: GitHub Discussions untuk Q&A
- **Email**: Contact maintainer untuk partnerships

---

<div align="center">

**⭐ Star this repository if you find it helpful!**

[![GitHub stars](https://img.shields.io/github/stars/Elloe2/Klarifikasi.id.svg?style=social&label=Star)](https://github.com/Elloe2/Klarifikasi.id)
[![GitHub forks](https://img.shields.io/github/forks/Elloe2/Klarifikasi.id.svg?style=social&label=Fork)](https://github.com/Elloe2/Klarifikasi.id/fork)

**Made with ❤️ for the Indonesian fact-checking community**

</div>
