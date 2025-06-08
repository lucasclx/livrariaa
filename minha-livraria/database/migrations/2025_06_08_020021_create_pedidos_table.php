<?php
// database/migrations/create_pedidos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('nome_cliente');
            $table->string('email_cliente');
            $table->string('telefone_cliente');
            $table->text('endereco_entrega');
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pendente', 'processando', 'enviado', 'entregue', 'cancelado'])->default('pendente');
            $table->enum('forma_pagamento', ['cartao_credito', 'cartao_debito', 'pix', 'boleto']);
            $table->timestamp('data_entrega')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pedidos');
    }
};