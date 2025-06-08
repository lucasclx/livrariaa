<?php
// app/Http/Controllers/LivroController.php

namespace App\Http\Controllers;

use App\Models\Livro;
use App\Models\Categoria;
use Illuminate\Http\Request;

class LivroController extends Controller
{
    public function index(Request $request)
    {
        $query = Livro::with('categoria')->ativo();

        // Filtros
        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->categoria);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                  ->orWhere('autor', 'like', "%{$busca}%")
                  ->orWhere('editora', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('preco_min')) {
            $query->where('preco', '>=', $request->preco_min);
        }

        if ($request->filled('preco_max')) {
            $query->where('preco', '<=', $request->preco_max);
        }

        // Ordenação
        $orderBy = $request->get('orderby', 'titulo');
        $order = $request->get('order', 'asc');
        
        $validOrderBy = ['titulo', 'preco', 'created_at', 'autor'];
        $validOrder = ['asc', 'desc'];

        if (in_array($orderBy, $validOrderBy) && in_array($order, $validOrder)) {
            $query->orderBy($orderBy, $order);
        }

        $livros = $query->paginate(12);
        $categorias = Categoria::ativo()->orderBy('nome')->get();

        // Estatísticas
        $stats = [
            'total_livros' => Livro::ativo()->count(),
            'total_categorias' => Categoria::ativo()->count(),
            'livros_destaque' => Livro::ativo()->destaque()->count(),
            'valor_medio' => Livro::ativo()->avg('preco')
        ];

        return view('livros.index', compact('livros', 'categorias', 'stats'));
    }

    public function show($slug)
    {
        $livro = Livro::with('categoria')->where('slug', $slug)->ativo()->firstOrFail();
        
        // Livros relacionados da mesma categoria
        $livrosRelacionados = Livro::with('categoria')
            ->where('categoria_id', $livro->categoria_id)
            ->where('id', '!=', $livro->id)
            ->ativo()
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('livros.show', compact('livro', 'livrosRelacionados'));
    }
}