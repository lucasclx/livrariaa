<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Categoria;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Livro>
 */
class LivroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => $this->faker->unique()->sentence(3),
            'autor' => $this->faker->name(),
            'editora' => $this->faker->company(),
            'isbn' => $this->faker->unique()->isbn13(),
            'descricao' => $this->faker->paragraph(),
            'preco' => $this->faker->randomFloat(2, 10, 100),
            'preco_promocional' => null,
            'estoque' => $this->faker->numberBetween(1, 20),
            'paginas' => $this->faker->numberBetween(100, 500),
            'idioma' => 'Português',
            'data_publicacao' => $this->faker->date(),
            'categoria_id' => Categoria::factory(),
            'ativo' => true,
            'destaque' => false,
        ];
    }
}
