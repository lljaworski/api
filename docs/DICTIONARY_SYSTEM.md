# Dictionary Resource System

## Overview

The Dictionary Resource System provides a flexible way to expose dictionary data (key-value pairs) through API endpoints for frontend components like select inputs. The system supports multiple data sources including PHP enums and database repositories.

## Features

- 🔐 **Role-based Access Control**: Configure different security rules per dictionary type
- 🔌 **Extensible Architecture**: Easy to add new dictionary providers
- 📊 **Multiple Data Sources**: Supports enums, repositories, and custom providers
- 🚀 **API Platform Integration**: Full API Platform support with JSON, JSON-LD, and other formats
- ✅ **Well Tested**: Comprehensive test coverage following TDD principles

## API Endpoints

### Get Dictionary
```
GET /api/dictionaries/{type}
Authorization: Bearer {jwt_token}
Accept: application/json
```

**Response Format:**
```json
{
  "type": "roles",
  "items": [
    {"id": "ROLE_ADMIN", "name": "Administrator"},
    {"id": "ROLE_USER", "name": "User"}
  ]
}
```

**Supported Content Types:**
- `application/json` (default)
- `application/ld+json` (JSON-LD with context)
- `text/html` (API documentation)

## Available Dictionaries

### Roles Dictionary
- **Endpoint**: `GET /api/dictionaries/roles`
- **Source**: `App\Enum\RolesEnum`
- **Required Role**: `ROLE_ADMIN`
- **Description**: User roles available in the system

## Architecture

### Core Components

#### 1. Dictionary Providers
Providers implement `DictionaryProviderInterface` and convert data from various sources:

```php
interface DictionaryProviderInterface
{
    public function getType(): string;
    public function getItems(): array; // Returns DictionaryItem[]
    public function supports(string $type): bool;
}
```

#### 2. Dictionary Registry
Central registry that manages providers and security rules:

```php
class DictionaryRegistry
{
    public function registerProvider(DictionaryProviderInterface $provider, array $requiredRoles = []): void;
    public function getDictionary(string $type): array;
    public function hasDictionary(string $type): bool;
}
```

#### 3. API Platform Integration
- **Resource**: `App\ApiResource\Dictionary`
- **State Provider**: `App\State\DictionaryProvider`
- **Route Pattern**: `/api/dictionaries/{type}`

## Security

### Role-Based Access Control
Dictionary access is controlled through role requirements:

```php
// In DictionaryProviderPass
$definition->addMethodCall('registerProvider', [
    new Reference('app.dictionary.provider.roles'),
    ['ROLE_ADMIN'] // Required roles
]);
```

### Authentication
All dictionary endpoints require JWT authentication:
- Login at `/api/login_check` to get token
- Include token in `Authorization: Bearer {token}` header

## Adding New Dictionaries

### 1. Enum-Based Dictionary

Create an enum:
```php
enum StatusEnum: string
{
    case ACTIVE = 'Active';
    case INACTIVE = 'Inactive';
    case PENDING = 'Pending';
}
```

Register the provider in `config/services_dictionary.yaml`:
```yaml
services:
    app.dictionary.provider.status:
        class: App\Dictionary\Provider\EnumDictionaryProvider
        arguments:
            $type: 'status'
            $enumClass: 'App\Enum\StatusEnum'
```

Update `DictionaryProviderPass.php`:
```php
$definition->addMethodCall('registerProvider', [
    new Reference('app.dictionary.provider.status'),
    ['ROLE_USER'] // Required roles
]);
```

### 2. Repository-Based Dictionary

Create a custom provider:
```php
class UserDictionaryProvider implements DictionaryProviderInterface
{
    public function __construct(private UserRepository $userRepository) {}
    
    public function getType(): string
    {
        return 'users';
    }
    
    public function getItems(): array
    {
        $users = $this->userRepository->findActiveUsers();
        return array_map(
            fn(User $user) => new DictionaryItem(
                id: (string) $user->getId(),
                name: $user->getUsername()
            ),
            $users
        );
    }
    
    public function supports(string $type): bool
    {
        return $type === 'users';
    }
}
```

