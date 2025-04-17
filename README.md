# factura.com-laravel

Este repositorio es una prueba técnica para la empresa **Factura.com**. El objetivo es demostrar conocimientos en desarrollo backend, específicamente consumiendo su API en el entorno **Sandbox**, y cumpliendo con las siguientes funcionalidades:

### Funcionalidades requeridas:

#### Backend (API RESTful con Laravel)

- Listado de facturas con las siguientes columnas:
  - Tipo de documento
  - Folio
  - Serie
  - Total
  - Fecha
  - Estatus
  - Opciones

- Funcionalidades adicionales:
  - Cancelar CFDI
  - Enviar CFDI por correo electrónico
  - Paginación de resultados
  - Filtrado de resultados
  - Creación de CFDI
  - CRUD de clientes (para enviarlos como receptor)
  - Consumo del API de factura.com

---

## Configuración del proyecto

Para comenzar, creé un proyecto desde cero con **Laravel 12**, configurando CORS desde el inicio (aunque no es parte del requerimiento, lo hago por buenas prácticas). Esto asegura que solo peticiones provenientes de `http://localhost:5173` (mi frontend en Vue 3) puedan acceder al backend.

Archivo `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('VUE_URL', 'http://localhost:5173'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

---

## Middleware de validación de API Keys

Para asegurar la autenticación entre el frontend y el backend, creé un middleware: `app/Http/Middleware/ValidateApiKey.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $publicKey  = $request->bearerToken();

        $privateKey = $request->header('F-Api-Secret');

        if (!$publicKey || !$privateKey) {
            Log::warning('Missing API credentials');
            return response()->json([
                'error'   => 'Missing API credentials',
                'message' => 'Key and secret are required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $apiKeyRecord = ApiKey::where('key', $publicKey)->first();

        if (!$apiKeyRecord) {
            Log::warning('Invalid API key', ['key' => $publicKey]);
            return response()->json([
                'error'   => 'Invalid key',
                'message' => 'Provided API key is not valid',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($apiKeyRecord->secret !== $privateKey) {
            Log::warning('Secret mismatch', ['key' => $publicKey]);
            return response()->json([
                'error'   => 'Invalid secret',
                'message' => 'Provided secret does not match',
            ], Response::HTTP_UNAUTHORIZED);
        }

        Log::info('API key validated', ['key' => $publicKey]);
        return $next($request);
    }
}
```

Este middleware verifica que el usuario envíe **exclusivamente** las API keys necesarias. Estas claves las generé manualmente y las incluí en un **seeder** para tenerlas disponibles siempre en la base de datos del servidor Laravel.

Variables de entorno necesarias (`.env`):

```env
VUE_API_KEY=VUE-dfzo6FZOv7Z9629LLc9aO8PaIDxHFLks
VUE_API_SECRET=VUE-S-BC7DDD9F9DABC7F39F8BEEF721C33-1D1E3
```

---

## Rutas principales (RESTfull API)

En el archivo `routes/api.php`, definí la arquitectura de la API como lo solicitaban las instrucciones:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\CfdiController;
use App\Http\Controllers\Api\V1\ClientsController;
use App\Http\Controllers\Api\V1\PaymentController;

Route::prefix('v1')->middleware('validate')->group(function () {
    Route::get('health', HealthController::class);

    Route::apiResource('cfdi', CfdiController::class)
        ->only(['index','show','store'])
        ->parameters(['cfdi' => 'uuid']);

    Route::post('cfdi/{uuid}/cancel', [CfdiController::class, 'cancel'])->name('cfdi.cancel');
    Route::post('cfdi/{uuid}/email',  [CfdiController::class, 'sendEmail'])->name('cfdi.email');

    Route::get('cfdi-types', [CfdiController::class, 'getCfdiTypes'])->name('cfdi.types');
    Route::get('clients', [ClientsController::class, 'index'])->name('clients');
    Route::get('cfdi-usage', [CfdiController::class, 'cfdiUsage'])->name('cfdi.usage');
    Route::get('payment-terms', [PaymentController::class, 'terms'])->name('payment.terms');
    Route::get('payment-methods', [PaymentController::class, 'methods'])->name('payment.methods');
    Route::get('payment-currency', [PaymentController::class, 'currency'])->name('payment.currency');
    Route::get('unit', [CfdiController::class, 'unit'])->name('unit');
});
```

Ejemplo de consumo de mi RESTful API 'api/v1/cfdi':
```json
{
    "total": 96,
    "per_page": 1,
    "current_page": 1,
    "last_page": 96,
    "from": 1,
    "to": 1,
    "data": [
        {
            "uuid": "b5f5d394-1746-4cbc-be6e-9bbcb4af6f2b",
            "uid": "680034070a0d6",
            "cfdi_type": "Factura",
            "folio": "FH 16",
            "serial": "FH",
            "total": "300.000000",
            "date": "2025-04-16",
            "status": "enviada",
            "links": {
                "email": "http://127.0.0.1:8000/api/v1/cfdi/b5f5d394-1746-4cbc-be6e-9bbcb4af6f2b/email",
                "self": "http://127.0.0.1:8000/api/v1/cfdi/b5f5d394-1746-4cbc-be6e-9bbcb4af6f2b",
                "cancel": "http://127.0.0.1:8000/api/v1/cfdi/b5f5d394-1746-4cbc-be6e-9bbcb4af6f2b/cancel"
            }
        }
    ]
}
```

> Nota: Para completar el formulario de creación, fue necesario consumir más endpoints del API de Factura.com, como métodos de pago, usos de CFDI, monedas, unidades, etc.
---

## ¿Cómo ejecutar el backend?

1. Clonar el repositorio:

```bash
git clone https://github.com/DavidAr55/factura.com-laravel.git
cd factura.com-laravel
```

2. Instalar dependencias:

```bash
composer install
npm install # (no es estrictamente necesario para el backend, pero lo dejo instalado)
```

3. Copiar archivo de entorno:

```bash
cp .env.example .env
```

4. Agregar claves necesarias al `.env`:

```env
VUE_API_KEY=VUE-dfzo6FZOv7Z9629LLc9aO8PaIDxHFLks
VUE_API_SECRET=VUE-S-BC7DDD9F9DABC7F39F8BEEF721C33-1D1E3

F_PLUGIN=9d4095c8f7ed5785cb14c0e3b033eeb8252416ed
F_SECRET_KEY=JDJ5JDEwJHRXbFROTHNiYzRzTXBkRHNPUVA3WU83Y2hxTHdpZHltOFo5UEdoMXVoakNKWTl5aDQwdTFT
```

5. Generar la app key:

```bash
php artisan key:generate
```

6. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

7. Levantar el servidor:

```bash
php artisan serve
```

Si todo se ejecutó correctamente, podrás acceder a una vista de estado del servidor.

> ![Captura servidor](https://github.com/DavidAr55/factura.com-laravel/blob/main/public/Captura%20servidor.png?raw=true)

---

## Autor

**David Arvizu**  
[GitHub: DavidAr55](https://github.com/DavidAr55)