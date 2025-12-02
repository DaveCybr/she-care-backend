# SheCare API Documentation (PHP)

Complete API reference for SheCare Backend.

**Base URL:** `http://localhost/shecare-api/public/api`

---

## 📋 Table of Contents

1. [Response Format](#response-format)
2. [Authentication](#authentication)
3. [Questionnaire](#questionnaire)
4. [Admin Endpoints](#admin-endpoints)
5. [External APIs](#external-apis)
6. [Error Codes](#error-codes)

---

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Success message",
  "data": { ... },
  "count": 10
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": [
    {
      "field": "email",
      "message": "Valid email is required"
    }
  ]
}
```

---

## Authentication

### 1. Register

**Endpoint:** `POST /auth/register`

**Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "securepass123",
  "phone": "08123456789"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Pendaftaran berhasil",
  "data": {
    "user": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "user",
      "phone": "08123456789"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

### 2. Login

**Endpoint:** `POST /auth/login`

**Body:**
```json
{
  "email": "jane@example.com",
  "password": "securepass123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "user"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

### 3. Get Current User

**Endpoint:** `GET /auth/me`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "role": "user"
  }
}
```

---

### 4. Forgot Password

**Endpoint:** `POST /auth/forgot-password`

**Body:**
```json
{
  "email": "jane@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Link reset password telah dikirim ke email Anda"
}
```

---

### 5. Reset Password

**Endpoint:** `POST /auth/reset-password`

**Body:**
```json
{
  "token": "abc123...",
  "password": "newpassword123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password berhasil direset"
}
```

---

## Questionnaire

### 1. Get Questions

**Endpoint:** `GET /questions?lang=id`

**Response (200):**
```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "id": 1,
      "question_text": "Seberapa sering Anda mengalami nyeri panggul kronis?",
      "question_type": "scale",
      "min_value": 1,
      "max_value": 5,
      "order_number": 1
    }
  ]
}
```

---

### 2. Submit Questionnaire

**Endpoint:** `POST /questionnaire/submit`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Body:**
```json
{
  "lang": "id",
  "answers": [
    {
      "question_id": 1,
      "answer_value": 4
    },
    {
      "question_id": 2,
      "answer_value": 3
    },
    {
      "question_id": 3,
      "answer_value": 5
    }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Kuisioner berhasil dikirim",
  "data": {
    "submission_id": 10,
    "diagnosis_id": 5,
    "diagnosis": {
      "disease_id": 1,
      "disease_name": "Endometriosis",
      "confidence": 0.80,
      "diagnosis_text": "Nyeri panggul kronis yang Anda alami...",
      "recommendations": "Segera konsultasi dengan dokter..."
    }
  }
}
```

---

### 3. Get Result

**Endpoint:** `GET /questionnaire/result/:id?lang=id`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "submission_id": 10,
    "confidence_score": 0.80,
    "diagnosis_text": "Nyeri panggul kronis...",
    "recommendations": "Segera konsultasi...",
    "disease_name": "Endometriosis",
    "severity": "high",
    "user_name": "Jane Doe",
    "answers": [
      {
        "question_id": 1,
        "answer_value": "4",
        "question_text": "Seberapa sering..."
      }
    ]
  }
}
```

---

### 4. Get History

**Endpoint:** `GET /questionnaire/history?limit=10&offset=0&lang=id`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200):**
```json
{
  "success": true,
  "count": 5,
  "total": 15,
  "data": [
    {
      "submission_id": 10,
      "submission_date": "2024-11-28 10:30:00",
      "completed": true,
      "diagnosis_id": 5,
      "confidence_score": 0.80,
      "disease_name": "Endometriosis",
      "severity": "high"
    }
  ]
}
```

---

### 5. Export to PDF

**Endpoint:** `GET /questionnaire/export/pdf/:id?lang=id`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:** HTML content (can be printed or converted to PDF by frontend)

---

### 6. Export to Excel

**Endpoint:** `GET /questionnaire/export/excel/:id?lang=id`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:** CSV file download

---

### 7. Export History

**Endpoint:** `GET /questionnaire/export/history?lang=id`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:** CSV file download with all history

---

## Admin Endpoints

**All admin endpoints require:**
- Valid JWT token
- User role = `admin`

### User Management

#### 1. Get All Users

**Endpoint:** `GET /admin/users?limit=10&offset=0`

**Response (200):**
```json
{
  "success": true,
  "count": 10,
  "total": 150,
  "data": [
    {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "user",
      "created_at": "2024-11-28 10:30:00"
    }
  ]
}
```

---

#### 2. Get User Detail

