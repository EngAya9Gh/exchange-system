#!/bin/bash

# Navigate to the project directory
cd /home/vcmoneytransfer/htdocs/vcmoneytransfer.com

# Pull the latest changes from the main branch
git pull origin main

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# Install Node dependencies and build assets
npm install
npm run build

# Run database migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Restart queues (if using Supervisor for Laravel Horizon/Queue)
# php artisan queue:restart

echo "Deployment finished successfully!"
