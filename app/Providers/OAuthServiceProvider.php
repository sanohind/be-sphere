<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\CryptKey;
use App\Repositories\OAuth\ClientRepository;
use App\Repositories\OAuth\AccessTokenRepository;
use App\Repositories\OAuth\ScopeRepository;
use App\Repositories\OAuth\UserRepository;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use DateInterval;

class OAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Fix OpenSSL Config for Laragon/Windows
        // This is required for openssl_pkey_get_private to work properly on this env
        $opensslConf = 'D:/laragon/bin/php/php-8.2.29-Win32-vs16-x64/extras/ssl/openssl.cnf';
        if (file_exists($opensslConf)) {
            putenv("OPENSSL_CONF=$opensslConf");
        }

        $this->app->singleton(AuthorizationServer::class, function () {
            $clientRepository = new ClientRepository();
            $scopeRepository = new ScopeRepository();
            $accessTokenRepository = new AccessTokenRepository();
            $userRepository = new UserRepository();
            $authCodeRepository = new \App\Repositories\OAuth\AuthCodeRepository();
            $refreshTokenRepository = new \App\Repositories\OAuth\RefreshTokenRepository();
            
            // Loading Keys - Standard Way
            // Since we fixed OPENSSL_CONF, standard loading should work
            $privateKeyPath = storage_path('oauth/private.key');
            
            // We pass file:// path explicitly to avoid issues
            // And use forward slashes for better compatibility
            $privateKeyUri = 'file://' . str_replace('\\', '/', $privateKeyPath);
            
            // Pass permissions check as false to avoid Windows permission issues
            $privateKey = new CryptKey($privateKeyUri, null, false);
            
            $encryptionKey = config('oauth2.encryption_key');

            // Setup Authorization Server
            $server = new AuthorizationServer(
                $clientRepository,
                $accessTokenRepository,
                $scopeRepository,
                $privateKey,
                $encryptionKey
            );

            // ✅ Inject Custom OIDC Response Type
            // This replaces the default BearerTokenResponse with our custom one that adds id_token
            $oidcResponseType = new \App\OAuth\OidcTokenResponse();
            $oidcResponseType->setIdTokenPrivateKey($privateKeyPath);
            
            // Use Reflection to replace the protected 'responseType' property
            $reflector = new \ReflectionClass($server);
            $property = $reflector->getProperty('responseType');
            $property->setAccessible(true);
            $property->setValue($server, $oidcResponseType);

            // Enable Auth Code Grant
            $grant = new AuthCodeGrant(
                $authCodeRepository,
                $refreshTokenRepository,
                new DateInterval('PT10M') // Auth code TTL
            );
            
            $grant->setRefreshTokenTTL(new DateInterval('P1M')); // Refresh token TTL
            
            $server->enableGrantType(
                $grant,
                new DateInterval('PT1H') // Access token TTL
            );

            // Enable Refresh Token Grant
            $grant = new \League\OAuth2\Server\Grant\RefreshTokenGrant($refreshTokenRepository);
            $grant->setRefreshTokenTTL(new DateInterval('P1M'));
            
            $server->enableGrantType(
                $grant,
                new DateInterval('PT1H')
            );
            
            return $server;
        });

        $this->app->singleton(ResourceServer::class, function () {
            $accessTokenRepository = new AccessTokenRepository();
            
            // Loading Public Key - Standard Way
            $publicKeyPath = storage_path('oauth/public.key');
            $publicKeyUri = 'file://' . str_replace('\\', '/', $publicKeyPath);
            
            // Pass permissions check as false
            $publicKey = new CryptKey($publicKeyUri, null, false);

            return new ResourceServer(
                $accessTokenRepository,
                $publicKey
            );
        });
    }
}
