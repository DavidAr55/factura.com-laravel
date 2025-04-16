<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ClientsController extends Controller
{
    public function index(): JsonResponse
    {
        $response = facturaRequest()->get('/v1/clients');
    
        if (!$response->successful()) {
            return errorResponse($response);
        }

        $data = $response->json();
    
        return response()->json($data['data']);
    }
}
