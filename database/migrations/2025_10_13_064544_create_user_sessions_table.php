// database/migrations/2024_01_01_000005_create_user_sessions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('token_jti')->unique()->nullable()->comment('JWT Token ID');
            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('logout_at')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            
            $table->index(['user_id', 'token_jti', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
}; 