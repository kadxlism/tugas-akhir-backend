#!/bin/bash

# Role Management System Setup Script
# This script sets up the development environment for the role management system

set -e

echo "🚀 Setting up Role Management System..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed. Please install PHP 8.1 or higher."
    exit 1
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed. Please install Composer."
    exit 1
fi

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed. Please install Node.js 18 or higher."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    print_error "npm is not installed. Please install npm."
    exit 1
fi

print_status "Prerequisites check passed"

# Backend setup
echo "📦 Setting up backend..."

# Install PHP dependencies
print_status "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy environment file
if [ ! -f .env ]; then
    print_status "Creating environment file..."
    cp env.example .env
    print_warning "Please update .env file with your database credentials"
else
    print_status "Environment file already exists"
fi

# Generate application key
print_status "Generating application key..."
php artisan key:generate

# Run database migrations
print_status "Running database migrations..."
php artisan migrate --force

# Seed the database with initial data
print_status "Seeding database with initial data..."
php artisan db:seed --class=RoleInitializationSeeder

# Create storage link
print_status "Creating storage link..."
php artisan storage:link

# Set permissions
print_status "Setting proper permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

print_status "Backend setup completed"

# Frontend setup
echo "📦 Setting up frontend..."

cd ../frontend

# Install Node.js dependencies
print_status "Installing Node.js dependencies..."
npm install

# Build frontend assets
print_status "Building frontend assets..."
npm run build

print_status "Frontend setup completed"

# Testing setup
echo "🧪 Setting up testing environment..."

# Backend testing
cd ../backend
print_status "Setting up backend testing database..."
php artisan migrate --env=testing

# Frontend testing
cd ../frontend
print_status "Installing testing dependencies..."
npm install --save-dev

print_status "Testing setup completed"

# Documentation setup
echo "📚 Setting up documentation..."

cd ../backend

# Install Swagger dependencies
print_status "Installing Swagger dependencies..."
composer require darkaonline/l5-swagger --no-interaction

# Publish Swagger configuration
print_status "Publishing Swagger configuration..."
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# Generate Swagger documentation
print_status "Generating API documentation..."
php artisan l5-swagger:generate

print_status "Documentation setup completed"

# Final instructions
echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Update your .env file with correct database credentials"
echo "2. Start the backend server: cd backend && php artisan serve"
echo "3. Start the frontend server: cd frontend && npm run dev"
echo "4. Access the application at http://localhost:3000"
echo "5. Access API documentation at http://localhost:8000/api/documentation"
echo ""
echo "🔐 Default login credentials:"
echo "- Super Admin: superadmin@example.com / SuperAdmin123!"
echo "- Admin: admin@example.com / Admin123!"
echo "- PM: pm@example.com / PM123!"
echo "- Team: team@example.com / Team123!"
echo "- Client: client@example.com / Client123!"
echo ""
echo "🧪 To run tests:"
echo "- Backend tests: cd backend && php artisan test"
echo "- Frontend tests: cd frontend && npm test"
echo "- E2E tests: cd frontend && npm run test:e2e"
echo ""
echo "📖 For more information, check the README.md file"
