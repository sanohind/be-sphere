<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use App\Entities\OAuth\UserEntity;

/**
 * OIDC Test Controller - FOR DEVELOPMENT ONLY
 * This bypasses authentication for testing purposes
 */
class OIDCTestController extends Controller
{
    protected $server;

    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    /**
     * Test Authorization Endpoint (Bypasses Login)
     * FOR DEVELOPMENT/TESTING ONLY - DO NOT USE IN PRODUCTION
     */
    public function authorizeTest(Request $request)
    {
        // Convert to PSR-7
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            // Validate the authorization request
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);

            // Mock user (Admin ID 1) - BYPASS AUTHENTICATION
            $user = new UserEntity(1);
            $authRequest->setUser($user);

            // Auto approve
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
}
