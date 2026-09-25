# Manual Técnico de Arquitectura y Guía de Onboarding para Desarrolladores

**Proyecto:** ATLAS TecNM (Plataforma de Indicadores Nacionales del TecNM)  
**Audiencia:** Equipo de Ingeniería y Nuevos Desarrolladores (Fábrica de Software)  
**Versión del Documento:** 1.0 (Septiembre de 2026)  
**Ubicación del Proyecto:** `d:\data\ATLAS TECM`  

---

## 1. Contexto Institucional y Propósito del Sistema

El **Tecnológico Nacional de México (TecNM)** es la institución de educación superior tecnológica más grande de México, compuesta por **263 institutos tecnológicos y centros** distribuidos en las 32 entidades federativas.

### El Problema que Resolvemos
Históricamente, la Dirección General recolectaba información operativa y de vinculación mediante hojas de cálculo descentralizadas (`F.37`, `F.32`, etc.). Este esquema presentaba graves fallas:
* Dispersión de archivos y nula trazabilidad histórica.
* Errores humanos en fórmulas de suma por género y categorías.
* Semanas de retraso para consolidar un panorama nacional de cumplimiento.

### La Misión de ATLAS TecNM
**ATLAS TecNM no es un sistema escolar transaccional (OLTP).** Es un **Data Mart Directivo y de Inteligencia de Negocios (BI)** diseñado para:
1. Proveer a oficinas centrales tableros interactivos con **mapas coropléjicos de México** (vía Leaflet.js y capas vectoriales GeoJSON).
2. Generar **semáforos de cumplimiento trimestral** (Q1 a Q4) por plantel y por estado.
3. Permitir analítica cruzada instantánea por género (Docentes y Estudiantes) y sostenimiento institucional (**Federal** vs. **Descentralizado**).

---

## 2. Stack Tecnológico

El proyecto está construido sobre estándares modernos y probados del ecosistema PHP/Laravel:

| Componente               | Tecnología        | Versión  | Propósito                                                                        |
| :-------------------------| :------------------| :---------| :---------------------------------------------------------------------------------|
| **Lenguaje**             | PHP               | `8.4+`   | Backend de alto rendimiento (ejecutado con Laravel Herd).                        |
| **Framework**            | Laravel           | `11.x`   | Núcleo de la aplicación, ORM Eloquent, Jobs y colas.                             |
| **Panel Administrativo** | Filament PHP      | `v3.3+`  | Framework TALL Stack para CRUDs reactivos y tableros.                            |
| **Capa Reactiva**        | Livewire          | `v3.8+`  | Reactividad en tiempo real sin escribir APIs complejas de JS.                    |
| **Base de Datos**        | PostgreSQL (Neon) | `16.x`   | Base de datos serverless con soporte nativo de JSONB y columnas generadas.       |
| **Cartografía**          | Leaflet.js + ESRI | `v1.9.4` | Despliegue territorial interactivo, proyección GeoJSON y capas geográficas base. |
| **Estilos y UI**         | Tailwind CSS      | `v3.x`   | Diseño adaptado al Manual de Identidad Gráfica oficial TecNM 2026.               |

---

## 3. Guía Rápida de Onboarding (Setup Local en 5 Minutos)

Sigue estos pasos para levantar el entorno de desarrollo local desde cero en Windows:

### Paso 1: Clonar el Repositorio e Instalar Dependencias
```bash
git clone https://github.com/tu-organizacion/atlas-tecnm.git
cd atlas-tecnm
composer install
```

### Paso 2: Configurar las Variables de Entorno (`.env`)
Copia el archivo de ejemplo:
```bash
cp .env.example .env
php artisan key:generate
```

Asegúrate de configurar la conexión directa a Neon PostgreSQL en `.env`:
```env
DB_CONNECTION=pgsql
DB_URL="postgresql://neondb_owner:PASSWORD@ep-quiet-thunder-awveex94.c-12.us-east-1.aws.neon.tech/neondb?sslmode=require"
```
> **REGLA CRITICA DE NEON:**  
> Para ejecutar migraciones y seeders, **NUNCA utilices el host con `-pooler`** (PgBouncer bloquea transacciones DDL de creación de tablas). Utiliza siempre la **conexión directa**.

