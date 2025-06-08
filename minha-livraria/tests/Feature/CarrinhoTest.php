<?php

namespace Tests\Feature;

use App\Models\CarrinhoItem;
use App\Models\Livro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrinhoTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_book_to_cart_creates_item(): void
    {
        $livro = Livro::factory()->create();

        $response = $this->post(route('carrinho.adicionar'), [
            'livro_id' => $livro->id,
            'quantidade' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('carrinho_itens', [
            'livro_id' => $livro->id,
            'quantidade' => 2,
        ]);
    }

    public function test_remove_item_from_cart(): void
    {
        $livro = Livro::factory()->create();
        $this->post(route('carrinho.adicionar'), [
            'livro_id' => $livro->id,
            'quantidade' => 1,
        ]);
        $item = CarrinhoItem::first();

        $this->delete(route('carrinho.remover', $item));

        $this->assertDatabaseMissing('carrinho_itens', [
            'id' => $item->id,
        ]);
    }
}
