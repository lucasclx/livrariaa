<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    use HasFactory;

    protected $table = 'pedido_items';

    protected $fillable = [
        'pedido_id', 
        'livro_id', 
        'quantidade', 
        'preco_unitario', 
        'subtotal'
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function livro()
    {
        return $this->belongsTo(Livro::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->quantidade * $this->preco_unitario;
    }
}