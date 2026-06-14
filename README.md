# 🩺 Seen — Diabetes Management & Community Platform

> A full-stack healthcare platform built for diabetes patients to manage their health, consult doctors in real-time, and connect with a supportive community.

---

## 📌 Overview

**Seen** is a graduation project developed as a comprehensive diabetes care solution. It combines medical health tracking, AI-powered guidance, real-time doctor consultation, and a social community — all secured with role-based access control and deployed on a fault-tolerant AWS infrastructure.

---

## ✨ Key Features

### 🔐 Secure Authentication & RBAC
- JWT + Laravel Sanctum for stateless API authentication
- Role-based access control for **Patients**, **Doctors**, and **Admins**
- Medical records protected per role with fine-grained permissions

### 🤖 AI Chatbot
- Integrated external AI API for instant diabetes-related medical guidance
- Conversational interface for symptom checks and health questions

### 📊 Health Logging & Reporting
- Automated blood sugar tracking with configurable schedules
- Dynamic health report generation with trend analysis

### 💬 Real-Time Chat (WebSockets)
- Live doctor-patient consultation via **Laravel Reverb** + WebSockets
- Instant bi-directional messaging with read receipts

### 🔔 Push Notifications (Firebase FCM)
- Medication reminders delivered via Firebase Cloud Messaging
- Community notifications for posts, comments, and interactions

### 👥 Community & Friendship System
- Social feed with posts, likes, comments, and peer support
- Friendship system for diabetes patient networking

### 🗄️ Database Architecture
- Optimized MySQL schemas using Eloquent ORM
- Strategic indexing for high performance under load

---



---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP, Laravel |
| **Database** | MySQL, Eloquent ORM |
| **Auth** | JWT, Laravel Sanctum |
| **Real-Time** | WebSockets, Laravel Reverb |
| **Notifications** | Firebase FCM |
| **AI** | External AI API Integration |
| **Cloud** | AWS (EC2, ALB, ASG, Route 53, RDS, S3) |
| **Web Server** | Nginx |

---

## 🚀 Getting Started

### Prerequisites
- PHP >= 8.1
- Composer
- MySQL
- Node.js & NPM

### Installation

```bash
# Clone the repo
git clone https://github.com/Kassem-86/grad-project.git
cd grad-project

# Install PHP dependencies
composer install

# Install JS dependencies
npm install && npm run build

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your .env with DB, Firebase, AI API credentials

# Run migrations
php artisan migrate --seed

# Start WebSocket server
php artisan reverb:start

# Start the app
php artisan serve
```

---

## 📁 Project Structure

```
├── app/
│   ├── Http/Controllers/     # API Controllers (Auth, Chat, Health, Community)
│   ├── Models/               # Eloquent Models
│   └── Services/             # Business Logic Services
├── routes/
│   └── api.php               # API Routes
├── database/
│   ├── migrations/           # DB Schema
│   └── seeders/              # Seed Data
└── scripts/                  # Deployment & utility scripts
```

---

## 👨‍💻 Author

**Ziad Hossam Kassem** — Backend Developer  
📧 ziadkassem54@gmail.com  
🔗 [LinkedIn](https://linkedin.com/in/ziad-hossam) | [GitHub](https://github.com/Kassem-86)

---

## 📄 License

This project is for academic and portfolio purposes.
