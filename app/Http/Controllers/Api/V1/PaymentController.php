<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function terms(): JsonResponse
    {
        $response = facturaRequest()->get('/v3/catalogo/MetodoPago');
    
        if (!$response->successful()) {
            return errorResponse($response);
        }

        $data = $response->json();
    
        return response()->json($data['data']);
    }

    public function methods(): JsonResponse
    {
        $response = facturaRequest()->get('/v3/catalogo/FormaPago');
    
        if (!$response->successful()) {
            return errorResponse($response);
        }

        $data = $response->json();
    
        return response()->json($data['data']);
    }

    public function currency(): JsonResponse
    {
        $response = facturaRequest()->get('/v3/catalogo/Moneda');
    
        if (!$response->successful()) {
            return errorResponse($response);
        }

        $data = $response->json();
    
        return response()->json($data['data']);
    }
}
