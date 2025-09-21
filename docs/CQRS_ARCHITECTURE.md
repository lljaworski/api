# CQRS Architecture Documentation

## Overview

This project implements **Command Query Responsibility Segregation (CQRS)** using Symfony Messenger with separate command and query buses. This architectural pattern separates read and write operations to improve scalability, maintainability, and performance.

## Architecture Components

### 1. Message Buses Configuration

Located in `config/packages/messenger.yaml`:

```yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction
            query.bus:
                middleware:
                    - validation
        transports:
            sync: 'sync://'
        routing:
            'App\Application\Command\*': sync
            'App\Application\Query\*': sync
```

- **command.bus**: Handles state-changing operations (Create, Update, Delete)
- **query.bus**: Handles data retrieval operations (Read)
- **sync transport**: All operations are synchronous as requested

### 2. Base Interfaces and Classes

#### Core Interfaces (`src/Application/`)

- **CommandInterface**: Marker interface for all commands
- **QueryInterface**: Marker interface for all queries
- **CommandHandlerInterface**: Interface for command handlers
- **QueryHandlerInterface**: Interface for query handlers

#### Abstract Base Classes

- **AbstractCommand**: Base class with UUID tracking
- **AbstractQuery**: Base class with UUID tracking

### 3. Command Structure (`src/Application/Command/`)

Commands represent **write operations** (state changes):

#### User Commands

- **CreateUserCommand**: Creates a new user
  ```php
  new CreateUserCommand(
      username: 'john_doe',
      plainPassword: 'password123',
      roles: ['ROLE_USER']
  )
  ```

- **UpdateUserCommand**: Updates an existing user
  ```php
  new UpdateUserCommand(
      id: 1,
      username: 'john_doe_updated',
      plainPassword: 'newpassword',
      roles: ['ROLE_USER', 'ROLE_ADMIN']
  )
  ```

- **DeleteUserCommand**: Soft deletes a user
  ```php
  new DeleteUserCommand(id: 1)
  ```

### 4. Query Structure (`src/Application/Query/`)

Queries represent **read operations** (data retrieval):

#### User Queries

- **GetUserQuery**: Retrieves a single user by ID
  ```php
  new GetUserQuery(id: 1)
  ```

- **GetUsersQuery**: Retrieves a paginated list of users with optional search
  ```php
  new GetUsersQuery(
      page: 1,
      itemsPerPage: 30,
      search: 'john'
  )
  ```

### 5. Handlers (`src/Application/Handler/`)

Handlers contain the business logic for processing commands and queries:

#### Command Handlers

- **CreateUserCommandHandler**: Handles user creation
- **UpdateUserCommandHandler**: Handles user updates
- **DeleteUserCommandHandler**: Handles user soft deletion

#### Query Handlers

- **GetUserQueryHandler**: Handles single user retrieval
- **GetUsersQueryHandler**: Handles user collection retrieval with pagination

All handlers use the `#[AsMessageHandler]` attribute for automatic registration.

### 6. Data Transfer Objects (`src/Application/DTO/`)

DTOs are used for query responses to decouple the read side from entities:

- **UserDTO**: Represents a single user
- **UserCollectionDTO**: Represents a paginated collection of users

### 7. API Platform Integration

#### State Providers (`src/State/UserProvider.php`)

The provider uses the **query bus** to retrieve data and converts DTOs back to entities for API Platform compatibility:

```php
public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
{
    if ($operation instanceof CollectionOperationInterface) {
        $query = new GetUsersQuery(/* ... */);
        $envelope = $this->queryBus->dispatch($query);
        $userCollectionDto = $envelope->last(HandledStamp::class)->getResult();
        
        // Convert DTOs back to entities for API Platform
        return $this->convertDtosToEntities($userCollectionDto->users);
    }
    
    // Single item retrieval
    $query = new GetUserQuery($uriVariables['id']);
    $envelope = $this->queryBus->dispatch($query);
    $userDto = $envelope->last(HandledStamp::class)->getResult();
    
    return $this->convertDtoToEntity($userDto);
}
```

#### State Processors (`src/State/UserProcessor.php`)

The processor uses the **command bus** to handle state changes:

