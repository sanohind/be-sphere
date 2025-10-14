// database/migrations/2024_01_01_000002_create_roles_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->tinyInteger('level')->comment('1=superadmin, 2=admin, 3=operator, 4=user');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['name', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};