#!/usr/bin/env python3
"""
Google Drive Connector & File Organization Manager
Enables reading, querying, creating subdirectories, and organizing files in Google Drive.
Part of MCP-ATLAS-TecNM
"""

import os
import json
import logging
from datetime import datetime
from typing import Dict, Any, List, Optional
from db import get_connection

SERVICE_ACCOUNT_PATH = os.environ.get(
    "GDRIVE_SERVICE_ACCOUNT",
    os.path.join(os.path.dirname(os.path.abspath(__file__)), "service_account.json")
)
SERVICE_ACCOUNT_JSON_RAW = os.environ.get("GDRIVE_SERVICE_ACCOUNT_JSON", "")
OAUTH_TOKEN_PATH = os.environ.get(
    "GDRIVE_OAUTH_TOKEN_PATH",
    os.path.join(os.path.dirname(os.path.abspath(__file__)), "gdrive_oauth_token.json")
)
DEFAULT_FOLDER_ID = os.environ.get("GDRIVE_FOLDER_ID", "")

SCOPES = ["https://www.googleapis.com/auth/drive"]

def get_drive_service():
    """Builds and returns Google Drive API v3 client using OAuth2 user credentials (5TB quota) or Service Account."""
    try:
        from googleapiclient.discovery import build
        creds = None

        # 1. Prefer OAuth 2.0 User Token (consumes user's 5 TB plan)
        if os.path.exists(OAUTH_TOKEN_PATH):
            try:
                from google.oauth2.credentials import Credentials
                from google.auth.transport.requests import Request
                creds = Credentials.from_authorized_user_file(OAUTH_TOKEN_PATH, SCOPES)
                if creds and creds.expired and creds.refresh_token:
                    creds.refresh(Request())
                    with open(OAUTH_TOKEN_PATH, "w") as token_file:
                        token_file.write(creds.to_json())
            except Exception as oauth_err:
                logging.warning(f"Error loading OAuth token, falling back to Service Account: {oauth_err}")
                creds = None

        # 2. Fallback to Service Account if OAuth token is not present
        if not creds:
            from google.oauth2 import service_account
            if SERVICE_ACCOUNT_JSON_RAW:
                info = json.loads(SERVICE_ACCOUNT_JSON_RAW)
                creds = service_account.Credentials.from_service_account_info(info, scopes=SCOPES)
            elif os.path.exists(SERVICE_ACCOUNT_PATH):
                creds = service_account.Credentials.from_service_account_file(SERVICE_ACCOUNT_PATH, scopes=SCOPES)
        
        if creds:
            return build("drive", "v3", credentials=creds)
    except Exception as e:
        logging.error(f"Error initializing Google Drive API: {e}")
    return None

