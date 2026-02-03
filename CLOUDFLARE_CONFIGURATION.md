# Konfigurasi Cloudflare di Laravel dengan TrustProxies

## Overview
Konfigurasi ini memungkinkan aplikasi Laravel untuk bekerja dengan baik di belakang Cloudflare proxy, memastikan bahwa IP address asli dari client dapat dideteksi dengan benar.

## Komponen yang Dikonfigurasi

### 1. Package yang Digunakan
- **monicahq/laravel-cloudflare**: Package yang secara otomatis mengambil dan mempercayai IP address dari Cloudflare

### 2. File yang Dibuat/Dimodifikasi

#### a. `app/Http/Middleware/TrustProxies.php`
Middleware ini extends dari `Monicahq\Cloudflare\Http\Middleware\TrustProxies` dan mengkonfigurasi header-header yang dipercaya:

```php
protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB;
```

**Fungsi:**
- Mempercayai header `X-Forwarded-*` dari Cloudflare
- Secara otomatis menggunakan IP address dari Cloudflare's IP ranges
- Mengganti `REMOTE_ADDR` dengan nilai dari header `Cf-Connecting-Ip`

#### b. `config/laravelcloudflare.php`
File konfigurasi untuk package laravel-cloudflare:

```php
'enabled' => (bool) env('LARAVEL_CLOUDFLARE_ENABLED', true),
'replace_ip' => (bool) env('LARAVEL_CLOUDFLARE_REPLACE_IP', true),
```

**Opsi Konfigurasi:**
- `enabled`: Mengaktifkan/menonaktifkan middleware Cloudflare
- `replace_ip`: Mengganti IP address request dengan nilai dari `Cf-Connecting-Ip` header
- `cache`: Key untuk menyimpan daftar IP Cloudflare di cache
- `url`: URL API Cloudflare
- `ipv4-path`: Path untuk mendapatkan IPv4 ranges
- `ipv6-path`: Path untuk mendapatkan IPv6 ranges

#### c. `bootstrap/app.php`
Registrasi middleware di aplikasi:

```php
// Trust Cloudflare proxies
$middleware->trustProxies(
    at: '*',
    headers: \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
             \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
             \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
             \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO |
             \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_AWS_ELB
);

// Use custom TrustProxies middleware for Cloudflare
$middleware->use([
    \App\Http\Middleware\TrustProxies::class,
]);
```

#### d. `.env`
Environment variables untuk konfigurasi Cloudflare:

```env
# Cloudflare Configuration
LARAVEL_CLOUDFLARE_ENABLED=true
LARAVEL_CLOUDFLARE_REPLACE_IP=true
```

## Cara Kerja

### 1. Request Flow
```
Client → Cloudflare → Laravel App
```

### 2. Header Processing
Ketika request masuk melalui Cloudflare:
1. Cloudflare menambahkan header `Cf-Connecting-Ip` dengan IP asli client
2. Cloudflare menambahkan header `X-Forwarded-*` dengan informasi proxy
3. TrustProxies middleware membaca header-header ini
4. Laravel menggunakan IP asli client untuk logging, rate limiting, dll.

### 3. IP Address Detection
- **Tanpa Cloudflare**: `$request->ip()` mengembalikan IP Cloudflare
- **Dengan Cloudflare**: `$request->ip()` mengembalikan IP asli client

## Perintah Artisan

### Reload Cloudflare IP Ranges
```bash
php artisan cloudflare:reload
```
Perintah ini mengambil daftar terbaru IP ranges dari Cloudflare dan menyimpannya di cache.

**Kapan menggunakan:**
- Setelah instalasi pertama kali
- Secara berkala (misalnya via cron job) untuk memastikan IP ranges selalu up-to-date
- Ketika ada perubahan pada infrastruktur Cloudflare

### Clear Configuration Cache
```bash
php artisan config:clear
```
Membersihkan cache konfigurasi setelah mengubah file config atau .env.

## Testing

### 1. Test IP Detection
Buat route test untuk memverifikasi IP detection:

```php
// routes/web.php atau routes/api.php
Route::get('/test-ip', function (Request $request) {
    return response()->json([
        'ip' => $request->ip(),
        'ips' => $request->ips(),
        'cf_connecting_ip' => $request->header('Cf-Connecting-Ip'),
        'x_forwarded_for' => $request->header('X-Forwarded-For'),
    ]);
});
```

### 2. Expected Results
Ketika diakses melalui Cloudflare:
```json
{
    "ip": "123.456.789.0",  // IP asli client
    "ips": ["123.456.789.0"],
    "cf_connecting_ip": "123.456.789.0",
    "x_forwarded_for": "123.456.789.0"
}
```

## Troubleshooting

### Problem: IP masih menunjukkan IP Cloudflare
**Solusi:**
1. Pastikan `LARAVEL_CLOUDFLARE_ENABLED=true` di .env
2. Pastikan `LARAVEL_CLOUDFLARE_REPLACE_IP=true` di .env
3. Jalankan `php artisan config:clear`
4. Jalankan `php artisan cloudflare:reload`

### Problem: Middleware tidak berfungsi
**Solusi:**
1. Pastikan middleware terdaftar di `bootstrap/app.php`
2. Clear cache: `php artisan cache:clear`
3. Clear config: `php artisan config:clear`

### Problem: Rate limiting tidak bekerja dengan benar
**Solusi:**
- Pastikan TrustProxies middleware berjalan sebelum rate limiting middleware
- Verifikasi bahwa `$request->ip()` mengembalikan IP yang benar

## Security Considerations

### 1. Trust Only Cloudflare IPs
Package ini secara otomatis hanya mempercayai IP dari Cloudflare's official IP ranges. Ini mencegah IP spoofing dari sumber lain.

### 2. Regular Updates
Jalankan `php artisan cloudflare:reload` secara berkala (misalnya via cron job) untuk memastikan IP ranges selalu up-to-date:

```bash
# Tambahkan ke crontab atau Laravel scheduler
php artisan cloudflare:reload
```

Di `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('cloudflare:reload')->weekly();
}
```

### 3. Environment-Specific Configuration
Untuk production, pastikan menggunakan nilai yang sesuai:

```env
# Production
LARAVEL_CLOUDFLARE_ENABLED=true
LARAVEL_CLOUDFLARE_REPLACE_IP=true

# Development (jika tidak menggunakan Cloudflare)
LARAVEL_CLOUDFLARE_ENABLED=false
LARAVEL_CLOUDFLARE_REPLACE_IP=false
```

## Benefits

1. **Accurate IP Logging**: Mendapatkan IP asli client untuk logging dan analytics
2. **Proper Rate Limiting**: Rate limiting bekerja berdasarkan IP client, bukan IP Cloudflare
3. **Geolocation**: Fitur geolocation bekerja dengan IP asli client
4. **Security**: Hanya mempercayai IP dari Cloudflare's official ranges
5. **Automatic Updates**: IP ranges Cloudflare diperbarui secara otomatis

## Additional Resources

- [Laravel Cloudflare Package](https://github.com/monicahq/laravel-cloudflare)
- [Cloudflare IP Ranges](https://www.cloudflare.com/ips/)
- [Laravel TrustProxies Documentation](https://laravel.com/docs/11.x/requests#configuring-trusted-proxies)
