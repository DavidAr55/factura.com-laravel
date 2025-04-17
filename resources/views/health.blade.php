<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="api-url" content="{{ config('app.url').'/api' }}">
    <meta name="api-key" content="{{ config('app.vue.api_key') }}">
    <meta name="api-secret" content="{{ config('app.vue.api_secret') }}">
    
    <title>Health Check - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f8fa;
            color: #333;
            display: flex;
            height: 100vh;
            margin: 0;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 2rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-ok {
            color: #22c55e;
            font-size: 4rem;
            margin: 0.5rem 0;
        }
        .status-error {
            color: #dc2626;
            font-size: 4rem;
            margin: 0.5rem 0;
        }
        .timestamp {
            color: #64748b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container" id="health-container">
        <h1>{{ config('app.name') }} Health Check</h1>
        <div id="status-indicator" class="status-ok">…</div>
        <div class="timestamp" id="timestamp">Cargando…</div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const indicator = document.getElementById('status-indicator');
        const timestamp = document.getElementById('timestamp');

        const API_URL  = document.querySelector('meta[name="api-url"]').getAttribute('content');
        const API_KEY  = document.querySelector('meta[name="api-key"]').getAttribute('content');
        const SECRET   = document.querySelector('meta[name="api-secret"]').getAttribute('content');

        fetch(`${API_URL}/v1/health`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',  
                'Authorization': `Bearer ${API_KEY}`,
                'F-Api-Secret': SECRET
            }
        })
        .then(async response => {
            const data = await response.json();
            if (response.ok) {
                indicator.textContent = data.status;
                indicator.className = 'status-ok';
            } else {
                indicator.textContent = data.status;
                indicator.className = 'status-error';
            }
            timestamp.textContent = 'As of ' + data.timestamp;
        })
        .catch(err => {
            indicator.textContent = 'ERROR';
            indicator.className = 'status-error';
            timestamp.textContent = 'Could not fetch health data';
            console.error('Health fetch error:', err);
        });
    });
    </script>
</body>
</html>
