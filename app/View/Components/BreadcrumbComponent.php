<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BreadcrumbComponent extends Component
{
    public $items;

    public function __construct()
    {
        $this->items = $this->getBreadcrumbItems();
    }

    public function render()
    {
        return view('components.breadcrumb');
    }

    private function getBreadcrumbItems()
    {
        $route = request()->route();
        $routeName = $route ? $route->getName() : null;

        switch ($routeName) {
            case 'super_admin.dashboard':
                return [
                    ['label' => 'Dashboard Super Admin']
                ];
            
            case 'admin.dashboard':
                return [
                    ['label' => 'Dashboard Admin']
                ];
            
            case 'vendedor.dashboard':
                return [
                    ['label' => 'Dashboard Vendedor']
                ];
            
            case 'comprador.dashboard':
                return [
                    ['label' => 'Dashboard Comprador']
                ];
            
            case 'cobranca.index':
                return [
                    ['label' => 'Cobrança Única']
                ];
            
            case 'cobranca.recorrente':
                return [
                    ['label' => 'Cobrança Recorrente']
                ];
            
            case 'cobranca.planos':
                return [
                    ['label' => 'Planos de Cobrança']
                ];
            
            case 'cobranca.pix':
                return [
                    ['label' => 'Enviar Pix']
                ];
            
            case 'cobranca.pagarcontas':
                return [
                    ['label' => 'Pagar Contas']
                ];
            
            case 'cobranca.saldoextrato':
                return [
                    ['label' => 'Saldo e Extrato']
                ];
            
            default:
                return [];
        }
    }
} 