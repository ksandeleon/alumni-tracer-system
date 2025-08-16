# Survey Bank API Testing with cURL

A comprehensive guide for testing the Survey Bank functionality of the Alumni Tracer System API using cURL commands. The Survey Bank allows admins to create, edit, manage surveys and survey questions with advanced filtering capabilities.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Authentication](#authentication)
- [Survey Management](#survey-management)
- [Survey Questions Management](#survey-questions-management)
- [Survey Filtering](#survey-filtering)
- [Survey Analytics](#survey-analytics)
- [Data Export](#data-export)
- [Testing Scenarios](#testing-scenarios)
- [Error Handling](#error-handling)

## Prerequisites

1. **Start the Laravel development server:**

    ```bash
    cd /path/to/alumni-tracer
    php artisan serve
    ```

2. **Admin Authentication:**
    ```bash
    curl -X POST http://localhost:8000/api/v1/login \
      -H "Content-Type: application/json" \
      -d '{
        "email": "admin@alumnitracer.edu",
        "password": "password"
      }'
    ```
    **Save the token from the response for all subsequent requests.**

## Authentication

All Survey Bank endpoints require admin authentication. Include the Bearer token in all requests:

```
-H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## Survey Management

### 1. Get All Surveys (Enhanced with Filtering)

#### Basic Survey List

```bash
curl -X GET http://localhost:8000/api/v1/admin/surveys \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Survey Status

```bash
# Active surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?status=active" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Draft surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?status=draft" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Completed surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?status=completed" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Survey Type

```bash
# Registration surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?type=registration" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Feedback surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?type=feedback" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Employment surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?type=employment" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Search Surveys

```bash
# Search by title or description
curl -X GET "http://localhost:8000/api/v1/admin/surveys?search=registration" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Date Range

```bash
# Surveys created in specific date range
curl -X GET "http://localhost:8000/api/v1/admin/surveys?created_from=2024-01-01&created_to=2024-12-31" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Combined Filters

```bash
curl -X GET "http://localhost:8000/api/v1/admin/surveys?status=active&type=feedback&search=alumni" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Pagination and Sorting

```bash
# Paginated results
curl -X GET "http://localhost:8000/api/v1/admin/surveys?page=2&per_page=10" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Sort by creation date
curl -X GET "http://localhost:8000/api/v1/admin/surveys?sort_by=created_at&sort_order=desc" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Sort by response count
curl -X GET "http://localhost:8000/api/v1/admin/surveys?sort_by=responses_count&sort_order=desc" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 2. Create New Survey

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Alumni Employment Survey 2024",
    "description": "Annual employment and career satisfaction survey for alumni",
    "type": "employment",
    "status": "draft",
    "is_anonymous": false,
    "allow_multiple_responses": false,
    "requires_invitation": false,
    "start_date": "2024-09-01",
    "end_date": "2024-12-31",
    "instructions": "Please provide accurate information about your current employment status",
    "thank_you_message": "Thank you for participating in our alumni survey!"
  }'
```

### 3. Get Survey Details

```bash
# Get specific survey with all details
curl -X GET http://localhost:8000/api/v1/admin/surveys/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 4. Update Survey

```bash
curl -X PUT http://localhost:8000/api/v1/admin/surveys/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Alumni Employment Survey 2024",
    "description": "Updated description for the employment survey",
    "status": "active",
    "end_date": "2025-01-31"
  }'
```

### 5. Duplicate Survey

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/duplicate \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Alumni Employment Survey 2025",
    "status": "draft"
  }'
```

### 6. Delete Survey

```bash
curl -X DELETE http://localhost:8000/api/v1/admin/surveys/2 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## Survey Questions Management

### 1. Create Survey Question

#### Text Question

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is your current job title?",
    "question_type": "text",
    "is_required": true,
    "order_index": 1,
    "help_text": "Please provide your exact job title as it appears on your business card"
  }'
```

#### Multiple Choice Question

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is your current employment status?",
    "question_type": "radio",
    "is_required": true,
    "order_index": 2,
    "options": [
      "Employed Full-time",
      "Employed Part-time",
      "Self-employed",
      "Unemployed",
      "Student",
      "Retired"
    ],
    "help_text": "Select the option that best describes your current situation"
  }'
```

#### Dropdown Question

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What industry do you work in?",
    "question_type": "select",
    "is_required": false,
    "order_index": 3,
    "options": [
      "Technology",
      "Healthcare",
      "Education",
      "Finance",
      "Manufacturing",
      "Government",
      "Non-profit",
      "Other"
    ]
  }'
```

#### Rating Question

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "How satisfied are you with your current job?",
    "question_type": "rating",
    "is_required": true,
    "order_index": 4,
    "validation_rules": {
      "min": 1,
      "max": 5
    },
    "help_text": "Rate from 1 (Very Unsatisfied) to 5 (Very Satisfied)"
  }'
```

#### Checkbox Question

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "Which skills are most important in your current role?",
    "question_type": "checkbox",
    "is_required": false,
    "order_index": 5,
    "options": [
      "Communication",
      "Leadership",
      "Technical Skills",
      "Problem Solving",
      "Project Management",
      "Teamwork",
      "Analytical Thinking"
    ]
  }'
```

#### Date Question

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "When did you start your current job?",
    "question_type": "date",
    "is_required": false,
    "order_index": 6
  }'
```

#### Number Question

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "How many years of experience do you have in your field?",
    "question_type": "number",
    "is_required": false,
    "order_index": 7,
    "validation_rules": {
      "min": 0,
      "max": 50
    }
  }'
```

### 2. Update Survey Question

```bash
curl -X PUT http://localhost:8000/api/v1/admin/surveys/1/questions/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is your current job title and company?",
    "help_text": "Please provide both your job title and company name",
    "is_required": true
  }'
```

### 3. Reorder Survey Questions

```bash
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions/reorder \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_orders": [
      {"question_id": 3, "order_index": 1},
      {"question_id": 1, "order_index": 2},
      {"question_id": 2, "order_index": 3},
      {"question_id": 4, "order_index": 4}
    ]
  }'
