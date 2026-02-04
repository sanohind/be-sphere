# OIDC State Parameter Fix - Final Solution

## Problem
Error: `"No state in response"` meskipun callback URL sudah benar:
```
http://localhost:5174/?code=...&state=...#/callback
```

## Root Cause
`oidc-client-ts` library secara default mencari response parameters (code, state) di **hash fragment**, bukan di **query string**. Meskipun kita sudah memindahkan query params sebelum hash di backend, library masih mencari di hash.

## Solution

### 1. Backend Fix (Already Done ✅)
Backend sudah memindahkan query params sebelum hash:
- From: `http://localhost:5174/#/callback?code=...&state=...`
- To: `http://localhost:5174/?code=...&state=...#/callback`

### 2. Frontend Fix (Required)
Tambahkan `response_mode: 'query'` di oidcConfig untuk memberitahu library membaca dari query string:

```typescript
const oidcConfig = {
  // ... other config
  response_mode: 'query', // Read response from query string, not hash
};
```

## Why This Works

1. **Default Behavior**: `oidc-client-ts` default mencari response di hash fragment (`window.location.hash`)
2. **Hash Routing Issue**: Dengan hash routing, query params setelah `#` tidak dikirim ke server
3. **Solution**: Set `response_mode: 'query'` untuk membaca dari `window.location.search` (query string)

## Testing

Setelah menambahkan `response_mode: 'query'`:

1. Restart AMS frontend dev server
2. Login ke Sphere → Pilih AMS
3. Check browser console - seharusnya tidak ada error "No state in response"
4. Check callback URL - format: `http://localhost:5174/?code=...&state=...#/callback`
5. OIDC flow seharusnya berhasil

## Files Modified

1. `AMS/fe-ams/src/auth/oidcConfig.ts` - Added `response_mode: 'query'`
2. `AMS/fe-ams/src/pages/AuthPages/SSOCallback.tsx` - Added debug logging

## Important Notes

- `response_mode: 'query'` tells oidc-client-ts to read response parameters from query string
- This is compatible with hash routing because query params are before hash
- State parameter will be read from `window.location.search`, not `window.location.hash`
