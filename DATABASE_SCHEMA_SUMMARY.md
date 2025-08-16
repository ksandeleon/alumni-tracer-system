# Alumni Tracer System - Database Schema Summary

## 🗄️ Database Tables Overview

The Alumni Tracer System uses **12 core tables** organized into logical groups:

### 1. 👤 User Management

- **`users`** - Core user accounts (both admin and alumni)
- **`password_reset_tokens`** - Password reset functionality
- **`sessions`** - User session management

### 2. 🎓 Alumni Management

- **`batches`** - Graduation batches/years organization
- **`alumni_profiles`** - Detailed alumni information and profiles

### 3. 📋 Survey System

- **`surveys`** - Survey definitions and metadata
- **`survey_questions`** - Individual questions within surveys
- **`survey_responses`** - User responses to surveys
- **`survey_answers`** - Individual answers to specific questions
- **`survey_invitations`** - Survey invitation tracking

### 4. ⚙️ System Management

- **`admin_settings`** - System configuration and settings
- **`activity_logs`** - System activity and audit trail

### 5. 🔧 Infrastructure (Laravel Default)

- **`cache`** - Application caching
- **`cache_locks`** - Cache locking mechanism
- **`jobs`** - Queue job management
- **`job_batches`** - Batch job processing
- **`failed_jobs`** - Failed job tracking

## 🔄 Alumni Registration Flow

1. **Survey Phase**: Alumni receives survey link → Completes survey
2. **Registration Phase**: Last page of survey collects email/password
3. **Auto-Registration**: System creates user account automatically
4. **Login Redirect**: Alumni redirected to login page

## 👥 User Roles

- **Admin**: Full system access (dashboard, manage alumni, create surveys, view results)
- **Alumni**: Limited access (profile management, survey history)

## 🔗 Key Relationships

- `users` ↔ `alumni_profiles` (1:1)
- `batches` ↔ `alumni_profiles` (1:Many)
- `surveys` ↔ `survey_questions` (1:Many)
- `surveys` ↔ `survey_responses` (1:Many)
- `survey_responses` ↔ `survey_answers` (1:Many)
- `users` ↔ `survey_responses` (1:Many)

## 🚀 Initial Data

The system comes with:

- **Default Admin Account**:
    - Email: `admin@alumnitracer.edu`
    - Password: `password`
- **Sample Batches**: 2020-2024
- **Sample Survey**: "Alumni Employment Survey 2024"
- **System Settings**: Default configurations

## 📊 Features Supported

✅ **Alumni Features**:

- Profile management
- Survey completion
- Response history

✅ **Admin Features**:

- Dashboard analytics
- Alumni database management
- Survey creation and management
- Results analysis and export
- Batch management
- Activity monitoring

## 🔧 Database Commands

```bash
# Run migrations
php artisan migrate

# Seed initial data
php artisan db:seed

# Check migration status
php artisan migrate:status

# Reset and re-run migrations
php artisan migrate:reset && php artisan migrate
```

## 📝 Notes

- All timestamps are properly configured for MySQL
- Foreign key constraints ensure data integrity
- Soft deletes implemented where appropriate
- JSON fields used for flexible data storage
- Comprehensive indexing for performance
