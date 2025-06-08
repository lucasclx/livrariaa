<?php
// app/Models/Categoria.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome', 'slug', 'descricao', 'ativo'
    ];

    protected $casts = [
        'ativo' => 'boolean'
    ];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($categoria) {
            $categoria->slug = Str::slug($categoria->nome);
        });

        static::updating(function ($categoria) {
            $categoria->slug = Str::slug($categoria->nome);
        });
    }

    public function livros()
    {
        return $this->hasMany(Livro::class);
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}