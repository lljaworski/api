# Test Instructions for API Platform Project

## Admin Credentials

**CRITICAL RULE**: Admin user credentials for testing are:
- Username: `admin`
- Password: `admin`

**NEVER** change the admin password to `admin123` or any other value. The admin user is created in `DatabaseTestTrait` with password `'admin'` and all authentication must use these exact credentials.

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