# Reporte de Ingeniería: Arquitectura de Roles v2.1, Catálogo Oficial de 266 Planteles, Bitácora Forense Inmutable y Oráculo MCP

**Proyecto:** ATLAS TECNM — Plataforma de Indicadores Nacionales del Tecnológico Nacional de México  
**Fecha de Emisión:** 02 de Noviembre de 2026  
**Área Responsable:** Fábrica de Software / Arquitectura de Datos y Seguridad  
**Versión de Especificación:** v2.1 (Multi-Tenant + Caja Negra de Auditoría)  

---

## Resumen Ejecutivo

Durante esta jornada de ingeniería se completó la base estructural de **Seguridad Institucional, Segregación Multi-Tenant por Plantel/Estado y Trazabilidad Forense Inmutable** de la plataforma **ATLAS TECNM**, dejándola lista para que el equipo de **Frontend** pueda diseñar, probar y validar las vistas diferenciadas por jerarquía institucional.

Los 5 entregables principales implementados hoy son:

1. **Integración del Servidor MCP (`atlas-knowledge-mcp` v2.1.0):** Instalación local del Oráculo de Conocimiento Híbrido con base de datos vectorial/FTS5 en SQLite (`303 fragmentos` indexados de `54 documentos` oficiales y código fuente) y `23 herramientas` especializadas.
2. **Catálogo Oficial de los 266 Planteles y Centros del TecNM:** Extracción íntegra desde la macro-base oficial `BASE COMEXTRAS 2026.xlsm` (hoja `Edo_Reg_IT_Dir`), clasificando los **126 Institutos Federales (Centralizados)**, **134 Institutos Descentralizados (OPD Estatales)** y **6 Centros Especializados** (`CRODE`, `CIIDET`, `CENIDET`), con sus Regiones (`1` a `9`), siglas y directores titulares.
3. **Arquitectura Institucional de Roles v2.1 (`R1` a `R6`) y Matriz de Submódulos (`user_submodulos`):** Expansión del esquema de usuarios para soportar alcance Nacional, Estatal/Regional, Local por Plantel y Operativo por Submódulo (`1` a `35`), además de la figura legal de **Firma por Ausencia (`R4_SUBDIRECTOR_DELEGADO`)**.
4. **Entorno de Pruebas para Frontend (13 Perfiles Semilla):** Creación del seeder `UsersRolesSeeder` con 13 cuentas de prueba representativas de toda la pirámide del TecNM y filtrado automático de Widgets y Recursos en Filament v3.
5. **Sistema de Monitoreo y Bitácora Forense Inmutable ("Caja Negra"):** Implementación de la tabla `rep_dictamenes_log`, el trait automático `RegistraBitacoraForense` y el módulo visual de **solo lectura estricta** `BitacoraAuditoriaResource` con visibilidad jerárquica (Nacional, Estatal y Perfil de Plantel).

---

## 1. Integración del Servidor MCP (`atlas-knowledge-mcp` v2.1.0)

Se clonó, configuró e indexó el servidor Model Context Protocol oficial del proyecto desde el repositorio `Richpol99/MCP-ATLAS-TecNM` dentro de `.agents/mcp/atlas-knowledge/`.

### 1.1 Acciones Realizadas
* **Adaptación Multiplataforma (Windows / Entorno Local):** Se actualizaron las rutas predeterminadas en `db.py`, `indexer.py`, `laravel_introspector.py`, `excel_manager.py`, `comextras_tracker.py` y `drive_connector.py` para apuntar directamente a la raíz del proyecto (`d:\data\ATLAS TECM`) y excluir archivos temporales de bloqueo de Office (`~$*.xlsm`).
* **Indexación de Conocimiento (SQLite FTS5):**
  * Se ejecutaron las semillas de **6 Tarjetas Atómicas de Contexto** (`cards_seed.py`).
  * Se indexaron **54 archivos locales** generando **303 fragmentos de conocimiento** en `.agents/mcp/atlas-knowledge/atlas_knowledge.db`.
