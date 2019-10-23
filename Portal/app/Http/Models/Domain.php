<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    //
    protected $table = 'domains';

    public $timestamps = false;

    public function customer() {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }
}
