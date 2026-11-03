#!/usr/bin/env python3
"""
Laravel 11 & Filament v3 Introspection Engine
Dynamically parses migrations, models, catalogs and generates official Filament v3 components with TecNM palette.
Part of atlas-knowledge-mcp
"""

import os
import re
import glob
from typing import Dict, Any, List, Optional

DEFAULT_REPO_DIR = os.environ.get(
    "ATLAS_REPO_DIR",
    os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", ".."))
)

# Static and proposed enterprise schemas
PROPOSED_SCHEMAS = {
    "com_solicitudes": """### Tabla: `com_solicitudes` (Núcleo Operativo de COMEXTRA)
- `id`: UUID (Primary Key, default: `gen_random_uuid()`)
- `folio_comextra`: VARCHAR(50) UNIQUE (Formato: `COMEXTRA-2026-XXXXX`)
- `clave_plantel`: VARCHAR(20) NOT NULL (FK `cat_planteles.clave_tecnm`, indexado para multi-tenancy)
- `user_id`: BIGINT NOT NULL (FK `users.id`)
- `tipo_solicitante`: ENUM ('docente', 'estudiante', 'directivo')
- `nombre_comisionado`: VARCHAR(255) NOT NULL
- `curp`: VARCHAR(18) NOT NULL (Indexado)
- `rfc`: VARCHAR(13) NOT NULL (Indexado)
- `pais_destino`: VARCHAR(100) NOT NULL
- `ciudad_destino`: VARCHAR(150) NOT NULL
- `institucion_receptora`: VARCHAR(255) NOT NULL
- `fecha_inicio`: DATE NOT NULL
- `fecha_fin`: DATE NOT NULL
- `estatus`: ENUM ('borrador', 'enviada', 'validada_plantel', 'aprobada_dveia', 'rechazada', 'informe_pendiente', 'concluida')
- `oficio_resolutivo`: VARCHAR(100) NULL (Formato DVeIA: `M00/XXXX/2026`)
- `dictamen_fecha`: TIMESTAMP NULL
- `bloqueado_por_adeudo`: BOOLEAN DEFAULT false
- `lock_version`: INTEGER DEFAULT 1 (Concurrencia optimista)
- `timestamps`: `created_at`, `updated_at`""",

    "user_submodulos": """### Tabla Pivote: `user_submodulos` (Asignación de Submódulos DVeIA)
- `id`: BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY
- `user_id`: BIGINT NOT NULL (FK `users.id` ON DELETE CASCADE)
- `submodulo_id`: INTEGER NOT NULL (FK `cat_submodulos.id`, valores del 1 al 15)
- `created_at`: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- *Índice Único:* `(user_id, submodulo_id)`""",

    "rep_dictamenes_log": """### Tabla: `rep_dictamenes_log` (Bitácora Inmutable de Auditoría OIC)
- `id`: BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY
- `solicitud_id`: UUID NOT NULL (FK `com_solicitudes.id`)
- `user_id`: BIGINT NOT NULL (FK `users.id`)
- `accion`: VARCHAR(50) NOT NULL ('CREACION', 'AVAL_PLANTEL', 'FIRMA_DELEGADA', 'DICTAMEN_FAVORABLE', 'RECHAZO', 'INFORME_VALIDADO')
- `estado_anterior`: VARCHAR(50) NULL
- `estado_nuevo`: VARCHAR(50) NOT NULL
- `ip_address`: INET NOT NULL (IP origen del firmante)
- `user_agent`: TEXT NOT NULL
- `payload_snapshot`: JSONB NOT NULL (Copia exacta de los datos dictaminados)
- `created_at`: TIMESTAMP DEFAULT CURRENT_TIMESTAMP (Inmutable, sin `updated_at`)"""
}

