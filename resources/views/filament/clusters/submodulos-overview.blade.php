<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Banner Institucional TecNM -->
        <div class="tecnm-banner relative overflow-hidden rounded-2xl p-6 text-white shadow-xl" style="background: linear-gradient(135deg, #1B396A 0%, #162e54 50%, #1B396A 100%) !important; border-bottom: 4px solid #A57F2C !important;">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-300" style="background-color: rgba(255, 255, 255, 0.12); border: 1px solid rgba(252, 211, 77, 0.45);">
                            {{ $ejeClave }} • Marco Institucional TecNM
                        </span>
                    </div>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl font-sans">
                        {{ $ejeNombre }}
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-blue-100/90 tracking-wide uppercase">
                        Tecnológico Nacional de México • "Excelencia en Educación Tecnológica®"
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-200" style="background-color: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.35);">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Estructurado en BD
                    </span>
                </div>
            </div>
        </div>

        <!-- Grid de Submódulos Institucionales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($submodulos as $submodulo)
                <div class="relative flex flex-col justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-blue-400 dark:hover:border-blue-600">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center justify-center h-8 px-2.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-xs font-bold text-tecnm-blue border border-blue-200 dark:border-blue-900">
                                Submódulo {{ $submodulo->clave }}
                            </span>
                            @if ($submodulo->activo_mvp)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-0.5 text-xs font-semibold text-tecnm-green border border-emerald-200 dark:border-emerald-800">
                                    <x-heroicon-m-check-badge class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                    Funcional MVP
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-950/40 px-2.5 py-0.5 text-xs font-medium text-tecnm-gold border border-amber-200 dark:border-amber-800">
                                    Fase 2
                                </span>
                            @endif
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">
                            {{ $submodulo->nombre }}
                        </h3>
                        <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 line-clamp-3">
                            {{ $submodulo->descripcion ?? 'Módulo institucional catalogado conforme a las directrices oficiales del Tecnológico Nacional de México.' }}
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Estatus: {{ $submodulo->activo_mvp ? 'Captura Habilitada' : 'Estructura Lista' }}
                        </span>
                        @if ($submodulo->clave === '2.2')
                            <a href="{{ \App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource::getUrl('index') }}" 
                               class="inline-flex items-center text-xs font-semibold text-tecnm-blue hover:underline">
                                Ir al módulo &rarr;
                            </a>
                        @elseif ($submodulo->clave === '3.1')
                            <a href="{{ \App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource::getUrl('index') }}" 
                               class="inline-flex items-center text-xs font-semibold text-tecnm-blue hover:underline">
                                Ir al módulo &rarr;
                            </a>
                        @else
                            <span class="text-xs text-gray-400 italic">Programado</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-sm text-gray-500">No se encontraron submódulos registrados para este eje.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
