<div class="p-4 bg-gray-900">
    @if (session('error'))
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
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form id="formFiltros"
          action="{{ route('sensores.cargar-vista-estadisticas',['vista'=>'rangoHoras']) }}"
          method="GET"
          class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-5 space-y-1">
                <label for="horas" class="block text-sm font-medium text-purple-300">Últimas horas</label>
                <div class="relative">
                    <input type="number"
                           class="mt-1 block w-full rounded-md bg-gray-800 border-purple-500 text-purple-300 shadow-lg focus:border-purple-400 focus:ring-purple-400 sm:text-sm"
                           id="horas" name="horas" value="{{ $horas }}" min="1" max="168">
                    <div class="absolute inset-0 bg-purple-400/10 pointer-events-none rounded-md"></div>
                </div>
            </div>
            <div class="flex items-end">
                <button type="submit"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-purple-100 font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 border border-purple-400 shadow-lg transition-all duration-300 hover:shadow-purple-400/50">
                    Actualizar
                </button>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-purple-800">
            <thead>
            <tr class="bg-gray-800">
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    Fecha/Hora</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    Temp. Ext</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    Temp. Int</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    Humedad</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    Presión</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    Gas</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    CO</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    H2</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    CH4</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    NH3</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-purple-700">
                    EtOH</th>
            </tr>
            </thead>
            <tbody class="bg-gray-900 divide-y divide-purple-800/30">
            @foreach ($datos as $dato)
                <tr class="hover:bg-violet-900/20 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-300">
                        {{ \Carbon\Carbon::parse($dato['timestamp'])->format('d/m/Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['temp_ext']) && is_numeric($dato['temp_ext']) ? number_format($dato['temp_ext'], 2) . ' °C' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['temp_int']) && is_numeric($dato['temp_int']) ? number_format($dato['temp_int'], 2) . ' °C' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['humedad']) && is_numeric($dato['humedad']) ? number_format($dato['humedad'], 2) . ' %' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['presion']) && is_numeric($dato['presion']) ? number_format($dato['presion'], 2) . ' hPa' : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['gas']) && is_numeric($dato['gas']) ? number_format($dato['gas'], 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['co']) && is_numeric($dato['co']) ? number_format($dato['co'], 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['h2']) && is_numeric($dato['h2']) ? number_format($dato['h2'], 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['ch4']) && is_numeric($dato['ch4']) ? number_format($dato['ch4'], 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['nh3']) && is_numeric($dato['nh3']) ? number_format($dato['nh3'], 2) : '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">
                        {{ isset($dato['etoh']) && is_numeric($dato['etoh']) ? number_format($dato['etoh'], 2) : '—' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
