<?php
// app/Models/Livro.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Livro extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo', 'slug', 'autor', 'editora', 'isbn', 'descricao',
        'preco', 'preco_promocional', 'estoque', 'paginas', 'idioma',
        'data_publicacao', 'capa', 'categoria_id', 'ativo', 'destaque'
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'preco_promocional' => 'decimal:2',
        'data_publicacao' => 'date',
        'ativo' => 'boolean',
        'destaque' => 'boolean'
    ];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($livro) {
            $livro->slug = Str::slug($livro->titulo);
        });

        static::updating(function ($livro) {
            $livro->slug = Str::slug($livro->titulo);
        });
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function carrinhoItens()
    {
        return $this->hasMany(CarrinhoItem::class);
    }

    public function getPrecoFinalAttribute()
    {
        return $this->preco_promocional ?? $this->preco;
    }

    public function getImagemCapaAttribute()
    {
        return $this->capa ? asset('storage/uploads/livros/' . $this->capa) : asset('images/no-image.png');
    }

    public function getStatusEstoqueAttribute()
    {
        if ($this->estoque > 10) {
            return ['status' => 'disponivel', 'classe' => 'badge-stock-ok', 'texto' => 'Em estoque'];
        } elseif ($this->estoque > 0) {
            return ['status' => 'baixo', 'classe' => 'badge-stock-low', 'texto' => 'Últimas unidades'];
        } else {
            return ['status' => 'indisponivel', 'classe' => 'badge-stock-out', 'texto' => 'Esgotado'];
        }
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeDestaque($query)
    {
        return $query->where('destaque', true);
    }

    public function scopeDisponivel($query)
    {
        return $query->where('estoque', '>', 0);
    }
}