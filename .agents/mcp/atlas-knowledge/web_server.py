#!/usr/bin/env python3
"""
ATLAS Knowledge Hub - Remote MCP Server (SSE Transport & Direct HTTP RPC) & Webhook Receiver
Enterprise Security Edition: Bearer Token / API Key Authentication
Prevents unauthorized public access to sensitive TecNM data and Google Drive.
Part of MCP-ATLAS-TecNM
"""

import sys
import os
import json
import uuid
import time
import logging
import threading
from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from typing import Dict, Any

# Ensure local imports work
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)

from server import process_tool_call, TOOLS_METADATA
from github_connector import handle_github_push_event
from drive_connector import sync_knowledge_sources

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")

PORT = int(os.environ.get("PORT", 8000))
MCP_API_KEY = os.environ.get("ATLAS_MCP_API_KEY", "").strip()
GITHUB_WEBHOOK_SECRET = os.environ.get("GITHUB_WEBHOOK_SECRET", "").strip()

active_sessions: Dict[str, Any] = {}
sessions_lock = threading.Lock()

class MCPHttpHandler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    def log_message(self, format, *args):
        logging.info("%s - - [%s] %s" % (self.address_string(), self.log_date_time_string(), format%args))

    def send_cors_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization, X-API-Key")

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_cors_headers()
        self.end_headers()

    def is_authorized(self, query_params) -> bool:
        """
        Validates that the incoming request has a valid ATLAS_MCP_API_KEY.
        Supports:
        1. Header: Authorization: Bearer <key>
        2. Header: X-API-Key: <key>
        3. Query parameter: ?key=<key> or ?apiKey=<key> or ?token=<key>
        """
        if not MCP_API_KEY:
            # If no key set in environment, allow with warning
            return True

        # Check Authorization header
        auth_header = self.headers.get("Authorization", "")
        if auth_header.startswith("Bearer "):
            token = auth_header.split("Bearer ")[1].strip()
            if token == MCP_API_KEY:
                return True

        # Check X-API-Key header
        x_api_key = self.headers.get("X-API-Key", "").strip()
        if x_api_key == MCP_API_KEY:
            return True

        # Check URL Query params
        param_token = (
            query_params.get("key", [None])[0] or
            query_params.get("apiKey", [None])[0] or
            query_params.get("token", [None])[0]
        )
        if param_token and param_token.strip() == MCP_API_KEY:
            return True

        return False

    def send_unauthorized(self):
        """Sends an HTTP 401 Unauthorized response blocking external access."""
        self.send_response(401)
        self.send_header("Content-Type", "application/json")
        self.send_cors_headers()
        self.end_headers()
        payload = {
            "error": "Unauthorized",
            "message": "Acceso restringido: Este servidor contiene información sensible del TecNM. Se requiere una clave de autorización válida (ATLAS_MCP_API_KEY).",
            "code": "ERR_UNAUTHORIZED"
        }
        self.wfile.write(json.dumps(payload, ensure_ascii=False).encode("utf-8"))

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path
        query_params = parse_qs(parsed.query)

        # 1. Healthcheck (Public for Render uptime monitoring, exposes NO data)
        if path == "/health":
            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.send_cors_headers()
            self.end_headers()
            self.wfile.write(json.dumps({"status": "healthy", "service": "mcp-atlas-tecnm", "auth_required": bool(MCP_API_KEY)}).encode("utf-8"))
            return

        # 2. Status / Dashboard
        elif path == "/":
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_cors_headers()
            self.end_headers()
            
            is_auth = self.is_authorized(query_params)
            host = self.headers.get('Host', f'localhost:{PORT}')

            if is_auth or not MCP_API_KEY:
                badge = '<span class="badge" style="background:#285C4D">Autenticado / Acceso Autorizado</span>'
                content_html = f"""
                <div class="card">
                    <h3>📡 Conexión Protegida para Cursor y VS Code</h3>
                    <p>Usa esta configuración con tu clave privada en <code>.cursor/mcp.json</code>:</p>
                    <pre>{{
  "mcpServers": {{
    "atlas-knowledge": {{
      "url": "https://{host}/sse",
      "headers": {{
        "Authorization": "Bearer {MCP_API_KEY if MCP_API_KEY else 'TU_CLAVE_AQUI'}"
      }}
    }}
  }}
}}</pre>
                </div>
                <div class="card">
                    <h3>⚡ Herramientas Disponibles (10 Tools Activas)</h3>
                    <ul>
                        <li><code>atlas_quick_context</code>: Tarjeta atómica (< 800 tokens).</li>
                        <li><code>atlas_search_knowledge</code>: Búsqueda BM25 en normativa y código.</li>
                        <li><code>atlas_get_schema</code>: Esquema DDL exacto de tablas.</li>
                        <li><code>atlas_get_regulatory_rule</code>: Circular 0045 y MOO-PR-039.</li>
                        <li><code>atlas_generate_diagram</code>: Generador Mermaid institucional.</li>
                        <li><code>atlas_sync_drive</code>: Sincronización en caliente.</li>
                        <li><code>atlas_drive_list</code>: Consulta de archivos en Google Drive.</li>
                        <li><code>atlas_drive_create_folder</code>: Crear subdirectorios en Drive.</li>
                        <li><code>atlas_drive_move_file</code>: Mover archivos entre carpetas.</li>
                        <li><code>atlas_drive_organize</code>: Acomodo automático en subdirectorios temáticos.</li>
                    </ul>
                </div>"""
            else:
                badge = '<span class="badge" style="background:#9B2247">🔒 Servidor Privado Protegido</span>'
                content_html = """
                <div class="card" style="border-left-color: #9B2247;">
                    <h3>⛔ Acceso Restringido</h3>
                    <p>Este servidor MCP pertenece a la <strong>Fábrica de Software del TecNM</strong> y almacena contexto y bases de datos institucionales sensibles.</p>
                    <p>Para conectarte desde Cursor o VS Code, debes proporcionar el <code>Bearer Token</code> autorizado por el equipo.</p>
                </div>"""

            html = f"""<!DOCTYPE html>
<html>
<head>
    <title>MCP ATLAS TecNM - Privado</title>
    <style>
        body {{ font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; line-height: 1.6; background: #0F172A; color: #F8FAFC; }}
        h1 {{ color: #4D92DF; border-bottom: 2px solid #1B396A; padding-bottom: 10px; }}
        .card {{ background: #1E293B; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #1B396A; }}
        code {{ background: #020617; padding: 2px 6px; border-radius: 4px; color: #D4C19C; }}
        pre {{ background: #020617; padding: 15px; border-radius: 6px; overflow-x: auto; color: #38BDF8; }}
        .badge {{ display: inline-block; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; }}
    </style>
</head>
<body>
    <h1>🛡️ MCP ATLAS TecNM {badge}</h1>
    <p>Oráculo de contexto e infraestructura de IA para la plataforma ATLAS TECNM.</p>
    {content_html}
</body>
</html>"""
            self.wfile.write(html.encode("utf-8"))
            return

        # 3. Server-Sent Events (SSE) Endpoint (Requires Authentication)
        elif path == "/sse":
            if not self.is_authorized(query_params):
                self.send_unauthorized()
                return

            session_id = str(uuid.uuid4())
            self.send_response(200)
            self.send_header("Content-Type", "text/event-stream")
            self.send_header("Cache-Control", "no-cache")
            self.send_header("Connection", "keep-alive")
            self.send_cors_headers()
            self.end_headers()

            with sessions_lock:
                active_sessions[session_id] = self.wfile

            endpoint_uri = f"/messages?sessionId={session_id}"
            try:
                self.wfile.write(f"event: endpoint\ndata: {endpoint_uri}\n\n".encode("utf-8"))
                self.wfile.flush()
                logging.info(f"Authorized SSE client connected: {session_id}")

                while True:
                    with sessions_lock:
                        if session_id not in active_sessions:
                            break
                    time.sleep(15)
                    self.wfile.write(b": ping\n\n")
                    self.wfile.flush()
            except Exception as e:
                logging.info(f"SSE client disconnected: {session_id} ({e})")
            finally:
                with sessions_lock:
                    active_sessions.pop(session_id, None)
            return

        else:
            self.send_response(404)
            self.end_headers()

    def do_POST(self):
        parsed = urlparse(self.path)
        path = parsed.path
        query_params = parse_qs(parsed.query)
        length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(length).decode("utf-8") if length > 0 else ""

        # 1. Direct HTTP JSON-RPC Endpoint (/rpc) - Protected
        if path == "/rpc":
            if not self.is_authorized(query_params):
                self.send_unauthorized()
                return

            try:
                request = json.loads(body)
                msg_id = request.get("id")
                method = request.get("method")
                params = request.get("params", {})

                if method == "initialize":
                    res = {
                        "jsonrpc": "2.0",
                        "id": msg_id,
                        "result": {
                            "protocolVersion": "2024-11-05",
                            "capabilities": {"tools": {}},
                            "serverInfo": {"name": "atlas-knowledge-mcp-secure", "version": "1.3.0"}
                        }
                    }
                elif method == "tools/list":
                    res = {"jsonrpc": "2.0", "id": msg_id, "result": {"tools": TOOLS_METADATA}}
                elif method == "tools/call":
                    tname = params.get("name")
                    targs = params.get("arguments", {})
                    output = process_tool_call(tname, targs)
                    res = {"jsonrpc": "2.0", "id": msg_id, "result": {"content": [{"type": "text", "text": output}]}}
                else:
                    res = {"jsonrpc": "2.0", "id": msg_id, "error": {"code": -32601, "message": "Method not found"}}

                self.send_response(200)
                self.send_header("Content-Type", "application/json")
                self.send_cors_headers()
                self.end_headers()
                self.wfile.write(json.dumps(res, ensure_ascii=False).encode("utf-8"))
            except Exception as e:
                self.send_response(500)
                self.send_cors_headers()
                self.end_headers()
                self.wfile.write(json.dumps({"error": str(e)}).encode("utf-8"))
            return

        # 2. MCP Messages Handler (/messages?sessionId=...) - Protected
        elif path == "/messages":
            if not self.is_authorized(query_params):
                self.send_unauthorized()
                return

            session_id = query_params.get("sessionId", [None])[0]

            with sessions_lock:
                stream = active_sessions.get(session_id)

            if not session_id or not stream:
                self.send_response(400)
                self.send_header("Content-Type", "application/json")
                self.send_cors_headers()
                self.end_headers()
                self.wfile.write(json.dumps({"error": "Invalid or expired session"}).encode("utf-8"))
                return

            try:
                request = json.loads(body)
            except Exception as e:
                self.send_response(400)
                self.send_cors_headers()
                self.end_headers()
                return

            msg_id = request.get("id")
            method = request.get("method")
            params = request.get("params", {})

            response = None
            if method == "initialize":
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {
                        "protocolVersion": "2024-11-05",
                        "capabilities": {"tools": {}},
                        "serverInfo": {"name": "atlas-knowledge-mcp-secure", "version": "1.3.0"}
                    }
                }
            elif method == "notifications/initialized":
                logging.info("Remote client initialized.")
            elif method == "tools/list":
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {"tools": TOOLS_METADATA}
                }
            elif method == "tools/call":
                tname = params.get("name")
                targs = params.get("arguments", {})
                output = process_tool_call(tname, targs)
                response = {
                    "jsonrpc": "2.0",
                    "id": msg_id,
                    "result": {"content": [{"type": "text", "text": output}]}
                }

            # Acknowledge HTTP POST (202 Accepted)
            self.send_response(202)
            self.send_cors_headers()
            self.end_headers()

            # Push response back to client over the open SSE connection
            if response is not None:
                try:
                    sse_msg = f"event: message\ndata: {json.dumps(response, ensure_ascii=False)}\n\n"
                    stream.write(sse_msg.encode("utf-8"))
                    stream.flush()
                except Exception as err:
                    logging.error(f"Failed to write to SSE stream: {err}")
            return

        # 3. Webhook: GitHub Push Event
        elif path == "/api/webhooks/github":
            try:
                payload = json.loads(body) if body else {}
                result = handle_github_push_event(payload)
                self.send_response(200)
                self.send_header("Content-Type", "application/json")
                self.end_headers()
                self.wfile.write(json.dumps(result).encode("utf-8"))
            except Exception as e:
                self.send_response(500)
                self.end_headers()
                self.wfile.write(json.dumps({"error": str(e)}).encode("utf-8"))
            return

        # 4. Webhook: Google Drive Push Notification
        elif path == "/api/webhooks/gdrive":
            channel_id = self.headers.get("X-Goog-Channel-ID", "")
            resource_state = self.headers.get("X-Goog-Resource-State", "")
            logging.info(f"Google Drive Push Event received! State: {resource_state}, Channel: {channel_id}")

            res = sync_knowledge_sources(force=True)

            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"status": "acknowledged", "sync_result": res}).encode("utf-8"))
            return

        else:
            self.send_response(404)
            self.end_headers()

def run_web_server():
    server = ThreadingHTTPServer(("0.0.0.0", PORT), MCPHttpHandler)
    logging.info(f"🛡️ ATLAS Remote MCP Server running on http://0.0.0.0:{PORT} (Enterprise Security Mode)")
    logging.info(f"🔑 Authentication: {'ENABLED (ATLAS_MCP_API_KEY required)' if MCP_API_KEY else 'DISABLED (Open Mode)'}")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    server.server_close()

if __name__ == "__main__":
    run_web_server()
