<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CfdiController extends Controller
{
    public function status()
    {
        return response()->json([
            'status' => 200,
            'message' => 'Connection successfully established'
        ]);
    }
}
