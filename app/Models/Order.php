<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['placed_at' => 'date', 'final_sale' => 'boolean', 'total' => 'float'];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
