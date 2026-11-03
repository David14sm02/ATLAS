#!/usr/bin/env python3
"""
OAuth 2.0 Authorization Script for Google Drive (ATLAS TECNM)
Generates gdrive_oauth_token.json so uploads use the user's 5 TB Google One / AI Pro quota.
"""

import os
import sys
import json
from google_auth_oauthlib.flow import InstalledAppFlow

SCOPES = [
    "https://www.googleapis.com/auth/drive"
]

OAUTH_TOKEN_PATH = "/data/data/com.termux/files/home/tools/atlas_mcp/gdrive_oauth_token.json"
STORAGE_OAUTH_TOKEN_PATH = "/storage/emulated/0/proyecto TecNm/tools/atlas_mcp/gdrive_oauth_token.json"
CLIENT_SECRETS_PATH = "/data/data/com.termux/files/home/tools/atlas_mcp/client_secret.json"

def run_oauth():
    print("=" * 65)
    print("   ATLAS TECNM - Configuración OAuth 2.0 de Google Drive (5 TB)")
    print("=" * 65)
    
    flow = None
    if os.path.exists(CLIENT_SECRETS_PATH):
        print(f"[*] Archivo de credenciales detectado: {CLIENT_SECRETS_PATH}")
        flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_PATH, SCOPES)
    else:
        print("[!] No se encontró client_secret.json.")
        print("    Ingresa tus credenciales de Google Cloud Console:")
        print("    (Tipo: 'Desktop app' / 'Aplicación de escritorio')\n")
        client_id = input("1. Introduce tu Client ID: ").strip()
        client_secret = input("2. Introduce tu Client Secret: ").strip()
        if not client_id or not client_secret:
            print("\n[X] Error: Client ID y Client Secret son requeridos.")
            sys.exit(1)
        
        client_config = {
            "installed": {
                "client_id": client_id,
                "client_secret": client_secret,
                "auth_uri": "https://accounts.google.com/o/oauth2/auth",
                "token_uri": "https://oauth2.googleapis.com/token",
                "redirect_uris": ["http://localhost:8080/"]
            }
        }
        flow = InstalledAppFlow.from_client_config(client_config, SCOPES)

    print("\n[+] Iniciando servidor de autenticación local en http://localhost:8085/ ...")
    print("[*] Abre el enlace que aparecerá a continuación en tu navegador para autorizar con tu cuenta de 5 TB:\n")
    
    creds = flow.run_local_server(
        port=8085,
        open_browser=False,
        prompt="consent",
        access_type="offline"
    )

    token_json = creds.to_json()
    with open(OAUTH_TOKEN_PATH, "w") as f:
        f.write(token_json)
    print(f"\n[OK] Token OAuth guardado exitosamente en: {OAUTH_TOKEN_PATH}")

    storage_dir = os.path.dirname(STORAGE_OAUTH_TOKEN_PATH)
    if os.path.exists(storage_dir):
        with open(STORAGE_OAUTH_TOKEN_PATH, "w") as f:
            f.write(token_json)
        print(f"[OK] Copia sincronizada en: {STORAGE_OAUTH_TOKEN_PATH}")

    print("\n" + "=" * 65)
    print(" [ÉXITO] ¡Conexión OAuth 2.0 completada!")
    print(" A partir de ahora, el MCP y todo tu equipo subirán archivos")
    print(" consumiendo directamente tu cuota de 5 TB de Google AI Pro.")
    print("=" * 65)

if __name__ == "__main__":
    run_oauth()
