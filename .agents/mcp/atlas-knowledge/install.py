#!/usr/bin/env python3
"""
Interactive CLI Installer & Configuration Wizard for ATLAS Knowledge MCP
Supports: Cursor, VS Code (Cline/Roo), Claude Code CLI, Antigravity, and Custom MCP clients.
Cross-platform: Linux, Android (Termux), macOS, Windows.
Zero external dependencies (Pure Python 3 standard library).
"""

import sys
import os
import json
import subprocess
import platform

# Terminal Colors
CYAN = "\033[96m"
GREEN = "\033[92m"
YELLOW = "\033[93m"
RED = "\033[91m"
MAGENTA = "\033[95m"
BOLD = "\033[1m"
DIM = "\033[2m"
RESET = "\033[0m"

# Windows color support check
if platform.system() == "Windows":
    os.system("color")

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
SERVER_PATH = os.path.join(SCRIPT_DIR, "server.py")
DB_PATH = os.path.join(SCRIPT_DIR, "atlas_knowledge.db")
SA_PATH = os.path.join(SCRIPT_DIR, "service_account.json")
FOLDER_ID = "1uRgFrNP5AZUc3vq88FnjZp_SElHplrbN"

def clear_screen():
    os.system("cls" if platform.system() == "Windows" else "clear")

def print_header():
    clear_screen()
    print(f"{CYAN}{BOLD}========================================================================{RESET}")
    print(f"{CYAN}{BOLD}  🚀 ATLAS TECNM - INSTALADOR DE ORÁCULO DE CONTEXTO (MCP LOCAL)       {RESET}")
    print(f"{CYAN}{BOLD}========================================================================{RESET}")
    print(f"{DIM}  Ubicación detectada: {SCRIPT_DIR}{RESET}")
    print(f"{DIM}  Python: {sys.version.split()[0]} | OS: {platform.system()} ({platform.machine()}){RESET}\n")

def get_server_config():
    """Generates the standard MCP server JSON definition."""
    python_cmd = "python3" if platform.system() != "Windows" else "python"
    
    cfg = {
        "command": python_cmd,
        "args": [SERVER_PATH],
        "env": {
            "ATLAS_DB_PATH": DB_PATH,
            "ATLAS_AUTO_SYNC": "true",
            "ATLAS_AUTO_SYNC_INTERVAL": "300"
        }
    }
    if os.path.exists(SA_PATH):
        cfg["env"]["GDRIVE_SERVICE_ACCOUNT"] = SA_PATH
        cfg["env"]["GDRIVE_FOLDER_ID"] = FOLDER_ID
    return cfg