### Paso 3: Ejecutar Migraciones y Seeders Maestros
```bash
php artisan migrate:fresh --seed
```
Este comando creará las 10 tablas del sistema y poblará automáticamente:
* Los 4 Ejes institucionales (`EJE-01` a `EJE-04`).
* Los 35 Submódulos oficiales (con `activo_mvp = true` para 2.2 y 3.1).
* Las 32 Entidades federativas con claves INEGI (`01` a `32`).
* Los 4 periodos trimestrales del año 2026.

### Paso 4: Crear tu Usuario Administrador y Servir la App
```bash
# 1. Crear credenciales
php artisan make:filament-user

# 2. Iniciar el servidor local
php artisan serve
```
Abre en tu navegador: **`http://localhost:8000/admin`** (o `http://localhost:8000`, la raíz redirige automáticamente al panel).

---

## 4. Anatomía y Mapa de Carpetas del Proyecto

Para mantener el orden arquitectónico, el código se estructura estrictamente en las siguientes carpetas:

```
ATLAS TECM/
├── app/
│   ├── Filament/
│   │   └── Clusters/                          # Los 4 Ejes Rectores del TecNM
│   │       ├── VinculacionEstrategica.php     # Cluster Eje 1
│   │       ├── InnovacionEmprendimiento.php   # Cluster Eje 2
│   │       │   └── Resources/
│   │       │       └── MteResource.php        # Recurso Funcional MVP (Submódulo 2.2)
│   │       ├── IntercambioAcademico.php       # Cluster Eje 3
│   │       │   └── Resources/
│   │       │       └── ComextrasResource.php  # Recurso Funcional MVP (Submódulo 3.1)
│   │       └── Extension.php                  # Cluster Eje 4
│   │
│   ├── Models/                                # Modelos Eloquent
│   │   ├── User.php                           # Usuarios con Multi-Tenancy (plantel_id)
│   │   ├── CatEje.php                         # Catálogo de Ejes
│   │   ├── CatSubmodulo.php                   # Catálogo de los 35 Submódulos
│   │   ├── CatEntidad.php                     # 32 Estados INEGI
│   │   ├── CatPlantel.php                     # 263 Institutos Tecnológicos
│   │   ├── CatPeriodo.php                     # Cortes Trimestrales (Q1 a Q4)
│   │   ├── RepRegistroBase.php                # Tabla central de hechos (Métricas + JSONB)
│   │   └── RepBitacoraCarga.php               # Auditoría de cargas masivas Excel
│   │
│   └── Providers/Filament/
│       └── AdminPanelProvider.php             # Configuración visual, colores TecNM y Clusters
│
├── database/
│   ├── migrations/                            # 10 migraciones ordenadas secuencialmente
│   └── seeders/                               # Seeders maestros de catálogos
│
├── docs/                                      # Acervo Documental Oficial
│   ├── AVANCES.md                             # Bitácora histórica obligatoria por sprint
│   ├── Análisis.md                            # Documento conceptual y visión estratégica
│   ├── arquitectura_bd.md                     # Especificación técnica DDL y ERD de Base de Datos
│   ├── guia_entorno_desarrollo.md             # Justificación de Laravel Herd
│   ├── justificacion_data_warehouse...md      # Fundamentación del modelo dimensional Kimball
│   └── MANUAL_TECNICO_DESARROLLADORES.md      # Este documento
│
└── resources/views/filament/clusters/
    └── submodulos-overview.blade.php          # Vista Blade reutilizable con tarjetas de submódulos
```

---

## 5. Filosofía de Base de Datos: Esquema Estrella Híbrido

No creamos 35 tablas físicas aisladas para cada submódulo. Utilizamos un **Esquema Estrella con Extensión JSONB**:

```
[ cat_periodos ] ──┐
[ cat_entidades ] ─┼──▶ [ rep_registros_base ] ◀── [ cat_submodulos ] ◀── [ cat_ejes ]
[ cat_planteles ] ─┤           ▲
                   │           │
                 users ────────┘
```

### Reglas Clave que Todo Desarrollador Debe Saber:
1. **Columnas Calculadas en Motor (`STORED ALWAYS`):**
   * Las columnas `total_docentes`, `total_estudiantes` y `total_general` se calculan físicamente en PostgreSQL:
     $$\text{total\_general} = \text{docentes\_mujeres} + \text{docentes\_hombres} + \text{estudiantes\_mujeres} + \text{estudiantes\_hombres}$$
   * **En el código de Laravel NUNCA calcules ni intentes hacer `fill` de estos campos.** PostgreSQL los calcula en disco de forma infalible.
