#!/bin/bash

# Super Admin System Setup Script
# This script automates the setup of the new admin system
# Run this from the admin-system directory

echo "======================================"
echo "Super Admin System Setup"
echo "======================================"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "ERROR: artisan not found. Please run this script from the admin-system directory."
    exit 1
fi

echo "Step 1: Clearing cache..."
php artisan cache:clear
php artisan config:clear
echo "✓ Cache cleared"
echo ""

echo "Step 2: Running database migration..."
php artisan migrate
if [ $? -ne 0 ]; then
    echo "✗ Migration failed"
    exit 1
fi
echo "✓ Database schema updated"
echo ""

echo "Step 3: Seeding admin users..."
php artisan db:seed --class=AdminSeeder
if [ $? -ne 0 ]; then
    echo "✗ Seeding failed"
    exit 1
fi
echo "✓ Admin users created"
echo ""

echo "======================================"
echo "Setup Complete!"
echo "======================================"
echo ""
echo "Default Admin Credentials:"
echo ""
echo "Super Admin:"
echo "  Email: superadmin@tourist-spots.com"
echo "  Password: SuperAdmin@123"
echo ""
echo "Municipality Admins:"
echo "  Email Pattern: [municipality]_admin@tourist-spots.com"
echo "  Password: MuniAdmin@123"
echo ""
echo "Examples:"
echo "  - lingayen_admin@tourist-spots.com"
echo "  - binmaley_admin@tourist-spots.com"
echo "  - urbiztondo_admin@tourist-spots.com"
echo ""
echo "Next Steps:"
echo "1. Start your Laravel server: php artisan serve"
echo "2. Open browser to: http://localhost:8000/login"
echo "3. Log in with one of the credentials above"
echo ""
echo "Documentation: See ADMIN_SYSTEM_SETUP.md for detailed information"
echo ""
