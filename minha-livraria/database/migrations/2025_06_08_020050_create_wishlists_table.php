<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('livro_id')->constrained('livros')->onDelete('cascade');
            
            // Metadados
            $table->integer('prioridade')->default(0)->comment('Prioridade na wishlist (0 = baixa, 10 = alta)');
            $table->text('observacoes')->nullable()->comment('Observações pessoais sobre o livro');
            
            // Notificações
            $table->boolean('notificar_promocao')->default(true)->comment('Notificar quando entrar em promoção');
            $table->boolean('notificar_disponibilidade')->default(true)->comment('Notificar quando voltar ao estoque');
            $table->boolean('notificar_lancamento')->default(false)->comment('Notificar sobre livros similares');
            
            // Controle de preços
            $table->decimal('preco_desejado', 10, 2)->nullable()->comment('Preço que o usuário gostaria de pagar');
            $table->decimal('preco_quando_adicionado', 10, 2)->nullable()->comment('Preço quando foi adicionado à wishlist');
            
            // Dados de acompanhamento
            $table->timestamp('notificado_em')->nullable()->comment('Última vez que foi notificado sobre este item');
            $table->integer('visualizacoes')->default(0)->comment('Quantas vezes o usuário visitou a página do livro');
            $table->timestamp('ultima_visualizacao')->nullable();
            
            // Status
            $table->enum('status', ['ativo', 'comprado', 'removido', 'indisponivel'])->default('ativo')->index();
            $table->timestamp('status_alterado_em')->nullable();
            
            $table->timestamps();
            
            // Índices para performance
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['livro_id', 'status']);
            $table->index(['prioridade', 'status']);
            $table->index(['notificar_promocao', 'status']);
            $table->index(['notificar_disponibilidade', 'status']);
            
            // Garantir que um usuário não adicione o mesmo livro duas vezes
            $table->unique(['user_id', 'livro_id'], 'unique_user_livro_wishlist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};