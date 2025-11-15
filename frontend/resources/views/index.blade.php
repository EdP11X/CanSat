@extends('layouts.app')

@section('content')
<div class="bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-blue-500 glow-effect">
    <div class="nasa-gradient px-4 py-5 sm:px-6 border-b border-blue-700">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-blue-300 space-font flex items-center">
                <i class="fas fa-satellite text-blue-400 mr-3"></i>
                Telemetría en Tiempo Real
            </h2>
            <div class="text-blue-400 space-font text-sm">
                <i class="fas fa-clock mr-2"></i>
                <span id="currentTime">{{ now()->format('H:i:s') }}</span>
            </div>
        </div>
    </div>
    <div class="p-4 bg-gray-900">
        <div id="sensorChart" class="rounded-lg border border-blue-700" style="width: 100%; height: 500px;"></div>
    </div>
    <div class="p-4 bg-gray-900">
        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-blue-700 bg-gray-800">
            <table class="min-w-full divide-y divide-blue-700">
                <thead>
                    <tr class="bg-gray-900">
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-clock mr-2"></i>Fecha/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-temperature-high mr-2"></i>Temperatura</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-tint mr-2"></i>Humedad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-compress-arrows-alt mr-2"></i>Presión</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-wind mr-2"></i>Gas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-smog mr-2"></i>CO</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-atom mr-2"></i>H2</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-flask mr-2"></i>CH4</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-vial mr-2"></i>NH3</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-400 uppercase tracking-wider space-font"><i class="fas fa-flask-vial mr-2"></i>EtOH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-700">
                    @foreach($datos['datos'] as $dato)
                        <tr class="hover:bg-gray-700 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300 space-font">{{ \Carbon\Carbon::parse($dato['timestamp'])->format('d/m/Y H:i:s') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['temperatura'], 2) }} °C</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['humedad'], 2) }} %</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['presion'], 2) }} hPa</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['gas'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['co'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['h2'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['ch4'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['nh3'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-300">{{ number_format($dato['etoh'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
                    </div>

                    <div class="flex justify-center mt-6 space-x-2">
                        <nav class="space-font">
                            <ul class="flex space-x-2">
                                @for($i = 1; $i <= $datos['total_paginas']; $i++)
                                    <li>
                                        <a href="{{ route('sensores.index', ['page' => $i]) }}"
                                           class="px-4 py-2 text-sm {{ $i == $datos['pagina_actual'] ? 'bg-blue-600 text-blue-200' : 'bg-gray-800 text-blue-400 hover:bg-blue-900' }} rounded border border-blue-700 transition-colors duration-200">
                                            {{ $i }}
                                        </a>
                                    </li>
                                @endfor
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection