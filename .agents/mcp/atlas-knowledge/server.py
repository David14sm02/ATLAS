#!/usr/bin/env python3
"""
ATLAS Knowledge Hub - Model Context Protocol (MCP) Server v2.1.0
Pure Python High-Performance Server with Dual-Transport (stdio + SSE/HTTP)
Compliant with Anthropic MCP Specification (2024-11-05).
Features:
- 20 Enterprise Tools
- 100% Dynamic MCP Resources & Prompts
- isError: true exception safety
- Session-based SSE Queue Dispatcher
- GitHub & Google Drive Webhooks
"""

import sys
import os
import json
import logging
import threading
import time
import argparse
import queue
import uuid
from typing import Dict, Any, List

# Ensure local modules can be loaded
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)

from db import get_atomic_card, list_atomic_cards, search_knowledge, get_connection
from diagram_generator import generate_mermaid_diagram
from indexer import sync_all_sources_incremental
from drive_connector import (
    sync_knowledge_sources,
    list_drive_contents,
    create_drive_folder,
    move_drive_file,
    auto_organize_drive,
    read_drive_file_content,
    upload_drive_file
)
from comextras_tracker import audit_comextras_codebase, generate_comextras_status_report
from excel_manager import inspect_excel_metadata, sample_excel_rows, get_f37_indicators
from compliance_engine import calculate_deadlines, validate_solicitud_compliance, validate_mexican_id
from laravel_introspector import (
    get_table_schema,
    search_planteles_catalog,
    get_filament_blueprint,
    generate_laravel_scaffold,
    check_database_health,
    scan_migration_files,
    PROPOSED_SCHEMAS
)

# Auto-sync configuration
AUTO_SYNC_ENABLED = os.environ.get("ATLAS_AUTO_SYNC", "true").lower() in ("true", "1", "yes")
AUTO_SYNC_INTERVAL = int(os.environ.get("ATLAS_AUTO_SYNC_INTERVAL", "300"))
_LAST_JIT_CHECK = 0
_JIT_COOLDOWN = 60  # Check at most once every 60 seconds on tool queries

def ensure_knowledge_fresh():
    """Smart Just-In-Time cache invalidator: ensures knowledge is fresh before tool execution."""
    global _LAST_JIT_CHECK
    now = time.time()
    if now - _LAST_JIT_CHECK > _JIT_COOLDOWN:
        _LAST_JIT_CHECK = now
        try:
            res = sync_all_sources_incremental()
            if res.get("total_updated", 0) > 0:
                logging.info(f"[JIT-Sync] Refreshed {res['total_updated']} files ({res['total_new_chunks']} chunks).")
        except Exception as e:
            logging.warning(f"[JIT-Sync] Warning during auto-check: {e}")

def _background_auto_sync_worker():
    """Background daemon thread that periodically refreshes context without blocking developer workflow."""
    logging.info(f"[Auto-Sync Daemon] Started with interval {AUTO_SYNC_INTERVAL}s.")
    while True:
        try:
            time.sleep(AUTO_SYNC_INTERVAL)
            logging.info("[Auto-Sync Daemon] Checking for modified documents and codebase changes...")
            res = sync_all_sources_incremental()
            if res.get("total_updated", 0) > 0:
                logging.info(f"[Auto-Sync Daemon] Knowledge base refreshed: {res['total_updated']} files updated ({res['total_new_chunks']} chunks).")
            try:
                sync_knowledge_sources()
            except Exception:
                pass
        except Exception as e:
            logging.error(f"[Auto-Sync Daemon] Error in background loop: {e}")

def start_background_sync():
    """Launches the background daemon thread if enabled."""
    if AUTO_SYNC_ENABLED and AUTO_SYNC_INTERVAL > 0:
        t = threading.Thread(target=_background_auto_sync_worker, daemon=True, name="AtlasAutoSync")
        t.start()
        logging.info("[Auto-Sync Daemon] Daemon thread launched successfully.")

# Logging to stderr so stdout remains clean for JSON-RPC in stdio mode
logging.basicConfig(
    stream=sys.stderr,
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s"
)

# ==========================================
# MCP TOOLS METADATA (20 Enterprise Tools)
# ==========================================

