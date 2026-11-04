# Reporte Ejecutivo de Ingeniería: Fusión Scoped Multi-Tenancy, 35 Submódulos en 4 Ejes, Filtros Reactivos y Gestión de Usuarios (`UserResource`)

**Proyecto:** ATLAS TECNM — Plataforma de Indicadores Nacionales del Tecnológico Nacional de México  
**Fecha de Emisión:** 03 de Noviembre de 2026  
**Área Responsable:** Fábrica de Software / Arquitectura Full-Stack y Seguridad  
**Hito del Roadmap:** Hito 7 (Habilitación Integral de los 35 Submódulos, Filtros Analíticos en Vivo y Autogestión de Usuarios por Plantel)

---

## Resumen Ejecutivo de la Jornada

En esta sesión de ingeniería se consolidaron **5 avances estructurales** que dejan la plataforma 100% navegable de punta a punta en sus **4 Ejes y 35 Submódulos**, con su **Tablero Directivo reactivo en vivo** y con **autogestión de usuarios y permisos por plantel**:

1. **Integración y Unificación del PR #1 (*Scoped Multi-Tenancy* + *Hardening Docker*):** Fusión limpia en la rama `main` entre el aislamiento automático por alcance (`BelongsToPlantel` / `PlantelScope`) y el sistema de Roles v2.1 (`R1`–`R6`), catálogo de los 266 planteles reales y la Caja Negra de Auditoría Forense (`RegistraBitacoraForense`).
2. **Habilitación de las 35 Pestañas y Módulos en los 4 Ejes Institucionales (Clusters):** Creación de la arquitectura base (`BaseSubmoduloResource` y `BaseManageSubmodulo`) y activación de los **35 submódulos oficiales (`1.1` a `4.12`)**, eliminando los estados bloqueados de "Fase 2 / Programado" y activando los enlaces *"Ir al módulo ->"* en todo el catálogo.
3. **Filtros Reactivos en Tiempo Real en el Mapa de la República (`MapaRepublicaWidget`):** Conexión de 3 filtros dinámicos (**Módulo/Eje**, **Región TecNM `1 a 9`** y **Tipo de Institución: `126 Federales` / `134 Descentralizados OPD` / `6 Centros Especializados`**) con repintado en vivo de polígonos y tooltips en Leaflet y despliegue del directorio real de Tecnológicos al seleccionar un Estado.
4. **Filtros Interactivos en el Bloque 3 de Gráficas (`InclusionSectoresWidget`) + Datos Semilla (`RegistrosDemoSeeder`):** Incorporación de filtros por **Estado (`01–32`)**, **Región (`1–9`)** y **Tipo de Institución** que actualizan en vivo las 3 gráficas de Apache ECharts (Dona de Paridad Demográfica, Sectores Estratégicos MTE `2.2` y Modalidades COMEXTRAS `3.1`), respaldado por un seeder demostrativo en 9 institutos clave.
5. **Módulo de Administración de Usuarios y Asignación de los 35 Submódulos (`UserResource`):** Creación de la pestaña **"Usuarios y Permisos"** con aislamiento Multi-Tenant por plantel, candados anti-escalamiento de privilegios, matriz de casillas (`CheckboxList`) para asignar submódulos (`1.1` a `4.12`) a los operadores (`R5`), botón de revocación/reactivación institucional y registro automático en la Caja Negra de Auditoría.

---

## 1. Unificación de *Scoped Multi-Tenancy* con Roles v2.1 y Auditoría Forense

