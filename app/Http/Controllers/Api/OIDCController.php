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
            return $this->server->completeAuthorizationRequest($authRequest, new \Nyholm\Psr7\Response());

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
     * JWKS Endpoint (TODO: Implement PEM to JWK conversion)
     */
    public function jwks()
    {
        // Requires RSA Key parsing to extract modulus (n) and exponent (e)
        // For now, return empty keys set
        return response()->json([
            'keys' => []
        ]);
    }
}
