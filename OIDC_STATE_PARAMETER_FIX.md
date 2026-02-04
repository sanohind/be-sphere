# OIDC State Parameter Fix

## Problem
Error: `"No state in response"` saat callback dari authorization server.

## Root Cause
Masalah terjadi karena **hash routing** (`#`) di redirect_uri. Ketika OAuth2 server mengembalikan redirect response dengan format:
```
http://localhost:5174/#/callback?code=XXX&state=YYY
```

Query parameters setelah hash (`#`) **tidak dikirim ke server** oleh browser. Browser hanya mengirim bagian sebelum hash ke server, sehingga query parameters hilang dan `oidc-client-ts` tidak bisa membaca `state` parameter.

## Solution
Memindahkan query parameters **sebelum** hash (`#`) dalam redirect response. Format yang benar:
```
http://localhost:5174/?code=XXX&state=YYY#/callback
```

Dengan format ini, browser akan:
1. Mengirim `http://localhost:5174/?code=XXX&state=YYY` ke server
2. Client-side JavaScript bisa membaca query parameters dari URL
3. Hash routing tetap berfungsi dengan `#/callback`

## Implementation

### Updated OIDCController.php
Menambahkan logic untuk memindahkan query parameters sebelum hash:

```php
// Fix redirect URI for hash routing (#)
$location = $response->getHeaderLine('Location');
if ($location && strpos($location, '#') !== false) {
    // Parse and rebuild URL with query params before hash
    $parts = parse_url($location);
    $fixedLocation = $scheme . '://' . $host . $port . $path . $query . $fragment;
    return $response->withHeader('Location', $fixedLocation);
}
```

## Testing

### Before Fix
- Authorization URL: `http://127.0.0.1:8000/api/oauth/authorize?...&state=XXX`
- Callback URL: `http://localhost:5174/#/callback?code=YYY&state=XXX` ❌
- Result: State parameter hilang, error "No state in response"

### After Fix
- Authorization URL: `http://127.0.0.1:8000/api/oauth/authorize?...&state=XXX`
- Callback URL: `http://localhost:5174/?code=YYY&state=XXX#/callback` ✅
- Result: State parameter tersedia, OIDC flow berhasil

## Frontend Compatibility

### AMS Frontend (React Router with Hash Routing)
Frontend harus bisa membaca query parameters dari URL sebelum hash:

```typescript
// oidc-client-ts will automatically read query params from URL
// Format: http://localhost:5174/?code=XXX&state=YYY#/callback
const user = await userManager.signinRedirectCallback();
```

### Route Configuration
Pastikan route `/callback` atau `/#/callback` bisa menangani query parameters:

```typescript
// Route should be accessible at both:
// - /?code=XXX&state=YYY#/callback
// - /#/callback?code=XXX&state=YYY (old format, for compatibility)
```

## Important Notes

1. **Hash Routing Limitation**: Query parameters setelah hash tidak dikirim ke server oleh browser. Ini adalah behavior standar browser.

2. **OIDC Client Library**: `oidc-client-ts` library seharusnya bisa membaca query parameters dari URL sebelum hash. Pastikan library versi terbaru.

3. **Backward Compatibility**: Fix ini tetap kompatibel dengan redirect_uri tanpa hash routing.

## Files Modified

1. `app/Http/Controllers/Api/OIDCController.php` - Added hash routing fix for redirect URI

## Next Steps

1. ✅ **Backend Fixed** - OIDCController sekarang memindahkan query params sebelum hash
2. ⏳ **Test End-to-End** - Test login flow dari Sphere ke AMS
3. ⏳ **Verify State Parameter** - Pastikan state parameter tersedia di callback URL
4. ⏳ **Verify OIDC Flow** - Pastikan authorization code exchange berhasil
