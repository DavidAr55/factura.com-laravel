<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function index()
    {
        $response = Http::withHeaders([
            'F-PLUGIN'     => config('app.factura.plugin'),
            'F-API-KEY'    => config('app.factura.api_key'),
            'F-SECRET-KEY' => config('app.factura.secret_key'),
        ])->get(config('app.factura.host') . '/v3/catalogo/MetodoPago');
    
        if (!$response->successful()) {
            return response()->json([
                'message' => 'Error al obtener los datos',
                'status' => $response->status(),
                'body' => $response->body(),
            ], $response->status());
        }

        $data = $response->json();
    
        return response()->json($data['data']);
    }
}
