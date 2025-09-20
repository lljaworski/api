#!/bin/bash

WITH_SERVER=false
for arg in "$@"; do
  if [ "$arg" == "--with-server" ]; then
    WITH_SERVER=true
    break
  fi
done

echo "Starting Docker Compose services..."
docker-compose up -d

echo "Waiting for database to be ready..."
until docker-compose exec -T database mysqladmin ping -h localhost --silent; do
  echo "Database is unavailable - sleeping"
  sleep 2
done
echo "Database is up - continuing"

echo "Creating database..."
php bin/console doctrine:database:create

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

if [ "$WITH_SERVER" = true ]; then
  echo "Starting Symfony development server..."
  symfony server:start -d
fi

echo "Project boot script finished."