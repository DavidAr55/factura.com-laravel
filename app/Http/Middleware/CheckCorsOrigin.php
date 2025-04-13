<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckCorsOrigin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        $allowed = config('cors.allowed_origins', []);

        if ($origin && ! in_array($origin, $allowed, true)) {
            Log::warning('CORS origin denied', [
                'origin'    => $origin,
                'url'       => $request->fullUrl(),
                'ip'        => $request->ip(),
                'user_agent'=> $request->userAgent(),
            ]);

            // Return JSON with error 403
            return response()->json([
                'status'  => 403,
                'message' => 'CORS origin not allowed',
                'origin'  => $origin,
            ], 403);
        }

        // If Origin is allowed or not (non-CORS request), continues
        return $next($request);
    }
}
