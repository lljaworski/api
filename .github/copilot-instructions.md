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
- `src/Controller/` - Custom controllers (use sparingly with API Platform)
- `src/Repository/` - Custom repository classes
- `src/ApiResource/` - API Platform specific resources and DTOs
- `config/packages/` - Bundle configurations
- `migrations/` - Doctrine database migrations
- `tests/` - PHPUnit test files

## Code Style and Standards

### PHP Code Standards
- Use PHP 8.2+ features (readonly properties, enums, union types, etc.)
- Follow PSR-12 coding standards
- Use strict typing: always include `declare(strict_types=1);`
- Prefer constructor property promotion
- Use typed properties and return types
- Follow Symfony naming conventions

### API Platform Best Practices
- Use attributes for API Platform configuration (not annotations)
- Prefer API Platform's automatic operations over custom controllers
- Use State Providers and State Processors for complex business logic
- Implement proper serialization groups for different contexts
- Use API Platform's built-in validation with Symfony Validator

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

### Testing Guidelines
- Write unit tests for business logic
- Create functional tests for API endpoints
- Use Symfony's testing tools and fixtures
- Test both success and error scenarios
- Mock external dependencies

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
1. Create entities with `php bin/console make:entity`
2. Generate migrations with `php bin/console make:migration`
3. Configure API Platform resources with attributes
4. Write tests before implementing complex logic
5. Use `php bin/console debug:router` to verify routes
6. Test API endpoints with built-in documentation at `/api/docs`

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