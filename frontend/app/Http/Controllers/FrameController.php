<?php
// app/Http/Controllers/Api/FrameController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FrameController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        // Puedes mover esto a config/services.php y usar env('SENSORES_API_URL')
        $this->apiUrl = config('services.sensores.api_url', 'http://localhost:5000/api');
    }

    
    public function latest()
{
    try {
        $res = Http::timeout(5)->get(config('services.sensores.api_url').'/frames/latest');
        if (!$res->ok()) {
            return response()->json(['error' => 'API upstream no disponible'], 503);
        }
        $data = $res->json() ?? [];

        // --- NORMALIZAR URL ---
        $url = $data['url'] ?? null; // p.ej. "/frames/shinjiChair.jpg"
        if ($url && !Str::startsWith($url, ['http://','https://'])) {
            $base = rtrim(config('services.sensores.base_files'), '/'); // http://127.0.0.1:5000
            $url  = $base.'/'.ltrim($url, '/'); // -> http://127.0.0.1:5000/frames/shinjiChair.jpg
        }

        return response()->json([
            'id'        => $data['id'] ?? null,
            'url'       => $url,
            'lat'       => $data['lat'] ?? null,
            'lon'       => $data['lon'] ?? null,
            'num'       => $data['num'] ?? null,
            'timestamp' => $data['timestamp'] ?? null,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 503);
    }
}

    public function latestImage()
    {
        try {
            $latest = Http::timeout(5)->get(rtrim($this->apiUrl, '/') . '/frames/latest');
            if (!$latest->successful()) {
                return response()->json(['error' => 'API upstream no disponible'], 503);
            }

            $path = $latest->json('url');
            if (!is_string($path) || !preg_match('/^frames\/[A-Za-z0-9._-]+\.jpe?g$/i', $path)) {
                return response()->json(['error' => 'No hay una imagen de radio disponible'], 404);
            }

            $baseFiles = rtrim(config('services.sensores.base_files'), '/');
            if ($baseFiles === '') {
                return response()->json(['error' => 'Origen de imágenes no configurado'], 503);
            }

            $image = Http::timeout(5)->get($baseFiles . '/' . $path);
            if (!$image->successful()) {
                return response()->json(['error' => 'No se pudo obtener la última imagen'], 503);
            }

            return response($image->body(), 200, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No se pudo obtener la última imagen.'], 503);
        }
    }
}
