<?php
// app/Models/Carrinho.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrinho extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id', 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itens()
    {
        return $this->hasMany(CarrinhoItem::class);
    }

    public function getTotalItensAttribute()
    {
        return $this->itens->sum('quantidade');
    }

    public function getTotalAttribute()
    {
        return $this->itens->sum(function ($item) {
            return $item->quantidade * $item->preco_unitario;
        });
    }
}