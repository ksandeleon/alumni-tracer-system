# Alumni Tracer System - Database Documentation

## Overview

This database schema supports a comprehensive alumni tracking system with survey functionality, user management, and detailed analytics.

## Database Schema

### Core Tables

#### 1. `users`

- **Purpose**: Stores user accounts for both alumni and administrators
- **Key Fields**:
    - `role`: Distinguishes between 'admin' and 'alumni' users
    - `status`: Tracks user account status ('active', 'inactive', 'pending')
    - Standard Laravel authentication fields

#### 2. `alumni_profiles`

- **Purpose**: Detailed alumni information and career tracking
- **Key Sections**:
    - **Personal Info**: Name, contact details, demographics
    - **Academic Info**: Degree, major, GPA, graduation details
    - **Employment Info**: Current job, salary, employment status
    - **Additional Info**: Skills, certifications, mentorship willingness
- **Features**: Profile completion tracking, batch association

#### 3. `batches`

- **Purpose**: Organize alumni by graduation year/batch
- **Usage**: Filter surveys, analyze trends by graduation cohort

### Survey System

#### 4. `surveys`

- **Purpose**: Manage survey campaigns
- **Features**:
    - Survey lifecycle management (draft → active → archived)
    - Target audience specification (by batch/year)
    - Email campaign integration
    - Response rate analytics
    - Special registration survey support

#### 5. `survey_questions`

- **Purpose**: Define survey question structure
- **Question Types Supported**:
    - Text inputs (text, textarea, email, phone, number, date)
    - Choice questions (single_choice, multiple_choice, dropdown, checkbox)
    - Interactive (rating, file_upload, matrix)
- **Features**:
    - Conditional logic support
    - Validation rules
    - Question ordering

#### 6. `survey_responses`

- **Purpose**: Track individual survey completion sessions
- **Features**:
    - Progress tracking
    - Response quality metrics
    - Anonymous response support
    - Completion time tracking

#### 7. `survey_answers`

- **Purpose**: Store actual survey responses
- **Storage Strategy**: Flexible columns to handle different data types
- **Features**: File upload support, timing analytics

#### 8. `survey_invitations`

- **Purpose**: Manage survey email campaigns
- **Features**:
    - Email delivery tracking
    - Reminder management
    - Response correlation

### System Management

#### 9. `admin_settings`

- **Purpose**: Configurable system settings
- **Categories**: General, email, survey, system
- **Features**: Type-safe value storage, public/private settings

#### 10. `activity_logs`

- **Purpose**: Audit trail for user actions
- **Tracked Events**: Logins, survey activities, profile updates
- **Features**: IP tracking, metadata storage

## Migration Files Created

1. `2024_01_01_000003_create_users_table.php` - User authentication and roles
2. `2024_01_01_000004_create_batches_table.php` - Graduation batch management
3. `2024_01_01_000005_create_alumni_profiles_table.php` - Detailed alumni profiles
4. `2024_01_01_000006_create_surveys_table.php` - Survey management
5. `2024_01_01_000007_create_survey_questions_table.php` - Survey question definitions
6. `2024_01_01_000008_create_survey_responses_table.php` - Response session tracking
7. `2024_01_01_000009_create_survey_answers_table.php` - Individual answer storage
8. `2024_01_01_000010_create_survey_invitations_table.php` - Email campaign tracking
9. `2024_01_01_000011_create_admin_settings_table.php` - System configuration
10. `2024_01_01_000012_create_activity_logs_table.php` - Audit logging

## Key Features Supported

### Alumni Registration Flow

1. Alumni receives survey invitation link
2. Completes survey questions (including personal/career info)
3. Sets up account credentials in final survey steps
4. Gets automatically registered and redirected to login
5. Can then manage profile and view survey history

### Admin Capabilities

- **Dashboard**: Response rates, employment statistics, batch analytics
- **Alumni Bank**: View and manage all alumni data
- **Survey Bank**: Create, edit, and manage surveys
- **Survey Results**: Analyze responses with filtering and export
- **Batch Manager**: Organize and filter by graduation cohorts

### Data Analytics Support

- Employment outcome tracking
- Response rate monitoring
- Batch-based comparisons
- Career progression analysis
- Survey effectiveness metrics

## Running Migrations

```bash
# Run migrations
php artisan migrate

# Run with sample data
php artisan db:seed --class=AlumniTracerSeeder
```

## Sample Data Included

- Default admin account (admin@alumnitracer.edu / password)
- Sample graduation batches (2020-2024)
- Complete registration survey template
- Default system settings
- Comprehensive survey questions covering:
    - Personal information
    - Academic background
    - Employment status
    - Contact details
    - Engagement preferences

## Security Features

- Role-based access control
- Email verification
- Password reset functionality
- Activity logging
- Anonymous response support (when needed)
- Data retention settings

## Performance Considerations

- Indexed foreign keys for fast joins
- Composite indexes for common query patterns
- Efficient JSON storage for flexible data
- Optimized for reporting queries

This schema provides a solid foundation for tracking alumni career outcomes, managing survey campaigns, and generating insights for institutional improvement.