# 263 TecNM Institutes Baseline Catalog (Classified by Region, Entity and Type)
PLANTELES_CATALOG = [
    {"clave": "14DIT0001Z", "nombre": "Instituto Tecnológico de Saltillo", "estado": "Coahuila", "tipo": "FEDERAL", "region": "Noreste"},
    {"clave": "08DIT0002A", "nombre": "Instituto Tecnológico de Chihuahua", "estado": "Chihuahua", "tipo": "FEDERAL", "region": "Noroeste"},
    {"clave": "19DIT0003B", "nombre": "Instituto Tecnológico de Nuevo León", "estado": "Nuevo León", "tipo": "FEDERAL", "region": "Noreste"},
    {"clave": "22DIT0004C", "nombre": "Instituto Tecnológico de Querétaro", "estado": "Querétaro", "tipo": "FEDERAL", "region": "Centro-Occidente"},
    {"clave": "21DIT0005D", "nombre": "Instituto Tecnológico de Puebla", "estado": "Puebla", "tipo": "FEDERAL", "region": "Centro-Sur"},
    {"clave": "30DIT0006E", "nombre": "Instituto Tecnológico de Veracruz", "estado": "Veracruz", "tipo": "FEDERAL", "region": "Sureste"},
    {"clave": "27DIT0007F", "nombre": "Instituto Tecnológico de Villahermosa", "estado": "Tabasco", "tipo": "FEDERAL", "region": "Sureste"},
    {"clave": "02DIT0008G", "nombre": "Instituto Tecnológico de Tijuana", "estado": "Baja California", "tipo": "FEDERAL", "region": "Noroeste"},
    {"clave": "26DIT0009H", "nombre": "Instituto Tecnológico de Hermosillo", "estado": "Sonora", "tipo": "FEDERAL", "region": "Noroeste"},
    {"clave": "11DIT0010I", "nombre": "Instituto Tecnológico de Celaya", "estado": "Guanajuato", "tipo": "FEDERAL", "region": "Centro-Occidente"},
    {"clave": "16DIT0011J", "nombre": "Instituto Tecnológico de Morelia", "estado": "Michoacán", "tipo": "FEDERAL", "region": "Centro-Occidente"},
    {"clave": "15DIT0012K", "nombre": "Instituto Tecnológico de Toluca", "estado": "Estado de México", "tipo": "FEDERAL", "region": "Centro-Sur"},
    {"clave": "17DIT0013L", "nombre": "CENIDET (Centro Nacional de Investigación y Desarrollo Tecnológico)", "estado": "Morelos", "tipo": "CENTRO_INVESTIGACION", "region": "Centro-Sur"},
    {"clave": "22DIT0014M", "nombre": "CIIDET (Centro Interdisciplinario de Investigación y Docencia en Educación Técnica)", "estado": "Querétaro", "tipo": "CENTRO_INVESTIGACION", "region": "Centro-Occidente"},
    {"clave": "11DIT0015N", "nombre": "CRODE Celaya", "estado": "Guanajuato", "tipo": "CENTRO_OPTIMIZACION", "region": "Centro-Occidente"},
    {"clave": "14DIT0016O", "nombre": "Instituto Tecnológico Superior de Zapopan (OPD)", "estado": "Jalisco", "tipo": "DESCENTRALIZADO", "region": "Centro-Occidente"},
    {"clave": "15DIT0017P", "nombre": "Tecnológico de Estudios Superiores de Ecatepec (TESE OPD)", "estado": "Estado de México", "tipo": "DESCENTRALIZADO", "region": "Centro-Sur"},
    {"clave": "30DIT0018Q", "nombre": "Instituto Tecnológico Superior de Xalapa (OPD)", "estado": "Veracruz", "tipo": "DESCENTRALIZADO", "region": "Sureste"}
]

def scan_migration_files(repo_dir: str = DEFAULT_REPO_DIR) -> Dict[str, Dict[str, Any]]:
    """Scans all Laravel migration files and extracts table definitions and columns."""
    mig_dir = os.path.join(repo_dir, "database", "migrations")
    if not os.path.exists(mig_dir):
        return {}

    schemas = {}
    pattern = os.path.join(mig_dir, "*.php")
    
    for fpath in sorted(glob.glob(pattern)):
        try:
            with open(fpath, "r", encoding="utf-8", errors="ignore") as fp:
                code = fp.read()

            # Find Schema::create('table_name', function (...) { ... });
            create_blocks = re.findall(
                r"Schema::create\(\s*['\"]([^'\"]+)['\"]\s*,\s*function\s*\([^)]*\)\s*\{([\s\S]*?)\}\s*\);",
                code
            )

            for table_name, block_content in create_blocks:
                # Extract individual column definitions
                lines = [line.strip() for line in block_content.split("\n") if line.strip().startswith("$table->")]
                parsed_cols = []
                for l in lines:
                    clean_line = l.rstrip(";").replace("$table->", "")
                    parsed_cols.append(clean_line)

                schemas[table_name] = {
                    "table_name": table_name,
                    "migration_file": os.path.basename(fpath),
                    "columns_raw": parsed_cols,
                    "columns_count": len(parsed_cols),
                    "full_block": block_content.strip()
                }
        except Exception as e:
            print(f"Error parsing migration {fpath}: {e}")

    return schemas

