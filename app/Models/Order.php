<?php

namespace App\Models;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id','status', 'total_price'
    ];
    public function users(){
        return $this->belongsTo(User::class);
    }
    public function items(){
        return $this->hasMany(OrderItem::class);
    }
    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
