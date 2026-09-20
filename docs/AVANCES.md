# Bitácora de Avances y Registro de Ingeniería: ATLAS TECNM

**Proyecto:** ATLAS TECNM (Plataforma de Indicadores Nacionales del TecNM)  
**Ubicación:** `d:\data\ATLAS TECM`  
**Responsable:** Fábrica de Software  
**Estatus General:** Infraestructura, Base de Datos y Seeders Listos (Semana 1)  

---

## Control de Versiones y Registro de Hitos

| Hito / Sprint | Fecha | Objetivo Principal | Estatus |
| :---: | :---: | :--- | :---: |
| **Hito 0** | 19/Sep/2026 | Arquitectura conceptual, catálogo oficial de 35 submódulos y diseño de BD | **COMPLETADO** |
| **Hito 1** | 19/Sep/2026 | Inicialización de Laravel 11, entorno local (Herd), panel Filament v3 y Git | **COMPLETADO** |
| **Hito 2** | 19/Sep/2026 | Arquitectura Data Mart, 10 Migraciones y Seeders Maestros (Ejes, Submódulos, Estados, Periodos) | **COMPLETADO** |
| **Hito 3** | Pendiente | Configuración de Clusters en Filament (4 Ejes) y Recursos MVP (2.2 MTE y 3.1 COMEXTRAS) | *Siguiente* |
| **Hito 4** | Pendiente | Ingesta asíncrona de archivos Excel con validación en segundo plano (*dry-run*) | *Pendiente* |
| **Hito 5** | Pendiente | Tablero Nacional Directivo: KPIs, semáforos y mapa coropléjico con Apache ECharts | *Pendiente* |

---

## Registro Detallado por Hito

### [Hito 0] - Definición de Arquitectura y Especificación de Datos
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Formalizar la identidad del proyecto, definir el universo de 4 ejes y 35 submódulos, acotar el alcance del MVP y diseñar la arquitectura técnica de persistencia.

#### 1. Entregables y Archivos Creados:
1. `docs/Análisis.md`:
   * Documento conceptual maestro.
   * Formalización del nombre oficial: **ATLAS TECNM** (se descartó la propuesta preliminar PINT).
   * Mapeo jerárquico completo de los 4 ejes y 35 submódulos institucionales numerados.
   * Acotación del MVP a dos submódulos de vinculación: **2.2 Modelo Talento Emprendedor (MTE)** y **3.1 Movilidad Nacional e Internacional / COMEXTRAS**.
   * Definición de matriz de 6 roles en 4 niveles de acceso (R1 a R6).
2. `docs/arquitectura_bd.md`:
   * Especificación técnica profunda de la base de datos en PostgreSQL 16 (Neon Serverless).
   * Esquema estrella relacional con extensión JSONB indexada con GIN (`detalles_adicionales`).
   * Columnas generadas `STORED ALWAYS` para garantizar cálculo automático e infalible de totales por género a nivel disco.
   * Diagrama Entidad-Relación (ERD) en Mermaid.
   * Código de migraciones para Laravel 11 y sentencias DDL completas.
   * Configuración de alta concurrencia con PgBouncer Connection Pooler (puerto 6543).

#### 2. Decisiones de Ingeniería Clave:
* Se descartó el uso de NoSQL (MongoDB) por la naturaleza relacional de los datos y el volumen controlado (~263 planteles en 4 cortes trimestrales).
* Se definió un flujo pragmático de dos estados para el MVP (`Borrador` $\rightarrow$ `Publicado`) para evitar bloqueos burocráticos y cumplir con la ventana de 7 semanas hacia noviembre.

---

### [Hito 1] - Inicialización del Proyecto e Instalación de Filament v3
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Montar la estructura limpia de Laravel 11 en la raíz `d:\data\ATLAS TECM` y preparar el panel Filament v3.

