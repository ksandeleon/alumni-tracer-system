# Admin API Testing Guide

This guide covers all the admin endpoints for the Alumni Tracer System. Admin endpoints require authentication with an admin role.

## Prerequisites

1. Have the Laravel development server running:

    ```bash
    php artisan serve
    ```

2. Ensure you have an admin user (created by the seeder):
    - Email: admin@university.edu
    - Password: password
    - Role: admin

## Authentication

First, get an admin token by logging in:

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@university.edu",
    "password": "password"
  }'
```

Save the token from the response. Use it in all subsequent admin requests as a Bearer token.

## Admin Dashboard Endpoints

### 1. Dashboard Overview

Get comprehensive dashboard metrics including total counts, recent activity, batch distribution, employment stats, and trends.

```bash
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Response includes:**

- Overview: total alumni, surveys, batches, responses, response rate
- Recent activity: registrations and responses in last 30 days
- Batch distribution: alumni count per batch
- Employment statistics: distribution by employment status
- Recent surveys: last 5 surveys with response counts
- Monthly trend: registration trend for last 12 months

### 2. Alumni Management

#### Get All Alumni (with filtering and pagination)

```bash
# Get all alumni
curl -X GET http://localhost:8000/api/v1/admin/alumni \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by batch
curl -X GET "http://localhost:8000/api/v1/admin/alumni?batch_id=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by employment status
curl -X GET "http://localhost:8000/api/v1/admin/alumni?employment_status=employed" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Search by name or email
curl -X GET "http://localhost:8000/api/v1/admin/alumni?search=john" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Pagination (15 per page by default)
curl -X GET "http://localhost:8000/api/v1/admin/alumni?page=2&per_page=10" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Export Alumni Data

Export alumni data to CSV format:

```bash
curl -X GET http://localhost:8000/api/v1/admin/alumni/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Export with filters
curl -X GET "http://localhost:8000/api/v1/admin/alumni/export?batch_id=1&employment_status=employed" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

The response includes base64-encoded CSV content that you can decode and save as a file.

### 3. Survey Management

#### Get All Surveys

```bash
# Get all surveys
curl -X GET http://localhost:8000/api/v1/admin/surveys \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by status
curl -X GET "http://localhost:8000/api/v1/admin/surveys?status=active" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Search surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?search=registration" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Get Survey Responses and Analytics

Get detailed analytics for a specific survey:

```bash
# Get survey analytics (replace 1 with actual survey ID)
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/responses \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Include individual responses in the data
curl -X GET "http://localhost:8000/api/v1/admin/surveys/1/responses?include_responses=true" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter responses by batch
curl -X GET "http://localhost:8000/api/v1/admin/surveys/1/responses?batch_id=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Analytics include:**

- Question-by-question breakdown
- Option counts for multiple choice questions
- Sample responses for text questions
- Total response count

#### Export Survey Responses

Export survey responses to CSV:

```bash
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 4. Batch Management

Get all batches with alumni counts:

```bash
curl -X GET http://localhost:8000/api/v1/admin/batches \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## Error Handling

### Authentication Errors

If you get a 401 error:

```json
{
    "success": false,
    "message": "Authentication required"
}
```

Make sure you're including the Bearer token in the Authorization header.

### Authorization Errors

If you get a 403 error:

```json
{
    "success": false,
    "message": "Admin access required"
}
```

This means your user doesn't have admin role. Make sure you're logged in as an admin user.

### Not Found Errors

If you get a 404 error when accessing survey-specific endpoints:

```json
{
    "success": false,
    "message": "Survey not found"
}
```

Make sure the survey ID exists in your database.

## Sample Workflow

Here's a complete workflow for testing admin functionality:

1. **Login as admin:**

    ```bash
    curl -X POST http://localhost:8000/api/v1/login \
      -H "Content-Type: application/json" \
      -d '{"email": "admin@university.edu", "password": "password"}'
    ```

2. **Get dashboard overview:**

    ```bash
    curl -X GET http://localhost:8000/api/v1/admin/dashboard \
      -H "Authorization: Bearer YOUR_TOKEN"
    ```

3. **View all alumni:**

    ```bash
    curl -X GET http://localhost:8000/api/v1/admin/alumni \
      -H "Authorization: Bearer YOUR_TOKEN"
    ```

4. **Check survey analytics:**

    ```bash
    curl -X GET http://localhost:8000/api/v1/admin/surveys/1/responses \
      -H "Authorization: Bearer YOUR_TOKEN"
    ```

5. **Export data:**
    ```bash
    curl -X GET http://localhost:8000/api/v1/admin/alumni/export \
      -H "Authorization: Bearer YOUR_TOKEN"
    ```

## CSV Export Format

### Alumni Export

- Name, Email, Phone, Batch, Year, Employment Status, Current Position, Company, Industry, Registration Date

### Survey Responses Export

- Respondent Name, Email, Submitted At, [Question 1], [Question 2], ... (one column per question)

The CSV content is returned as base64-encoded data in the response. To save it as a file, decode the base64 content.

## Tips

1. **Pagination:** Most list endpoints support pagination with `page` and `per_page` parameters
2. **Filtering:** Use query parameters to filter results (batch_id, employment_status, status, search)
3. **Large datasets:** Use export endpoints for large data sets rather than trying to paginate through everything
4. **Base64 decoding:** For CSV exports, remember to decode the base64 content before saving to file
5. **Survey IDs:** Use the surveys list endpoint to get valid survey IDs for analytics and export endpoints













===========

✅ Working Admin Endpoints:
Dashboard Overview (/admin/dashboard)

Total alumni: 1, Total surveys: 1, Total batches: 5
Response rate: 50%
Recent activity tracking
Batch distribution showing alumni per graduation year
Employment status breakdown
Monthly registration trends
Alumni Management (/admin/alumni)

Lists all alumni with their profiles
Pagination support (15 per page by default)
Filtering by batch and employment status
Search by name and email
Batch Management (/admin/batches)

All graduation batches with alumni counts
Shows classes from 2020-2024
Survey Analytics (/admin/surveys/{id}/responses)

Question-by-question analysis
Response statistics
Data Export (/admin/alumni/export, /admin/surveys/{id}/export)

CSV export functionality
✅ Security & Access Control:
Admin middleware protects all admin endpoints
Only users with role = 'admin' can access
Proper authentication via Bearer tokens
✅ What the Dashboard Shows:
1 alumni registered (John Doe from Class of 2022)
50% response rate (1 completed out of 2 total responses)
Employment distribution: 1 full-time employed
Monthly trend: 1 registration in August 2025
5 graduation batches available (2020-2024)
Now you can continue testing other endpoints or start building the frontend! The backend foundation for the admin dashboard is solid and working perfectly.
