<?php

namespace Rifkiard\Gatekeeper;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GatekeeperAuthenticatableUser extends Authenticatable
{
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';


    protected $casts = [
        'setting' => 'object',
    ];

    public function save(array $options = [])
    {
        return false;
    }
}
