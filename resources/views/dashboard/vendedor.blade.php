@extends('templates.dashboard-template')

@section('title', 'Dashboard')

@section('content')
<x-dashboard-main 
    :title="'Dashboard Vendedor'"
    :saldos="$saldos"
    :metricas="$metricas"
/>
@endsection