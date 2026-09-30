<x-filament-widgets::widget>
    <div class="space-y-3 font-sans">
        {{-- Cinta Superior Compacta (Status Ribbon) --}}
        <div class="tecnm-banner relative overflow-hidden rounded-xl px-4 py-2.5 text-white shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2" style="background: linear-gradient(135deg, #1B396A 0%, #162e54 60%, #1B396A 100%) !important; border-bottom: 2px solid #A57F2C !important;">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 font-bold uppercase tracking-wider text-amber-300 text-[11px]" style="background-color: rgba(255, 255, 255, 0.12); border: 1px solid rgba(252, 211, 77, 0.45);">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    {{ $periodo ? $periodo->nombre_completo : 'Corte 2026' }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-medium text-blue-100" style="background-color: rgba(0, 0, 0, 0.28); border: 1px solid rgba(255, 255, 255, 0.12);">
                    <x-heroicon-m-clock class="w-3 h-3 text-blue-200" />
                    Cierre: {{ $periodo && $periodo->fecha_limite ? \Carbon\Carbon::parse($periodo->fecha_limite)->format('d/m/Y') : 'Por definir' }}
                    @if ($diasRestantes > 0)
                        <strong class="text-amber-300 font-bold ml-1">({{ $diasRestantes }} días)</strong>
                    @else
                        <strong class="text-rose-300 font-bold ml-1">(Concluido)</strong>
                    @endif
                </span>
                <span class="hidden md:inline text-[11px] text-blue-200/80 font-semibold tracking-wider uppercase pl-2">
                    Red Nacional • 263 Institutos Tecnológicos
                </span>
            </div>

            <div class="flex items-center gap-2 text-xs">
                <span class="text-blue-200 text-[11px] uppercase font-semibold">Cumplimiento Global:</span>
                <span class="text-base font-black text-amber-300 tabular-nums">{{ $porcentajeCumplimiento }}%</span>
            </div>
        </div>

        {{-- 4 Tarjetas de Cabina en Fila Única (Garantizado con display: grid en línea y clases CSS) --}}
        <div class="atlas-cockpit-grid" style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; width: 100%;">
            
            {{-- KPI 1: Avance Institucional --}}
            <div class="atlas-cockpit-card">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Avance Institucional
                    </span>
                    <span class="text-[11px] font-black px-1.5 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300">
                        {{ $porcentajeCumplimiento }}%
                    </span>
                </div>
                <div class="my-1.5 flex items-baseline gap-1.5">
                    <span class="text-xl font-black text-gray-900 dark:text-white tabular-nums">{{ $plantelesPublicados }}</span>
                    <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">/ {{ $metaPlanteles }} planteles</span>
                </div>
                <div class="w-full h-1.5 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 flex" title="Verde: Publicados, Ámbar: Borrador, Rosa: Sin Carga">
                    <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ $metaPlanteles > 0 ? ($plantelesPublicados / $metaPlanteles) * 100 : 0 }}%"></div>
                    <div class="bg-amber-400 h-full transition-all duration-500" style="width: {{ $metaPlanteles > 0 ? ($plantelesBorrador / $metaPlanteles) * 100 : 0 }}%"></div>
                    <div class="bg-rose-400 h-full transition-all duration-500" style="width: {{ $metaPlanteles > 0 ? ($plantelesSinReporte / $metaPlanteles) * 100 : 0 }}%"></div>
                </div>
            </div>

            {{-- KPI 2: Semáforo de Captura --}}
            <div class="atlas-cockpit-card">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Estatus de Captura
                    </span>
                    <span class="text-[10px] text-gray-400 font-medium">Meta: {{ $metaPlanteles }}</span>
                </div>
                <div class="atlas-semaforo-grid my-auto" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.35rem; width: 100%;">
                    <div class="p-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800/60 text-center">
                        <div class="text-sm font-black text-emerald-800 dark:text-emerald-300 tabular-nums leading-tight">{{ $plantelesPublicados }}</div>
                        <div class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">Al Día</div>
                    </div>
                    <div class="p-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-800/60 text-center">
                        <div class="text-sm font-black text-amber-800 dark:text-amber-300 tabular-nums leading-tight">{{ $plantelesBorrador }}</div>
                        <div class="text-[9px] font-bold text-amber-700 dark:text-amber-400 uppercase">Carga</div>
                    </div>
                    <div class="p-1 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-800/60 text-center">
                        <div class="text-sm font-black text-rose-800 dark:text-rose-300 tabular-nums leading-tight">{{ $plantelesSinReporte }}</div>
                        <div class="text-[9px] font-bold text-rose-700 dark:text-rose-400 uppercase">Rezago</div>
                    </div>
                </div>
            </div>

            {{-- KPI 3: Población Impactada --}}
            <div class="atlas-cockpit-card">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Población Impactada
                    </span>
                    <x-heroicon-o-user-group class="w-3.5 h-3.5 text-tecnm-blue" />
                </div>
                <div class="my-1.5">
                    <span class="text-xl font-black text-tecnm-blue tabular-nums">{{ number_format($totalGeneral) }}</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 ml-1">personas</span>
                </div>
                <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400 font-semibold border-t border-gray-100 dark:border-gray-800/80 pt-1">
                    <span>{{ number_format($totalEstudiantes) }} Alumnos</span>
                    <span>•</span>
                    <span>{{ number_format($totalDocentes) }} Docentes</span>
                </div>
            </div>

            {{-- KPI 4: Equidad Institucional --}}
            <div class="atlas-cockpit-card">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Equidad Institucional
                    </span>
                    <span class="text-[10px] font-bold text-purple-700 dark:text-purple-300">
                        {{ $porcentajeMujeres }}% M / {{ $porcentajeHombres }}% H
                    </span>
                </div>
                <div class="my-2 flex w-full h-1.5 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                    <div class="bg-purple-600 dark:bg-purple-500 h-full transition-all duration-300" style="width: {{ $porcentajeMujeres }}%"></div>
                    <div class="bg-blue-600 dark:bg-blue-500 h-full transition-all duration-300" style="width: {{ $porcentajeHombres }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400 font-medium">
                    <span>{{ number_format($totalMujeres) }} Mujeres</span>
                    <span>•</span>
                    <span>{{ number_format($totalHombres) }} Hombres</span>
                </div>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
