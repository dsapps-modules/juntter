<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard do Super Administrador
     */
    public function superAdminDashboard()
    {
        $saldos = [
            'disponivel' => 'R$ 15.500,00',
            'transito' => 'R$ 2.300,00',
            'bloqueado_cartao' => 'R$ 850,00',
            'bloqueado_boleto' => 'R$ 1.200,00'
        ];

        $metricas = [
            [
                'valor' => '25',
                'label' => 'Total de Usuários',
                'icone' => 'fas fa-users',
                'cor' => 'metric-icon-blue'
            ],
            [
                'valor' => '142',
                'label' => 'Transações Hoje',
                'icone' => 'fas fa-exchange-alt',
                'cor' => 'metric-icon-green'
            ],
            [
                'valor' => '8',
                'label' => 'Sistemas Ativos',
                'icone' => 'fas fa-server',
                'cor' => 'metric-icon-teal'
            ],
            [
                'valor' => 'R$ 45.200,00',
                'label' => 'Receita Total',
                'icone' => 'fas fa-chart-line',
                'cor' => 'metric-icon-cyan'
            ],
            [
                'valor' => '98.5%',
                'label' => 'Uptime do Sistema',
                'icone' => 'fas fa-check-circle',
                'cor' => 'metric-icon-green'
            ],
            [
                'valor' => '3',
                'label' => 'Alertas Pendentes',
                'icone' => 'fas fa-exclamation-triangle',
                'cor' => 'metric-icon-red'
            ]
        ];

        return view('dashboard.super_admin', compact('saldos', 'metricas'));
    }

    /**
     * Dashboard do Administrador
     */
    public function adminDashboard()
    {
        $saldos = [
            'disponivel' => 'R$ 8.750,00',
            'transito' => 'R$ 1.200,00',
            'bloqueado_cartao' => 'R$ 450,00',
            'bloqueado_boleto' => 'R$ 800,00'
        ];

        $metricas = [
            [
                'valor' => '5',
                'label' => 'Clientes Inadimplentes',
                'icone' => 'fas fa-user-times',
                'cor' => 'metric-icon-red'
            ],
            [
                'valor' => '89',
                'label' => 'Transações Pagas',
                'icone' => 'fas fa-dollar-sign',
                'cor' => 'metric-icon-blue'
            ],
            [
                'valor' => '12',
                'label' => 'Contratos Ativos',
                'icone' => 'fas fa-file-contract',
                'cor' => 'metric-icon-teal'
            ],
            [
                'valor' => 'R$ 3.200,00',
                'label' => 'A vencer nos próximos dias',
                'icone' => 'fas fa-calendar-check',
                'cor' => 'metric-icon-cyan'
            ],
            [
                'valor' => 'R$ 850,00',
                'label' => 'Ticket médio: contratos',
                'icone' => 'fas fa-chart-line',
                'cor' => 'metric-icon-teal'
            ],
            [
                'valor' => 'R$ 420,00',
                'label' => 'Ticket médio: vendas',
                'icone' => 'fas fa-shopping-cart',
                'cor' => 'metric-icon-green'
            ]
        ];

        return view('dashboard.admin', compact('saldos', 'metricas'));
    }

    /**
     * Dashboard do Vendedor
     */
    public function vendedorDashboard()
    {
        $saldos = [
            'disponivel' => 'R$ 3.250,00',
            'transito' => 'R$ 680,00',
            'bloqueado_cartao' => 'R$ 120,00',
            'bloqueado_boleto' => 'R$ 200,00'
        ];

        $metricas = [
            [
                'valor' => '2',
                'label' => 'Clientes Inadimplentes',
                'icone' => 'fas fa-user-times',
                'cor' => 'metric-icon-red'
            ],
            [
                'valor' => '24',
                'label' => 'Transações Pagas',
                'icone' => 'fas fa-dollar-sign',
                'cor' => 'metric-icon-blue'
            ],
            [
                'valor' => '8',
                'label' => 'Contratos Ativos',
                'icone' => 'fas fa-file-contract',
                'cor' => 'metric-icon-teal'
            ],
            [
                'valor' => 'R$ 1.850,00',
                'label' => 'A vencer nos próximos dias',
                'icone' => 'fas fa-calendar-check',
                'cor' => 'metric-icon-cyan'
            ],
            [
                'valor' => 'R$ 485,00',
                'label' => 'Ticket médio: contratos',
                'icone' => 'fas fa-chart-line',
                'cor' => 'metric-icon-teal'
            ],
            [
                'valor' => 'R$ 320,00',
                'label' => 'Ticket médio: vendas',
                'icone' => 'fas fa-shopping-cart',
                'cor' => 'metric-icon-green'
            ]
        ];

        return view('dashboard.vendedor', compact('saldos', 'metricas'));
    }

    /**
     * Dashboard do Comprador
     */
    public function compradorDashboard()
    {
        $saldos = [
            'disponivel' => 'R$ 1.500,00',
            'transito' => 'R$ 300,00',
            'bloqueado_cartao' => 'R$ 0,00',
            'bloqueado_boleto' => 'R$ 150,00'
        ];

        $metricas = [
            [
                'valor' => '12',
                'label' => 'Compras Realizadas',
                'icone' => 'fas fa-shopping-cart',
                'cor' => 'metric-icon-blue'
            ],
            [
                'valor' => '8',
                'label' => 'Produtos Favoritos',
                'icone' => 'fas fa-heart',
                'cor' => 'metric-icon-red'
            ],
            [
                'valor' => '3',
                'label' => 'Pedidos Pendentes',
                'icone' => 'fas fa-clock',
                'cor' => 'metric-icon-teal'
            ],
            [
                'valor' => 'R$ 2.840,00',
                'label' => 'Total Gasto',
                'icone' => 'fas fa-money-bill-wave',
                'cor' => 'metric-icon-cyan'
            ],
            [
                'valor' => 'R$ 237,00',
                'label' => 'Ticket Médio',
                'icone' => 'fas fa-chart-bar',
                'cor' => 'metric-icon-green'
            ],
            [
                'valor' => '5',
                'label' => 'Cashback Disponível',
                'icone' => 'fas fa-gift',
                'cor' => 'metric-icon-green'
            ]
        ];

        return view('dashboard.comprador', compact('saldos', 'metricas'));
    }
}
