<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Carrinho extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
    ];

    /**
     * Define a relação "um-para-muitos": um carrinho tem muitos itens.
     * Este é o método que estava a faltar.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CarrinhoItem::class);
    }

    /**
     * Define a relação inversa: um carrinho pertence a um utilizador.
     * Relação opcional, mas útil.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}