<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use App\Entities\OAuth\UserEntity;
use Tymon\JWTAuth\Facades\JWTAuth; // ✅ Add JWTAuth


class OIDCController extends Controller
{
    protected $server;
    protected $resourceServer;

    public function __construct(AuthorizationServer $server, ResourceServer $resourceServer)
    {
        $this->server = $server;
        $this->resourceServer = $resourceServer;
    }

    /**
     * OIDC Discovery Endpoint
     */
    public function discovery(): JsonResponse
    {
        $baseUrl = config('app.url');

        return response()->json([
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl . '/api/oauth/authorize',
            'token_endpoint' => $baseUrl . '/api/oauth/token',
            'userinfo_endpoint' => $baseUrl . '/api/oauth/userinfo',
            'jwks_uri' => $baseUrl . '/api/oauth/jwks',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => ['openid', 'profile', 'email'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post'],
            'claims_supported' => ['sub', 'name', 'email', 'profile'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256', 'plain'],
        ]);
    }

    /**
     * Authorization Endpoint
     */
    public function authorize(Request $request)
    {
        // 1. Try to get User from Session (if using Web Guard)
        $user = auth()->user();

        // 2. If not found, try to get User from JWT Token in Query Param (SSO Flow)
        // e.g. FE redirects back to /authorize?token=...
        if (!$user && $request->has('token')) {
            try {
                // Set the token
                \Tymon\JWTAuth\Facades\JWTAuth::setToken($request->query('token'));
                // Authenticate and get user
                $user = \Tymon\JWTAuth\Facades\JWTAuth::authenticate();
            } catch (\Exception $e) {
                // Token invalid
                $user = null;
            }
        }

        // 3. If still not authenticated, redirect to Sphere FE Login
        if (!$user || !($user instanceof \App\Models\User)) {
            // Get the current full URL to return to
            $returnUrl = $request->fullUrl();

            // Sphere Frontend Login URL (Configure in .env)
            $feLoginUrl = env('FE_SPHERE_LOGIN_URL', 'http://localhost:5173/#/signin');

            // Build Redirect URL
            // The FE should login, then redirect back to 'redirect' param with '?token=JWT' appended
            $redirectUrl = $feLoginUrl . '?redirect=' . urlencode($returnUrl);

            // Return JSON if expecting JSON (optional), but Authorize endpoint is Browser flow usually.
            // But since this is API Controller, we can return 401 JSON with location? 
            // Standard OAuth2 says "User Authentication is handled by Authorization Server". 
            // So a 302 Redirect is correct behavior for the Browser.
            return redirect()->away($redirectUrl);
        }

        // Convert Symfony Request to PSR-7 Request
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            // Validate the HTTP request and return an AuthorizationRequest object.
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);

            // Set the authenticated user on the request
            $userEntity = new UserEntity($user->id);
            $authRequest->setUser($userEntity);

            // Auto approve everything (Trusted First Party Apps)
            // In a real SSO for 3rd party, you would show an "Approve Scope" screen here if not approved.
            $authRequest->setAuthorizationApproved(true);

            // Generate response
            $response = $this->server->completeAuthorizationRequest($authRequest, new \Nyholm\Psr7\Response());
            
            // Fix redirect URI for hash routing (#)
            // OAuth2 server puts query params after redirect_uri, but with hash routing,
            // query params after # are not sent to server by browser
            // Solution: Extract query params from fragment and move them before hash
            $location = $response->getHeaderLine('Location');
            
            // Log original location for debugging
            \Log::info('OIDC Authorization Redirect', [
                'original_location' => $location,
                'has_hash' => strpos($location, '#') !== false,
            ]);
            
            if ($location && strpos($location, '#') !== false) {
                // Parse the redirect URL
                $parts = parse_url($location);
                
                // Extract components
                $scheme = $parts['scheme'] ?? 'http';
                $host = $parts['host'] ?? '';
                $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                $path = $parts['path'] ?? '/';
                
                // Handle fragment - it may contain query params
                $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';
                $queryFromFragment = '';
                $fragmentPath = $fragment;
                
                // Check if fragment contains query params (e.g., "/callback?code=xxx&state=yyy")
                if (strpos($fragment, '?') !== false) {
                    list($fragmentPath, $queryFromFragment) = explode('?', $fragment, 2);
                    $queryFromFragment = '?' . $queryFromFragment;
                }
                
                // Also check for query in main URL (if OAuth2 server puts it there)
                $query = isset($parts['query']) ? '?' . $parts['query'] : '';
                
                // Use query from fragment if main query is empty
                if (empty($query) && !empty($queryFromFragment)) {
                    $query = $queryFromFragment;
                }
                
                // Rebuild URL: base + query + fragment (path only)
                // This puts query params BEFORE hash so browser can read them
                $fixedLocation = $scheme . '://' . $host . $port . $path . $query . '#' . $fragmentPath;
                
                // Log fixed location for debugging
                \Log::info('OIDC Authorization Redirect Fixed', [
                    'fixed_location' => $fixedLocation,
                    'query_params' => $query,
                    'fragment_path' => $fragmentPath,
                ]);
                
                // Update response with fixed location
                return $response->withHeader('Location', $fixedLocation);
            }
            
            return $response;

        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new \Nyholm\Psr7\Response());
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'unknown_error',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Token Endpoint
     */
    public function token(Request $request)
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            return $this->server->respondToAccessTokenRequest($psrRequest, new \Nyholm\Psr7\Response());
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new \Nyholm\Psr7\Response());
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'unknown_error',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * UserInfo Endpoint
     */
    public function userInfo(Request $request): JsonResponse
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            // Validate the Bearer token
            $psrRequest = $this->resourceServer->validateAuthenticatedRequest($psrRequest);
            $userId = $psrRequest->getAttribute('oauth_user_id');