* **Seguridad de Repositorio (`.gitignore`):** Se blindó el repositorio agregando reglas de exclusión para la base compilada `atlas_knowledge.db` y credenciales sensibles (`service_account.json`, `gdrive_oauth_token.json`).
* **Registro de Configuración MCP:** Configurado en `.mcp.json` y `.cursor/mcp.json` con soporte para 23 herramientas divididas en 5 dominios (Cerebro Documental, Reglas Normativas COMEXTRAS, Introspección Laravel/Filament, Conector Google Drive y Automatización).

---

## 2. Catálogo Maestro Oficial de los 266 Planteles y Centros (`PlantelesSeeder`)

A partir de la inspección directa de la hoja oculta `Edo_Reg_IT_Dir` del archivo oficial `docs/modulos/3.1_comextras/formatos_origen/BASE COMEXTRAS 2026.xlsm`, se verificó el universo exacto del Tecnológico Nacional de México:

* **Total de Filas en el Padrón Maestro:** `269` registros.
  * **3 Autoridades de Área Central (Región 0):**
    1. Dirección General del TecNM — *Prof. Ramón Jiménez López*
    2. Secretaría de Extensión y Vinculación — *Mtra. Andrea Yadira Zárate Fuentes*
    3. Dirección de Vinculación e Intercambio Académico — *Dr. Marco Antonio Trujillo Martínez*
  * **266 Planteles y Centros Operativos (Regiones 1 a 9):**

| Clasificación Institucional (`tipo_institucion`) | Tipo de Sostenimiento (`tipo_sostenimiento`) | Total | Descripción Operativa |
| :--- | :---: | :---: | :--- |
| **`IT_FEDERAL`** | `FEDERAL` | **126** | Institutos Tecnológicos Federales (Centralizados), administrados directamente por la Federación. |
| **`IT_DESCENTRALIZADO`** | `DESCENTRALIZADO` | **134** | Institutos Tecnológicos Superiores (OPD con convenio federal-estatal), supervisados también por el nivel `R3`. |
| **`CENTRO_ESPECIALIZADO`** | `FEDERAL` | **6** | 4 `CRODE` (Celaya, Chihuahua, Mérida, Orizaba), `CIIDET` (Querétaro) y `CENIDET` (Cuernavaca). |
| **TOTAL NACIONAL** | — | **266** | **100% del padrón oficial TecNM cargado en `cat_planteles`** |

### 2.1 Nuevas Columnas en `cat_planteles`
Mediante la migración `2026_10_01_100004_add_tipo_institucion_and_lock_version.php`, cada plantel cuenta ahora con:
* `region` (`integer`): Región geográfica oficial del TecNM (`1` al `9`).
* `siglas` (`string`): Acrónimo oficial del plantel (ej. `ITCANCUN`, `ITSZAPOPAN`, `CENIDET`).
* `tipo_institucion` (`string`): `IT_FEDERAL`, `IT_DESCENTRALIZADO`, `CENTRO_ESPECIALIZADO`.
* `director_nombre` (`string`): Nombre completo del Director(a) General del plantel según el padrón 2026.
* `director_cargo` (`string`): Cargo oficial titular.

---

## 3. Arquitectura de Roles Institucionales v2.1 (`R1` a `R6`)

Se implementó la jerarquía completa de 10 claves de rol agrupadas en 6 niveles estratégicos dentro del modelo `app/Models/User.php` y las migraciones `2026_10_01_100001_update_users_table_roles_v2_1.php` y `2026_10_01_100002_create_user_submodulos_table.php`.

### 3.1 Matriz de Roles y Alcance de Visibilidad

