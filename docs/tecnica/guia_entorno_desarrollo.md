# Justificación Técnica del Entorno Local: Laravel Herd

**Proyecto:** ATLAS TECNM  
**Documento:** Guía y Justificación del Entorno de Desarrollo Local  
**Equipo:** Fábrica de Software  
**Fecha:** Septiembre de 2026  

---

## 1. ¿Qué es Laravel Herd?

**Laravel Herd** es el entorno de desarrollo oficial, ligero y de alto rendimiento creado específicamente para el ecosistema Laravel por sus desarrolladores originales (Taylor Otwell y Beyond Code).

A diferencia de paquetes clásicos como XAMPP o configuraciones manuales, Herd empaqueta **PHP (versión 8.3)**, **Composer** y un servidor web ultrarrápido sin requerir máquinas virtuales, contenedores pesados ni edición manual de variables de entorno del sistema operativo.

---

## 2. ¿Por qué se eligió para ATLAS TECNM en Windows?

Para cumplir con la ventana de desarrollo de **7 semanas del MVP**, el equipo necesita velocidad, estabilidad y cero tiempo perdido en problemas de configuración de bajo nivel.

| Criterio | Instalación Manual / XAMPP | Docker Puro en Windows (WSL2) | Laravel Herd (⭐ Elección Oficial) |
| :--- | :--- | :--- | :--- |
| **Tiempo de Setup** | 1 a 2 horas (configurando `.ini`, `PATH` y extensiones) | 30 a 60 min (descarga de imágenes Docker pesadas) | **2 minutos (1 solo instalador)** |
| **Consumo de RAM** | Medio (servicios en segundo plano) | Alto (WSL2 puede consumir 4GB a 8GB de RAM) | **Mínimo (~50MB a 100MB)** |
| **Soporte PostgreSQL** | Requiere habilitar librerías `pdo_pgsql` a mano | Nativo en contenedor | **Nativo y preactivado** |
| **Compatibilidad con Filament v3** | Suele fallar por falta de `intl`, `gd` o `fileinfo` | Compatible | **100% optimizado para Filament** |
| **Rendimiento de Archivos** | Bueno | Lento si el proyecto está en discos secundarios (D:) | **Velocidad nativa en disco D:** |

> **Nota Crítica sobre el Disco D:**  
> Cuando se usa Docker en Windows con WSL2, acceder a archivos fuera del disco `C:` (como `d:\data\ATLAS TECM`) sufre una penalización drástica de velocidad en lectura y escritura. **Laravel Herd corre directamente sobre la API de Windows**, permitiendo compilar y ejecutar en el disco `D:` a máxima velocidad.

---

## 3. Componentes y Extensiones que Resuelve de Fábrica

Filament v3 y PostgreSQL 16 exigen una serie de extensiones críticas que Herd entrega preconfiguradas:

1. **`pdo_pgsql` y `pgsql`:** Controladores indispensables para comunicarse con la base de datos PostgreSQL en Neon.
2. **`intl`:** Para localización, traducción y formato de fechas institucionales en español de México.
3. **`fileinfo` y `gd`:** Para la carga, lectura y validación de archivos Excel (`.xlsx`) y avatares/logotipos institucionales.
4. **`zip`:** Requerida por Composer para descargar librerías y por el motor de ingesta de hojas de cálculo de Excel.
5. **`mbstring`:** Manejo seguro de caracteres con acentos y caracteres especiales del idioma español.

---

## 4. Portabilidad y Trabajo en Equipo

El uso de Herd en tu máquina local **no genera ninguna dependencia propietaria ni compromete al proyecto**:
* El código fuente de **ATLAS TECNM** es **100% Laravel 11 estándar**.
* Si otro desarrollador de la Fábrica de Software trabaja en Linux, Mac o prefiere Docker con Laravel Sail, el código, las migraciones y los modelos funcionarán de manera exactamente idéntica.
* La base de datos es agnóstica: tanto local como en producción nos conectamos al mismo motor **PostgreSQL 16**.

---

## 5. Verificación de la Instalación

Una vez concluido el asistente de instalación de Herd, abre una nueva ventana de terminal PowerShell en `d:\data\ATLAS TECM` y ejecuta:

```powershell
# 1. Comprobar que PHP 8.3 esté disponible
php -v

# 2. Comprobar que Composer esté disponible
composer -V

# 3. Comprobar que la extensión de PostgreSQL esté activa
php -m | findstr pgsql
```

*(Si `pgsql` y `pdo_pgsql` aparecen en la lista, el entorno está 100% blindado y listo para iniciar el código de ATLAS TECNM).*
