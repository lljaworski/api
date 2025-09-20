#!/bin/bash

echo "Stopping Symfony development server..."
symfony server:stop

echo "Bringing down Docker Compose services..."
docker-compose down

echo "Clearing Symfony cache..."
php bin/console cache:clear

echo "Project shutdown script finished."
