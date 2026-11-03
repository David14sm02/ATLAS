<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepDictamenLog extends Model
{
    public $timestamps = false;

    protected $table = 'rep_dictamenes_log';

    protected $fillable = [
        'registro_id',
        'user_id',
        'accion',
        'estatus_anterior',
        'estatus_nuevo',
        'motivo_rechazo_tipo',
        'observaciones',
        'ip_address',
        'user_agent',
        'payload_snapshot',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RepRegistroBase::class, 'registro_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

