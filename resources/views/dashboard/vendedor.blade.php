@extends('templates.dashboard-template')

@section('title', 'Dashboard')

@section('content')
<x-dashboard-main 
    :title="'Dashboard Vendedor'"
    :saldos="$saldos"
    :metricas="$metricas"
    :breadcrumbItems="[
        ['label' => 'Vendas', 'icon' => 'fas fa-chart-line', 'url' => '#']
    ]"
/>
@endsection