TOOLS_METADATA = [
    {
        "name": "atlas_quick_context",
        "description": "Retorna una Tarjeta Atómica de Conocimiento (< 800 tokens) sobre un tópico clave del proyecto ATLAS TECNM, eliminando el desperdicio de tokens.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "topic": {
                    "type": "string",
                    "enum": [
                        "comextras",
                        "roles_v2_1",
                        "database_schemas",
                        "planteles_regiones",
                        "identidad_visual",
                        "roadmap_hitos"
                    ],
                    "description": "Tópico técnico o normativo sobre el cual se necesita contexto inmediato."
                }
            },
            "required": ["topic"]
        }
    },
    {
        "name": "atlas_search_knowledge",
        "description": "Búsqueda semántica y BM25 en toda la base de conocimiento de ATLAS (Google Drive, GitHub, PDFs, Word, Excel y circulares). Retorna los 3 fragmentos más relevantes.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "query": {
                    "type": "string",
                    "description": "Pregunta o términos clave a buscar (ej. 'plazo entrega informe comextra', 'clave_plantel', 'neon postgresql')."
                },
                "source_filter": {
                    "type": "string",
                    "enum": ["todos", "local_doc", "github"],
                    "description": "Filtrar por origen de datos (default: 'todos')."
                },
                "limit": {
                    "type": "integer",
                    "description": "Número máximo de fragmentos a devolver (1 a 5, default 3)."
                }
            },
            "required": ["query"]
        }
    },
    {
        "name": "atlas_get_schema",
        "description": "Retorna el esquema relacional exacto (columnas, tipos, claves e índices) inspeccionando dinámicamente las migraciones de Laravel 11 y esquemas propuestos.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "table_name": {
                    "type": "string",
                    "description": "Nombre de la tabla (ej. 'cat_planteles', 'com_solicitudes', 'users', 'cat_submodulos', 'rep_dictamenes_log')."
                }
            },
            "required": ["table_name"]
        }
    },
    {
        "name": "atlas_get_regulatory_rule",
        "description": "Consulta un articulado o regla no negociable de la Circular 0045 y Manual MOO-PR-039 (plazos de 30 días, oficios M00, causales de rechazo).",
        "inputSchema": {
            "type": "object",
            "properties": {
                "rule_topic": {
                    "type": "string",
                    "enum": [
                        "anticipacion_30_dias",
                        "informe_30_dias",
                        "bloqueo_adeudo",
                        "oficio_m00",
                        "requisitos_expediente"
                    ],
                    "description": "Regla legal específica de COMEXTRA."
                }
            },
            "required": ["rule_topic"]
        }
    },
    {
        "name": "atlas_generate_diagram",
        "description": "Genera código Mermaid sintácticamente válido con la paleta oficial TecNM para flujos como: 'comextra', 'roles', 'etl_excel', 'multi_tenancy', 'oic_audit', 'auth_totp', 'f37_indicadores'.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "diagram_type": {
                    "type": "string",
                    "enum": ["erd", "sequence", "architecture", "flowchart"],
                    "description": "Tipo de diagrama a graficar."
                },
                "feature": {
                    "type": "string",
                    "description": "Flujo o módulo (ej. 'comextra', 'roles', 'etl_excel', 'multi_tenancy', 'oic_audit', 'auth_totp', 'f37_indicadores')."
                }
            },
            "required": ["diagram_type", "feature"]
        }
    },
    {
        "name": "atlas_sync_drive",
        "description": "Sincroniza y actualiza el índice de conocimiento con los últimos archivos de Google Drive y el repositorio GitHub.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "force": {
                    "type": "boolean",
                    "description": "Forzar reindexación completa de todos los orígenes de datos."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_drive_list",
        "description": "Consulta y lista todos los archivos y subdirectorios de la carpeta de Google Drive configurada.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "folder_id": {
                    "type": "string",
                    "description": "ID de la carpeta a consultar (opcional, usa la carpeta raíz configurada por defecto)."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_drive_create_folder",
        "description": "Crea un nuevo subdirectorio dentro de la carpeta de Google Drive.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "folder_name": {
                    "type": "string",
                    "description": "Nombre del subdirectorio a crear (ej. '01_Normativas', '02_Bases_Excel')."
                },
                "parent_folder_id": {
                    "type": "string",
                    "description": "ID del directorio padre (opcional, usa la carpeta principal por defecto)."
                }
            },
            "required": ["folder_name"]
        }
    },
    {
        "name": "atlas_drive_move_file",
        "description": "Mueve un archivo hacia un subdirectorio específico en Google Drive.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "file_id": {
                    "type": "string",
                    "description": "ID del archivo en Google Drive que se desea mover."
                },
                "target_folder_id": {
                    "type": "string",
                    "description": "ID del subdirectorio destino."
                }
            },
            "required": ["file_id", "target_folder_id"]
        }
    },
    {
        "name": "atlas_drive_organize",
        "description": "Clasifica y acomoda automáticamente los archivos de Google Drive en subdirectorios temáticos (Normativa, Bases Excel, Formatos, Presentaciones, Prototipos UI, Documentación Técnica).",
        "inputSchema": {
            "type": "object",
            "properties": {
                "folder_id": {
                    "type": "string",
                    "description": "ID de la carpeta a organizar (opcional, usa la carpeta raíz configurada)."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_inspect_excel_columns",
        "description": "Inspecciona metadatos, hojas y nombres de columnas de archivos Excel masivos (BASE COMEXTRAS 2026.xlsm de 22MB o F.37) con detección inteligente de cabeceras desplazadas.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "file_alias_or_path": {
                    "type": "string",
                    "description": "Alias ('comextras', 'f37') o ruta al archivo Excel (default: 'comextras')."
                },
                "sheet_name": {
                    "type": "string",
                    "description": "Nombre específico de la hoja a inspeccionar (opcional)."
                },
                "header_row": {
                    "type": "integer",
                    "description": "Índice de fila de cabecera manual (0-indexed, opcional; auto-detectado si se omite)."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_sample_excel",
        "description": "Muestrea de forma segura y en streaming filas de un archivo Excel de 22MB con filtro de texto (ej. 'Saltillo', '2025', 'docente').",
        "inputSchema": {
            "type": "object",
            "properties": {
                "file_alias_or_path": {
                    "type": "string",
                    "description": "Alias ('comextras', 'f37') o ruta al archivo Excel (default: 'comextras')."
                },
                "sheet_name": {
                    "type": "string",
                    "description": "Nombre de la hoja a consultar (opcional)."
                },
                "query": {
                    "type": "string",
                    "description": "Filtro de texto para coincidencia en filas."
                },
                "limit": {
                    "type": "integer",
                    "description": "Número máximo de filas a retornar (default: 3)."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_validate_solicitud",
        "description": "Validador algorítmico de cumplimiento normativo (Circular 0045 y MOO-PR-039) para COMEXTRA: verifica los 30 días hábiles obligatorios, checklist documental y bloqueo por adeudos.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "fecha_solicitud": {
                    "type": "string",
                    "description": "Fecha en que se registra el trámite (YYYY-MM-DD, default hoy)."
                },
                "fecha_inicio": {
                    "type": "string",
                    "description": "Fecha de inicio de la comisión en el extranjero (YYYY-MM-DD)."
                },
                "fecha_fin": {
                    "type": "string",
                    "description": "Fecha de término de la comisión (YYYY-MM-DD)."
                },
                "tipo_solicitante": {
                    "type": "string",
                    "enum": ["docente", "estudiante", "directivo"],
                    "description": "Perfil del solicitante institucional."
                },
                "documentos_anexos": {
                    "type": "array",
                    "items": {"type": "string"},
                    "description": "Lista de claves de documentos anexados (carta_invitacion, aval_director, dictamen_academia, seguro_internacional, justificacion_academica)."
                },
                "tiene_adeudo_previo": {
                    "type": "boolean",
                    "description": "Indica si el comisionado o su plantel tienen informes de comisión vencidos pendientes de comprobar."
                },
                "pais_destino": {
                    "type": "string",
                    "description": "País receptor de la comisión."
                }
            },
            "required": ["fecha_inicio", "fecha_fin", "tipo_solicitante"]
        }
    },
    {
        "name": "atlas_calculate_deadlines",
        "description": "Calcula las fechas límites institucionales (30 días hábiles previos para ingresar solicitud y 30 días hábiles posteriores para entregar informe) con base en el calendario oficial mexicano.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "fecha_inicio": {
                    "type": "string",
                    "description": "Fecha de inicio del viaje (YYYY-MM-DD)."
                },
                "fecha_fin": {
                    "type": "string",
                    "description": "Fecha de término del viaje (YYYY-MM-DD)."
                }
            },
            "required": ["fecha_inicio", "fecha_fin"]
        }
    },
    {
        "name": "atlas_get_catalog_planteles",
        "description": "Consulta y busca en el catálogo oficial de los 263 institutos tecnológicos del TecNM (Federales, Descentralizados OPDs y Centros Especializados: CRODE, CIIDET, CENIDET).",
        "inputSchema": {
            "type": "object",
            "properties": {
                "query": {
                    "type": "string",
                    "description": "Término de búsqueda: nombre de plantel, entidad federativa, clave o tipo de sostenimiento."
                },
                "limit": {
                    "type": "integer",
                    "description": "Máximo de planteles a mostrar (default: 5)."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_get_filament_blueprint",
        "description": "Genera código PHP para Filament v3 (Resources, Widgets, Form Schemas, Table Columns) aplicando la paleta oficial TecNM y aislamiento multi-tenant por clave_plantel.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "feature": {
                    "type": "string",
                    "description": "Módulo o entidad a modelar (ej. 'comextra', 'planteles', 'indicadores')."
                },
                "component_type": {
                    "type": "string",
                    "enum": ["resource", "widget"],
                    "description": "Tipo de componente Filament a generar."
                }
            },
            "required": ["feature"]
        }
    },
    {
        "name": "atlas_drive_read_content",
        "description": "Descarga y extrae el texto de un documento de Google Drive (Google Docs, PDFs o minutas) para incorporarlo al contexto del asistente sin saturar memoria.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "file_id": {
                    "type": "string",
                    "description": "ID del archivo en Google Drive que se desea leer."
                }
            },
            "required": ["file_id"]
        }
    },
    # --- NUEVAS HERRAMIENTAS v2.1 ---
    {
        "name": "atlas_validate_mexican_id",
        "description": "Valida formalmente la estructura y algoritmos oficiales de CURP (RENAPO con dígito verificador) y RFC (SAT con homoclave) para comisionados y usuarios.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "id_type": {
                    "type": "string",
                    "enum": ["curp", "rfc"],
                    "description": "Tipo de documento a validar ('curp' o 'rfc')."
                },
                "id_value": {
                    "type": "string",
                    "description": "Cadena alfanumérica del CURP o RFC."
                }
            },
            "required": ["id_type", "id_value"]
        }
    },
    {
        "name": "atlas_get_f37_indicators",
        "description": "Extrae de forma agregada los indicadores institucionales del archivo oficial F.37 (Intercambio y Comisiones al Extranjero) sin saturar tokens de contexto.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "year": {
                    "type": "integer",
                    "description": "Año de reporte a consultar (default: 2026)."
                },
                "sheet_name": {
                    "type": "string",
                    "description": "Nombre específico de hoja en F.37 (opcional)."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_generate_laravel_scaffold",
        "description": "Genera un scaffold institucional completo de Laravel 11: Migración DDL, Modelo Eloquent (con casts y aislamiento multi-tenant) y Resource de Filament v3.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "table_name": {
                    "type": "string",
                    "description": "Nombre en snake_case de la tabla a crear (ej. 'cat_viaticos', 'com_comprobaciones')."
                },
                "feature_name": {
                    "type": "string",
                    "description": "Nombre descriptivo del módulo en español (ej. 'Viáticos y Pasajes')."
                },
                "fields": {
                    "type": "array",
                    "items": {
                        "type": "object",
                        "properties": {
                            "name": {"type": "string"},
                            "type": {"type": "string", "enum": ["string", "integer", "decimal", "boolean", "date", "text", "foreignId"]},
                            "nullable": {"type": "boolean"},
                            "references": {"type": "string"}
                        },
                        "required": ["name", "type"]
                    },
                    "description": "Lista de campos y tipos de datos a incluir."
                }
            },
            "required": ["table_name"]
        }
    },
    {
        "name": "atlas_check_db_health",
        "description": "Ejecuta un diagnóstico institucional de la base de datos Neon PostgreSQL 16, audita migraciones existentes y verifica la salud de la caché SQLite FTS5.",
        "inputSchema": {
            "type": "object",
            "properties": {},
            "required": []
        }
    },
    {
        "name": "atlas_comextras_status_report",
        "description": "Audita en tiempo real el repositorio de código de ATLAS TecNM (migraciones, modelos, Filament resources, jobs), calcula el porcentaje de avance del módulo COMEXTRAS (F.37), detecta componentes faltantes y opcionalmente genera y sube un informe ejecutivo a Google Drive (5 TB).",
        "inputSchema": {
            "type": "object",
            "properties": {
                "upload_to_drive": {
                    "type": "boolean",
                    "description": "Indica si debe subir automáticamente el reporte Markdown a la carpeta técnica de Google Drive (default: true)."
                },
                "repo_dir": {
                    "type": "string",
                    "description": "Ruta absoluta al repositorio local de ATLAS Laravel (opcional)."
                }
            },
            "required": []
        }
    },
    {
        "name": "atlas_drive_upload",
        "description": "Sube un archivo local o documento generado a una carpeta específica en Google Drive (5 TB) usando OAuth 2.0.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "file_path": {
                    "type": "string",
                    "description": "Ruta absoluta al archivo local que se desea subir."
                },
                "target_folder_id": {
                    "type": "string",
                    "description": "ID de la carpeta destino en Google Drive (opcional, usa la carpeta de documentación técnica por defecto)."
                },
                "file_name": {
                    "type": "string",
                    "description": "Nombre con el que se guardará en Drive (opcional, usa el nombre del archivo local)."
                },
                "mime_type": {
                    "type": "string",
                    "description": "MIME type del archivo (default: 'text/markdown')."
                }
            },
            "required": ["file_path"]
        }
    }
]

