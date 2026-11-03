<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Catálogo Oficial de Roles Institucionales v2.1 (MOO-PR-039 & Circular 0045).
     */
    public const ROL_SUPERADMIN = 'R1_SUPERADMIN';

    public const ROL_AUTORIZADOR_SEV_DG = 'R1_AUTORIZADOR_SEV_DG';

    public const ROL_ANALISTA_NAL = 'R2_ANALISTA_NAL';

    public const ROL_REVISOR_DVEIA = 'R2_REVISOR_DVEIA';

    public const ROL_AUDITOR_OIC = 'R2_AUDITOR_OIC';

    public const ROL_SUPERVISOR_TERRITORIAL = 'R3_SUPERVISOR_TERRITORIAL';

    public const ROL_DIRECTOR_TITULAR = 'R4_DIRECTOR_TITULAR';

    public const ROL_SUBDIRECTOR_DELEGADO = 'R4_SUBDIRECTOR_DELEGADO';

    public const ROL_OPERADOR_MOD = 'R5_OPERADOR_MOD';

    public const ROL_SOPORTE_DEV = 'R6_SOPORTE_DEV';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plantel_id',
        'entidad_id',
        'zona_geografica',
        'rol_clave',
        'cargo',
        'telefono',
        'name',
        'email',
        'password',
        'activo',
        'revocado_at',
        'revocado_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'revocado_at' => 'datetime',
        ];
    }

    /**
     * Regla de acceso al panel Filament: requiere usuario activo y no revocado.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) ($this->activo ?? true) && is_null($this->revocado_at);
    }

    /**
     * Plantel asignado para Multi-Tenancy (null para directivos nacionales).
     */
    public function plantel(): BelongsTo
    {
        return $this->belongsTo(CatPlantel::class, 'plantel_id');
    }

    /**
     * Entidad federativa asignada (para supervisores estatales de OPDs).
     */
    public function entidad(): BelongsTo
    {
        return $this->belongsTo(CatEntidad::class, 'entidad_id');
    }

    /**
     * Submódulos que un operador (R5) tiene asignados para captura (N:M).
     */
    public function submodulosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(CatSubmodulo::class, 'user_submodulos', 'user_id', 'submodulo_id')
            ->withPivot('asignado_por')
            ->withTimestamps();
    }

    /**
     * Reportes registrados por este usuario.
     */
    public function reportes(): HasMany
    {
        return $this->hasMany(RepRegistroBase::class, 'user_id');
    }

    /**
     * Dictámenes o firmas emitidos por este usuario en la bitácora forense.
     */
    public function dictamenes(): HasMany
    {
        return $this->hasMany(RepDictamenLog::class, 'user_id');
    }

    /**
     * Determina si el usuario pertenece a Oficinas Centrales (alcance nacional).
     */
    public function esNacional(): bool
    {
        if (empty($this->rol_clave)) {
            return is_null($this->plantel_id);
        }

        return in_array($this->rol_clave, [
            self::ROL_SUPERADMIN,
            self::ROL_AUTORIZADOR_SEV_DG,
            self::ROL_ANALISTA_NAL,
            self::ROL_REVISOR_DVEIA,
            self::ROL_AUDITOR_OIC,
            self::ROL_SOPORTE_DEV,
        ], true);
    }

    /**
     * Determina si el usuario es Auditor del Órgano Interno de Control (solo lectura forense).
     */
    public function esAuditorOIC(): bool
    {
        return $this->rol_clave === self::ROL_AUDITOR_OIC;
    }

    /**
     * Determina si el usuario es Supervisor Territorial / Dirección Estatal de OPDs.
     */
    public function esSupervisorTerritorial(): bool
    {
        return $this->rol_clave === self::ROL_SUPERVISOR_TERRITORIAL;
    }

    /**
     * Determina si el usuario es Director Titular o Subdirector con Firma Delegada en Plantel.
     */
    public function esAdminPlantel(): bool
    {
        return in_array($this->rol_clave, [
            self::ROL_DIRECTOR_TITULAR,
            self::ROL_SUBDIRECTOR_DELEGADO,
        ], true);
    }

    /**
     * Determina si el usuario es Operador de Submódulos en Plantel (Jefe de Depto).
     */
    public function esOperador(): bool
    {
        return $this->rol_clave === self::ROL_OPERADOR_MOD;
    }

    /**
     * Determina si el usuario tiene perfil de solo lectura (OIC, Analista Nacional o Supervisor Estatal).
     */
    public function esSoloLectura(): bool
    {
        return in_array($this->rol_clave, [
            self::ROL_AUDITOR_OIC,
            self::ROL_ANALISTA_NAL,
            self::ROL_SUPERVISOR_TERRITORIAL,
        ], true);
    }

    /**
     * Verifica si el usuario tiene permiso para ver/capturar un submódulo específico (ej. '3.1' o '2.2').
     */
    public function puedeCapturarSubmodulo(string $claveSubmodulo): bool
    {
        if ($this->esNacional() || $this->esSupervisorTerritorial() || $this->esAdminPlantel()) {
            return true;
        }

        return $this->submodulosAsignados()->where('clave', $claveSubmodulo)->exists();
    }
}
