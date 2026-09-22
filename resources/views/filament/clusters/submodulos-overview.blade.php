<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Banner Institucional TecNM -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#1B396A] via-[#162e54] to-[#1B396A] p-6 text-white shadow-xl border-b-4 border-[#A57F2C]">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-amber-300 border border-amber-300/30">
                            {{ $ejeClave }} • Marco Institucional TecNM
                        </span>
                    </div>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl font-sans">
                        {{ $ejeNombre }}
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-blue-100/80 tracking-wide uppercase">
                        Tecnológico Nacional de México • "Excelencia en Educación Tecnológica®"
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500/20 px-3 py-1.5 text-xs font-semibold text-emerald-300 border border-emerald-500/30">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Estructurado en BD
                    </span>
                </div>
            </div>
        </div>

        <!-- Grid de Submódulos Institucionales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($submodulos as $submodulo)
                <div class="relative flex flex-col justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-[#1B396A]/50">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center justify-center h-8 px-2.5 rounded-lg bg-[#1B396A]/10 dark:bg-[#1B396A]/30 text-xs font-bold text-[#1B396A] dark:text-blue-300 border border-[#1B396A]/20">
                                Submódulo {{ $submodulo->clave }}
                            </span>
                            @if ($submodulo->activo_mvp)
                                <span class="inline-flex items-center rounded-full bg-[#1E5B4F]/10 dark:bg-[#1E5B4F]/30 px-2.5 py-0.5 text-xs font-semibold text-[#1E5B4F] dark:text-emerald-300 border border-[#1E5B4F]/30">
                                    ⭐ Funcional MVP
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-[#A57F2C]/10 dark:bg-[#A57F2C]/30 px-2.5 py-0.5 text-xs font-medium text-[#A57F2C] dark:text-amber-300 border border-[#A57F2C]/30">
                                    Fase 2
                                </span>
                            @endif
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">
                            {{ $submodulo->nombre }}
                        </h3>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-3">
                            {{ $submodulo->descripcion ?? 'Módulo institucional catalogado conforme a las directrices oficiales del Tecnológico Nacional de México.' }}
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-800/60 flex items-center justify-between">
                        <span class="text-[11px] text-gray-400">
                            Estatus: {{ $submodulo->activo_mvp ? 'Captura Habilitada' : 'Estructura Lista' }}
                        </span>
                        @if ($submodulo->clave === '2.2')
                            <a href="{{ \App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource::getUrl('index') }}" 
                               class="inline-flex items-center text-xs font-semibold text-[#1B396A] hover:text-blue-800 dark:text-blue-400">
                                Ir al módulo &rarr;
                            </a>
                        @elseif ($submodulo->clave === '3.1')
                            <a href="{{ \App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource::getUrl('index') }}" 
                               class="inline-flex items-center text-xs font-semibold text-[#1B396A] hover:text-blue-800 dark:text-blue-400">
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