# ==========================================
# MCP PROMPTS METADATA
# ==========================================

PROMPTS_METADATA = [
    {
        "name": "audit-comextra-pr",
        "description": "Plantilla para auditar un Pull Request de Laravel/Filament contra la Circular 0045 y la matriz de roles v2.1.",
        "arguments": [
            {
                "name": "pr_diff",
                "description": "Contenido del diff o descripción de cambios del PR.",
                "required": True
            }
        ]
    },
    {
        "name": "generate-filament-widget",
        "description": "Guía estructurada para crear un widget analítico de Filament v3 con paleta institucional TecNM y Leaflet/ESRI.",
        "arguments": [
            {
                "name": "widget_topic",
                "description": "Tema del widget (ej. 'MapaRepublica', 'PulsoNacional', 'TasaDictaminacion').",
                "required": True
            }
        ]
    }
]

# ==========================================
# DYNAMIC MCP RESOURCES BUILDER
# ==========================================

def get_dynamic_resources_list() -> List[Dict[str, Any]]:
    """Builds a complete, dynamic list of all available MCP resources in the platform."""
    resources = [
        {
            "uri": "atlas://catalogs/planteles",
            "name": "Catálogo Maestro de Planteles TecNM",
            "description": "Directorio estructurado de los 263 institutos tecnológicos con regiones y tipo.",
            "mimeType": "application/json"
        },
        {
            "uri": "atlas://catalogs/f37_indicadores",
            "name": "Concentrado F.37 de Intercambio y COMEXTRAS",
            "description": "Matriz agregada de indicadores trimestrales de movilidad DVeIA.",
            "mimeType": "application/json"
        }
    ]

    # Add all tables from migrations and proposals
    live_migrations = scan_migration_files()
    all_tables = set(list(live_migrations.keys()) + list(PROPOSED_SCHEMAS.keys()))
    for tbl in sorted(all_tables):
        resources.append({
            "uri": f"atlas://schemas/{tbl}",
            "name": f"Esquema DDL: {tbl}",
            "description": f"Estructura relacional de la tabla '{tbl}' en PostgreSQL / Laravel 11.",
            "mimeType": "text/markdown"
        })

    # Add all atomic cards
    cards = list_atomic_cards()
    for c in cards:
        resources.append({
            "uri": f"atlas://cards/{c['topic_slug']}",
            "name": f"Tarjeta Atómica: {c['title']}",
            "description": f"Paquete denso de conocimiento ({c['token_count_estimate']} tokens).",
            "mimeType": "text/markdown"
        })

    # Add regulatory rules
    for r in ["anticipacion_30_dias", "informe_30_dias", "bloqueo_adeudo", "oficio_m00", "requisitos_expediente"]:
        resources.append({
            "uri": f"atlas://rules/{r}",
            "name": f"Regla Normativa: {r}",
            "description": "Fundamento legal y mandatos de la Circular 0045 y Manual MOO-PR-039.",
            "mimeType": "text/markdown"
        })

    return resources

