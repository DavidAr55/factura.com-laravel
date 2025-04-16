<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CfdiController extends Controller
{
    /**
     * Summary of index
     * index is a method that returns a list of CFDIs
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'month'    => 'nullable|numeric',
            'year'     => 'nullable|numeric',
            'rfc'      => 'nullable|string',
            'page'     => 'nullable|integer',
            'per_page' => 'nullable|integer',
        ]);

        $response = facturaRequest()->post('/v4/cfdi/list', [
            'month'    => $request->month,
            'year'     => $request->year,
            'rfc'      => $request->rfc,
            'page'     => $request->page,
            'per_page' => $request->per_page ?: 15,
        ]);

        if (!$response->successful()) {
            return errorResponse($response);
        }

        $data = $response->json();

        $items = array_map(function ($item) {
            $links = [
                'email'  => route('cfdi.email',  ['uuid' => $item['UUID']]),
                'self'   => route('cfdi.show',   ['uuid' => $item['UUID']]),
            ];

            if ($item['Status'] !== 'cancelada') {
                $links['cancel'] = route('cfdi.cancel', ['uuid' => $item['UUID']]);
            }

            return [
                'uuid'      => $item['UUID'],
                'uid'       => $item['UID'],
                'cfdi_type' => $this->getType($item['Folio']),
                'folio'     => $item['Folio'],
                'serial'    => $this->getSerial($item['Folio']),
                'total'     => $item['Total'],
                'date'      => $item['FechaTimbrado'],
                'status'    => $item['Status'],
                'links'     => $links,
            ];
        }, $data['data']);

        return response()->json([
            'total'        => $data['total'],
            'per_page'     => $data['per_page'],
            'current_page' => $data['current_page'],
            'last_page'    => $data['last_page'],
            'from'         => $data['from'],
            'to'           => $data['to'],
            'data'         => $items,
        ], 200);
    }

    /**
     * Summary of show
     * show is a method that returns a single CFDI by UUID
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $response = facturaRequest()->get("/v4/cfdi/uuid/{$uuid}");

        if (!$response->successful()) {
            return errorResponse($response);
        }

        $payload = $response->json();

        $item = $payload['data'];

        $links = [
            'email' => route('cfdi.email', ['uuid' => $uuid]),
            'self'  => route('cfdi.show',  ['uuid' => $uuid]),
            'store' => route('cfdi.store'),
        ];

        if ($item['Status'] !== 'cancelada') {
            $links['cancel'] = route('cfdi.cancel', ['uuid' => $uuid]);
        }

        return response()->json([
            'uuid'       => $item['UUID'],
            'uid'        => $item['UID'],
            'cfdi_type'  => $this->getType($item['Folio']),
            'folio'      => $item['Folio'],
            'serial'     => $item['TipoDocumento'],
            'total'      => $item['Total'],
            'date'       => $item['FechaTimbrado'],
            'status'     => $item['Status'],
            'links'      => $links,
        ], 200);
    }

    /**
     * Summary of store
     * store is a method that store a new CFDI by 
     * "Receptor", "TipoDocumento", "BorradorSiFalla", "Draft", "Conceptos", "UsoCFDI", "Serie", "FormaPago", "MetodoPago",
     * "CondicionesDePago", "CfdiRelacionados", "Moneda", "TipoCambio", "NumOrder", "FechaFromAPI", "Comentarios",
     * "Cuenta", "EnviarCorreo" and "LugarExpedicion"
     * @param \Illuminate\Http\Request $request
     * @param array $request->Receptor
     * @param string $request->TipoDocumento
     * @param string $request->BorradorSiFalla (Optional)
     * @param string $request->Draft (Optional)
     * @param array $request->Conceptos
     * @param string $request->UsoCFDI
     * @param number $request->Serie
     * @param string $request->FormaPago
     * @param string $request->MetodoPago
     * @param string $request->CondicionesDePago (Optional)
     * @param array $request->CfdiRelacionados (Optional)
     * @param string $request->Moneda
     * @param string $request->TipoCambio (Optional/Required in case the currency attribute is different from MXN)
     * @param string $request->NumOrder (Optional)
     * @param string $request->FechaFromAPI (Optional)
     * @param string $request->Comentarios (Optional)
     * @param string $request->Cuenta (Optional)
     * @param bool $request->EnviarCorreo (Optional)
     * @param string $request->LugarExpedicion (Optional)
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->only([
            'Receptor',
            'TipoDocumento',
            'UsoCFDI',
            'Serie',
            'FormaPago',
            'MetodoPago',
            'Moneda',
            'Conceptos',
            'EnviarCorreo',
            'BorradorSiFalla',
            'Draft',
            'CondicionesDePago',
            'CfdiRelacionados',
            'TipoCambio',
            'NumOrder',
            'FechaFromAPI',
            'Comentarios',
            'Cuenta',
            'LugarExpedicion',
        ]);

        $response = facturaRequest()
            ->post('/v4/cfdi40/create', $payload);

        if (! $response->successful()) {
            return errorResponse($response);
        }

        return response()->json(
            $response->json(),
            201
        );
    }

    /**
     * Summary of cancel
     * cancel is a method that cancels a CFDI by "cfdi_uid", "motivo" and "folioSustituto"
     * @param string $cfdi_uid
     * @param \Illuminate\Http\Request $request
     * @param string $reason
     * @param string $substituteFolio
     * @return JsonResponse
     */
    public function cancel(string $cfdi_uid, Request $request): JsonResponse
    {
        $request->validate([
            'reason'          => 'required|string',
            'substituteFolio' => 'nullable|string',
        ]);

        $payload = ['motivo' => $request->reason];
        if ($request->reason === '01' && $request->substituteFolio !== null) {
            $payload['folioSustituto'] = $request->substituteFolio;
        }

        $response = facturaRequest()->post("/v4/cfdi40/{$cfdi_uid}/cancel", $payload);

        if (! $response->successful()) {
            return errorResponse($response);
        }

        try {
            $data = $response->json();

            return response()->json([
                'response'     => $data['response'],
                'message'      => $data['message'],
                'api_response' => $data['respuestaapi'],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error parsing cancel response', [
                'exception' => $e->getMessage(),
                'body'      => $response->body(),
            ]);

            $raw = json_decode($response->body(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'response' => 'error',
                    'message'  => $response->body(),
                ], 500);
            }

            return response()->json($raw, 500);
        }
    }

    /**
     * Summary of sendEmail
     * sendEmail is a method that sends an email to the users with the CFDI
     * @param string $uuid
     * @return JsonResponse
     */
    public function sendEmail(string $uuid): JsonResponse
    {
        $response = facturaRequest()->get("/v4/cfdi40/{$uuid}/email");

        if (! $response->successful()) {
            return errorResponse($response);
        }

        try {
            $data = $response->json();

            $resp    = $data['response'] ?? 'error';
            $message = $data['message']  ?? 'Error desconocido';

            return response()->json([
                'response' => $resp,
                'uuid'     => $uuid,
                'message'  => $message,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error parsing sendEmail response', [
                'exception' => $e->getMessage(),
                'body'      => $response->body(),
            ]);

            return response()->json([
                'response' => 'error',
                'uuid'     => $uuid,
                'message'  => 'Respuesta inválida del servidor externo',
            ], 500);
        }
    }

    /**
     * Summary of getCfdiTypes
     * getCfdiTypes is a method that returns the type of CFDI based on the folio
     * @param string $folio
     * @return string
     */
    public function getCfdiTypes(): JsonResponse
    {
        $response = Http::withHeaders([
            'F-PLUGIN'     => config('app.factura.plugin'),
            'F-API-KEY'    => config('app.factura.api_key'),
            'F-SECRET-KEY' => config('app.factura.secret_key'),
        ])->get(config('app.factura.host') . '/v4/series');

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

    public function cfdiUsage(): JsonResponse
    {
        $response = facturaRequest()->get('/v4/catalogo/UsoCfdi');
    
        if (!$response->successful()) {
            return errorResponse($response);
        }
    
        return response()->json($response->json());
    }

    public function unit(): JsonResponse
    {
        $response = facturaRequest()->get('/v3/catalogo/ClaveUnidad');
    
        if (!$response->successful()) {
            return errorResponse($response);
        }

        $data = $response->json();
    
        return response()->json($data['data']);
    }



    /**
     * Summary of getType
     * getType is a method that returns the type of CFDI based on the folio
     * @param string $folio
     * @return string
     */
    private function getType(string $folio): string
    {
        return match (true) {
            preg_match('/^F/', $folio)  === 1 => 'Factura',
            preg_match('/^FH/', $folio) === 1 => 'Factura para hoteles',
            preg_match('/^R/', $folio)  === 1 => 'Recibo de honorarios',
            preg_match('/^NC/', $folio) === 1 => 'Nota de cargo',
            preg_match('/^DO/', $folio) === 1 => 'Donativo',
            preg_match('/^RA/', $folio) === 1 => 'Recibo de arrendamiento',
            preg_match('/^N/', $folio)  === 1 => 'Nota de crédito',
            preg_match('/^D/', $folio)  === 1 => 'Nota de débito',
            preg_match('/^ND/', $folio) === 1 => 'Nota de devolución',
            preg_match('/^C/', $folio)  === 1 => 'Carta porte (Traslado)',
            preg_match('/^CI/', $folio) === 1 => 'Carta porte (Ingreso)',
            default                           => 'Desconocido',
        };
    }

    /**
     * Summary of getSerial
     * getSerial is a method that returns the serial of the CFDI
     * @param string $folio
     * @return string
     */
    private function getSerial(string $folio): string
    {
        return explode(' ', $folio)[0];
    }
}
