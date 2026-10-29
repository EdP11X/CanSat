import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        origin: process.env.VITE_DEV_SERVER_ORIGIN || 'http://localhost:5173',
        cors: {
            origin: [
                process.env.VITE_APP_ORIGIN,
                'http://localhost:8000',
                'http://127.0.0.1:8000',
                /^http:\/\/(?:10(?:\.\d{1,3}){3}|192\.168(?:\.\d{1,3}){2}|172\.(?:1[6-9]|2\d|3[01])(?:\.\d{1,3}){2}):8000$/,
            ].filter(Boolean),
        },
        proxy: {
            'api/': 'http://localhost:5173',
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css',
                'resources/css/charts.css',
                'resources/js/app.js',
                'resources/js/uplot-sensores.js',
                'resources/js/mapEcharts.js',
                'resources/js/3DmodelEcharts.js',
                'resources/js/modelo3d-feed.js',
                'resources/js/riesgo-bioma.js',
                'resources/js/ultimos-mini.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