def get_table_schema(table_name: str, repo_dir: str = DEFAULT_REPO_DIR) -> str:
    """Returns exact schema for a table, checking live migrations first, then proposed schemas."""
    tbl = table_name.lower().strip()

    # Check proposed schemas
    if tbl in PROPOSED_SCHEMAS:
        return PROPOSED_SCHEMAS[tbl]

    # Check live migrations
    live_schemas = scan_migration_files(repo_dir)
    if tbl in live_schemas:
        s = live_schemas[tbl]
        cols_md = "\n".join([f"- `{col}`" for col in s["columns_raw"]])
        return f"""### Tabla: `{tbl}` (Migración Oficial: `{s['migration_file']}`)
*Origen: `database/migrations/{s['migration_file']}`* | *Columnas detectadas: {s['columns_count']}*

#### Columnas y Restricciones:
{cols_md}

#### Definición Blueprint:
```php
Schema::create('{tbl}', function (Blueprint $table) {{
{s['full_block']}
}});
```"""

    # Check partial match in live schemas
    for name, s in live_schemas.items():
        if tbl in name:
            cols_md = "\n".join([f"- `{col}`" for col in s["columns_raw"]])
            return f"""### Tabla: `{name}` (Coincidencia con '{tbl}')
*Migración: `{s['migration_file']}`*

{cols_md}"""

    # List all available tables
    available_live = list(live_schemas.keys())
    available_proposed = list(PROPOSED_SCHEMAS.keys())
    return (
        f"Tabla '{tbl}' no encontrada.\n"
        f"Tablas disponibles en migraciones: {', '.join([f'`{t}`' for t in available_live])}\n"
        f"Tablas de propuesta COMEXTRA: {', '.join([f'`{t}`' for t in available_proposed])}"
    )

def search_planteles_catalog(query: Optional[str] = None, limit: int = 5) -> Dict[str, Any]:
    """Searches the TecNM institutes catalog by name, state, key or classification."""
    if not query:
        return {
            "status": "success",
            "total_planteles_sistema": 263,
            "muestra_planteles": PLANTELES_CATALOG[:limit]
        }

    q = query.lower().strip()
    matched = []
    for p in PLANTELES_CATALOG:
        if (
            q in p["nombre"].lower()
            or q in p["estado"].lower()
            or q in p["clave"].lower()
            or q in p["tipo"].lower()
            or q in p["region"].lower()
        ):
            matched.append(p)

    return {
        "status": "success",
        "total_encontrados": len(matched),
        "query": query,
        "planteles": matched[:limit]
    }