```

### 4. Delete Survey Question

```bash
curl -X DELETE http://localhost:8000/api/v1/admin/surveys/1/questions/2 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## Survey Analytics

### 1. Get Survey Responses

```bash
# Basic survey responses
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

# Filter by completion status
curl -X GET "http://localhost:8000/api/v1/admin/surveys/1/responses?status=completed" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 2. Survey Statistics

The survey responses endpoint returns comprehensive analytics including:

- Total response count
- Completion rate
- Question-by-question breakdown
- Option counts for multiple choice questions
- Sample responses for text questions

## Data Export

### Export Survey Responses

```bash
# Export all responses
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Export with filters
curl -X GET "http://localhost:8000/api/v1/admin/surveys/1/export?batch_id=3&status=completed" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## Testing Scenarios

### Scenario 1: Create a Complete Employment Survey

```bash
# 1. Create the survey
curl -X POST http://localhost:8000/api/v1/admin/surveys \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Alumni Employment Survey 2024",
    "description": "Annual employment survey for graduates",
    "type": "employment",
    "status": "draft"
  }'

# Save the survey ID from response (e.g., ID: 2)

# 2. Add employment status question
curl -X POST http://localhost:8000/api/v1/admin/surveys/2/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is your current employment status?",
    "question_type": "radio",
    "is_required": true,
    "order_index": 1,
    "options": ["Employed Full-time", "Employed Part-time", "Self-employed", "Unemployed", "Student"]
  }'

# 3. Add job title question
curl -X POST http://localhost:8000/api/v1/admin/surveys/2/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is your current job title?",
    "question_type": "text",
    "is_required": false,
    "order_index": 2
  }'

# 4. Add salary question
curl -X POST http://localhost:8000/api/v1/admin/surveys/2/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is your annual salary range?",
    "question_type": "select",
    "is_required": false,
    "order_index": 3,
    "options": ["Under $30,000", "$30,000-$50,000", "$50,000-$75,000", "$75,000-$100,000", "Over $100,000"]
  }'

# 5. Activate the survey
curl -X PUT http://localhost:8000/api/v1/admin/surveys/2 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "active"}'

# 6. Get survey details to verify
curl -X GET http://localhost:8000/api/v1/admin/surveys/2 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Scenario 2: Manage Existing Survey

```bash
# 1. Get all surveys
curl -X GET http://localhost:8000/api/v1/admin/surveys \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Get specific survey details
curl -X GET http://localhost:8000/api/v1/admin/surveys/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 3. Update survey information
curl -X PUT http://localhost:8000/api/v1/admin/surveys/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Alumni Registration Survey",
    "description": "Updated comprehensive registration survey for new alumni"
  }'

