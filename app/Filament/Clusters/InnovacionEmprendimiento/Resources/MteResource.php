<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource\Pages;
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

class MteResource extends Resource
{
    protected static ?string $model = RepRegistroBase::class;

    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = '2.2 Modelo Talento Emprendedor';

    protected static ?string $modelLabel = 'Reporte MTE';

    protected static ?string $pluralModelLabel = 'Reportes MTE (2.2)';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $submodulo = CatSubmodulo::where('clave', '2.2')->first();

        $query = parent::getEloquentQuery()
            ->where('submodulo_id', $submodulo?->id ?? 0);

        // Aislamiento Multi-Tenancy: Si el usuario tiene plantel asignado, solo ve su información
        $user = Auth::user();
        if ($user && ! $user->esNacional() && $user->plantel_id) {
            $query->where('plantel_id', $user->plantel_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $esNacional = $user ? $user->esNacional() : true;

        return $form
            ->schema([
                Forms\Components\Section::make('Control de Captura Institucional')
                    ->description('Identificación del periodo, plantel y estatus del reporte oficial')
                    ->schema([
                        Forms\Components\Select::make('periodo_id')
                            ->label('Periodo Trimestral')
                            ->options(CatPeriodo::all()->pluck('nombre_completo', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => CatPeriodo::where('bloqueado', false)->first()?->id),

                        Forms\Components\Select::make('plantel_id')
                            ->label('Instituto Tecnológico / Centro')
                            ->options(CatPlantel::where('activo', true)->pluck('nombre', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => $user?->plantel_id)
                            ->disabled(! $esNacional)
                            ->dehydrated(),

                        Forms\Components\Select::make('estado')
                            ->label('Estatus del Reporte')
                            ->options([
                                'BORRADOR' => 'Borrador (En Captura)',
                                'PUBLICADO' => 'Publicado (Oficial)',
                            ])
                            ->default('BORRADOR')
                            ->required()
                            ->native(false),
                    ])->columns(3),

                Forms\Components\Section::make('Métricas de Participación por Género')
                    ->description('Conteos agregados de docentes y estudiantes participantes en MTE')
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

                Forms\Components\Section::make('Detalles Específicos del Modelo de Talento Emprendedor')
                    ->description('Atributos particulares almacenados en el payload JSONB extensible')
                    ->schema([
                        Forms\Components\Select::make('detalles_adicionales.sector_estrategico')
                            ->label('Sector Estratégico Predominante')
                            ->options([
                                'Agroindustria y Alimentaria' => 'Agroindustria y Alimentaria',
                                'Tecnologías de la Información y Software' => 'Tecnologías de la Información y Software',
                                'Energía y Sostenibilidad' => 'Energía y Sostenibilidad',
                                'Salud y Biotecnología' => 'Salud y Biotecnología',
                                'Manufactura Avanzada y Aeroespacial' => 'Manufactura Avanzada y Aeroespacial',
                                'Servicios e Impacto Social' => 'Servicios e Impacto Social',
                            ])
                            ->searchable(),

                        Forms\Components\Select::make('detalles_adicionales.fase_predominante')
                            ->label('Fase de Emprendimiento')
                            ->options([
                                'Ideación y Sensibilización' => 'Ideación y Sensibilización',
                                'Prototipado y Validación' => 'Prototipado y Validación',
                                'Modelo de Negocio e Incubación' => 'Modelo de Negocio e Incubación',
                                'Empresa Legalmente Constituida' => 'Empresa Legalmente Constituida',
                            ]),

                        Forms\Components\TextInput::make('detalles_adicionales.proyectos_incubados')
                            ->label('Proyectos en Incubación')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(3),

                Forms\Components\Section::make('Soportes Institucionales y Observaciones')
                    ->schema([
                        Forms\Components\FileUpload::make('archivo_evidencia')
                            ->label('Documento Comprobatorio / Minuta / Evidencia (PDF o Excel)')
                            ->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->maxSize(10240) // 10MB
                            ->directory('evidencias/mte'),

                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones / Justificaciones')
                            ->placeholder('Aclaraciones de cifras o justificación en caso de incidencias...')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plantel.nombre')
                    ->label('Instituto Tecnológico')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('plantel.sostenimiento')
                    ->label('Sostenimiento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'FEDERAL' => 'primary',
                        'DESCENTRALIZADO' => 'info',
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
            ->filters([
                Tables\Filters\SelectFilter::make('periodo_id')
                    ->label('Periodo')
                    ->relationship('periodo', 'anio')
                    ->options(CatPeriodo::all()->pluck('nombre_completo', 'id')),

                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'BORRADOR' => 'Borrador',
                        'PUBLICADO' => 'Publicado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMtes::route('/'),
            'create' => Pages\CreateMte::route('/create'),
            'edit' => Pages\EditMte::route('/{record}/edit'),
        ];
    }
}
