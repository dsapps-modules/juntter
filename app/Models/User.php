<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'trade_name',
        'email',
        'password',
        'nivel_acesso',
        'email_verified_at',
        'company_logo_path',
        'electronic_signature_hash',
        'electronic_signature_pending_hash',
        'electronic_signature_code_hash',
        'electronic_signature_code_attempts',
        'electronic_signature_code_sent_at',
        'electronic_signature_code_expires_at',
        'electronic_signature_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'electronic_signature_code_attempts' => 'integer',
        'electronic_signature_code_sent_at' => 'datetime',
        'electronic_signature_code_expires_at' => 'datetime',
        'electronic_signature_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'avatar_url',
    ];

    /**
     * Verifica se o usuário é super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->nivel_acesso === 'super_admin';
    }

    /**
     * Verifica se o usuário é admin
     */
    public function isAdmin(): bool
    {
        return $this->nivel_acesso === 'admin';
    }

    /**
     * Verifica se o usuário tem nível super admin ou admin
     */
    public function isSuperAdminOrAdmin(): bool
    {
        return in_array($this->nivel_acesso, ['super_admin', 'admin']);
    }

    /**
     * Verifica se o usuário é vendedor
     */
    public function isVendedor(): bool
    {
        return $this->nivel_acesso === 'vendedor';
    }

    /**
     * Verifica se o usuário tem nível super admin, admin ou vendedor
     */
    public function isSuperAdminOrAdminOrVendedor(): bool
    {
        return in_array($this->nivel_acesso, ['super_admin', 'admin', 'vendedor']);
    }

    /**
     * Relacionamento com Vendedor (1:1)
     */
    public function vendedor()
    {
        return $this->hasOne(Vendedor::class);
    }

    public function shippingOptions(): HasMany
    {
        return $this->hasMany(CheckoutShippingOption::class, 'seller_id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (filled($this->company_logo_path) && Storage::disk('public')->exists($this->company_logo_path)) {
            return '/company-logo?path='.rawurlencode($this->company_logo_path);
        }

        return null;
    }

    /**
     * Verifica se é admin de loja
     */
    public function isAdminLoja(): bool
    {
        return $this->isVendedor() && $this->vendedor && $this->vendedor->isAdminLoja();
    }

    /**
     * Verifica se é vendedor de loja
     */
    public function isVendedorLoja(): bool
    {
        return $this->isVendedor() && $this->vendedor && $this->vendedor->isVendedorLoja();
    }

    /**
     * Obtém o ID do estabelecimento (se for vendedor)
     */
    public function getEstabelecimentoId()
    {
        return $this->vendedor ? $this->vendedor->estabelecimento_id : null;
    }
}
