# Alumni Tracer API Testing Guide

This document shows how to test the Alumni Tracer System API using curl commands.

## Base URL

```
http://localhost:8000/api/v1
```

## 1. Health Check

```bash
curl -X GET http://localhost:8000/api/health
```

## 2. Admin Registration

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

## 3. Admin Login

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@alumnitracer.edu",
    "password": "password"
  }'
```

Save the token from the response for authenticated requests.

## 3.1. Test Admin Dashboard (New!)

Once logged in as admin, test the dashboard endpoints:

```bash
# Get dashboard overview
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# View all alumni
curl -X GET http://localhost:8000/api/v1/admin/alumni \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# View all surveys with stats
curl -X GET http://localhost:8000/api/v1/admin/surveys \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Get survey analytics (replace 1 with survey ID)
curl -X GET http://localhost:8000/api/v1/admin/surveys/1/responses \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# View all batches
curl -X GET http://localhost:8000/api/v1/admin/batches \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Export alumni data
curl -X GET http://localhost:8000/api/v1/admin/alumni/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## 4. Get Current User Profile

```bash
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 5. Get Registration Survey (Public Access)

The seeder creates a registration survey with ID 1. Get it:

```bash
curl -X GET http://localhost:8000/api/v1/surveys/1
```

## 6. Start Survey Response (Alumni Registration Flow)

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/start \
  -H "Content-Type: application/json"
```

Save the `response_token` from the response for subsequent requests.

## 7. Submit Answers to Survey Questions

### Submit First Name

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 1,
    "answer": "John"
  }'
```

### Submit Last Name

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 2,
    "answer": "Doe"
  }'
```

### Submit Student ID

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 3,
    "answer": "STU12345"
  }'
```

### Submit Email

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 4,
    "answer": "john.doe@email.com"
  }'
```

### Submit Phone (Optional)

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 5,
    "answer": "+1234567890"
  }'
```

### Submit Date of Birth (Optional)

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 6,
    "answer": "1995-06-15"
  }'
```

### Submit Gender (Optional)

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "YOUR_RESPONSE_TOKEN",
    "question_id": 7,
    "answer": "Male"
  }'
```

### Submit Degree Program

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 8,
    "answer": "Bachelor of Science in Computer Science"
  }'
```

### Submit Major

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 9,
    "answer": "Computer Science"
  }'
```

### Submit Graduation Year

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 10,
    "answer": 2022
  }'
```

### Submit Employment Status

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 12,
    "answer": "Employed Full-time"
  }'
```

### Submit Job Title (Optional)

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 13,
    "answer": "Software Developer"
  }'
```

### Submit Employer (Optional)

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/answer \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "question_id": 14,
    "answer": "Tech Company Inc."
  }'
```

## 8. Check Survey Progress

```bash
curl -X GET "http://localhost:8000/api/v1/surveys/1/progress?response_token=d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw"
```

## 9. Complete Survey and Register Alumni Account

This step completes the survey and creates the alumni account:

```bash
curl -X POST http://localhost:8000/api/v1/surveys/1/complete \
  -H "Content-Type: application/json" \
  -d '{
    "response_token": "d2IxqY6peR328HhQZSkWR7EBvyqtN71SUaCubTJw",
    "email": "john.doe@email.com",
    "password": "alumnipass123"
  }'
```

This will:

- Mark the survey as completed
- Create a new alumni user account
- Create an alumni profile with the survey data
- Return a login token for the new account

## 10. Login as the New Alumni

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@email.com",
    "password": "alumnipass123"
  }'
```

## 11. Get Alumni Profile

```bash
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer ALUMNI_TOKEN_HERE"
```

## 12. Logout

```bash
curl -X POST http://localhost:8000/api/v1/logout \
  -H "Authorization: Bearer UfOQanl144lCTaEwRG1FyT3grD5AIpenIgF2CNDk92f834de"
```

## Testing Flow Summary

1. **Admin Setup**: Register and login as admin
2. **Admin Dashboard**: Test dashboard metrics, alumni management, survey analytics, batch management, and data export
3. **Survey Access**: Get the registration survey (public)
4. **Alumni Registration**:
    - Start survey response
    - Submit answers to all required questions
    - Complete survey with email/password
    - System auto-creates alumni account
5. **Alumni Access**: Login as alumni and access profile
6. **Admin Analytics**: View alumni data, survey responses, and export reports

## Notes

- Replace `YOUR_TOKEN_HERE` with actual token from login response
- Replace `YOUR_RESPONSE_TOKEN` with actual response token from survey start
- The registration survey is pre-seeded with sample questions
- All survey responses are tracked and stored
- Alumni profiles are automatically created from survey responses
- Activity logs track all user actions

## Error Handling

The API returns consistent JSON responses:

- Success: `{"success": true, "data": {...}}`
- Error: `{"success": false, "message": "Error description", "errors": {...}}`

HTTP status codes follow REST conventions:

- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