### 1.1 ¿Qué se integró del PR #1 (`Angel-Var0117`)?
* **Trait `BelongsToPlantel` y `PlantelScope` (`app/Models/Scopes/PlantelScope.php`):** Aplica un filtro automático en las consultas Eloquent de `RepRegistroBase` y `RepBitacoraCarga`.
* **Enriquecimiento con Roles v2.1:** Se amplió `PlantelScope` para que, además de filtrar por `plantel_id` en los roles `R4` y `R5`, filtre automáticamente por `entidad_id` y `sostenimiento = 'DESCENTRALIZADO'` cuando inicia sesión un **Supervisor Estatal de OPDs (`R3_SUPERVISOR_TERRITORIAL`)**.
* **Compatibilidad PgBouncer en Neon (`config/database.php`):** Se incorporó `\PDO::ATTR_EMULATE_PREPARES => true` para evitar bloqueos de transacciones en conexiones concurrentes.
* **Documentación Técnica para Frontend:** Se elaboró la guía `docs/tecnica/guia_frontend_roles_vistas_y_scoped_multitenancy.md` aclarando la diferencia entre *Scoped Multi-Tenancy en Base Única (Esquema Estrella)* y *Multi-Tenancy Multi-Base*, junto con las reglas de diseño de vistas y CSS (`public/css/atlas-theme.css` + Tailwind).

---

## 2. Habilitación de las 35 Pestañas de los 4 Ejes Institucionales

Gracias al modelo dimensional en Esquema Estrella (donde los 35 submódulos persisten en `rep_registros_base` + `detalles_adicionales` en `jsonb`), se habilitaron todas las pestañas de navegación lateral y tablas de captura en los 4 Clusters:

| Eje Institucional (Cluster) | Submódulos Habilitados | Total de Pestañas Activas |
| :--- | :--- | :---: |
| **Eje 1: Vinculación Estratégica** | `1.1` Consejos de Vinculación, `1.2` Convenios, `1.3` Egresados y Bolsa de Trabajo, `1.4` Proyectos Estratégicos, `1.5` Semiconductores, `1.6` Vinculación con Sectores, `1.7` Indicadores de Calidad, `1.8` Marco Normativo | **8 submódulos** + Catálogo |
| **Eje 2: Innovación y Emprendimiento** | `2.1` InnovaTecNM, `2.2` **MTE (Prioritario MVP)**, `2.3` NODESS, `2.4` Centros de Innovación Empresarial, `2.5` Educación Financiera, `2.6` Propiedad Intelectual, `2.7` Transferencia de Tecnología, `2.8` Centros de Patentamiento | **8 submódulos** + Catálogo |
| **Eje 3: Intercambio Académico** | `3.1` **Movilidad y COMEXTRAS (Prioritario MVP)**, `3.2` Servicio Social, `3.3` AlfabetizaTec, `3.4` Residencias Profesionales, `3.5` Educación Dual, `3.6` Networking, `3.7` Unidades Regionales | **7 submódulos** + Catálogo |
| **Eje 4: Extensión** | `4.1` Cursos MOOC, `4.2` Lenguas Extranjeras y Maternas, `4.3` Educación Continua, `4.4` Centros de Certificación, `4.5` Red CIIA Automotriz/Aeroespacial, `4.6` Nodos de Creatividad, `4.7` Certificación de Laboratorios, `4.8` Servicio Externo, `4.9` Comercialización, `4.10` Arte y Cultura, `4.11` Deporte, `4.12` Formación Cívica | **12 submódulos** + Catálogo |
| **TOTAL NACIONAL** | **100% del Marco Institucional TecNM** | **35 Submódulos Operativos** |

### 2.1 Filtros Homologados en las 35 Tablas (`BaseSubmoduloResource`)
Cada uno de los 35 submódulos cuenta ahora con **5 filtros institucionales**:
1. **Periodo Trimestral** (`periodo_id`)
2. **Entidad Federativa / Estado** (`01` a `32`)
3. **Tipo de Institución** (`Federales Centralizados`, `Descentralizados OPD`, `Centros Especializados`)
4. **Instituto Tecnológico / Centro** (Buscador sobre los 266 planteles)
5. **Estatus de Entrega** (`BORRADOR`, `PUBLICADO`)

---

## 3. Filtros Reactivos en Vivo en el Mapa y las Gráficas del Dashboard