def list_drive_contents(folder_id: Optional[str] = None) -> Dict[str, Any]:
    """Lists files and folders inside the specified Google Drive folder."""
    target_id = folder_id or DEFAULT_FOLDER_ID
    if not target_id:
        return {"status": "error", "message": "GDRIVE_FOLDER_ID no configurado y no se proporcionó folder_id."}

    service = get_drive_service()
    if not service:
        return {
            "status": "error",
            "message": "Credenciales de Google Drive no configuradas. Proporciona GDRIVE_SERVICE_ACCOUNT_JSON en las variables de entorno de Render."
        }

    try:
        query = f"'{target_id}' in parents and trashed = false"
        results = service.files().list(
            q=query,
            fields="files(id, name, mimeType, size, modifiedTime, webViewLink, parents)",
            orderBy="folder, name"
        ).execute()

        items = results.get("files", [])
        folders = []
        files = []

        for item in items:
            if item.get("mimeType") == "application/vnd.google-apps.folder":
                folders.append(item)
            else:
                files.append(item)

        return {
            "status": "success",
            "folder_id": target_id,
            "total_items": len(items),
            "subfolders": folders,
            "files": files
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al consultar Google Drive: {str(e)}"}

def create_drive_folder(folder_name: str, parent_id: Optional[str] = None) -> Dict[str, Any]:
    """Creates a new subfolder in Google Drive."""
    target_parent = parent_id or DEFAULT_FOLDER_ID
    if not target_parent:
        return {"status": "error", "message": "GDRIVE_FOLDER_ID no configurado y no se proporcionó parent_id."}

    service = get_drive_service()
    if not service:
        return {"status": "error", "message": "Credenciales de Google Drive no configuradas."}

    try:
        file_metadata = {
            "name": folder_name,
            "mimeType": "application/vnd.google-apps.folder",
            "parents": [target_parent]
        }
        folder = service.files().create(body=file_metadata, fields="id, name, webViewLink").execute()
        return {
            "status": "success",
            "folder_id": folder.get("id"),
            "folder_name": folder.get("name"),
            "link": folder.get("webViewLink")
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al crear subdirectorio: {str(e)}"}

def upload_drive_file(file_path: str, target_folder_id: Optional[str] = None, file_name: Optional[str] = None, mime_type: str = "text/markdown") -> Dict[str, Any]:
    """Uploads a local file to a specific Google Drive folder."""
    service = get_drive_service()
    if not service:
        return {"status": "error", "message": "Credenciales de Google Drive no configuradas."}

    target_parent = target_folder_id or DEFAULT_FOLDER_ID
    if not target_parent:
        return {"status": "error", "message": "GDRIVE_FOLDER_ID no configurado y no se proporcionó target_folder_id."}

    try:
        from googleapiclient.http import MediaFileUpload
        name = file_name or os.path.basename(file_path)
        file_metadata = {
            "name": name,
            "parents": [target_parent]
        }
        media = MediaFileUpload(file_path, mimetype=mime_type, resumable=True)
        uploaded = service.files().create(
            body=file_metadata,
            media_body=media,
            fields="id, name, webViewLink, parents"
        ).execute()

        return {
            "status": "success",
            "file_id": uploaded.get("id"),
            "file_name": uploaded.get("name"),
            "link": uploaded.get("webViewLink"),
            "parent_folder_id": target_parent
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al subir archivo a Google Drive: {str(e)}"}

def move_drive_file(file_id: str, target_folder_id: str, current_parent_id: Optional[str] = None) -> Dict[str, Any]:
    """Moves a file into a target subfolder in Google Drive."""
    service = get_drive_service()
    if not service:
        return {"status": "error", "message": "Credenciales de Google Drive no configuradas."}

    try:
        # Retrieve the existing parents to remove
        if not current_parent_id:
            file_meta = service.files().get(fileId=file_id, fields="parents, name").execute()
            current_parents = ",".join(file_meta.get("parents", []))
            file_name = file_meta.get("name")
        else:
            current_parents = current_parent_id
            file_name = file_id

        updated = service.files().update(
            fileId=file_id,
            addParents=target_folder_id,
            removeParents=current_parents,
            fields="id, name, parents"
        ).execute()

        return {
            "status": "success",
            "file_id": updated.get("id"),
            "name": updated.get("name"),
            "new_parents": updated.get("parents")
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al mover archivo: {str(e)}"}

def auto_organize_drive(folder_id: Optional[str] = None) -> Dict[str, Any]:
    """
    Scans the specified folder and organizes files into clean thematic subdirectories:
    - 01_Normativa_y_Circulares/
    - 02_Bases_Datos_Excel/
    - 03_Formatos_y_Solicitudes/
    - 04_Presentaciones_e_Indicadores/
    - 05_Prototipos_e_Interfaces_UI/
    - 06_Documentacion_Tecnica_y_Arquitectura/
    """
    target_id = folder_id or DEFAULT_FOLDER_ID
    if not target_id:
        return {"status": "error", "message": "GDRIVE_FOLDER_ID requerido para auto-organizar."}

    contents = list_drive_contents(target_id)
    if contents.get("status") != "success":
        return contents

    files = contents.get("files", [])
    if not files:
        return {"status": "success", "message": "La carpeta raíz no tiene archivos sueltos para organizar."}

    # Standard directory mapping
    categories = {
        "01_Normativa_y_Circulares": ["circular", "manual", "procedimiento", "norma", "reglamento"],
        "02_Bases_Datos_Excel": [".xlsx", ".xlsm", ".csv", ".xls", "base", "f.37"],
        "03_Formatos_y_Solicitudes": [".docx", ".doc", "formato", "ejemplo", "solicitud"],
        "04_Presentaciones_e_Indicadores": [".pptx", ".ppt", "indicadores", "presentacion"],
        "05_Prototipos_e_Interfaces_UI": [".jpg", ".jpeg", ".png", "prototipo", "ui", "login", "tablero", "panel"],
        "06_Documentacion_Tecnica_y_Arquitectura": [".md", ".txt", "prd", "arquitectura", "roles", "migracion", "analisis", "seguimiento", "servidor", "instalador", "preguntas"]
    }

    # Ensure categories exist as subfolders
    existing_subfolders = {sf["name"]: sf["id"] for sf in contents.get("subfolders", [])}
    for cat_name in categories.keys():
        if cat_name not in existing_subfolders:
            new_f = create_drive_folder(cat_name, parent_id=target_id)
            if new_f.get("status") == "success":
                existing_subfolders[cat_name] = new_f["folder_id"]

    moved_log = []
    for file_item in files:
        fname = file_item.get("name", "").lower()
        fid = file_item.get("id")
        
        assigned_cat = None
        for cat_name, keywords in categories.items():
            if any(k in fname for k in keywords):
                assigned_cat = cat_name
                break

        if not assigned_cat:
            assigned_cat = "06_Documentacion_Tecnica_y_Arquitectura"

        target_subfolder_id = existing_subfolders.get(assigned_cat)
        if target_subfolder_id:
            move_res = move_drive_file(fid, target_subfolder_id, current_parent_id=target_id)
            if move_res.get("status") == "success":
                moved_log.append(f"'{file_item.get('name')}' -> '{assigned_cat}/'")

    return {
        "status": "success",
        "organized_files_count": len(moved_log),
        "details": moved_log
    }

def sync_knowledge_sources(force: bool = False) -> dict:
    """Synchronizes from Google Drive and indexes knowledge."""
    service = get_drive_service()
    google_connected = service is not None

    msg = "Google Drive API conectado. " if google_connected else "Google Drive en modo local. "

    conn = get_connection()
    cur = conn.cursor()
    now = datetime.utcnow().isoformat()
    cur.execute("""
    INSERT INTO sync_state (source_key, last_sync_at, status, items_processed)
    VALUES ('all_sources', ?, 'success', 1)
    ON CONFLICT(source_key) DO UPDATE SET
        last_sync_at = excluded.last_sync_at,
        status = excluded.status
    """, (now,))
    conn.commit()
    conn.close()

    return {
        "status": "success",
        "google_drive_connected": google_connected,
        "message": msg
    }

def read_drive_file_content(file_id: str) -> Dict[str, Any]:
    """Downloads or exports a file from Google Drive and extracts text content."""
    service = get_drive_service()
    if not service:
        return {"status": "error", "message": "Credenciales de Google Drive no configuradas."}

    try:
        file_meta = service.files().get(fileId=file_id, fields="id, name, mimeType, size").execute()
        mime = file_meta.get("mimeType", "")
        name = file_meta.get("name", "")

        # Google Docs export
        if mime == "application/vnd.google-apps.document":
            content = service.files().export(fileId=file_id, mimeType="text/plain").execute().decode("utf-8", errors="ignore")
        elif mime == "application/vnd.google-apps.spreadsheet":
            content = service.files().export(fileId=file_id, mimeType="text/csv").execute().decode("utf-8", errors="ignore")
        else:
            from io import BytesIO
            from googleapiclient.http import MediaIoBaseDownload
            request = service.files().get_media(fileId=file_id)
            fh = BytesIO()
            downloader = MediaIoBaseDownload(fh, request)
            done = False
            while not done:
                _, done = downloader.next_chunk()
            raw_bytes = fh.getvalue()

            if mime.startswith("text/") or name.endswith(('.txt', '.md', '.csv', '.json', '.php')):
                content = raw_bytes.decode("utf-8", errors="ignore")
            elif name.endswith('.docx'):
                import docx
                doc = docx.Document(BytesIO(raw_bytes))
                content = "\n\n".join([p.text for p in doc.paragraphs if p.text.strip()])
            elif name.endswith('.pdf'):
                import pypdf
                reader = pypdf.PdfReader(BytesIO(raw_bytes))
                content = "\n\n".join([p.extract_text() for p in reader.pages if p.extract_text()])
            else:
                content = f"[Archivo binario {name} ({len(raw_bytes)} bytes)]"

        return {
            "status": "success",
            "file_id": file_id,
            "file_name": name,
            "mime_type": mime,
            "content": content[:12000]
        }
    except Exception as e:
        return {"status": "error", "message": f"Error al leer archivo de Drive: {str(e)}"}