| Nivel | Clave de Rol (`rol_clave`) | Perfil Institucional | Alcance de Datos (Multi-Tenant) | Permisos Clave |
| :---: | :--- | :--- | :--- | :--- |
| **R1** | `R1_SUPERADMIN` | Superadministrador General (DTI / Planeación) | **Nacional (266 planteles)** | Control total de sistema, catálogos, trimestres, usuarios y auditoría nacional. |
| **R1** | `R1_DIRECTOR_GENERAL` | Director General / Secretario Sectorial | **Nacional (266 planteles)** | Tablero Ejecutivo Nacional (3 bloques), semáforos y reportes ejecutivos. |
| **R2** | `R2_DIRECTOR_AREA` | Director de Área Central / Titular de Eje | **Nacional (Su Eje/Módulos)** | Dictamina registros nacionales (`VALIDAR`, `RECHAZAR`, `APERTURA_EXTEMPORANEA`). |
| **R2** | `R2_AUDITOR_OIC` | Auditor del Órgano Interno de Control | **Nacional (266 planteles)** | **Solo lectura estricta (`Read-Only`)** en capturas, anexos y Caja Negra Forense. |
| **R3** | `R3_SUPERVISOR_ESTATAL` | Director de Educación Superior Estatal (OPD) | **Estatal (`entidad_id`)** | Supervisión de los Tecnológicos Descentralizados de su estado. |
| **R3** | `R3_COORDINADOR_REGIONAL` | Coordinador de Zona Geográfica | **Regional (`zona_geografica`)** | Monitoreo de planteles ubicados en su región asignada. |
| **R4** | `R4_DIRECTOR_PLANTEL` | Director(a) de Instituto o Centro | **Local (`plantel_id`)** | Autoridad máxima del plantel. Firma y publica cortes oficiales; audita a su plantel. |
| **R4** | `R4_SUBDIRECTOR_DELEGADO` | Subdirector / Jefe de Gestión y Vinculación | **Local (`plantel_id`)** | Coordina los 35 submódulos del plantel y **firma por ausencia** del Director. |
| **R5** | `R5_OPERADOR_MODULO` | Jefe de Depto. / Capturista de Submódulo | **Local + Submódulos (`user_submodulos`)** | **Solo ve y captura los submódulos explícitamente asignados** en su plantel. |
| **R6** | `R6_CONSULTA_PUBLICA` | Portal de Transparencia / Invitado | **Datos Agregados Públicos** | Consulta de estadísticas públicas sin acceso a datos personales. |

### 3.2 Asignación Matricial de Submódulos (`user_submodulos`)
Un operador (`R5_OPERADOR_MODULO`) no tiene acceso abierto a todos los módulos de su plantel. La tabla pivote `user_submodulos` vincula:
* `user_id` $\leftrightarrow$ `submodulo_id` (`1` a `35`, ej. `3.1 COMEXTRAS`, `2.2 MTE`)
* `puede_capturar` (`boolean`) y `puede_editar` (`boolean`)
* Trazabilidad de quién le otorgó el permiso (`asignado_por`) y vigencia (`fecha_expiracion`).

---

## 4. Directorio de Usuarios de Prueba para el Equipo de Frontend (`UsersRolesSeeder`)

Para que el equipo de Frontend pueda diseñar y probar las pantallas de cada jerarquía de inmediato, el seeder `database/seeders/UsersRolesSeeder.php` crea **13 usuarios de prueba** listos para iniciar sesión en `/admin/login`.

> **Contraseña universal en entorno de desarrollo:** `password`

