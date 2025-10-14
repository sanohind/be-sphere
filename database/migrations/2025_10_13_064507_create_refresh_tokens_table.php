// database/migrations/2024_01_01_000004_create_refresh_tokens_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 500)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['user_id', 'token', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};