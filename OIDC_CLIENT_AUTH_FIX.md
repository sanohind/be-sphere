# OIDC Client Authentication Fix

## Problem
Error saat login ke AMS melalui Sphere:
```
error: "invalid_client"
error_description: "Client authentication failed"
message: "Client authentication failed"
```

## Root Cause
1. **ClientRepository menggunakan config file** - Repository mencari client di `config('oauth2.clients')` dengan ID seperti 'scope-client' dan 'ams-client', tetapi database menggunakan struktur berbeda dengan ID numerik (1, 2) dan kolom `client_id` (string).

2. **OAuthClient model tidak sesuai struktur database** - Model menggunakan kolom `secret` dan `redirect`, tetapi database sebenarnya menggunakan `client_secret` dan `redirect_uris`.

3. **Clients tidak memiliki secrets** - Clients di database tidak memiliki `client_secret`, sehingga autentikasi gagal untuk confidential clients.

## Solution

### 1. Updated ClientRepository (`app/Repositories/OAuth/ClientRepository.php`)
- ✅ Sekarang membaca dari database (OAuthClient model) bukan config file
- ✅ Mendukung lookup berdasarkan numeric ID (1, 2) atau client_id string ('scope-client', 'ams-client')
- ✅ Validasi secret untuk confidential clients
- ✅ Public clients (tanpa secret) tidak memerlukan validasi secret

### 2. Updated OAuthClient Model (`app/Models/OAuthClient.php`)
- ✅ Diperbarui untuk menggunakan struktur database yang sebenarnya:
  - `client_id` (string identifier)
  - `client_secret` (secret untuk autentikasi)
  - `redirect_uris` (array JSON)
  - `is_confidential` (boolean)
  - `is_active` (boolean)
- ✅ Menambahkan accessor untuk backward compatibility (`redirect`, `secret`)

### 3. Updated Database Clients
- ✅ Menambahkan `client_secret` ke clients di database
- ✅ Mengatur `is_confidential = true` untuk clients yang memiliki secret
- ✅ SCOPE client (ID: 1): Secret dari `SCOPE_APPLICATION_CLIENT_SECRET`
- ✅ AMS client (ID: 2): Secret dari `AMS_ARRIVAL_MANAGEMENT_SYSTEM_CLIENT_SECRET`

## Configuration Required

### AMS Frontend (.env atau environment variables)
Pastikan AMS frontend memiliki konfigurasi berikut:

```env
VITE_OIDC_AUTHORITY=http://127.0.0.1:8000/api
VITE_OIDC_CLIENT_ID=2
VITE_OIDC_CLIENT_SECRET=ltJeOVUz7vbVm3KDwXrN1HMRtMoZGrofr7W5Bc2t
```

**PENTING**: 
- `VITE_OIDC_CLIENT_ID` harus `2` (numeric ID dari database)
- `VITE_OIDC_CLIENT_SECRET` harus sesuai dengan secret di database untuk client ID 2
- Secret harus di-set di environment variables AMS frontend

### Sphere Backend (.env)
Pastikan Sphere backend memiliki:

```env
AMS_ARRIVAL_MANAGEMENT_SYSTEM_CLIENT_SECRET=ltJeOVUz7vbVm3KDwXrN1HMRtMoZGrofr7W5Bc2t
SCOPE_APPLICATION_CLIENT_SECRET=RpFsx4BNQXpgILGvpY7rbwY1QvZLtwk4RQvuhmIL
```

## Testing

### Test Client Lookup
```bash
php test-client-lookup.php
```

Expected output:
- ✓ Client ditemukan dengan ID '2'
- ✓ Client ditemukan dengan client_id 'ams-client'
- ✓ Secret validation berhasil dengan secret yang benar
- ✗ Secret validation gagal dengan secret yang salah
- ✗ Request tanpa secret ditolak untuk confidential client

### Test Token Endpoint
1. Login ke Sphere
2. Pilih aplikasi AMS
3. Verifikasi redirect ke OIDC authorization
4. Setelah callback, token exchange harus berhasil

## Verification

Untuk memverifikasi clients di database:
```bash
php check-oauth-data.php
```

Expected:
- Client ID 2 (AMS) memiliki `client_secret` yang tidak NULL
- `is_confidential = 1` (true)
- `is_active = 1` (true)

## Next Steps

1. ✅ **Backend Fixed** - ClientRepository dan OAuthClient model sudah diperbaiki
2. ✅ **Database Updated** - Clients sudah memiliki secrets
3. ⏳ **AMS Frontend Configuration** - Pastikan `VITE_OIDC_CLIENT_SECRET` di-set dengan benar
4. ⏳ **Test End-to-End** - Test login flow dari Sphere ke AMS

## Notes

- Clients sekarang adalah **confidential clients** (memerlukan secret)
- Jika ingin menggunakan **public clients** (tanpa secret), set `is_confidential = false` di database
- Public clients biasanya menggunakan PKCE untuk keamanan tambahan
- Untuk production, pastikan secrets disimpan dengan aman dan tidak di-commit ke version control

## Files Modified

1. `app/Repositories/OAuth/ClientRepository.php` - Updated to use database
2. `app/Models/OAuthClient.php` - Updated to match actual database structure
3. Database: `oauth_clients` table - Added secrets to clients

## Files Created (Temporary - for testing)

- `check-oauth-clients.php` - Check clients in database
- `check-oauth-data.php` - Check actual table structure
- `update-clients-with-secrets.php` - Update clients with secrets
- `test-client-lookup.php` - Test client repository
- `check-table-structure.php` - Check table columns

These test files can be deleted after verification.