| # | Correo Electrónico | Rol (`rol_clave`) | Plantel / Estado Asignado | ¿Qué ve al entrar al Sistema? |
| :-: | :--- | :--- | :--- | :--- |
| 1 | `admin@tecnm.mx` | `R1_SUPERADMIN` | Nacional (TecNM Central) | Todo el sistema, Dashboard Nacional (Mapa + Pulso), los 266 planteles y Bitácora Forense Nacional. |
| 2 | `ramon.jimenez@tecnm.mx` | `R1_DIRECTOR_GENERAL` | Nacional (Dirección General) | Vista Ejecutiva Nacional: Mapa de la República, Pulso Nacional y Semáforos de los 266 planteles. |
| 3 | `andrea.zarate@tecnm.mx` | `R2_DIRECTOR_AREA` | Nacional (Secretaría Extensión y Vinculación) | Dashboard Nacional y módulos de su secretaría a nivel nacional para dictaminar. |
| 4 | `marco.trujillo@tecnm.mx` | `R2_DIRECTOR_AREA` | Nacional (Dir. Vinculación e Intercambio) | Gestión y validación nacional de COMEXTRAS (`3.1`) y MTE (`2.2`). |
| 5 | `auditoria.oic@tecnm.mx` | `R2_AUDITOR_OIC` | Nacional (Órgano Interno de Control) | **Modo Solo Lectura Total**: Ve todos los registros y la Bitácora Forense sin botones de crear/editar. |
| 6 | `supervisor.jalisco@tecnm.mx` | `R3_SUPERVISOR_ESTATAL` | Estado de Jalisco (`entidad_id = 14`) | Ve el tablero y registros únicamente de los planteles del Estado de Jalisco y su bitácora estatal. |
| 7 | `director.cancun@tecnm.mx` | `R4_DIRECTOR_PLANTEL` | I.T. de Cancún (*Federal*) | **No ve el Mapa Nacional**. Ve el widget `PulsoPlantelWidget` de Cancún, sus capturas y la Bitácora de su plantel. |
| 8 | `vinculacion.cancun@tecnm.mx` | `R4_SUBDIRECTOR_DELEGADO` | I.T. de Cancún (*Federal*) | Coordina los submódulos de Cancún, audita a sus capturistas y puede **Firmar por Ausencia**. |
| 9 | `comextras.cancun@tecnm.mx` | `R5_OPERADOR_MODULO` | I.T. de Cancún — **Solo Submódulo 3.1** | Ve **únicamente COMEXTRAS (3.1)** de Cancún. MTE (`2.2`) y la Bitácora de Auditoría le aparecen ocultos. |
| 10 | `mte.cancun@tecnm.mx` | `R5_OPERADOR_MODULO` | I.T. de Cancún — **Solo Submódulo 2.2** | Ve **únicamente MTE (2.2)** de Cancún. COMEXTRAS (`3.1`) y la Bitácora de Auditoría le aparecen ocultos. |
| 11 | `director.zapopan@tecnm.mx` | `R4_DIRECTOR_PLANTEL` | I.T.S. de Zapopan (*Descentralizado OPD*) | Vista local del I.T.S. de Zapopan (OPD Jalisco) y bitácora interna de su plantel. |
| 12 | `operador.zapopan@tecnm.mx` | `R5_OPERADOR_MODULO` | I.T.S. de Zapopan — **Submódulos 3.1 y 2.2** | Capturista polivalente de Zapopan con acceso a ambos submódulos MVP (`3.1` y `2.2`). |
| 13 | `director.cenidet@tecnm.mx` | `R4_DIRECTOR_PLANTEL` | CENIDET (*Centro Especializado*) | Vista local de Centro Especializado de Posgrado e Investigación y su bitácora interna. |

---

## 5. Sistema de Monitoreo y Bitácora Forense Inmutable ("Caja Negra")

Para garantizar transparencia institucional y evitar alteraciones discrecionales ("mañas" o modificaciones sin rastro), se separó la bitácora técnica de importación de archivos (`rep_bitacora_cargas`) de la nueva **Caja Negra de Auditoría Forense (`rep_dictamenes_log`)**.

