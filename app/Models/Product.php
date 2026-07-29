<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'title' , 'description' , 'stock' , 'price' , 'image'
    ];
    public function subscribers(){
        return $this->belongsToMany(User::class , 'product_subscribers');
    }
}
