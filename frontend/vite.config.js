import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        proxy: {
            'api/': 'http://localhost:5173',
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 
                'resources/js/app.js', 
                'resources/js/echarts.js', 
                'resources/js/echarts-arduino.js',
                'resources/js/uplot-sensores.js',
                 'resources/js/mapEcharts.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
