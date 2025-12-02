# 🏥 SheCare Backend API (PHP Version)

Backend system untuk SheCare - Platform Diagnosis Kesehatan Kewanitaan dengan Decision Tree AI.

## 📋 Daftar Isi

- [Fitur](#fitur)
- [Tech Stack](#tech-stack)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Struktur Folder](#struktur-folder)
- [API Endpoints](#api-endpoints)
- [Multi-Language Support](#multi-language-support)
- [Export Features](#export-features)

---

## ✨ Fitur Lengkap

### 🔐 Authentication
- ✅ Register & Login dengan JWT
- ✅ Forgot Password & Reset Password (Email)
- ✅ Role-based Access (User & Admin)
- ✅ Password Hashing (bcrypt)

### 📝 Kuisioner (User)
- ✅ Dynamic Questions (skala 1-5)
- ✅ Submit Questionnaire
- ✅ AI Diagnosis (Decision Tree ID3)
- ✅ View Result & History
- ✅ **Export to PDF**
- ✅ **Export to Excel/CSV**

### 👨‍💼 Admin Panel
#### User Management
- ✅ List all users
- ✅ View user detail
- ✅ Delete user
- ✅ View all history

#### Questions CRUD
- ✅ Create Question
- ✅ Read/List Questions
- ✅ Update Question
- ✅ Delete Question

#### Diseases CRUD
- ✅ Create Disease
- ✅ Read/List Diseases
- ✅ Update Disease
- ✅ Delete Disease

### 🌍 Multi-Language
- ✅ Bahasa Indonesia (default)
- ✅ English
- ✅ Language switcher (?lang=id atau ?lang=en)

### 📰 External APIs
- ✅ Health Articles (News API)
- ✅ Google Maps (Nearby Clinics)
- ✅ Statistics untuk Homepage

---

## 🛠️ Tech Stack

- **Language**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Authentication**: JWT (Custom Implementation)
- **Decision Tree**: PHP + ID3 Algorithm
- **Export**: PDF (HTML), Excel (CSV)
- **APIs**: News API, Google Maps API

---

## 🚀 Instalasi

### Prerequisites

- PHP 7.4 atau lebih tinggi
- MySQL 8.0+
- Apache/Nginx dengan mod_rewrite
- Composer (optional)

### Step 1: Extract Project

```bash
# Extract dan masuk ke folder
cd shecare-backend-php
```

### Step 2: Setup Database

```bash
# Import database schema
mysql -u root -p < database/schema.sql
```

Atau via phpMyAdmin:
1. Buat database `shecare_db`
2. Import file `database/schema.sql`

### Step 3: Konfigurasi Environment

```bash
cp .env.example .env
```

Edit file `.env`:

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=shecare_db
DB_USER=root
DB_PASSWORD=your_password

# JWT Secret (min 32 karakter)
JWT_SECRET=your_super_secret_key_min_32_characters

# Email (untuk forgot password)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_email_password

# API Keys (optional)
NEWS_API_KEY=your_news_api_key
GOOGLE_MAPS_API_KEY=your_google_maps_key

# CORS (Frontend URL)
CORS_ORIGIN=http://localhost:3000
```

### Step 4: Setup Web Server

#### Apache

Pastikan `mod_rewrite` enabled:

```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

Konfigurasi VirtualHost:

```apache
<VirtualHost *:80>
    ServerName shecare-api.local
    DocumentRoot /path/to/shecare-backend-php/public
    
    <Directory /path/to/shecare-backend-php/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name shecare-api.local;
    root /path/to/shecare-backend-php/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### Step 5: Test Installation

Buka browser:
```
http://localhost/shecare-backend-php/public/api/health
```

Expected response:
```json
{
  "success": true,
  "message": "SheCare API is running",
  "timestamp": "2024-11-28T10:30:00+07:00",
  "version": "1.0.0"
}
```

---

## 📁 Struktur Folder

```
shecare-backend-php/
├── config/
│   ├── database.php          # Database connection
│   └── config.php             # App configuration
├── controllers/
│   ├── AuthController.php     # Authentication
│   ├── QuestionnaireController.php
│   ├── AdminController.php    # Admin CRUD
│   ├── ArticleController.php
│   ├── MapsController.php
│   └── StatisticsController.php
├── middleware/
│   ├── Auth.php               # JWT middleware
│   └── CORS.php               # CORS handler
├── models/
│   ├── User.php
│   ├── Question.php
│   ├── Disease.php
│   └── Questionnaire.php
├── routes/
│   └── api.php                # Route definitions
├── utils/
│   ├── Response.php           # JSON response helper
│   ├── JWT.php                # JWT implementation
│   ├── Validator.php          # Input validation
│   ├── I18n.php               # Multi-language
│   ├── Mailer.php             # Email service
│   ├── PDFExport.php          # PDF export
│   └── ExcelExport.php        # Excel export
├── python/
│   └── DecisionTree.php       # Decision tree logic
├── database/
│   └── schema.sql             # Database schema
├── public/
│   ├── index.php              # Entry point
│   └── .htaccess              # URL rewriting
├── .env.example               # Environment template
├── .htaccess                  # Root htaccess
└── README.md
```

---

## 📡 API Endpoints

### Base URL

```
http://localhost/shecare-backend-php/public/api
```

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register user baru |
| POST | `/auth/login` | Login user |
| GET | `/auth/me` | Get user profile |
| POST | `/auth/logout` | Logout |
| POST | `/auth/forgot-password` | Request reset password |
| POST | `/auth/reset-password` | Reset password |

### Questionnaire

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/questions` | Get all questions |
| POST | `/questionnaire/submit` | Submit answers |
| GET | `/questionnaire/result/:id` | Get result |
| GET | `/questionnaire/history` | Get history |
| GET | `/questionnaire/export/pdf/:id` | Export to PDF |
| GET | `/questionnaire/export/excel/:id` | Export to Excel |
| GET | `/questionnaire/export/history` | Export history |

### Admin

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/users` | List users |
| GET | `/admin/users/:id` | User detail |
| DELETE | `/admin/users/:id` | Delete user |
| GET | `/admin/history` | All history |
| GET | `/admin/questions` | List questions |
| POST | `/admin/questions` | Create question |
| PUT | `/admin/questions/:id` | Update question |
| DELETE | `/admin/questions/:id` | Delete question |
| GET | `/admin/diseases` | List diseases |
| POST | `/admin/diseases` | Create disease |
| PUT | `/admin/diseases/:id` | Update disease |
| DELETE | `/admin/diseases/:id` | Delete disease |

### Others

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/articles` | Health articles |
| GET | `/maps/clinics` | Nearby clinics |
| GET | `/statistics/diseases` | Disease statistics |
| GET | `/statistics/summary` | Summary stats |

---

## 🌍 Multi-Language Support

Tambahkan parameter `?lang=` di setiap request:

```bash
# Bahasa Indonesia
GET /api/questions?lang=id

# English
GET /api/questions?lang=en
```

---

## 📄 Export Features

### Export to PDF

```bash
GET /api/questionnaire/export/pdf/1?lang=id
Authorization: Bearer YOUR_TOKEN
```

Returns HTML yang bisa di-print atau convert ke PDF.

### Export to Excel

```bash
GET /api/questionnaire/export/excel/1?lang=id
Authorization: Bearer YOUR_TOKEN
```

Download file CSV (compatible dengan Excel).

### Export History

```bash
GET /api/questionnaire/export/history?lang=id
Authorization: Bearer YOUR_TOKEN
```

Export semua riwayat ke Excel.

---

## 🔑 Default Credentials

### Admin Account

```
Email: admin@shecare.com
Password: admin123
```

**⚠️ PENTING:** Ganti password setelah instalasi!

---

## 🧪 Testing API

### Using cURL

```bash
# Register
curl -X POST http://localhost/shecare-api/public/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'

# Login
curl -X POST http://localhost/shecare-api/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# Submit Questionnaire
curl -X POST http://localhost/shecare-api/public/api/questionnaire/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "answers": [
      {"question_id": 1, "answer_value": 4},
      {"question_id": 2, "answer_value": 3},
      {"question_id": 3, "answer_value": 2}
    ]
  }'
```

---

## 🐛 Troubleshooting

### Database Connection Error

```
Database connection failed
```

**Solution:** 
- Check credentials di `.env`
- Pastikan MySQL service running

### Mod Rewrite Not Working

```
404 Not Found
```

**Solution:**
```bash
# Apache
sudo a2enmod rewrite
sudo service apache2 restart

# Check .htaccess
AllowOverride All
```

### CORS Error

```
Access to fetch has been blocked by CORS policy
```

**Solution:**
- Update `CORS_ORIGIN` di `.env`
- Check middleware CORS.php

---

## 📝 Notes untuk Frontend Developer

### Base URL

```javascript
const API_BASE_URL = 'http://localhost/shecare-api/public/api';
```

### Authentication

```javascript
// Save token after login
localStorage.setItem('token', response.data.token);

// Add to request headers
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

### Language Switching

```javascript
// Add lang parameter
const response = await axios.get(`${API_BASE_URL}/questions?lang=en`);
```

---

## 📊 Database Schema

8 tables:
- `users` - User accounts
- `diseases` - Diseases (bilingual)
- `questions` - Questions (bilingual)
- `question_rules` - Decision tree rules
- `questionnaire_submissions` - Submissions
- `questionnaire_answers` - Answers
- `diagnosis_results` - Results
- `disease_statistics` - Statistics

---

## 🔒 Security Features

- ✅ Password hashing (bcrypt)
- ✅ JWT authentication
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ Input validation
- ✅ CORS protection
- ✅ Role-based access control

---

## 👥 Team

SheCare Development Team

## 📄 License

MIT License

---

**Happy Coding! 🚀**