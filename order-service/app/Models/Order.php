<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Tambahkan ini biar datanya bisa masuk lewat mass-assignment
    protected $fillable = [
        'user_id',
        'product_id',
        'product_name',
        'quantity',
        'total_price'
        ];
}
