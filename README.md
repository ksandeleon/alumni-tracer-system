# Alumni Tracer System - API Testing with cURL

A comprehensive guide for testing the Alumni Tracer System API using cURL commands. This system supports both alumni registration flows and comprehensive admin management capabilities.

## Table of Contents

- [Quick Start](#quick-start)
- [System Overview](#system-overview)
- [Authentication](#authentication)
- [Alumni Flow Testing](#alumni-flow-testing)
- [Admin Dashboard Testing](#admin-dashboard-testing)
- [Alumni Bank Testing](#alumni-bank-testing)
- [Survey Management Testing](#survey-management-testing)
- [Data Export Testing](#data-export-testing)
- [Error Handling](#error-handling)
- [Testing Scenarios](#testing-scenarios)

## Quick Start

1. **Start the Laravel development server:**

    ```bash
    cd /path/to/alumni-tracer
    php artisan serve
    ```

2. **Health check:**

    ```bash
    curl -X GET http://localhost:8000/api/health
    ```

3. **Admin login:**
    ```bash
    curl -X POST http://localhost:8000/api/v1/login \
      -H "Content-Type: application/json" \
      -d '{"email": "admin@alumnitracer.edu", "password": "password"}'
    ```

## System Overview

The Alumni Tracer System provides:

### For Alumni:

- **Registration via Survey**: Multi-step survey process that creates alumni accounts
- **Profile Management**: Access and update alumni profiles
- **Survey Participation**: Complete various surveys and questionnaires

### For Admins:

- **Dashboard Analytics**: Comprehensive overview of alumni data and metrics
- **Alumni Bank**: Advanced alumni management with filtering and organization
- **Survey Management**: Create, manage, and analyze surveys
- **Data Export**: Export data for reporting and research
- **Batch Organization**: Organize alumni by graduation batches

## Authentication

### Admin Registration (First Time Setup)

```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "admin"
  }'
```

### Admin Login

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@alumnitracer.edu",
    "password": "password"
  }'
```

**Save the token from the response for all subsequent admin requests.**

### Alumni Login (after registration)

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "alumni@example.com",
    "password": "alumnipassword"
  }'
```

## Alumni Flow Testing

### 1. Get Registration Survey (Public Access)

```bash
curl -X GET http://localhost:8000/api/v1/surveys/1
```

### 2. Start Survey Response

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/start \
  -H "Content-Type: application/json"
```

**Save the `response_token` from the response.**

### 3. Submit Survey Answers

#### Personal Information

```bash
# First Name
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 1,
    "answer": "John"
  }'

# Last Name
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 2,
    "answer": "Doe"
  }'

# Student ID
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 3,
    "answer": "STU12345"
  }'

# Email
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 4,
    "answer": "john.doe@email.com"
  }'
```

#### Academic Information

```bash
# Degree Program
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 8,
    "answer": "Bachelor of Science in Computer Science"
  }'

# Major
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 9,
    "answer": "Computer Science"
  }'

# Graduation Year
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 10,
    "answer": 2022
  }'
```

#### Employment Information

```bash
# Employment Status
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 12,
    "answer": "Employed Full-time"
  }'

# Job Title
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 13,
    "answer": "Software Developer"
  }'

# Employer
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 14,
    "answer": "Tech Company Inc."
  }'
```

### 4. Check Survey Progress

```bash
curl -X GET "http://localhost:8000/api/v1/surveys/1/progress?response_token=YOUR_RESPONSE_TOKEN"
```

### 5. Complete Survey and Create Alumni Account

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/complete \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "email": "john.doe@email.com",
    "password": "alumnipass123"
  }'
```

### 6. Access Alumni Profile

```bash
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer ALUMNI_TOKEN"
```

## Admin Dashboard Testing

### Dashboard Overview

```bash
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Returns:**

- Total alumni, surveys, batches, responses
- Response rates and recent activity
- Batch distribution and employment statistics
- Recent surveys and monthly registration trends

### Get Current User Profile

```bash
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## Alumni Bank Testing

### Basic Alumni Management

#### Get All Alumni

```bash
curl -X GET http://localhost:8000/api/v1/admin/alumni \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Get Alumni Statistics

```bash
curl -X GET http://localhost:8000/api/v1/admin/alumni/stats \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Get Individual Alumni Profile

```bash
curl -X GET http://localhost:8000/api/v1/admin/alumni/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### Advanced Filtering and Organization

#### Filter by Graduation Batch

```bash
# By specific batch ID
curl -X GET "http://localhost:8000/api/v1/admin/alumni?batch_id=3" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# By graduation year
curl -X GET "http://localhost:8000/api/v1/admin/alumni?graduation_year=2022" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Employment Status

```bash
curl -X GET "http://localhost:8000/api/v1/admin/alumni?employment_status=employed_full_time" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Academic Information

```bash
# By degree program
curl -X GET "http://localhost:8000/api/v1/admin/alumni?degree_program=Computer%20Science" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# By major
curl -X GET "http://localhost:8000/api/v1/admin/alumni?major=Computer%20Science" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Company and Location

```bash
# By company
curl -X GET "http://localhost:8000/api/v1/admin/alumni?company=Tech%20Company" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# By location
curl -X GET "http://localhost:8000/api/v1/admin/alumni?location=California" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Mentorship Willingness

```bash
# Alumni willing to mentor
curl -X GET "http://localhost:8000/api/v1/admin/alumni?willing_to_mentor=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Alumni willing to hire
curl -X GET "http://localhost:8000/api/v1/admin/alumni?willing_to_hire=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Advanced Search

