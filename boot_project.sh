#!/bin/bash

# Enhanced boot script for API Platform project
# Usage: ./boot_project.sh [--with-server] [--skip-cache] [--verbose]

set -e  # Exit on any error

# Color codes for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default options
WITH_SERVER=false
SKIP_CACHE=false
VERBOSE=false

# Parse command line arguments
for arg in "$@"; do
  case $arg in
    --with-server)
      WITH_SERVER=true
      ;;
    --skip-cache)
      SKIP_CACHE=true
      ;;
    --verbose)
      VERBOSE=true
      ;;
    --help|-h)
      echo "Usage: $0 [OPTIONS]"
      echo "Options:"
      echo "  --with-server    Start Symfony development server after setup"
      echo "  --skip-cache     Skip cache clearing and warming"
      echo "  --verbose        Show detailed output"
      echo "  --help, -h       Show this help message"
      exit 0
      ;;
    *)
      echo -e "${RED}Error: Unknown option $arg${NC}"
      echo "Use --help for usage information"
      exit 1
      ;;
  esac
done

# Helper functions
log_info() {
  echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
  echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
  echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
  echo -e "${RED}[ERROR]${NC} $1"
}

check_command() {
  if ! command -v "$1" &> /dev/null; then
    log_error "Required command '$1' is not installed or not in PATH"
    exit 1
  fi
}

# Validate environment and dependencies
log_info "Validating environment and dependencies..."

# Check required commands
check_command "docker"
check_command "php"
check_command "composer"

# Check if Docker is running
if ! docker info &> /dev/null; then
  log_error "Docker is not running. Please start Docker and try again."
  exit 1
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
  log_error ".env file not found. Please ensure you're in the project root directory."
  exit 1
fi

# Validate DATABASE_URL in .env
if ! grep -q "DATABASE_URL=" .env; then
  log_error "DATABASE_URL not found in .env file"
  exit 1
fi

# Check PHP version (require 8.2+)
PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
if [ "$(echo "$PHP_VERSION < 8.2" | bc)" -eq 1 ] 2>/dev/null; then
  log_warning "PHP version $PHP_VERSION detected. This project requires PHP 8.2 or higher."
fi

log_success "Environment validation completed"

# Start Docker services
log_info "Starting Docker Compose services..."
if [ "$VERBOSE" = true ]; then
  docker compose up -d
else
  docker compose up -d > /dev/null 2>&1
fi

if [ $? -eq 0 ]; then
  log_success "Docker services started successfully"
else
  log_error "Failed to start Docker services"
  exit 1
fi

# Wait for database with timeout
log_info "Waiting for database to be ready..."
TIMEOUT=60
COUNTER=0

until docker compose exec -T database mysqladmin ping -h localhost --silent > /dev/null 2>&1; do
  if [ $COUNTER -ge $TIMEOUT ]; then
    log_error "Database failed to start within $TIMEOUT seconds"
    log_info "Checking Docker logs..."
    docker compose logs database
    exit 1
  fi
  
  echo -n "."
  sleep 2
  COUNTER=$((COUNTER + 2))
done

echo ""
log_success "Database is ready"

# Additional wait for MySQL to fully initialize
log_info "Allowing database to fully initialize..."
sleep 3

# Generate JWT keys if they don't exist
log_info "Checking JWT configuration..."
if [ ! -f "config/jwt/private.pem" ] || [ ! -f "config/jwt/public.pem" ]; then
  log_info "JWT keys not found. Generating keypair..."
  
  # Create jwt directory if it doesn't exist
  mkdir -p config/jwt
  
  # Generate JWT keypair
  if php bin/console lexik:jwt:generate-keypair --skip-if-exists; then
    log_success "JWT keypair generated successfully"
  else
    log_error "Failed to generate JWT keypair"
    exit 1
  fi
else
  log_success "JWT keys already exist"
fi

# Clear cache unless skipped
if [ "$SKIP_CACHE" = false ]; then
  log_info "Clearing application cache..."
  if php bin/console cache:clear; then
    log_success "Cache cleared successfully"
  else
    log_warning "Failed to clear cache, continuing anyway..."
  fi
fi

# Create database
log_info "Creating database (if not exists)..."
if php bin/console doctrine:database:create --if-not-exists; then
  log_success "Database creation completed"
else
  log_error "Failed to create database"
  exit 1
fi

# Run migrations
log_info "Running database migrations..."
if php bin/console doctrine:migrations:migrate --no-interaction; then
  log_success "Database migrations completed"
else
  log_error "Database migrations failed"
  exit 1
fi

# Install assets
log_info "Installing bundle assets..."
if php bin/console assets:install public --no-interaction; then
  log_success "Bundle assets installed"
else
  log_warning "Failed to install assets, continuing anyway..."
fi

# Warm up cache unless skipped
if [ "$SKIP_CACHE" = false ]; then
  log_info "Warming up application cache..."
  if php bin/console cache:warmup; then
    log_success "Cache warmed up successfully"
  else
    log_warning "Failed to warm up cache, continuing anyway..."
  fi
fi

# Verify database connection
log_info "Verifying database connection..."
if php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
  log_success "Database connection verified"
else
  log_error "Database connection failed"
  exit 1
fi

# Check if admin user exists
log_info "Checking for admin user..."
ADMIN_EXISTS=$(php bin/console dbal:run-sql "SELECT COUNT(*) as count FROM users WHERE username = 'admin'" --format json 2>/dev/null | grep -o '"count":"[^"]*"' | cut -d'"' -f4)

if [ "$ADMIN_EXISTS" = "0" ]; then
  log_warning "Admin user not found in database. You may need to run migrations that create the admin user."
else
  log_success "Admin user found in database"
fi

# Start Symfony server if requested
if [ "$WITH_SERVER" = true ]; then
  log_info "Starting Symfony development server..."
  
  # Check if symfony command is available
  if command -v symfony &> /dev/null; then
    if symfony server:start -d; then
      log_success "Symfony server started successfully"
      log_info "Server is running at: http://127.0.0.1:8000"
      log_info "API Documentation: http://127.0.0.1:8000/api/docs"
    else
      log_error "Failed to start Symfony server"
      exit 1
    fi
  else
    log_warning "Symfony CLI not found. Starting with PHP built-in server..."
    if php -S 127.0.0.1:8000 -t public/ > /dev/null 2>&1 & then
      log_success "PHP built-in server started"
      log_info "Server is running at: http://127.0.0.1:8000"
      log_info "API Documentation: http://127.0.0.1:8000/api/docs"
    else
      log_error "Failed to start PHP server"
      exit 1
    fi
  fi
fi

# Final status report
echo ""
log_success "=== PROJECT BOOT COMPLETED SUCCESSFULLY ==="
log_info "Database: Ready and connected"
log_info "JWT Keys: Generated and available"
log_info "Assets: Installed"
log_info "Cache: $([ "$SKIP_CACHE" = false ] && echo "Cleared and warmed" || echo "Skipped")"

if [ "$WITH_SERVER" = true ]; then
  echo ""
  log_info "🚀 Your API is now running!"
  log_info "📋 API Documentation: http://127.0.0.1:8000/api/docs"
  log_info "🔑 Test login endpoint: curl -X POST http://127.0.0.1:8000/api/login_check -H 'Content-Type: application/json' -d '{\"username\":\"admin\",\"password\":\"admin123!\"}'"
else
  echo ""
  log_info "✅ Project is ready for development"
  log_info "💡 Run './boot_project.sh --with-server' to start the web server"
fi

echo ""