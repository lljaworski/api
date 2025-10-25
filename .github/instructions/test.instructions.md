# Test Instructions for API Platform Project

## Admin Credentials

**CRITICAL RULE**: Admin user credentials for testing are:
- Username: `admin`
- Password: `admin123!`

**NEVER** change the admin password to `admin` or any other value. The admin user is created in `DatabaseTestTrait` with password `'admin123!'` and all authentication must use these exact credentials.

## HTTP Status Code Constants

**CRITICAL RULE**: Always use Response constants instead of hardcoded HTTP status codes in tests.

- Use `Response::HTTP_OK` instead of `200`
- Use `Response::HTTP_CREATED` instead of `201` 
- Use `Response::HTTP_NO_CONTENT` instead of `204`
- Use `Response::HTTP_UNAUTHORIZED` instead of `401`
- Use `Response::HTTP_FORBIDDEN` instead of `403`
- Use `Response::HTTP_NOT_FOUND` instead of `404`
- Use `Response::HTTP_UNPROCESSABLE_ENTITY` instead of `422`

Always import `use Symfony\Component\HttpFoundation\Response;` at the top of test files.

**Example:**
```php
// ❌ Wrong - hardcoded status code
$this->assertEquals(201, $this->client->getResponse()->getStatusCode());

// ✅ Correct - Response constant
$this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
```

## HTTP Request Method Constants

**CRITICAL RULE**: Always use Request constants instead of hardcoded HTTP method strings in tests.

- Use `Request::METHOD_GET` instead of `'GET'`
- Use `Request::METHOD_POST` instead of `'POST'`
- Use `Request::METHOD_PUT` instead of `'PUT'`
- Use `Request::METHOD_PATCH` instead of `'PATCH'`
- Use `Request::METHOD_DELETE` instead of `'DELETE'`
- Use `Request::METHOD_HEAD` instead of `'HEAD'`
- Use `Request::METHOD_OPTIONS` instead of `'OPTIONS'`

Always import `use Symfony\Component\HttpFoundation\Request;` at the top of test files.

**Example:**
```php
// ❌ Wrong - hardcoded method string
$this->client->request('GET', '/api/users');
$this->requestAsAdmin('POST', '/api/users', [], [], $headers, $data);

// ✅ Correct - Request constant
$this->client->request(Request::METHOD_GET, '/api/users');
$this->requestAsAdmin(Request::METHOD_POST, '/api/users', [], [], $headers, $data);
```

## Test-Driven Development (TDD) Process

**CRITICAL RULE**: Always follow the TDD Red-Green-Refactor cycle when implementing new functionality.

### TDD Workflow:
1. **RED**: Write a failing test first
   - Create a test that describes the desired functionality
   - Run the test to ensure it fails (red)
   - The failure should be meaningful and specific

2. **GREEN**: Write minimal code to make the test pass
   - Implement only enough code to make the test pass
   - Don't worry about perfect code quality yet
   - Focus on making the test green as quickly as possible

3. **REFACTOR**: Improve the code while keeping tests green
   - Clean up the implementation
   - Improve code structure, naming, and design
   - Ensure all tests still pass after refactoring

4. **FINAL CONFIRMATION**: Run all tests
   - Execute the complete test suite to ensure no regressions
   - All tests must pass before considering the feature complete

### TDD Benefits:
- Ensures comprehensive test coverage
- Drives better API design
- Prevents over-engineering
- Provides immediate feedback on code changes
- Creates living documentation through tests

### Example TDD Workflow:
```php
// 1. RED: Write failing test
public function testUserCanBeCreatedWithValidData(): void
{
    $userData = ['username' => 'testuser', 'password' => 'password123'];
    
    $this->requestAsAdmin(Request::METHOD_POST, '/api/users', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode($userData));
    
    $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    // Test fails because endpoint doesn't exist yet
}

// 2. GREEN: Implement minimal solution
// Create API endpoint, entity, etc. to make test pass

// 3. REFACTOR: Improve code quality
// Clean up implementation, add validation, improve structure

// 4. FINAL: Run all tests to ensure no regressions
// ./vendor/bin/phpunit
```

**Remember**: Never write production code without a failing test first!

## Database Test Isolation

### Mandatory: Use DatabaseTestTrait for All Database Tests

**CRITICAL RULE**: When creating or modifying any test that interacts with the database, you MUST use the `DatabaseTestTrait` from `tests/Functional/DatabaseTestTrait.php`.

#### Usage Pattern

```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class YourFunctionalTest extends WebTestCase
{
    use DatabaseTestTrait; // MANDATORY for database tests
    
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin exists for authentication tests
        $this->ensureTestAdmin();
    }

    protected function tearDown(): void
    {
        // MANDATORY: Clean up test data
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testExample(): void
    {
        // Use unique usernames to prevent conflicts
        $username = $this->generateUniqueUsername('testuser');
        $user = $this->createTestUser($username, 'password123');
        
        // Your test logic here
        // User will be automatically cleaned up in tearDown()
    }
}
```

### Required Methods

#### 1. `ensureTestAdmin(): User`
- **When to use**: In `setUp()` method for tests requiring authentication
- **Purpose**: Ensures admin user exists without creating duplicates
- **Returns**: Existing or newly created admin user

#### 2. `createTestUser(string $username, string $password, array $roles = ['ROLE_USER']): User`
- **When to use**: When test needs to create a user
- **Purpose**: Creates user with hashed password and tracks for cleanup
- **Parameters**:
  - `$username`: Use `generateUniqueUsername()` to ensure uniqueness
  - `$password`: Plain password (will be hashed automatically)
  - `$roles`: User roles array

