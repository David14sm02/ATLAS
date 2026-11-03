<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BitacoraAuditoriaResource\Pages;
use App\Models\CatPlantel;
use App\Models\RepDictamenLog;
use App\Models\User;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BitacoraAuditoriaResource extends Resource
{
    protected static ?string $model = RepDictamenLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Bitácora de Auditoría';

    protected static ?string $modelLabel = 'Registro de Auditoría';

    protected static ?string $pluralModelLabel = 'Bitácora Inmutable de Auditoría y Movimientos';

    protected static ?int $navigationSort = 99;

    /**
     * ¿Quién puede ver la pestaña de Bitácora?
     * - Nivel Nacional (R1, R2, OIC): Ven todo el país.
     * - Supervisores Territoriales (R3): Ven los OPDs de su estado.
     * - Directores / Subdirectores de Plantel (R4): Ven únicamente los movimientos de su plantel.
     * - Operadores / Capturistas (R5): NO ven la bitácora.
     */
    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->esNacional() || $user->esSupervisorTerritorial() || $user->esAdminPlantel();
    }

    /**
     * Caja Negra Estricta: NADIE puede crear registros manualmente desde la interfaz.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Caja Negra Estricta: NADIE puede editar ni alterar un registro de auditoría.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Caja Negra Estricta: NADIE puede eliminar registros de la bitácora.
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
        $query = parent::getEloquentQuery()->with(['plantel']);

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->visibleParaUsuario($user);
    }

    public static function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();
        $esVistaMultiPlantel = $user && ($user->esNacional() || $user->esSupervisorTerritorial());

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario_nombre')
                    ->label('Usuario Responsable')
                    ->description(fn (RepDictamenLog $record): string => ($record->usuario_cargo ?? 'Sin cargo').' ('.($record->usuario_rol_clave ?? 'N/A').')')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('plantel.nombre')
                    ->label('Instituto / Centro')
                    ->placeholder('Oficinas Centrales (Nacional)')
                    ->searchable()
                    ->sortable()
                    ->visible($esVistaMultiPlantel),

                Tables\Columns\TextColumn::make('modulo')
                    ->label('Módulo')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('accion')
                    ->label('Acción Realizada')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CREACION' => 'info',
                        'PUBLICACION', 'DICTAMEN_FAVORABLE' => 'success',
                        'FIRMA_DELEGADA' => 'warning',
                        'ELIMINACION', 'RECHAZO' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('estatus_nuevo')
                    ->label('Transición de Estatus')
                    ->formatStateUsing(fn (RepDictamenLog $record): string => $record->estatus_anterior
                        ? "{$record->estatus_anterior} → {$record->estatus_nuevo}"
                        : (string) $record->estatus_nuevo)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('observaciones')
                    ->label('Detalle del Movimiento')
                    ->limit(55)
                    ->tooltip(fn (RepDictamenLog $record): ?string => $record->observaciones)
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Dirección IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('accion')
                    ->label('Tipo de Acción')
                    ->options([
                        'CREACION' => 'Creación de Registro',
                        'ACTUALIZACION' => 'Actualización / Edición',
                        'PUBLICACION' => 'Publicación Oficial (Director Titular)',
                        'FIRMA_DELEGADA' => 'Firma por Ausencia (Subdirector Delegado)',
                        'CARGA_EXCEL' => 'Carga de Plantilla Excel',
                        'ELIMINACION' => 'Eliminación de Registro',
                        'RECHAZO' => 'Rechazo / Observación',
                    ]),

                Tables\Filters\SelectFilter::make('usuario_rol_clave')
                    ->label('Rol del Usuario')
                    ->options([
                        User::ROL_SUPERADMIN => 'R1 - SuperAdmin TICs',
                        User::ROL_AUTORIZADOR_SEV_DG => 'R1 - Dirección General / SEV',
                        User::ROL_REVISOR_DVEIA => 'R2 - Revisor DVeIA',
                        User::ROL_AUDITOR_OIC => 'R2 - Auditor OIC',
                        User::ROL_DIRECTOR_TITULAR => 'R4 - Director Titular de Plantel',
                        User::ROL_SUBDIRECTOR_DELEGADO => 'R4 - Subdirector (Firma por Ausencia)',
                        User::ROL_OPERADOR_MOD => 'R5 - Jefe de Depto / Operador',
                    ]),

                Tables\Filters\SelectFilter::make('plantel_id')
                    ->label('Filtrar por Plantel')
                    ->options(fn () => CatPlantel::orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible($esVistaMultiPlantel),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalle')
                    ->modalHeading('Inspección Forense del Movimiento (Solo Lectura)'),
            ])
            ->bulkActions([]); // Cero acciones masivas ni borrado permitido
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Identidad y Contexto de la Acción')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha y Hora Exacta')
                            ->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\TextEntry::make('usuario_nombre')
                            ->label('Nombre del Usuario'),
                        Infolists\Components\TextEntry::make('usuario_cargo')
                            ->label('Cargo Institucional'),
                        Infolists\Components\TextEntry::make('usuario_rol_clave')
                            ->label('Clave de Rol')
                            ->badge(),
                        Infolists\Components\TextEntry::make('plantel.nombre')
                            ->label('Instituto Tecnológico / Centro')
                            ->placeholder('Oficinas Centrales (Nacional)'),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('Dirección IP de Origen'),
                    ])->columns(3),

                Infolists\Components\Section::make('Trazabilidad del Cambio (Antes vs. Después)')
                    ->schema([
                        Infolists\Components\TextEntry::make('modulo')
                            ->label('Módulo Afectado')
                            ->badge(),
                        Infolists\Components\TextEntry::make('accion')
                            ->label('Tipo de Movimiento')
                            ->badge(),
                        Infolists\Components\TextEntry::make('observaciones')
                            ->label('Resumen / Observación')
                            ->columnSpanFull(),
                        Infolists\Components\KeyValueEntry::make('valores_anteriores')
                            ->label('Valores Anteriores (Antes del cambio)')
                            ->placeholder('Sin valores previos (Registro nuevo)'),
                        Infolists\Components\KeyValueEntry::make('valores_nuevos')
                            ->label('Valores Nuevos (Después del cambio)')
                            ->placeholder('Sin valores nuevos (Registro eliminado)'),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBitacoraAuditorias::route('/'),
        ];
    }
}

