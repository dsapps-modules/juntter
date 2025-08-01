@extends('templates.dashboard-template')

@section('title', 'Super Admin')

@section('content')
<x-dashboard-main 
    :title="'Dashboard Super Admin'"
    :saldos="$saldos"
    :metricas="$metricas"
/>
@endsection