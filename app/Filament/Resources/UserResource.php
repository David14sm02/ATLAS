<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\CatEntidad;
use App\Models\CatPlantel;
use App\Models\CatSubmodulo;
use App\Models\RepDictamenLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios y Permisos';

    protected static ?string $modelLabel = 'Usuario Institucional';

    protected static ?string $pluralModelLabel = 'Gestión de Usuarios y Asignación de Submódulos';

    protected static ?int $navigationSort = 98;

    /**
     * Etiquetas legibles del Catálogo Oficial de Roles v2.1.
     *
     * @return array<string, string>
     */
    public static function getCatalogoRolesEtiquetas(): array
    {
        return [
            User::ROL_SUPERADMIN => 'R1 — Superadministrador General (DTI / Planeación)',
            User::ROL_AUTORIZADOR_SEV_DG => 'R1 — Autorizador Ejecutivo (DG / Secretaría SEV)',
            User::ROL_ANALISTA_NAL => 'R2 — Analista Nacional (Consulta Consolidada)',
            User::ROL_REVISOR_DVEIA => 'R2 — Revisor / Dictaminador Nacional (DVeIA)',
            User::ROL_AUDITOR_OIC => 'R2 — Auditor del Órgano Interno de Control (OIC)',
            User::ROL_SUPERVISOR_TERRITORIAL => 'R3 — Supervisor Estatal OPD / Coordinador Regional',
            User::ROL_DIRECTOR_TITULAR => 'R4 — Director(a) Titular de Plantel o Centro',
            User::ROL_SUBDIRECTOR_DELEGADO => 'R4 — Subdirector / Jefe de Vinculación (Firma por Ausencia)',
            User::ROL_OPERADOR_MOD => 'R5 — Jefe de Departamento / Operador de Submódulos',
            User::ROL_SOPORTE_DEV => 'R6 — Soporte Técnico / Mantenimiento',
        ];
    }

    /**
     * ¿Quién puede ver el módulo de Usuarios y Permisos?
     * - R1_SUPERADMIN: Control total nacional.
     * - R2_AUDITOR_OIC: Solo lectura nacional para auditoría de accesos.
     * - R4 (Director Titular / Subdirector Delegado): Administra el equipo de su propio plantel.
     */
    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->rol_clave === User::ROL_SUPERADMIN
            || $user->esAuditorOIC()
            || $user->esAdminPlantel();
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || $user->esSoloLectura()) {
            return false;
        }

        return $user->rol_clave === User::ROL_SUPERADMIN || $user->esAdminPlantel();
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || $user->esSoloLectura() || ! ($record instanceof User)) {
            return false;
        }

        if ($user->rol_clave === User::ROL_SUPERADMIN) {
            return true;
        }

        // Un Director/Subdirector de Plantel (R4) solo puede editar usuarios de su mismo plantel y que no sean Nacionales
        return $user->esAdminPlantel()
            && $user->plantel_id !== null
            && $record->plantel_id === $user->plantel_id
            && ! $record->esNacional();
    }

    /**
     * Por integridad forense (Caja Negra), las cuentas institucionales no se borran físicamente;
     * se utiliza el botón de "Revocar Acceso" (revocado_at / activo = false).
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with(['plantel', 'entidad', 'submodulosAsignados']);

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->rol_clave === User::ROL_SUPERADMIN || $user->esAuditorOIC()) {
            return $query;
        }

        if ($user->esAdminPlantel() && $user->plantel_id) {
            return $query->where('plantel_id', $user->plantel_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        $esSuperadmin = $authUser && $authUser->rol_clave === User::ROL_SUPERADMIN;

        $opcionesRoles = $esSuperadmin
            ? static::getCatalogoRolesEtiquetas()
            : [
                User::ROL_OPERADOR_MOD => 'R5 — Jefe de Departamento / Operador de Submódulos',
                User::ROL_SUBDIRECTOR_DELEGADO => 'R4 — Subdirector / Jefe de Vinculación (Firma por Ausencia)',
                User::ROL_DIRECTOR_TITULAR => 'R4 — Director(a) Titular de Plantel',
            ];

        $opcionesSubmodulos = CatSubmodulo::orderBy('eje_id')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (CatSubmodulo $s) => [
                $s->id => "{$s->clave} — {$s->nombre}".($s->activo_mvp ? ' ★' : ''),
            ])
            ->all();

        return $form
            ->schema([
                Forms\Components\Section::make('Identidad del Funcionario y Credenciales')
                    ->description('Datos oficiales del servidor público y correo institucional')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo (con Grado Académico)')
                            ->placeholder('Ej. Mtro. Juan Carlos Pérez López')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Institucional (@tecnm.mx)')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('cargo')
                            ->label('Cargo Oficial en Organigrama')
                            ->placeholder('Ej. Jefe del Depto. de Gestión Tecnológica y Vinculación')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono / Extensión')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña de Acceso')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Déjelo en blanco si no desea cambiar la contraseña actual.'
                                : 'Mínimo 8 caracteres.'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Cuenta Activa para Iniciar Sesión')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('Adscripción y Nivel de Autoridad (Roles v2.1)')
                    ->description('Define el alcance territorial y los privilegios dentro de ATLAS TecNM')
                    ->schema([
                        Forms\Components\Select::make('rol_clave')
                            ->label('Rol Institucional (R1 a R6)')
                            ->options($opcionesRoles)
                            ->default(User::ROL_OPERADOR_MOD)
                            ->required()
                            ->live()
                            ->native(false),

                        Forms\Components\Select::make('plantel_id')
                            ->label('Instituto Tecnológico / Centro Adscrito')
                            ->options(fn () => $esSuperadmin
                                ? CatPlantel::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')
                                : ($authUser?->plantel_id ? CatPlantel::where('id', $authUser->plantel_id)->pluck('nombre', 'id') : [])
                            )
                            ->default(fn () => $authUser?->plantel_id)
                            ->disabled(fn () => ! $esSuperadmin)
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->placeholder('Oficinas Centrales TecNM (Alcance Nacional)'),

                        Forms\Components\Select::make('entidad_id')
                            ->label('Entidad Federativa (Solo para Supervisores Estatales R3)')
                            ->options(fn () => CatEntidad::orderBy('clave_inegi')->pluck('nombre', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $esSuperadmin && $get('rol_clave') === User::ROL_SUPERVISOR_TERRITORIAL),

                        Forms\Components\TextInput::make('zona_geografica')
                            ->label('Zona / Región Asignada')
                            ->placeholder('Ej. Región 4 / Occidente')
                            ->maxLength(50)
                            ->visible(fn (Get $get): bool => $esSuperadmin && $get('rol_clave') === User::ROL_SUPERVISOR_TERRITORIAL),
                    ])->columns(2),

                Forms\Components\Section::make('Matriz de Submódulos Asignados para Captura (1.1 a 4.12)')
                    ->description('Seleccione qué submódulos de los 4 Ejes podrá ver y capturar este operador en su plantel. (Los Directores R4 y roles Nacionales R1/R2 tienen visibilidad automática de los 35 submódulos).')
                    ->schema([
                        Forms\Components\CheckboxList::make('submodulosAsignados')
                            ->label('Submódulos Autorizados')
                            ->relationship('submodulosAsignados', 'nombre')
                            ->options($opcionesSubmodulos)
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row'),
                    ])
                    ->visible(fn (Get $get): bool => $get('rol_clave') === User::ROL_OPERADOR_MOD),
            ]);
    }

    public static function table(Table $table): Table
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        $esSuperadmin = $authUser && $authUser->rol_clave === User::ROL_SUPERADMIN;

        return $table
            ->defaultSort('id', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Funcionario')
                    ->description(fn (User $record): string => $record->cargo ?? 'Sin cargo registrado')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Institucional')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size('xs'),

                Tables\Columns\TextColumn::make('rol_clave')
                    ->label('Rol v2.1')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        User::ROL_SUPERADMIN, User::ROL_AUTORIZADOR_SEV_DG => 'primary',
                        User::ROL_REVISOR_DVEIA, User::ROL_ANALISTA_NAL => 'info',
                        User::ROL_AUDITOR_OIC => 'danger',
                        User::ROL_SUPERVISOR_TERRITORIAL => 'warning',
                        User::ROL_DIRECTOR_TITULAR, User::ROL_SUBDIRECTOR_DELEGADO => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('plantel.siglas')
                    ->label('Adscripción')
                    ->placeholder(fn (User $record): string => $record->entidad ? 'Estado: '.$record->entidad->nombre : 'TecNM Central (Nacional)')
                    ->description(fn (User $record): ?string => $record->plantel?->nombre)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('submodulos_resumen')
                    ->label('Submódulos Habilitados')
                    ->state(function (User $record): string {
                        if ($record->esNacional() || $record->esSupervisorTerritorial() || $record->esAdminPlantel()) {
                            return 'Todos (35 Submódulos)';
                        }

                        $claves = $record->submodulosAsignados->pluck('clave')->all();

                        return empty($claves)
                            ? 'Sin asignar (0)'
                            : count($claves).' asignados: '.implode(', ', array_slice($claves, 0, 5)).(count($claves) > 5 ? '...' : '');
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_starts_with($state, 'Sin asignar') ? 'danger' : 'info'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rol_clave')
                    ->label('Rol Institucional')
                    ->options(static::getCatalogoRolesEtiquetas()),

                Tables\Filters\SelectFilter::make('plantel_id')
                    ->label('Instituto Tecnológico / Centro')
                    ->relationship('plantel', 'nombre')
                    ->searchable()
                    ->preload()
                    ->visible($esSuperadmin || ($authUser && $authUser->esAuditorOIC())),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estatus de Cuenta')
                    ->trueLabel('Solo Cuentas Activas')
                    ->falseLabel('Cuentas Revocadas / Inactivas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->visible(fn (User $record): bool => static::canEdit($record))
                    ->after(function (User $record): void {
                        /** @var User|null $actor */
                        $actor = Auth::user();
                        if (! $actor) {
                            return;
                        }

                        $submodulos = $record->submodulosAsignados()->pluck('clave')->all();

                        RepDictamenLog::create([
                            'plantel_id' => $record->plantel_id ?? $actor->plantel_id,
                            'registro_id' => null,
                            'modulo' => 'USUARIOS',
                            'entidad_tipo' => 'users',
                            'entidad_id_ref' => $record->id,
                            'user_id' => $actor->id,
                            'usuario_nombre' => $actor->name,
                            'usuario_rol_clave' => $actor->rol_clave,
                            'usuario_cargo' => $actor->cargo,
                            'accion' => RepDictamenLog::ACCION_ACTUALIZACION,
                            'estatus_anterior' => 'ACTIVO',
                            'estatus_nuevo' => $record->activo ? 'ACTIVO' : 'REVOCADO',
                            'observaciones' => "Actualización de perfil/permisos del usuario {$record->email} ({$record->rol_clave}). Submódulos: ".(empty($submodulos) ? 'Alcance por rol' : implode(', ', $submodulos)),
                            'valores_nuevos' => [
                                'email' => $record->email,
                                'rol_clave' => $record->rol_clave,
                                'plantel_id' => $record->plantel_id,
                                'activo' => $record->activo,
                                'submodulos_asignados' => $submodulos,
                            ],
                            'ip_address' => request()?->ip() ?? '127.0.0.1',
                            'user_agent' => substr((string) request()?->userAgent(), 0, 250),
                            'created_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('toggleAcceso')
                    ->label(fn (User $record): string => $record->activo ? 'Revocar' : 'Reactivar')
                    ->icon(fn (User $record): string => $record->activo ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->activo ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->activo
                        ? 'Revocar Acceso Institucional a '.$record->name
                        : 'Reactivar Cuenta Institucional de '.$record->name)
                    ->modalDescription(fn (User $record): string => $record->activo
                        ? 'El usuario ya no podrá iniciar sesión en ATLAS TecNM, pero todos sus registros y firmas históricas permanecerán intactos en la Bitácora Forense.'
                        : 'El usuario recuperará el acceso al sistema con su rol y submódulos asignados.')
                    ->visible(fn (User $record): bool => static::canEdit($record) && $record->id !== Auth::id())
                    ->action(function (User $record): void {
                        /** @var User $actor */
                        $actor = Auth::user();
                        $nuevoEstado = ! $record->activo;

                        $record->update([
                            'activo' => $nuevoEstado,
                            'revocado_at' => $nuevoEstado ? null : now(),
                            'revocado_by' => $nuevoEstado ? null : $actor->id,
                        ]);

                        RepDictamenLog::create([
                            'plantel_id' => $record->plantel_id ?? $actor->plantel_id,
                            'registro_id' => null,
                            'modulo' => 'USUARIOS',
                            'entidad_tipo' => 'users',
                            'entidad_id_ref' => $record->id,
                            'user_id' => $actor->id,
                            'usuario_nombre' => $actor->name,
                            'usuario_rol_clave' => $actor->rol_clave,
                            'usuario_cargo' => $actor->cargo,
                            'accion' => $nuevoEstado ? RepDictamenLog::ACCION_ACTUALIZACION : RepDictamenLog::ACCION_ELIMINACION,
                            'estatus_anterior' => $nuevoEstado ? 'REVOCADO' : 'ACTIVO',
                            'estatus_nuevo' => $nuevoEstado ? 'ACTIVO' : 'REVOCADO',
                            'observaciones' => $nuevoEstado
                                ? "Reactivación de cuenta institucional autorizada para {$record->name} ({$record->email})."
                                : "Revocación de acceso institucional aplicada a {$record->name} ({$record->email}).",
                            'valores_nuevos' => [
                                'usuario_afectado' => $record->email,
                                'rol_clave' => $record->rol_clave,
                                'activo' => $nuevoEstado,
                            ],
                            'ip_address' => request()?->ip() ?? '127.0.0.1',
                            'user_agent' => substr((string) request()?->userAgent(), 0, 250),
                            'created_at' => now(),
                        ]);

                        Notification::make()
                            ->title($nuevoEstado ? 'Cuenta institucional reactivada' : 'Acceso institucional revocado')
                            ->body("El movimiento quedó registrado en la Bitácora Forense de Auditoría.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}

