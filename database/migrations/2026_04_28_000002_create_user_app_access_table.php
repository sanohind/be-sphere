<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_app_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('app_id', 50); // e.g. 'ams', 'scope', 'fg-store', 'cch'
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable(); // null = access is active
            $table->timestamps();

            // Satu user hanya punya satu record per app (re-grant = update revoked_at)
            $table->unique(['user_id', 'app_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_app_access');
    }
};
