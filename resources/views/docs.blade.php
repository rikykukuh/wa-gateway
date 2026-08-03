<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="wa-gateway-api-key" content="{{ $apiKey }}">
    <title>WA Gateway API Documentation</title>
    @vite('resources/js/swagger.js')
    <style>
        body { margin: 0; background: #f4f7f9; }
        .topbar-custom {
            display: flex; align-items: center; gap: 12px; padding: 14px 24px;
            background: #075e54; color: white; font-family: system-ui, sans-serif;
        }
        .topbar-custom strong { font-size: 18px; }
        .topbar-custom span { color: #d7f8ef; font-size: 13px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #25d366; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 28px 0 20px; }
        .swagger-ui .scheme-container { box-shadow: none; border-radius: 8px; }
    </style>
</head>
<body>
    <header class="topbar-custom">
        <div class="status-dot"></div>
        <strong>WA Gateway</strong>
        <span>API Documentation & Testing Console</span>
    </header>
    <div id="swagger-ui"></div>
</body>
</html>
