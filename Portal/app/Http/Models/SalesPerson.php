<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SalesPerson extends Model
{
    //
    protected $table = 'salesperson';

    public $timestamps = false;

}
