<?php

namespace App\Http\Middleware;

use App\Auth\PortalJwtException;
use App\Auth\PortalJwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PortalJwtApiBearerMiddleware
{
    public function __construct(protected PortalJwtService $jwtService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $payload = $this->jwtService->validateToken($token);
            $user = $this->jwtService->findOrCreateUser($payload);
            auth()->setUser($user);
        } catch (PortalJwtException $e) {
            Log::warning('API bearer token validation failed: '.$e->getMessage());

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