### 3.1 Bloque 2: Mapa Interactivo de la República (`MapaRepublicaWidget`)
* **Repintado sin recarga (`@atlas-mapa-actualizado`):** Se conectó Livewire con Alpine.js y Leaflet para que al cambiar cualquier filtro superior (**Eje/Submódulo**, **Región `1 a 9`** o **Tipo de Institución `126 / 134 / 6`**), los 32 estados actualicen su color de calor y el contenido de su *tooltip* flotante en tiempo real.
* **Directorio Estatal de Tecnológicos:** Al hacer clic sobre cualquier entidad federativa en el mapa, el panel lateral derecho despliega ahora la **lista de los Tecnológicos reales de ese estado** con sus siglas, región, tipo (`Federal` / `OPD` / `Centro`) y su estatus de entrega en el trimestre (`Al Día`, `Borrador` o `Pendiente`).

### 3.2 Bloque 3: Gráficas Analíticas con Apache ECharts (`InclusionSectoresWidget`)
* Se agregaron selectores interactivos por **Estado**, **Región (`1–9`)** y **Tipo de Institución** para usuarios Nacionales (`R1`/`R2`), y filtrado automático por Plantel/Estado para usuarios `R3`, `R4` y `R5`.
* Al cambiar un filtro, el evento `@atlas-graficas-actualizadas` actualiza y anima en tiempo real las 3 gráficas (Dona de Paridad Demográfica, Barras de Sectores Estratégicos MTE `2.2` y Barras de Modalidades COMEXTRAS `3.1`).

### 3.3 Seeder de Capturas Demostrativas (`RegistrosDemoSeeder`)
* Pobla registros trimestrales realistas en 9 instituciones representativas (`ITCANCUN`, `ITSZAPOPAN`, `CENIDET`, `ITCELAYA`, `ITMERIDA`, `ITVER`, `ITPUEBLA`, `ITTIJUANA`, `ITSALTILLO`), incluyendo publicaciones con **Firma Titular** y **Firma por Ausencia (`FIRMA_DELEGADA`)** reflejadas en el Semáforo Nacional y en la Bitácora Forense.

---

## 4. Módulo de Gestión de Usuarios y Asignación de Submódulos (`UserResource`)

Se implementó el recurso `app/Filament/Resources/UserResource.php` (**pestaña "Usuarios y Permisos"**) con las siguientes capacidades y candados de seguridad:

1. **Aislamiento Multi-Tenant de Usuarios:**
   * **`R1_SUPERADMIN`:** Administra usuarios de todo el país (Oficinas Centrales, Supervisores Estatales y los 266 Planteles).
   * **`R2_AUDITOR_OIC`:** Consulta el padrón nacional de usuarios y permisos en **modo 100% solo lectura**.
   * **`R4_DIRECTOR_TITULAR` y `R4_SUBDIRECTOR_DELEGADO`:** Ven y administran **únicamente al personal de su propio Tecnológico (`WHERE plantel_id = su_plantel`)**.
   * **`R5_OPERADOR_MOD`:** No tiene acceso a este módulo.
2. **Candados Anti-Escalamiento de Privilegios:**
   * Un Director o Subdirector de Plantel (`R4`) tiene bloqueado el campo `plantel_id` en su propio instituto y solo puede asignar roles locales (`R5_OPERADOR_MOD`, `R4_SUBDIRECTOR_DELEGADO`, `R4_DIRECTOR_TITULAR`). Jamás puede crear ni editar cuentas de nivel Nacional (`R1`/`R2`).
3. **Matriz Visual de los 35 Submódulos (`CheckboxList`):**
   * Al crear o editar un usuario con rol `R5_OPERADOR_MOD`, se despliega la lista buscable de los **35 submódulos (`1.1` a `4.12`)** con opción de marcar/desmarcar en bloque, guardando la asignación en `user_submodulos`.
4. **Revocación Institucional sin Pérdida Forense (`toggleAcceso`):**
   * No se permite el borrado físico de cuentas (`canDelete = false`). En su lugar, existe el botón **"Revocar / Reactivar"** que desactiva el acceso (`activo = false`, `revocado_at = now()`, `revocado_by = auth()->id()`) manteniendo intacto todo el historial de auditoría.
5. **Conexión Directa con la Caja Negra (`RepDictamenLog`):**
   * Toda creación de usuario, modificación de rol/submódulos o revocación/reactivación genera automáticamente una entrada inmutable en `rep_dictamenes_log` bajo el módulo `USUARIOS`.