Register and configure access:
```yaml
services:
    App\Dictionary\Provider\UserDictionaryProvider: ~
```

```php
// In DictionaryProviderPass
$definition->addMethodCall('registerProvider', [
    new Reference(UserDictionaryProvider::class),
    ['ROLE_ADMIN']
]);
```

### 3. Custom Dictionary Provider

For complex logic, create a custom provider:
```php
class CustomDictionaryProvider implements DictionaryProviderInterface
{
    public function getItems(): array
    {
        // Custom logic here - API calls, calculations, etc.
        return [
            new DictionaryItem('key1', 'Display Name 1'),
            new DictionaryItem('key2', 'Display Name 2'),
        ];
    }
}
```

## Testing

### Running Tests
```bash
# Run dictionary-specific tests
./vendor/bin/phpunit tests/Functional/DictionaryApiTest.php

# Test with coverage
./vendor/bin/phpunit --coverage-html coverage tests/Functional/DictionaryApiTest.php
```

### Test Examples
```php
public function testRolesDictionaryRequiresAdminRole(): void
{
    $username = $this->generateUniqueUsername('regularuser');
    $this->createTestUser($username, 'password123', ['ROLE_USER']);
    
    $token = $this->getAuthToken($username, 'password123');
    
    $this->client->request(Request::METHOD_GET, '/api/dictionaries/roles', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        'HTTP_ACCEPT' => 'application/json',
    ]);
    
    $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
}
```

## Manual Testing

### Using cURL
```bash
# Get authentication token
TOKEN=$(curl -s -X POST -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}' \
  http://localhost:8000/api/login_check | jq -r .token)

# Test roles dictionary
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  http://localhost:8000/api/dictionaries/roles | jq .

# Test JSON-LD format
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/ld+json" \
  http://localhost:8000/api/dictionaries/roles | jq .

# Test unauthorized access
curl -H "Accept: application/json" \
  http://localhost:8000/api/dictionaries/roles
```

## Error Handling

### Common HTTP Status Codes
- `200 OK`: Dictionary retrieved successfully
- `401 Unauthorized`: Missing or invalid JWT token
- `403 Forbidden`: User lacks required role
- `404 Not Found`: Dictionary type doesn't exist

### Error Response Format
```json
{
  "title": "An error occurred",
  "detail": "Dictionary type 'nonexistent' not found",
  "status": 404,
  "type": "/errors/404"
}
```

## Performance Considerations

### Caching
For expensive dictionary operations, consider adding caching:

```php
class CachedDictionaryProvider implements DictionaryProviderInterface
{
    public function __construct(
        private DictionaryProviderInterface $provider,
        private CacheInterface $cache
    ) {}
    
    public function getItems(): array
    {
        $cacheKey = 'dictionary_' . $this->getType();
        
        return $this->cache->get($cacheKey, function() {
            return $this->provider->getItems();
        });
    }
}
```

### Database Queries
For repository-based dictionaries:
- Use appropriate indexes
- Limit result sets when appropriate
- Consider pagination for large datasets

## Best Practices

1. **Use Descriptive Types**: Dictionary types should be clear and consistent
2. **Implement Security**: Always configure appropriate role requirements
3. **Test Thoroughly**: Include tests for authentication, authorization, and data format
4. **Document Changes**: Update this documentation when adding new dictionaries
5. **Monitor Performance**: Watch for slow dictionary endpoints in production

## Troubleshooting

### Service Configuration Issues
If providers aren't being registered:
1. Clear cache: `php bin/console cache:clear`
2. Check service definitions in `config/services_dictionary.yaml`
3. Verify compiler pass is correctly registered in `src/Kernel.php`

### Authentication Issues
If getting 401 errors:
1. Verify admin credentials: `admin/admin`
2. Check JWT token validity
3. Ensure proper Authorization header format: `Bearer {token}`

### Permission Issues
If getting 403 errors:
1. Check user roles with: `php bin/console debug:container security.helper`
2. Verify provider security configuration
3. Test with admin user first