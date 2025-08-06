@extends('templates.dashboard-template')

@section('title', 'Comprador')

@section('content')
<x-dashboard-main 
    :title="'Dashboard Comprador'"
    :saldos="$saldos"
    :metricas="$metricas"
    :breadcrumbItems="[
        ['label' => 'Compras', 'icon' => 'fas fa-shopping-cart', 'url' => '#']
    ]"
/>
@endsection