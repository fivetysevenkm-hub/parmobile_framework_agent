<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table = 'tbl_audit_trail';

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'username',
        'event',
        'module',
        'action',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'old_data',
        'new_data',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'old_data' => 'array',
            'new_data' => 'array',
            'metadata' => 'array',
        ];
    }
}
