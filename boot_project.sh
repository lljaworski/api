#!/bin/bash

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

echo "Starting Symfony development server..."
symfony server:start -d

echo "Project boot script finished."
