<?php

namespace App\Filament\Clusters;

use App\Models\CatEntidad;
use App\Models\CatPeriodo;
use App\Models\CatPlantel;
use App\Models\CatSubmodulo;
use App\Models\RepRegistroBase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class BaseSubmoduloResource extends Resource
{
    protected static ?string $model = RepRegistroBase::class;

    /**
     * Clave oficial del submódulo en cat_submodulos (ej. '1.1', '2.1', '4.11').
     */
    protected static string $submoduloClave = '';

    /**
     * Nombre descriptivo corto del submódulo.
     */
    protected static string $submoduloNombre = '';

    public static function getSubmoduloClave(): string
    {
        return static::$submoduloClave;
    }

    public static function getNavigationLabel(): string
    {
        return static::$submoduloClave.' '.static::$submoduloNombre;
    }

    public static function getModelLabel(): string
    {
        return 'Reporte '.static::$submoduloClave;
    }

    public static function getPluralModelLabel(): string
    {
        return static::$submoduloClave.' — '.static::$submoduloNombre;
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->puedeCapturarSubmodulo(static::$submoduloClave);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user ? ! $user->esSoloLectura() : false;
    }

    public static function getEloquentQuery(): Builder
    {
        $submodulo = CatSubmodulo::where('clave', static::$submoduloClave)->first();

        // El aislamiento Multi-Tenancy (Plantel y Supervisión Estatal OPD) se aplica vía BelongsToPlantel (PlantelScope)
        return parent::getEloquentQuery()
            ->where('submodulo_id', $submodulo?->id ?? 0);
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $esNacional = $user ? $user->esNacional() : true;

        return $form
            ->schema([
                Forms\Components\Section::make('Control de Captura Institucional — Submódulo '.static::$submoduloClave)
                    ->description(static::$submoduloNombre)
                    ->schema([
                        Forms\Components\Select::make('periodo_id')
                            ->label('Periodo Trimestral')
                            ->options(fn () => CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->get()->pluck('nombre_completo', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => CatPeriodo::where('bloqueado', false)->first()?->id),

                        Forms\Components\Select::make('plantel_id')
                            ->label('Instituto Tecnológico / Centro')
                            ->options(fn () => $esNacional
                                ? CatPlantel::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')
                                : ($user?->plantel_id ? CatPlantel::where('id', $user->plantel_id)->pluck('nombre', 'id') : [])
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => $user?->plantel_id)
                            ->disabled(fn () => ! $esNacional)
                            ->dehydrated(),

                        Forms\Components\Select::make('estado')
                            ->label('Estatus de Entrega')
                            ->options([
                                'BORRADOR' => 'Borrador (En Captura Local)',
                                'PUBLICADO' => 'Publicado (Corte Oficial)',
                            ])
                            ->default('BORRADOR')
                            ->required()
                            ->native(false),
                    ])->columns(3),

                Forms\Components\Section::make('Métricas de Participación por Género')
                    ->description('Conteos agregados de docentes y estudiantes participantes en el submódulo '.static::$submoduloClave)
                    ->schema([
                        Forms\Components\Fieldset::make('Personal Docente Participante')
                            ->schema([
                                Forms\Components\TextInput::make('docentes_mujeres')
                                    ->label('Docentes Mujeres')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('docentes_hombres')
                                    ->label('Docentes Hombres')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true),

                                Forms\Components\Placeholder::make('subtotal_docentes')
                                    ->label('Subtotal Docentes')
                                    ->content(fn (Get $get) => (int) $get('docentes_mujeres') + (int) $get('docentes_hombres')),
                            ])->columns(3),

                        Forms\Components\Fieldset::make('Comunidad Estudiantil Participante')
                            ->schema([
                                Forms\Components\TextInput::make('estudiantes_mujeres')
                                    ->label('Estudiantes Mujeres')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('estudiantes_hombres')
                                    ->label('Estudiantes Hombres')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true),

                                Forms\Components\Placeholder::make('subtotal_estudiantes')
                                    ->label('Subtotal Estudiantes')
                                    ->content(fn (Get $get) => (int) $get('estudiantes_mujeres') + (int) $get('estudiantes_hombres')),
                            ])->columns(3),

                        Forms\Components\Placeholder::make('calculo_total_general')
                            ->label('TOTAL GENERAL DE PARTICIPANTES (Cálculo en Vivo)')
                            ->content(fn (Get $get) => (
                                (int) $get('docentes_mujeres') +
                                (int) $get('docentes_hombres') +
                                (int) $get('estudiantes_mujeres') +
                                (int) $get('estudiantes_hombres')
                            ).' personas registradas'),
                    ]),

                Forms\Components\Section::make('Indicadores Específicos del Submódulo (Payload JSONB)')
                    ->description('Campos adicionales configurables por el equipo para '.static::$submoduloNombre)
                    ->schema([
                        Forms\Components\TextInput::make('detalles_adicionales.acciones_realizadas')
                            ->label('Acciones / Eventos / Convenios Realizados')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('detalles_adicionales.meta_trimestral')
                            ->label('Meta Trimestral Programada')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('detalles_adicionales.responsable_area')
                            ->label('Responsable del Submódulo en Plantel')
                            ->maxLength(150),
                    ])->columns(3),

                Forms\Components\Section::make('Soportes Institucionales y Observaciones')
                    ->schema([
                        Forms\Components\FileUpload::make('archivo_evidencia')
                            ->label('Documento Comprobatorio / Evidencia (PDF o Excel)')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(10240)
                            ->directory('evidencias/submodulo-'.str_replace('.', '_', static::$submoduloClave)),

                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones / Justificaciones')
                            ->placeholder('Aclaraciones de cifras o seguimiento trimestral...')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plantel.siglas')
                    ->label('Siglas')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plantel.nombre')
                    ->label('Instituto Tecnológico / Centro')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('plantel.entidad.nombre')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('plantel.tipo_institucion')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'IT_FEDERAL' => 'Federal',
                        'IT_DESCENTRALIZADO' => 'Descentralizado OPD',
                        'CENTRO_ESPECIALIZADO' => 'Centro Esp.',
                        default => $state ?? 'N/D',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'IT_FEDERAL' => 'primary',
                        'IT_DESCENTRALIZADO' => 'info',
                        'CENTRO_ESPECIALIZADO' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('periodo.nombre_completo')
                    ->label('Periodo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_docentes')
                    ->label('Docentes')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_estudiantes')
                    ->label('Estudiantes')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_general')
                    ->label('Total General')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BORRADOR' => 'warning',
                        'PUBLICADO' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(static::getCommonInstitutionalFilters())
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data, RepRegistroBase $record): array {
                        if (($data['estado'] ?? '') === 'PUBLICADO' && is_null($record->published_at)) {
                            $data['published_at'] = now();
                            $data['published_by'] = Auth::id();
                        }

                        return $data;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Filtros institucionales compartidos por los 35 submódulos.
     */
    public static function getCommonInstitutionalFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('periodo_id')
                ->label('Periodo Trimestral')
                ->options(fn () => CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->get()->pluck('nombre_completo', 'id')),

            Tables\Filters\SelectFilter::make('entidad_id')
                ->label('Entidad Federativa')
                ->options(fn () => CatEntidad::orderBy('clave_inegi')->pluck('nombre', 'id'))
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['value'])) {
                        $query->whereHas('plantel', fn (Builder $q) => $q->where('entidad_id', $data['value']));
                    }

                    return $query;
                }),

            Tables\Filters\SelectFilter::make('tipo_institucion')
                ->label('Tipo de Institución')
                ->options([
                    'IT_FEDERAL' => 'Federales (Centralizados)',
                    'IT_DESCENTRALIZADO' => 'Descentralizados (OPD)',
                    'CENTRO_ESPECIALIZADO' => 'Centros Especializados (CRODE/CIIDET/CENIDET)',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['value'])) {
                        $query->whereHas('plantel', fn (Builder $q) => $q->where('tipo_institucion', $data['value']));
                    }

                    return $query;
                }),

            Tables\Filters\SelectFilter::make('plantel_id')
                ->label('Instituto Tecnológico / Centro')
                ->relationship('plantel', 'nombre')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('estado')
                ->label('Estatus de Entrega')
                ->options([
                    'BORRADOR' => 'Borrador',
                    'PUBLICADO' => 'Publicado',
                ]),
        ];
    }

    /**
     * Resuelve dinámicamente la URL de cualquiera de los 35 submódulos a partir de su clave ('1.1' a '4.12').
     */
    public static function getUrlForClave(string $clave): ?string
    {
        $mapa = static::getMapaRecursosPorClave();
        $resourceClass = $mapa[$clave] ?? null;

        if ($resourceClass && class_exists($resourceClass)) {
            return $resourceClass::getUrl('index');
        }

        return null;
    }

    /**
     * Mapa maestro de los 35 Submódulos a sus clases Resource en Filament.
     *
     * @return array<string, class-string<Resource>>
     */
    public static function getMapaRecursosPorClave(): array
    {
        return [
            // EJE 1: VINCULACIÓN ESTRATÉGICA
            '1.1' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo11Resource::class,
            '1.2' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo12Resource::class,
            '1.3' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo13Resource::class,
            '1.4' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo14Resource::class,
            '1.5' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo15Resource::class,
            '1.6' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo16Resource::class,
            '1.7' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo17Resource::class,
            '1.8' => \App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo18Resource::class,

            // EJE 2: INNOVACIÓN Y EMPRENDIMIENTO
            '2.1' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo21Resource::class,
            '2.2' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource::class,
            '2.3' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo23Resource::class,
            '2.4' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo24Resource::class,
            '2.5' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo25Resource::class,
            '2.6' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo26Resource::class,
            '2.7' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo27Resource::class,
            '2.8' => \App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo28Resource::class,

            // EJE 3: INTERCAMBIO ACADÉMICO
            '3.1' => \App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource::class,
            '3.2' => \App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo32Resource::class,
            '3.3' => \App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo33Resource::class,
            '3.4' => \App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo34Resource::class,
            '3.5' => \App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo35Resource::class,
            '3.6' => \App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo36Resource::class,
            '3.7' => \App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo37Resource::class,

            // EJE 4: EXTENSIÓN
            '4.1' => \App\Filament\Clusters\Extension\Resources\Submodulo41Resource::class,
            '4.2' => \App\Filament\Clusters\Extension\Resources\Submodulo42Resource::class,
            '4.3' => \App\Filament\Clusters\Extension\Resources\Submodulo43Resource::class,
            '4.4' => \App\Filament\Clusters\Extension\Resources\Submodulo44Resource::class,
            '4.5' => \App\Filament\Clusters\Extension\Resources\Submodulo45Resource::class,
            '4.6' => \App\Filament\Clusters\Extension\Resources\Submodulo46Resource::class,
            '4.7' => \App\Filament\Clusters\Extension\Resources\Submodulo47Resource::class,
            '4.8' => \App\Filament\Clusters\Extension\Resources\Submodulo48Resource::class,
            '4.9' => \App\Filament\Clusters\Extension\Resources\Submodulo49Resource::class,
            '4.10' => \App\Filament\Clusters\Extension\Resources\Submodulo410Resource::class,
            '4.11' => \App\Filament\Clusters\Extension\Resources\Submodulo411Resource::class,
            '4.12' => \App\Filament\Clusters\Extension\Resources\Submodulo412Resource::class,
        ];
    }
}

