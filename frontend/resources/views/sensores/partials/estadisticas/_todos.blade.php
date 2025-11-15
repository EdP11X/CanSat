<div id="estadisticasTodos" class="p-4 bg-gray-900">
    @php
        // Desempaquetar estructura que viene del controlador
        $rows          = $datos['datos'] ?? [];
        $paginaActual  = $datos['pagina_actual'] ?? 1;
        $totalPaginas  = $datos['total_paginas'] ?? 1;
        $totalReg      = is_countable($rows) ? count($rows) : 0;
    @endphp

    @if (!empty($error))
        <div class="bg-red-900/50 border-l-4 border-red-400 p-4 mb-4 text-red-300">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                              clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-300">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h3 class="text-lg font-semibold text-purple-200 space-font flex items-center gap-2">
                <i class="fas fa-database text-violet-300"></i>
                Todos los datos almacenados
            </h3>
            <p class="text-xs text-purple-300/70">
                Registros históricos de telemetría del CanSat.
            </p>
        </div>

        <div class="text-sm text-purple-300 space-font">
            @if($totalReg !== null)
                <span class="px-2 py-1 rounded-full bg-gray-800 border border-violet-700">
                    Total en esta página: <span class="font-semibold">{{ $totalReg }}</span> registros
                </span>
                <span class="ml-2 text-xs text-purple-400">
                    Página {{ $paginaActual }} de {{ $totalPaginas }}
                </span>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-violet-700 bg-gray-800">
        <table class="min-w-full divide-y divide-violet-700">
            <thead>
            <tr class="bg-gray-900">
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-clock mr-2"></i>Fecha/Hora
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-temperature-high mr-2"></i>Temp. Ext
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-temperature-low mr-2"></i>Temp. Int
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-tint mr-2"></i>Humedad
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-compress-arrows-alt mr-2"></i>Presión
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-wind mr-2"></i>Gas
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-smog mr-2"></i>CO
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-atom mr-2"></i>H2
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-flask mr-2"></i>CH4
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-vial mr-2"></i>NH3
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-flask-vial mr-2"></i>EtOH
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-bolt mr-2"></i>|a| (m/s²)
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-mountain mr-2"></i>Altitud (m)
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-400 uppercase tracking-wider space-font">
                    <i class="fas fa-arrow-down mr-2"></i>V. descenso (m/s)
                </th>
            </tr>
            </thead>
            <tbody class="divide-y divide-violet-700">
            @forelse($rows as $dato)
                @php
                    // Soporta array o modelo Eloquent
                    $ts      = $dato['timestamp']   ?? $dato['tsISO'] ?? $dato->timestamp ?? null;
                    $tempExt = $dato['temp_ext']    ?? $dato->temp_ext ?? null;
                    $tempInt = $dato['temp_int']    ?? $dato->temp_int ?? null;
                    $hum     = $dato['humedad']     ?? $dato->humedad ?? null;
                    $pres    = $dato['presion']     ?? $dato->presion ?? null;
                    $gas     = $dato['gas']         ?? $dato->gas ?? null;
                    $co      = $dato['co']          ?? $dato->co ?? null;
                    $h2      = $dato['h2']          ?? $dato->h2 ?? null;
                    $ch4     = $dato['ch4']         ?? $dato->ch4 ?? null;
                    $nh3     = $dato['nh3']         ?? $dato->nh3 ?? null;
                    $etoh    = $dato['etoh']        ?? $dato->etoh ?? null;
                    $aabs    = $dato['a_abs_ms2']   ?? $dato->a_abs_ms2 ?? null;
                    $alt     = $dato['altitud_m']   ?? $dato->altitud_m ?? null;
                    $vdesc   = $dato['v_desc_ms']   ?? $dato->v_desc_ms ?? null;
                @endphp

                <tr class="hover:bg-gray-700 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300 space-font">
                        @if($ts)
                            {{ \Carbon\Carbon::parse($ts)->format('d/m/Y H:i:s') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($tempExt) ? number_format($tempExt, 2) . ' °C' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($tempInt) ? number_format($tempInt, 2) . ' °C' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($hum) ? number_format($hum, 2) . ' %' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($pres) ? number_format($pres, 2) . ' hPa' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($gas) ? number_format($gas, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($co) ? number_format($co, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($h2) ? number_format($h2, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($ch4) ? number_format($ch4, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($nh3) ? number_format($nh3, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($etoh) ? number_format($etoh, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($aabs) ? number_format($aabs, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($alt) ? number_format($alt, 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ is_numeric($vdesc) ? number_format($vdesc, 2) : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="px-6 py-4 text-center text-sm text-purple-300">
                        No hay registros disponibles.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginita sencilla basada en pagina_actual / total_paginas --}}
    @if($totalPaginas > 1)
        <div class="mt-4 flex items-center justify-between text-xs text-purple-300">
            <span>Mostrando página {{ $paginaActual }} de {{ $totalPaginas }}</span>

            <div class="space-x-2">
                @if($paginaActual > 1)
                    <a href="?page={{ $paginaActual - 1 }}"
                       class="px-3 py-1 rounded bg-gray-800 border border-violet-700 hover:bg-gray-700">
                        ← Anterior
                    </a>
                @endif

                @if($paginaActual < $totalPaginas)
                    <a href="?page={{ $paginaActual + 1 }}"
                       class="px-3 py-1 rounded bg-gray-800 border border-violet-700 hover:bg-gray-700">
                        Siguiente →
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
