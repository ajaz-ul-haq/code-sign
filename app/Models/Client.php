<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Client extends Authenticatable
{

    use HasApiTokens;

    protected $guarded = [];
}
