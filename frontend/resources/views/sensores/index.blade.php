@extends('layouts.app')

@section('content')
    <div class="bg-purple-950 shadow-lg rounded-lg overflow-hidden border border-violet-500 glow-effect">
        <div class="nasa-gradient px-4 py-5 sm:px-6 border-b border-violet-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-purple-300 space-font flex items-center">
                    <i class="fas fa-satellite text-violet-200 mr-3"></i>
                    Telemetría en Tiempo Real
                </h2>
                <div class="text-purple-400 space-font text-sm">
                    <i class="fas fa-satellite-dish mr-2"></i>
                    <span>Panel en vivo</span>
                </div>
            </div>
        </div>

        {{-- 1) IMAGEN EN TIEMPO REAL --}}
        <div class="p-4 bg-gray-900">
            <div class="mx-auto max-w-6xl">
                <div class="rounded-lg border border-violet-700 bg-gray-800 overflow-hidden">
                    <div class="px-4 py-3 border-b border-violet-700 flex items-center justify-between">
                        <h3 class="text-purple-300 space-font font-semibold flex items-center">
                            <i class="fas fa-camera mr-2 text-violet-200"></i> Imagen en tiempo real
                        </h3>
                        <span id="live-frame-status" class="text-xs text-purple-400">—</span>
                    </div>

                    <div class="grid md:grid-cols-3 gap-0">
                        {{-- Imagen (ocupa 2 columnas) --}}
                        <div class="md:col-span-3 bg-black flex items-center justify-center">
                            <img id="live-frame-img" class="max-h-[420px] w-full object-contain select-none"
                                src="http://172.31.7.140:8080/stream" {{-- imagen por defecto --}}
                                data-stream-src="http://172.31.7.140:8080/stream" {{-- URL del stream --}}
                                data-placeholder="http://172.31.7.140:8080/stream" {{-- fallback --}} alt="cam">
                        </div>




                        {{-- Datos a la derecha --}}

                    </div>
                </div>
            </div>
        </div>

        {{-- 2) MÉTRICAS RÁPIDAS: Altitud / Vel. descenso / Aceleración --}}
        <div class="px-4 pb-2 bg-gray-900">
            <div class="mx-auto max-w-6xl">
                <div class="rounded-lg border border-violet-700 bg-gray-800 p-4">
                    <h3 class="text-center text-purple-300 space-font tracking-wide text-sm mb-2">
                        ÚLTIMOS DATOS REGISTRADOS
                    </h3>

                    @php
                        $last = $datos['datos'][0] ?? null;
                    @endphp

                    <div id="tripletMetrics" data-init='@json($last)'
                        class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                        {{-- Altitud --}}
                        <div
                            class="rounded-lg border border-violet-700 bg-gray-900/40 p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-mountain text-violet-300 text-xl"></i>
                                <div>
                                    <div class="text-xs uppercase tracking-wider text-purple-400 space-font">Altura</div>
                                    <div class="text-2xl font-semibold text-purple-100 font-mono">
                                        <span id="metric-alt">—</span>
                                        <span class="text-sm opacity-80">m</span>
                                    </div>
                                </div>
                            </div>
                            <span id="ts-alt" class="text-[10px] text-purple-400/70 font-mono"></span>
                        </div>

                        {{-- Velocidad de descenso --}}
                        <div
                            class="rounded-lg border border-violet-700 bg-gray-900/40 p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-arrow-down text-violet-300 text-xl"></i>
                                <div>
                                    <div class="text-xs uppercase tracking-wider text-purple-400 space-font">
                                        Velocidad de descenso
                                    </div>
                                    <div class="text-2xl font-semibold text-purple-100 font-mono">
                                        <span id="metric-vdesc">—</span>
                                        <span class="text-sm opacity-80">m/s</span>
                                    </div>
                                </div>
                            </div>
                            <span id="ts-vdesc" class="text-[10px] text-purple-400/70 font-mono"></span>
                        </div>

                        {{-- Aceleración |a| --}}
                        <div
                            class="rounded-lg border border-violet-700 bg-gray-900/40 p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-bolt text-violet-300 text-xl"></i>
                                <div>
                                    <div class="text-xs uppercase tracking-wider text-purple-400 space-font">
                                        Aceleración
                                    </div>
                                    <div class="text-2xl font-semibold text-purple-100 font-mono">
                                        <span id="metric-aabs">—</span>
                                        <span class="text-sm opacity-80">m/s²</span>
                                    </div>
                                </div>
                            </div>
                            <span id="ts-aabs" class="text-[10px] text-purple-400/70 font-mono"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3) BIOMA / RIESGO --}}


        {{-- 4) MAPA + MODELO 3D EN TIEMPO REAL --}}
        <div class="p-4 bg-gray-900">
            <div class="mx-auto max-w-6xl">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    {{-- MAPA + TELEMETRÍA --}}
                    <div class="bg-gray-800 rounded-lg border border-violet-700 h-full flex flex-col">
                        <div class="px-4 py-3 border-b border-violet-700/70 bg-gray-900/60 rounded-t-lg">
                            <h3 class="text-purple-300 font-semibold flex items-center gap-2">
                                <i class="fas fa-location-dot text-violet-200"></i>
                                Mapa en tiempo real
                            </h3>
                            <p class="text-xs text-purple-200/60">Trayectoria y últimos paquetes</p>
                        </div>

                        <div id="map-main" class="w-full h-[360px] rounded-b-lg border-b border-violet-700/40"></div>

                        {{-- CONTENEDOR DE TABLA: alto fijo + scroll interno --}}
                        <div class="border-t border-violet-700/70">
                            <div class="h-40 lg:h-72 overflow-y-auto morado-scroll">
                                <table class="min-w-full text-xs tabla-morado table-fixed">
                                    <thead
                                        class="sticky top-0 bg-gray-900/90 border-b border-violet-700/70 text-purple-200 z-10">
                                        <tr>
                                            <th class="text-left px-3 py-2 font-medium uppercase tracking-wider">fecha/hora
                                            </th>
                                            <th class="text-right px-3 py-2 font-medium uppercase tracking-wider">lat</th>
                                            <th class="text-right px-3 py-2 font-medium uppercase tracking-wider">lon</th>
                                            <th class="text-right px-3 py-2 font-medium uppercase tracking-wider">temp</th>
                                            <th class="text-right px-3 py-2 font-medium uppercase tracking-wider">hum</th>
                                        </tr>
                                    </thead>
                                    <tbody id="map-telemetry-body" class="divide-y divide-violet-800/30 text-gray-200">
                                        {{-- filas generadas por JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        <div
                            class="px-4 py-3 border-t border-violet-700/70 flex items-center justify-between gap-3 flex-wrap text-xs md:text-sm text-purple-100/90">
                            <span id="map-telemetry-status"
                                class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full
                                         bg-emerald-900/30 border border-emerald-500/40">
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                paquetes: 0
                            </span>

                            <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                <span id="map-distancia-seleccion"
                                    class="font-semibold px-3 py-1.5 rounded-full bg-violet-800/40
                                             border border-violet-500/50">
                                    distancia: —
                                </span>
                                <span id="map-delta-tiempo-seleccion"
                                    class="font-semibold px-3 py-1.5 rounded-full bg-fuchsia-800/30
                                             border border-fuchsia-500/50">
                                    Δt: —
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button id="map-btn-limpiar-seleccion"
                                    class="px-3 py-1.5 rounded-lg border border-violet-600/60 text-violet-200 hover:text-white
                                               hover:border-violet-400 hover:bg-violet-700/30 transition-colors">
                                    quitar selección
                                </button>
                                <button id="map-btn-limpiar-tabla"
                                    class="px-3 py-1.5 rounded-lg border border-slate-500/60 text-slate-200 hover:text-white
                                               hover:border-slate-300 hover:bg-slate-600/30 transition-colors">
                                    limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- MODELO 3D + IMU --}}
                    <div class="bg-gray-800/60 rounded-lg border border-violet-700/60 flex flex-col">
                        <div class="px-4 py-3 border-b border-violet-700/60 flex items-center justify-between">
                            <h3 class="text-purple-200 space-font font-semibold">
                                <i class="fas fa-cube mr-2"></i> Modelo 3D
                            </h3>
                            <span class="text-xs text-purple-400">orientación / vibraciones</span>
                        </div>

                        <div id="model3d-main" class="w-full h-[500px]"></div>

                        <div class="px-4 pt-5 pb-1 border-t border-violet-700/60">
                            <h4 class="text-purple-200 space-font font-semibold mb-3">IMU en tiempo real</h4>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="p-2 bg-gray-900/60 rounded border border-violet-800/40">
                                    <div class="text-[11px] text-gray-400">Ax (g)</div>
                                    <div id="kpiAx"
                                        class="text-purple-200 font-mono tabular-nums text-right whitespace-nowrap">—</div>
                                </div>
                                <div class="p-2 bg-gray-900/60 rounded border border-violet-800/40">
                                    <div class="text-[11px] text-gray-400">Ay (g)</div>
                                    <div id="kpiAy"
                                        class="text-purple-200 font-mono tabular-nums text-right whitespace-nowrap">—</div>
                                </div>
                                <div class="p-2 bg-gray-900/60 rounded border border-violet-800/40">
                                    <div class="text-[11px] text-gray-400">Az (g)</div>
                                    <div id="kpiAz"
                                        class="text-purple-200 font-mono tabular-nums text-right whitespace-nowrap">—</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 mt-3">
                                <div class="p-2 bg-gray-900/60 rounded border border-violet-800/40">
                                    <div class="text-[11px] text-gray-400">x° (roll)</div>
                                    <div id="kpiRoll"
                                        class="text-purple-200 font-mono tabular-nums text-right whitespace-nowrap">—</div>
                                </div>
                                <div class="p-2 bg-gray-900/60 rounded border border-violet-800/40">
                                    <div class="text-[11px] text-gray-400">y° (pitch)</div>
                                    <div id="kpiPitch"
                                        class="text-purple-200 font-mono tabular-nums text-right whitespace-nowrap">—</div>
                                </div>
                                <div class="p-2 bg-gray-900/60 rounded border border-violet-800/40">
                                    <div class="text-[11px] text-gray-400">z° (yaw)</div>
                                    <div id="kpiYaw"
                                        class="text-purple-200 font-mono tabular-nums text-right whitespace-nowrap">—</div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div> {{-- grid --}}
            </div>
        </div>
        <div class="p-4 bg-gray-900">
            <div class="mx-auto max-w-6xl">
                <div class="rounded-lg border border-violet-700 bg-gray-800 p-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <div class="text-purple-400 text-s uppercase space-font mb-1">
                                Bioma identificado
                            </div>
                            <div id="biome-name" class="text-purple-200 font-medium">—</div>
                            <div id="ecoregion-name" class="text-purple-300 text-sm mt-1 opacity-80">—</div>
                        </div>
                        <div>
                            <div class="text-purple-400 text-s uppercase space-font mb-1">
                                Riesgo de incendio <span class="text-xs lowercase">( Última hora )</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span id="risk-pill"
                                    class="px-2 py-1 text-sm rounded-full bg-gray-600 text-white">—</span>
                                <span id="risk-score" class="text-purple-200 text-sm">—</span>
                            </div>
                            <div id="risk-msg" class="text-purple-400 text-xs mt-1 opacity-70"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- 5) GRID DE CHARTS (uPlot lee datos desde data-rows) --}}
        <div class="p-4 bg-gray-900">
            <div id="chartsGrid" class="grid gap-4 sm:grid-cols-1 xl:grid-cols-2"
                data-rows='@json($datos['datos'] ?? [])'>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">Temp. Externa</h3>
                    <div id="chart-temp-ext" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">Temp. Interna</h3>
                    <div id="chart-temp-int" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">Humedad</h3>
                    <div id="chart-hum" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">Presión</h3>
                    <div id="chart-pres" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">Gas</h3>
                    <div id="chart-gas" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">CO</h3>
                    <div id="chart-co" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">H2</h3>
                    <div id="chart-h2" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">CH4</h3>
                    <div id="chart-ch4" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">C2H2</h3>
                    <div id="chart-c2h2" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">NO2</h3>
                    <div id="chart-no2" style="height:220px"></div>
                </div>

                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">Aceleración Absoluta (m/s²)</h3>
                    <div id="chart-aabs" style="height:220px"></div>
                </div>

                
                <div class="rounded-lg border border-violet-700 bg-gray-800 p-3 chart-card">
                    <h3 class="text-sm text-purple-300 mb-2">Altitud (m)</h3>
                    <div id="chart-alt" style="height:220px"></div>
                </div>

               
            </div>
        </div>

        {{-- OJO: la tabla completa de todos los datos LA VAMOS A MOVER a Estadísticas --}}
    </div>
@endsection

@push('scripts')
    @vite('resources/js/uplot-sensores.js')
    @vite('resources/css/charts.css')

    @vite('resources/js/riesgo-bioma.js')
    @vite('resources/js/ultimos-mini.js')

    {{-- scripts de mapa + modelo 3D (antes estaban en control) --}}
    @vite('resources/js/mapEcharts.js')
    @vite('resources/js/3DmodelEcharts.js')
    @vite('resources/js/modelo3d-feed.js')
    @vite('resources/js/cam-stream-fallback.js')
@endpush
