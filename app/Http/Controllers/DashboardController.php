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
        return view('dashboard.super_admin');
    }

    /**
     * Dashboard do Administrador
     */
    public function adminDashboard()
    {
        return view('dashboard.admin');
    }

    /**
     * Dashboard do Vendedor
     */
    public function vendedorDashboard()
    {
        return view('dashboard.vendedor');
    }

    /**
     * Dashboard do Comprador
     */
    public function compradorDashboard()
    {
        return view('dashboard.comprador');
    }
}
