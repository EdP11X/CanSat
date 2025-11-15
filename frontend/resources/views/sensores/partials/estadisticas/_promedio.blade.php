<div id ="estadisticasPromedio" class="p-4 bg-gray-900">
        @if(session('error'))
            <div class="bg-red-900/50 border-l-4 border-red-400 p-4 mb-4 text-red-300">
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

        <form id = "formFiltros"action="{{ route('sensores.cargar-vista-estadisticas',['vista'=>'promedio'] )}}" method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label for="inicio" class="block text-sm font-medium text-purple-300">Fecha Inicio</label>
                    <div class="relative">
                        <input type="datetime-local" 
                               class="mt-1 block w-full rounded-md bg-gray-800 border-violet-500 text-purple-300 shadow-lg focus:border-violet-400 focus:ring-violet-400 sm:text-sm" 
                               id="inicio" 
                               name="inicio" 
                               value="{{ request('inicio') }}">
                        <div class="absolute inset-0 bg-violet-400/10 pointer-events-none rounded-md"></div>
                    </div>
                </div>
                <div class="space-y-1">
                    <label for="fin" class="block text-sm font-medium text-purple-300">Fecha Fin</label>
                    <div class="relative">
                        <input type="datetime-local" 
                               class="mt-1 block w-full rounded-md bg-gray-800 border-violet-500 text-purple-300 shadow-lg focus:violet-blue-400 focus:ring-violet-400 sm:text-sm" 
                               id="fin" 
                               name="fin" 
                               value="{{ request('fin') }}">
                        <div class="absolute inset-0 bg-violet-400/10 pointer-events-none rounded-md"></div>
                    </div>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-blue-100 font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 border border-violet-400 shadow-lg transition-all duration-300 hover:shadow-violet-400/50">
                        Filtrar
                    </button>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-violet-800">
                <thead>
                    <tr class="bg-gray-800">
                        <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-violet-700">Sensor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-violet-700">Promedio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-violet-700">Mínimo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider border-b border-violet-700">Máximo</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-900 divide-y divide-violet-800/30">
                                @foreach($estadisticas as $sensor => $datos)
                                    <tr class="hover:bg-violet-900/20 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-purple-300">{{ ucfirst($sensor) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">{{ number_format($datos['promedio'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">{{ number_format($datos['minimo'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-200">{{ number_format($datos['maximo'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>