**Endpoint:** `GET /admin/users/:id`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "role": "user",
    "total_submissions": 5
  }
}
```

---

#### 3. Delete User

**Endpoint:** `DELETE /admin/users/:id`

**Response (200):**
```json
{
  "success": true,
  "message": "User berhasil dihapus"
}
```

---

#### 4. Get All History

**Endpoint:** `GET /admin/history?limit=20&offset=0&lang=id`

**Response (200):**
```json
{
  "success": true,
  "count": 20,
  "total": 500,
  "data": [
    {
      "submission_id": 100,
      "submission_date": "2024-11-28 10:30:00",
      "user_name": "Jane Doe",
      "user_email": "jane@example.com",
      "disease_name": "Endometriosis",
      "confidence_score": 0.80,
      "severity": "high"
    }
  ]
}
```

---

### Questions CRUD

#### 1. Get All Questions

**Endpoint:** `GET /admin/questions?lang=id`

**Response (200):**
```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "id": 1,
      "question_text_id": "Seberapa sering...",
      "question_text_en": "How often...",
      "question_type": "scale",
      "min_value": 1,
      "max_value": 5,
      "order_number": 1,
      "is_active": true
    }
  ]
}
```

---

#### 2. Create Question

**Endpoint:** `POST /admin/questions`

**Body:**
```json
{
  "question_text_id": "Apakah Anda mengalami demam?",
  "question_text_en": "Do you have a fever?",
  "question_type": "scale",
  "min_value": 1,
  "max_value": 5,
  "order_number": 6,
  "is_active": true
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Pertanyaan berhasil ditambahkan",
  "data": { ... }
}
```

---

#### 3. Update Question

**Endpoint:** `PUT /admin/questions/:id`

**Body:**
```json
{
  "question_text_id": "Updated question...",
  "is_active": false
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Pertanyaan berhasil diperbarui",
  "data": { ... }
}
```

---

#### 4. Delete Question

**Endpoint:** `DELETE /admin/questions/:id`

**Response (200):**
```json
{
  "success": true,
  "message": "Pertanyaan berhasil dihapus"
}
```

---

### Diseases CRUD

#### 1. Get All Diseases

**Endpoint:** `GET /admin/diseases`

**Response (200):**
```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "id": 1,
      "name_id": "Endometriosis",
      "name_en": "Endometriosis",
      "description_id": "Kondisi dimana...",
      "description_en": "A condition where...",
      "severity": "high",
      "recommendations_id": "Konsultasi...",
      "recommendations_en": "Consult..."
    }
  ]
}
```

---

#### 2. Create Disease

**Endpoint:** `POST /admin/diseases`

**Body:**
```json
{
  "name_id": "PCOS",
  "name_en": "PCOS",
  "description_id": "Sindrom ovarium polikistik...",
  "description_en": "Polycystic ovary syndrome...",
  "severity": "moderate",
  "recommendations_id": "Jaga pola makan...",
  "recommendations_en": "Maintain diet..."
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Penyakit berhasil ditambahkan",
  "data": { ... }
}
```

---

#### 3. Update Disease

**Endpoint:** `PUT /admin/diseases/:id`

**Body:** (all fields optional)
```json
{
  "severity": "high",
  "recommendations_id": "Updated..."
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Penyakit berhasil diperbarui",
  "data": { ... }
}
```

---

#### 4. Delete Disease

**Endpoint:** `DELETE /admin/diseases/:id`

**Response (200):**
```json
{
  "success": true,
  "message": "Penyakit berhasil dihapus"
}
```

---

## External APIs

### 1. Get Articles

**Endpoint:** `GET /articles?limit=10&q=women health`

**Response (200):**
```json
{
  "success": true,
  "count": 10,
  "data": [
    {
      "title": "Understanding Women's Health",
      "description": "Learn about...",
      "url": "https://example.com/article",
      "urlToImage": "https://example.com/image.jpg",
      "publishedAt": "2024-11-28T10:00:00Z",
      "source": {
        "name": "Health Magazine"
      }
    }
  ]
}
```

---

### 2. Get Nearby Clinics

**Endpoint:** `GET /maps/clinics?lat=-6.2088&lng=106.8456&radius=5000`

**Response (200):**
```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "name": "Klinik Kesehatan Wanita",
      "address": "Jl. Sudirman No. 123",
      "rating": 4.5,
      "location": {
        "lat": -6.2108,
        "lng": 106.8476
      },
      "open_now": true
    }
  ]
}
```

---

### 3. Get Disease Statistics

**Endpoint:** `GET /statistics/diseases?lang=id`

**Response (200):**
```json
{
  "success": true,
  "count": 4,
  "data": [
    {
      "id": 2,
      "disease_name": "Infeksi Jamur",
      "region": "Indonesia",
      "percentage": 15.30,
      "total_cases": 3600000,
      "year": 2024,
      "source": "WHO Indonesia",
      "severity": "low"
    }
  ]
}
```

---

### 4. Get Summary

**Endpoint:** `GET /statistics/summary`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_users": 150,
    "total_submissions": 520,
    "total_diseases": 5
  }
}
```

---

## Error Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Success |
| 201 | Created - Resource created |
| 400 | Bad Request - Validation error |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 500 | Internal Server Error |

---

## Multi-Language Support

Add `?lang=id` or `?lang=en` to any endpoint:

```
GET /api/questions?lang=en
GET /api/questionnaire/result/1?lang=id
```

Supported languages:
- `id` - Bahasa Indonesia (default)
- `en` - English

---

## Rate Limiting

Currently no rate limiting implemented. Consider adding for production.

---

**Last Updated:** 28 November 2024