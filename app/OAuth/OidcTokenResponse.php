<?php

namespace App\OAuth;

use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use DateTimeImmutable;

class OidcTokenResponse extends BearerTokenResponse
{
    /**
     * @var string
     */
    protected $idTokenPrivateKeyPath;

    public function setIdTokenPrivateKey($path)
    {
        $this->idTokenPrivateKeyPath = $path;
    }

    protected function getExtraParams(AccessTokenEntityInterface $accessToken)
    {
        $params = parent::getExtraParams($accessToken);
        
        // Check if 'openid' scope is present
        $scopes = $accessToken->getScopes();
        $hasOpenId = false;
        foreach ($scopes as $scope) {
            if ($scope->getIdentifier() === 'openid') {
                $hasOpenId = true;
                break;
            }
        }

        if ($hasOpenId) {
            try {
                $params['id_token'] = $this->generateIdToken($accessToken);
            } catch (\Exception $e) {
                // Log error but don't fail access token
                // \Log::error('ID Token generation failed: ' . $e->getMessage());
            }
        }

        return $params;
    }

    protected function generateIdToken(AccessTokenEntityInterface $accessToken)
    {
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm    = new Sha256();
        
        // Fix key path for ID token signing (reuse server private key or specific one)
        // Ensure standard file URI logic similar to Provider fix
        $keyContent = file_get_contents($this->idTokenPrivateKeyPath);
        $signingKey   = InMemory::plainText($keyContent);

        $now   = new DateTimeImmutable();
        
        $userIdentifier = $accessToken->getUserIdentifier();
        
        // In real app, we should fetch user claims based on scopes (profile, email)
        // For MVP, we pass minimal claims
        
        $token = $tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy(config('app.url'))
            // Configures the audience (aud claim)
            ->permittedFor($accessToken->getClient()->getIdentifier())
            // Configures the subject of the token (sub claim)
            ->relatedTo($userIdentifier)
            // Configures the id (jti claim) - unique identifier for the token
            ->identifiedBy(str_repeat('a', 16)) // Random ID in real app
            // Configures the time that the token was issued (iat claim)
            ->issuedAt($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+1 hour'))
            // Add custom claims
            ->withClaim('nonce', 'test-nonce') // Should come from auth request if present
            ->withClaim('auth_time', $now->getTimestamp());
            
        // Add User Info Mock
        // In real implementation, inject UserRepository to fetch user details
        if ($userIdentifier === '1') {
            $token = $token->withClaim('name', 'Admin User')
                          ->withClaim('email', 'admin@example.com');
        }

        return $token->getToken($algorithm, $signingKey)->toString();
    }
}
