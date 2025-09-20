# Copilot Instructions for API Platform Project

## Project Overview
This is a Symfony 7.3 API project built with API Platform 4.2, following modern PHP development practices.

## Technology Stack
- **Framework**: Symfony 7.3
- **API Framework**: API Platform 4.2
- **PHP Version**: 8.2+
- **Database**: Doctrine ORM 3.5
- **Testing**: PHPUnit 12.3
- **Architecture**: RESTful API with JSON-LD, JSON, HTML, and OpenAPI YAML formats

## Project Structure
- `src/Entity/` - Doctrine entities and API resources
- `src/Controller/` - **AVOID**: Only for non-API endpoints (redirects, webhooks, etc.)
- `src/Repository/` - Custom repository classes
- `src/ApiResource/` - API Platform specific resources and DTOs (**PREFERRED** for all API endpoints)
- `src/State/` - State Providers and Processors for custom business logic
- `config/packages/` - Bundle configurations
- `migrations/` - Doctrine database migrations
- `tests/` - PHPUnit test files
- `boot_project.sh` - **MANDATORY**: Project startup script for development and testing
- `shutdown_project.sh` - **MANDATORY**: Project cleanup script

## Code Style and Standards

### PHP Code Standards
- Use PHP 8.2+ features (readonly properties, enums, union types, etc.)
- Follow PSR-12 coding standards
- Use strict typing: always include `declare(strict_types=1);`
- Prefer constructor property promotion
- Use typed properties and return types
- Follow Symfony naming conventions

### API Platform Best Practices
- **MANDATORY**: Use API Platform for ALL endpoints - never create traditional Symfony controllers for API endpoints
- Use attributes for API Platform configuration (not annotations)
- Prefer API Platform's automatic operations over custom controllers
- Use State Providers and State Processors for complex business logic
- Implement proper serialization groups for different contexts
- Use API Platform's built-in validation with Symfony Validator
- Create API Resources (DTOs) instead of direct entity exposure when appropriate

### Entity Design
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource]
class ExampleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    // Constructor property promotion when applicable
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    // Getters and setters...
}
```

### API Resource Configuration
- Use granular operations configuration
- Implement proper security with `security` and `securityMessage`
- Configure serialization contexts appropriately
- Use custom DTOs when entities shouldn't be directly exposed

### Repository Pattern
- Extend `ServiceEntityRepository`
- Use QueryBuilder for complex queries
- Implement proper error handling
- Add type hints for return values

## Testing and Debugging Practices

### API Testing Guidelines
- **Use curl for endpoint testing** - prefer command-line testing over browser
- **Test different content types**: JSON, JSON-LD, HTML, YAML
- **Verify response headers** and status codes
- **Test error scenarios** and edge cases

### Common curl Examples
```bash
# Test JSON response
curl -H "Accept: application/json" http://localhost:8000/api/health

# Test JSON-LD response  
curl -H "Accept: application/ld+json" http://localhost:8000/api/health

# Test with verbose output
curl -v http://localhost:8000/api/health

# Format JSON output
curl -s http://localhost:8000/api/health | python3 -m json.tool

# Test POST requests
curl -X POST -H "Content-Type: application/json" \
  -d '{"field":"value"}' http://localhost:8000/api/resource
```

### Testing Guidelines
- Write unit tests for business logic
- Create functional tests for API endpoints
- Use Symfony's testing tools and fixtures
- Test both success and error scenarios
- Mock external dependencies
- **MANDATORY**: Use automation scripts for test environment setup:
  - `./boot_project.sh` for unit tests (database only, no server)
  - `./boot_project.sh --with-server` for integration/functional tests
  - `./shutdown_project.sh` to clean up after testing

## Project Management Scripts
- **`./boot_project.sh`**: Starts Docker services, database, and migrations
  - Use without parameters for unit testing environments
  - Use with `--with-server` for full development environment
- **`./shutdown_project.sh`**: Stops all services and cleans cache
- **ALWAYS** use these scripts instead of manual Docker or Symfony commands
- **NEVER** bypass these scripts when setting up test environments

## Common Patterns

### Custom Operations
```php
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
        new Post(
            uriTemplate: '/entities/{id}/custom-action',
            controller: CustomActionController::class,
            name: 'custom_action'
        )
    ]
)]
```

### State Processors
```php
<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

final class EntityProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Custom business logic here
        return $data;
    }
}
```

### Validation Groups
```php
#[ApiResource(
    operations: [
        new Post(validationContext: ['groups' => ['create']]),
        new Put(validationContext: ['groups' => ['update']])
    ]
)]
```

## Configuration Files
- Keep `api_platform.yaml` minimal and focused
- Use environment variables for sensitive configuration
- Organize package configurations logically
- Document complex configuration choices

## Database and Migrations
- Always create migrations for schema changes: `php bin/console make:migration`
- Review migrations before running them
- Use meaningful migration names
- Keep migrations small and focused

## Security Considerations
- Implement proper authentication and authorization
- Use security attributes on API resources
- Validate all input data
- Sanitize output when necessary
- Follow OWASP guidelines for API security

## Development Workflow
1. **ALWAYS use API Platform for endpoints** - create API Resources, not controllers
2. **MANDATORY: Use project automation scripts for testing and development**
   - Use `./boot_project.sh` to start Docker services and database for testing
   - Use `./boot_project.sh --with-server` to start everything including Symfony server
   - Use `./shutdown_project.sh` to properly stop all services and clean up
   - For unit tests only: use `./boot_project.sh` (without --with-server option)
3. **Use curl for API testing and URL examination** - prefer curl over browser for endpoint testing
4. Create entities with `php bin/console make:entity`
5. Generate migrations with `php bin/console make:migration`
6. Configure API Platform resources with attributes in `src/ApiResource/`
7. Implement State Providers/Processors in `src/State/` for custom logic
8. Write tests before implementing complex logic
9. Use `php bin/console debug:router` to verify routes
10. Test API endpoints with built-in documentation at `/api/docs`

## Performance Tips
- Use serialization groups to limit data transfer
- Implement pagination for collections
- Use eager loading to avoid N+1 queries
- Cache frequently accessed data
- Monitor query performance

## Error Handling
- Use API Platform's built-in error handling
- Create custom exception handlers when needed
- Return meaningful error messages
- Follow RFC 7807 for error responses

When generating code, always consider these guidelines and the specific context of this API Platform project.