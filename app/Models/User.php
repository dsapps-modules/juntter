<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        'email',
        'password',
        'nivel_acesso',
        'email_verified_at',
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
        'password' => 'hashed',
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
     * Verifica se o usuário é vendedor
     */
    public function isVendedor(): bool
    {
        return $this->nivel_acesso === 'vendedor';
    }

    /**
     * Verifica se o usuário é comprador
     */
    public function isComprador(): bool
    {
        return $this->nivel_acesso === 'comprador';
    }

    /**
     * Verifica se o usuário tem nível super admin ou admin
     */
    public function isSuperAdminOrAdmin(): bool
    {
        return in_array($this->nivel_acesso, ['super_admin', 'admin']);
    }

    /**
     * Verifica se o usuário tem nível super admin, admin ou vendedor
     */
    public function isSuperAdminOrAdminOrVendedor(): bool
    {
        return in_array($this->nivel_acesso, ['super_admin', 'admin', 'vendedor']);
    }

    /**
     * Verifica se o usuário tem nível super admin, admin, vendedor ou comprador
     */
    public function isSuperAdminOrAdminOrVendedorOrComprador(): bool
    {
        return in_array($this->nivel_acesso, ['super_admin', 'admin', 'vendedor', 'comprador']);
    }

    /**
     * Relacionamento com Vendedor (1:1)
     */
    public function vendedor()
    {
        return $this->hasOne(Vendedor::class);
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
