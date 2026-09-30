<x-filament-widgets::widget>
    <style>
        .tecnm-leaflet-tooltip {
            background: rgba(27, 57, 106, 0.96) !important;
            border: 1.5px solid #A57F2C !important;
            color: #FFFFFF !important;
            font-family: 'Noto Sans', sans-serif !important;
            border-radius: 10px !important;
            padding: 8px 12px !important;
            box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.6) !important;
        }
        .tecnm-leaflet-tooltip::before {
            border-top-color: #A57F2C !important;
        }
        html.dark .leaflet-container {
            background-color: #0b1329 !important;
            font-family: inherit !important;
        }
        html:not(.dark) .leaflet-container {
            background-color: #E2E8F0 !important;
            font-family: inherit !important;
        }
    </style>

    <div class="space-y-4">
        {{-- Barra de Titulo y Filtros Superiores --}}
        <div class="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-tecnm-blue dark:text-blue-300">
                        <x-heroicon-o-map class="w-5 h-5 text-tecnm-blue" />
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Cartografia y Despliegue Territorial
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold text-white tracking-wide" style="background-color: #1B396A;">
                                32 ENTIDADES
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Avance geoespacial de reporte y cobertura institucional en la Republica Mexicana
                        </p>
                    </div>
                </div>
            </div>

            {{-- Filtros Interactivos --}}
            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Selector de Submodulo --}}
                <div class="flex items-center gap-1.5 text-xs">
                    <label class="text-gray-600 dark:text-gray-400 font-medium">Submodulo:</label>
                    <select
                        wire:model.live="submoduloFiltro"
                        class="text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-[#1B396A] focus:ring-[#1B396A] py-1.5 px-2.5 font-medium transition shadow-xs"
                    >
                        <option value="todos">Todos los Submodulos Activos</option>
                        @foreach ($submodulos as $sub)
                            <option value="{{ $sub->clave }}">{{ $sub->clave }} {{ $sub->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Selector de Sostenimiento --}}
                <div class="flex items-center gap-1.5 text-xs">
                    <label class="text-gray-600 dark:text-gray-400 font-medium">Sostenimiento:</label>
                    <select
                        wire:model.live="sostenimientoFiltro"
                        class="text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-[#1B396A] focus:ring-[#1B396A] py-1.5 px-2.5 font-medium transition shadow-xs"
                    >
                        <option value="todos">Todos (Federales y Descentralizados)</option>
                        <option value="FEDERAL">Solo Federales</option>
                        <option value="DESCENTRALIZADO">Solo Descentralizados</option>
                    </select>
                </div>

                @if ($selectedEstadoClave)
                    <button
                        wire:click="resetSeleccion"
                        type="button"
                        class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition"
                        title="Restablecer vista a toda la Republica"
                    >
                        <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                        Vista Nacional
                    </button>
                @endif
            </div>
        </div>

        {{-- Contenedor Principal: Disposicion Flexible Lado a Lado --}}
        <div style="display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: stretch; width: 100%;">
            {{-- Columna del Mapa con Leaflet (Abarca ~65% en pantallas amplias) --}}
            <div
                style="flex: 2 1 600px; min-width: 320px; position: relative; min-height: 540px;"
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs p-4 flex flex-col justify-between"
                x-data="{
                    map: null,
                    geojsonLayer: null,
                    tileLayer: null,
                    loading: true,
                    seriesData: {{ $mapSeriesJson }},
                    selectedClave: @entangle('selectedEstadoClave'),
                    dataMap: {},

                    init() {
                        try {
                            sessionStorage.removeItem('atlas_mexico_geojson_v1');
                        } catch (e) {}

                        this.seriesData.forEach(d => {
                            this.dataMap[d.clave_inegi] = d;
                        });

                        const comprobarL = () => {
                            if (typeof window.L !== 'undefined') {
                                this.$nextTick(() => {
                                    this.crearMapa();
                                    this.iniciarObservadorTema();
                                });
                            } else {
                                setTimeout(comprobarL, 50);
                            }
                        };
                        comprobarL();
                    },

                    currentIsDark: null,

                    iniciarObservadorTema() {
                        const self = this;
                        window.addEventListener('theme-changed', () => {
                            self.actualizarCapaTiles();
                        });

                        const observer = new MutationObserver(() => {
                            self.actualizarCapaTiles();
                        });
                        observer.observe(document.documentElement, {
                            attributes: true,
                            attributeFilter: ['class']
                        });
                    },

                    actualizarCapaTiles() {
                        if (!this.map || !this.tileLayer) return;
                        const isDark = document.documentElement.classList.contains('dark');
                        if (this.currentIsDark === isDark) return;
                        this.currentIsDark = isDark;

                        const tileUrl = isDark
                            ? 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}'
                            : 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}';

                        this.tileLayer.setUrl(tileUrl);
                        if (this.geojsonLayer) {
                            const self = this;
                            this.geojsonLayer.setStyle((feature) => self.obtenerEstiloFeature(feature));
                        }
                    },

                    obtenerColor(val) {
                        const isDark = document.documentElement.classList.contains('dark');
                        return val > 20 ? '#1B396A' :
                               val > 10 ? '#255294' :
                               val > 5  ? '#3B82F6' :
                               val > 0  ? '#60A5FA' :
                                          (isDark ? '#334155' : '#CBD5E1');
                    },

                    obtenerEstiloFeature(feature) {
                        const clave = feature.properties.clave_inegi;
                        const item = this.dataMap[clave] || {};
                        const isSelected = this.selectedClave === clave;

                        return {
                            fillColor: this.obtenerColor(item.total_planteles || 0),
                            weight: isSelected ? 3.5 : 1.2,
                            opacity: 1,
                            color: isSelected ? '#1E5B4F' : '#A57F2C',
                            dashArray: isSelected ? '' : '2',
                            fillOpacity: isSelected ? 0.85 : 0.65
                        };
                    },

                    async crearMapa() {
                        const dom = this.$refs.leafletMapDom;
                        if (!dom) return;

                        if (this.map) {
                            this.map.remove();
                        }

                        const isDark = document.documentElement.classList.contains('dark');
                        this.currentIsDark = isDark;
                        const tileUrl = isDark
                            ? 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}'
                            : 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}';

                        this.map = window.L.map(dom, {
                            center: [23.6345, -102.5528],
                            zoom: 5,
                            minZoom: 4,
                            maxZoom: 9,
                            zoomControl: false,
                            attributionControl: false
                        });

                        this.tileLayer = window.L.tileLayer(tileUrl, {
                            maxZoom: 16,
                            attribution: 'Esri, HERE, Garmin, (c) OpenStreetMap'
                        }).addTo(this.map);

                        try {
                            if (!window.__mexicoGeoJsonData || !window.__mexicoGeoJsonData.features) {
                                const res = await fetch('{{ asset("js/maps/mexico.json") }}');
                                window.__mexicoGeoJsonData = await res.json();
                            }

                            const self = this;

                            this.geojsonLayer = window.L.geoJSON(window.__mexicoGeoJsonData, {
                                style: (feature) => self.obtenerEstiloFeature(feature),
                                onEachFeature: function (feature, layer) {
                                    const clave = feature.properties.clave_inegi;
                                    const item = self.dataMap[clave] || {
                                        nombre_oficial: feature.properties.name,
                                        total_planteles: 0,
                                        federales: 0,
                                        descentralizados: 0,
                                        total_participantes: 0,
                                        porcentaje_cumplimiento: 0
                                    };

                                    const tooltipHtml = `
                                        <div style='min-width: 170px;'>
                                            <div style='display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.25); padding-bottom:3px; margin-bottom:5px;'>
                                                <strong style='font-size:13px; color:#FFFFFF;'>${item.nombre_oficial || feature.properties.name}</strong>
                                                <span style='background:rgba(165,127,44,0.4); color:#E8D39E; font-size:10px; font-weight:bold; padding:1px 4px; border-radius:3px;'>INEGI ${clave}</span>
                                            </div>
                                            <div style='font-size:11px; line-height:1.5; color:#E2E8F0;'>
                                                <div>Planteles: ${item.total_planteles} <span style='font-size:10px; color:#A4C3E8;'>(Fed: ${item.federales} | Desc: ${item.descentralizados})</span></div>
                                                <div>Poblacion: ${Number(item.total_participantes).toLocaleString()}</div>
                                                <div>Cumplimiento: ${item.porcentaje_cumplimiento}%</div>
                                            </div>
                                            <div style='margin-top:5px; font-size:10px; color:#A57F2C; font-weight:bold; text-align:right;'>
                                                Clic para ver detalle
                                            </div>
                                        </div>
                                    `;

                                    layer.bindTooltip(tooltipHtml, {
                                        sticky: true,
                                        className: 'tecnm-leaflet-tooltip',
                                        direction: 'auto'
                                    });

                                    layer.on({
                                        mouseover: function (e) {
                                            const l = e.target;
                                            l.setStyle({
                                                weight: 2.8,
                                                color: '#FFFFFF',
                                                dashArray: '',
                                                fillOpacity: 0.9
                                            });
                                            if (!window.L.Browser.ie && !window.L.Browser.opera && !window.L.Browser.edge) {
                                                l.bringToFront();
                                            }
                                        },
                                        mouseout: function (e) {
                                            self.geojsonLayer.resetStyle(e.target);
                                        },
                                        click: function (e) {
                                            self.$wire.selectEstado(clave);
                                            self.map.fitBounds(e.target.getBounds(), { padding: [30, 30], maxZoom: 7 });
                                        }
                                    });
                                }
                            }).addTo(this.map);

                            this.map.fitBounds(this.geojsonLayer.getBounds(), { padding: [15, 15] });
                            this.loading = false;

                            this.$watch('selectedClave', (newClave) => {
                                if (!newClave) {
                                    self.resetView();
                                    return;
                                }
                                self.geojsonLayer.eachLayer((layer) => {
                                    if (layer.feature && layer.feature.properties.clave_inegi === newClave) {
                                        layer.setStyle({
                                            weight: 3.5,
                                            color: '#1E5B4F',
                                            fillOpacity: 0.9
                                        });
                                        self.map.fitBounds(layer.getBounds(), { padding: [40, 40], maxZoom: 7 });
                                    } else {
                                        self.geojsonLayer.resetStyle(layer);
                                    }
                                });
                            });

                        } catch (err) {
                            console.error('Error cargando capa GeoJSON en Leaflet:', err);
                            this.loading = false;
                        }

                        this.map.invalidateSize();
                        setTimeout(() => {
                            if (this.map) {
                                this.map.invalidateSize();
                            }
                        }, 250);
                    },

                    zoomIn() {
                        if (this.map) this.map.zoomIn();
                    },

                    zoomOut() {
                        if (this.map) this.map.zoomOut();
                    },

                    resetView() {
                        if (this.map && this.geojsonLayer) {
                            this.map.fitBounds(this.geojsonLayer.getBounds(), { padding: [15, 15] });
                            this.geojsonLayer.eachLayer((layer) => {
                                this.geojsonLayer.resetStyle(layer);
                            });
                        }
                    }
                }"
            >
                {{-- Controles flotantes de Zoom y Reset --}}
                <div style="position: absolute; top: 1.5rem; right: 1.5rem; z-index: 1000; display: flex; flex-direction: column; gap: 0.375rem;" class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-xs p-1 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        @click="zoomIn()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold transition text-base"
                        title="Acercar mapa"
                    >
                        +
                    </button>
                    <button
                        type="button"
                        @click="zoomOut()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold transition text-base"
                        title="Alejar mapa"
                    >
                        -
                    </button>
                    <button
                        type="button"
                        @click="resetView()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition text-xs"
                        title="Centrar mapa en México"
                    >
                        <x-heroicon-m-arrows-pointing-in class="w-4 h-4" />
                    </button>
                </div>

                {{-- Indicador de carga --}}
                <div
                    x-show="loading"
                    style="position: absolute; inset: 0; z-index: 1001; display: flex; flex-direction: column; align-items: center; justify-content: center;"
                    class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xs transition"
                >
                    <div class="w-8 h-8 border-3 border-[#1B396A] border-t-transparent rounded-full animate-spin"></div>
                    <span class="mt-2 text-xs font-semibold text-gray-600 dark:text-gray-300">Cargando cartografia nacional...</span>
                </div>

                {{-- Contenedor del Mapa Leaflet con altura obligatoria (wire:ignore para protegerlo de morphdom) --}}
                <div
                    wire:ignore
                    x-ref="leafletMapDom"
                    style="width: 100%; height: 500px; min-height: 500px; border-radius: 12px; overflow: hidden; z-index: 1; position: relative;"
                ></div>

                {{-- Leyenda inferior descriptiva --}}
                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 mt-2">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-xs bg-[#1B396A] border border-[#A57F2C]"></span>
                            <span>Mayor presencia (>20)</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-xs bg-[#255294] border border-[#A57F2C]"></span>
                            <span>Presencia media (10-20)</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-xs bg-[#3B82F6] border border-[#A57F2C]"></span>
                            <span>Presencia base (1-10)</span>
                        </span>
                    </div>
                    <div>
                        <span>Motor Leaflet • Capa ESRI ArcGIS Dark Canvas • TecNM 2026</span>
                    </div>
                </div>
            </div>

            {{-- Columna de Detalle Territorial (Abarca ~35% en pantallas amplias) --}}
            <div style="flex: 1 1 320px; min-width: 280px;" class="space-y-4">
                @if ($estadoSeleccionado)
                    {{-- Tarjeta de Estado Seleccionado (Ficha Ejecutiva) --}}
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border-2 border-blue-900 dark:border-blue-700 shadow-md p-5 relative overflow-hidden transition-all animate-fadeIn">
                        {{-- Borde superior de acento con gradiente institucional --}}
                        <div class="absolute top-0 left-0 right-0 h-1.5" style="background: linear-gradient(90deg, #1B396A 0%, #A57F2C 50%, #1E5B4F 100%);"></div>

                        {{-- Encabezado del Estado --}}
                        <div class="flex items-start justify-between gap-2 mb-4 pt-1">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[11px] px-2 py-0.5 rounded-md font-mono font-bold bg-blue-100 text-blue-900 dark:bg-blue-950/60 dark:text-blue-300">
                                        INEGI {{ $estadoSeleccionado['clave_inegi'] }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                        {{ $estadoSeleccionado['abreviatura'] }}
                                    </span>
                                </div>
                                <h4 class="text-lg font-black text-gray-900 dark:text-white mt-1 leading-snug">
                                    {{ $estadoSeleccionado['nombre_oficial'] }}
                                </h4>
                            </div>
                            <button
                                wire:click="resetSeleccion"
                                type="button"
                                class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition"
                                title="Cerrar detalle y volver al mapa nacional"
                            >
                                <x-heroicon-m-x-mark class="w-5 h-5" />
                            </button>
                        </div>

                        {{-- Semáforo Local de Cumplimiento --}}
                        <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl mb-3.5 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-xs mb-2">
                                <span class="font-bold text-gray-700 dark:text-gray-300">Cumplimiento Estatal</span>
                                <span class="font-black text-tecnm-blue text-sm">
                                    {{ $estadoSeleccionado['porcentaje_cumplimiento'] }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden flex shadow-inner">
                                <div class="bg-[#1E5B4F] h-2.5 transition-all duration-500" style="width: {{ $estadoSeleccionado['total_planteles'] > 0 ? ($estadoSeleccionado['publicados'] / $estadoSeleccionado['total_planteles']) * 100 : 0 }}%" title="Publicados: {{ $estadoSeleccionado['publicados'] }}"></div>
                                <div class="bg-[#A57F2C] h-2.5 transition-all duration-500" style="width: {{ $estadoSeleccionado['total_planteles'] > 0 ? ($estadoSeleccionado['borradores'] / $estadoSeleccionado['total_planteles']) * 100 : 0 }}%" title="Borrador: {{ $estadoSeleccionado['borradores'] }}"></div>
                                <div class="bg-[#9B2247] h-2.5 transition-all duration-500" style="width: {{ $estadoSeleccionado['total_planteles'] > 0 ? ($estadoSeleccionado['sin_reporte'] / $estadoSeleccionado['total_planteles']) * 100 : 0 }}%" title="Sin Carga: {{ $estadoSeleccionado['sin_reporte'] }}"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 mt-2.5 text-center">
                                <div class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                                    <div class="text-sm font-black text-emerald-950 dark:text-emerald-300">{{ $estadoSeleccionado['publicados'] }}</div>
                                    <div class="text-[9px] font-bold text-emerald-800 dark:text-emerald-300 uppercase">Al Día</div>
                                </div>
                                <div class="p-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                                    <div class="text-sm font-black text-amber-950 dark:text-amber-300">{{ $estadoSeleccionado['borradores'] }}</div>
                                    <div class="text-[9px] font-bold text-amber-800 dark:text-amber-300 uppercase">Carga</div>
                                </div>
                                <div class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800">
                                    <div class="text-sm font-black text-rose-950 dark:text-rose-300">{{ $estadoSeleccionado['sin_reporte'] }}</div>
                                    <div class="text-[9px] font-bold text-rose-800 dark:text-rose-300 uppercase">Rezago</div>
                                </div>
                            </div>
                        </div>

                        {{-- Desglose de Sostenimiento: Federales vs Descentralizados --}}
                        <div class="mb-3.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-2">
                                Red de Planteles ({{ $estadoSeleccionado['total_planteles'] }} en Total)
                            </span>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40">
                                    <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase block">Federales</span>
                                    <span class="text-lg font-black text-gray-900 dark:text-white tabular-nums">{{ $estadoSeleccionado['federales'] }}</span>
                                </div>
                                <div class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40">
                                    <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase block">Descentralizados</span>
                                    <span class="text-lg font-black text-gray-900 dark:text-white tabular-nums">{{ $estadoSeleccionado['descentralizados'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Desglose de Población Participante --}}
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/80 mb-4">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-gray-600 dark:text-gray-400">Población Reportada:</span>
                                <span class="font-black text-tecnm-blue text-sm">
                                    {{ number_format($estadoSeleccionado['total_participantes']) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-1.5 border-t border-gray-100 dark:border-gray-800 font-medium">
                                <span>{{ number_format($estadoSeleccionado['total_estudiantes'] ?? 0) }} Alumnos</span>
                                <span>•</span>
                                <span>{{ number_format($estadoSeleccionado['total_docentes'] ?? 0) }} Docentes</span>
                            </div>
                        </div>

                        <div class="pt-1">
                            <button
                                wire:click="resetSeleccion"
                                type="button"
                                class="w-full py-2 px-3 rounded-xl text-xs font-bold text-center bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition flex items-center justify-center gap-1.5"
                            >
                                <x-heroicon-m-arrows-pointing-in class="w-4 h-4" />
                                Volver al Resumen Nacional
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Vista Consolidada Nacional (Inspector Ejecutivo) --}}
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xs p-5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-tecnm-blue">
                                    <x-heroicon-o-chart-bar class="w-4 h-4 text-tecnm-blue" />
                                </span>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Monitor de Entidades
                                </h4>
                            </div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                32 ESTADOS
                            </span>
                        </div>

                        {{-- Ranking: Top 5 Presencia TecNM --}}
                        <div>
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider block mb-2">
                                Mayor Presencia de Planteles
                            </span>
                            <div class="space-y-1.5">
                                @foreach ($topEntidades as $ent)
                                    <button
                                        wire:click="selectEstado('{{ $ent['clave_inegi'] }}')"
                                        type="button"
                                        class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-blue-50 dark:hover:bg-gray-800/80 transition text-left group border border-transparent hover:border-blue-200 dark:hover:border-blue-900"
                                        title="Haz clic para enfocar {{ $ent['nombre_oficial'] }}"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-5 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-800 text-[10px] font-mono font-bold text-gray-600 dark:text-gray-400 group-hover:bg-[#1B396A] group-hover:text-white transition">
                                                {{ $ent['clave_inegi'] }}
                                            </span>
                                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 group-hover:text-tecnm-blue transition truncate max-w-[150px]">
                                                {{ $ent['nombre_oficial'] }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-tecnm-blue tabular-nums">
                                                {{ $ent['total_planteles'] }}
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-md font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                                                {{ $ent['porcentaje_cumplimiento'] }}%
                                            </span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Sección de Atención Prioritaria (Mayor Rezago) --}}
                        @if ($estadosRezagados->isNotEmpty())
                            <div class="border-t border-gray-100 dark:border-gray-800 pt-3">
                                <span class="text-[11px] font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider block mb-2 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    Atención Prioritaria (Rezago)
                                </span>
                                <div class="space-y-1.5">
                                    @foreach ($estadosRezagados as $rez)
                                        <button
                                            wire:click="selectEstado('{{ $rez['clave_inegi'] }}')"
                                            type="button"
                                            class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/30 transition text-left group border border-transparent hover:border-rose-200 dark:hover:border-rose-900"
                                            title="Ver detalle de rezago en {{ $rez['nombre_oficial'] }}"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span class="w-5 h-5 flex items-center justify-center rounded-md bg-rose-100 dark:bg-rose-900/40 text-[10px] font-mono font-bold text-rose-800 dark:text-rose-300">
                                                    {{ $rez['clave_inegi'] }}
                                                </span>
                                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 group-hover:text-rose-700 dark:group-hover:text-rose-300 transition truncate max-w-[150px]">
                                                    {{ $rez['nombre_oficial'] }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200">
                                                {{ $rez['sin_reporte'] }} pendientes
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
