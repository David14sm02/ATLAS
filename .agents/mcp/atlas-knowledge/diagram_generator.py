#!/usr/bin/env python3
"""
Mermaid Diagram Generator for ATLAS Knowledge Base
Compliant with design-doc-mermaid skill standards (Unicode symbols, High-contrast TecNM palette).
Part of atlas-knowledge-mcp
"""

from typing import Dict, Any

TECNM_PALETTE = {
    "blue_primary": "#1B396A",
    "wine_accent": "#9B2247",
    "green_secondary": "#285C4D",
    "gold_highlight": "#D4C19C",
    "blue_light": "#4D92DF",
    "dark_bg": "#0F172A",
    "white_text": "#FFFFFF",
    "dark_text": "#1F1F1F"
}

def generate_mermaid_diagram(diagram_type: str, feature: str) -> str:
    dtype = diagram_type.lower()
    feat = feature.lower().replace("-", "_")

    if dtype == "sequence":
        if "comextra" in feat or "autorizacion" in feat:
            return """```mermaid
sequenceDiagram
    autonumber
    actor Solicitante as 👤 Solicitante (R5 Docente/Estudiante)
    participant Plantel as 🏫 Validador / Director (R3/R4)
    participant ATLAS as ⚙️ Plataforma ATLAS
    participant DVeIA as 🏛️ Gestor DVeIA (R1 Nacional)
    participant OIC as ⚖️ OIC Auditor (R6)

    Note over Solicitante,ATLAS: Solicitud con 30 días hábiles de anticipación (Circular 0045)
    Solicitante->>ATLAS: Registro de Solicitud + Carta Invitación + Seguro
    ATLAS-->>Plantel: Notificación de expediente pendiente
    Plantel->>ATLAS: Aval Institucional y Firma Delegada (R4)
    ATLAS->>ATLAS: Validación de concurrencia optimista (lock_version)
    ATLAS->>DVeIA: Expediente remitido para dictamen nacional
    
    rect rgb(240, 245, 255)
        Note over DVeIA,ATLAS: Dictaminación y Oficio Resolutivo
        DVeIA->>ATLAS: Emisión de Dictamen Favorable (Oficio M00/XXXX/2026)
        ATLAS->>ATLAS: Registro inmutable en rep_dictamenes_log
    end
    
    ATLAS-->>Solicitante: Notificación de Autorización Oficial
    ATLAS-->>OIC: Bitácora auditable con IP y firmas
    
    Note over Solicitante,ATLAS: 30 días hábiles posteriores: Entrega de Informe de Comisión
    Solicitante->>ATLAS: Carga de Informe Técnico y Comprobantes
    ATLAS->>ATLAS: Desbloqueo de estatus del plantel
```"""
        elif "audit" in feat or "oic" in feat or "forense" in feat:
            return """```mermaid
sequenceDiagram
    autonumber
    actor Directivo as 👤 Director de Plantel (R4)
    participant Filament as 🖥️ Panel Filament v3
    participant Guard as 🛡️ Policy & Middleware
    participant Core as ⚙️ Laravel 11 Core
    participant LogDB as ⚖️ Bitácora Inmutable (rep_dictamenes_log)
    actor Auditor as 🔍 OIC Fiscalizador (R6)

    Directivo->>Filament: Avala Solicitud COMEXTRA
    Filament->>Guard: Valida Permiso 'dictaminar_comextra'
    Guard->>Core: Inicia Transacción ACID
    Core->>Core: Snapshot JSON de estado anterior y nuevo
    Core->>LogDB: INSERT INTO rep_dictamenes_log (solicitud_id, accion='AVAL_PLANTEL', ip, user_agent, snapshot)
    LogDB-->>Core: ID generado (Inmutable, sin SoftDeletes)
    Core-->>Filament: Transacción Exitosa
    Auditor->>Filament: Consulta Tablero de Inspección Forense
    Filament->>LogDB: SELECT * FROM rep_dictamenes_log ORDER BY created_at DESC
    LogDB-->>Auditor: Vista auditable de firmas y trazabilidad IP
```"""
        elif "totp" in feat or "2fa" in feat or "auth" in feat:
            return """```mermaid
sequenceDiagram
    autonumber
    actor Usuario as 👤 Director / Gestor (R1/R4)
    participant Auth as 🔐 Laravel Fortify / Filament
    participant TOTP as 📱 Google Authenticator (TOTP)
    participant DB as 🐘 Neon PostgreSQL

    Usuario->>Auth: Ingresa Email y Password
    Auth->>DB: Verifica credenciales y flag 2FA
    DB-->>Auth: Credenciales válidas (2FA requerido)
    Auth-->>Usuario: Solicita Token de 6 dígitos
    Usuario->>TOTP: Consulta código temporal
    TOTP-->>Usuario: Código (ej. 482910)
    Usuario->>Auth: Envía código TOTP
    Auth->>Auth: Valida HMAC-SHA1 y ventana de 30s
    Auth->>DB: Registra inicio de sesión en audit_log
    Auth-->>Usuario: Acceso concedido a plataforma ATLAS
```"""
        elif "tenancy" in feat or "plantel" in feat:
            return """```mermaid
sequenceDiagram
    autonumber
    actor Validador as 👤 Validador de Plantel (R3)
    participant Web as 🌐 Request HTTP
    participant Middleware as 🛡️ TenancyContextMiddleware
    participant Model as 📦 Eloquent Model (ComSolicitud)
    participant DB as 🐘 Neon PostgreSQL

    Validador->>Web: GET /admin/com-solicitudes
    Web->>Middleware: Procesa sesión de usuario autenticado
    Middleware->>Middleware: Extrae user.clave_plantel ('14DIT0001Z')
    Middleware->>Model: Registra contexto de tenant activo
    Model->>Model: Inyecta GlobalScope (clave_plantel = '14DIT0001Z')
    Model->>DB: SELECT * FROM com_solicitudes WHERE clave_plantel = '14DIT0001Z'
    DB-->>Model: Retorna únicamente registros del plantel de origen
    Model-->>Validador: Renderiza tabla con aislamiento estricto
```"""
        else: # Generic roles/delegated signature
            return """```mermaid
sequenceDiagram
    autonumber
    actor Subdirector as 👤 Subdirector Académico (Firma por Ausencia)
    participant Sistema as ⚙️ ATLAS Core
    participant DB as 💾 Neon PostgreSQL 16
    participant Auditor as ⚖️ OIC Fiscalizador (R6)

    Subdirector->>Sistema: Firma Aval para Comisión (Ausencia R4)
    Sistema->>DB: Check lock_version = 1
    Sistema->>DB: UPDATE com_solicitudes SET estatus='validada_plantel', lock_version=2
    Sistema->>DB: INSERT INTO rep_dictamenes_log (solicitud_id, accion='FIRMA_DELEGADA', ip, user_agent)
    DB-->>Sistema: Commit exitoso
    Sistema-->>Auditor: Registro visible en tablero forense
```"""

    elif dtype == "erd":
        return """```mermaid
erDiagram
    USERS ||--o{ COM_SOLICITUDES : "registra"
    USERS ||--o{ USER_SUBMODULOS : "atiende"
    CAT_PLANTELES ||--o{ COM_SOLICITUDES : "origina"
    COM_SOLICITUDES ||--o{ REP_DICTAMENES_LOG : "auditoria"

    USERS {
        bigint id PK
        string name
        string email UK
        string role
        string clave_plantel FK
        string tipo_institucion
        boolean activo
    }

    COM_SOLICITUDES {
        uuid id PK
        string folio_comextra UK
        string clave_plantel FK
        bigint user_id FK
        string tipo_solicitante
        string pais_destino
        date fecha_inicio
        date fecha_fin
        string estatus
        string oficio_resolutivo
        int lock_version
    }

    CAT_PLANTELES {
        bigint id PK
        string clave_tecnm UK
        string nombre
        string sostenimiento
    }

    USER_SUBMODULOS {
        bigint id PK
        bigint user_id FK
        int submodulo_id
    }

    REP_DICTAMENES_LOG {
        bigint id PK
        uuid solicitud_id FK
        bigint user_id FK
        string accion
        inet ip_address
        jsonb payload_snapshot
        timestamp created_at
    }
```"""

    elif dtype == "architecture":
        if "indicadores" in feat or "f37" in feat or "pipeline" in feat:
            return """```mermaid
graph TD
    classDef source fill:#1B396A,stroke:#0B2341,color:#FFFFFF;
    classDef etl fill:#9B2247,stroke:#5E152B,color:#FFFFFF;
    classDef storage fill:#285C4D,stroke:#16362D,color:#FFFFFF;
    classDef ui fill:#D4C19C,stroke:#8F7A54,color:#1F1F1F;

    ExcelRaw[📊 BASE COMEXTRAS 2026.xlsm] --> ImportJob[⚙️ ImportComextrasJob - Cola Redis]
    ImportJob --> Validator[🛡️ Validador RFC, CURP y Fechas]
    Validator --> DBComextras[(💾 com_solicitudes - Microdata)]

    DBComextras --> Aggregator[🧠 F37AggregatorService]
    Aggregator --> DBF37[(📊 rep_registros_base - Macrodata F.37)]

    DBF37 --> WidgetPulso[📈 PulsoNacionalWidget]
    DBF37 --> WidgetMapa[🗺️ MapaRepublicaWidget - ESRI Leaflet]
    DBF37 --> ExportExcel[📥 Generador Trimestral F.37 Excel]

    class ExcelRaw,ExportExcel source;
    class ImportJob,Validator,Aggregator etl;
    class DBComextras,DBF37 storage;
    class WidgetPulso,WidgetMapa ui;
```"""
        return """```mermaid
graph TB
    classDef client fill:#1B396A,stroke:#0B2341,stroke-width:2px,color:#FFFFFF;
    classDef app fill:#9B2247,stroke:#5E152B,stroke-width:2px,color:#FFFFFF;
    classDef db fill:#285C4D,stroke:#16362D,stroke-width:2px,color:#FFFFFF;
    classDef mcp fill:#D4C19C,stroke:#8F7A54,stroke-width:2px,color:#1F1F1F;

    Users[👤 263 Planteles y DVeIA] --> WebApp[🌐 Laravel 11 + Filament v3.2]
    AIEditors[💻 Cursor / VS Code / Claude] --> MCPServer[⚡ atlas-knowledge-mcp Server]

    WebApp --> PostgreSQL[(🐘 Neon PostgreSQL 16)]
    WebApp --> Redis[(⚡ Redis Cache & Queue)]
    
    MCPServer --> SQLite[(🗄️ SQLite FTS5 Cache)]
    MCPServer -.->|AST Read-Only| WebApp
    MCPServer -.->|Sync| GoogleDrive[📁 Google Drive]

    class Users,AIEditors client;
    class WebApp app;
    class PostgreSQL,Redis,SQLite db;
    class MCPServer,GoogleDrive mcp;
```"""

    elif dtype == "flowchart":
        return """```mermaid
flowchart TD
    classDef startEnd fill:#285C4D,stroke:#16362D,color:#FFFFFF;
    classDef process fill:#1B396A,stroke:#0B2341,color:#FFFFFF;
    classDef decision fill:#D4C19C,stroke:#8F7A54,color:#000000;
    classDef error fill:#9B2247,stroke:#5E152B,color:#FFFFFF;

    Start([🚀 Inicio: Carga de Excel]) --> ReadFile[📂 Cargar archivo .xlsm en Cola]
    ReadFile --> ParseRows[🔍 Leer 17,837 registros con Spatie Simple-Excel]
    ParseRows --> CheckDryRun{¿Modo Dry-Run activado?}
    
    CheckDryRun -->|Sí| ValidateOnly[📋 Validar RFC, Fechas y Catálogos sin escribir BD]
    CheckDryRun -->|No| ProcessBatch[⚙️ Procesar en Lotes de 500 registros]
    
    ValidateOnly --> ReportErrors[📑 Generar Reporte de Errores e Inconsistencias]
    ProcessBatch --> MatchPlantel{¿Clave de Plantel válida?}
    
    MatchPlantel -->|No| LogErr[⚠️ Registrar error en log de importación]
    MatchPlantel -->|Sí| UpsertRecord[💾 Upsert en com_solicitudes con folio único]
    
    UpsertRecord --> EndOk([✅ Importación Exitosa])
    ReportErrors --> EndOk
    LogErr --> EndOk

    class Start,EndOk startEnd;
    class ReadFile,ParseRows,ValidateOnly,ProcessBatch,UpsertRecord process;
    class CheckDryRun,MatchPlantel decision;
    class LogErr,ReportErrors error;
```"""

    # Default fallback
    return f"""```mermaid
graph TD
    classDef defaultStyle fill:#1B396A,stroke:#0B2341,color:#FFFFFF;
    Node1[🚀 Componente: {feature}] --> Node2[⚙️ Proceso ATLAS]
    Node2 --> Node3[✅ Resultado Validado]
    class Node1,Node2,Node3 defaultStyle;
```"""
