# Alumni Bank API Testing Guide

This guide covers testing the comprehensive Alumni Bank functionality for managing and organizing alumni data.

## Prerequisites

Make sure you have an admin token. Login as admin first:

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@alumnitracer.edu",
    "password": "password"
  }'
```

Use the token from the response in all subsequent requests.

## Alumni Bank Endpoints

### 1. Get All Alumni (Enhanced with Comprehensive Filtering)

#### Basic Alumni List

```bash
curl -X GET http://localhost:8000/api/v1/admin/alumni \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Graduation Batch

```bash
# Filter by specific batch ID
curl -X GET "http://localhost:8000/api/v1/admin/alumni?batch_id=3" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by graduation year
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

#### Filter by Degree Program

```bash
curl -X GET "http://localhost:8000/api/v1/admin/alumni?degree_program=Computer Science" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Major

```bash
curl -X GET "http://localhost:8000/api/v1/admin/alumni?major=Computer Science" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Company/Employer

```bash
curl -X GET "http://localhost:8000/api/v1/admin/alumni?company=Tech Company" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Filter by Location

```bash
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
# Search across multiple fields (name, email, student ID, job title, employer)
curl -X GET "http://localhost:8000/api/v1/admin/alumni?search=john" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Combined Filters

```bash
# Multiple filters together
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

# Sort by employment status
curl -X GET "http://localhost:8000/api/v1/admin/alumni?sort_by=employment_status&sort_order=asc" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Pagination

```bash
# Custom page size
curl -X GET "http://localhost:8000/api/v1/admin/alumni?per_page=10&page=2" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 2. Get Alumni Statistics & Analytics

Get comprehensive statistics about all alumni:

```bash
curl -X GET http://localhost:8000/api/v1/admin/alumni/stats \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**This returns:**

- Total alumni count
- Batch-wise distribution
- Employment status breakdown
- Top 10 employers
- Degree program distribution
- Major distribution (top 15)
- Geographic distribution (top 20 locations)
- Mentorship and hiring willingness statistics

### 3. Get Individual Alumni Profile

Get detailed profile of a specific alumni:

```bash
# Replace {id} with actual alumni profile ID
curl -X GET http://localhost:8000/api/v1/admin/alumni/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**This returns:**

- Complete alumni profile information
- All survey responses by this alumni
- Response count

### 4. Export Alumni Data (Enhanced)

Export filtered alumni data:

```bash
# Export all alumni
curl -X GET http://localhost:8000/api/v1/admin/alumni/export \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Export specific batch
curl -X GET "http://localhost:8000/api/v1/admin/alumni/export?batch_id=3" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Export by employment status
curl -X GET "http://localhost:8000/api/v1/admin/alumni/export?employment_status=employed_full_time" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## Testing Scenarios

### Scenario 1: Organize Alumni by Graduation Batch

```bash
# 1. Get all batches to see available options
curl -X GET http://localhost:8000/api/v1/admin/batches \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Filter alumni by Class of 2022 (batch_id=3)
curl -X GET "http://localhost:8000/api/v1/admin/alumni?batch_id=3" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 3. Get statistics for alumni analytics
curl -X GET http://localhost:8000/api/v1/admin/alumni/stats \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Scenario 2: Find Alumni for Mentorship Program

```bash
# Find alumni willing to mentor from recent graduates
curl -X GET "http://localhost:8000/api/v1/admin/alumni?willing_to_mentor=1&graduation_year=2022&sort_by=name" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Scenario 3: Employment Analysis

```bash
# 1. Get employment statistics
curl -X GET http://localhost:8000/api/v1/admin/alumni/stats \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Filter by employment status
curl -X GET "http://localhost:8000/api/v1/admin/alumni?employment_status=employed_full_time" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 3. Export employment data for reporting
curl -X GET "http://localhost:8000/api/v1/admin/alumni/export?employment_status=employed_full_time" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Scenario 4: Program-Specific Analysis

```bash
# Find all Computer Science graduates
curl -X GET "http://localhost:8000/api/v1/admin/alumni?major=Computer Science&sort_by=graduation_year&sort_order=desc" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Scenario 5: Geographic Analysis

```bash
# Find alumni in specific location
curl -X GET "http://localhost:8000/api/v1/admin/alumni?location=California" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## Response Format Examples

### Alumni List Response

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "first_name": "John",
                "last_name": "Doe",
                "employment_status": "employed_full_time",
                "current_job_title": "Software Developer",
                "current_employer": "Tech Company Inc.",
                "batch": {
                    "id": 3,
                    "name": "Class of 2022",
                    "graduation_year": 2022
                },
                "user": {
                    "id": 3,
                    "email": "john.doe@email.com"
                }
            }
        ],
        "total": 1,
        "per_page": 15
    },
    "filter_summary": {
        "total_filtered": 1,
        "employment_breakdown": {
            "employed_full_time": 1
        }
    }
}
```

### Alumni Statistics Response

```json
{
    "success": true,
    "data": {
        "overview": {
            "total_alumni": 1,
            "total_batches": 5
        },
        "batch_distribution": [
            {
                "batch_id": 3,
                "batch_name": "Class of 2022",
                "graduation_year": 2022,
                "alumni_count": 1
            }
        ],
        "employment_stats": {
            "employed_full_time": 1
        },
        "top_employers": [
            {
                "current_employer": "Tech Company Inc.",
                "count": 1
            }
        ]
    }
}
```

## Key Features

1. **Comprehensive Filtering**: Filter by batch, year, employment, degree, major, company, location, mentorship willingness
2. **Advanced Search**: Search across multiple fields simultaneously
3. **Flexible Sorting**: Sort by name, graduation year, employment status, registration date
4. **Detailed Analytics**: Get statistics and breakdowns for better insights
5. **Individual Profiles**: Access detailed information for each alumni
6. **Export Capabilities**: Export filtered data for reporting
7. **Filter Summaries**: Get summary statistics for current filter results

The Alumni Bank now provides a comprehensive tool for organizing, filtering, and managing alumni data with powerful analytics capabilities!
