<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_review';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'produk_id',
        'user_id',
        'content',
        'rating',
        'order_id', // Menambahkan order_id
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id', 'produk_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id'); // Relasi dengan model Order
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id_review)) {
                $model->id_review = (string) Str::uuid();
            }
        });
    }
}