#### 3. `generateUniqueUsername(string $base = 'testuser'): string`
- **When to use**: Before creating any test user
- **Purpose**: Generates unique username to prevent database conflicts
- **Example**: `$username = $this->generateUniqueUsername('newuser');`

#### 4. `cleanupTestData(): void`
- **When to use**: MANDATORY in `tearDown()` method
- **Purpose**: Removes all test users except admin
- **Result**: Clean database state for next test

### Test Categories and Requirements

#### Functional Tests (Database Required)
- **Location**: `tests/Functional/`
- **Requirement**: MUST use `DatabaseTestTrait`
- **Examples**: API endpoint tests, authentication tests, CRUD operations

#### Unit Tests (No Database)
- **Location**: `tests/Unit/`
- **Requirement**: DO NOT use `DatabaseTestTrait`
- **Examples**: Entity tests, service tests with mocked dependencies

### Authentication in Tests

```php
private function getAuthToken(string $username = 'admin', string $password = 'admin123'): string
{
    $client = static::createClient();
    
    $client->request('POST', '/api/login_check', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'username' => $username,
        'password' => $password
    ]));
    
    $this->assertResponseIsSuccessful();
    $response = json_decode($client->getResponse()->getContent(), true);
    
    return $response['token'];
}
```

### Best Practices

#### ✅ DO:
- Always use `generateUniqueUsername()` for test users
- Call `ensureTestAdmin()` in `setUp()` for auth tests
- Call `cleanupTestData()` in `tearDown()` for database tests
- Use meaningful test usernames: `generateUniqueUsername('updateuser')`
- Track entities created during tests for proper cleanup

#### ❌ DON'T:
- Create users with hardcoded usernames (causes conflicts)
- Skip calling `cleanupTestData()` in tearDown
- Use `createTestAdmin()` directly (use `ensureTestAdmin()` instead)
- Create database tests without using `DatabaseTestTrait`
- Assume database state from previous tests

### Test Isolation Verification

After implementing tests with `DatabaseTestTrait`, verify isolation:

```bash
# Run tests multiple times - should always pass
./vendor/bin/phpunit tests/Functional/
./vendor/bin/phpunit tests/Functional/
./vendor/bin/phpunit tests/Functional/

# Check database is clean (only admin should remain)
php bin/console doctrine:query:sql "SELECT username FROM users"
```

### Custom Serialization Format Testing

**CRITICAL RULE**: This project uses custom serialization for collection responses when requesting `application/json`. Tests must be written to expect this format.

#### Collection Response Testing
```php
// ✅ Correct - Test for custom pagination format
$this->client->request(Request::METHOD_GET, '/api/resources', [], [], [
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
    'HTTP_ACCEPT' => 'application/json'  // This triggers custom format
]);

$responseData = json_decode($this->client->getResponse()->getContent(), true);
$this->assertGreaterThan(0, $responseData['pagination']['total'] ?? 0);
$this->assertIsArray($responseData['data']);
$this->assertEquals(30, $responseData['pagination']['itemsPerPage']);

// ❌ Wrong - Don't test for Hydra format in JSON responses
$this->assertGreaterThan(0, $responseData['hydra:totalItems'] ?? 0);
$this->assertArrayHasKey('hydra:member', $responseData);
```

#### Standard Hydra Format Testing (JSON-LD)
```php
// For JSON-LD format, use standard Hydra format expectations
$this->client->request(Request::METHOD_GET, '/api/resources', [], [], [
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
    'HTTP_ACCEPT' => 'application/ld+json'  // This uses standard Hydra format
]);

$responseData = json_decode($this->client->getResponse()->getContent(), true);
$this->assertGreaterThan(0, $responseData['hydra:totalItems'] ?? 0);
$this->assertArrayHasKey('hydra:member', $responseData);
```

### Error Prevention

#### Common Issue: Duplicate Entry Violations
**Problem**: `Duplicate entry 'username' for key 'users.UNIQ_IDENTIFIER_USERNAME'`

**Solution**: Always use `generateUniqueUsername()`:
```php
// ❌ Wrong - can cause conflicts
$user = $this->createTestUser('testuser', 'password');

// ✅ Correct - prevents conflicts
$username = $this->generateUniqueUsername('testuser');
$user = $this->createTestUser($username, 'password');
```

#### Common Issue: Admin Creation Conflicts
**Problem**: `Duplicate entry 'admin' for key 'users.UNIQ_IDENTIFIER_USERNAME'`

**Solution**: Use `ensureTestAdmin()` instead of `createTestAdmin()`:
```php
// ❌ Wrong - can create duplicate admin
$this->createTestAdmin();

// ✅ Correct - ensures admin exists without duplicates
$this->ensureTestAdmin();
```

#### Common Issue: Wrong Collection Response Format
**Problem**: `Failed asserting that 0 is greater than 0` when testing `$responseData['hydra:totalItems']`

**Solution**: Use the correct response format for the content type:
```php
// ❌ Wrong - Testing for Hydra format in JSON response
$this->assertGreaterThan(0, $responseData['hydra:totalItems'] ?? 0);

// ✅ Correct - Testing for custom pagination format in JSON response
$this->assertGreaterThan(0, $responseData['pagination']['total'] ?? 0);
```

### File Reference

The complete `DatabaseTestTrait` implementation is available at:
`tests/Functional/DatabaseTestTrait.php`

This trait provides all necessary methods for proper test isolation and database management in functional tests.

## Summary

**MANDATORY CHECKLIST for Database Tests:**

- [ ] Test class uses `DatabaseTestTrait`
- [ ] `setUp()` calls `ensureTestAdmin()` if authentication needed
- [ ] `tearDown()` calls `cleanupTestData()`
- [ ] All usernames generated with `generateUniqueUsername()`
- [ ] Users created with `createTestUser()` method
- [ ] Test runs independently multiple times