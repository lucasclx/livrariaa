<?php

namespace App\Http\Controllers;

use App\Models\Livro;
use App\Services\RecomendacaoService;

class RecomendacaoController extends Controller
{
    private RecomendacaoService $service;

    public function __construct(RecomendacaoService $service)
    {
        $this->service = $service;
    }

    public function show(Livro $livro)
    {
        $user = auth()->user();
        $recomendados = $this->service->recomendar($livro, $user);

        return response()->json($recomendados);
    }
}