2. **El Payload Dinámico (`detalles_adicionales JSONB`):**
   * Cada submódulo tiene datos propios (ej. MTE tiene "fase de incubación"; COMEXTRAS tiene "modalidad deportiva/cultural"; Convenios tiene "empresa aliada").
   * En lugar de alterar la tabla con `ALTER TABLE`, guarda estos datos dentro de `detalles_adicionales`:
     ```php
     Forms\Components\TextInput::make('detalles_adicionales.empresa_convenio')
     ```
   * En PostgreSQL, esta columna cuenta con un **índice GIN** para búsquedas instantáneas.
3. **Multi-Tenancy por Plantel:**
   * Si `Auth::user()->plantel_id` tiene un ID asignado, el usuario es un capturista local y **solo puede ver y registrar información de su tecnológico**.
   * Si `Auth::user()->plantel_id` es `null`, el usuario es Administrador Nacional y tiene visibilidad consolidada de los 263 planteles.

---

## 6. Guía Práctica: "¿Cómo implementar un nuevo submódulo en la plataforma?"

Si te asignan programar un nuevo submódulo (por ejemplo, el **1.2 Convenios**), sigue esta receta exacta:

### Paso 1: Identificar la Clave Oficial
Revisa `cat_submodulos` para conocer el Eje y la clave (ej. Clave `1.2`, Eje `EJE-01: Vinculación Estratégica`).

### Paso 2: Crear el Recurso de Filament en el Cluster Correspondiente
Crea el archivo en:  
`app/Filament/Clusters/VinculacionEstrategica/Resources/ConveniosResource.php`

En la clase debes declarar:
```php
namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\VinculacionEstrategica;
use App\Models\RepRegistroBase;
use Filament\Resources\Resource;

class ConveniosResource extends Resource
{
    protected static ?string $model = RepRegistroBase::class;
    protected static ?string $cluster = VinculacionEstrategica::class;
    protected static ?string $navigationLabel = '1.2 Convenios';
    protected static ?string $modelLabel = 'Convenio';
...
```

### Paso 3: Filtrar la Consulta por el Submódulo
Sobrescribe `getEloquentQuery()` para aislar los registros de ese submódulo y aplicar el Multi-Tenancy:
```php
public static function getEloquentQuery(): Builder
{
    $submodulo = CatSubmodulo::where('clave', '1.2')->first();

    $query = parent::getEloquentQuery()
        ->where('submodulo_id', $submodulo?->id ?? 0);

    $user = Auth::user();
    if ($user && ! $user->esNacional() && $user->plantel_id) {
        $query->where('plantel_id', $user->plantel_id);
    }

    return $query;
}
```

### Paso 4: Autoasignar el Submódulo al Crear
En la página `CreateConvenios.php`:
```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $submodulo = CatSubmodulo::where('clave', '1.2')->firstOrFail();
    $data['submodulo_id'] = $submodulo->id;
    $data['user_id'] = Auth::id();

    return $data;
}
```
¡Listo! El nuevo submódulo queda integrado con reportes, evidencias, sumatorias en vivo y permisos en menos de 20 minutos.

---

## 7. Identidad Gráfica y Estándares de Diseño (MIG TecNM 2026)

Todo desarrollo visual en la plataforma debe cumplir con el **Manual de Identidad Gráfica Oficial del TecNM**:

* **Tipografía Obligatoria:** `Noto Sans` (configurada globalmente en `AdminPanelProvider.php`).
* **Paleta Oficial:**
  * Primario: `#1B396A` (Azul TecNM - Pantone 294 C).
  * Acentos y Destacados: `#A57F2C` (Dorado - Pantone 1255 C).
  * Éxito / Validados: `#1E5B4F` (Verde Institucional - Pantone 626 C).
  * Alertas / Crítico: `#9B2247` (Guinda - Pantone 7420 C).
  * Neutros y Bordes: `#807E82` (Gris Institucional - Cool Gray 10 C).
* **Nomenclatura:** Se escribe formalmente **TecNM** y **ATLAS TecNM**.
* **Lema Institucional:** *"EXCELENCIA EN EDUCACIÓN TECNOLÓGICA®"*.

