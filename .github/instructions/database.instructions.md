# Database Instructions

## Database Configuration

### Technology Stack
- **Database**: MySQL 8.0
- **ORM**: Doctrine ORM 3.x
- **Migrations**: Doctrine Migrations

### Connection
- Database name: `app`
- Connection configured in `.env` and `config/packages/doctrine.yaml`
- Uses Docker Compose for local development

## Entity Design

### User Entity
- Primary key: Auto-increment integer `id`
- Username: Unique string field (3-180 characters)
- Password: Hashed password storage
- Roles: JSON array field
- Timestamps: `createdAt`, `updatedAt` (automatic)
- Soft delete: `deletedAt` field (nullable)

### Soft Delete Pattern
```php
public function softDelete(): void
{
    $this->deletedAt = new \DateTime();
}

public function isDeleted(): bool
{
    return $this->deletedAt !== null;
}

public function isActive(): bool
{
    return $this->deletedAt === null;
}
```

## Repository Patterns

### UserRepository
- Extends `ServiceEntityRepository`
- Custom methods for CQRS queries
- Pagination support
- Search functionality

### Key Methods
```php
// Find active user by ID
public function findActiveById(int $id): ?User

// Find active users with pagination and search
public function findActiveUsers(int $limit, int $offset = 0, ?string $search = null): array

// Count active users for pagination
public function countActiveUsers(?string $search = null): int
```

## Migrations

### Location
- Migration files in `migrations/` directory
- Managed by Doctrine Migrations

### Commands
```bash
# Generate migration
php bin/console make:migration

# Execute migrations
php bin/console doctrine:migrations:migrate

# Check migration status
php bin/console doctrine:migrations:status
```

### Best Practices
1. **Always review generated migrations** before executing
2. **Test migrations on copy of production data**
3. **Create backup before running migrations in production**
4. **Use descriptive migration names**

## Entity Lifecycle

### Automatic Timestamps
```php
#[ORM\PrePersist]
public function setCreatedAt(): void
{
    $this->createdAt = new \DateTime();
    $this->updatedAt = new \DateTime();
}

#[ORM\PreUpdate]
public function setUpdatedAt(): void
{
    $this->updatedAt = new \DateTime();
}
```

### Validation Constraints
- Applied at entity level using Symfony Validator
- Different validation groups for create vs update operations
- Database-level constraints for data integrity

## Query Optimization

### Repository Query Patterns
```php
// Use QueryBuilder for complex queries
public function findActiveUsers(int $limit, int $offset = 0, ?string $search = null): array
{
    $qb = $this->createQueryBuilder('u')
        ->andWhere('u.deletedAt IS NULL')
        ->orderBy('u.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult($offset);
        
    if ($search !== null) {
        $qb->andWhere('u.username LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }
    
    return $qb->getQuery()->getResult();
}
```

### Performance Considerations
1. **Index frequently queried fields** (username, deletedAt)
2. **Use pagination** for large result sets
3. **Avoid N+1 queries** with proper eager loading
4. **Use partial objects** when full entity not needed

## Testing

### Database Testing
- Uses `DatabaseTestTrait` for test isolation
- Automatic cleanup after each test
- Separate test database configuration

### Test Data Management
```php
trait DatabaseTestTrait
{
    protected array $createdEntities = [];
    
    protected function ensureTestAdmin(): void
    {
        // Create admin user for testing
    }
    
    protected function cleanupTestData(): void
    {
        // Remove test entities
    }
}
```

## Environment Configuration

### Development
- Docker Compose with MySQL container
- Database seeding for development data
- Debug toolbar integration

### Testing
- In-memory SQLite or separate test database
- Fast test execution with proper isolation
- Automatic schema creation/destruction

### Production
- Proper connection pooling
- Read replicas for query scaling (future)
- Backup and monitoring setup

## Best Practices

1. **Entity Design**
   - Use appropriate field types and lengths
   - Add proper constraints and indexes
   - Implement soft delete for user data

2. **Repository Pattern**
   - Keep queries in repository classes
   - Use QueryBuilder for complex queries
   - Return typed results

3. **Migration Management**
   - Review all generated migrations
   - Test on data copies
   - Use version control for migration files

4. **Performance**
   - Index commonly queried fields
   - Use pagination for large datasets
   - Monitor query performance

5. **Testing**
   - Use proper test isolation
   - Clean up test data
   - Test edge cases and constraints