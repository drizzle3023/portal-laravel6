<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Model
{
    //
    protected $table = 'customer';

    public $timestamps = false;

    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'customer_id', 'id');
    }

}
