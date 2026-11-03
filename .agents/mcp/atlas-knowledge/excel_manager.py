#!/usr/bin/env python3
"""
Excel Data Manager for ATLAS Knowledge Base
Fast, memory-efficient inspection, streaming sampler, and F.37 indicator aggregator for massive Excel files (.xlsx, .xlsm).
Part of atlas-knowledge-mcp
"""

import os
import openpyxl
from typing import Dict, Any, List, Optional

DEFAULT_EXCEL_PATHS = {
    "comextras": os.environ.get(
        "ATLAS_EXCEL_COMEXTRA",
        os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..", "docs", "modulos", "3.1_comextras", "formatos_origen", "BASE COMEXTRAS 2026.xlsm"))
    ),
    "f37": os.environ.get(
        "ATLAS_EXCEL_F37",
        os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..", "docs", "modulos", "3.1_comextras", "formatos_origen", "F.37._INTERCAMBIO Y COMEXTRAS _2do trimestre 2026..xlsx"))
    )
}

HEADER_KEYWORDS = [
    "instituto", "nombre", "zona", "región", "region", "entidad",
    "solicitud", "comisión", "comision", "fecha", "oficio", "estado",
    "docente", "estudiante", "clave", "folio", "trimestre", "sector"
]

def resolve_excel_path(file_alias_or_path: Optional[str] = None) -> str:
    """Resolves alias ('comextras', 'f37') or absolute/relative file path."""
    if not file_alias_or_path:
        return DEFAULT_EXCEL_PATHS["comextras"]
    alias = file_alias_or_path.lower().strip()
    if alias in DEFAULT_EXCEL_PATHS:
        return DEFAULT_EXCEL_PATHS[alias]
    if os.path.exists(file_alias_or_path):
        return file_alias_or_path
    
    default_dir = "/storage/emulated/0/proyecto TecNm"
    candidate = os.path.join(default_dir, file_alias_or_path)
    if os.path.exists(candidate):
        return candidate
    return file_alias_or_path

def detect_best_header_row(sheet, max_scan: int = 10) -> int:
    """
    Intelligently finds the row index (0-indexed) with the highest concentration of table headers.
    Essential for sheets like F.37 where table headers start on row 5.
    """
    best_row = 0
    max_score = -1

    for row_idx, row in enumerate(sheet.iter_rows(max_row=max_scan, values_only=True)):
        non_empty = [str(c).strip().lower() for c in row if c is not None and str(c).strip()]
        if not non_empty:
            continue

        # Score by count of non-empty cells and match with common table header keywords
        keyword_matches = sum(1 for val in non_empty if any(k in val for k in HEADER_KEYWORDS))
        score = len(non_empty) + (keyword_matches * 3)

        if score > max_score:
            max_score = score
            best_row = row_idx

    return best_row

