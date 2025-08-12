<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'vendedores';

    protected $fillable = [
        'user_id',
        'estabelecimento_id',
        'sub_nivel',
        'comissao',
        'meta_vendas',
        'telefone',
        'endereco',
        'status'
    ];

    protected $casts = [
        'comissao' => 'decimal:2',
        'meta_vendas' => 'decimal:2',
    ];

    /**
     * Relacionamento com User (1:1)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se é admin de loja
     */
    public function isAdminLoja(): bool
    {
        return $this->sub_nivel === 'admin_loja';
    }

    /**
     * Verifica se é vendedor de loja
     */
    public function isVendedorLoja(): bool
    {
        return $this->sub_nivel === 'vendedor_loja';
    }

    /**
     * Verifica se está ativo
     */
    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Formata comissão para exibição
     */
    public function getComissaoFormatadaAttribute(): string
    {
        return $this->comissao ? number_format($this->comissao, 2, ',', '.') . '%' : 'N/A';
    }

    /**
     * Formata meta de vendas para exibição
     */
    public function getMetaVendasFormatadaAttribute(): string
    {
        return $this->meta_vendas ? 'R$ ' . number_format($this->meta_vendas, 2, ',', '.') : 'N/A';
    }
}
