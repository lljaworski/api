# API Platform Project

A modern Symfony 7.3 API built with API Platform 4.2, following best practices for REST API development.

## 🚀 Quick Start

### Prerequisites

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Docker**: For database services
- **Docker Compose**: For orchestrating services

### 1. Project Setup

Clone the repository and install dependencies:

```bash
# Clone the repository
git clone <repository-url>
cd api

# Install PHP dependencies
composer install
```

### 2. Environment Configuration

Copy and configure your environment file:

```bash
# Copy environment file (if .env.local doesn't exist)
cp .env .env.local

# Edit database configuration if needed
# The default configuration works with Docker Compose
```

Default database configuration:
```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/app?serverVersion=8.0&charset=utf8mb4"
```

### 3. Start Services with Docker Compose

Start the database and other services:

```bash
# Start all services in background
docker-compose up -d

# Check services are running
docker-compose ps
```

**Services included:**
- **MySQL 8.0**: Database server (port 3306)
- **Mailpit**: Email testing tool (SMTP: 1025, Web UI: 8025)

### 4. Initialize Database

Create and set up the database:

```bash
# Create database (if not exists)
php bin/console doctrine:database:create

# Run migrations to create tables
php bin/console doctrine:migrations:migrate --no-interaction

# (Optional) Load fixtures if you have any
php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Start Symfony Development Server

Choose one of the following methods:

#### Option A: Symfony CLI (Recommended)
```bash
# Install Symfony CLI if not installed
# https://symfony.com/download

# Start server
symfony server:start

# Or start in background
symfony server:start -d
```

#### Option B: PHP Built-in Server
```bash
# Start PHP built-in server
php -S localhost:8000 -t public/

# Or in background
php -S localhost:8000 -t public/ &
```

## 📚 API Documentation

Once the server is running, access the interactive API documentation:

- **Swagger UI**: http://localhost:8000/api/docs
- **JSON-LD Documentation**: http://localhost:8000/api/docs.jsonld
- **OpenAPI Schema**: http://localhost:8000/api/docs.json

## 🔍 Health Check Endpoints

The API includes health check endpoints for monitoring:

```bash
# Comprehensive health check
curl http://localhost:8000/api/health

# Simple ping
curl http://localhost:8000/api/ping
```

## 🛠️ Development Commands

### Database Management
```bash
# Create new migration
php bin/console make:migration

# Run pending migrations
php bin/console doctrine:migrations:migrate

# Drop and recreate database (⚠️ DESTRUCTIVE)
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Code Generation
```bash
# Create new entity
php bin/console make:entity

# Create new API resource
php bin/console make:entity --api-resource

# Create repository
php bin/console make:repository
```

### Cache and Debug
```bash
# Clear cache
php bin/console cache:clear

# Debug routes
php bin/console debug:router

# Debug container services
php bin/console debug:container
```

### Testing
```bash
# Run all tests
php bin/phpunit

# Run tests with coverage (requires Xdebug)
php bin/phpunit --coverage-html coverage
```

## 🏗️ Project Structure

```
src/
├── ApiResource/     # API Platform resources and DTOs (PREFERRED for endpoints)
├── Entity/          # Doctrine entities
├── Repository/      # Custom repository classes
├── State/           # State Providers and Processors
└── Controller/      # Traditional controllers (AVOID for API endpoints)

config/
├── packages/        # Bundle configurations
└── routes/          # Route configurations

migrations/          # Database migrations
tests/              # PHPUnit tests
public/             # Web server document root
```

## 🔧 Configuration

### API Platform
Configuration is in `config/packages/api_platform.yaml`:
- Supports JSON, JSON-LD, HTML, and YAML formats
- Stateless by default
- Proper cache headers configured

### Database
- Uses MySQL 8.0 by default
- Connection configured via `DATABASE_URL` environment variable
- Migrations are in `migrations/` directory

### Security
- Health check endpoints are public (no authentication required)
- Main API endpoints can be secured as needed
- Configuration in `config/packages/security.yaml`

## 🚀 Production Deployment

### Environment Setup
```bash
# Set production environment
APP_ENV=prod

# Generate optimized autoloader
composer install --no-dev --optimize-autoloader

# Clear and warm up cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### Database Migration
```bash
# Run migrations in production
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

## 📝 API Development Guidelines

- **Use API Platform for ALL endpoints** - avoid traditional Symfony controllers
- Create API Resources in `src/ApiResource/` directory
- Use State Providers/Processors for custom business logic
- Follow RESTful conventions and HTTP status codes
- Implement proper validation and serialization groups
- Write tests for all endpoints

## 🔍 Troubleshooting

### Common Issues

1. **Port 3306 already in use**:
   ```bash
   # Stop local MySQL service
   sudo systemctl stop mysql
   # Or change port in compose.yaml
   ```

2. **Permission denied for cache directory**:
   ```bash
   # Fix cache permissions
   sudo chown -R $USER:$USER var/
   ```

3. **Database connection failed**:
   ```bash
   # Check if Docker services are running
   docker-compose ps
   
   # Restart services
   docker-compose down && docker-compose up -d
   ```

### Logs
```bash
# View Symfony logs
tail -f var/log/dev.log

# View Docker logs
docker-compose logs database
docker-compose logs mailer
```

## 📊 Performance

### Production Optimizations
- Use opcache in production
- Enable APCu for user cache
- Configure proper HTTP caching headers
- Use CDN for static assets
- Monitor with profiler in development only

---

For more information, check the [API Platform documentation](https://api-platform.com/docs/) and [Symfony documentation](https://symfony.com/doc/current/index.html).