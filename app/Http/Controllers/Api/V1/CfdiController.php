<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
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
            'per_page' => $request->per_page ?: 100,
        ]);

        if (!$response->successful()) {
            return $this->errorResponse($response);
        }

        $data = $response->json();

        // Format items for response
        $items = array_map(fn($item) => [
            'uuid'       => $item['UUID'],
            'cfdi_type'  => $this->getType($item['Folio']),
            'folio'      => $item['Folio'],
            'serial'     => $this->getSerial($item['Folio']),
            'total'      => $item['Total'],
            'date'       => $item['FechaTimbrado'],
            'status'     => $item['Status'],
            'links'      => [
                'cancel' => route('cfdi.cancel', ['uuid' => $item['UUID']]),
                'email'  => route('cfdi.email',  ['uuid' => $item['UUID']]),
                'self'   => route('cfdi.show',   ['uuid' => $item['UUID']]),
                'create' => route('cfdi.store'),
            ],
        ], $data['data']);

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
                'create' => route('cfdi.store'),
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
     * @param array $receiver
     * @param string $documentType
     * @param string $draftIfFail (Optional)
     * @param string $draft (Optional)
     * @param array $concepts
     * @param string $cfdiUsage
     * @param number $serial
     * @param string $paymentForm
     * @param string $paymentMethod
     * @param string $paymentConditions (Optional)
     * @param array $relatedCfdi (Optional)
     * @param string $currency
     * @param string $exchangeRate (Optional/Required in case the currency attribute is different from MXN)
     * @param string $orderNumber (Optional)
     * @param string $dateFromApi (Optional)
     * @param string $comments (Optional)
     * @param string $account (Optional)
     * @param bool $sendEmail (Optional)
     * @param string $placeOfIssue (Optional)
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'receiver'          => 'required|array',
            'documentType'      => 'required|string',
            'draftIfFail'       => 'nullable|string',
            'draft'             => 'nullable|string',
            'concepts'          => 'required|array',
            'cfdiUsage'         => 'required|string',
            'serial'            => 'required|numeric',
            'paymentForm'       => 'required|string',
            'paymentMethod'     => 'required|string',
            'paymentConditions' => 'nullable|string',
            'relatedCfdi'       => 'nullable|array',
            'currency'          => 'required|string',
            'exchangeRate'      => 'nullable|string',
            'orderNumber'       => 'nullable|string',
            'dateFromApi'       => 'nullable|string',
            'comments'          => 'nullable|string',
            'account'           => 'nullable|string',
            'sendEmail'         => 'nullable|bool',
            'placeOfIssue'      => 'nullable|string',
        ]);
        $response = $this->externalClient()->post('/v4/cfdi40/create', [
            'Receptor'          => $request->receiver,
            'TipoDocumento'     => $request->documentType,
            'BorradorSiFalla'   => $request->draftIfFail,
            'Draft'             => $request->draft,
            'Conceptos'         => $request->concepts,
            'UsoCFDI'           => $request->cfdiUsage,
            'Serie'             => $request->serial,
            'FormaPago'         => $request->paymentForm,
            'MetodoPago'        => $request->paymentMethod,
            'CondicionesDePago' => $request->paymentConditions,
            'CfdiRelacionados'  => $request->relatedCfdi,
            'Moneda'            => $request->currency,
            'TipoCambio'        => $request->exchangeRate,
            'NumOrder'          => $request->orderNumber,
            'FechaFromAPI'      => $request->dateFromApi,
            'Comentarios'       => $request->comments,
            'Cuenta'            => $request->account,
            'EnviarCorreo'      => $request->sendEmail,
            'LugarExpedicion'   => $request->placeOfIssue,
        ]);
        
        if (! $response->successful()) {
            return $this->errorResponse($response);
        }
    
        return response()->json($response->json(), 201);
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
            'substituteFolio' => 'required|string'
        ]);

        $response = $this->externalClient()->post("/v4/cfdi40/{$cfdi_uid}/cancel", [
            'motivo' => $request->reason,
            'folioSustituto' => $request->substituteFolio
        ]);
        
        if (!$response->successful()) {
            return $this->errorResponse($response);
        }

        $data = $response->json();

        return response()->json([
            'response'     => $data['response'],
            'message'      => $data['message'],
            'api_response' => $data['respuestaapi'],
        ], 200);
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

        return response()->json([
            'response' => $response['response'],
            'uuid'     => $uuid,
            'message'  => $response['message']
        ], 200);
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
