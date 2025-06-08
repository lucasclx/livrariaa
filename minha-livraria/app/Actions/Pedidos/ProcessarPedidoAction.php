<?php

namespace App\Actions\Pedidos;

use App\Models\User;
use App\Models\Pedido;
use App\Models\Carrinho;
use Illuminate\Support\Facades\DB;

class ProcessarPedidoAction
{
    /**
     * Processa e cria um novo pedido a partir do carrinho do utilizador.
     * Envolve a lógica em uma transação para garantir a atomicidade.
     *
     * @param User $user O utilizador que está a fazer o pedido.
     * @param array $data Os dados validados do formulário de checkout.
     * @return Pedido O pedido que foi criado.
     * @throws \Exception Se um livro estiver fora de stock.
     */
    public function execute(User $user, array $data): Pedido
    {
        // Encontra o carrinho do utilizador, que deve existir para chegar aqui.
        $carrinho = Carrinho::where('user_id', $user->id)->with('items.livro')->firstOrFail();

        // DB::transaction garante que todas as operações sejam bem-sucedidas.
        // Se ocorrer qualquer erro, todas as alterações são desfeitas (rollback).
        return DB::transaction(function () use ($user, $carrinho, $data) {
            
            // 1. Verificar o stock de todos os livros antes de criar o pedido.
            foreach ($carrinho->items as $item) {
                if ($item->livro->estoque < $item->quantidade) {
                    // Lança uma exceção que será capturada pelo controlador.
                    throw new \Exception("Desculpe, o livro '{$item->livro->titulo}' não tem stock suficiente.");
                }
            }

            // 2. Criar o Pedido
            $pedido = Pedido::create([
                'user_id' => $user->id,
                'status' => 'processando', // Status inicial do pedido
                'total' => $carrinho->items->sum(function ($item) {
                    return $item->quantidade * $item->livro->preco;
                }),
                'endereco_entrega' => $data['endereco_entrega'],
                'cidade' => $data['cidade'],
                'estado' => $data['estado'],
                'cep' => $data['cep'],
                'metodo_pagamento' => $data['metodo_pagamento'],
            ]);

            // 3. Mover os itens do carrinho para a tabela de itens do pedido e abater o stock.
            foreach ($carrinho->items as $item) {
                $pedido->items()->create([
                    'livro_id' => $item->livro_id,
                    'quantidade' => $item->quantidade,
                    'preco_unitario' => $item->livro->preco, // Grava o preço no momento da compra
                ]);

                // Abater o stock
                $item->livro->decrement('estoque', $item->quantidade);
            }

            // 4. Limpar o carrinho de compras do utilizador.
            $carrinho->items()->delete();
            $carrinho->delete();

            // 5. Retornar o pedido criado para o controlador.
            return $pedido;
        });
    }
}