def handle_resource_read(uri: str) -> str:
    """Dynamically resolves any requested atlas:// resource URI."""
    if uri.startswith("atlas://schemas/"):
        table_name = uri[len("atlas://schemas/"):].strip()
        return get_table_schema(table_name)
    elif uri.startswith("atlas://cards/"):
        topic_slug = uri[len("atlas://cards/"):].strip()
        return handle_quick_context({"topic": topic_slug})
    elif uri.startswith("atlas://rules/"):
        rule_topic = uri[len("atlas://rules/"):].strip()
        return handle_get_regulatory_rule({"rule_topic": rule_topic})
    elif uri == "atlas://catalogs/planteles":
        res = search_planteles_catalog(limit=263)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif uri == "atlas://catalogs/f37_indicadores":
        res = get_f37_indicators(2026)
        return json.dumps(res, indent=2, ensure_ascii=False)
    else:
        return f"Recurso no encontrado: {uri}"

# ==========================================
# HANDLERS
# ==========================================

def handle_quick_context(args: Dict[str, Any]) -> str:
    topic = args.get("topic", "comextras")
    card = get_atomic_card(topic)
    if card:
        return f"## {card['title']}\n\n{card['high_density_content']}\n\n*(Estimación: {card['token_count_estimate']} tokens | Actualizado: {card['updated_at']})*"
    
    all_cards = list_atomic_cards()
    topics = ", ".join([f"`{c['topic_slug']}`" for c in all_cards])
    return f"Tópico no encontrado. Tópicos disponibles: {topics}"

