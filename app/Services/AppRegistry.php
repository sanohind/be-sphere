<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AppRegistry — Sumber kebenaran tunggal (Single Source of Truth)
 * untuk semua aplikasi yang terdaftar di SPHERE.
 *
 * ✅ Cara menambah aplikasi baru:
 *   Cukup tambahkan entry baru di method getAll() di bawah.
 *   DashboardController dan UserAppAccessController akan otomatis menggunakannya.
 */
class AppRegistry
{
    /**
     * Semua aplikasi yang terdaftar di SPHERE.
     *
     * Field:
     *   id          — identifier unik aplikasi (digunakan di tabel user_app_access)
     *   name        — nama tampilan
     *   description — deskripsi singkat
     *   url         — URL aplikasi (baca dari .env)
     *   icon        — nama icon (untuk FE)
     *   color       — warna tema (untuk FE)
     *   permissions — level akses yang didukung
     */
    public static function getAll(): array
    {
        return [
            [
                'id'          => 'ams',
                'name'        => 'Arrival Management System',
                'description' => 'Arrival management system for incoming goods',
                'url'         => env('AMS_URL', 'http://localhost:5174/#/'),
                'icon'        => 'truck',
                'color'       => 'red',
                'permissions' => ['read', 'write', 'admin'],
            ],
            [
                'id'          => 'scope',
                'name'        => 'SCOPE (Dashboard)',
                'description' => 'Inventory and Warehouse management',
                'url'         => env('SCOPE_URL', 'http://localhost:5175/#/'),
                'icon'        => 'meeting',
                'color'       => 'purple',
                'permissions' => ['read', 'write', 'admin'],
            ],
            [
                'id'          => 'fg-store',
                'name'        => 'Finish Good Store',
                'description' => 'Warehouse management system for finished goods',
                'url'         => env('FG_STORE_URL', 'http://fg-store.ns1.sanoh.co.id'),
                'icon'        => 'warehouse',
                'color'       => 'blue',
                'permissions' => ['read', 'write', 'admin'],
            ],
            [
                'id'          => 'cch',
                'name'        => 'CCH',
                'description' => 'Customer Complaint Handling System',
                'url'         => env('CCH_URL', 'http://localhost:5176'),
                'icon'        => 'qc',
                'color'       => 'orange',
                'permissions' => ['read', 'write', 'admin'],
            ],
            [
                'id'          => 'arrival-dashboard',
                'name'        => 'Arrival Dashboard (Public)',
                'description' => 'Arrival Dashboard for public access',
                'url'         => env('ARRIVAL_DASHBOARD_URL', 'http://localhost:5174/#/arrival-dashboard'),
                'icon'        => 'arrival',
                'color'       => 'green',
                'permissions' => ['read'],
            ],
            [
                'id'          => 'arrival-check',
                'name'        => 'Arrival Check (Public)',
                'description' => 'Arrival Check for driver',
                'url'         => env('ARRIVAL_CHECK_URL', 'http://localhost:5174/#/arrival-check'),
                'icon'        => 'driver',
                'color'       => 'yellow',
                'permissions' => ['read'],
            ],
            // ─────────────────────────────────────────────────────────────────────
            // Tambahkan aplikasi baru di sini.
            // Contoh:
            // [
            //     'id'          => 'new-app',
            //     'name'        => 'New Application',
            //     'description' => 'Description of the new app',
            //     'url'         => env('NEW_APP_URL', 'http://localhost:5180'),
            //     'icon'        => 'app',
            //     'color'       => 'teal',
            //     'permissions' => ['read', 'write'],
            // ],
            // ─────────────────────────────────────────────────────────────────────
        ];
    }

    /**
     * Ambil satu aplikasi berdasarkan ID-nya.
     */
    public static function findById(string $id): ?array
    {
        return collect(self::getAll())->firstWhere('id', $id);
    }

    /**
     * Ambil hanya ID semua aplikasi.
     */
    public static function getAllIds(): array
    {
        return array_column(self::getAll(), 'id');
    }

    /**
     * Ambil data ringkas (tanpa url/permissions) untuk keperluan FE modal.
     */
    public static function getForModal(): array
    {
        return array_map(fn ($app) => [
            'id'          => $app['id'],
            'name'        => $app['name'],
            'description' => $app['description'],
            'icon'        => $app['icon'],
            'color'       => $app['color'],
        ], self::getAll());
    }
}
