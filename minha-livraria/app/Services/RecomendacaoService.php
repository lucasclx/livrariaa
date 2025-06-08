<?php

namespace App\Services;

use App\Models\Livro;
use App\Models\User;

class RecomendacaoService
{
    /**
     * Retorna uma coleção de livros recomendados.
     */
    public function recomendar(Livro $livro, ?User $user = null, int $limit = 4)
    {
        $categorias = collect([$livro->categoria_id]);

        if ($user) {
            $categoriasCompradas = $user->pedidos()
                ->with('itens.livro')
                ->get()
                ->pluck('itens.*.livro.categoria_id')
                ->flatten()
                ->unique();

            $categorias = $categorias->merge($categoriasCompradas);
        }

        return Livro::whereIn('categoria_id', $categorias->unique())
            ->where('id', '!=', $livro->id)
            ->ativo()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
