# Invoice Number Format Configuration

## Overview
This feature allows administrators to configure a custom format for invoice numbering through the System Preferences API.

## Configuration Key
- **Key**: `invoice_number_format`
- **Type**: String
- **Description**: Format string for invoice numbering. Must contain {year}, {month}, and {number} placeholders.

## Required Placeholders
The invoice number format must contain all three of the following placeholders:
- `{year}` - The year component
- `{month}` - The month component
- `{number}` - The sequential number component

## Validation Rules
1. The format value must be a string
2. The format must contain all three required placeholders: `{year}`, `{month}`, and `{number}`
3. Custom text and additional characters are allowed
4. Empty or null values are not allowed

## API Usage

### Create Invoice Number Format Preference

```bash
POST /api/system-preferences
Authorization: Bearer {token}
Content-Type: application/json

{
  "preferenceKey": "invoice_number_format",
  "value": "{year}/{month}/{number}"
}
```

### Valid Format Examples

```json
// Simple format
{"value": "{year}/{month}/{number}"}

// With prefix
{"value": "INV-{year}-{month}-{number}"}

// Complex format with custom text
{"value": "INVOICE/{year}/{month}/NO/{number}/END"}

// Using different separators
{"value": "{year}_{month}_{number}"}
```

### Invalid Format Examples

```json
// Missing {year} - INVALID
{"value": "{month}/{number}"}

// Missing {month} - INVALID
{"value": "{year}/{number}"}

// Missing {number} - INVALID
{"value": "{year}/{month}"}

// Missing all placeholders - INVALID
{"value": "INVOICE"}

// Empty value - INVALID
{"value": ""}
```

## Error Responses

### Missing Placeholder Error
When a required placeholder is missing, you'll receive a 422 Unprocessable Entity response:

```json
{
  "@type": "ConstraintViolationList",
  "detail": "value: The invoice number format must contain the {year} placeholder.",
  "violations": [
    {
      "propertyPath": "value",
      "message": "The invoice number format must contain the {year} placeholder.",
      "code": null
    }
  ]
}
```

### Non-String Value Error
If a non-string value is provided:

```json
{
  "@type": "ConstraintViolationList",
  "detail": "value: The invoice number format must be a string.",
  "violations": [
    {
      "propertyPath": "value",
      "message": "The invoice number format must be a string.",
      "code": null
    }
  ]
}
```

## Update Format

```bash
PATCH /api/system-preferences/{id}
Authorization: Bearer {token}
Content-Type: application/merge-patch+json

{
  "value": "{year}-{month}-{number}"
}
```

## Get Current Format

```bash
GET /api/system-preferences/{id}
Authorization: Bearer {token}
```

## Implementation Details

### Validator Components
- **Constraint**: `App\Validator\Constraints\InvoiceNumberFormat`
- **Validator**: `App\Validator\Constraints\InvoiceNumberFormatValidator`
- **Entity Validator**: `App\Validator\Constraints\ValidSystemPreferenceValue`

### Validation Logic
The validation is applied automatically when creating or updating a SystemPreference with the key `invoice_number_format`. The validator checks:
1. Value is not null or empty
2. Value is a string
3. Value contains `{year}` placeholder
4. Value contains `{month}` placeholder
5. Value contains `{number}` placeholder

### Extensibility
The validation system is designed to be extensible:
- Additional placeholders can be added in the future
- Custom validation logic can be added for other preference keys
- The `ValidSystemPreferenceValueValidator` can be extended to validate other preference types

## Testing
Comprehensive test coverage is provided in:
- `tests/Functional/InvoiceNumberFormatValidationTest.php`

The test suite covers:
- Valid format creation
- Invalid format rejection (missing each placeholder)
- Complex format patterns
- Empty/null value rejection
- Non-string value rejection
- Format updates
- Ensuring other preference keys are not affected

## Security
- Only users with `ROLE_ADMIN` can create or modify system preferences
- All API calls require JWT authentication
- Input validation prevents invalid configurations

## Future Enhancements
Potential future additions:
- Additional placeholders (e.g., `{customer_id}`, `{country_code}`)
- Format preview/validation endpoint
- Actual invoice number generation using the configured format
- Format templates library
