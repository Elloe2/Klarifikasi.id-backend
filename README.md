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


## 📁 Project Structure

### **⚙️ Backend Architecture (Laravel)**

```
Klarifikasi.id-backend/
├── 🎯 app/                                 # Application core
│   ├── 🎮 Http/Controllers/                # API controllers
│   │   ├── AuthController.php              # User authentication & profile
│   │   ├── SearchController.php            # Fact-checking & Gemini AI
│   │   └── Controller.php                  # Base controller
│   ├── 📊 Models/                          # Eloquent models
│   │   └── User.php                        # User model dengan Sanctum
│   ├── 🔧 Services/                        # Business logic services
│   │   ├── GoogleSearchService.php         # Google CSE integration
│   │   └── GeminiService.php              # Gemini AI integration
│   ├── 🛡️ Providers/                      # Service providers
│   │   └── AppServiceProvider.php          # Service container bindings
│   └── 🚀 Console/                        # Artisan commands
├── 🌐 api/                                 # Serverless API endpoints
│   ├── index.php                           # Root API router
│   ├── auth.php                            # Authentication endpoints
│   ├── search.php                          # Search endpoints
│   ├── _init.php                           # Serverless initialization
│   └── _env.php                            # Environment configuration
├── ⚙️ config/                             # Configuration files
│   ├── app.php                             # Application configuration
│   ├── auth.php                            # Authentication config
│   ├── database.php                        # Database configuration
│   ├── services.php                        # Third-party services
│   └── cors.php                            # CORS configuration
├── 🗄️ database/                           # Database management
│   ├── migrations/                         # Database migrations
│   │   ├── create_users_table.php          # Users table
│   │   ├── create_personal_access_tokens_table.php  # Sanctum tokens
│   │   └── create_cache_table.php          # Cache table
│   ├── factories/                          # Model factories
│   │   └── UserFactory.php                 # User factory
│   └── seeders/                            # Database seeders
│       ├── DatabaseSeeder.php              # Main seeder
│       └── UserSeeder.php                  # User seeder
├── 🛣️ routes/                             # Route definitions
│   ├── api.php                             # API routes
│   ├── web.php                             # Web routes
│   └── console.php                         # Console routes
├── 🚀 bootstrap/                           # Application bootstrap
│   ├── app.php                             # Application bootstrap
│   ├── serverless.php                      # Serverless bootstrap
│   └── providers.php                       # Service providers
├── 📦 composer.json                        # PHP dependencies
├── 📋 README.md                            # Backend documentation
└── 🔧 artisan                              # Laravel command line tool
```

### **🔗 API Architecture**

```
Backend API Structure:
├── 🎮 Controller Layer
│   ├── AuthController                      # Authentication & user management
│   │   ├── register()                     # User registration
│   │   ├── login()                        # User authentication
│   │   ├── profile()                      # Get user profile
│   │   ├── updateProfile()                # Update user profile
│   │   └── logout()                       # User logout
│   └── SearchController                   # Fact-checking & AI integration
│       ├── search()                       # Main search endpoint
│       └── searchByQuery()                # Search by URL parameter
├── 🔧 Service Layer
│   ├── GoogleSearchService                 # Google CSE integration
│   │   ├── search()                       # Web search functionality
│   │   ├── Result Processing               # Thumbnail & snippet extraction
│   │   └── Error Handling                 # API error management
│   └── GeminiService                       # Gemini AI integration
│       ├── analyzeClaim()                 # AI analysis functionality
│       ├── buildPrompt()                  # Prompt engineering
│       ├── parseResponse()                # JSON response parsing
│       └── getFallbackResponse()           # Error fallback
├── 🛡️ Middleware Layer
│   ├── auth:sanctum                       # Token-based authentication
│   ├── throttle                          # Rate limiting (10 req/min)
│   └── cors                              # Cross-origin resource sharing
└── 🛣️ Route Layer
    ├── Authentication Routes              # /api/auth/*
    ├── Search Routes                      # /api/search
    ├── Health Check                       # /api/health
    └── Test Endpoints                     # /api/test-google-cse
```

### **🤖 AI Integration Architecture**

```
AI Services Integration:
├── 🧠 Google Gemini AI Service
│   ├── Model: gemini-2.0-flash            # Latest Gemini model
│   ├── API Endpoint                       # generateContent endpoint
│   ├── Prompt Engineering                 # Structured fact-checking prompts
│   ├── Response Parsing                   # JSON extraction & validation
│   ├── Safety Settings                    # Content filtering
│   └── Error Handling                     # Fallback responses
├── 🔍 Google Custom Search Engine
│   ├── Search API                         # Real-time web search
│   ├── Result Processing                  # Thumbnail & snippet extraction
│   ├── Query Optimization                 # Search term refinement
│   └── Rate Limiting                      # API quota management
└── 📊 Data Aggregation
    ├── Parallel Processing                # Simultaneous API calls
    ├── Response Combination               # Merge search + AI results
    ├── Error Management                   # Comprehensive error handling
    └── Performance Optimization           # Caching & optimization
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


---

<div align="center">

**⭐ Star this repository if you find it helpful!**

[![GitHub stars](https://img.shields.io/github/stars/Elloe2/Klarifikasi.id.svg?style=social&label=Star)](https://github.com/Elloe2/Klarifikasi.id)
[![GitHub forks](https://img.shields.io/github/forks/Elloe2/Klarifikasi.id.svg?style=social&label=Fork)](https://github.com/Elloe2/Klarifikasi.id/fork)

**Made with ❤️ for the Indonesian fact-checking community**

</div>