def handle_search_knowledge(args: Dict[str, Any]) -> str:
    query = args.get("query", "")
    source_filter = args.get("source_filter", "todos")
    limit = int(args.get("limit", 3))

    results = search_knowledge(query, source_filter=source_filter, limit=limit)
    if not results:
        return f"No se encontraron coincidencias exactas para: '{query}'. Prueba con términos como 'comextras', 'circular 0045', 'roles', 'planteles', 'neon' o 'excel'."

    out = [f"### Resultados de Búsqueda para: *{query}* ({len(results)} fragmentos)\n"]
    for i, r in enumerate(results, 1):
        out.append(f"#### {i}. {r['doc_title']} — {r['section']}")
        out.append(f"*Origen: {r['source_type']}* | *Ref: {r['reference_link']}*\n")
        out.append(f"```text\n{r['content'].strip()[:1000]}\n```\n")

    return "\n".join(out)

def handle_get_schema(args: Dict[str, Any]) -> str:
    tbl = args.get("table_name", "").lower().strip()
    return get_table_schema(tbl)

def handle_get_regulatory_rule(args: Dict[str, Any]) -> str:
    rule = args.get("rule_topic", "")
    rules = {
        "anticipacion_30_dias": """### Regla No Negociable: Plazo de Solicitud de 30 Días Hábiles
- **Fundamento:** Circular 0045/COMEXTRAS y Manual de Procedimientos MOO-PR-039 (Numeral 4.2).
- **Mandato:** Todo trámite de comisión al extranjero debe registrarse ante la DVeIA con un mínimo estricto de **30 días hábiles previos** al inicio del viaje.
- **Consecuencia en Sistema:** El sistema ATLAS calcula automáticamente la diferencia en días hábiles. Si la solicitud entra con < 30 días, el sistema la rechaza automáticamente con el código `ERR_PLAZO_EXTEMPORANEO` salvo oficio de excepción de la Dirección General.""",

        "informe_30_dias": """### Regla No Negociable: Entrega de Informe Final en 30 Días Hábiles
- **Fundamento:** Manual MOO-PR-039 (Numeral 6.1).
- **Mandato:** Al concluir la comisión, el beneficiario dispone de máximo **30 días hábiles** para entregar el Informe Pormenorizado de Actividades, constancias de participación y comprobación de pasajes.
- **Consecuencia en Sistema:** Al día hábil 31 sin informe cargado, el estatus pasa a `EN_MORA_DE_INFORME`.""",

        "bloqueo_adeudo": """### Regla No Negociable: Bloqueo Institucional por Adeudo de Informes
- **Fundamento:** Circular 0045/COMEXTRAS (Disposición Transitoria 3).
- **Mandato:** Si un plantel tiene docentes o directivos con informes de comisión pendientes de comprobar fuera de plazo, **la Dirección General no autorizará ninguna nueva comisión** para dicho plantel.
- **Consecuencia en Sistema:** Flag `bloqueado_por_adeudo = true` a nivel plantel. Ningún usuario de ese instituto puede radicar nuevas comisiones hasta que se liquide el adeudo.""",

        "oficio_m00": """### Regla Institucional: Oficio Resolutivo Formato M00
- **Fundamento:** Estatuto Orgánico del TecNM.
- **Mandato:** Solo la Dirección General del TecNM (o la DVeIA por delegación de facultades) puede emitir la resolución formal.
- **Estructura Oficial:** `M00/XXXX/2026`, donde `XXXX` es el consecutivo anual nacional foliado y validado criptográficamente con código QR en ATLAS.""",

        "requisitos_expediente": """### Requisitos Obligatorios del Expediente COMEXTRA
1. Carta de invitación oficial de la institución extranjera receptora (con traducción oficial si es idioma distinto al español).
2. Aval firmado por el Director del Instituto Tecnológico de origen (o Subdirector con firma por ausencia justificada).
3. Dictamen favorable de la Academia o Consejo Técnico respectivo.
4. Póliza de seguro de gastos médicos mayores internacional con cobertura de repatriación.
5. Justificación del impacto académico y vinculación con los planes de estudio del TecNM."""
    }

    return rules.get(rule, f"Regla no identificada. Consulta disponibles: {list(rules.keys())}")

