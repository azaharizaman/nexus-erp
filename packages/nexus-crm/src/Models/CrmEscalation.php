<?php

declare(strict_types=1);

namespace Nexus\Crm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CrmEscalation extends Model
{
    use HasUlids;

    protected $table = 'crm_escalations';

    protected $fillable = [
        'entity_id',
        'level',
        'from_user_id',
        'to_user_id',
        'reason',
        'escalated_at',
    ];

    protected $casts = [
        'escalated_at' => 'datetime'
    ];

    public function entity()
    {
        return $this->belongsTo(CrmEntity::class, 'entity_id');
    }
}