            $user = \App\Models\User::find($userId);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Load relationships if they exist
            // $user->load(['role', 'department']);

            return response()->json([
                // Standard OIDC claims
                'sub' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => !is_null($user->email_verified_at),
                'preferred_username' => $user->username ?? $user->email,
                // 'picture' => $user->avatar ?? null,
                'updated_at' => $user->updated_at->timestamp,

                // Custom claims (add check if relation exists)
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'slug' => $user->role->slug,
                    'level' => $user->role->level,
                ] : null,

                // Additional user info
                'nik' => $user->nik,
                'phone_number' => $user->phone_number,
                'is_active' => $user->is_active,
            ]);

        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new \Nyholm\Psr7\Response());
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Verify OIDC Token Endpoint
     * Compatible with /api/auth/verify-token format for AMS backend
     */
    public function verifyOidcToken(Request $request): JsonResponse
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            // Validate the Bearer token
            $psrRequest = $this->resourceServer->validateAuthenticatedRequest($psrRequest);
            $userId = $psrRequest->getAttribute('oauth_user_id');

            $user = \App\Models\User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Load relationships
            $user->load(['role', 'department']);

            // Return in same format as /api/auth/verify-token for compatibility
            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'data' => [
                    'valid' => true,
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'username' => $user->username,
                        'name' => $user->name,
                        'nik' => $user->nik,
                        'phone_number' => $user->phone_number,
                        'avatar' => $user->avatar,
                        'role' => [
                            'id' => $user->role->id,
                            'name' => $user->role->name,
                            'slug' => $user->role->slug,
                            'level' => $user->role->level,
                        ],
                        'department' => $user->department ? [
                            'id' => $user->department->id,
                            'name' => $user->department->name,
                            'code' => $user->department->code,
                        ] : null,
                        'is_active' => $user->is_active,
                        'last_login_at' => $user->last_login_at,
                    ],
                ],
            ]);

        } catch (OAuthServerException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Token is Invalid'
            ], 401);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * JWKS Endpoint - JSON Web Key Set
     * Returns public keys in JWK format for clients to verify token signatures
     */
    public function jwks()
    {
        try {
            $publicKeyPath = storage_path('oauth/public.key');

            if (!file_exists($publicKeyPath)) {
                return response()->json([
                    'error' => 'Public key not found'
                ], 500);
            }

            $publicKeyContent = file_get_contents($publicKeyPath);

            // Parse the PEM public key
            $publicKey = openssl_pkey_get_details(
                openssl_pkey_get_public($publicKeyContent)
            );

            if (!$publicKey || !isset($publicKey['rsa'])) {
                return response()->json([
                    'error' => 'Invalid RSA public key'
                ], 500);
            }

            // Extract RSA components
            $n = $publicKey['rsa']['n']; // Modulus
            $e = $publicKey['rsa']['e']; // Exponent

            // Convert to base64url encoding (JWK standard)
            $nBase64 = rtrim(strtr(base64_encode($n), '+/', '-_'), '=');
            $eBase64 = rtrim(strtr(base64_encode($e), '+/', '-_'), '=');

            // Build JWK
            $jwk = [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'n' => $nBase64,
                'e' => $eBase64,
            ];

            return response()->json([
                'keys' => [$jwk]
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Failed to generate JWKS',
                'message' => config('app.debug') ? $exception->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
