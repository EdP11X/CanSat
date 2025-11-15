<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrameController;

Route::get('/api/sensores/latest-frame', [FrameController::class, 'latest'])
    ->name('api.sensores.latest-frame');