# 4. Add new question
curl -X POST http://localhost:8000/api/v1/admin/surveys/1/questions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "Would you be interested in mentoring current students?",
    "question_type": "radio",
    "is_required": false,
    "order_index": 15,
    "options": ["Yes", "No", "Maybe in the future"]
  }'
```

### Scenario 3: Survey Analytics and Export

```bash
# 1. Get survey responses and analytics
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/responses \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Filter responses by specific batch
curl -X GET "http://localhost:8000/api/v1/admin/surveys/1/responses?batch_id=3" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 3. Export survey data
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Scenario 4: Survey Organization and Filtering

```bash
# 1. Get all employment surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?type=employment" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Get active surveys only
curl -X GET "http://localhost:8000/api/v1/admin/surveys?status=active" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 3. Search for specific surveys
curl -X GET "http://localhost:8000/api/v1/admin/surveys?search=employment" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 4. Get surveys with most responses
curl -X GET "http://localhost:8000/api/v1/admin/surveys?sort_by=responses_count&sort_order=desc" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## Error Handling

### Common HTTP Status Codes

- `200`: Success
- `201`: Created
- `400`: Bad Request (validation errors)
- `401`: Unauthorized
- `403`: Forbidden (admin access required)
- `404`: Not Found
- `422`: Validation Error

### Error Response Examples

#### Validation Error (422)

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": ["The title field is required."],
        "question_type": ["The selected question type is invalid."]
    }
}
```

#### Not Found Error (404)

```json
{
    "success": false,
    "message": "Survey not found"
}
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "id": 2,
        "title": "Alumni Employment Survey 2024",
        "status": "active",
        "questions_count": 5,
        "responses_count": 12
    }
}
```

## Question Types Reference

| Type       | Description                   | Options Required | Validation Rules         |
| ---------- | ----------------------------- | ---------------- | ------------------------ |
| `text`     | Single line text input        | No               | max_length               |
| `textarea` | Multi-line text input         | No               | max_length               |
| `email`    | Email input with validation   | No               | email format             |
| `number`   | Numeric input                 | No               | min, max                 |
| `date`     | Date picker                   | No               | date format              |
| `radio`    | Single choice (radio buttons) | Yes              | -                        |
| `checkbox` | Multiple choice (checkboxes)  | Yes              | min_choices, max_choices |
| `select`   | Dropdown selection            | Yes              | -                        |
| `rating`   | Rating scale                  | No               | min, max                 |
| `phone`    | Phone number input            | No               | phone format             |

## Survey Status Reference

| Status      | Description                            |
| ----------- | -------------------------------------- |
| `draft`     | Survey is being created/edited         |
| `active`    | Survey is live and accepting responses |
| `paused`    | Survey is temporarily unavailable      |
| `completed` | Survey has ended                       |
| `archived`  | Survey is archived                     |

## Survey Type Reference

| Type           | Description                            |
| -------------- | -------------------------------------- |
| `registration` | Alumni registration surveys            |
| `employment`   | Employment and career surveys          |
| `feedback`     | Feedback and satisfaction surveys      |
| `academic`     | Academic and education-related surveys |
| `event`        | Event-specific surveys                 |
| `research`     | Research and data collection surveys   |
| `other`        | Other survey types                     |

## Tips for Testing

1. **Always authenticate first** - Get an admin token before testing Survey Bank endpoints
2. **Save IDs** - Save survey and question IDs from creation responses for subsequent operations
3. **Test validation** - Try creating surveys/questions with missing required fields to test validation
4. **Use meaningful data** - Use realistic survey titles and questions for better testing
5. **Test filters** - Combine multiple filters to test complex search scenarios
6. **Check responses** - Verify that survey changes are reflected in the survey details endpoint
7. **Test ordering** - Create multiple questions and test the reordering functionality
8. **Export testing** - Test CSV exports with both small and large datasets

The Survey Bank provides comprehensive survey management capabilities with powerful filtering and organization features for effective alumni data collection and analysis.
