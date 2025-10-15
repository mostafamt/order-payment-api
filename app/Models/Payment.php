<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $casts = ['meta' => 'array'];

    protected $fillable = ['payment_id', 'order_id', 'method', 'status', 'amount', 'meta'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