#### 1. Actividades Realizadas:
* Verificación exitosa del entorno local: **PHP 8.4** con extensiones `pdo_pgsql` y `pgsql` habilitadas a través de Laravel Herd.
* Descarga de **Laravel 11** y migración atómica de archivos a la raíz (`d:\data\ATLAS TECM`) preservando la documentación del proyecto en `docs/`.
* Instalación exitosa de **Laravel Boost**.
* Instalación e inicialización de **Filament v3 (v3.3.55)** y **Livewire (v3.8.9)**.
* Generación del panel administrativo institucional (`app/Providers/Filament/AdminPanelProvider.php`).
* Creación de `docs/guia_entorno_desarrollo.md` documentando la justificación de Herd frente a opciones alternativas.
* Inicialización del repositorio Git, control de versiones y primer push a la rama `main` en GitHub.

---

### [Hito 2] - Arquitectura Data Mart, Migraciones y Seeders Maestros
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Diseñar el modelo dimensional en estrella, crear la suite completa de 10 migraciones de base de datos en Neon PostgreSQL y programar los seeders de catálogos institucionales.

#### 1. Entregables y Archivos Creados:
1. `docs/justificacion_data_warehouse_esquema_estrella.md`:
   * Fundamentación teórica: Diferenciación entre OLTP (transaccional) y OLAP (analítica directiva tipo Kimball).
   * Mapeo de las 4 Dimensiones: Tiempo (`cat_periodos`), Geografía (`cat_entidades`), Institución (`cat_planteles`) y Concepto (`cat_submodulos`).
   * Justificación técnica del porqué este diseño ejecuta consultas agregadas para mapas de calor en menos de 5 ms.
2. **Suite de 10 Migraciones en `database/migrations/`:**
   * `0001_01_01_000001_create_cache_table.php` (Caché del sistema).
   * `0001_01_01_000002_create_jobs_table.php` (Colas para procesamiento en segundo plano).
   * `2026_09_19_100001_create_cat_ejes_table.php` (Los 4 ejes rectores).
   * `2026_09_19_100002_create_cat_submodulos_table.php` (Los 35 submódulos con bandera `activo_mvp`).
   * `2026_09_19_100003_create_cat_entidades_table.php` (Las 32 entidades con clave INEGI).
   * `2026_09_19_100004_create_cat_planteles_table.php` (Catálogo maestro de institutos con sostenimiento Federal/Descentralizado).
   * `2026_09_19_100005_create_users_table.php` (Usuarios con campo nativo `plantel_id` para Multi-Tenancy).
   * `2026_09_19_100006_create_cat_periodos_table.php` (Cortes trimestrales Q1 a Q4 con bandera de bloqueo).
   * `2026_09_19_100007_create_rep_registros_base_table.php` (Tabla central de hechos con columnas calculadas `STORED`, `detalles_adicionales JSONB`, índice GIN, `archivo_evidencia`, `observaciones`, `published_at`, `published_by` y `softDeletes`).
   * `2026_09_19_100008_create_rep_bitacora_cargas_table.php` (Auditoría de cargas de archivos Excel).
3. **Seeders Maestros en `database/seeders/`:**
   * `EjesSeeder.php`: Inserta los 4 ejes rectores.
   * `SubmodulosSeeder.php`: Inserta los 35 submódulos oficiales (activando `2.2` MTE y `3.1` COMEXTRAS).
   * `EntidadesSeeder.php`: Inserta los 32 estados de la República con estándar INEGI.
   * `PeriodosSeeder.php`: Inserta los 4 trimestres del año 2026.
   * `DatabaseSeeder.php`: Orquestador de la ejecución completa de semillas.

#### 2. Decisiones de Ingeniería Clave:
* Se adoptó la conexión directa de Neon (`ep-...aws.neon.tech` sin pooler) en `.env` para asegurar transacciones DDL exitosas.
* Se blindó la tabla de hechos con `unsignedInteger()->storedAs(...)` para que PostgreSQL calcule las sumatorias por género a nivel físico.
* Se integraron los 4 soportes del mundo real: evidencias documentales (`archivo_evidencia`), notas de justificación (`observaciones`), trazabilidad de dictamen (`published_at`/`published_by`) y borrado suave (`SoftDeletes`).

---
*(El Hito 3 comenzará en la siguiente sesión con la creación de los Clusters de Filament y los Recursos MTE y COMEXTRAS).*
