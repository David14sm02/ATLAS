#!/usr/bin/env python3
"""
Seed file for ATLAS High-Density Atomic Cards
Part of atlas-knowledge-mcp
"""

import sys
import os
from db import init_db, upsert_atomic_card

def seed_atomic_cards():
    cards = {
        "comextras": {
            "title": "Normativa y Operación de Comisiones al Extranjero (COMEXTRA)",
            "content": """### Normativa y Operación COMEXTRA (Circular 0045 & MOO-PR-039)
- **Marco Legal:** Circular 0045/COMEXTRAS y Manual de Procedimientos MOO-PR-039 (DVeIA / TecNM).
- **Plazo de Solicitud Anticipada:** Mínimo **30 días hábiles previos** al viaje. Incumplimiento genera improcedencia automática.
- **Plazo de Informe y Comprobación:** Máximo **30 días hábiles posteriores** al término. Si no se entrega informe con evidencias, el sistema **bloquea nuevas autorizaciones** para dicho plantel.
- **Resolución Oficial:** Todo viaje requiere Oficio Resolutivo emitido por la Dirección General del TecNM (formato `M00/XXXX/2026`).
- **Coexistencia de Datos:** 
  1. *Macrodata:* Formato F.37 (Intercambio y comisiones agregadas por trimestre).
  2. *Microdata:* Base nominal individual (`BASE COMEXTRAS 2026.xlsm` con 17,837 registros históricos, 47 columnas).
- **Requisitos de Expediente:** Carta de invitación traducida, dictamen de academia, justificación académica, seguro de gastos médicos internacional."""
        },

        "roles_v2_1": {
            "title": "Matriz de Roles y Cargos Institucionales Reales (COMEXTRAS)",
            "content": """### Matriz de Roles y Cargos Oficiales Institucionales (Manual MOO-PR-039 & Circular 0045)
- **R0 - Administrador TI (Nacional):**
  - **Cargo:** *Jefe(a) de Departamento de Tecnologías de la Información y Comunicaciones (TecNM)*.
  - **Facultades:** Administración técnica global, gestión de tenants/planteles, cuentas y variables.
- **R1 - Autoridad Resolutiva Central (Oficio M00):**
  - **Cargos:** *Director(a) General del TecNM* / *Secretario(a) de Extensión y Vinculación (SEV)*.
  - **Facultades:** Autorización y firma de Oficios Oficiales M00/XXXX/2026 (MOO-PR-039-A01) y validación de austeridad.
- **R2 - Dictaminador Técnico Revisor (DVeIA Central):**
  - **Cargos:** *Director(a) de Vinculación e Intercambio Académico* / *Jefe(a) de Departamento de Movilidad Internacional*.
  - **Facultades:** Cotejo normativo de 5 requisitos, validación de plazo (≥30 días hábiles) y emisión de nota ejecutiva.
- **R3 - Enlace Operativo en Plantel:**
  - **Cargo:** *Jefe(a) de Departamento de Gestión Tecnológica y Vinculación*.
  - **Facultades:** Ventanilla receptora del instituto, verificación presupuestal local e integración del expediente.
- **R4 - Titular del Instituto / Centro (Aval Institucional):**
  - **Cargo:** *Director(a) del Instituto Tecnológico / Centro*.
  - **Facultades:** Firma de postulación y aval oficial del plantel. Suplencia legal por ausencia delegada en *Subdirector(a) de Planeación y Vinculación* o *Académico(a)*.
- **R5 - Comisionado(a) Solicitante:**
  - **Cargos:** *Personal Docente Comisionado* / *Estudiante Comisionado* (o *PAAE*).
  - **Facultades:** Registro de solicitud, comisión y entrega obligatoria de informe técnico en ≤30 días hábiles (bloqueo ante omisión).
- **R6 - Órgano Interno de Control (Auditoría Forense):**
  - **Cargo:** *Titular del Órgano Interno de Control (OIC en el TecNM)* / *Auditor SFP*.
  - **Facultades:** Fiscalización de comprobaciones, apego a Circular 0045 e inspección de `rep_dictamenes_log`.
- **R_ESTATAL - Coordinación Estatal OPD:**
  - **Cargo:** *Director(a) General de Educación Superior Tecnológica Estatal*.
  - **Facultades:** Supervisión y acompañamiento de institutos descentralizados con subsidio estatal.
- **Mecanismos de Protección:**
  - `lock_version` (concurrencia optimista contra colisiones).
  - Bitácora inmutable `rep_dictamenes_log` (IP, user_agent, snapshot JSON anterior/nuevo).
  - Clasificación `tipo_institucion`: Federales, Descentralizados (OPDs) y Centros (CRODE/CIIDET/CENIDET)."""
        },

        "database_schemas": {
            "title": "Esquemas Relacionales Clave en PostgreSQL / Laravel 11",
            "content": """### Esquemas Relacionales Clave (PostgreSQL 16 / Filament v3)
- **`com_solicitudes`:**
  - `id` (UUID PK), `folio_comextra` (VARCHAR UNIQUE, formato `COMEXTRA-2026-XXXXX`).
  - `clave_plantel` (VARCHAR, FK `planteles.clave`), `user_id` (FK `users.id`).
  - `tipo_solicitante` (ENUM: 'docente', 'estudiante', 'directivo').
  - `pais_destino` (VARCHAR), `ciudad_destino` (VARCHAR), `fecha_inicio`, `fecha_fin` (DATE).
  - `estatus` (ENUM: 'borrador', 'enviada', 'validada_plantel', 'aprobada_dveia', 'rechazada', 'informe_pendiente', 'concluida').
  - `oficio_resolutivo` (VARCHAR NULL, ej. 'M00/1042/2026'), `dictamen_fecha` (DATETIME).
  - `bloqueado_por_adeudo` (BOOLEAN DEFAULT false), `lock_version` (INTEGER DEFAULT 1).
- **`user_submodulos` (Pivote N:M):**
  - `user_id` (FK), `submodulo_id` (1 al 15: Idiomas, Servicio Social, COMEXTRA, Becas, etc.).
- **`rep_dictamenes_log` (Auditoría Forense):**
  - `id` (BIGINT PK), `solicitud_id` (UUID), `user_id`, `accion` (VARCHAR), `estado_anterior`, `estado_nuevo`.
  - `ip_address` (INET), `user_agent` (TEXT), `payload_snapshot` (JSONB), `created_at` (TIMESTAMP)."""
        },

        "planteles_regiones": {
            "title": "Catálogo de Planteles y Descentralización TecNM",
            "content": """### Estructura Territorial y Clasificación de Planteles
- **Universo de Institutos:** 263 planteles a nivel nacional.
- **Régimen Jurídico:**
  - **Federales:** Centralizados, dependen presupuestalmente de la Dirección General TecNM.
  - **Descentralizados (OPDs):** Organismos Públicos Descentralizados dependientes de Secretarías de Educación Estatales.
  - **Centros Especializados:** CRODE (Optimización de Equipo), CIIDET (Investigación Educativa), CENIDET (Desarrollo Tecnológico).
- **Directorio:** 272 directores registrados históricamente en bases de datos.
- **Regiones Operativas:** Noroeste, Noreste, Centro-Occidente, Centro-Sur, Sureste.
- **Filtro Multi-Tenant:** Cada usuario de plantel (R3/R4) está aislado por su `clave_plantel`."""
        },

        "identidad_visual": {
            "title": "Guía de Identidad Gráfica Institucional TecNM & Filament v3",
            "content": """### Guía de Identidad Gráfica y Tokens de Diseño TecNM
- **Paleta de Colores Oficial:**
  - Azul Marino TecNM (Primario): `#1B396A`
  - Tinto / Guinda Institucional (Acento / Destacados): `#9B2247`
  - Verde Secretaría de Educación (Validaciones / Éxito): `#285C4D`
  - Dorado Institucional (Bordes / Alertas sutiles): `#D4C19C`
  - Azul Claro (Interactivo / Enlaces): `#4D92DF`
  - Gris Fondo Dark: `#0F172A` / `#1E293B`
- **Dashboard Nacional (Widgets Auditados en Repo):**
  - `PulsoNacionalWidget`: 4 Stat Cards (Solicitudes activas, monto estimado, planteles con comisiones, tasa de dictaminación).
  - `MapaRepublicaWidget`: Leaflet integrado con tile layer ESRI Dark Canvas y GeoJSON de los 32 estados.
  - `InclusionSectoresWidget`: Gráfica Donut (Docentes vs Estudiantes vs Investigadores)."""
        },

        "roadmap_hitos": {
            "title": "Estado del Repositorio ATLAS (Richpol99/ATLAS) e Hitos",
            "content": """### Auditoría de Hitos en ATLAS (Richpol99/ATLAS)
- **Hito 0 (Entorno Base):** Laravel 11.28 + Filament v3.2 + Neon PostgreSQL 16. [COMPLETADO]
- **Hito 1 (Multi-Tenancy):** Aislamiento por `clave_plantel` y middleware de contexto. [COMPLETADO]
- **Hito 2 (Seguridad & Roles):** Laravel Shields / Políticas base. (Propuesta v2.1 lista para migración).
- **Hito 3 (Tablero Nacional):** PulsoNacional, MapaRepublica y widgets analíticos. [COMPLETADO]
- **Hito 4 (Ingesta y ETL de Excel):** Carga en cola (`ImportComextrasJob`) de `BASE COMEXTRAS 2026.xlsm` con modo Dry-run y validación de RFC/CURP. [PENDIENTE - SIGUIENTE SPRINT]
- **Hito 5 (Indicadores DVeIA):** 15 submódulos integrados con reportes trimestrales F.37. [EN PROCESO]"""
        }
    }

    init_db()
    for slug, data in cards.items():
        upsert_atomic_card(slug, data["title"], data["content"])
        print(f"[*] Seeded atomic card: {slug} - '{data['title']}'")

if __name__ == "__main__":
    seed_atomic_cards()
    print("[+] All atomic cards successfully seeded into SQLite FTS5 database!")