---

## 8. Convenciones de Código, Git y Gobernanza

1. **Flujo de Ramas (GitFlow Simplificado):**
   * `main`: Producción estable. Prohibido hacer `git push` directo.
   * `develop`: Integración y pruebas.
   * `feature/[eje-submodulo]`: Ramas individuales de trabajo derivadas de `develop`.
2. **Estilo de Código Automatizado (Laravel Pint):**
   * Antes de hacer commit de archivos PHP, formatea tu código con:
     ```bash
     vendor/bin/pint --format agent
     ```
3. **Registro Obligatorio de Avances:**
   * Cada tarea o sprint finalizado debe asentarse en **`docs/AVANCES.md`**, describiendo la fecha, hito, archivos modificados y próximos pasos.

---

## 9. Arquitectura del Tablero Directivo Nacional (Hito 5 - Storytelling UX)

El Tablero Directivo Nacional de **ATLAS TecNM** (disponible en la ruta `/admin`) fue concebido bajo el concepto de **Storytelling UX (Narrativa Ejecutiva en 3 Bloques)**. Su propósito es responder en menos de 10 segundos las tres preguntas críticas de la Dirección General del TecNM:

1. **¿Cómo vamos a nivel nacional?** (Semáforo de cumplimiento institucional de los 263 planteles).
2. **¿Dónde están los focos de atención territorial?** (Cartografía interactiva por entidad federativa).
3. **¿Cuál es el impacto sustantivo de los programas?** (Paridad de género y vocación técnica/deportiva).

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│ BLOQUE 1: Pulso Nacional (Corte trimestral, 263 planteles, KPIs de paridad)    │
├───────────────────────────────────────────────────────┬─────────────────────────┤
│ BLOQUE 2: Cartografía Nacional (Leaflet + ESRI Canvas) │ Panel Detalle Estatal   │
│ • 32 entidades con gradiente Azul TecNM (#1B396A)      │ • Semáforo local        │
│ • Tooltips enriquecidos al pasar cursor               │ • Federales vs Descen.  │
│ • Filtros por submódulo (MTE/COMEXTRAS) y sostenimiento│ • Top 5 de cobertura    │
├───────────────────────────────────────────────────────┴─────────────────────────┤
│ BLOQUE 3: Analítica de Inclusión, Paridad y Sectores Estratégicos              │
│ • Equidad Estudiantes / Docentes  • Sectores MTE 2.2  • Modalidades COMEXTRAS   │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### Componente 1: `PulsoNacionalWidget`
* **Archivo PHP:** `app/Filament/Widgets/PulsoNacionalWidget.php`
* **Vista Blade:** `resources/views/filament/widgets/pulso-nacional-widget.blade.php`
* **Lógica clave:**
  * Determina el periodo trimestral activo mediante `CatPeriodo::where('bloqueado', false)` y calcula los días restantes antes del cierre.
  * Monitorea la meta de los **263 planteles** divididos en:
    * **Publicados:** Planteles con al menos un reporte oficializado en el trimestre actual (Verde `#1E5B4F`).
    * **En Borrador:** Planteles que han iniciado captura pero no han concluido (Dorado `#A57F2C`).
    * **Sin Reporte:** Planteles rezagados sin actividad registrada (Guinda `#9B2247`).
  * Consolida la sumatoria de personas reportadas (Personal Docente + Comunidad Estudiantil) y calcula el ratio de paridad global.

### Componente 2: `MapaRepublicaWidget`
* **Archivo PHP:** `app/Filament/Widgets/MapaRepublicaWidget.php`
* **Vista Blade:** `resources/views/filament/widgets/mapa-republica-widget.blade.php`
* **Lógica y Arquitectura Cartográfica:**
  * **Motor:** Leaflet v1.9.4 empaquetado localmente en `public/js/leaflet/`.
  * **Capa Base:** Mosaicos vectoriales de alta fidelidad **ESRI ArcGIS Dark Gray Canvas** (`World_Dark_Gray_Base`), libre de marcas de agua y optimizada para temas oscuros.
  * **Capa Vectorial:** Vector oficial GeoJSON de las 32 entidades federativas (`public/js/maps/mexico.json`). Cada polígono contiene la clave INEGI oficial (`01` al `32`).
  * **Reactividad Bidireccional:**
    * Al hacer clic en un estado en el mapa de Leaflet, se emite el método Livewire `selectEstado(claveInegi)`.
    * El panel lateral derecho se actualiza al instante con el semáforo local, desglose de institutos federales vs. descentralizados y población participante.
    * Si el usuario selecciona un estado desde el listado Top 5, Leaflet detecta el cambio reactivo mediante `$watch('selectedClave')`, hace zoom suave y enfoca la entidad seleccionada.
  * **Filtros cruzados:** Selector dinámico por Submódulo (MTE 2.2, COMEXTRAS 3.1) y Sostenimiento (Federal vs. Descentralizado).

### Componente 3: `InclusionSectoresWidget`
* **Archivo PHP:** `app/Filament/Widgets/InclusionSectoresWidget.php`
* **Vista Blade:** `resources/views/filament/widgets/inclusion-sectores-widget.blade.php`
* **Lógica clave:**
  * **Paridad de Género:** Calcula de forma desagregada el porcentaje de mujeres y hombres en estudiantes y en personal docente, generando el diagnóstico institucional de brecha.
  * **Sectores MTE (2.2):** Agrupa los proyectos registrados en el atributo JSONB `datos_especificos['sector_estrategico']` en las 5 vocaciones clave: TI y Software, Agroindustria, Energía, Manufactura Avanzada/Aeroespacial y Salud.
  * **Modalidades COMEXTRAS (3.1):** Agrupa la participación del atributo JSONB `datos_especificos['tipo_modalidad']` (Deportiva, Cultural, Cívica, Movilidad Internacional y Nacional).
  * **Respaldo estadístico:** Cuando no hay capturas aún en la base de datos, el widget presenta benchmarks institucionales ponderados para garantizar que el tablero siempre brinde valor visual y analítico.

---

## 10. Guía de Presentación Técnica para la Reunión con el Equipo

Para tu reunión con el equipo de desarrollo y liderazgo técnico, apóyate en esta estructura clara y contundente:

### 1. El Porqué del Proyecto (Elevator Pitch)
* *"Pasamos de hojas de cálculo aisladas (F.37 y F.32) a un Data Mart institucional centralizado sobre PostgreSQL 16 y Laravel 11/Filament v3."*
* *"El sistema está diseñado para que la Dirección General conozca en tiempo real el estatus de los 263 institutos tecnológicos en los 4 ejes institucionales."*

### 2. Demostración en Vivo del Tablero Directivo (`/admin`)
* **Bloque 1 (Pulso Nacional):**
  * Mostrar el corte activo y la cuenta regresiva de días.
  * Resaltar el semáforo institucional: Publicados (Verde), Borrador (Dorado), Sin Reporte (Guinda) sobre la meta de 263 planteles.
* **Bloque 2 (Cartografía con Leaflet):**
  * Demostrar la navegación geográfica fluida en México sobre la capa ESRI Dark Canvas.
  * Mostrar el hover sobre los estados (ej. Veracruz, Jalisco, CDMX) con sus tooltips informativos.
  * Dar clic en un estado para ver cómo el panel lateral fija el semáforo local y el desglose de institutos federales vs. descentralizados.
  * Demostrar la sincronización inversa: hacer clic en el Top 5 para que el mapa vuele automáticamente a ese estado.
  * Probar los filtros superiores (cambiar entre Submódulos y Sostenimiento).
* **Bloque 3 (Inclusión y Sectores):**
  * Presentar la analítica de paridad por género diferenciando estudiantes de docentes.
  * Mostrar la distribución de vocaciones de proyectos MTE y disciplinas formativas COMEXTRAS.

### 3. Puntos Fuertes de Arquitectura para el Equipo
* **Zero Dependencies externas críticas:** Leaflet, el GeoJSON y los estilos están empaquetados localmente en el repositorio.
* **Extensibilidad JSONB:** Los submódulos nuevos solo definen sus atributos en `datos_especificos` sin requerir migraciones complejas continuas.
* **Multi-Tenancy transparente:** El campo `plantel_id` en la tabla `users` determina si el usuario ve toda la República (Dirección General) o únicamente su propio instituto (Director de Plantel / Capturista).
* **Estandarización de código:** Todo el código sigue PSR-12 formateado automáticamente con Laravel Pint (`vendor/bin/pint --format agent`).