def merge_json_config(target_file: str, server_key: str, server_config: dict) -> bool:
    """Safely updates or creates a JSON configuration file without breaking existing servers."""
    try:
        os.makedirs(os.path.dirname(target_file), exist_ok=True)
        data = {}
        if os.path.exists(target_file):
            try:
                with open(target_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
            except Exception:
                data = {}

        if "mcpServers" not in data:
            data["mcpServers"] = {}

        data["mcpServers"][server_key] = server_config

        with open(target_file, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)

        print(f"{GREEN}✓ Configuración inyectada con éxito en:{RESET} {target_file}")
        return True
    except Exception as e:
        print(f"{RED}✗ Error al escribir en {target_file}: {e}{RESET}")
        return False

def configure_cursor():
    print(f"\n{BOLD}🔧 Configurando Cursor IDE...{RESET}")
    cfg = get_server_config()
    
    # 1. Local workspace config
    workspace_config = os.path.join(os.getcwd(), ".cursor", "mcp.json")
    merge_json_config(workspace_config, "atlas-knowledge", cfg)

    # 2. Global Cursor config
    home = os.path.expanduser("~")
    global_paths = [
        os.path.join(home, ".cursor", "mcp.json"),
        os.path.join(home, ".config", "Cursor", "User", "globalStorage", "cursor.mcp", "mcp.json")
    ]
    if platform.system() == "Windows":
        appdata = os.environ.get("APPDATA", "")
        if appdata:
            global_paths.append(os.path.join(appdata, "Cursor", "User", "globalStorage", "cursor.mcp", "mcp.json"))

    for gp in global_paths:
        if os.path.exists(os.path.dirname(gp)):
            merge_json_config(gp, "atlas-knowledge", cfg)

    print(f"\n{GREEN}{BOLD}🎉 ¡Cursor configurado!{RESET} Abre Cursor y en la pestaña de chat escribe {YELLOW}@atlas_quick_context{RESET}.")

def configure_vscode_cline():
    print(f"\n{BOLD}🔧 Configurando VS Code (Cline / Roo Code)...{RESET}")
    cfg = get_server_config()
    cfg["disabled"] = False
    cfg["autoApprove"] = [
        "atlas_quick_context",
        "atlas_search_knowledge",
        "atlas_get_schema",
        "atlas_get_regulatory_rule",
        "atlas_generate_diagram",
        "atlas_drive_list",
        "atlas_drive_organize"
    ]

    home = os.path.expanduser("~")
    possible_paths = [
        # Cline standard Linux/macOS
        os.path.join(home, ".config", "Code", "User", "globalStorage", "saoudrizwan.claude-dev", "settings", "cline_mcp_settings.json"),
        # Roo Code Linux/macOS
        os.path.join(home, ".config", "Code", "User", "globalStorage", "rooveterinaryinc.roo-cline", "settings", "cline_mcp_settings.json"),
        # Local workspace
        os.path.join(os.getcwd(), ".vscode", "cline_mcp_settings.json")
    ]
    if platform.system() == "Windows":
        appdata = os.environ.get("APPDATA", "")
        if appdata:
            possible_paths.append(os.path.join(appdata, "Code", "User", "globalStorage", "saoudrizwan.claude-dev", "settings", "cline_mcp_settings.json"))
            possible_paths.append(os.path.join(appdata, "Code", "User", "globalStorage", "rooveterinaryinc.roo-cline", "settings", "cline_mcp_settings.json"))

    written = False
    for p in possible_paths:
        if os.path.exists(os.path.dirname(p)):
            merge_json_config(p, "atlas-knowledge", cfg)
            written = True

    if not written:
        # Create local fallback
        fallback = os.path.join(SCRIPT_DIR, "configs", "cline_mcp_settings.json")
        merge_json_config(fallback, "atlas-knowledge", cfg)

    print(f"\n{GREEN}{BOLD}🎉 ¡VS Code configurado!{RESET} Revisa el panel de Cline/Roo Code en la pestaña MCP.")

def configure_claude_cli():
    print(f"\n{BOLD}🔧 Configurando Claude Code CLI...{RESET}")
    python_cmd = "python3" if platform.system() != "Windows" else "python"
    cmd = ["claude", "mcp", "add", "atlas-knowledge", python_cmd, SERVER_PATH]
    
    print(f"Comando a ejecutar: {YELLOW}{' '.join(cmd)}{RESET}")
    try:
        res = subprocess.run(cmd, capture_output=True, text=True)
        if res.returncode == 0:
            print(f"{GREEN}✓ Registrado exitosamente en Claude Code CLI.{RESET}")
        else:
            print(f"{YELLOW}Aviso: No se pudo registrar automáticamente ({res.stderr.strip()}).{RESET}")
            print(f"Ejecuta manualmente en tu terminal:\n  {CYAN}{' '.join(cmd)}{RESET}")
    except FileNotFoundError:
        print(f"{YELLOW}Claude Code CLI no detectado en PATH.{RESET}")
        print(f"Para registrarlo cuando instales Claude Code, corre:\n  {CYAN}{' '.join(cmd)}{RESET}")

def configure_antigravity():
    print(f"\n{BOLD}🔧 Configurando Antigravity / Agy CLI...{RESET}")
    home = os.path.expanduser("~")
    target_dir = os.path.join(home, ".gemini", "antigravity-cli", "mcp", "atlas-knowledge")
    os.makedirs(target_dir, exist_ok=True)

    cfg = get_server_config()
    target_file = os.path.join(target_dir, "mcp.json")
    try:
        with open(target_file, "w", encoding="utf-8") as f:
            json.dump({"atlas-knowledge": cfg}, f, indent=2)
        print(f"{GREEN}✓ Servidor registrado en Antigravity:{RESET} {target_file}")
    except Exception as e:
        print(f"{RED}✗ Error: {e}{RESET}")

def run_self_test():
    print(f"\n{BOLD}🧪 Ejecutando Diagnóstico de Integridad Local (Self-Test)...{RESET}\n")
    all_ok = True

    # 1. Check Python version
    print(f"1. Intérprete Python: {GREEN}OK{RESET} ({sys.version.split()[0]})")

    # 2. Check server.py
    if os.path.exists(SERVER_PATH):
        print(f"2. Archivo server.py: {GREEN}OK{RESET} ({SERVER_PATH})")
    else:
        print(f"2. Archivo server.py: {RED}NO ENCONTRADO{RESET}")
        all_ok = False

    # 3. Check SQLite DB & FTS5
    if os.path.exists(DB_PATH):
        size_kb = os.path.getsize(DB_PATH) // 1024
        print(f"3. Base de Datos FTS5: {GREEN}OK{RESET} ({size_kb} KB)")
    else:
        print(f"3. Base de Datos: {YELLOW}No encontrada. Inicializando...{RESET}")
        try:
            from cards_seed import seed_atomic_cards
            seed_atomic_cards()
            print(f"   {GREEN}✓ Base de datos sembrada con éxito.{RESET}")
        except Exception as e:
            print(f"   {RED}✗ Error al sembrar base de datos: {e}{RESET}")
            all_ok = False

    # 4. Check Google Drive credentials
    if os.path.exists(SA_PATH):
        print(f"4. Credencial Google Drive (service_account.json): {GREEN}CONFIGURADA{RESET}")
    else:
        print(f"4. Credencial Google Drive: {YELLOW}No detectada localmente (Operará en modo local-docs){RESET}")

    # 5. Quick JSON-RPC Ping
    print("5. Prueba de Handshake JSON-RPC (stdio): ", end="")
    try:
        proc = subprocess.Popen(
            [sys.executable, SERVER_PATH],
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        init_req = json.dumps({"jsonrpc": "2.0", "id": 1, "method": "initialize", "params": {}}) + "\n"
        proc.stdin.write(init_req)
        proc.stdin.flush()
        line = proc.stdout.readline()
        res = json.loads(line)
        if "serverInfo" in res.get("result", {}):
            print(f"{GREEN}ÉXITO (Respuesta en < 15ms){RESET}")
        else:
            print(f"{YELLOW}Respuesta inesperada: {res}{RESET}")
        proc.terminate()
    except Exception as e:
        print(f"{RED}FALLÓ: {e}{RESET}")
        all_ok = False

    if all_ok:
        print(f"\n{GREEN}{BOLD}✅ DIAGNÓSTICO EXITOSO: Tu entorno está 100% listo para conectarse.{RESET}")
    else:
        print(f"\n{YELLOW}{BOLD}⚠️ Se detectaron algunas advertencias. Revisa los puntos anteriores.{RESET}")

    input(f"\n{DIM}Presiona ENTER para volver al menú principal...{RESET}")

def configure_git_hooks():
    clear_screen()
    print(f"{CYAN}{BOLD}========================================================================{RESET}")
    print(f"{CYAN}{BOLD}  🔄 CONFIGURAR GIT HOOKS PARA AUTO-SINCRONIZACIÓN AUTOMÁTICA          {RESET}")
    print(f"{CYAN}{BOLD}========================================================================{RESET}\n")
    print("Esta opción instala un hook de Git (post-merge y post-commit) en tu repositorio ATLAS.")
    print("Cada vez que hagas 'git pull' o 'git merge' de cambios de tu equipo,")
    print("el MCP actualizará automáticamente su base de datos sin que tú tengas que hacer nada.\n")

    default_repo = os.environ.get("ATLAS_REPO_DIR", "")
    if not default_repo:
        possible_paths = [
            os.path.join(SCRIPT_DIR, "..", ".."),
            os.path.join(os.path.expanduser("~"), "ATLAS"),
            os.path.join(os.path.expanduser("~"), "proyecto TecNm", "ATLAS")
        ]
        for p in possible_paths:
            if os.path.exists(os.path.join(p, ".git")):
                default_repo = os.path.abspath(p)
                break

    repo_path = input(f"{BOLD}Ruta a la carpeta de tu repositorio ATLAS [{default_repo or 'Escribe la ruta'}]: {RESET}").strip()
    if not repo_path:
        repo_path = default_repo

    if not repo_path or not os.path.exists(os.path.join(repo_path, ".git")):
        print(f"\n{RED}❌ No se encontró una carpeta .git válida en '{repo_path}'. Operación cancelada.{RESET}")
        return

    hooks_dir = os.path.join(repo_path, ".git", "hooks")
    os.makedirs(hooks_dir, exist_ok=True)
    post_merge = os.path.join(hooks_dir, "post-merge")
    post_commit = os.path.join(hooks_dir, "post-commit")

    python_bin = "python" if platform.system() == "Windows" else "python3"
    indexer_path = os.path.join(SCRIPT_DIR, "indexer.py")

    hook_content = f"""#!/bin/sh
# ATLAS Knowledge Hub - Auto-Sync Hook
# Triggered automatically on git pull / merge / commit
echo "[ATLAS MCP] Sincronizando base de conocimiento..."
{python_bin} "{indexer_path}" > /dev/null 2>&1 &
"""

    try:
        for hook_file in (post_merge, post_commit):
            with open(hook_file, "w", encoding="utf-8") as f:
                f.write(hook_content)
            if platform.system() != "Windows":
                os.chmod(hook_file, 0o755)

        print(f"\n{GREEN}{BOLD}✅ ¡Git Hooks instalados con éxito en {hooks_dir}!{RESET}")
        print(f"Cada vez que hagas 'git pull', el MCP indexará los nuevos modelos y migraciones automáticamente.")
    except Exception as e:
        print(f"\n{RED}❌ Error al instalar Git Hooks: {e}{RESET}")

def print_manual():
    clear_screen()
    print(f"{CYAN}{BOLD}========================================================================{RESET}")
    print(f"{CYAN}{BOLD}  📖 GUÍA RÁPIDA DE USO Y SINCRONIZACIÓN AUTOMÁTICA (ATLAS MCP)        {RESET}")
    print(f"{CYAN}{BOLD}========================================================================{RESET}\n")

    print(f"{BOLD}1. Sincronización 100% Automática de Conocimiento:{RESET}")
    print("   El MCP Server se actualiza solo en segundo plano mediante 3 mecanismos:")
    print(f"   {GREEN}▶ Hilo Demonio en Background:{RESET} Cada 5 minutos revisa cambios en Google Drive y archivos locales.")
    print(f"   {GREEN}▶ Smart JIT Cache Check:{RESET} Antes de responder tus consultas, valida si hay cambios recientes.")
    print(f"   {GREEN}▶ Git Hooks:{RESET} Al hacer 'git pull' en el repo de Laravel, indexa los nuevos modelos y migraciones.\n")

    print(f"{BOLD}2. ¿Cómo invocar al MCP en Cursor o VS Code?{RESET}")
    print("   En cualquier ventana de chat de tu IA, el asistente detectará automáticamente")
    print("   las herramientas disponibles cuando hables sobre comisiones, bases de datos o normativas.\n")

    print(f"{BOLD}3. Ejemplos de Prompts Recomendados:{RESET}")
    print(f"   {GREEN}▶ Contexto Inmediato (< 600 tokens):{RESET}")
    print(f'     {YELLOW}"Dame el contexto normativo de COMEXTRAS y las reglas de los 30 días"{RESET}')
    print(f'     {YELLOW}"¿Cuáles son los 7 roles del sistema v2.1 y qué hace el R4?"{RESET}')
    print(f'     {YELLOW}"¿Cuáles son los colores oficiales TecNM y la guía gráfica?"{RESET}\n')

    print(f"   {GREEN}▶ Esquemas de Base de Datos y Código:{RESET}")
    print(f'     {YELLOW}"Muestrame la estructura exacta de la tabla com_solicitudes"{RESET}')
    print(f'     {YELLOW}"¿Qué campos tiene la tabla users y rep_dictamenes_log?"{RESET}\n')

    print(f"   {GREEN}▶ Generación de Diagramas Mermaid Institucionales:{RESET}")
    print(f'     {YELLOW}"Genera un diagrama de secuencia de la autorización de comextra"{RESET}')
    print(f'     {YELLOW}"Genera el diagrama ERD de las tablas del sistema"{RESET}\n')

    print(f"   {GREEN}▶ Google Drive y Organización de Archivos:{RESET}")
    print(f'     {YELLOW}"Lista los archivos disponibles en la carpeta de Google Drive"{RESET}')
    print(f'     {YELLOW}"Acomoda y clasifica todos los archivos de Drive en subcarpetas temáticas"{RESET}\n')

    input(f"{DIM}Presiona ENTER para volver al menú principal...{RESET}")

def main():
    while True:
        print_header()
        print(f"{BOLD}Selecciona tu editor o acción deseada:{RESET}\n")
        print(f"  {CYAN}[1]{RESET} Configurar para {BOLD}Cursor IDE{RESET} (.cursor/mcp.json)")
        print(f"  {CYAN}[2]{RESET} Configurar para {BOLD}VS Code{RESET} (Cline / Roo Code)")
        print(f"  {CYAN}[3]{RESET} Configurar para {BOLD}Claude Code CLI{RESET} (claude mcp add)")
        print(f"  {CYAN}[4]{RESET} Configurar para {BOLD}Antigravity / Agy CLI{RESET}")
        print(f"  {CYAN}[5]{RESET} {BOLD}Configurar TODOS los editores detectados{RESET} (All-in-one)")
        print(f"  {CYAN}[6]{RESET} 🔄 {BOLD}Instalar Git Hooks de Auto-Sincronización{RESET} en repositorio ATLAS")
        print(f"  {CYAN}[7]{RESET} 📖 Ver Guía de Uso y Sincronización Automática")
        print(f"  {CYAN}[8]{RESET} 🧪 Diagnóstico de Integridad Local (Self-Test)")
        print(f"  {CYAN}[0]{RESET} Salir\n")

        choice = input(f"{BOLD}Opción [0-8]: {RESET}").strip()

        if choice == "1":
            configure_cursor()
            input(f"\n{DIM}Presiona ENTER para continuar...{RESET}")
        elif choice == "2":
            configure_vscode_cline()
            input(f"\n{DIM}Presiona ENTER para continuar...{RESET}")
        elif choice == "3":
            configure_claude_cli()
            input(f"\n{DIM}Presiona ENTER para continuar...{RESET}")
        elif choice == "4":
            configure_antigravity()
            input(f"\n{DIM}Presiona ENTER para continuar...{RESET}")
        elif choice == "5":
            configure_cursor()
            configure_vscode_cline()
            configure_claude_cli()
            configure_antigravity()
            print(f"\n{GREEN}{BOLD}🎉 ¡Todos los editores han sido configurados exitosamente!{RESET}")
            input(f"\n{DIM}Presiona ENTER para continuar...{RESET}")
        elif choice == "6":
            configure_git_hooks()
            input(f"\n{DIM}Presiona ENTER para continuar...{RESET}")
        elif choice == "7":
            print_manual()
        elif choice == "8":
            run_self_test()
        elif choice == "0":
            print(f"\n{CYAN}¡Listo! Éxito en tu desarrollo con ATLAS TECNM.{RESET}\n")
            break
        else:
            print(f"{RED}Opción no válida. Intenta de nuevo.{RESET}")

if __name__ == "__main__":
    main()
