<x-filament-widgets::widget>
    <div class="space-y-4 font-sans">
        {{-- Encabezado del Bloque Analítico --}}
        <div class="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#1E5B4F] dark:text-emerald-300">
                    <x-heroicon-o-chart-pie class="w-5 h-5" />
                </span>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Analítica Estratégica: Paridad, Vocación y Formación Integral
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold text-white tracking-wider" style="background-color: #1E5B4F;">
                            INTELIGENCIA NACIONAL
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Indicadores de impacto transversal sin saturación de texto
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 font-semibold">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Corte {{ $periodo?->nombre_completo ?? 'Trimestral 2026' }}</span>
            </div>
        </div>

        {{-- Cuadrícula de 3 Gráficas Interactivas con ECharts --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

            {{-- Gráfica 1: Donut de Paridad y Estructura Demográfica --}}
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs p-4 flex flex-col justify-between"
                x-data="{
                    chart: null,
                    data: {{ $paridadDonutJson }},
                    total: {{ $totalGeneral > 0 ? $totalGeneral : 100 }},
                    init() {
                        const checkEcharts = () => {
                            if (typeof window.echarts !== 'undefined' && this.$refs.donutDom) {
                                this.chart = echarts.init(this.$refs.donutDom);
                                this.render();
                                window.addEventListener('resize', () => this.chart && this.chart.resize());
                                const observer = new MutationObserver(() => this.render());
                                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                            } else {
                                setTimeout(checkEcharts, 60);
                            }
                        };
                        this.$nextTick(checkEcharts);
                    },
                    render() {
                        if (!this.chart) return;
                        const isDark = document.documentElement.classList.contains('dark');
                        const option = {
                            tooltip: {
                                trigger: 'item',
                                backgroundColor: isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                borderColor: isDark ? '#334155' : '#E2E8F0',
                                textStyle: { color: isDark ? '#F8FAFC' : '#0F172A', fontSize: 12 },
                                formatter: '{b}: <b>{c}</b> ({d}%)'
                            },
                            legend: {
                                bottom: '0%',
                                left: 'center',
                                textStyle: { color: isDark ? '#94A3B8' : '#475569', fontSize: 10, fontWeight: 600 },
                                itemWidth: 10,
                                itemHeight: 10,
                                itemGap: 12
                            },
                            series: [{
                                name: 'Población',
                                type: 'pie',
                                radius: ['48%', '75%'],
                                center: ['50%', '42%'],
                                avoidLabelOverlap: false,
                                itemStyle: {
                                    borderRadius: 6,
                                    borderColor: isDark ? '#0f172a' : '#ffffff',
                                    borderWidth: 2
                                },
                                label: { show: false },
                                emphasis: {
                                    label: {
                                        show: true,
                                        fontSize: 12,
                                        fontWeight: 'bold',
                                        color: isDark ? '#FFFFFF' : '#1E293B'
                                    }
                                },
                                data: this.data
                            }]
                        };
                        this.chart.setOption(option);
                    }
                }"
            >
                <div>
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="p-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400">
                                <x-heroicon-o-users class="w-4 h-4" />
                            </span>
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                Paridad y Demografía
                            </h4>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300">
                            {{ $pctEstudiantesMujeres }}% M / {{ $pctEstudiantesHombres }}% H
                        </span>
                    </div>

                    {{-- Contenedor del Gráfico ECharts Donut --}}
                    <div wire:ignore x-ref="donutDom" style="width: 100%; height: 210px;"></div>
                </div>

                {{-- Micro-chips inferiores --}}
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px] font-medium text-gray-600 dark:text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: #A57F2C;"></span>
                        {{ number_format($estudiantesMujeres + $docentesMujeres) }} Mujeres
                    </span>
                    <span class="flex items-center justify-end gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: #1B396A;"></span>
                        {{ number_format($estudiantesHombres + $docentesHombres) }} Hombres
                    </span>
                </div>
            </div>

            {{-- Gráfica 2: Barras Horizontales de Sectores Estratégicos MTE (2.2) --}}
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs p-4 flex flex-col justify-between"
                x-data="{
                    chart: null,
                    data: {{ $sectoresJson }},
                    init() {
                        const checkEcharts = () => {
                            if (typeof window.echarts !== 'undefined' && this.$refs.sectoresDom) {
                                this.chart = echarts.init(this.$refs.sectoresDom);
                                this.render();
                                window.addEventListener('resize', () => this.chart && this.chart.resize());
                                const observer = new MutationObserver(() => this.render());
                                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                            } else {
                                setTimeout(checkEcharts, 60);
                            }
                        };
                        this.$nextTick(checkEcharts);
                    },
                    render() {
                        if (!this.chart) return;
                        const isDark = document.documentElement.classList.contains('dark');
                        const nombres = this.data.map(d => {
                            if (d.nombre.includes('Tecnologías')) return 'TIC y Software';
                            if (d.nombre.includes('Agroindustria')) return 'Agroindustria';
                            if (d.nombre.includes('Energía')) return 'Energía y Sost.';
                            if (d.nombre.includes('Manufactura')) return 'Manufactura';
                            if (d.nombre.includes('Salud')) return 'Biotecnología';
                            return d.nombre.substring(0, 16);
                        }).reverse();
                        const valores = this.data.map(d => d.porcentaje).reverse();

                        const option = {
                            tooltip: {
                                trigger: 'axis',
                                axisPointer: { type: 'shadow' },
                                backgroundColor: isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                borderColor: isDark ? '#334155' : '#E2E8F0',
                                textStyle: { color: isDark ? '#F8FAFC' : '#0F172A', fontSize: 11 },
                                formatter: (params) => {
                                    const p = params[0];
                                    return `<b>${p.name}</b>: ${p.value}% de proyectos`;
                                }
                            },
                            grid: {
                                top: '8%',
                                left: '3%',
                                right: '12%',
                                bottom: '5%',
                                containLabel: true
                            },
                            xAxis: {
                                type: 'value',
                                max: 40,
                                splitLine: { lineStyle: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)' } },
                                axisLabel: { formatter: '{value}%', color: isDark ? '#94A3B8' : '#64748B', fontSize: 10 }
                            },
                            yAxis: {
                                type: 'category',
                                data: nombres,
                                axisLine: { show: false },
                                axisTick: { show: false },
                                axisLabel: { color: isDark ? '#CBD5E1' : '#334155', fontSize: 11, fontWeight: 500 }
                            },
                            series: [{
                                name: 'Porcentaje',
                                type: 'bar',
                                barWidth: '55%',
                                data: valores,
                                itemStyle: {
                                    borderRadius: [0, 6, 6, 0],
                                    color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                                        { offset: 0, color: '#1B396A' },
                                        { offset: 1, color: '#3B82F6' }
                                    ])
                                },
                                label: {
                                    show: true,
                                    position: 'right',
                                    formatter: '{c}%',
                                    fontSize: 10,
                                    fontWeight: 'bold',
                                    color: isDark ? '#93C5FD' : '#1D4ED8'
                                }
                            }]
                        };
                        this.chart.setOption(option);
                    }
                }"
            >
                <div>
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="p-1 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-tecnm-blue">
                                <x-heroicon-o-light-bulb class="w-4 h-4" />
                            </span>
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                Sectores Estratégicos (MTE 2.2)
                            </h4>
                        </div>
                        <span class="text-[10px] font-bold text-blue-700 dark:text-blue-300">
                            Proyectos
                        </span>
                    </div>

                    {{-- Contenedor del Gráfico ECharts Barras Horizontales --}}
                    <div wire:ignore x-ref="sectoresDom" style="width: 100%; height: 210px;"></div>
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-between">
                    <span>Vocación tecnológica regional</span>
                    <span class="font-bold text-tecnm-blue">5 Sectores Clave</span>
                </div>
            </div>

            {{-- Gráfica 3: Barras de Modalidades COMEXTRAS (3.1) --}}
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs p-4 flex flex-col justify-between"
                x-data="{
                    chart: null,
                    data: {{ $modalidadesJson }},
                    init() {
                        const checkEcharts = () => {
                            if (typeof window.echarts !== 'undefined' && this.$refs.modalidadesDom) {
                                this.chart = echarts.init(this.$refs.modalidadesDom);
                                this.render();
                                window.addEventListener('resize', () => this.chart && this.chart.resize());
                                const observer = new MutationObserver(() => this.render());
                                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                            } else {
                                setTimeout(checkEcharts, 60);
                            }
                        };
                        this.$nextTick(checkEcharts);
                    },
                    render() {
                        if (!this.chart) return;
                        const isDark = document.documentElement.classList.contains('dark');
                        const nombres = this.data.map(d => {
                            if (d.nombre.includes('Deportiva')) return 'Deportiva';
                            if (d.nombre.includes('Cultural')) return 'Cultural';
                            if (d.nombre.includes('Cívica')) return 'Cívica';
                            if (d.nombre.includes('Internacional')) return 'Mov. Internacional';
                            if (d.nombre.includes('Nacional')) return 'Mov. Nacional';
                            return d.nombre.substring(0, 16);
                        }).reverse();
                        const valores = this.data.map(d => d.porcentaje).reverse();

                        const option = {
                            tooltip: {
                                trigger: 'axis',
                                axisPointer: { type: 'shadow' },
                                backgroundColor: isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                borderColor: isDark ? '#334155' : '#E2E8F0',
                                textStyle: { color: isDark ? '#F8FAFC' : '#0F172A', fontSize: 11 },
                                formatter: (params) => {
                                    const p = params[0];
                                    return `<b>${p.name}</b>: ${p.value}% participación`;
                                }
                            },
                            grid: {
                                top: '8%',
                                left: '3%',
                                right: '12%',
                                bottom: '5%',
                                containLabel: true
                            },
                            xAxis: {
                                type: 'value',
                                max: 50,
                                splitLine: { lineStyle: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)' } },
                                axisLabel: { formatter: '{value}%', color: isDark ? '#94A3B8' : '#64748B', fontSize: 10 }
                            },
                            yAxis: {
                                type: 'category',
                                data: nombres,
                                axisLine: { show: false },
                                axisTick: { show: false },
                                axisLabel: { color: isDark ? '#CBD5E1' : '#334155', fontSize: 11, fontWeight: 500 }
                            },
                            series: [{
                                name: 'Participación',
                                type: 'bar',
                                barWidth: '55%',
                                data: valores,
                                itemStyle: {
                                    borderRadius: [0, 6, 6, 0],
                                    color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                                        { offset: 0, color: '#1E5B4F' },
                                        { offset: 1, color: '#10B981' }
                                    ])
                                },
                                label: {
                                    show: true,
                                    position: 'right',
                                    formatter: '{c}%',
                                    fontSize: 10,
                                    fontWeight: 'bold',
                                    color: isDark ? '#6EE7B7' : '#047857'
                                }
                            }]
                        };
                        this.chart.setOption(option);
                    }
                }"
            >
                <div>
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="p-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-[#1E5B4F] dark:text-emerald-300">
                                <x-heroicon-o-academic-cap class="w-4 h-4" />
                            </span>
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                Modalidades (COMEXTRAS 3.1)
                            </h4>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                            Participación
                        </span>
                    </div>

                    {{-- Contenedor del Gráfico ECharts Barras Horizontales --}}
                    <div wire:ignore x-ref="modalidadesDom" style="width: 100%; height: 210px;"></div>
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-between">
                    <span>Formación cívica y deportiva</span>
                    <span class="font-bold text-[#1E5B4F] dark:text-emerald-300">Impacto Integral</span>
                </div>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