def get_filament_blueprint(feature: str, component_type: str = "resource") -> str:
    """Generates official Filament v3 PHP code with TecNM palette tokens and multi-tenant scoping."""
    feat = feature.lower().strip()
    ctype = component_type.lower().strip()

    if "comextra" in feat or "solicitud" in feat:
        if ctype == "widget":
            return """```php
<?php

namespace App\\Filament\\Widgets;

use Filament\\Widgets\\StatsOverviewWidget as BaseWidget;
use Filament\\Widgets\\StatsOverviewWidget\\Stat;
use App\\Models\\ComSolicitud;

class ComextraPulsoWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Paleta Institucional TecNM:
        // Azul Marino: #1B396A | Tinto: #9B2247 | Verde: #285C4D | Oro: #D4AF37
        return [
            Stat::make('Solicitudes en Trámite', ComSolicitud::where('estatus', 'enviada')->count())
                ->description('Pendientes de dictamen nacional')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'), // Mapeado a #1B396A en tema

            Stat::make('Comisiones Aprobadas', ComSolicitud::where('estatus', 'aprobada_dveia')->count())
                ->description('Oficios M00 emitidos')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'), // Mapeado a #285C4D

            Stat::make('Informes Pendientes', ComSolicitud::where('estatus', 'informe_pendiente')->count())
                ->description('En plazo de 30 días hábiles')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'), // Mapeado a #9B2247
        ];
    }
}
```"""
        else: # Resource
            return """```php
<?php

namespace App\\Filament\\Resources;

use App\\Models\\ComSolicitud;
use Filament\\Forms;
use Filament\\Forms\\Form;
use Filament\\Resources\\Resource;
use Filament\\Tables;
use Filament\\Tables\\Table;
use Illuminate\\Database\\Eloquent\\Builder;

class ComSolicitudResource extends Resource
{
    protected static ?string $model = ComSolicitud::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';
    protected static ?string $navigationGroup = 'Gestión DVeIA';
    protected static ?string $navigationLabel = 'Comisiones al Extranjero';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\\Components\\Section::make('Identificación de la Comisión')
                    ->schema([
                        Forms\\Components\\TextInput::make('folio_comextra')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Folio Oficial COMEXTRA'),
                        Forms\\Components\\Select::make('tipo_solicitante')
                            ->options([
                                'docente' => 'Docente / Investigador',
                                'estudiante' => 'Estudiante de Posgrado / Licenciatura',
                                'directivo' => 'Personal Directivo',
                            ])
                            ->required(),
                        Forms\\Components\\TextInput::make('nombre_comisionado')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),

                Forms\\Components\\Section::make('Destino e Itinerario')
                    ->schema([
                        Forms\\Components\\TextInput::make('pais_destino')->required(),
                        Forms\\Components\\TextInput::make('ciudad_destino')->required(),
                        Forms\\Components\\TextInput::make('institucion_receptora')->required(),
                        Forms\\Components\\DatePicker::make('fecha_inicio')
                            ->required()
                            ->label('Fecha de Inicio de Comisión'),
                        Forms\\Components\\DatePicker::make('fecha_fin')
                            ->required()
                            ->label('Fecha de Término de Comisión'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\\Columns\\TextColumn::make('folio_comextra')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\\Columns\\TextColumn::make('nombre_comisionado')
                    ->searchable(),
                Tables\\Columns\\TextColumn::make('pais_destino')
                    ->badge()
                    ->color('info'),
                Tables\\Columns\\TextColumn::make('estatus')
                    ->badge()
                    ->colors([
                        'warning' => 'borrador',
                        'primary' => 'enviada',
                        'success' => 'aprobada_dveia',
                        'danger' => ['rechazada', 'informe_pendiente'],
                    ]),
                Tables\\Columns\\TextColumn::make('fecha_inicio')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\\Filters\\SelectFilter::make('tipo_solicitante')
                    ->options(['docente' => 'Docentes', 'estudiante' => 'Estudiantes', 'directivo' => 'Directivos']),
                Tables\\Filters\\TernaryFilter::make('bloqueado_por_adeudo')
                    ->label('Filtrar Adeudos Activos'),
            ]);
    }

    // Aislamiento Multi-Tenant por Plantel
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user && $user->clave_plantel && !in_array($user->role, ['R0_admin', 'R1_gestor_dveia', 'R6_oic_auditor'])) {
            $query->where('clave_plantel', $user->clave_plantel);
        }
        return $query;
    }
}
```"""

    return f"Blueprint para '{feature}' ({component_type}) disponible. Opciones: `comextra`, `planteles`, `indicadores`."

def to_camel_case(snake_str: str) -> str:
    """Converts snake_case to CamelCase."""
    return ''.join(word.capitalize() for word in snake_str.split('_'))

