# Bitácora de Avances y Registro de Ingeniería: ATLAS TECNM

**Proyecto:** ATLAS TECNM (Plataforma de Indicadores Nacionales del TecNM)  
**Ubicación:** `d:\data\ATLAS TECM`  
**Responsable:** Fábrica de Software  
**Estatus General:** Identidad Gráfica Oficial, Clusters y Recursos Funcionales Listos (Semana 2)  

---

## Control de Versiones y Registro de Hitos

| Hito / Sprint | Fecha | Objetivo Principal | Estatus |
| :---: | :---: | :--- | :---: |
| **Hito 0** | 19/Sep/2026 | Arquitectura conceptual, catálogo oficial de 35 submódulos y diseño de BD | **COMPLETADO** |
| **Hito 1** | 19/Sep/2026 | Inicialización de Laravel 11, entorno local (Herd), panel Filament v3 y Git | **COMPLETADO** |
| **Hito 2** | 22/Sep/2026 | Ejecución de 10 Migraciones y 4 Seeders en Neon PostgreSQL | **COMPLETADO** |
| **Hito 3** | 22/Sep/2026 | 4 Clusters, Modelos Eloquent, Recursos MVP (MTE y COMEXTRAS) e Identidad TecNM Oficial | **COMPLETADO** |
| **Hito 4** | Pendiente | Ingesta asíncrona de archivos Excel con validación en segundo plano (*dry-run*) | *PENDIENTE (Por revisar con el equipo)* |
| **Hito 5** | Pendiente | Tablero Nacional Directivo: KPIs, semáforos y mapa coropléjico con Apache ECharts | *Pendiente* |

---

## Registro Detallado por Hito

### [Hito 0] - Definición de Arquitectura y Especificación de Datos
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Formalizar la identidad del proyecto, definir el universo de 4 ejes y 35 submódulos, acotar el alcance del MVP y diseñar la arquitectura técnica de persistencia.

---

### [Hito 1] - Inicialización del Proyecto e Instalación de Filament v3
* **Fecha:** 19 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Montar la estructura limpia de Laravel 11 en la raíz `d:\data\ATLAS TECM` y preparar el panel Filament v3.

---

### [Hito 2] - Arquitectura Data Mart, Migraciones y Seeders Maestros
* **Fecha:** 22 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Ejecución y población exitosa del esquema relacional en Neon Serverless.
* **Resultado:** 10 migraciones completadas al 100%, 4 seeders ejecutados (Ejes, Submódulos, Entidades y Periodos) y creación del usuario administrador institucional.

---

### [Hito 3] - Clusters Institucionales, Modelos, Recursos MVP e Identidad Gráfica Oficial
* **Fecha:** 22 de Septiembre de 2026
* **Responsable:** Fábrica de Software
* **Objetivo:** Construir la navegación institucional, recursos de captura del MVP y adopción estricta del Manual de Identidad Gráfica TecNM 2026.

#### 1. Entregables y Archivos Creados:
1. **Adopción del Manual de Identidad Gráfica TecNM 2026:**
   * Tipografía oficial obligatoria: **Noto Sans** (MIG Pág. 10).
   * Paleta institucional: Azul TecNM (`#1B396A` Pantone 294 C), Gris Institucional (`#807E82` Cool Gray 10 C), Dorado (`#A57F2C`), Verde oficial (`#1E5B4F`) y Guinda (`#9B2247`).
   * Acrónimo oficial: **ATLAS TecNM**.
   * Lema institucional inyectado en el pie de página: *"EXCELENCIA EN EDUCACIÓN TECNOLÓGICA®"*.
2. **Modelos Eloquent en `app/Models/`:**
   * `User.php` (Multi-Tenancy con `plantel_id`).
   * `CatEje.php`, `CatSubmodulo.php`, `CatEntidad.php`, `CatPlantel.php`, `CatPeriodo.php`.
   * `RepRegistroBase.php` (Tabla de hechos con casts JSONB y scopes).
   * `RepBitacoraCarga.php` (Auditoría de cargas).
3. **Navegación por Clusters Oficiales (`app/Filament/Clusters/`):**
   * `VinculacionEstrategica` (Eje 1).
   * `InnovacionEmprendimiento` (Eje 2).
   * `IntercambioAcademico` (Eje 3).
   * `Extension` (Eje 4).
4. **Recursos Funcionales del MVP:**
   * `MteResource` (Submódulo 2.2): Formulario reactivo Livewire con sumatorias por género en vivo, cálculo total, atributos de incubación en JSONB, subida de evidencias PDF/Excel y tabla administrativa con filtros.
   * `ComextrasResource` (Submódulo 3.1): Captura de movilidad y actividades extraescolares (deportivas, culturales, cívicas) en JSONB con evidencias y estatus de reporte.
5. **Vistas Institucionales de Submódulos ("Fase 2"):**
   * Vista Blade `submodulos-overview.blade.php` con diseño institucional y tarjetas interactivas para los 33 submódulos fuera del MVP.

---

### [Hito 4] - Ingesta Asíncrona de Archivos Excel (*Dry-Run*)
* **Estatus:** **PENDIENTE (Por revisar y validar con el equipo)**
* **Puntos de Análisis para la Mesa de Trabajo con el Team:**
  1. Definir los formatos de Excel específicos que se recibirán (ej. `F.37`, `F.32` u otros formatos oficiales).
  2. Determinar si se entregará a los planteles una plantilla canónica estandarizada descargable desde la misma plataforma.
  3. Definir las tolerancias de validación (celdas vacías, acentos, nombres de tecnológicos con discrepancias tipográficas).
  4. Flujo de aprobación tras el *Dry-Run* (si el usuario aprueba manualmente la importación al ver el preview o si se inserta automáticamente al pasar todas las pruebas).
