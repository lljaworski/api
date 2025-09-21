# Security Instructions

## Authentication & Authorization

### JWT Authentication
- Uses `lexik/jwt-authentication-bundle`
- RSA keys stored in `config/jwt/`
- Token expiration configurable in `config/packages/lexik_jwt_authentication.yaml`

### User Authentication Flow
```php
// Login endpoint
POST /api/login_check
{
    "username": "admin",
    "password": "admin123"
}

// Response
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}

// Use token in subsequent requests
Authorization: Bearer {token}
```

## Role-Based Access Control

### User Roles
- `ROLE_USER`: Basic authenticated user
- `ROLE_ADMIN`: Administrative privileges

### Role Hierarchy
```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
```

### API Platform Security
```php
#[ApiResource(
    security: "is_granted('ROLE_ADMIN')",
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        // ...
    ]
)]
```

## Password Security

### Password Hashing
```php
// Using Symfony PasswordHasher
public function __construct(
    private readonly UserPasswordHasherInterface $passwordHasher
) {}

// Hash password
$hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
$user->setPassword($hashedPassword);

// Verify password
$isValid = $this->passwordHasher->isPasswordValid($user, $plainPassword);
```

### Password Requirements
- Minimum length: 6 characters
- Validation applied during user creation
- Hashed using secure algorithm (bcrypt/sodium)

## Security Headers

### CORS Configuration
```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            origin_regex: true
            allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
            allow_headers: ['*']
            allow_methods: ['*']
```

## Input Validation & Sanitization

### Request Validation
- Symfony Validator integration
- Validation groups for different operations
- Custom validation constraints when needed

### SQL Injection Prevention
- Doctrine ORM with prepared statements
- Parameter binding in custom queries
- Input sanitization at repository level

### XSS Prevention
- API responses are JSON (not HTML)
- Proper content-type headers
- Input validation and encoding

## Error Handling

### Security-Safe Error Messages
```php
// Don't expose sensitive information in errors
try {
    $user = $this->userRepository->find($id);
    if (!$user) {
        throw new NotFoundHttpException('User not found');
    }
} catch (\Exception $e) {
    // Log actual error, return generic message
    $this->logger->error('User lookup failed', ['exception' => $e]);
    throw new NotFoundHttpException('Resource not available');
}
```

### Rate Limiting
- Consider implementing rate limiting for authentication endpoints
- Use tools like `symfony/rate-limiter` for production

## Environment Security

### Environment Variables
```bash
# .env.local (not committed)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_secret_passphrase

DATABASE_URL="mysql://user:password@127.0.0.1:3306/app"
```

### JWT Key Management
```bash
# Generate JWT keys
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

## Testing Security

### Authentication Testing
```php
// Get authentication token for tests
private function getAuthToken(): string
{
    $this->client->request('POST', '/api/login_check', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'username' => 'admin',
        'password' => 'admin123'
    ]));
    
    $responseData = json_decode($this->client->getResponse()->getContent(), true);
    return $responseData['token'];
}

// Test authorization
public function testUnauthorizedAccess(): void
{
    $this->client->request('GET', '/api/users');
    $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
}
```

### Role Testing
```php
public function testAdminOnlyEndpoint(): void
{
    // Test with regular user token
    $this->client->request('GET', '/api/users', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $regularUserToken,
    ]);
    $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    
    // Test with admin token
    $this->client->request('GET', '/api/users', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
    ]);
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
}
```

## Security Best Practices

1. **Authentication**
   - Use strong JWT secrets
   - Implement token expiration
   - Validate tokens on every request
   - Use HTTPS in production

2. **Authorization**
   - Implement least privilege principle
   - Check permissions at multiple levels
   - Use role-based access control
   - Validate user context

3. **Data Protection**
   - Hash passwords securely
   - Don't log sensitive data
   - Implement soft delete for user data
   - Encrypt sensitive database fields if needed

4. **Input Validation**
   - Validate all inputs
   - Use appropriate validation groups
   - Sanitize data before storage
   - Use prepared statements

5. **Error Handling**
   - Don't expose system internals
   - Log security events
   - Return appropriate HTTP status codes
   - Implement rate limiting

6. **Environment Security**
   - Keep secrets in environment variables
   - Use different keys per environment
   - Regular key rotation
   - Secure key storage

## Security Monitoring

### Logging Security Events
```php
// Log authentication attempts
$this->logger->info('Authentication attempt', [
    'username' => $username,
    'ip' => $request->getClientIp(),
    'success' => $success
]);

// Log authorization failures
$this->logger->warning('Unauthorized access attempt', [
    'user' => $user->getUsername(),
    'endpoint' => $request->getPathInfo(),
    'method' => $request->getMethod()
]);
```

### Security Auditing
- Regular security dependency updates
- Code security reviews
- Penetration testing for production
- Monitor for suspicious patterns