```bash
# Search across multiple fields
curl -X GET "http://localhost:8000/api/v1/admin/alumni?search=john" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Combined Filters

```bash
curl -X GET "http://localhost:8000/api/v1/admin/alumni?graduation_year=2022&employment_status=employed_full_time&willing_to_mentor=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Sorting Options

```bash
# Sort by name
curl -X GET "http://localhost:8000/api/v1/admin/alumni?sort_by=name&sort_order=asc" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Sort by graduation year
curl -X GET "http://localhost:8000/api/v1/admin/alumni?sort_by=graduation_year&sort_order=desc" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## Survey Management Testing

### Get All Surveys

```bash
curl -X GET http://localhost:8000/api/v1/admin/surveys \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### Survey Analytics

```bash
# Get survey responses and analytics
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/responses \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Include individual responses
curl -X GET "http://localhost:8000/api/v1/admin/surveys/1/responses?include_responses=true" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter responses by batch
curl -X GET "http://localhost:8000/api/v1/admin/surveys/1/responses?batch_id=3" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### Batch Management

```bash
curl -X GET http://localhost:8000/api/v1/admin/batches \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## Data Export Testing

### Export Alumni Data

```bash
# Export all alumni
curl -X GET http://localhost:8000/api/v1/admin/alumni/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Export with filters
curl -X GET "http://localhost:8000/api/v1/admin/alumni/export?batch_id=3&employment_status=employed_full_time" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### Export Survey Responses

```bash
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Export Format:**

- Alumni: Name, Email, Phone, Batch, Year, Employment Status, Position, Company, Industry, Registration Date
- Survey Responses: Respondent Email, Submitted At, [Question columns...]
- Data returned as base64-encoded CSV content

## Error Handling

### Common HTTP Status Codes

- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized (missing or invalid token)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found
- `422`: Validation Error

### Error Response Format

```json
{
  "success": false,
  "message": "Error description",
  "errors": {...}
}
```

### Success Response Format

```json
{
  "success": true,
  "data": {...}
}
```

## Testing Scenarios

### Scenario 1: Complete Alumni Registration Flow

```bash
# 1. Admin login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@alumnitracer.edu", "password": "password"}'

# 2. Get registration survey
curl -X GET http://localhost:8000/api/v1/surveys/1

# 3. Start survey response
curl -X POST http://localhost:8000/api/v1/surveys/1/start \
  -H "Content-Type: application/json"

# 4. Submit required answers (use actual response_token)
# [Submit all required fields as shown in Alumni Flow section]

# 5. Complete survey and create account
curl -X POST http://localhost:8000/api/v1/surveys/1/complete \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "ACTUAL_TOKEN",
    "email": "newuser@email.com",
    "password": "password123"
  }'

# 6. Login as new alumni
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "newuser@email.com", "password": "password123"}'
```

### Scenario 2: Admin Dashboard Analysis

```bash
# 1. Admin login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@alumnitracer.edu", "password": "password"}'

# 2. Dashboard overview
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer TOKEN"

# 3. Alumni statistics
curl -X GET http://localhost:8000/api/v1/admin/alumni/stats \
  -H "Authorization: Bearer TOKEN"

# 4. Batch analysis
curl -X GET http://localhost:8000/api/v1/admin/batches \
  -H "Authorization: Bearer TOKEN"

# 5. Survey analytics
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/responses \
  -H "Authorization: Bearer TOKEN"
```

### Scenario 3: Alumni Organization by Batch

```bash
# 1. Get all batches
curl -X GET http://localhost:8000/api/v1/admin/batches \
  -H "Authorization: Bearer TOKEN"

# 2. Filter alumni by specific batch
curl -X GET "http://localhost:8000/api/v1/admin/alumni?batch_id=3" \
  -H "Authorization: Bearer TOKEN"

# 3. Export batch-specific data
curl -X GET "http://localhost:8000/api/v1/admin/alumni/export?batch_id=3" \
  -H "Authorization: Bearer TOKEN"
```

### Scenario 4: Employment Analysis

```bash
# 1. Get employment statistics
curl -X GET http://localhost:8000/api/v1/admin/alumni/stats \
  -H "Authorization: Bearer TOKEN"

# 2. Filter by employment status
curl -X GET "http://localhost:8000/api/v1/admin/alumni?employment_status=employed_full_time" \
  -H "Authorization: Bearer TOKEN"

# 3. Find mentors
curl -X GET "http://localhost:8000/api/v1/admin/alumni?willing_to_mentor=1&employment_status=employed_full_time" \
  -H "Authorization: Bearer TOKEN"
```

## Logout

```bash
curl -X POST http://localhost:8000/api/v1/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Notes

- Replace `YOUR_ADMIN_TOKEN`, `YOUR_RESPONSE_TOKEN`, and `TOKEN` with actual values from API responses
- The system uses Laravel Sanctum for authentication
- All admin endpoints require both authentication and admin role
- Survey responses are tracked and stored with detailed analytics
- CSV exports include base64-encoded content that needs to be decoded
- The Alumni Bank provides comprehensive filtering and organization capabilities
- All endpoints return consistent JSON responses with success/error indicators

## Key Features Tested

✅ **Alumni Registration Flow**: Complete survey-based registration process
✅ **Admin Authentication**: Secure admin access with role-based permissions
✅ **Dashboard Analytics**: Comprehensive metrics and statistics
✅ **Alumni Bank**: Advanced filtering, searching, and organization
✅ **Survey Management**: Creation, analytics, and response tracking
✅ **Batch Organization**: Group alumni by graduation batches
✅ **Data Export**: CSV export for reporting and research
✅ **Error Handling**: Consistent error responses and status codes

The Alumni Tracer System provides a complete solution for tracking alumni data, managing surveys, and generating insights for institutional reporting and alumni engagement.
