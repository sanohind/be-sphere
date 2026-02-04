<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            // Human-friendly identifier used by clients (optional; some flows may still use numeric `id`)
            $table->string('client_id')->unique();

            // Secret for confidential clients (nullable for public SPA clients)
            $table->string('client_secret', 255)->nullable();

            $table->string('name');

            // JSON array string of allowed redirect URIs (we store as text for compatibility)
            // Example: ["https://ams.company.com/#/callback"]
            $table->text('redirect_uris');

            // Optional JSON array string scopes configuration
            $table->text('scopes')->nullable();

            // Whether this client must authenticate at /oauth/token (requires client_secret)
            $table->boolean('is_confidential')->default(false);

            // Soft "enabled/disabled" flag for clients
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};
