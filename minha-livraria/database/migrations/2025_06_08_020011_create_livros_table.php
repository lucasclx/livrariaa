<?php
// database/migrations/create_livros_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('livros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->string('autor');
            $table->string('editora');
            $table->string('isbn')->unique()->nullable();
            $table->text('descricao');
            $table->decimal('preco', 10, 2);
            $table->decimal('preco_promocional', 10, 2)->nullable();
            $table->integer('estoque');
            $table->integer('paginas')->nullable();
            $table->string('idioma')->default('Português');
            $table->date('data_publicacao')->nullable();
            $table->string('capa')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->boolean('ativo')->default(true);
            $table->boolean('destaque')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('livros');
    }
};