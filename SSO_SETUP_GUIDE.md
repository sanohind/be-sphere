# Be-Sphere SSO Setup Guide

## Overview
Sistem SSO (Single Sign-On) dengan Be-Sphere sebagai Identity Provider (IdP) dan project lain sebagai Service Provider (SP).

## Architecture
```
fe-sphere (Frontend) → be-sphere (Backend/IdP) → fg-sp-app (Service Provider)
```

## Setup Instructions

### 1. Be-Sphere (Identity Provider)
- **Port**: 8000
- **Database**: MySQL dengan tabel users, roles, departments
- **JWT**: Menggunakan tymon/jwt-auth

#### Environment Variables (.env)
```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=be_sphere
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=your-jwt-secret-key
JWT_TTL=60
JWT_REFRESH_TTL=43200

FG_STORE_URL=http://127.0.0.1:8001
INVENTORY_URL=http://127.0.0.1:8002
```

#### Commands
```bash
cd be-sphere-2
composer install
php artisan migrate
php artisan db:seed
php artisan serve --port=8000
```

### 2. Fe-Sphere (Frontend)
- **Port**: 3000 (default Vite)
- **Framework**: React + TypeScript + Tailwind CSS

#### Environment Variables (.env)
```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_FG_STORE_URL=http://127.0.0.1:8001
VITE_INVENTORY_URL=http://127.0.0.1:8002
```

#### Commands
```bash
cd fe-sphere
npm install
npm run dev
```

### 3. FG-SP-App (Service Provider)
- **Port**: 8001
- **Framework**: Laravel
- **SSO**: Menggunakan middleware SSO untuk validasi token

#### Environment Variables (.env)
```env
APP_URL=http://127.0.0.1:8001
SSO_BE_SPHERE_URL=http://127.0.0.1:8000
SSO_CACHE_ENABLED=true
SSO_CACHE_TTL=300
SSO_REQUEST_TIMEOUT=10
```

#### Commands
```bash
cd fg-sp-app
composer install
php artisan migrate
php artisan serve --port=8001
```

## API Endpoints

### Be-Sphere API
- `POST /api/auth/login` - Login user
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/verify-token` - Verify JWT token
- `GET /api/auth/user-info` - Get user information
- `GET /api/dashboard` - Get dashboard with projects
- `GET /api/dashboard/project/{id}/url` - Get project access URL

### FG-SP-App SSO Routes
- `GET /sso/callback` - SSO callback handler
- `POST /sso/logout` - SSO logout
- `GET /sso/user-info` - Get user info from SSO
- `GET /sso/check-auth` - Check authentication status

## User Flow

1. **Login**: User login di fe-sphere
2. **Dashboard**: User melihat project cards berdasarkan role
3. **Project Access**: User klik project card → redirect ke project dengan token
4. **Token Validation**: Project memvalidasi token dengan be-sphere
5. **Access**: User dapat mengakses project tanpa login ulang

## Role-Based Access

### Superadmin (Level 1)
- Akses ke semua project
- Full permissions (read, write, admin)

### Admin (Level 2)
- Akses ke project di departmentnya
- Limited permissions (read, write)

### Operator (Level 3)
- Akses terbatas ke project
- Read-only permissions

### User (Level 4)
- Tidak ada akses ke project

## Testing

### Test Login
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin",
    "password": "password"
  }'
```

### Test Token Verification
```bash
curl -X GET http://127.0.0.1:8000/api/auth/verify-token \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Test Dashboard
```bash
curl -X GET http://127.0.0.1:8000/api/dashboard \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Troubleshooting

### Common Issues

1. **CORS Error**: Pastikan CORS dikonfigurasi di be-sphere
2. **Token Expired**: Check JWT_TTL setting
3. **SSO Validation Failed**: Check SSO_BE_SPHERE_URL setting
4. **Database Connection**: Pastikan database running dan credentials benar

### Debug Commands
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan config:clear
php artisan cache:clear

# Check routes
php artisan route:list
```

## Security Notes

1. **JWT Secret**: Gunakan secret key yang kuat
2. **HTTPS**: Gunakan HTTPS di production
3. **Token Expiry**: Set TTL yang reasonable
4. **Rate Limiting**: Implement rate limiting untuk API
5. **CORS**: Konfigurasi CORS dengan benar

## Next Steps

1. Implement refresh token mechanism
2. Add multi-factor authentication
3. Implement session management
4. Add audit logging
5. Implement role-based UI components
