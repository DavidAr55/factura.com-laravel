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

        $response = $this->externalClient()->post('/v4/cfdi/list', [
            'month'    => $request->month,
            'year'     => $request->year,
            'rfc'      => $request->rfc,
            'page'     => $request->page,
            'per_page' => $request->per_page ?: 15,
        ]);

        if (!$response->successful()) {
            return $this->errorResponse($response);
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

        // Return paginated response
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
        $response = $this->externalClient()->get("/v4/cfdi/uuid/{$uuid}");

        if (!$response->successful()) {
            return $this->errorResponse($response);
        }

        $payload = $response->json();

        $item = $payload['data'];

        // Return transformed object
        return response()->json([
            'uuid'       => $item['UUID'],
            'uid'        => $item['UID'],
            'cfdi_type'  => $this->getType($item['Folio']),
            'folio'      => $item['Folio'],
            'serial'     => $item['TipoDocumento'],
            'total'      => $item['Total'],
            'date'       => $item['FechaTimbrado'],
            'status'     => $item['Status'],
            'links'      => [
                'cancel' => route('cfdi.cancel', ['uuid' => $uuid]),
                'email'  => route('cfdi.email',  ['uuid' => $uuid]),
                'self'   => route('cfdi.show',   ['uuid' => $uuid]),
                'store'  => route('cfdi.store'),
            ],
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
        // 1) Validación de la petición usando las claves tal cual vienen en el JSON
        $request->validate([
            'Receptor'                      => 'required|array',
            'Receptor.UID'                  => 'required|string',
            'Receptor.ResidenciaFiscal'     => 'nullable|string',

            'TipoDocumento'                 => 'required|string',
            'UsoCFDI'                       => 'required|string',
            'Serie'                         => 'required|string',
            'FormaPago'                     => 'required|string',
            'MetodoPago'                    => 'required|string',
            'Moneda'                        => 'required|string',

            'Conceptos'                     => 'required|array|min:1',
            'Conceptos.*.ClaveProdServ'     => 'required|string',
            'Conceptos.*.Cantidad'          => 'required|numeric',
            'Conceptos.*.ClaveUnidad'       => 'required|string',
            'Conceptos.*.Unidad'            => 'required|string',
            'Conceptos.*.ValorUnitario'     => 'required|numeric',
            'Conceptos.*.Descripcion'       => 'required|string',
            'Conceptos.*.ObjetoImp'         => 'required|string',

            'EnviarCorreo'                  => 'nullable|boolean',
            'BorradorSiFalla'               => 'nullable|boolean',
            'Draft'                         => 'nullable|boolean',
            'CondicionesDePago'             => 'nullable|string',
            'CfdiRelacionados'              => 'nullable|array',
            'TipoCambio'                    => 'nullable|numeric',
            'NumOrder'                      => 'nullable|string',
            'FechaFromAPI'                  => 'nullable|date',
            'Comentarios'                   => 'nullable|string',
            'Cuenta'                        => 'nullable|string',
            'LugarExpedicion'               => 'nullable|string',
        ]);

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

        $response = $this->externalClient()
            ->post('/v4/cfdi40/create', $payload);

        if (! $response->successful()) {
            return $this->errorResponse($response);
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

        $response = $this->externalClient()->post("/v4/cfdi40/{$cfdi_uid}/cancel", $payload);

        if (! $response->successful()) {
            return $this->errorResponse($response);
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
        $response = $this->externalClient()->get("/v4/cfdi40/{$uuid}/email");

        if (! $response->successful()) {
            return $this->errorResponse($response);
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
     * Summary of externalClient
     * externalClient is a method that returns a pending request to the external API
     * it has the headers and base URL configured to make requests to the external API
     * it sends the request to a sandbox environment
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function externalClient(): PendingRequest
    {
        return Http::withHeaders([
            'F-PLUGIN'     => config('app.factura.plugin'),
            'F-API-KEY'    => config('app.factura.api_key'),
            'F-SECRET-KEY' => config('app.factura.secret_key'),
        ])->baseUrl(config('app.factura.host'));
    }

    /**
     * Summary of errorResponse
     * errorResponse is a method that returns a JSON response with an error message
     * @param \Illuminate\Http\Client\Response $response
     * @return \Illuminate\Http\JsonResponse
     */
    private function errorResponse($response): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'External API error',
            'status'  => $response->status(),
            'body'    => $response->body(),
        ], 502);
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
