<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Register a new e-mail in the newsletter list.
     */
    public function inscrever(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email',
        ]);

        NewsletterSubscriber::create([
            'email' => $request->email,
            'token' => Str::uuid(),
            'status' => 'pendente',
        ]);

        return back()->with('success', 'Inscrição realizada. Verifique seu e-mail para confirmar.');
    }

    /**
     * Confirm subscription using the provided token.
     */
    public function confirmar($token)
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        $subscriber->update([
            'status' => 'ativo',
            'confirmed_at' => now(),
        ]);

        return redirect()->route('livros.index')
            ->with('success', 'Inscrição confirmada com sucesso.');
    }
}
