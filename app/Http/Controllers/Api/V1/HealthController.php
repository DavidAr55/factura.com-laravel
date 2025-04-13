<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Returns a JSON with the status of the application.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $dbStatus = 'OK';
        } catch (\Exception $e) {
            $dbStatus = 'ERROR';
        }

        $payload = [
            'app'       => config('app.name'),
            'status'    => 'OK',
            'db_status' => $dbStatus,
            'timestamp' => now()->toIso8601String(),
        ];

        $code = ($dbStatus === 'OK') ? 200 : 503;

        return response()->json($payload, $code);
    }
}
