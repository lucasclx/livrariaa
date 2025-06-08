<?php

use App\Http\Controllers\LivroController;
use App\Http\Controllers\CarrinhoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\RecomendacaoController;
use App\Http\Controllers\ProfileController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LivroController as AdminLivroController;
use App\Http\Controllers\Admin\CategoriaController as AdminCategoriaController;
use App\Http\Controllers\Admin\PedidoController as AdminPedidoController;
use App\Http\Controllers\Admin\CupomController as AdminCupomController;
use App\Http\Controllers\Admin\RelatorioController as AdminRelatorioController;
use App\Http\Controllers\Admin\AvaliacaoController as AdminAvaliacaoController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/

// Página inicial - redirecionamento para catálogo
Route::get('/', function () {
    return redirect()->route('livros.index');
});

// Catálogo de livros
Route::get('/livros', [LivroController::class, 'index'])->name('livros.index');
Route::get('/livros/{slug}', [LivroController::class, 'show'])->name('livros.show');

// Carrinho de compras
Route::prefix('carrinho')->name('carrinho.')->group(function () {
    Route::get('/', [CarrinhoController::class, 'index'])->name('index');
    Route::post('/adicionar', [CarrinhoController::class, 'adicionar'])->name('adicionar');
    Route::put('/atualizar/{item}', [CarrinhoController::class, 'atualizar'])->name('atualizar');
    Route::delete('/remover/{item}', [CarrinhoController::class, 'remover'])->name('remover');
    Route::delete('/limpar', [CarrinhoController::class, 'limpar'])->name('limpar');
});

// Checkout
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/processar', [CheckoutController::class, 'processar'])->name('processar');
});

// Newsletter
Route::prefix('newsletter')->name('newsletter.')->group(function () {
    Route::post('/inscrever', [NewsletterController::class, 'inscrever'])->name('inscrever');
    Route::get('/confirmar/{token}', [NewsletterController::class, 'confirmar'])->name('confirmar');
});

/*
|--------------------------------------------------------------------------
| Rotas Autenticadas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    
    // Pedidos do usuário
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/', [PedidoController::class, 'index'])->name('index');
        Route::get('/{pedido}', [PedidoController::class, 'show'])->name('show');
        Route::get('/sucesso/{pedido}', [PedidoController::class, 'sucesso'])->name('sucesso');
    });
    
    // Wishlist
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/adicionar', [WishlistController::class, 'adicionar'])->name('adicionar');
        Route::delete('/remover/{item}', [WishlistController::class, 'remover'])->name('remover');
    });
    
    // Avaliações
    Route::prefix('avaliacoes')->name('avaliacoes.')->group(function () {
        Route::post('/', [AvaliacaoController::class, 'store'])->name('store');
        Route::put('/{avaliacao}', [AvaliacaoController::class, 'update'])->name('update');
        Route::delete('/{avaliacao}', [AvaliacaoController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Rotas de Administração
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    
});

/*
|--------------------------------------------------------------------------
| Rotas API
|--------------------------------------------------------------------------
*/

Route::prefix('api')->name('api.')->group(function () {
    
    // API pública para catálogo
    Route::get('livros', [App\Http\Controllers\Api\LivroController::class, 'index']);
    Route::get('livros/{id}', [App\Http\Controllers\Api\LivroController::class, 'show']);
    Route::get('categorias', [App\Http\Controllers\Api\CategoriaController::class, 'index']);
    
    // APIs autenticadas
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('carrinho/adicionar', [CarrinhoController::class, 'adicionar']);
        Route::get('carrinho', [CarrinhoController::class, 'index']);
        Route::post('avaliacoes', [AvaliacaoController::class, 'store']);
    });
});

/*
|--------------------------------------------------------------------------
| Rotas de Fallback e Utilitárias
|--------------------------------------------------------------------------
*/

// Rota para busca rápida (AJAX)
Route::get('/buscar', [LivroController::class, 'buscar'])->name('livros.buscar');

// Rota para recomendações
Route::get('/recomendacoes/{livro}', [RecomendacaoController::class, 'show'])->name('recomendacoes.show');

// Rota de fallback para páginas não encontradas
Route::fallback(function () {
    return view('errors.404');
});

/*
|--------------------------------------------------------------------------
| Rotas de Teste (Apenas em ambiente de desenvolvimento)
|--------------------------------------------------------------------------
*/

if (app()->environment('local')) {
    Route::get('/test-seed', function () {
        // Rota para popular dados de teste
        Artisan::call('db:seed');
        return 'Dados de teste criados com sucesso!';
    });
    
    Route::get('/test-clear', function () {
        // Rota para limpar cache em desenvolvimento
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        return 'Cache limpo com sucesso!';
    });
}

// Dashboard e perfil de usuário gerenciados pelo Breeze
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
