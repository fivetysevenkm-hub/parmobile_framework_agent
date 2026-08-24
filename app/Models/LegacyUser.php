<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class LegacyUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'tbl_user';

    protected $primaryKey = 'id_user';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'username',
        'pwd',
        'id_role',
        'is_active',
    ];

    protected $hidden = [
        'pwd',
        'remember_token',
    ];

    protected $casts = [
        'id_role' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $rememberTokenName = null;

    public function getAuthPasswordName(): string
    {
        return 'pwd';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->getAttribute('pwd');
    }
}
