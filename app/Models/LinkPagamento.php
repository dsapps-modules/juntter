<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JsonException;

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
        'url_webhook',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_centavos' => 'integer',
        'is_avista' => 'boolean',
        'juros' => 'string',
        'data_expiracao' => 'datetime',
        'data_vencimento' => 'datetime',
        'data_limite_pagamento' => 'datetime',
        'dados_cliente' => 'array',
        'instrucoes_boleto' => 'array',
        'tipo_pagamento' => 'string',
    ];

    public static function gerarCodigoUnico(): string
    {
        do {
            $codigo = 'link_'.strtolower(substr(md5(uniqid()), 0, 8));
        } while (self::where('codigo_unico', $codigo)->exists());

        return $codigo;
    }

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

    public function getUrlCompletaAttribute(): string
    {
        return route('pagamento.link', $this->codigo_unico);
    }

    public function getValorFormatadoAttribute(): string
    {
        return 'R$ '.number_format($this->valor, 2, ',', '.');
    }

    public function getParcelasAttribute(mixed $value): array
    {
        return self::normalizarParcelas($value);
    }

    public function setParcelasAttribute(mixed $value): void
    {
        $this->attributes['parcelas'] = json_encode(self::normalizarParcelas($value));
    }

    public function getParcelasMaximasAttribute(): int
    {
        return max($this->parcelas ?: [1]);
    }

    public function getParcelasPermitidasAttribute(): array
    {
        return $this->parcelas;
    }

    public function permiteParcelamento(int $parcela): bool
    {
        return in_array($parcela, $this->parcelas_permitidas, true);
    }

    public static function parcelasAte(int $maxParcelas): array
    {
        return range(1, max(1, $maxParcelas));
    }

    public function getOpcoesParcelamentoAttribute(): array
    {
        if (empty($this->parcelas)) {
            return [1 => 'A vista'];
        }

        $opcoes = [];
        foreach ($this->parcelas as $parcela) {
            $opcoes[$parcela] = $parcela.'x';
        }

        return $opcoes;
    }

    public static function normalizarParcelas(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [1];
        }

        if (is_string($value)) {
            $decoded = self::decodificarParcelas($value);

            if ($decoded !== null) {
                return self::normalizarParcelas($decoded);
            }

            if (is_numeric($value)) {
                return self::parcelasAte((int) $value);
            }

            return [1];
        }

        if (is_int($value)) {
            return self::parcelasAte($value);
        }

        if (is_array($value)) {
            if (array_key_exists('installments', $value)) {
                return self::normalizarParcelas($value['installments']);
            }

            $parcelas = [];

            foreach ($value as $item) {
                foreach (self::normalizarParcelas($item) as $parcela) {
                    $parcelas[] = $parcela;
                }
            }

            $parcelas = array_values(array_unique(array_filter(
                array_map(static fn (mixed $parcela): int => (int) $parcela, $parcelas),
                static fn (int $parcela): bool => $parcela > 0
            )));

            sort($parcelas);

            return $parcelas === [] ? [1] : $parcelas;
        }

        return [1];
    }

    private static function decodificarParcelas(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
