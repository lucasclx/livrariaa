<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Display the authenticated user's wishlist items.
     */
    public function index()
    {
        $itens = Wishlist::with('livro')
            ->where('user_id', auth()->id())
            ->get();

        return view('wishlist.index', compact('itens'));
    }

    /**
     * Add a book to the user's wishlist.
     */
    public function adicionar(Request $request)
    {
        $request->validate([
            'livro_id' => 'required|exists:livros,id',
        ]);

        Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'livro_id' => $request->livro_id,
        ]);

        return back()->with('success', 'Livro adicionado à wishlist.');
    }

    /**
     * Remove an item from the user's wishlist.
     */
    public function remover(Wishlist $item)
    {
        if ($item->user_id !== auth()->id()) {
            abort(403);
        }

        $item->delete();

        return back()->with('success', 'Item removido da wishlist.');
    }
}
