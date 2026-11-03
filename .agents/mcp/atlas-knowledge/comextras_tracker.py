#!/usr/bin/env python3
"""
COMEXTRAS Technical Progress Tracker & Executive Reporter
Audits repository files (migrations, models, Filament resources, policies, jobs)
and generates real-time engineering reports with auto-upload to Google Drive.
Part of atlas-knowledge-mcp
"""

import os
import glob
from datetime import datetime
from typing import Dict, Any, Optional

from drive_connector import upload_drive_file
from laravel_introspector import DEFAULT_REPO_DIR

# Thematic documentation folder in Google Drive
DRIVE_DOCS_FOLDER_ID = os.environ.get(
    "GDRIVE_DOCS_FOLDER_ID",
    "1YNDsPfrxV5473c2IpGvb_JmCxJKnm9u2"  # 06_Documentacion_Tecnica_y_Arquitectura
)

LOCAL_REPORTS_DIR = os.environ.get(
    "ATLAS_LOCAL_REPORTS_DIR",
    os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..", "docs", "ejecutivos"))
)

def audit_comextras_codebase(repo_dir: str = DEFAULT_REPO_DIR) -> Dict[str, Any]:
    """Inspects the ATLAS Laravel 11 repository to evaluate COMEXTRAS completion status."""
    components = [
        {
            "id": "CAT_PLANTELES",
            "categoria": "Catálogos Maestros",
            "componente": "Migración y Modelo CatPlantel (Multi-Tenancy)",
            "rutas_esperadas": [
                "database/migrations/*_create_cat_planteles_table.php",
                "app/Models/CatPlantel.php"
            ],
            "peso": 10
        },
        {
            "id": "CAT_PERIODOS",
            "categoria": "Catálogos Maestros",
            "componente": "Migración y Modelo CatPeriodo (Trimestres Q1-Q4)",
            "rutas_esperadas": [
                "database/migrations/*_create_cat_periodos_table.php",
                "app/Models/CatPeriodo.php"
            ],
            "peso": 5
        },
        {
            "id": "REP_REGISTROS_BASE",
            "categoria": "Capa Analítica (F.37 Agregado)",
            "componente": "Tabla de hechos agregada con totales storedAs y JSONB",
            "rutas_esperadas": [
                "database/migrations/*_create_rep_registros_base_table.php",
                "app/Models/RepRegistroBase.php"
            ],
            "peso": 15
        },
        {
            "id": "COMEXTRAS_RESOURCE_AGREGADO",
            "categoria": "Interfaz Filament v3 (F.37)",
            "componente": "ComextrasResource con tema TecNM y aislamiento por plantel",
            "rutas_esperadas": [
                "app/Filament/Clusters/IntercambioAcademico/Resources/ComextrasResource.php"
            ],
            "peso": 15
        },
        {
            "id": "COM_SOLICITUDES_MIGRACION",
            "categoria": "Capa Transaccional (Microdatos Nominales)",
            "componente": "Migración granular com_solicitudes (47 campos individuales)",
            "rutas_esperadas": [
                "database/migrations/*_create_com_solicitudes_table.php"
            ],
            "peso": 20
        },
        {
            "id": "COM_SOLICITUD_MODEL",
            "categoria": "Capa Transaccional (Microdatos Nominales)",
            "componente": "Modelo Eloquent ComSolicitud con relaciones y scopes",
            "rutas_esperadas": [
                "app/Models/ComSolicitud.php"
            ],
            "peso": 10
        },
        {
            "id": "ROLES_V2_1_MIGRACION",
            "categoria": "Seguridad y RBAC Institucional",
            "componente": "Actualización de tabla users con cargos oficiales y roles v2.1",
            "rutas_esperadas": [
                "database/migrations/*_update_users_table_roles_v2_1.php",
                "database/migrations/*_create_user_submodulos_table.php"
            ],
            "peso": 10
        },
        {
            "id": "EXCEL_INGESTION_JOB",
            "categoria": "ETL Asíncrono (Hito 4)",
            "componente": "Job encolado ImportComextrasJob con dry-run y validación",
            "rutas_esperadas": [
                "app/Jobs/ImportComextrasJob.php",
                "app/Jobs/ProcesarReporteExcelJob.php"
            ],
            "peso": 10
        },
        {
            "id": "OFICIO_M00_GENERATOR",
            "categoria": "Automatización Documental",
            "componente": "Servicio de compilación de Oficios M00 Word/PDF",
            "rutas_esperadas": [
                "app/Services/ComextraDocumentService.php",
                "app/Services/OficioM00Generator.php"
            ],
            "peso": 5
        }
    ]

    total_peso = sum(c["peso"] for c in components)
    peso_obtenido = 0
    resultados = []

    for c in components:
        encontrado = False
        evidencias = []
        for pat in c["rutas_esperadas"]:
            full_pattern = os.path.join(repo_dir, pat)
            matches = glob.glob(full_pattern)
            if matches:
                encontrado = True
                evidencias.extend([os.path.relpath(m, repo_dir) for m in matches])

        if encontrado:
            peso_obtenido += c["peso"]
            estado = "IMPLEMENTADO"
        else:
            estado = "PENDIENTE"

        resultados.append({
            "id": c["id"],
            "categoria": c["categoria"],
            "componente": c["componente"],
            "estado": estado,
            "peso": c["peso"],
            "evidencias": evidencias
        })

    porcentaje = round((peso_obtenido / total_peso) * 100, 1)

    # Determine next immediate priority task
    pendientes = [r for r in resultados if r["estado"] == "PENDIENTE"]
    siguiente_tarea = pendientes[0] if pendientes else None

    return {
        "repo_dir": repo_dir,
        "porcentaje_avance": porcentaje,
        "puntos_obtenidos": peso_obtenido,
        "puntos_totales": total_peso,
        "siguiente_prioridad": siguiente_tarea,
        "componentes": resultados
    }

