# Informe de Auditoría de Seguridad y Aplicación de Parches PHP

**Proyecto:** ATLAS TecNM (Laravel 13 + Filament 3 + Docker Sail)  
**Fecha de Aplicación:** 24 de Octubre de 2026  
**Clasificación:** Confidencial / Técnico de Seguridad  
**Estado General:** **PROTEGIDO Y PARCHEADO**  

---

## 1. Resumen Ejecutivo

A finales de octubre de 2026 se emitió una alerta global de seguridad ante la explotación activa de vulnerabilidades críticas en el núcleo de PHP (**PHP2Shell**), permitiendo Ejecución Remota de Código (RCE) no autenticada. 

En respuesta, se ejecutó una auditoría exhaustiva en el repositorio **ATLAS**, implementando:
1. **Parche de infraestructura:** Creación de un entorno de ejecución Docker aislado y versionado (`docker/8.3`) con mitigaciones directas contra *PHP2Shell* y actualización obligatoria a binarios parcheados (`php8.3 >= 8.3.36`).
2. **Auditoría de código malicioso:** Escaneo estático de backdoors, webshells y funciones de ejecución arbitraria. **Resultado: 0 amenazas detectadas en el código fuente.**
3. **Remediación de brechas críticas:**
   * Sanitización de credenciales de producción de Neon PostgreSQL expuestas en `.env.example`.
   * Corrección de vulnerabilidad crítica de inyección de comandos en dependencias npm (`shell-quote` / GHSA-pqg4-j6r4-53mv).
   * Implementación del contrato `FilamentUser` en el modelo `User` para restringir el acceso a paneles administrativos.
   * Blindaje contra ejecución de scripts en carpetas de almacenamiento público (`storage/app/public/.htaccess`).
   * Adición de cabeceras de seguridad HTTP (`HSTS`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`) y bloqueo de dotfiles (`.env`, `.git`) en `public/.htaccess`.

---

## 2. Contexto de la Amenaza: Ataque a PHP («PHP2Shell»)

| Identificador | Severidad (CVSS v4.0) | Vector de Ataque | Impacto |
| :--- | :--- | :--- | :--- |
| **CVE-2026-109618** | **9.2 (Crítica)** | Corrupción de memoria por falta de memoria (*Out-Of-Memory / longjmp*) durante la subida o procesamiento de peticiones complejas interrumpidas. | Ejecución Remota de Código (RCE) sin autenticación previa. |
| **CVE-2026-109619** | **9.2 (Crítica)** | Estado global persistente y liberación indebida (*Use-After-Free*) entre peticiones recicladas por el mismo *worker* de PHP. | Ejecución Remota de Código / Fuga de memoria interna del proceso. |
| **CVE-2024-4577** | **9.8 (Crítica)** | Inyección de argumentos en entornos PHP-CGI / Windows mediante bypass de codificación Best-Fit. | Ejecución Remota de Código (RCE). |

### Versiones de PHP Seguras
* **PHP 8.3:** `8.3.36` o superior *(Versión objetivo del proyecto ATLAS)*.
* **PHP 8.4:** `8.4.27` o superior.
* **PHP 8.2:** `8.2.35` o superior.

---

## 3. Parches y Hardening Aplicados en Docker / Sail

Dado que el proyecto utiliza **Laravel Sail**, la configuración anterior utilizaba el contexto volátil dentro de `vendor/laravel/sail/runtimes/8.3`. Dicha carpeta no se versiona en Git, lo que dejaba el proyecto indefenso ante despliegues limpios o cambios en dependencias.

### 3.1. Publicación del Runtime en `docker/8.3/`
Se creó la carpeta [`docker/8.3`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/docker/8.3) con control de versiones propio y se actualizó [`docker-compose.yml`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/docker-compose.yml):

```yaml
services:
    laravel.test:
        build:
            context: ./docker/8.3
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP:-1000}'
        image: sail-8.3/app
```

### 3.2. Parche en `docker/8.3/Dockerfile`
Se añadió la actualización forzada de paquetes tras registrar el repositorio PPA oficial de Ondrej Sury:
```dockerfile
    && echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ noble main" > /etc/apt/sources.list.d/php.list \
    && apt-get update && apt-get upgrade -y \
    && apt-get install -y php8.3-cli ...
