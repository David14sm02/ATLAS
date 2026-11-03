# ATLAS Knowledge Hub MCP Server (`atlas-knowledge-mcp`)

Servidor centralizado y oráculo de contexto para la plataforma **ATLAS TECNM** compatible con el estándar abierto **Model Context Protocol (MCP)** de Anthropic.

Permite a cualquier editor con IA (**Cursor, VS Code con Cline/Roo, Claude Code CLI, Codex y Antigravity**) consultar esquemas de base de datos, normativas oficiales (Circular 0045, MOO-PR-039), generar diagramas Mermaid y buscar información sin tener que arrastrar archivos masivos como el Excel de 22 MB o PDFs escaneados, reduciendo el consumo de tokens en más de un **95%**.

---

## 🛠️ Herramientas Disponibles (MCP Tools v2.0)

| Herramienta | Descripción | Parámetros Clave | Salida Estimada |
|---|---|---|:---:|
| `atlas_quick_context` | Retorna una Tarjeta Atómica de Conocimiento condensada | `topic`: `'comextras'`, `'roles_v2_1'`, etc. | < 600 tokens |
| `atlas_search_knowledge` | Búsqueda BM25 en documentación, PDFs, Word, Excel y Git | `query`: texto libre, `source_filter`: `'todos'` | ~800 tokens |
| `atlas_get_schema` | Retorna el esquema DDL inspeccionando migraciones en vivo | `table_name`: `'cat_planteles'`, `'com_solicitudes'`, etc. | < 400 tokens |
| `atlas_get_regulatory_rule` | Consulta articulado oficial de COMEXTRA (Circular 0045) | `rule_topic`: `'anticipacion_30_dias'`, `'bloqueo_adeudo'`, etc. | < 300 tokens |
| `atlas_validate_solicitud` | Validador normativo estricto con cálculo de días hábiles | `fecha_inicio`, `fecha_fin`, `documentos_anexos` | JSON Veredicto |
| `atlas_calculate_deadlines` | Calcula plazos límites oficiales basados en calendario mexicano | `fecha_inicio`, `fecha_fin` | JSON Fechas |
| `atlas_inspect_excel_columns` | Inspecciona metadatos y columnas de Excel masivos (22MB) | `file_alias_or_path`: `'comextras'`, `'f37'` | JSON Columnas |
| `atlas_sample_excel` | Muestra filas en streaming filtradas por palabras clave | `query`: `'Saltillo'`, `limit`: 3 | JSON Muestra |
| `atlas_get_catalog_planteles` | Busca en los 263 planteles TecNM por estado, clave o tipo | `query`: texto libre, `limit`: 5 | JSON Planteles |
| `atlas_get_filament_blueprint`| Genera código PHP Filament v3 con paleta oficial TecNM | `feature`: `'comextra'`, `component_type`: `'resource'` | PHP Block |
| `atlas_generate_diagram` | Genera código Mermaid institucional validado | `diagram_type`: `'erd'`, `'sequence'`, `feature`: string | Mermaid Block |
| `atlas_sync_drive` | Sincroniza y reindexa Google Drive y GitHub | `force`: boolean | JSON Status |
| `atlas_drive_list` | Lista archivos y carpetas en Google Drive | `folder_id`: string (opcional) | JSON Lista |
| `atlas_drive_create_folder` | Crea subdirectorios temáticos en Drive | `folder_name`: string | JSON Carpeta |
| `atlas_drive_move_file` | Mueve archivos entre carpetas en Drive | `file_id`, `target_folder_id` | JSON Status |
| `atlas_drive_organize` | Organiza automáticamente archivos en 6 carpetas temáticas | `folder_id`: string (opcional) | JSON Log |
| `atlas_drive_read_content` | Lee y extrae texto de Docs/PDFs en Google Drive | `file_id`: string | Texto Plano |
| `atlas_validate_mexican_id` | Valida formalmente CURP (RENAPO) y RFC (SAT) con dígito verificador | `id_type`: `'curp'` o `'rfc'`, `id_value`: string | JSON Validación |
| `atlas_get_f37_indicators` | Extrae métricas agregadas del formato oficial F.37 | `year`: integer (default 2026) | JSON Indicadores |
| `atlas_generate_laravel_scaffold`| Genera Migración, Modelo y Filament Resource con multi-tenancy | `table_name`: string, `feature_name`: string | JSON Archivos PHP |
| `atlas_check_db_health` | Diagnóstico de conexión a Neon PostgreSQL 16 y salud SQLite FTS5 | Ninguno | JSON Diagnóstico |

---

## 📦 MCP Resources & Prompts Nativos (100% Dinámicos)

- **Recursos (`resources/read`)**:
  - `atlas://schemas/{table}`: Resuelve dinámicamente cualquier tabla (ej. `com_solicitudes`, `cat_planteles`, `users`, `rep_bitacora_cargas`).
  - `atlas://cards/{slug}`: Resuelve cualquier tarjeta atómica (`roles_v2_1`, `comextras`, `identidad_visual`, `planteles_regiones`, etc.).
  - `atlas://rules/{topic}`: Resuelve cualquier artículo de la Circular 0045 y MOO-PR-039.
  - `atlas://catalogs/planteles`: Directorio completo de los 263 institutos tecnológicos.
  - `atlas://catalogs/f37_indicadores`: Matriz agregada de indicadores de movilidad.
- **Prompts (`prompts/get`)**:
  - `audit-comextra-pr`: Plantilla de auditoría para OIC / R6 contra la Circular 0045.
  - `generate-filament-widget`: Plantilla para widgets con paleta TecNM y mapas Leaflet.

---

## 🚀 Guía de Instalación y Configuración por Editor

### 1. Cursor IDE
Copia el archivo `cursor_mcp.json` o añade en `.cursor/mcp.json`:
```json
{
  "mcpServers": {
    "atlas-knowledge": {
      "command": "python3",
      "args": [
        "/data/data/com.termux/files/home/tools/atlas_mcp/server.py"
      ],
      "env": {
        "ATLAS_DB_PATH": "/data/data/com.termux/files/home/tools/atlas_mcp/atlas_knowledge.db"
      }
    }
  }
}
```

### 2. VS Code (Cline / Roo Code)
En la configuración de MCP de Cline (`cline_mcp_settings.json`):
```json
{
  "mcpServers": {
    "atlas-knowledge": {
      "command": "python3",
      "args": [
        "/data/data/com.termux/files/home/tools/atlas_mcp/server.py"
      ],
      "env": {
        "ATLAS_DB_PATH": "/data/data/com.termux/files/home/tools/atlas_mcp/atlas_knowledge.db"
      }
    }
  }
}
```

### 3. Claude Code CLI
Ejecuta en tu terminal:
```bash
claude mcp add atlas-knowledge python3 /data/data/com.termux/files/home/tools/atlas_mcp/server.py
```

### 4. Antigravity / Agy CLI
El servidor queda registrado en `~/.gemini/antigravity-cli/mcp/atlas-knowledge/` para llamadas inmediatas del agente.

---

## 🔄 Sincronización con Google Drive
1. Para sincronización en la nube, coloca tu archivo `service_account.json` de Google Cloud en esta carpeta (`/data/data/com.termux/files/home/tools/atlas_mcp/service_account.json`).
2. Si trabajas localmente, el servidor sincroniza de forma automática con la carpeta del proyecto en `/storage/emulated/0/proyecto TecNm/`.
3. Para forzar una reindexación en cualquier momento:
```bash
python3 /data/data/com.termux/files/home/tools/atlas_mcp/indexer.py
```
