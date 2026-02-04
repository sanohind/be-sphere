# OIDC Redirect URI & Client Authentication Fix

## Masalah yang Ditemukan

Dari URL yang dikirim, terlihat:
```
redirect_uri=http://localhost:5174/
```

Tapi redirect URI yang terdaftar di database adalah:
```
http://localhost:5174/#/callback
```

Error: `"invalid_client"` / `"Client authentication failed"` terjadi di **token endpoint** saat exchange authorization code untuk access token.

## Root Cause

1. **Redirect URI Mismatch**: Redirect URI yang dikirim (`http://localhost:5174/`) tidak sesuai dengan yang terdaftar di database (`http://localhost:5174/#/callback`)

2. **OIDCRedirectService menggunakan env bukan database**: Service menggunakan redirect_uri dari env variable, bukan dari database client

3. **Client Secret mungkin tidak dikirim**: AMS frontend mungkin tidak mengirim `client_secret` saat token exchange

## Perbaikan yang Dilakukan

### 1. Updated OIDCRedirectService
- ✅ Sekarang menggunakan redirect_uri dari database client, bukan dari env
- ✅ Fallback ke env jika database tidak memiliki redirect_uri
- ✅ Mendukung multiple redirect URIs dari database

### 2. Updated ClientRepository
- ✅ Memperbaiki parsing redirect_uris dari database (mendukung JSON array)
- ✅ Mengembalikan semua redirect URIs yang terdaftar

## Konfigurasi yang Diperlukan

### AMS Frontend (.env atau environment variables)

**PENTING**: Pastikan AMS frontend memiliki konfigurasi berikut:

```env
VITE_OIDC_AUTHORITY=http://127.0.0.1:8000/api
VITE_OIDC_CLIENT_ID=2
VITE_OIDC_CLIENT_SECRET=ltJeOVUz7vbVm3KDwXrN1HMRtMoZGrofr7W5Bc2t
```

**CRITICAL**: 
- `VITE_OIDC_CLIENT_SECRET` **HARUS** di-set, karena client sekarang adalah confidential client
- `redirect_uri` di oidcConfig.ts akan otomatis menggunakan `${window.location.origin}/#/callback` yang menghasilkan `http://localhost:5174/#/callback` - ini sudah benar

### Verifikasi AMS Frontend Config

Pastikan `AMS/fe-ams/src/auth/oidcConfig.ts` memiliki:

```typescript
const oidcConfig = {
  authority: 'http://127.0.0.1:8000/api',
  client_id: '2',
  client_secret: import.meta.env.VITE_OIDC_CLIENT_SECRET, // MUST be set!
  redirect_uri: `${window.location.origin}/#/callback`, // This is correct
  // ...
};
```

## Testing

### 1. Test Redirect URI Matching
```bash
php test-redirect-uri-validation.php
```

Expected:
- ✓ `http://localhost:5174/#/callback` matches
- ✗ `http://localhost:5174/` does NOT match (this is the problem!)

### 2. Test Client Lookup
```bash
php test-client-lookup.php
```

Expected:
- ✓ Client found with ID '2'
- ✓ Secret validation works with correct secret
- ✗ Secret validation fails without secret (for confidential client)

### 3. End-to-End Test

1. **Pastikan AMS frontend memiliki `VITE_OIDC_CLIENT_SECRET` di environment variables**
2. Login ke Sphere (http://localhost:5173)
3. Pilih aplikasi AMS
4. Verifikasi redirect ke: `http://127.0.0.1:8000/api/oauth/authorize?client_id=2&redirect_uri=http://localhost:5174/#/callback&...`
5. Setelah callback, token exchange harus berhasil

## Troubleshooting

### Error: "invalid_client" / "Client authentication failed"

**Kemungkinan penyebab:**
1. ❌ `VITE_OIDC_CLIENT_SECRET` tidak di-set di AMS frontend
2. ❌ `client_secret` yang dikirim tidak sesuai dengan yang ada di database
3. ❌ `redirect_uri` yang dikirim tidak sesuai dengan yang terdaftar

**Solusi:**
1. Pastikan `VITE_OIDC_CLIENT_SECRET` di-set di `.env` atau environment variables AMS frontend
2. Restart AMS frontend dev server setelah mengubah env variables
3. Verifikasi redirect_uri yang dikirim adalah `http://localhost:5174/#/callback` (bukan `http://localhost:5174/`)

### Error: Redirect URI Mismatch

**Kemungkinan penyebab:**
- Redirect URI yang dikirim berbeda dengan yang terdaftar di database

**Solusi:**
1. Pastikan redirect_uri di database sesuai dengan yang digunakan frontend
2. Update database jika perlu:
   ```sql
   UPDATE oauth_clients 
   SET redirect_uris = '["http://localhost:5174/#/callback"]' 
   WHERE id = 2;
   ```

## Files Modified

1. `app/Services/OIDCRedirectService.php` - Now uses redirect_uri from database
2. `app/Repositories/OAuth/ClientRepository.php` - Improved redirect_uris parsing

## Next Steps

1. ✅ **Backend Fixed** - OIDCRedirectService dan ClientRepository sudah diperbaiki
2. ⏳ **AMS Frontend Configuration** - **PENTING**: Set `VITE_OIDC_CLIENT_SECRET` di environment variables
3. ⏳ **Restart AMS Frontend** - Restart dev server setelah mengubah env variables
4. ⏳ **Test End-to-End** - Test login flow dari Sphere ke AMS

## Important Notes

- **Client Secret is REQUIRED**: Karena clients sekarang adalah confidential clients, `client_secret` **HARUS** dikirim saat token exchange
- **Redirect URI must match exactly**: OAuth2 server memerlukan exact match antara redirect_uri yang dikirim dan yang terdaftar
- **Environment Variables**: Vite memerlukan restart dev server untuk membaca perubahan di `.env` file