def inspect_excel_metadata(
    file_alias_or_path: Optional[str] = None,
    sheet_name: Optional[str] = None,
    header_row: Optional[int] = None
) -> Dict[str, Any]:
    """
    Safely inspects workbook metadata, sheets, and column headers with smart offset detection.
    """
    fpath = resolve_excel_path(file_alias_or_path)
    if not os.path.exists(fpath):
        return {"status": "error", "message": f"Archivo Excel no encontrado en: {fpath}"}

    file_size_mb = round(os.path.getsize(fpath) / (1024 * 1024), 2)

    try:
        wb = openpyxl.load_workbook(fpath, read_only=True, data_only=True)
        sheet_names = wb.sheetnames
        selected_sheet = sheet_name if sheet_name in sheet_names else sheet_names[0]
        sheet = wb[selected_sheet]

        actual_header_idx = header_row if header_row is not None else detect_best_header_row(sheet)

        headers = []
        sample_types = {}

        # Scan rows to get headers and sample data types
        for i, row in enumerate(sheet.iter_rows(max_row=actual_header_idx + 3, values_only=True)):
            if i == actual_header_idx:
                headers = [str(cell).strip().replace('\n', ' ') if cell is not None else f"Col_{idx+1}" for idx, cell in enumerate(row)]
            elif i == actual_header_idx + 1:
                for idx, cell in enumerate(row):
                    h = headers[idx] if idx < len(headers) else f"Col_{idx+1}"
                    sample_types[h] = type(cell).__name__ if cell is not None else "null"

        wb.close()

        return {
            "status": "success",
            "file_name": os.path.basename(fpath),
            "file_size_mb": file_size_mb,
            "selected_sheet": selected_sheet,
            "detected_header_row": actual_header_idx,
            "all_sheets": sheet_names,
            "total_columns": len(headers),
            "headers": headers,
            "sample_column_types": sample_types,
            "estimated_rows": sheet.max_row or "Dinámico / Modo Solo Lectura"
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al inspeccionar Excel: {str(e)}"}

def sample_excel_rows(
    file_alias_or_path: Optional[str] = None,
    sheet_name: Optional[str] = None,
    query: Optional[str] = None,
    limit: int = 3,
    header_row: Optional[int] = None,
    selected_columns: Optional[List[str]] = None
) -> Dict[str, Any]:
    """
    Streams and retrieves up to `limit` sample rows with smart header detection and search filtering.
    """
    fpath = resolve_excel_path(file_alias_or_path)
    if not os.path.exists(fpath):
        return {"status": "error", "message": f"Archivo Excel no encontrado en: {fpath}"}

    try:
        wb = openpyxl.load_workbook(fpath, read_only=True, data_only=True)
        sheet_names = wb.sheetnames
        selected_sheet = sheet_name if sheet_name in sheet_names else sheet_names[0]
        sheet = wb[selected_sheet]

        actual_header_idx = header_row if header_row is not None else detect_best_header_row(sheet)

        headers = []
        rows = []
        clean_query = query.lower().strip() if query else None

        for idx, row in enumerate(sheet.iter_rows(values_only=True)):
            if idx < actual_header_idx:
                continue
            if idx == actual_header_idx:
                headers = [str(cell).strip().replace('\n', ' ') if cell is not None else f"Col_{cidx+1}" for cidx, cell in enumerate(row)]
                continue

            # Build row dict
            row_dict = {}
            row_matches = False
            for cidx, cell in enumerate(row):
                if cidx >= len(headers):
                    continue
                h = headers[cidx]
                val = str(cell).strip() if cell is not None else ""
                
                if clean_query and clean_query in val.lower():
                    row_matches = True
                
                if not selected_columns or h in selected_columns:
                    row_dict[h] = val

            if clean_query:
                if row_matches:
                    rows.append(row_dict)
            else:
                if any(v for v in row_dict.values()):  # Avoid completely blank rows
                    rows.append(row_dict)

            if len(rows) >= limit:
                break

        wb.close()

        return {
            "status": "success",
            "file_name": os.path.basename(fpath),
            "sheet": selected_sheet,
            "header_row_used": actual_header_idx,
            "matched_rows_count": len(rows),
            "filter_applied": query,
            "rows": rows
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al muestrear filas de Excel: {str(e)}"}

def get_f37_indicators(year: int = 2026, sheet_name: Optional[str] = None) -> Dict[str, Any]:
    """
    Extracts high-level official quarterly indicators directly from F.37 Excel file
    without saturating context (F.37 Intercambio y COMEXTRAS).
    """
    fpath = DEFAULT_EXCEL_PATHS["f37"]
    if not os.path.exists(fpath):
        return {"status": "error", "message": f"Archivo F.37 no encontrado en: {fpath}"}

    try:
        wb = openpyxl.load_workbook(fpath, read_only=True, data_only=True)
        target_sheet = sheet_name or f"CONCENTRADO COMEXTRAS {year}"
        if target_sheet not in wb.sheetnames:
            # Fallback to available sheet
            for s in wb.sheetnames:
                if "CONCENTRADO COMEXTRAS" in s:
                    target_sheet = s
                    break

        sheet = wb[target_sheet]
        periods = []
        metrics = {}

        for row in sheet.iter_rows(min_row=7, max_row=35, values_only=True):
            clean = [str(c).strip() for c in row if c is not None and str(c).strip()]
            if not clean:
                continue

            if clean[0].isdigit() and len(clean) >= 3:
                concept = clean[1]
                values = clean[2:]
                metrics[concept] = values
            elif "COMEXTRAS por Actividad" in clean:
                periods = clean[clean.index("COMEXTRAS por Actividad") + 1:]

        wb.close()

        return {
            "status": "success",
            "formato": "F.37 Intercambio y COMEXTRAS",
            "hoja": target_sheet,
            "periodos_reportados": periods[-8:] if periods else [],
            "indicadores_clave": {
                k: v[-8:] if len(v) >= 8 else v
                for k, v in metrics.items()
                if any(term in k.lower() for term in ["total", "docentes", "estudiantes", "institutos", "federales", "descentralizados", "concurso", "congreso"])
            }
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al extraer indicadores F.37: {str(e)}"}
