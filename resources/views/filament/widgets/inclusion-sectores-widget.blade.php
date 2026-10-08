<x-filament-widgets::widget>
    <div class="space-y-4 font-sans">
        {{-- Encabezado del Bloque Analítico --}}
        <div class="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#1E5B4F] dark:text-emerald-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" /></svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
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
