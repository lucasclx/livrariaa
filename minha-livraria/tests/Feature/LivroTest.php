<?php

namespace Tests\Feature;

use App\Models\Livro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivroTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_listing_displays_books(): void
    {
        $livro = Livro::factory()->create();

        $response = $this->get('/livros');

        $response->assertStatus(200);
        $response->assertSee($livro->titulo);
    }
}
