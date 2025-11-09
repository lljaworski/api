# Invoice Next Number API Endpoint

## Overview

The Invoice Next Number endpoint provides a way to preview the next available invoice number for a given issue date without actually creating an invoice. This is useful for:

- Pre-filling invoice forms with the next available number
- Validating invoice number sequences
- Planning invoice creation
- Testing invoice numbering logic

## Endpoint

```
GET /api/invoices/next-number
```

### Authentication

- **Required**: Yes (JWT Bearer token)
- **Required Role**: `ROLE_B2B`

### Query Parameters

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `date` | string | No | Invoice issue date in `YYYY-MM-DD` format. Defaults to today if not provided. | `2025-12-25` |

### Response Format

```json
{
  "nextNumber": "FV/2025/11/0001",
  "format": "FV/{year}/{month}/{number:4}",
  "issueDate": "2025-11-09",
  "sequenceNumber": 1
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `nextNumber` | string | The complete next invoice number that would be assigned |
| `format` | string | The current invoice number format template from settings |
| `issueDate` | string | The issue date used for generation (YYYY-MM-DD) |
| `sequenceNumber` | integer | The sequence number for the given month |

## Invoice Number Format

### Format Configuration

Invoice number formats are stored in the `invoice_settings` table and can be configured through the `/api/invoice-settings` endpoint (requires `ROLE_ADMIN`).

### Format Placeholders

The format must contain these required placeholders:

| Placeholder | Description | Example Output |
|-------------|-------------|----------------|
| `{year}` | 4-digit year | `2025` |
| `{month}` | 2-digit month (01-12) | `11` |
| `{number}` | Sequence number (no padding) | `1`, `123` |
| `{number:N}` | Sequence number with N-digit padding | `{number:4}` → `0001` |

### Valid Format Examples

```
FV/{year}/{month}/{number:4}          → FV/2025/11/0001
INV-{year}-{month}-{number:6}         → INV-2025-11-000001
{year}.{month}.{number}               → 2025.11.1
INVOICE_{year}_{month}_{number:5}     → INVOICE_2025_11_00001
{year}{month}{number:3}               → 20251100001
```

### Sequence Numbering

- Sequence numbers reset each month
- Sequence numbers start at 1 for each new month
- Deleted invoices are not counted in the sequence
- Sequences are calculated based on existing invoices in the database

## Usage Examples

### cURL Examples

#### Get Next Number for Today
```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -H "Accept: application/json" \
     http://localhost:8000/api/invoices/next-number
```

#### Get Next Number for Specific Date
```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -H "Accept: application/json" \
     "http://localhost:8000/api/invoices/next-number?date=2025-12-25"
