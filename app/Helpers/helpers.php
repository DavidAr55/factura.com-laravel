<?php

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

if (!function_exists('facturaRequest')) {
    /**
     * Summary of facturaRequest
     * facturaRequest is a method that returns a pending request to the external API
     * it has the headers and base URL configured to make requests to the external API
     * it sends the request to a sandbox environment
     * @return \Illuminate\Http\Client\PendingRequest
     */
    function facturaRequest(): PendingRequest
    {
        return Http::withHeaders([
            'F-PLUGIN'     => config('app.factura.plugin'),
            'F-API-KEY'    => config('app.factura.api_key'),
            'F-SECRET-KEY' => config('app.factura.secret_key'),
        ])->baseUrl(config('app.factura.host'));
    }
}

if (!function_exists('errorResponse')) {
    /**
     * Summary of errorResponse
     * errorResponse is a method that returns a JSON response with an error message
     * @param \Illuminate\Http\Client\Response $response
     * @return \Illuminate\Http\JsonResponse
     */
    function errorResponse($response): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'External API error',
            'status'  => $response->status(),
            'body'    => $response->body(),
        ], 502);
    }
}