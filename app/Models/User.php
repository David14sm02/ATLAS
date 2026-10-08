<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plantel_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    /**
     * Plantel asignado para Multi-Tenancy (null para directivos nacionales).
     */
    public function plantel(): BelongsTo
    {
        return $this->belongsTo(CatPlantel::class, 'plantel_id');
    }

    /**
     * Reportes registrados por este usuario.
     */
    public function reportes(): HasMany
    {
        return $this->hasMany(RepRegistroBase::class, 'user_id');
    }

    /**
     * Determina si el usuario es administrador nacional (sin restricción de plantel).
     */
    public function esNacional(): bool
    {
        return is_null($this->plantel_id);
    }
}