```

#### Get Authentication Token
```bash
curl -X POST http://localhost:8000/api/login_check \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123!"}'
```

### JavaScript/Fetch Example

```javascript
async function getNextInvoiceNumber(date = null) {
  const token = localStorage.getItem('jwt_token');
  const url = new URL('http://localhost:8000/api/invoices/next-number');
  
  if (date) {
    url.searchParams.append('date', date);
  }
  
  const response = await fetch(url, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  return await response.json();
}

// Usage
try {
  const result = await getNextInvoiceNumber('2025-12-25');
  console.log('Next invoice number:', result.nextNumber);
} catch (error) {
  console.error('Error:', error);
}
```

### PHP Example

```php
<?php

function getNextInvoiceNumber(string $token, ?string $date = null): array
{
    $url = 'http://localhost:8000/api/invoices/next-number';
    
    if ($date !== null) {
        $url .= '?' . http_build_query(['date' => $date]);
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode !== 200) {
        throw new Exception("API request failed with status code: $statusCode");
    }
    
    return json_decode($response, true);
}

// Usage
try {
    $token = 'your_jwt_token_here';
    $result = getNextInvoiceNumber($token, '2025-12-25');
    echo "Next invoice number: " . $result['nextNumber'] . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
```

## Error Responses

### 400 Bad Request - Invalid Date Format
```json
{
  "title": "An error occurred",
  "detail": "Invalid date format: invalid-date. Expected format: YYYY-MM-DD",
  "status": 400,
  "type": "/errors/400"
}
```

**Causes:**
- Date parameter is not in `YYYY-MM-DD` format
- Invalid date values (e.g., month 13, day 32)
- Malformed date string

### 401 Unauthorized
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

**Causes:**
- Missing `Authorization` header
- Invalid or expired JWT token

### 403 Forbidden
```json
{
  "title": "An error occurred",
  "detail": "Access Denied.",
  "status": 403,
  "type": "/errors/403"
}
```

**Causes:**
- User does not have `ROLE_B2B` role
- Token is valid but user lacks required permissions

## Configuration

### Viewing Current Format

```bash
GET /api/invoice-settings
```

**Authentication Required**: `ROLE_B2B`

**Response:**
```json
{
  "id": 1,
  "numberFormat": "FV/{year}/{month}/{number:4}",
  "createdAt": "2025-11-09T21:27:42+00:00",
  "updatedAt": "2025-11-09T21:27:42+00:00"
}
```

### Updating Format

```bash
PATCH /api/invoice-settings
Content-Type: application/merge-patch+json
Authorization: Bearer ADMIN_JWT_TOKEN

{
  "numberFormat": "INV-{year}-{month}-{number:6}"
}
```

**Authentication Required**: `ROLE_ADMIN`

**Validation:**
- Format must contain `{year}`, `{month}`, and `{number}` (or `{number:N}`) placeholders
- Maximum length: 255 characters

## Thread Safety

### Preview Behavior
- The endpoint **does not** reserve or lock invoice numbers
- Multiple concurrent requests will return the same next number
- The actual invoice number is assigned only when creating an invoice

### Sequence Generation
- Sequence numbers are calculated based on existing invoices in the database
- Invoice creation uses database transactions to ensure atomicity
- The `getNextSequenceNumber()` method counts invoices within the month

### Best Practices
- Call this endpoint just before invoice creation for most accurate preview
- Don't rely on the returned number remaining available for extended periods
- Handle potential conflicts if invoice numbers are created concurrently

## Technical Notes

### Date Handling
- Dates are strictly validated using `DateTime::createFromFormat('Y-m-d', $date)`
- Invalid dates (e.g., 2025-13-01) are rejected
- Timezone is server timezone (typically UTC in Docker)

### Database Queries
- Sequence calculation queries are optimized with database indexes
- Soft-deleted invoices are excluded from sequence counting
- Queries use Doctrine ORM with proper parameter binding

### Caching
- No caching is applied to this endpoint
- Each request generates a fresh calculation
- Settings are fetched from database on each request

## Testing

The feature includes comprehensive test coverage:

### Functional Tests
- Authentication and authorization
- Date parameter handling
- Invalid input validation
- Response format verification

### Unit Tests
- Format validator tests
- Invoice number generator tests
- Date parsing and validation

Run tests with:
```bash
php vendor/bin/phpunit tests/Functional/InvoiceNextNumberTest.php
php vendor/bin/phpunit tests/Unit/Validator/InvoiceNumberFormatValidatorTest.php
```

## Migration

The feature includes a database migration that:
1. Creates `invoice_settings` table
2. Inserts default format: `FV/{year}/{month}/{number:4}`

Run migration:
```bash
php bin/console doctrine:migrations:migrate
```

## Related Endpoints

- `GET /api/invoices` - List all invoices
- `POST /api/invoices` - Create new invoice
- `GET /api/invoice-settings` - View current settings
- `PATCH /api/invoice-settings` - Update settings (admin only)

## Support

For issues or questions:
- Check API documentation at `/api/docs`
- Review test files for usage examples
- Consult CQRS architecture documentation
