<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheckSnapshot extends Model
{
    protected $table = 'health_check_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'year_month',
        'title',
        'payload',
        'created_by',
        'lampiran_date',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
