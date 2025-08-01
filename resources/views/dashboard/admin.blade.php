@extends('templates.dashboard-template')

@section('title', 'Dashboard')

@section('content')
<x-dashboard-main 
    :title="'Dashboard Admin'"
    :saldos="$saldos"
    :metricas="$metricas"
/>
@endsection









