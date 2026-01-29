#!/bin/bash

# Quick Fix Script for Sphere SSO Route 404
# Run this script on Sphere production server

echo "========================================="
echo "Sphere SSO Route Fix Script"
echo "========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running in correct directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found!${NC}"
    echo "Please run this script from Sphere backend root directory"
    echo "Example: cd /var/www/sphere/be-sphere && bash fix-sso-route.sh"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking routes/web.php...${NC}"
if grep -q "Route::get('/sso/login'" routes/web.php; then
    echo -e "${GREEN}✓ SSO route found in routes/web.php${NC}"
else
    echo -e "${RED}✗ SSO route NOT found in routes/web.php${NC}"
    echo "Please add the SSO route to routes/web.php first!"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2: Clearing all caches...${NC}"

# Clear config cache
php artisan config:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Config cache cleared${NC}"
else
    echo -e "${RED}✗ Failed to clear config cache${NC}"
fi

# Clear route cache
php artisan route:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Route cache cleared${NC}"
else
    echo -e "${RED}✗ Failed to clear route cache${NC}"
fi

# Clear view cache
php artisan view:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ View cache cleared${NC}"
else
    echo -e "${RED}✗ Failed to clear view cache${NC}"
fi

# Clear application cache
php artisan cache:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Application cache cleared${NC}"
else
    echo -e "${RED}✗ Failed to clear application cache${NC}"
fi

echo ""
echo -e "${YELLOW}Step 3: Rebuilding caches...${NC}"

# Rebuild config cache
php artisan config:cache
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Config cache rebuilt${NC}"
else
    echo -e "${RED}✗ Failed to rebuild config cache${NC}"
fi

# Rebuild route cache
php artisan route:cache
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Route cache rebuilt${NC}"
else
    echo -e "${RED}✗ Failed to rebuild route cache${NC}"
fi

echo ""
echo -e "${YELLOW}Step 4: Checking .env configuration...${NC}"

if grep -q "FE_SPHERE_LOGIN_URL" .env; then
    FE_URL=$(grep "FE_SPHERE_LOGIN_URL" .env | cut -d '=' -f2)
    echo -e "${GREEN}✓ FE_SPHERE_LOGIN_URL found: $FE_URL${NC}"
else
    echo -e "${RED}✗ FE_SPHERE_LOGIN_URL not found in .env${NC}"
    echo "Please add: FE_SPHERE_LOGIN_URL=https://sphere.sanohindonesia.co.id/#/signin"
fi

if grep -q "SCOPE_URL" .env; then
    SCOPE_URL=$(grep "SCOPE_URL" .env | cut -d '=' -f2)
    echo -e "${GREEN}✓ SCOPE_URL found: $SCOPE_URL${NC}"
else
    echo -e "${RED}✗ SCOPE_URL not found in .env${NC}"
    echo "Please add: SCOPE_URL=https://scope.sanohindonesia.co.id"
fi

echo ""
echo "========================================="
echo -e "${GREEN}Fix script completed!${NC}"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Test the SSO route:"
echo "   curl -I https://sphere.sanohindonesia.co.id/sso/login?redirect=https://scope.sanohindonesia.co.id"
echo ""
echo "2. Expected response: HTTP 302 redirect"
echo ""
echo "3. If still not working, check:"
echo "   - Apache/Nginx configuration"
echo "   - Server logs: /var/log/apache2/error.log"
echo "   - Laravel logs: storage/logs/laravel.log"
echo ""