def handle_prompt_get(name: str, arguments: Dict[str, Any]) -> Dict[str, Any]:
    """Generates prompt template messages."""
    if name == "audit-comextra-pr":
        pr_diff = arguments.get("pr_diff", "")
        return {
            "description": "Auditoría de Pull Request según Circular 0045 y Matriz de Roles v2.1",
            "messages": [
                {
                    "role": "user",
                    "content": {
                        "type": "text",
                        "text": f"""Actúa como el Auditor Senior del Órgano Interno de Control (OIC / R6) y Arquitecto TecNM.
Audita el siguiente código o migración de ATLAS contra las siguientes reglas no negociables:
1. ¿Respeta el plazo de 30 días hábiles (Circular 0045 Numeral 4.2)?
2. ¿Garantiza el aislamiento multi-tenant por `clave_plantel`?
3. ¿Registra las transacciones críticas en la bitácora inmutable `rep_dictamenes_log` con IP y snapshot JSON?
4. ¿Usa concurrencia optimista (`lock_version`)?

Código a auditar:
```
{pr_diff}
```"""
                    }
                }
            ]
        }
    elif name == "generate-filament-widget":
        topic = arguments.get("widget_topic", "PulsoNacional")
        blueprint = get_filament_blueprint("comextra", "widget")
        return {
            "description": f"Creación de Widget Filament v3 para {topic}",
            "messages": [
                {
                    "role": "user",
                    "content": {
                        "type": "text",
                        "text": f"""Crea un widget analítico de Filament v3 para el tema '{topic}' en la plataforma ATLAS TecNM.
Asegúrate de:
1. Usar los tokens de diseño oficiales: Azul Marino (#1B396A), Tinto (#9B2247), Verde (#285C4D), Oro (#D4AF37).
2. Definir polling interval de 30s.
3. Seguir este blueprint institucional:
{blueprint}"""
                    }
                }
            ]
        }
    return {"description": "Prompt desconocido", "messages": []}

def process_tool_call(name: str, arguments: Dict[str, Any]) -> str:
    logging.info(f"Tool called: {name} with args: {arguments}")
    
    # Just-In-Time cache check: refreshes local docs & repo delta if modified
    if name in ("atlas_quick_context", "atlas_search_knowledge", "atlas_get_schema"):
        ensure_knowledge_fresh()
    
    if name == "atlas_quick_context":
        return handle_quick_context(arguments)
    elif name == "atlas_search_knowledge":
        return handle_search_knowledge(arguments)
    elif name == "atlas_get_schema":
        return handle_get_schema(arguments)
    elif name == "atlas_get_regulatory_rule":
        return handle_get_regulatory_rule(arguments)
    elif name == "atlas_generate_diagram":
        dtype = arguments.get("diagram_type", "erd")
        feat = arguments.get("feature", "comextra")
        return generate_mermaid_diagram(dtype, feat)
    elif name == "atlas_sync_drive":
        force = arguments.get("force", False)
        res = sync_knowledge_sources(force=force)
        inc_res = sync_all_sources_incremental()
        res["incremental_sync"] = inc_res
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_drive_list":
        folder_id = arguments.get("folder_id")
        res = list_drive_contents(folder_id=folder_id)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_drive_create_folder":
        folder_name = arguments.get("folder_name", "Nuevo_Folder")
        parent_id = arguments.get("parent_folder_id")
        res = create_drive_folder(folder_name=folder_name, parent_id=parent_id)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_drive_move_file":
        file_id = arguments.get("file_id")
        target_id = arguments.get("target_folder_id")
        res = move_drive_file(file_id=file_id, target_folder_id=target_id)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_drive_organize":
        import importlib
        import drive_connector
        importlib.reload(drive_connector)
        folder_id = arguments.get("folder_id")
        res = drive_connector.auto_organize_drive(folder_id=folder_id)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_inspect_excel_columns":
        alias = arguments.get("file_alias_or_path", "comextras")
        sheet = arguments.get("sheet_name")
        hrow = arguments.get("header_row")
        res = inspect_excel_metadata(alias, sheet, header_row=hrow)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_sample_excel":
        alias = arguments.get("file_alias_or_path", "comextras")
        sheet = arguments.get("sheet_name")
        query = arguments.get("query")
        limit = int(arguments.get("limit", 3))
        res = sample_excel_rows(alias, sheet, query=query, limit=limit)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_validate_solicitud":
        res = validate_solicitud_compliance(arguments)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_calculate_deadlines":
        f_ini = arguments.get("fecha_inicio", "")
        f_fin = arguments.get("fecha_fin", "")
        res = calculate_deadlines(f_ini, f_fin)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_get_catalog_planteles":
        query = arguments.get("query")
        limit = int(arguments.get("limit", 5))
        res = search_planteles_catalog(query, limit)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_get_filament_blueprint":
        feat = arguments.get("feature", "comextra")
        ctype = arguments.get("component_type", "resource")
        return get_filament_blueprint(feat, ctype)
    elif name == "atlas_drive_read_content":
        fid = arguments.get("file_id", "")
        res = read_drive_file_content(fid)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_validate_mexican_id":
        id_type = arguments.get("id_type", "curp")
        id_val = arguments.get("id_value", "")
        res = validate_mexican_id(id_type, id_val)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_get_f37_indicators":
        year = int(arguments.get("year", 2026))
        sheet = arguments.get("sheet_name")
        res = get_f37_indicators(year=year, sheet_name=sheet)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_generate_laravel_scaffold":
        tbl = arguments.get("table_name", "cat_modulos")
        feat = arguments.get("feature_name", "Modulo TecNM")
        fields = arguments.get("fields")
        res = generate_laravel_scaffold(table_name=tbl, fields=fields, feature_name=feat)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_check_db_health":
        res = check_database_health()
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_comextras_status_report":
        upload = arguments.get("upload_to_drive", True)
        rdir = arguments.get("repo_dir")
        kwargs = {"upload_to_drive": upload}
        if rdir:
            kwargs["repo_dir"] = rdir
        res = generate_comextras_status_report(**kwargs)
        return json.dumps(res, indent=2, ensure_ascii=False)
    elif name == "atlas_drive_upload":
        fpath = arguments.get("file_path", "")
        tfid = arguments.get("target_folder_id")
        fname = arguments.get("file_name")
        mtype = arguments.get("mime_type", "text/markdown")
        res = upload_drive_file(file_path=fpath, target_folder_id=tfid, file_name=fname, mime_type=mtype)
        return json.dumps(res, indent=2, ensure_ascii=False)
    else:
        return f"Herramienta desconocida: {name}"