### 5.1 Características de Seguridad de `rep_dictamenes_log`
1. **Inmutabilidad por Diseño (Append-Only):**
   * La tabla **no tiene columna `updated_at`**.
   * El modelo `app/Models/RepDictamenLog.php` intercepta los eventos `updating` y `deleting` a nivel Eloquent ORM y lanza una excepción `LogicException` si cualquier proceso intenta modificar o borrar un registro de auditoría.
   * Si un registro operativo de `rep_registros_base` llega a eliminarse, la clave foránea usa `nullOnDelete()` para que **el registro en la bitácora jamás desaparezca** y conserve el respaldo íntegro en JSON (`valores_anteriores`).
2. **Identidad Congelada Histórica:**
   * Cada movimiento guarda `user_id`, pero además **congela en texto plano** `usuario_nombre`, `usuario_rol_clave` y `usuario_cargo` en el instante exacto del movimiento, junto con la dirección `ip_address` y navegador (`user_agent`).
3. **Captura Automática Silenciosa (`RegistraBitacoraForense`):**
   * El trait `app/Models/Concerns/RegistraBitacoraForense.php` está conectado a `RepRegistroBase` y `RepBitacoraCarga`.
   * Detecta automáticamente si el movimiento fue:
     * `CREACION` (Nuevo registro o carga)
     * `ACTUALIZACION` (Guarda exactamente qué campos cambiaron: antes vs. después)
     * `PUBLICACION` (Cierre y envío oficial del Director de Plantel)
     * `FIRMA_DELEGADA` (Detecta automáticamente cuando un `R4_SUBDIRECTOR_DELEGADO` publica en nombre del Director titular)
     * `CARGA_EXCEL` (Subida de plantilla masiva)
     * `ELIMINACION` (Intento de baja o borrado de registro, respaldando el contenido completo eliminado)

### 5.2 Módulo Visual en Filament (`BitacoraAuditoriaResource`)
Ubicado en el grupo **Seguridad y Auditoría Interna -> Bitácora de Auditoría**, este módulo cumple con las siguientes reglas:
* **100% Solo Consulta (Cero Edición):** Tiene bloqueados por código los métodos `canCreate()`, `canEdit()`, `canDelete()` y `canDeleteAny()`, sin acciones masivas (`bulkActions([])`). Nadie —ni siquiera un Superadministrador desde la interfaz— puede subir, editar o borrar renglones de este historial.
* **Perfil de Auditoría por Plantel (Multi-Tenant):**
  * **Nivel Nacional (`R1` y `R2`):** Ven los movimientos de los **266 planteles** con filtro por Plantel, Tipo de Movimiento y Rol.
  * **Nivel Estatal (`R3`):** Ven únicamente los movimientos de los planteles de su Estado.
  * **Nivel Plantel (`R4` Director y Subdirector/Jefe de Vinculación):** Tienen su propio **perfil local de auditoría**; ven únicamente lo que hicieron los usuarios de su propio Tecnológico (`plantel_id`).
  * **Nivel Operador (`R5`):** No tienen acceso a esta pestaña.

---

## 6. Ajustes Técnicos en Base de Datos (Neon PostgreSQL) y Flujo para Frontend

### 6.1 Correcciones Aplicadas para Migración en Neon PostgreSQL
Durante la puesta a punto en Neon Serverless PostgreSQL se resolvieron dos detalles críticos:
1. **Conexión Directa vs. PgBouncer Pooler (`.env`):** El endpoint `-pooler` de Neon aborta transacciones DDL complejas (`SQLSTATE[25P02]`). Se configuró `DB_URL` en `.env` para apuntar al host directo (`ep-fancy-silence-aw83f1rs.c-12.us-east-1.aws.neon.tech`) permitiendo que `php artisan migrate:fresh --seed` corra de principio a fin sin bloqueos.
2. **Tipo `jsonb` para Índice GIN (`2026_09_19_100007_create_rep_registros_base_table.php`):** PostgreSQL requiere tipo `jsonb` (binario indexable) en lugar de `json` plano para crear índices `GIN`. Se actualizó la columna `detalles_adicionales` a `$table->jsonb('detalles_adicionales')->nullable()`.

