<?php

declare(strict_types=1);

namespace Nexus\Crm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CrmSla extends Model
{
    use HasUlids;

    protected $table = 'crm_sla';

    protected $fillable = [
        'entity_id',
        'duration_minutes',
        'started_at',
        'breach_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'breach_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function entity()
    {
        return $this->belongsTo(CrmEntity::class, 'entity_id');
    }
}
