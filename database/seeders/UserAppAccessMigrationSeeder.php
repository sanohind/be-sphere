<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Database\Seeder;

/**
 * Migrasi data existing: isi tabel user_app_access
 * berdasarkan mapping departemen lama dari DashboardController.
 *
 * Run: php artisan db:seed --class=UserAppAccessMigrationSeeder
 */
class UserAppAccessMigrationSeeder extends Seeder
{
    /**
     * Mapping departemen (code) → app IDs yang dulu bisa diakses
     */
    private array $departmentAppMapping = [
        'WH'  => ['ams', 'scope', 'cch', 'arrival-dashboard', 'arrival-check'],
        'LOG' => ['fg-store', 'scope', 'cch'],
        'PC'  => ['ams', 'cch'],
        'QC'  => ['cch'],
        'FIN' => ['cch'],
        'TOP' => ['cch'],
        'IT'  => ['ams', 'scope', 'fg-store', 'cch', 'arrival-dashboard', 'arrival-check'],
        'HR'  => ['cch'],
    ];

    public function run(): void
    {
        // Ambil Superadmin sebagai granter
        $superadmin = User::whereHas('role', fn($q) => $q->where('level', 1))->first();

        if (!$superadmin) {
            $this->command->error('No superadmin found. Please seed users first.');
            return;
        }

        $this->command->info("Using superadmin: {$superadmin->name} (ID: {$superadmin->id}) as granter.");

        // Ambil semua user yang bukan superadmin dan punya departemen
        $users = User::whereHas('role', fn($q) => $q->where('level', '>', 1))
            ->with('department')
            ->get();

        $grantedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $departmentCode = $user->department?->code;

            if (!$departmentCode || !isset($this->departmentAppMapping[$departmentCode])) {
                $this->command->warn("  Skipping {$user->name}: no department or no mapping for '{$departmentCode}'");
                $skippedCount++;
                continue;
            }

            $appIds = $this->departmentAppMapping[$departmentCode];

            foreach ($appIds as $appId) {
                // Gunakan updateOrCreate agar idempotent
                UserAppAccess::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'app_id'  => $appId,
                    ],
                    [
                        'granted_by' => $superadmin->id,
                        'granted_at' => now(),
                        'revoked_at' => null,
                    ]
                );
                $grantedCount++;
            }

            $appList = implode(', ', $appIds);
            $this->command->info("  ✅ {$user->name} ({$departmentCode}): {$appList}");
        }

        $this->command->newLine();
        $this->command->info("Migration completed: {$grantedCount} access records created, {$skippedCount} users skipped.");
    }
}