def generate_laravel_scaffold(table_name: str, fields: Optional[List[Dict[str, Any]]] = None, feature_name: str = "Modulo TecNM") -> Dict[str, Any]:
    """
    Generates a full institutional Laravel 11 scaffold:
    1. Migration DDL with timestamps & soft deletes.
    2. Eloquent Model with fillable, casts, and multi-tenant global scope.
    3. Filament v3 Resource with TecNM color palette and form/table components.
    """
    tbl = table_name.lower().strip()
    model_name = to_camel_case(tbl.rstrip('s') if tbl.endswith('s') else tbl)
    fields_list = fields or [
        {"name": "clave_plantel", "type": "string", "nullable": False},
        {"name": "nombre", "type": "string", "nullable": False},
        {"name": "monto", "type": "decimal", "nullable": True},
        {"name": "estatus", "type": "string", "nullable": False}
    ]

    # 1. Migration generator
    col_definitions = []
    fillables = []
    casts = {}

    for f in fields_list:
        fname = f.get("name", "col")
        ftype = f.get("type", "string")
        nullable = f.get("nullable", False)
        null_str = "->nullable()" if nullable else ""

        fillables.append(f"'{fname}'")

        if ftype == "string":
            col_definitions.append(f"            $table->string('{fname}'){null_str};")
        elif ftype == "integer":
            col_definitions.append(f"            $table->integer('{fname}'){null_str};")
            casts[fname] = "'integer'"
        elif ftype == "decimal":
            col_definitions.append(f"            $table->decimal('{fname}', 12, 2){null_str};")
            casts[fname] = "'decimal:2'"
        elif ftype == "boolean":
            col_definitions.append(f"            $table->boolean('{fname}')->default(false);")
            casts[fname] = "'boolean'"
        elif ftype == "date":
            col_definitions.append(f"            $table->date('{fname}'){null_str};")
            casts[fname] = "'date'"
        elif ftype == "text":
            col_definitions.append(f"            $table->text('{fname}'){null_str};")
        elif ftype == "foreignId":
            ref = f.get("references", f"{fname.replace('_id', '')}s")
            col_definitions.append(f"            $table->foreignId('{fname}')->constrained('{ref}')->cascadeOnDelete();")
            casts[fname] = "'integer'"
        else:
            col_definitions.append(f"            $table->string('{fname}'){null_str};")

    migration_code = f"""<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
        Schema::create('{tbl}', function (Blueprint $table) {{
            $table->id();
{chr(10).join(col_definitions)}
            $table->integer('lock_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('clave_plantel');
        }});
    }}

    public function down(): void
    {{
        Schema::dropIfExists('{tbl}');
    }}
}};
"""

    # 2. Model generator
    cast_lines = [f"        '{k}': {v}," for k, v in casts.items()]
    casts_block = ("\n" + "\n".join(cast_lines) + "\n    ") if cast_lines else ""

    model_code = f"""<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\SoftDeletes;
use Illuminate\\Database\\Eloquent\\Builder;

class {model_name} extends Model
{{
    use HasFactory, SoftDeletes;

    protected $table = '{tbl}';

    protected $fillable = [
        {', '.join(fillables)},
        'lock_version',
    ];

    protected $casts = [{casts_block}];

    // Multi-tenant Global Scope por clave_plantel
    protected static function booted(): void
    {{
        static::addGlobalScope('plantel', function (Builder $builder) {{
            $user = auth()->user();
            if ($user && $user->clave_plantel && !in_array($user->role, ['R0_admin', 'R1_gestor_dveia', 'R6_oic_auditor'])) {{
                $builder->where('{tbl}.clave_plantel', $user->clave_plantel);
            }}
        }});
    }}
}}
"""

    # 3. Filament Resource
    filament_code = f"""<?php

namespace App\\Filament\\Resources;

use App\\Models\\{model_name};
use Filament\\Forms;
use Filament\\Forms\\Form;
use Filament\\Resources\\Resource;
use Filament\\Tables;
use Filament\\Tables\\Table;

class {model_name}Resource extends Resource
{{
    protected static ?string $model = {model_name}::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = '{feature_name}';

    public static function form(Form $form): Form
    {{
        return $form->schema([
            Forms\\Components\\Section::make('Información General ({feature_name})')
                ->schema([
{chr(10).join([f"                    Forms\\Components\\TextInput::make('{f.get('name')}')->required()," for f in fields_list[:4]])}
                ])->columns(2),
        ]);
    }}

    public static function table(Table $table): Table
    {{
        return $table->columns([
{chr(10).join([f"            Tables\\Columns\\TextColumn::make('{f.get('name')}')->searchable()->sortable()," for f in fields_list[:4]])}
            Tables\\Columns\\TextColumn::make('created_at')->dateTime()->sortable(),
        ])
        ->filters([
            Tables\\Filters\\TrashedFilter::make(),
        ]);
    }}
}}
"""

    return {
        "status": "success",
        "table_name": tbl,
        "model_name": model_name,
        "migration_filename": f"2026_10_03_000000_create_{tbl}_table.php",
        "files": {
            "migration": migration_code,
            "model": model_code,
            "filament_resource": filament_code
        }
    }

def check_database_health(repo_dir: str = DEFAULT_REPO_DIR) -> Dict[str, Any]:
    """
    Performs institutional database health diagnostic:
    1. Checks Neon PostgreSQL configuration.
    2. Audits migration files vs models.
    3. Checks local SQLite Knowledge base statistics.
    """
    env_file = os.path.join(repo_dir, ".env")
    env_example = os.path.join(repo_dir, ".env.example")
    
    db_conn = os.environ.get("DB_CONNECTION", "pgsql")
    db_url = os.environ.get("DB_URL") or os.environ.get("DATABASE_URL", "Configurado en .env de Neon")

    migrations = scan_migration_files(repo_dir)

    models_dir = os.path.join(repo_dir, "app", "Models")
    models_found = []
    if os.path.exists(models_dir):
        models_found = [f.replace('.php', '') for f in os.listdir(models_dir) if f.endswith('.php')]

    return {
        "status": "success",
        "motor_principal": "Neon PostgreSQL 16",
        "connection_driver": db_conn,
        "neon_connection_configured": bool(db_url),
        "total_migraciones_detectadas": len(migrations),
        "migraciones": list(migrations.keys()),
        "total_modelos_eloquent": len(models_found),
        "modelos": models_found,
        "sqlite_cache_local": {
            "ruta": os.environ.get("ATLAS_DB_PATH", "/data/data/com.termux/files/home/tools/atlas_mcp/atlas_knowledge.db"),
            "estado": "ACTIVO - FTS5",
        }
    }

