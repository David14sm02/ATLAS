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
| **Hito 6** | 02/Nov/2026 | Roles v2.1 (R1–R6), Catálogo Oficial de 266 Planteles, Bitácora Forense Inmutable y Oráculo MCP | **COMPLETADO** |
| **Hito 7** | 03/Nov/2026 | 35 Submódulos en 4 Ejes, Filtros Reactivos en Mapa/Gráficas, Seeder Demo y Gestión de Usuarios (`UserResource`) | **COMPLETADO** |

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

### [Hito 6] - Arquitectura de Roles v2.1, Catálogo de 266 Planteles, Bitácora Forense Inmutable y Servidor MCP
* **Fecha:** 02 de Noviembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Implementar la segregación Multi-Tenant por jerarquía institucional (`R1` a `R6`), poblar el catálogo oficial completo de los **266 Institutos y Centros del TecNM**, habilitar los 13 usuarios semilla para el equipo de Frontend, construir el sistema de monitoreo interno de solo lectura ("Caja Negra" de auditoría) e integrar el servidor `atlas-knowledge-mcp`.
* **Entregables Principales:**
  1. **Servidor MCP (`atlas-knowledge-mcp` v2.1.0):** Instalado en `.agents/mcp/atlas-knowledge/` con 6 tarjetas atómicas y 303 fragmentos indexados en SQLite FTS5.
  2. **Catálogo Maestro de 266 Planteles (`PlantelesSeeder.php`):** Extracción directa desde `BASE COMEXTRAS 2026.xlsm` (`126` Federales, `134` Descentralizados OPD y `6` Centros Especializados: `CRODE`, `CIIDET`, `CENIDET`).
  3. **Sistema de Roles v2.1 y Matriz `user_submodulos`:** Migraciones `2026_10_01_100001` a `100004`, actualización de `User.php` y creación de `UsersRolesSeeder.php` con 13 perfiles de prueba para Frontend.
  4. **Caja Negra de Auditoría Forense (`rep_dictamenes_log`):** Modelo inmutable `RepDictamenLog`, observador automático `RegistraBitacoraForense` (registra altas, cambios antes/después, publicaciones, firmas delegadas, cargas Excel y eliminaciones) y recurso de solo lectura estricta `BitacoraAuditoriaResource`.
  5. **Documentación Ejecutiva:** `docs/ejecutivos/reporte_roles_v2_1_planteles_y_auditoria_forense.md`.
* **Estatus del Hito 6:** **CONCLUIDO EXITOSAMENTE**.

---

### [Hito 7] - Habilitación de los 35 Submódulos en los 4 Ejes, Filtros Reactivos en Vivo y Gestión de Usuarios (`UserResource`)
* **Fecha:** 03 de Noviembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Integrar el PR #1 (*Scoped Multi-Tenancy* y mejoras de Docker/PgBouncer), habilitar las 35 pestañas y tablas operativas de los 4 Ejes Institucionales, conectar filtros reactivos en tiempo real en el Mapa de la República y en el Bloque 3 de Gráficas ECharts, poblar datos demostrativos (`RegistrosDemoSeeder`) y construir el módulo `UserResource` para que Superadmins y Directores de Plantel administren usuarios y asignen submódulos visualmente.
* **Entregables Principales:**
  1. **Arquitectura de los 35 Submódulos (`BaseSubmoduloResource` y `BaseManageSubmodulo`):** 51 rutas administrativas activas en los 4 Clusters (`Eje 1: 1.1–1.8`, `Eje 2: 2.1–2.8`, `Eje 3: 3.1–3.7`, `Eje 4: 4.1–4.12`) con 5 filtros institucionales homologados.
  2. **Mapa y Gráficas 100% Reactivos (`MapaRepublicaWidget` e `InclusionSectoresWidget`):** Filtros en vivo por Módulo/Eje, Región (`1–9`), Estado (`01–32`) y Tipo de Institución (`126 Federales`, `134 Descentralizados OPD`, `6 Centros Especializados`), con directorio de planteles por entidad seleccionada.
  3. **Módulo de Usuarios y Permisos (`UserResource` y `ManageUsers`):** Gestión Multi-Tenant con candados anti-escalamiento, asignación matricial `CheckboxList` de los 35 submódulos, revocación institucional (`toggleAcceso`) y trazabilidad automática en `RepDictamenLog`.
  4. **Documentación Ejecutiva y Técnica:** `docs/ejecutivos/reporte_avances_clusters_filtros_y_gestion_usuarios.md` y `docs/tecnica/guia_frontend_roles_vistas_y_scoped_multitenancy.md`.
* **Estatus del Hito 7:** **CONCLUIDO EXITOSAMENTE**.

---
*(Siguiente hito del roadmap: Flujo de Firma Oficial / Rechazo en tablas de captura y Retomar Hito 4 - Ingesta Asíncrona de Excel F.37 / F.32).*
