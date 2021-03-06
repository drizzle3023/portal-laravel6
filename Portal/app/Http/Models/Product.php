<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $table = 'products';

    public $timestamps = false;

    public function domain() {
        return $this->hasOne(Domain::class, 'id', 'domain_id');
    }

    public function customer() {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }
}
