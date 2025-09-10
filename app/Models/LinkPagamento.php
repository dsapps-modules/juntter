<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkPagamento extends Model
{
    use HasFactory;

    protected $table = 'links_pagamento';

    protected $fillable = [
        'estabelecimento_id',
        'codigo_unico',
        'descricao',
        'valor',
        'valor_centavos',
        'parcelas',
        'is_avista',
        'juros',
        'status',
        'data_expiracao',
        'data_vencimento',
        'data_limite_pagamento',
        'dados_cliente',
        'instrucoes_boleto',
        'tipo_pagamento',
        'url_retorno',
        'url_webhook'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_centavos' => 'integer',
        'parcelas' => 'integer',
        'is_avista' => 'boolean',
        'juros' => 'string',
        'data_expiracao' => 'datetime',
        'data_vencimento' => 'datetime',
        'data_limite_pagamento' => 'datetime',
        'dados_cliente' => 'array',
        'instrucoes_boleto' => 'array',
        'tipo_pagamento' => 'string'
    ];

    /**
     * Gerar código único para o link
     */
    public static function gerarCodigoUnico(): string
    {
        do {
            $codigo = 'link_' . strtolower(substr(md5(uniqid()), 0, 8));
        } while (self::where('codigo_unico', $codigo)->exists());

        return $codigo;
    }

    /**
     * Verificar se o link está ativo
     */
    public function estaAtivo(): bool
    {
        if ($this->status !== 'ATIVO') {
            return false;
        }

        if ($this->data_expiracao && now()->isAfter($this->data_expiracao)) {
            return false;
        }

        return true;
    }

    /**
     * Gerar URL completa do link de pagamento
     */
    public function getUrlCompletaAttribute(): string
    {
        return route('pagamento.link', $this->codigo_unico);
    }

    /**
     * Obter valor formatado em reais
     */
    public function getValorFormatadoAttribute(): string
    {
        return 'R$ ' . number_format($this->valor, 2, ',', '.');
    }

    /**
     * Obter opções de parcelamento disponíveis
     */
    public function getOpcoesParcelamentoAttribute(): array
    {
        if (empty($this->parcelas) || !is_array($this->parcelas)) {
            return [1 => 'À vista'];
        }

        $opcoes = [];
        foreach ($this->parcelas as $parcela) {
            $opcoes[$parcela] = $parcela . 'x';
        }

        return $opcoes;
    }
}
