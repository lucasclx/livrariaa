<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categoria;
use App\Models\Livro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar usuário admin
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@livraria.com',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Criar usuário comum
        User::create([
            'name' => 'Cliente Teste',
            'email' => 'cliente@teste.com',
            'password' => Hash::make('123456'),
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        // Criar categorias
        $categorias = [
            [
                'nome' => 'Ficção',
                'descricao' => 'Livros de ficção, romances e literatura',
                'ativo' => true
            ],
            [
                'nome' => 'Não-ficção',
                'descricao' => 'Livros de não-ficção, biografias e ensaios',
                'ativo' => true
            ],
            [
                'nome' => 'Tecnologia',
                'descricao' => 'Livros sobre programação, desenvolvimento e tecnologia',
                'ativo' => true
            ],
            [
                'nome' => 'Negócios',
                'descricao' => 'Livros sobre empreendedorismo, gestão e negócios',
                'ativo' => true
            ],
            [
                'nome' => 'Autoajuda',
                'descricao' => 'Livros de desenvolvimento pessoal e autoajuda',
                'ativo' => true
            ],
            [
                'nome' => 'História',
                'descricao' => 'Livros sobre história mundial e brasileira',
                'ativo' => true
            ],
            [
                'nome' => 'Ciência',
                'descricao' => 'Livros científicos e divulgação científica',
                'ativo' => true
            ],
            [
                'nome' => 'Arte',
                'descricao' => 'Livros sobre arte, design e criatividade',
                'ativo' => true
            ]
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }

        // Criar livros de exemplo
        $livros = [
            // Ficção
            [
                'titulo' => 'Dom Casmurro',
                'autor' => 'Machado de Assis',
                'editora' => 'Penguin Classics',
                'isbn' => '9788563560087',
                'descricao' => 'Clássico da literatura brasileira que narra a história de Bentinho e Capitu.',
                'preco' => 29.90,
                'preco_promocional' => null,
                'estoque' => 15,
                'paginas' => 256,
                'idioma' => 'Português',
                'data_publicacao' => '1899-01-01',
                'categoria_id' => 1,
                'ativo' => true,
                'destaque' => true
            ],
            [
                'titulo' => 'O Cortiço',
                'autor' => 'Aluísio Azevedo',
                'editora' => 'Martin Claret',
                'isbn' => '9788572327429',
                'descricao' => 'Romance naturalista que retrata a vida em um cortiço no Rio de Janeiro.',
                'preco' => 24.90,
                'preco_promocional' => 19.90,
                'estoque' => 20,
                'paginas' => 304,
                'idioma' => 'Português',
                'data_publicacao' => '1890-01-01',
                'categoria_id' => 1,
                'ativo' => true,
                'destaque' => false
            ],
            
            // Tecnologia
            [
                'titulo' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'autor' => 'Robert C. Martin',
                'editora' => 'Prentice Hall',
                'isbn' => '9780132350884',
                'descricao' => 'Guia essencial para escrever código limpo e manutenível.',
                'preco' => 89.90,
                'preco_promocional' => null,
                'estoque' => 8,
                'paginas' => 464,
                'idioma' => 'Inglês',
                'data_publicacao' => '2008-08-01',
                'categoria_id' => 3,
                'ativo' => true,
                'destaque' => true
            ],
            [
                'titulo' => 'Laravel: Up & Running',
                'autor' => 'Matt Stauffer',
                'editora' => 'O\'Reilly Media',
                'isbn' => '9781491936672',
                'descricao' => 'Guia completo para desenvolvimento web com Laravel.',
                'preco' => 125.90,
                'preco_promocional' => 99.90,
                'estoque' => 12,
                'paginas' => 488,
                'idioma' => 'Inglês',
                'data_publicacao' => '2019-03-15',
                'categoria_id' => 3,
                'ativo' => true,
                'destaque' => false
            ],
            
            // Negócios
            [
                'titulo' => 'O Poder do Hábito',
                'autor' => 'Charles Duhigg',
                'editora' => 'Objetiva',
                'isbn' => '9788547000058',
                'descricao' => 'Como os hábitos funcionam e como transformá-los.',
                'preco' => 39.90,
                'preco_promocional' => 34.90,
                'estoque' => 25,
                'paginas' => 408,
                'idioma' => 'Português',
                'data_publicacao' => '2012-02-28',
                'categoria_id' => 4,
                'ativo' => true,
                'destaque' => true
            ],
            [
                'titulo' => 'Lean Startup',
                'autor' => 'Eric Ries',
                'editora' => 'Crown Business',
                'isbn' => '9780307887894',
                'descricao' => 'Como empreendedores de hoje usam inovação contínua para criar negócios de sucesso.',
                'preco' => 59.90,
                'preco_promocional' => null,
                'estoque' => 18,
                'paginas' => 336,
                'idioma' => 'Português',
                'data_publicacao' => '2011-09-13',
                'categoria_id' => 4,
                'ativo' => true,
                'destaque' => false
            ],
            
            // Autoajuda
            [
                'titulo' => 'Sapiens: Uma Breve História da Humanidade',
                'autor' => 'Yuval Noah Harari',
                'editora' => 'L&PM Editores',
                'isbn' => '9788525432322',
                'descricao' => 'Uma jornada ousada através da história da espécie humana.',
                'preco' => 49.90,
                'preco_promocional' => 42.90,
                'estoque' => 30,
                'paginas' => 464,
                'idioma' => 'Português',
                'data_publicacao' => '2011-01-01',
                'categoria_id' => 5,
                'ativo' => true,
                'destaque' => true
            ],
            [
                'titulo' => 'Mindset: A Nova Psicologia do Sucesso',
                'autor' => 'Carol S. Dweck',
                'editora' => 'Objetiva',
                'isbn' => '9788547001261',
                'descricao' => 'Como podemos aprender a cumprir nosso potencial.',
                'preco' => 34.90,
                'preco_promocional' => null,
                'estoque' => 22,
                'paginas' => 312,
                'idioma' => 'Português',
                'data_publicacao' => '2006-02-28',
                'categoria_id' => 5,
                'ativo' => true,
                'destaque' => false
            ],
            
            // História
            [
                'titulo' => '1808: Como uma Rainha Louca, um Príncipe Medroso e uma Corte Corrupta Enganaram Napoleão',
                'autor' => 'Laurentino Gomes',
                'editora' => 'Planeta',
                'isbn' => '9788576654258',
                'descricao' => 'A história da vinda da família real portuguesa para o Brasil.',
                'preco' => 44.90,
                'preco_promocional' => 39.90,
                'estoque' => 16,
                'paginas' => 368,
                'idioma' => 'Português',
                'data_publicacao' => '2007-01-01',
                'categoria_id' => 6,
                'ativo' => true,
                'destaque' => true
            ],
            
            // Ciência
            [
                'titulo' => 'Uma Breve História do Tempo',
                'autor' => 'Stephen Hawking',
                'editora' => 'Intrínseca',
                'isbn' => '9788551004173',
                'descricao' => 'Do Big Bang aos buracos negros, uma introdução à cosmologia.',
                'preco' => 39.90,
                'preco_promocional' => null,
                'estoque' => 14,
                'paginas' => 256,
                'idioma' => 'Português',
                'data_publicacao' => '1988-04-01',
                'categoria_id' => 7,
                'ativo' => true,
                'destaque' => false
            ]
        ];

        foreach ($livros as $livro) {
            Livro::create($livro);
        }

        $this->command->info('✅ Dados de teste criados com sucesso!');
        $this->command->info('👤 Admin: admin@livraria.com / admin123');
        $this->command->info('👤 Cliente: cliente@teste.com / 123456');
    }
}