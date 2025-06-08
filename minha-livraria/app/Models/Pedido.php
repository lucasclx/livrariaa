<?php
// app/Models/Pedido.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_pedido', 'user_id', 'nome_cliente', 'email_cliente',
        'telefone_cliente', 'endereco_entrega', 'total', 'status',
        'forma_pagamento', 'data_entrega'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'data_entrega' => 'datetime'
    ];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($pedido) {
            $pedido->numero_pedido = 'PED-' . strtoupper(uniqid());
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itens()
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function getStatusFormattedAttribute()
    {
        $status = [
            'pendente' => ['classe' => 'warning', 'texto' => 'Pendente'],
            'processando' => ['classe' => 'info', 'texto' => 'Processando'],
            'enviado' => ['classe' => 'primary', 'texto' => 'Enviado'],
            'entregue' => ['classe' => 'success', 'texto' => 'Entregue'],
            'cancelado' => ['classe' => 'danger', 'texto' => 'Cancelado']
        ];

        return $status[$this->status] ?? ['classe' => 'secondary', 'texto' => 'Desconhecido'];
    }
}