### 6.2 Flujo de Trabajo Recomendado en GitHub para el Equipo de Frontend
Para agilizar el trabajo paralelo del equipo de Frontend sin conflictos de código:
1. **Rama Protegida (`main` / `develop`):** Ningún desarrollador hace *push* directo a `main`.
2. **Ramas Cortas por Vista o Rol (`feature/`):**
   * `feature/ui-dashboard-plantel-r4` (Vistas de Director/Subdirector de Plantel)
   * `feature/ui-captura-operador-r5` (Formularios de captura para Operadores)
   * `feature/ui-supervisor-estatal-r3` (Tablero de Supervisores Estatales OPD)
3. **Validación por Pull Request (PR):** Cada integrante prueba su vista iniciando sesión con los 13 usuarios del `UsersRolesSeeder` y adjunta captura de pantalla en su Pull Request antes de hacer *merge*.

---

## 7. Inventario de Archivos Creados y Modificados en esta Jornada

### Migraciones y Base de Datos
* `database/migrations/2026_09_19_100007_create_rep_registros_base_table.php` *(ajuste `jsonb` para índice GIN)*
* `database/migrations/2026_10_01_100001_update_users_table_roles_v2_1.php` *(nuevo)*
* `database/migrations/2026_10_01_100002_create_user_submodulos_table.php` *(nuevo)*
* `database/migrations/2026_10_01_100003_create_rep_dictamenes_log_table.php` *(nuevo)*
* `database/migrations/2026_10_01_100004_add_tipo_institucion_and_lock_version.php` *(nuevo)*

### Seeders Oficiales
* `database/seeders/PlantelesSeeder.php` *(nuevo — 266 planteles y centros reales extraídos de Excel)*
* `database/seeders/UsersRolesSeeder.php` *(nuevo — 13 usuarios de prueba por rol y tipo de instituto)*
* `database/seeders/DatabaseSeeder.php` *(actualizado)*

### Modelos Eloquent y Auditoría Forense
* `app/Models/User.php` *(actualizado con matriz de roles R1–R6 y helpers de autorización)*
* `app/Models/CatPlantel.php` *(actualizado con campos regionales y directivos)*
* `app/Models/RepDictamenLog.php` *(nuevo — modelo inmutable de Caja Negra)*
* `app/Models/Concerns/RegistraBitacoraForense.php` *(nuevo — observador automático de cambios)*
* `app/Models/RepRegistroBase.php` *(conectado a `RegistraBitacoraForense`)*
* `app/Models/RepBitacoraCarga.php` *(conectado a `RegistraBitacoraForense`)*

### Recursos y Widgets en Filament v3
* `app/Filament/Resources/BitacoraAuditoriaResource.php` *(nuevo — módulo de auditoría 100% solo lectura)*
* `app/Filament/Resources/BitacoraAuditoriaResource/Pages/ListBitacoraAuditorias.php` *(nuevo)*
* `app/Filament/Widgets/PulsoPlantelWidget.php` *(nuevo — KPIs locales para Directores y Operadores de Plantel)*
* `app/Filament/Widgets/PulsoNacionalWidget.php` *(restringido a nivel Nacional/Regional)*
* `app/Filament/Widgets/MapaRepublicaWidget.php` *(restringido a nivel Nacional/Regional)*
* `app/Filament/Clusters/IntercambioAcademico/Resources/ComextrasResource.php` *(aislamiento Multi-Tenant y filtro por submódulo)*
* `app/Filament/Clusters/InnovacionEmprendimiento/Resources/MteResource.php` *(aislamiento Multi-Tenant y filtro por submódulo)*
* `app/Providers/Filament/AdminPanelProvider.php` *(registro de `PulsoPlantelWidget`)*

### Pruebas Automatizadas
* `tests/Feature/RolesV21AndPlantelesCatalogTest.php` *(nuevo — prueba integral de catálogo 266, aislamiento por rol e inmutabilidad de bitácora)*

