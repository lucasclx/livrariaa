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
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            
            // Informações básicas
            $table->string('email')->unique()->index();
            $table->string('nome')->nullable();
            $table->string('token')->unique()->comment('Token para confirmação e unsubscribe');
            
            // Status da inscrição
            $table->enum('status', ['pendente', 'ativo', 'inativo', 'bloqueado'])->default('pendente')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            
            // Preferências
            $table->json('categorias_interesse')->nullable()->comment('Categorias de livros de interesse');
            $table->boolean('receber_promocoes')->default(true);
            $table->boolean('receber_lancamentos')->default(true);
            $table->boolean('receber_avaliacoes')->default(false);
            $table->enum('frequencia', ['diaria', 'semanal', 'mensal'])->default('semanal');
            
            // Informações de origem
            $table->string('origem', 50)->nullable()->comment('De onde veio a inscrição (site, checkout, etc)');
            $table->string('ip_inscricao', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            // Estatísticas
            $table->integer('emails_enviados')->default(0);
            $table->integer('emails_abertos')->default(0);
            $table->integer('links_clicados')->default(0);
            $table->timestamp('ultimo_email_enviado')->nullable();
            $table->timestamp('ultimo_email_aberto')->nullable();
            $table->timestamp('ultimo_link_clicado')->nullable();
            
            // Dados do usuário registrado (se aplicável)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Controle de bounces e reclamações
            $table->integer('bounces')->default(0)->comment('Emails que retornaram');
            $table->integer('reclamacoes')->default(0)->comment('Marcações como spam');
            $table->timestamp('ultimo_bounce')->nullable();
            $table->timestamp('ultima_reclamacao')->nullable();
            
            $table->timestamps();
            
            // Índices para performance
            $table->index(['status', 'created_at']);
            $table->index(['confirmed_at', 'status']);
            $table->index(['receber_promocoes', 'status']);
            $table->index(['receber_lancamentos', 'status']);
            $table->index(['frequencia', 'status']);
            $table->index('origem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};