<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ============================================
        // Laravel Passport Configuration for OIDC
        // ============================================

        // ✅ Token Lifetimes (as approved: 60 minutes)
        Passport::tokensExpireIn(now()->addMinutes(config('passport.access_token_ttl', 60)));
        Passport::refreshTokensExpireIn(now()->addDays(config('passport.refresh_token_ttl', 30)));
        Passport::personalAccessTokensExpireIn(now()->addMonths(config('passport.personal_access_token_ttl', 6)));

        // ✅ OIDC Scopes
        Passport::tokensCan([
            'openid' => 'OpenID Connect',
            'profile' => 'User profile information',
            'email' => 'User email address',
            'roles' => 'User roles and permissions',
            'departments' => 'User department information',
        ]);

        // ✅ Default scope for all tokens
        Passport::setDefaultScope([
            'openid',
            'profile',
            'email',
        ]);

        // ✅ Enable PKCE (as approved)
        // PKCE is enabled by default in Passport 11+
        // No additional configuration needed

        // ✅ Cookie serialization (for session-based auth)
        Passport::withoutCookieSerialization();

        // ✅ Ignore CSRF for API routes
        Passport::ignoreCsrfToken();

        // ✅ Hash client secrets (security best practice)
        Passport::hashClientSecrets();

        // ✅ Load keys from storage (default location)
        // Passport::loadKeysFrom(storage_path('oauth'));

        // ✅ Custom token expiration check
        // Passport::tokensExpireIn(now()->addMinutes(60));
    }
}