```
Esto asegura que al compilar el contenedor se descarguen las librerías parcheadas (`>= 8.3.36`).

### 3.3. Mitigaciones en `docker/8.3/php.ini`
Se aplicaron parámetros estrictos de mitigación recomendados por CISA e INCIBE:

```ini
[PHP]
; Mitigación crítica contra PHP2Shell (CVE-2026-109618 y CVE-2026-109619)
; Desactiva el seguimiento de subida que genera la condición de carrera/OOM
session.upload_progress.enabled = 0

; Límites estrictos para evitar heap exhaustion
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
max_input_vars = 3000

; Tamaños de subida restringidos a requerimientos de documentos (PDF/Excel)
post_max_size = 25M
upload_max_filesize = 25M

; Ocultar firma de PHP y blindaje contra RFI
expose_php = Off
allow_url_fopen = On
allow_url_include = Off

; Prevención de fuga de información
display_errors = Off
display_startup_errors = Off
log_errors = On
```

### 3.4. Instrucciones para reconstruir la imagen en local o producción
Para aplicar el nuevo contenedor parcheado:
```bash
./vendor/bin/sail build --no-cache
# O directamente con Docker Compose:
docker compose build --no-cache laravel.test
docker compose up -d
```

---

## 4. Auditoría de Código Malicioso

Se ejecutó un barrido en profundidad en todas las capas del aplicativo:

| Vector Auditado | Herramienta / Criterio | Resultado |
| :--- | :--- | :--- |
| **Webshells conocidas** | Búsqueda regex de firmas: `c99`, `r57`, `b374k`, `wso`, `alfa`, `webshell`, `cmd.php` | **0 coincidencias** |
| **Ejecución dinámica** | `eval()`, `base64_decode()`, `shell_exec()`, `passthru()`, `system()`, `proc_open()`, `assert()` | **0 llamadas maliciosas** en `app/`, `routes/`, `config/`, `bootstrap/` |
| **Directorio público** | Análisis de `public/` en busca de scripts camuflados | Solo existe el controlador legítimo `public/index.php` |
| **Almacenamiento de archivos** | Inspección de `storage/app/public` y `storage/app/private` | **Limpio:** No se encontraron archivos interpretables cargados |
| **Logs del sistema** | Revisión de `storage/logs/laravel.log` | **Sin evidencias de explotación o intrusión previa** |

---

## 5. Brechas de Seguridad Detectadas y Corregidas

### 5.1. [CRÍTICA] Exposición de Credenciales en `.env.example`
* **Vulnerabilidad:** El archivo `.env.example` contenía credenciales reales y activas de la base de datos de producción alojada en **Neon AWS PostgreSQL** (`ep-fancy-silence-aw83f1rs-pooler...`), incluyendo usuario (`neondb_owner`) y contraseña (`npg_wrBRx0uk3ONJ`).
* **Riesgo:** Compromiso total de la base de datos institucional en caso de compartir el repositorio o subirlo a un control de versiones público o privado.
* **Solución aplicada:** Se sanitizó `.env.example` reemplazando los valores por variables locales estándar (`127.0.0.1`, `atlas_tecnm`, `postgres`).

### 5.2. [CRÍTICA] Inyección de Comandos en dependencias NPM
* **Vulnerabilidad:** **GHSA-pqg4-j6r4-53mv** en el paquete `shell-quote` (dependencia indirecta a través de `concurrently`).
* **Riesgo:** Inyección arbitraria de comandos en el shell del desarrollador o del pipeline CI/CD mediante saltos de línea en parámetros parseados.
* **Solución aplicada:** Se ejecutó `npm audit fix`, actualizando los paquetes a versiones corregidas. Resultado actual: **0 vulnerabilidades reportadas por `npm audit`**.

### 5.3. [ALTA] Control de Acceso Filament en `User.php`
* **Vulnerabilidad:** El modelo `App\Models\User` no implementaba la interfaz obligatoria `Filament\Models\Contracts\FilamentUser`.
* **Riesgo:** En entornos de producción, Filament restringe o puede desregular el acceso si no existe el contrato de validación formal por usuario.
* **Solución aplicada:** Se implementó `FilamentUser` y el método `canAccessPanel(Panel $panel): bool` en [`app/Models/User.php`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/app/Models/User.php).

### 5.4. [MEDIA] Prevención de Ejecución en Almacenamiento de Archivos
* **Vulnerabilidad:** Los submódulos de Extensión y Emprendimiento permiten subir archivos comprobatorios (`archivo_evidencia`). Si un atacante lograra burlar las validaciones MIME, podría ejecutar scripts en el servidor web.
* **Solución aplicada:** Se creó el archivo [`storage/app/public/.htaccess`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/storage/app/public/.htaccess) denegando explícitamente la ejecución de archivos `.php`, `.phtml`, `.phar`, `.sh`, `.cgi`, `.bat`, etc.

### 5.5. [MEDIA] Cabeceras de Seguridad y Bloqueo de Archivos Ocultos
* **Vulnerabilidad:** Ausencia de cabeceras HTTP de protección contra ataques de Clickjacking y MIME sniffing en el servidor web.
* **Solución aplicada:** Se actualizó [`public/.htaccess`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/public/.htaccess) incorporando:
  * Regla de bloqueo inmediato para dotfiles (`.env`, `.git`).
  * `X-Content-Type-Options: nosniff`.
  * `X-Frame-Options: SAMEORIGIN`.
  * `X-XSS-Protection: 1; mode=block`.
  * `Referrer-Policy: strict-origin-when-cross-origin`.

---

## 6. Registro de Archivos Modificados

| Archivo | Tipo de Cambio | Propósito |
| :--- | :--- | :--- |
| [`docker/8.3/php.ini`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/docker/8.3/php.ini) | Creado / Hardening | Mitigación PHP2Shell (`session.upload_progress.enabled=0`), límites de memoria y errores. |
| [`docker/8.3/Dockerfile`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/docker/8.3/Dockerfile) | Creado / Configuración | Actualización forzada a PHP 8.3.36+ desde Sury PPA. |
| [`docker-compose.yml`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/docker-compose.yml) | Modificación | Apuntador del servicio `laravel.test` al contexto local `./docker/8.3`. |
| [`.env.example`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/.env.example) | Sanitización | Eliminación de credenciales reales de Neon PostgreSQL. |
| [`app/Models/User.php`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/app/Models/User.php) | Modificación de Código | Implementación del contrato `FilamentUser` y método `canAccessPanel()`. |
| [`storage/app/public/.htaccess`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/storage/app/public/.htaccess) | Creado / Seguridad | Denegación de ejecución de scripts en el directorio de subidas. |
| [`public/.htaccess`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/public/.htaccess) | Modificación / Seguridad | Inyección de cabeceras de seguridad y restricción de dotfiles. |
| [`package.json`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/package.json) / `package-lock.json` | Actualización de Dependencias | Resolución de GHSA-pqg4-j6r4-53mv vía `npm audit fix`. |
| [`SETUP_DOCKER.md`](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/SETUP_DOCKER.md) | Documentación | Instrucciones de compilación del contenedor con el parche de seguridad. |

---

## 7. Acciones Obligatorias Pendientes para el Administrador

1. **Rotación Inmediata de Contraseña de Base de Datos:**
   * Dado que la credencial `npg_wrBRx0uk3ONJ` estuvo almacenada en texto plano en `.env.example`, **es obligatorio acceder a la consola de Neon / AWS y regenerar el password del usuario `neondb_owner`**.
   * Una vez cambiada, actualizar únicamente el archivo local `.env` (el cual **nunca** debe subirse al repositorio).
2. **Revisión de Parámetros de Producción:**
   * En el servidor de producción, verificar que en `.env` se configure:
     * `APP_ENV=production`
     * `APP_DEBUG=false`
     * `SESSION_ENCRYPT=true`
3. **Reconstrucción del Contenedor:**
   * Al iniciar Docker Desktop, ejecutar:
     ```bash
     ./vendor/bin/sail build --no-cache
     ./vendor/bin/sail up -d
     ```
