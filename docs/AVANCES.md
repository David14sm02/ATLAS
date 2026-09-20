# Bitácora de Avances y Registro de Ingeniería: ATLAS TECNM

**Proyecto:** ATLAS TECNM (Plataforma de Indicadores Nacionales del TecNM)  
**Ubicación:** `d:\data\ATLAS TECM`  
**Responsable:** Fábrica de Software  
**Estatus General:** En Fase de Configuración de Infraestructura y Base de Datos (Semana 1)  

---

## Control de Versiones y Registro de Hitos

| Hito / Sprint | Fecha | Objetivo Principal | Estatus |
| :---: | :---: | :--- | :---: |
| **Hito 0** | 19/Sep/2026 | Arquitectura conceptual, catálogo oficial de 35 submódulos y diseño de BD | **COMPLETADO** |
| **Hito 1** | 19/Sep/2026 | Inicialización de Laravel 11, entorno local y panel administrativo con Filament v3 | **COMPLETADO** |
| **Hito 2** | 19/Sep/2026 | Configuración de PostgreSQL (Neon), migraciones y seeders maestros | *En progreso* |
| **Hito 3** | Pendiente | Configuración de Clusters en Filament (4 Ejes) y Recursos MVP (2.2 MTE y 3.1 COMEXTRAS) | *Pendiente* |
| **Hito 4** | Pendiente | Ingesta asíncrona de archivos Excel con validación en segundo plano (*dry-run*) | *Pendiente* |
| **Hito 5** | Pendiente | Tablero Nacional Directivo: KPIs, semáforos y mapa coropléjico con Apache ECharts | *Pendiente* |

---

## Registro Detallado por Hito

### [Hito 0] - Definición de Arquitectura y Especificación de Datos
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Formalizar la identidad del proyecto, definir el universo de 4 ejes y 35 submódulos, acotar el alcance del MVP y diseñar la arquitectura técnica de persistencia.

#### 1. Entregables y Archivos Creados:
1. `Análisis.md`:
   * Documento conceptual maestro.
   * Formalización del nombre oficial: **ATLAS TECNM** (se descartó la propuesta preliminar PINT).
   * Mapeo jerárquico completo de los 4 ejes y 35 submódulos institucionales numerados.
   * Acotación del MVP a dos submódulos de vinculación: **2.2 Modelo Talento Emprendedor (MTE)** y **3.1 Movilidad Nacional e Internacional / COMEXTRAS**.
   * Definición de matriz de 6 roles en 4 niveles de acceso (R1 a R6).
2. `arquitectura_bd.md`:
   * Especificación técnica profunda de la base de datos en PostgreSQL 16 (Neon Serverless).
   * Esquema estrella relacional con extensión JSONB indexada con GIN (`detalles_adicionales`).
   * Columnas generadas `STORED ALWAYS` para garantizar cálculo automático e infalible de totales por género a nivel disco.
   * Diagrama Entidad-Relación (ERD) en Mermaid.
   * Código de migraciones para Laravel 11 y sentencias DDL completas.
   * Configuración de alta concurrencia con PgBouncer Connection Pooler (puerto 6543).

#### 2. Decisiones de Ingeniería Clave:
* Se descartó el uso de NoSQL (MongoDB) por la naturaleza relacional de los datos y el volumen controlado (~263 planteles en 4 cortes trimestrales).
* Se definió un flujo pragmático de dos estados para el MVP (`Borrador` $\rightarrow$ `Publicado`) para evitar bloqueos burocráticos y cumplir con la ventana de 7 semanas hacia noviembre.

### [Hito 1] - Inicialización del Proyecto e Instalación de Filament v3
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Montar la estructura limpia de Laravel 11 en la raíz `d:\data\ATLAS TECM` y preparar el panel Filament v3.

#### 1. Actividades Realizadas:
* Verificación exitosa del entorno local: **PHP 8.4** con extensiones `pdo_pgsql` y `pgsql` habilitadas a través de Laravel Herd.
* Descarga de **Laravel 11** y migración atómica de archivos a la raíz (`d:\data\ATLAS TECM`) preservando la documentación del proyecto.
* Instalación exitosa de **Laravel Boost**.
* Instalación e inicialización de **Filament v3 (v3.3.55)** y **Livewire (v3.8.9)**.
* Generación del panel administrativo institucional (`app/Providers/Filament/AdminPanelProvider.php`).
* Creación de `guia_entorno_desarrollo.md` documentando la justificación de Herd frente a opciones alternativas.

---
*(Los siguientes hitos se irán agregando automáticamente en este documento conforme avancemos).*
