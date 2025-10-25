# API Platform Instructions

## Project Structure

This is a Symfony 7.3 + API Platform 4.2 project with CQRS architecture.

## Entity Configuration

### User Entity
- Located at `src/Entity/User.php`
- Implements soft delete functionality
- Uses custom state provider and processor for CQRS integration
- Validation groups: `user:create`, `user:update`

### API Resources
```php
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    provider: UserProvider::class,
    processor: UserProcessor::class,
    security: "is_granted('ROLE_ADMIN')"
)]
```

## State Providers and Processors

### Custom State Providers
- Located in `src/State/`
- Integrate with CQRS query bus
- Convert DTOs back to entities for API Platform compatibility
- Handle pagination and filtering

### Custom State Processors
- Located in `src/State/`
- Integrate with CQRS command bus
- Handle create, update, and delete operations
- Extract fields from request context for partial updates

## Security

### Authentication
- JWT-based authentication using `lexik/jwt-authentication-bundle`
- Login endpoint: `POST /api/login_check`
- Returns JWT token for subsequent requests

### Authorization
- Role-based access control
- `ROLE_ADMIN` required for user management operations
- `ROLE_USER` for basic authenticated access

## Validation

### Validation Groups
- `user:create`: Required fields for user creation
- `user:update`: Fields that can be updated (partial validation for PATCH)

### Password Validation
- Minimum 6 characters for creation
- Password length validation only applies to `user:create` group
- Allows PATCH operations without password validation

## Serialization Groups

### Response Groups
- `user:read`: Basic user information
- `user:list`: Information for collection responses
- `user:details`: Detailed user information

### Request Groups  
- `user:create`: Fields accepted during creation
- `user:update`: Fields accepted during updates

## API Endpoints

### User Management
```
GET    /api/users          # List users (paginated)
GET    /api/users/{id}     # Get single user
POST   /api/users          # Create user
PUT    /api/users/{id}     # Update user (full)
PATCH  /api/users/{id}     # Update user (partial)
DELETE /api/users/{id}     # Soft delete user
```

### Authentication
```
POST   /api/login_check    # Authenticate and get JWT token
```

### Health Check
```
GET    /api/health         # Health check endpoint
GET    /api/ping           # Simple ping endpoint
```

## Request/Response Examples

### Create User
```bash
POST /api/users
Content-Type: application/json
Authorization: Bearer {jwt_token}

{
    "username": "john_doe",
    "password": "password123",
    "roles": ["ROLE_USER"]
}
```

### Partial Update
```bash
PATCH /api/users/1
Content-Type: application/merge-patch+json
Authorization: Bearer {jwt_token}

{
    "roles": ["ROLE_ADMIN"]
}
```

## Error Handling

### Standard HTTP Status Codes
- `200`: Success
- `201`: Created
- `204`: No Content (delete)
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error

### Error Response Format
```json
{
    "@context": "/api/contexts/Error",
    "@id": "/api/errors/404",
    "@type": "Error",
    "title": "An error occurred",
    "detail": "Not Found",
    "status": 404
}
```

## Best Practices

1. **Always use Authorization header** with Bearer token for protected endpoints
2. **Use appropriate Content-Type** (`application/json` for POST/PUT, `application/merge-patch+json` for PATCH)
3. **Handle validation errors** by checking for 422 status and `violations` array
4. **Test with different roles** to ensure proper authorization
5. **Use pagination parameters** for collection endpoints (`page`, `itemsPerPage`)
6. **Implement proper error handling** for all status codes

## Custom Serialization Format

### Collection Response Format (JSON)
**IMPORTANT**: This project uses a custom collection serialization format for `application/json` requests. The `JsonCollectionNormalizer` (located in `src/Serializer/JsonCollectionNormalizer.php`) transforms API Platform's standard Hydra format into a custom format.

#### Standard API Platform Hydra Format (application/ld+json):
```json
{
  "@context": "/api/contexts/Resource",
  "@id": "/api/resources",
  "@type": "hydra:Collection",
  "hydra:member": [...],
  "hydra:totalItems": 15,
  "hydra:view": {...}
}
```

#### Custom JSON Format (application/json):
```json
{
  "data": [...],
  "pagination": {
    "total": 15.0,
    "count": 15,
    "currentPage": 0.0,
    "itemsPerPage": 30.0,
    "totalPages": 1
  }
}
```

### Testing Guidelines for Custom Serialization
**CRITICAL**: When writing tests for collection endpoints, always expect the custom format:

```php
// ✅ Correct - Test for custom format
$responseData = json_decode($this->client->getResponse()->getContent(), true);
$this->assertGreaterThan(0, $responseData['pagination']['total'] ?? 0);
$this->assertIsArray($responseData['data']);

// ❌ Wrong - Don't test for Hydra format in JSON responses
$this->assertGreaterThan(0, $responseData['hydra:totalItems'] ?? 0);
```

### Single Item Responses
Single item responses maintain standard API Platform format for all content types.

## Content Negotiation

Supported formats:
- `application/json` (default) - **Uses custom collection format**
- `application/ld+json` (JSON-LD) - **Uses standard Hydra format**
- `text/html` (API documentation)
- `application/yaml`

## Documentation

- Interactive API documentation available at `/api/docs`
- OpenAPI specification auto-generated from entity annotations
- Swagger UI interface for testing endpoints