<?php

namespace Tests\Feature;

use App\Models\Livro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_order_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $livro = Livro::factory()->create();

        $this->post(route('carrinho.adicionar'), [
            'livro_id' => $livro->id,
            'quantidade' => 1,
        ]);

        $response = $this->post(route('checkout.processar'), [
            'nome_cliente' => 'John Doe',
            'email_cliente' => 'john@example.com',
            'telefone_cliente' => '123456789',
            'endereco_entrega' => 'Rua Teste, 123',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '12345-678',
            'forma_pagamento' => 'pix',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pedidos', [
            'email_cliente' => 'john@example.com',
            'total' => $livro->preco,
        ]);
        $this->assertDatabaseCount('carrinhos', 0);
    }
}
