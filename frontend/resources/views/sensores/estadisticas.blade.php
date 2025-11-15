@extends('layouts.app')

@section('content')
    <div class="bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-violet-500 glow-effect">
        {{-- Encabezado --}}
        <div class="nasa-gradient px-4 py-5 sm:px-6 border-b border-violet-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-purple-300 space-font flex items-center">
                    <i class="fas fa-chart-bar text-violet-200 mr-3"></i>
                    Estadísticas de Sensores
                </h2>
                <div class="text-purple-400 space-font text-sm">
                    <i class="fas fa-satellite-dish mr-2"></i>
                    <span>Control de Misión</span>
                </div>
            </div>
        </div>

        {{-- Pestañas --}}

        <div class="flex space-x-4   bg-gray-900 border-b border-violet-700">
            <button onclick="cargarVista('promedio')" data-vista="promedio"
                class="tab-btn h-full {{ $pagina === 'promedio' ? 'bg-violet-900 font-bold text-white' : ' ' }}  'text-purple-300' hover:text-white px-4 py-6 rounded transition-colors duration-200">
                Promedios
            </button>
            <button onclick="cargarVista('rangoHoras')" data-vista='rangoHoras'
                class="tab-btn h-full text-purple-300 hover:text-white px-4 py-6 rounded transition-colors duration-200">
                Rango por Hora
            </button>
            <button onclick="cargarVista('todos')" data-vista="todos"
                class="tab-btn h-full text-purple-300 hover:text-white px-4 py-6 rounded transition-colors duration-200">
                Todos los datos
            </button>

        </div>

        {{-- Contenedor dinámico --}}
        <div id="contenedorContenido" class="p-4 bg-gray-900 min-h-[300px]">
            {!! $contenido ?? '<div class="text-center text-gray-400">Cargando estadísticas...</div>' !!}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Función mejorada para cargar vistas
        function cargarVista(vista) {
            const contenedor = document.getElementById('contenedorContenido');
            contenedor.innerHTML = '<div class="text-center text-gray-400">Cargando...</div>';

            fetch(`/sensores/cargar-vista-estadisticas/${vista}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.text();
                })
                .then(html => {
                    contenedor.innerHTML = html;
                    resaltarBotonActivo(vista);
                })
                .catch(error => {
                    console.error('Error:', error);
                    contenedor.innerHTML = `
                    <div class="text-red-400 p-4 bg-gray-800 rounded">
                        Error al cargar los datos: ${error.message}
                    </div>
                `;
                });
        }
    </script>

    <script>
        // Manejo de formularios
        document.addEventListener('submit', function(e) {
            if (e.target.matches('#formFiltros')) {
                e.preventDefault();
                const form = e.target;
                const contenedor = document.getElementById('contenedorContenido');

                contenedor.innerHTML = '<div class="text-center text-gray-400">Aplicando filtros...</div>';

                fetch(form.action + '?' + new URLSearchParams(new FormData(form)))
                    .then(res => res.text())
                    .then(html => {
                        contenedor.innerHTML = html;
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        contenedor.innerHTML = `
                        <div class="text-red-400 p-4 bg-gray-800 rounded">
                            Error al aplicar filtros: ${err.message}
                        </div>
                    `;
                    });
            }
        });
    </script>
    <script>
        function resaltarBotonActivo(vistaActiva) {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.dataset.vista === vistaActiva) {
                    btn.classList.add('bg-violet-900', 'font-bold', 'text-white');
                } else {
                    btn.classList.remove('bg-violet-900', 'font-bold', 'text-white');
                    btn.classList.add('text-purple-300');
                }
            });
        }
    </script>
@endpush
