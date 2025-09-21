# CQRS Architecture Instructions

## Overview

This project implements **Command Query Responsibility Segregation (CQRS)** with Symfony Messenger. When working with user operations, always use the CQRS pattern instead of direct entity manipulation.

## CQRS Components

### Commands (Write Operations)
Located in `src/Application/Command/User/`:

- **CreateUserCommand**: For creating new users
- **UpdateUserCommand**: For updating existing users  
- **DeleteUserCommand**: For soft-deleting users

### Queries (Read Operations)
Located in `src/Application/Query/User/`:

- **GetUserQuery**: For retrieving a single user by ID
- **GetUsersQuery**: For retrieving paginated user collections with optional search

### Handlers
Located in `src/Application/Handler/User/`:

- Command handlers: Process business logic for state changes
- Query handlers: Retrieve data and return DTOs

### Data Transfer Objects (DTOs)
Located in `src/Application/DTO/`:

- **UserDTO**: For single user responses
- **UserCollectionDTO**: For paginated user collection responses

## Usage Patterns

### Creating Users
```php
$command = new CreateUserCommand(
    username: 'john_doe',
    plainPassword: 'password123',
    roles: ['ROLE_USER']
);
$envelope = $this->commandBus->dispatch($command);
$user = $envelope->last(HandledStamp::class)->getResult();
```

### Updating Users
```php
$command = new UpdateUserCommand(
    id: $userId,
    username: $newUsername,
    plainPassword: $newPassword, // null if not updating
    roles: $newRoles // null if not updating
);
$envelope = $this->commandBus->dispatch($command);
$user = $envelope->last(HandledStamp::class)->getResult();
```

### Querying Users
```php
// Single user
$query = new GetUserQuery(id: $userId);
$envelope = $this->queryBus->dispatch($query);
$userDto = $envelope->last(HandledStamp::class)->getResult();

// User collection
$query = new GetUsersQuery(
    page: 1,
    itemsPerPage: 30,
    search: 'optional search term'
);
$envelope = $this->queryBus->dispatch($query);
$collectionDto = $envelope->last(HandledStamp::class)->getResult();
```

## API Platform Integration

### State Providers
- Use **query bus** for all read operations
- Convert DTOs back to entities for API Platform compatibility
- Handle null results by returning null (triggers 404)

### State Processors  
- Use **command bus** for all write operations
- Extract only updated fields for PATCH operations
- Handle password extraction from request context

## Message Buses

### Injection
```php
public function __construct(
    private readonly MessageBusInterface $commandBus,
    private readonly MessageBusInterface $queryBus
) {}
```

### Configuration
- **command.bus**: For state-changing operations
- **query.bus**: For data retrieval operations  
- Both buses are synchronous
- Validation and doctrine_transaction middleware enabled

## Best Practices

1. **Always use CQRS**: Don't bypass the command/query buses for user operations
2. **Handler focus**: Keep handlers focused on single responsibility
3. **DTO usage**: Queries should return DTOs, not entities directly
4. **Null handling**: Query handlers return null for not found, command handlers throw exceptions
5. **Field extraction**: For updates, only pass fields that were explicitly provided
6. **Validation groups**: Use appropriate validation groups for create vs update operations

## Testing

- Use `CqrsIntegrationTest` for testing complete CQRS workflows
- Test both command and query handlers individually
- Verify DTO to entity conversion in providers
- Test error handling (404 for not found, validation errors)

## Error Handling

- **Query handlers**: Return null for not found items
- **Command handlers**: Throw `NotFoundHttpException` for not found items
- **API Platform**: Converts null provider results to 404 responses
- **Validation**: Use entity validation groups for proper constraint application