# ==========================================
# STDIO SERVER RUNNER (JSON-RPC 2.0)
# ==========================================

def run_stdio_server():
    logging.info("Starting atlas-knowledge-mcp server (JSON-RPC 2.0 stdio)...")
    start_background_sync()
    
    while True:
        msg_id = None
        try:
            line = sys.stdin.readline()
            if not line:
                break
            
            line = line.strip()
            if not line:
                continue

            try:
                request = json.loads(line)
            except json.JSONDecodeError as err:
                logging.error(f"Invalid JSON: {err}")
                err_resp = {
                    "jsonrpc": "2.0",
                    "id": None,
                    "error": {"code": -32700, "message": f"Parse error: {str(err)}"}
                }
                sys.stdout.write(json.dumps(err_resp) + "\n")
                sys.stdout.flush()
                continue

            msg_id = request.get("id")
            method = request.get("method")
            params = request.get("params", {})

            # 1. Handshake: initialize
            if method == "initialize":
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {
                        "protocolVersion": "2024-11-05",
                        "capabilities": {
                            "tools": {},
                            "resources": {},
                            "prompts": {}
                        },
                        "serverInfo": {
                            "name": "atlas-knowledge-mcp",
                            "version": "2.1.0"
                        }
                    }
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            # 2. Notification: initialized (no response required)
            elif method == "notifications/initialized":
                logging.info("Client handshake completed successfully.")

            # 3. Ping
            elif method == "ping":
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {}
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            # 4. List available tools
            elif method == "tools/list":
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {
                        "tools": TOOLS_METADATA
                    }
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            # 5. Call tool (with isError: true exception safety)
            elif method == "tools/call":
                tool_name = params.get("name")
                tool_args = params.get("arguments", {})
                
                try:
                    tool_output = process_tool_call(tool_name, tool_args)
                    is_err = False
                except Exception as e:
                    logging.error(f"Tool {tool_name} execution error: {e}", exc_info=True)
                    tool_output = f"Error ejecutando '{tool_name}': {str(e)}"
                    is_err = True

                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {
                        "content": [
                            {
                                "type": "text",
                                "text": tool_output
                            }
                        ],
                        "isError": is_err
                    }
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            # 6. Resources: list (Dynamic)
            elif method == "resources/list":
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {
                        "resources": get_dynamic_resources_list()
                    }
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            # 7. Resources: read (Dynamic)
            elif method == "resources/read":
                uri = params.get("uri", "")
                content_text = handle_resource_read(uri)
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {
                        "contents": [
                            {
                                "uri": uri,
                                "mimeType": "text/plain",
                                "text": content_text
                            }
                        ]
                    }
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            # 8. Prompts: list
            elif method == "prompts/list":
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {
                        "prompts": PROMPTS_METADATA
                    }
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            # 9. Prompts: get
            elif method == "prompts/get":
                prompt_name = params.get("name", "")
                prompt_args = params.get("arguments", {})
                prompt_data = handle_prompt_get(prompt_name, prompt_args)
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": prompt_data
                }
                sys.stdout.write(json.dumps(response) + "\n")
                sys.stdout.flush()

            else:
                # Unsupported method
                if msg_id is not None:
                    response = {
                        "jsonrpc": "2.0",
                        "id": msg_id,
                        "error": {
                            "code": -32601,
                            "message": f"Method not found: {method}"
                        }
                    }
                    sys.stdout.write(json.dumps(response) + "\n")
                    sys.stdout.flush()

        except Exception as e:
            logging.error(f"Unexpected error in server loop: {str(e)}", exc_info=True)
            if msg_id is not None:
                err_resp = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "error": {
                        "code": -32603,
                        "message": f"Internal server error: {str(e)}"
                    }
                }
                sys.stdout.write(json.dumps(err_resp) + "\n")
                sys.stdout.flush()

