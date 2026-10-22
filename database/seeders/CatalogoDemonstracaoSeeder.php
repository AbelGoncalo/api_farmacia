<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogoDemonstracaoSeeder extends Seeder
{
    /** @var array<string, string> */
    private const CATEGORIAS = [
        'Medicamentos sem receita' => 'Dados fictícios para demonstração do catálogo.',
        'Vitaminas e suplementos' => 'Dados fictícios para demonstração do catálogo.',
        'Higiene e cuidado pessoal' => 'Dados fictícios para demonstração do catálogo.',
        'Primeiros socorros' => 'Dados fictícios para demonstração do catálogo.',
        'Mãe e bebé' => 'Dados fictícios para demonstração do catálogo.',
    ];

    /** @var array<int, array{codigo: string, nome: string, categoria: string, preco: string, stock: string, unidade: string, iva: string, prescricao: bool}> */
    private const PRODUTOS = [
        ['codigo' => 'DEMO-MED-001', 'nome' => 'Paracetamol 500 mg (demonstração)', 'categoria' => 'Medicamentos sem receita', 'preco' => '850.00', 'stock' => '25.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-MED-002', 'nome' => 'Soro fisiológico 0,9% (demonstração)', 'categoria' => 'Medicamentos sem receita', 'preco' => '600.00', 'stock' => '18.000', 'unidade' => 'frasco', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-MED-003', 'nome' => 'Pastilhas para a garganta (demonstração)', 'categoria' => 'Medicamentos sem receita', 'preco' => '1200.00', 'stock' => '12.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-VIT-001', 'nome' => 'Vitamina C 1000 mg (demonstração)', 'categoria' => 'Vitaminas e suplementos', 'preco' => '2500.00', 'stock' => '15.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-VIT-002', 'nome' => 'Multivitamínico (demonstração)', 'categoria' => 'Vitaminas e suplementos', 'preco' => '3800.00', 'stock' => '9.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-VIT-003', 'nome' => 'Sais de reidratação oral (demonstração)', 'categoria' => 'Vitaminas e suplementos', 'preco' => '700.00', 'stock' => '20.000', 'unidade' => 'saqueta', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-HIG-001', 'nome' => 'Gel de banho suave (demonstração)', 'categoria' => 'Higiene e cuidado pessoal', 'preco' => '1800.00', 'stock' => '14.000', 'unidade' => 'frasco', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-HIG-002', 'nome' => 'Protetor solar FPS 50 (demonstração)', 'categoria' => 'Higiene e cuidado pessoal', 'preco' => '6500.00', 'stock' => '7.000', 'unidade' => 'frasco', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-HIG-003', 'nome' => 'Creme hidratante (demonstração)', 'categoria' => 'Higiene e cuidado pessoal', 'preco' => '3200.00', 'stock' => '11.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-PRI-001', 'nome' => 'Pensos rápidos sortidos (demonstração)', 'categoria' => 'Primeiros socorros', 'preco' => '500.00', 'stock' => '30.000', 'unidade' => 'caixa', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-PRI-002', 'nome' => 'Ligadura elástica (demonstração)', 'categoria' => 'Primeiros socorros', 'preco' => '950.00', 'stock' => '8.000', 'unidade' => 'unidade', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-PRI-003', 'nome' => 'Termómetro digital (demonstração)', 'categoria' => 'Primeiros socorros', 'preco' => '4200.00', 'stock' => '5.000', 'unidade' => 'unidade', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-BEB-001', 'nome' => 'Creme de proteção para bebé (demonstração)', 'categoria' => 'Mãe e bebé', 'preco' => '2100.00', 'stock' => '10.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-BEB-002', 'nome' => 'Toalhitas para bebé (demonstração)', 'categoria' => 'Mãe e bebé', 'preco' => '1300.00', 'stock' => '16.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
        ['codigo' => 'DEMO-BEB-003', 'nome' => 'Soro fisiológico infantil (demonstração)', 'categoria' => 'Mãe e bebé', 'preco' => '750.00', 'stock' => '0.000', 'unidade' => 'embalagem', 'iva' => '14.00', 'prescricao' => false],
    ];

    public function run(): void
    {
        $categorias = [];

        foreach (self::CATEGORIAS as $nome => $descricao) {
            $categorias[$nome] = Categoria::query()->firstOrCreate(
                ['nome' => $nome],
                ['id' => (string) Str::uuid(), 'descricao' => $descricao, 'ativa' => true],
            );
        }

        foreach (self::PRODUTOS as $dados) {
            $produto = Produto::query()->firstOrNew(['codigo' => $dados['codigo']]);

            if (! $produto->exists) {
                $produto->id = (string) Str::uuid();
            }

            $produto->fill([
                'id_produto_primavera' => null,
                'nome' => $dados['nome'],
                'categoria_id' => $categorias[$dados['categoria']]->id,
                'preco' => $dados['preco'],
                'quantidade_stock' => $dados['stock'],
                'unidade' => $dados['unidade'],
                'taxa_iva' => $dados['iva'],
                'requer_prescricao' => $dados['prescricao'],
                'disponivel_online' => true,
                'ativo' => true,
                'sincronizado_em' => null,
            ]);
            $produto->save();
        }
    }
}