```php
public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
{
    if ($operation instanceof DeleteOperationInterface) {
        $command = new DeleteUserCommand($data->getId());
        $this->commandBus->dispatch($command);
        return null;
    }
    
    $isUpdate = $data->getId() !== null;
    
    if ($isUpdate) {
        $command = new UpdateUserCommand(/* ... */);
    } else {
        $command = new CreateUserCommand(/* ... */);
    }
    
    $envelope = $this->commandBus->dispatch($command);
    return $envelope->last(HandledStamp::class)->getResult();
}
```

## Usage Examples

### Using Commands (Write Operations)

```php
// Inject the command bus
public function __construct(
    private readonly MessageBusInterface $commandBus
) {}

// Create a user
$command = new CreateUserCommand(
    username: 'jane_doe',
    plainPassword: 'secure_password',
    roles: ['ROLE_USER']
);
$envelope = $this->commandBus->dispatch($command);
$createdUser = $envelope->last(HandledStamp::class)->getResult();

// Update a user
$command = new UpdateUserCommand(
    id: $createdUser->getId(),
    username: 'jane_smith',
    plainPassword: 'new_password',
    roles: ['ROLE_USER', 'ROLE_ADMIN']
);
$envelope = $this->commandBus->dispatch($command);
$updatedUser = $envelope->last(HandledStamp::class)->getResult();

// Delete a user (soft delete)
$command = new DeleteUserCommand($updatedUser->getId());
$this->commandBus->dispatch($command);
```

### Using Queries (Read Operations)

```php
// Inject the query bus
public function __construct(
    private readonly MessageBusInterface $queryBus
) {}

// Get a single user
$query = new GetUserQuery(id: 1);
$envelope = $this->queryBus->dispatch($query);
$userDto = $envelope->last(HandledStamp::class)->getResult();

// Get users with pagination and search
$query = new GetUsersQuery(
    page: 1,
    itemsPerPage: 20,
    search: 'john'
);
$envelope = $this->queryBus->dispatch($query);
$userCollectionDto = $envelope->last(HandledStamp::class)->getResult();

echo "Total users: " . $userCollectionDto->total;
echo "Current page: " . $userCollectionDto->currentPage;
foreach ($userCollectionDto->users as $userDto) {
    echo "User: " . $userDto->username;
}
```

## Benefits

### 1. **Separation of Concerns**
- Commands handle business logic for state changes
- Queries focus purely on data retrieval
- Clear separation between read and write models

### 2. **Scalability**
- Read and write operations can be optimized independently
- Different databases could be used for read and write sides
- Caching can be applied more effectively to query sides

### 3. **Maintainability**
- Business logic is centralized in handlers
- Clear contracts through interfaces
- Easy to test individual handlers

### 4. **Performance**
- DTOs for queries reduce data transfer
- Optimized queries without entity overhead
- Separate optimization strategies for reads vs writes

### 5. **Flexibility**
- Easy to add new commands and queries
- Handlers can be enhanced without affecting API
- Support for complex business workflows

## Testing

Comprehensive tests are provided in `tests/Functional/CqrsIntegrationTest.php` that verify:

- Complete CRUD workflow using CQRS
- Command and query bus integration
- DTO to entity conversion
- Search functionality
- Error handling for non-existent entities

## API Platform Compatibility

The CQRS implementation maintains full compatibility with API Platform:

- **Standard REST endpoints** continue to work as expected
- **Serialization groups** and validation work normally
- **OpenAPI documentation** is automatically generated
- **Content negotiation** supports JSON, JSON-LD, HTML, and YAML

## Best Practices

1. **Commands should be immutable** - Use readonly properties
2. **Include validation constraints** - Use Symfony Validator attributes
3. **Keep handlers focused** - Single responsibility per handler
4. **Use DTOs for queries** - Avoid exposing entities directly
5. **Test the complete flow** - Test commands and queries together
6. **Handle errors gracefully** - Return null for not found scenarios
7. **Follow naming conventions** - Clear, descriptive names for commands/queries

## Future Enhancements

The current implementation can be extended with:

- **Event sourcing** for audit trails
- **Async processing** for heavy operations
- **Caching layers** for query optimization
- **Read replicas** for query scaling
- **Command validation** middleware
- **Authorization** middleware for commands/queries