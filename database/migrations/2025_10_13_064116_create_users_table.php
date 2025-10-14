// database/migrations/2024_01_01_000003_create_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 100)->unique();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('name', 100);
            $table->string('nik', 20)->unique()->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('role_id')->constrained()->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['email', 'username', 'role_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};