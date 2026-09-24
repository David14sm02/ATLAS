# Bitácora de Avances y Registro de Ingeniería: ATLAS TECNM

**Proyecto:** ATLAS TECNM (Plataforma de Indicadores Nacionales del TecNM)  
**Ubicación:** `d:\data\ATLAS TECM`  
**Responsable:** Fábrica de Software  
**Estatus General:** Tablero Directivo Nacional Concluido (3 Bloques Activos - Listo para Presentación)  

---

## Control de Versiones y Registro de Hitos

| Hito / Sprint | Fecha | Objetivo Principal | Estatus |
| :---: | :---: | :--- | :---: |
| **Hito 0** | 19/Sep/2026 | Arquitectura conceptual, catálogo oficial de 35 submódulos y diseño de BD | **COMPLETADO** |
| **Hito 1** | 19/Sep/2026 | Inicialización de Laravel 11, entorno local (Herd), panel Filament v3 y Git | **COMPLETADO** |
| **Hito 2** | 22/Sep/2026 | Ejecución de 10 Migraciones y 4 Seeders en Neon PostgreSQL | **COMPLETADO** |
| **Hito 3** | 22/Sep/2026 | 4 Clusters, Modelos Eloquent, Recursos MVP e Identidad TecNM Oficial | **COMPLETADO** |
| **Hito 4** | Pendiente | Ingesta asíncrona de archivos Excel con validación en segundo plano (*dry-run*) | *PENDIENTE (Por acordar con el equipo)* |
| **Hito 5** | 23/Sep/2026 | Tablero Directivo Nacional (Propuesta C - Storytelling UX en 3 Bloques) | **COMPLETADO** |

---

## Registro Detallado por Hito

### [Hito 0] - Definición de Arquitectura y Especificación de Datos
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Formalizar la identidad del proyecto, definir el universo de 4 ejes y 35 submódulos, acotar el alcance del MVP y diseñar la arquitectura técnica de persistencia.

---

### [Hito 1] - Inicialización del Proyecto e Instalación de Filament v3
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Montar la estructura limpia de Laravel 11 en la raíz `d:\data\ATLAS TECM` y preparar el panel Filament v3.

---

### [Hito 2] - Arquitectura Data Mart, Migraciones y Seeders Maestros
* **Fecha:** 22 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Ejecución y población exitosa del esquema relacional en Neon Serverless.

---

### [Hito 3] - Clusters Institucionales, Modelos, Recursos MVP e Identidad Gráfica Oficial
* **Fecha:** 22 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Construir la navegación institucional, recursos de captura del MVP y adopción estricta del Manual de Identidad Gráfica TecNM 2026.
* **Documentación Creada:** `docs/MANUAL_TECNICO_DESARROLLADORES.md` (8 módulos de onboarding y arquitectura).

---

### [Hito 4] - Ingesta Asíncrona de Archivos Excel (*Dry-Run*)
* **Estatus:** **PENDIENTE (Por revisar y validar con el equipo)**

---

### [Hito 5] - Tablero Directivo Nacional (Storytelling UX)
* **Fecha:** 23 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Construir el Dashboard principal de Nivel 1 (Dirección General del TecNM) bajo la **Propuesta C: Narrativa Ejecutiva por Bloques Temáticos**.

#### 1. Entregables y Archivos Creados:
1. `docs/diseno_tablero_directivo_nacional.md`:
   * Especificación funcional y tokens de diseño para los 3 bloques narrativos.
   * Análisis de compatibilidad de mapas vectoriales (Apache ECharts sobre SVG/Canvas nativo con claves INEGI `01` a `32`).
2. **Bloque 1: Pulso Nacional y Semáforo de Cumplimiento:**
   * `app/Filament/Widgets/PulsoNacionalWidget.php`: Lógica reactiva que calcula en tiempo real:
     * Días restantes del corte trimestral activo (Q3 2026).
     * Semáforo de los 263 planteles: Publicados (Verde `#1E5B4F`), En Borrador (Dorado `#A57F2C`) y Rezagados (Guinda `#9B2247`).
     * % de Cobertura Nacional y barra de avance animada.
     * Conteo consolidado de población participante (Docentes vs. Estudiantes) y barra de paridad de género.
   * `resources/views/filament/widgets/pulso-nacional-widget.blade.php`: Vista Blade con diseño institucional de alto impacto, degradado oficial Azul TecNM y badges de estado.
   * Registro en `AdminPanelProvider.php`.

3. **Bloque 2: Cartografía y Despliegue Territorial:**
   * `public/js/maps/mexico.json`: Cartografía vectorial oficial de las 32 entidades federativas optimizada con atributos `name` e `clave_inegi` (`01` a `32`).
   * `public/js/echarts.min.js`: Librería Apache ECharts v5.5.1 empaquetada localmente para garantizar independencia de CDN y carga instantánea.
   * `public/js/leaflet/leaflet.js` y `leaflet.css`: Motor cartográfico Leaflet v1.9.4 integrado localmente para renderizado geográfico real.
   * `public/js/maps/mexico.json`: Cartografía vectorial oficial de las 32 entidades federativas optimizada con atributos `name` e `clave_inegi` (`01` a `32`).
   * `app/Filament/Widgets/MapaRepublicaWidget.php`: Componente Livewire que gestiona:
     * Filtros cruzados dinámicos por Submódulo (Todos, 2.2 MTE, 3.1 COMEXTRAS) y Sostenimiento (Federal vs. Descentralizado).
     * Mapeo geográfico de las 32 entidades con agregación de planteles y registros de captura.
     * Selección interactiva de estado con actualización reactiva en tiempo real.
     * Respaldo de metas oficiales para los 263 institutos federales y descentralizados.
   * `resources/views/filament/widgets/mapa-republica-widget.blade.php`: Vista interactiva montada en **Leaflet.js** con capa base **ESRI ArcGIS Dark Canvas**, escala de calor en gradiente Azul TecNM (`#1B396A`), bordes dorados (`#A57F2C`), controles flotantes de zoom/recentrado, tooltip interactivo que sigue el cursor y panel lateral con semáforo local estatal y desglose de institutos.
   * Registro activo en `app/Providers/Filament/AdminPanelProvider.php`.

4. **Bloque 3: Inclusión, Paridad de Género y Sectores Estratégicos:**
   * `app/Filament/Widgets/InclusionSectoresWidget.php`: Componente Livewire analítico que consolida:
     * Métricas de paridad de género en la población estudiantil (% Mujeres vs. % Hombres).
     * Métricas de paridad de género en la plantilla docente (% Mujeres vs. % Hombres) y diagnóstico de brecha.
     * Distribución porcentual y conteo de proyectos en sectores estratégicos (MTE 2.2: TI y Software, Agroindustria, Energía, Aeroespacial, Salud).
     * Distribución de participantes en disciplinas formativas integrales (COMEXTRAS 3.1: Deporte, Cultura, Cívico, Movilidad Internacional y Nacional).
   * `resources/views/filament/widgets/inclusion-sectores-widget.blade.php`: Vista en cuadrícula de 3 columnas con barras de progreso bicolores institucionales, badges de ponderación y adaptación completa a modo claro y oscuro sin emojis.
   * Registro activo en `app/Providers/Filament/AdminPanelProvider.php`.

* **Estatus del Hito 5:** **CONCLUIDO EXITOSAMENTE**. Tablero Directivo Nacional 100% operativo con la narrativa ejecutiva de los 3 bloques.

---
*(Siguiente hito del roadmap: Retomar Hito 4 - Ingesta Asíncrona de Excel F.37 / F.32 tras validación de plantillas con el equipo).*
