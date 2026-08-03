import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';

const apiKey = document.querySelector('meta[name="wa-gateway-api-key"]')?.content || '';
const ui = SwaggerUIBundle({
    dom_id: '#swagger-ui',
    url: '/openapi.yaml',
    deepLinking: true,
    displayRequestDuration: true,
    filter: true,
    persistAuthorization: true,
    tryItOutEnabled: true,
    defaultModelsExpandDepth: 1,
    presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIBundle.SwaggerUIStandalonePreset,
    ],
    layout: 'BaseLayout',
    onComplete: () => {
        if (apiKey) ui.preauthorizeApiKey('bearerAuth', apiKey);
    },
});
