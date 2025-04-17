<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Extract public key from Bearer token
        $publicKey  = $request->bearerToken();

        // Extract private secret from custom header
        $privateKey = $request->header('F-Api-Secret');

        if (!$publicKey || !$privateKey) {
            Log::warning('Missing API credentials');
            return response()->json([
                'error'   => 'Missing API credentials',
                'message' => 'Key and secret are required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Fetch record matching public key
        $apiKeyRecord = ApiKey::where('key', $publicKey)->first();

        if (!$apiKeyRecord) {
            Log::warning('Invalid API key', ['key' => $publicKey]);
            return response()->json([
                'error'   => 'Invalid key',
                'message' => 'Provided API key is not valid',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verify private secret matches
        if ($apiKeyRecord->secret !== $privateKey) {
            Log::warning('Secret mismatch', ['key' => $publicKey]);
            return response()->json([
                'error'   => 'Invalid secret',
                'message' => 'Provided secret does not match',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Proceed on successful validation
        Log::info('API key validated', ['key' => $publicKey]);
        return $next($request);
    }
}