# ==========================================
# SSE / HTTP SERVER (Session-Based MCP Queue Dispatcher)
# ==========================================

_SSE_SESSIONS: Dict[str, queue.Queue] = {}

def run_sse_server(host: str = "0.0.0.0", port: int = 8000):
    """Runs high-performance HTTP / SSE transport server with Session Queue Dispatcher."""
    from flask import Flask, Response, request, jsonify

    app = Flask("atlas-knowledge-mcp")
    start_background_sync()

    @app.route("/", methods=["GET"])
    def index():
        return jsonify({
            "name": "atlas-knowledge-mcp",
            "version": "2.1.0",
            "status": "healthy",
            "transport": "SSE / HTTP (Anthropic MCP Compliant)",
            "tools_count": len(TOOLS_METADATA),
            "prompts_count": len(PROMPTS_METADATA),
            "active_sse_sessions": len(_SSE_SESSIONS)
        })

    @app.route("/sse", methods=["GET"])
    def sse_endpoint():
        session_id = uuid.uuid4().hex
        msg_queue: queue.Queue = queue.Queue()
        _SSE_SESSIONS[session_id] = msg_queue

        def event_stream():
            try:
                # 1. Emit endpoint discovery event with session ID
                yield f"event: endpoint\ndata: /messages?sessionId={session_id}\n\n"
                
                # 2. Main event loop
                while True:
                    try:
                        msg = msg_queue.get(timeout=15)
                        yield f"event: message\ndata: {json.dumps(msg)}\n\n"
                    except queue.Empty:
                        yield ": heartbeat\n\n"
            finally:
                _SSE_SESSIONS.pop(session_id, None)
                logging.info(f"[SSE] Session {session_id} disconnected.")

        return Response(event_stream(), mimetype="text/event-stream")

    @app.route("/messages", methods=["POST"])
    def messages_endpoint():
        session_id = request.args.get("sessionId")
        body = request.get_json(force=True, silent=True) or {}
        msg_id = body.get("id")
        method = body.get("method")
        params = body.get("params", {})

        if method == "initialize":
            res = {
                "jsonrpc": "2.0",
                "id": msg_id,
                "result": {
                    "protocolVersion": "2024-11-05",
                    "capabilities": {"tools": {}, "resources": {}, "prompts": {}},
                    "serverInfo": {"name": "atlas-knowledge-mcp", "version": "2.1.0"}
                }
            }
        elif method == "tools/list":
            res = {"jsonrpc": "2.0", "id": msg_id, "result": {"tools": TOOLS_METADATA}}
        elif method == "tools/call":
            tool_name = params.get("name")
            tool_args = params.get("arguments", {})
            try:
                out = process_tool_call(tool_name, tool_args)
                is_err = False
            except Exception as e:
                out = f"Error ejecutando '{tool_name}': {str(e)}"
                is_err = True
            res = {"jsonrpc": "2.0", "id": msg_id, "result": {"content": [{"type": "text", "text": out}], "isError": is_err}}
        elif method == "resources/list":
            res = {"jsonrpc": "2.0", "id": msg_id, "result": {"resources": get_dynamic_resources_list()}}
        elif method == "resources/read":
            uri = params.get("uri", "")
            out = handle_resource_read(uri)
            res = {"jsonrpc": "2.0", "id": msg_id, "result": {"contents": [{"uri": uri, "mimeType": "text/plain", "text": out}]}}
        elif method == "prompts/list":
            res = {"jsonrpc": "2.0", "id": msg_id, "result": {"prompts": PROMPTS_METADATA}}
        elif method == "prompts/get":
            pname = params.get("name", "")
            pargs = params.get("arguments", {})
            pdata = handle_prompt_get(pname, pargs)
            res = {"jsonrpc": "2.0", "id": msg_id, "result": pdata}
        else:
            res = {"jsonrpc": "2.0", "id": msg_id, "result": {}}

        # If connected via active SSE session, push event to the stream
        if session_id and session_id in _SSE_SESSIONS:
            _SSE_SESSIONS[session_id].put(res)

        return jsonify(res), 200

    @app.route("/api/webhooks/github", methods=["POST"])
    def webhook_github():
        logging.info("[Webhook] Received GitHub Push notification. Triggering re-index...")
        res = sync_all_sources_incremental()
        return jsonify({"status": "reindexed", "details": res})

    @app.route("/api/webhooks/gdrive", methods=["POST"])
    def webhook_gdrive():
        logging.info("[Webhook] Received Google Drive notification. Syncing Drive...")
        sync_knowledge_sources(force=True)
        res = sync_all_sources_incremental()
        return jsonify({"status": "drive_synced", "details": res})

    logging.info(f"Starting SSE / HTTP Session server on http://{host}:{port} ...")
    app.run(host=host, port=port, threaded=True)

# ==========================================
# MAIN ENTRYPOINT
# ==========================================

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="ATLAS Knowledge Hub MCP Server")
    parser.add_argument("--transport", choices=["stdio", "sse"], default="stdio", help="Transport mode (default: stdio)")
    parser.add_argument("--host", default="0.0.0.0", help="Host address for SSE transport")
    parser.add_argument("--port", type=int, default=8000, help="Port for SSE transport")

    args = parser.parse_args()

    if args.transport == "sse":
        run_sse_server(host=args.host, port=args.port)
    else:
        run_stdio_server()
