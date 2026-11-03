# 📖 Guía de Instalación y Uso: ATLAS Knowledge MCP (Standalone Windows .exe)

**Proyecto:** ATLAS TECNM — Oráculo de Conocimiento y Servidor MCP  
**Versión:** v1.2.0 (Local-First Autonomous Edition)  
**Binario Ejecutable:** `AtlasMCP-Setup.exe` (8.7 MB, Windows x64)  
**Repositorio Oficial Privado:** [https://github.com/Richpol99/MCP-ATLAS-TecNM.git](https://github.com/Richpol99/MCP-ATLAS-TecNM.git)

---

## 🎯 1. ¿Qué es este archivo?

`AtlasMCP-Setup.exe` es un instalador autónomo compilado con Python 3.11 para Windows que permite configurar en menos de 2 minutos el servidor de contexto **ATLAS Knowledge Hub** en tus entornos de desarrollo de Inteligencia Artificial (Cursor, VS Code Cline/Roo, Claude Code CLI o Antigravity).

- **No requiere tener Python instalado** en la máquina donde se ejecute.
- **No sobreescribe configuraciones previas**: Combina (*merges*) de manera segura las definiciones JSON de tus otros servidores MCP.
- **Configura sincronización automática**: Activa el hilo demonio de refresco en segundo plano y vincula los Git Hooks locales.

---

## 📥 2. ¿Dónde obtener el ejecutable?

Tienes dos opciones para obtener el archivo:

### Opción A: Copia Local
El archivo ya se encuentra listo en tu almacenamiento local:
* Ruta: `proyecto TecNm/tools/atlas_mcp/AtlasMCP-Setup.exe`

### Opción B: Descarga desde GitHub Releases
Si algún compañero de equipo necesita descargarlo en su computadora:
1. Acceder al release oficial: [GitHub Releases v1.0.0](https://github.com/Richpol99/MCP-ATLAS-TecNM/releases/tag/v1.0.0)
2. Descargar el archivo `AtlasMCP-Setup.exe`.

> ⚠️ **Importante (Estructura de Carpetas):**  
> Para que el servidor MCP funcione, `AtlasMCP-Setup.exe` debe residir dentro de la carpeta `tools/atlas_mcp/` (junto a los archivos `server.py`, `db.py`, `indexer.py` y `atlas_knowledge.db`).

---

## 🚀 3. Instrucciones de Instalación Paso a Paso

### Paso 1: Ejecutar el Instalador
1. En Windows, haz **doble clic** sobre `AtlasMCP-Setup.exe`.
2. Si aparece la pantalla azul de aviso de **Windows SmartScreen** (*"Windows protegió su PC"*):
   * Haz clic en el enlace **"Más información"** (*More info*).
   * Haz clic en el botón inferior **"Ejecutar de todas formas"** (*Run anyway*).
3. Se abrirá la consola con el menú interactivo:

```text
========================================================================
  🚀 ATLAS TECNM - INSTALADOR DE ORÁCULO DE CONTEXTO (MCP LOCAL)       
========================================================================
  Ubicación detectada: C:\...\tools\atlas_mcp
  Python: 3.11.x | OS: Windows (AMD64)

Selecciona tu editor o acción deseada:

  [1] Configurar para Cursor IDE (.cursor/mcp.json)
  [2] Configurar para VS Code (Cline / Roo Code)
  [3] Configurar para Claude Code CLI (claude mcp add)
  [4] Configurar para Antigravity / Agy CLI
  [5] Configurar TODOS los editores detectados (All-in-one)
  [6] 🔄 Instalar Git Hooks de Auto-Sincronización en repositorio ATLAS
  [7] 📖 Ver Guía de Uso y Sincronización Automática
  [8] 🧪 Diagnóstico de Integridad Local (Self-Test)
  [0] Salir

Opción [0-8]: 
```

---

### Paso 2: Vincular tu Editor de Código
Elige la opción correspondiente a tu entorno de trabajo:

* **Opción `[1]` — Cursor IDE:**  
  Crea o actualiza el archivo `.cursor/mcp.json` en la raíz de tu proyecto o directorio de usuario con las variables de auto-sincronización y rutas correctas.

* **Opción `[2]` — VS Code con extensión Cline o Roo Code:**  
  Configura automáticamente la sección `mcpServers` en:  
  `%APPDATA%\Code\User\globalStorage\saoudrizwan.claude-dev\settings\cline_mcp_settings.json`.

* **Opción `[3]` — Claude Code CLI:**  
  Ejecuta el comando `claude mcp add atlas-knowledge-hub ...` con la sintaxis oficial.

* **Opción `[5]` — Configuración Universal (Recomendada):**  
  Detecta e inyecta la configuración en todos los editores instalados en el equipo simultáneamente.

---

### Paso 3: Activar Auto-Sincronización con Git
1. En el menú, escribe **`6`** y presiona `Enter`.
2. Introduce la ruta de la carpeta donde tienes clonado el repositorio de Laravel de desarrollo:  
   *(Ejemplo: `C:\Users\TuUsuario\Proyectos\ATLAS`)*
3. El instalador creará automáticamente los ganchos `.git/hooks/post-merge` y `.git/hooks/post-commit`.
4. **Resultado:** Cada vez que hagas `git pull` para bajar cambios de tus compañeros, el MCP reindexará los nuevos modelos y migraciones de Laravel en menos de 1 segundo de forma totalmente automática.

---

### Paso 4: Validar el Funcionamiento (Self-Test)
1. En el menú, escribe **`8`** y presiona `Enter`.
2. El sistema ejecutará un test de diagnóstico verificando:
   - ✅ Existencia de `server.py` y base de datos SQLite FTS5.
   - ✅ Lectura de tarjetas de conocimiento (< 800 tokens).
   - ✅ Handshake del protocolo JSON-RPC stdio con latencia < 15 ms.
3. Escribe **`0`** para salir de la consola.
4. Reinicia o recarga tu editor (Cursor o VS Code).

---

## 💬 4. Ejemplos de Preguntas para Probar en el Chat de la IA

Una vez abierto tu editor, puedes hacerle preguntas técnicas en el chat sin preocuparte por darle contexto adicional:

### 📌 Normativa y Comisiones al Extranjero:
* *"¿Cuáles son los plazos de anticipación obligatorios para ingresar una COMEXTRA según la Circular 0045?"*
* *"¿Qué causales provocan el bloqueo de un docente por adeudo de informe de comisión?"*
* *"¿Qué documentos requiere el expediente oficial de COMEXTRA?"*

### 🗄️ Esquemas de Base de Datos y Código:
* *"Muestra la estructura exacta y campos de la tabla com_solicitudes"*
* *"¿Qué campos incluye la tabla users y la relación con user_submodulos?"*
* *"¿Cuáles son los 7 roles del sistema v2.1 y qué alcance tiene el R4?"*

### 📊 Diagramas con Paleta Oficial TecNM:
* *"Genera un diagrama de secuencia de la autorización de COMEXTRA"*
* *"Genera un diagrama ERD con la paleta de colores institucional TecNM"*

### ☁️ Archivos en Google Drive:
* *"Lista los documentos que tenemos en la carpeta de Google Drive"*
* *"Clasifica y organiza los archivos de la carpeta de Drive en sus carpetas temáticas"*

---

## 🔄 5. ¿Cómo se actualiza el conocimiento?

No necesitas pedirle nada a la IA ni ejecutar comandos manuales:
1. **Hilo Demonio:** Cada 5 minutos escanea silenciosamente cambios en Google Drive y archivos locales.
2. **Caché Inteligente JIT:** Antes de contestar una pregunta, el servidor valida en < 5 ms si hay cambios pendientes y actualiza el índice al vuelo.
3. **Git Pull:** Si bajas código nuevo, el hook de Git actualiza la base de datos al instante.
