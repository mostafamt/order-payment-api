<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    protected $fillable = ['user_id', 'status', 'total', 'notes'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }


    public function recalculateTotal()
    {
        $total = $this->items()->get()->sum(function ($i) {
            return $i->quantity * $i->unit_price;
        });

        $this->total = $total;
        $this->save();
    }
}
