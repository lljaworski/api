# API Platform Project

A modern Symfony 7.3 API built with API Platform 4.2, following best practices for REST API development.

## 🚀 Quick Start

### Prerequisites

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Docker**: For database services
- **Docker Compose**: For orchestrating services
- **Symfony CLI**: Recommended for development (https://symfony.com/download)

### Automated Setup (Recommended)

Use the provided automation scripts for easy project management:

```bash
# Clone the repository
git clone <repository-url>
cd api

# Install PHP dependencies
composer install

# Make scripts executable
chmod +x boot_project.sh shutdown_project.sh

# Option 1: Start Docker services + database only
./boot_project.sh

# Option 2: Start everything including Symfony server
./boot_project.sh --with-server

# When done working, stop everything
./shutdown_project.sh
```

**What `boot_project.sh` does:**
- Starts Docker Compose services (database, mailpit)
- Waits for database to be ready
- Creates database if it doesn't exist
- Runs pending migrations
- **Optionally** starts Symfony development server with `--with-server` flag

**What `shutdown_project.sh` does:**
- Stops Symfony development server
- Brings down Docker Compose services
- Clears Symfony cache

**Development Workflows:**

```bash
# Workflow 1: Use external server (IDE integration, custom port, etc.)
./boot_project.sh                    # Start services only
# Then start your preferred server manually

# Workflow 2: Use built-in Symfony server  
./boot_project.sh --with-server      # Start everything
# Server runs on default Symfony port

# Workflow 3: Restart just the server
./boot_project.sh                    # Start services
symfony server:start -d              # Start server separately
# OR
php -S localhost:8000 -t public/     # Use PHP built-in server
```

### Manual Setup (Alternative)

If you prefer manual control over each step:

### Manual Setup (Alternative)

If you prefer manual control over each step:

#### 1. Project Setup

Clone the repository and install dependencies:

```bash
# Clone the repository
git clone <repository-url>
cd api

# Install PHP dependencies
composer install
```

#### 2. Environment Configuration

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

#### 3. Start Services with Docker Compose

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

#### 4. Initialize Database

Create and set up the database:

```bash
# Create database (if not exists)
php bin/console doctrine:database:create

# Run migrations to create tables
php bin/console doctrine:migrations:migrate --no-interaction

# (Optional) Load fixtures if you have any
php bin/console doctrine:fixtures:load --no-interaction
```

#### 5. Start Symfony Development Server

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

## � Authentication

The API uses JWT (JSON Web Token) authentication for secure access to protected endpoints.

### Admin Credentials

A default admin user is created automatically during database migration:

- **Username**: `admin`
- **Password**: `admin`
- **Roles**: `ROLE_ADMIN`, `ROLE_USER`

⚠️ **Important**: Change these default credentials in production environments!

### Authentication Endpoints

#### Login
```bash
# Authenticate and get JWT token
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}' \
  http://localhost:8000/api/login_check
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

#### Access Protected Endpoints
```bash
# Use the token to access protected endpoints
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost:8000/api/protected
```

### Public vs Protected Endpoints

**Public Endpoints** (no authentication required):
- `GET /api/health` - Health check
- `GET /api/ping` - Simple ping
- `POST /api/login_check` - Authentication

**Protected Endpoints** (require JWT token):
- `GET /api/protected` - Protected demo endpoint

### JWT Token Management

JWT tokens are:
- Valid for 1 hour by default
- Signed with RSA256 algorithm
- Include user roles and username in payload
- Must be included in `Authorization: Bearer <token>` header

## �🛠️ Development Commands

### Project Management Scripts

Use these automation scripts for common development tasks:

```bash
# Start Docker services and database only
./boot_project.sh

# Start everything including Symfony development server
./boot_project.sh --with-server

# Stop the entire development environment  
./shutdown_project.sh

# Make scripts executable (first time only)
chmod +x boot_project.sh shutdown_project.sh
```

**Script Options:**
- `./boot_project.sh` - Sets up Docker services and database only
- `./boot_project.sh --with-server` - Additionally starts Symfony development server
- `./shutdown_project.sh` - Stops all services and cleans up

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

### Automation Scripts Issues

1. **Scripts not executable**:
   ```bash
   chmod +x boot_project.sh shutdown_project.sh
   ```

2. **Symfony CLI not found**:
   ```bash
   # Install Symfony CLI: https://symfony.com/download
   # Or modify scripts to use PHP built-in server instead
   ```

3. **Database connection timeout**:
   ```bash
   # Check if Docker is running
   docker --version
   
   # Restart Docker services
   ./shutdown_project.sh
   ./boot_project.sh
   ```

4. **Port conflicts during boot**:
   ```bash
   # Check what's using the ports
   netstat -tlnp | grep -E "(3306|8000)"
   
   # Stop conflicting services
   sudo systemctl stop mysql apache2 nginx
   ```

### Common Issues

### General Issues

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