def generate_comextras_status_report(
    upload_to_drive: bool = True,
    repo_dir: str = DEFAULT_REPO_DIR
) -> Dict[str, Any]:
    """
    Generates a full Markdown executive engineering report for COMEXTRAS progress
    and optionally uploads it to Google Drive 5TB storage.
    """
    audit = audit_comextras_codebase(repo_dir)
    pct = audit["porcentaje_avance"]
    now_str = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    date_slug = datetime.now().strftime("%Y%m%d_%H%M%S")

    # Build Markdown document
    lines = [
        f"# Reporte Técnico de Avance: Módulo COMEXTRAS (ATLAS TECNM)",
        f"\n**Fecha de Corte:** {now_str}  ",
        f"**Repositorio Auditado:** `Richpol99/ATLAS`  ",
        f"**Porcentaje Global de Avance en Código:** **{pct}%**  \n",
        "---",
        "\n## 1. Semáforo Ejecutivo de Componentes\n",
        "| Categoría | Componente Técnico | Estatus | Ponderación | Evidencia en Código |",
        "|---|---|:---:|:---:|---|"
    ]

    for c in audit["componentes"]:
        badge = "✅ **LISTO**" if c["estado"] == "IMPLEMENTADO" else "⏳ **PENDIENTE**"
        ev_str = f"`{c['evidencias'][0]}`" if c["evidencias"] else "*Sin implementar*"
        lines.append(f"| {c['categoria']} | {c['componente']} | {badge} | {c['peso']}% | {ev_str} |")

    lines.append("\n---\n")
    lines.append("## 2. Próxima Tarea Prioritaria de Ingeniería\n")

    sig = audit["siguiente_prioridad"]
    if sig:
        lines.append(f"> [!IMPORTANT]")
        lines.append(f"> **Siguiente Entrega Requerida:** `{sig['componente']}`")
        lines.append(f"> * Categoría: **{sig['categoria']}**")
        lines.append(f"> * Impacto: **+{sig['peso']}%** hacia la meta de producción.")
        lines.append(f"> * Acción recomendada: Solicitar al MCP el Blueprint o Scaffold con `atlas_get_filament_blueprint` o `atlas_generate_laravel_scaffold`.")
    else:
        lines.append("> [!TIP]")
        lines.append("> **¡Todos los componentes base de COMEXTRAS han sido implementados satisfactoriamente en el repositorio!**")

    lines.append("\n---\n")
    lines.append("## 3. Estado de la Infraestructura y Servicios Satélite\n")
    lines.append("* **Base de Datos Principal:** Neon PostgreSQL 16 (Serverless).")
    lines.append("* **Almacenamiento Cloud:** Google Drive 5 TB (Conexión OAuth 2.0 activa).")
    lines.append("* **Normativa Aplicada:** Circular 0045 y Manual MOO-PR-039 (Regla de 30 días hábiles y cargos oficiales v2.1).")
    lines.append("\n*(Generado automáticamente por el servidor atlas-knowledge-mcp)*\n")

    report_content = "\n".join(lines)
    file_name = f"reporte_avance_comextras_{date_slug}.md"

    # Local save
    os.makedirs(LOCAL_REPORTS_DIR, exist_ok=True)
    local_path = os.path.join(LOCAL_REPORTS_DIR, file_name)
    with open(local_path, "w", encoding="utf-8") as f:
        f.write(report_content)

    drive_result = None
    if upload_to_drive:
        drive_result = upload_drive_file(
            file_path=local_path,
            target_folder_id=DRIVE_DOCS_FOLDER_ID,
            file_name=file_name,
            mime_type="text/markdown"
        )

    return {
        "status": "success",
        "porcentaje_avance": pct,
        "siguiente_prioridad": sig["componente"] if sig else "Ninguna (Completo)",
        "archivo_local": local_path,
        "drive_subida": drive_result,
        "reporte_markdown": report_content
    }
