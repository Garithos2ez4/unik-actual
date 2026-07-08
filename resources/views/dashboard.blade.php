@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div id="dashboard-content">
    <x-dashboard_content :user="$user" :registros="$registros" :inventario="$inventario" :stock="$stock" :colors="$colors" :productos="$productos" :stockMin="$stockMin" :productosMostSold="$productosMostSold" :productosMostSoldMonth="$productosMostSoldMonth" :skusMostSoldMonth="$skusMostSoldMonth" :publicacionesMostSold="$publicacionesMostSold" :reclamosUrgentes="$reclamosUrgentes" :productosMostStock="$productosMostStock" :productos_old_stock="$productosOldStock" :publicacionesTopMonto="$publicacionesTopMonto" :publicacionesTopMontoHist="$publicacionesTopMontoHist" :productosConFallas="$productosConFallas" :ventas7Dias="$ventas7Dias" />
</div>
<input type="hidden" id="dashboardurl" value="{{route('dashboard')}}">
<script src="{{asset('js/dashboard.js')}}"></script>

@if(isset($devolucionesHoy) && $devolucionesHoy->count() > 0)
    @include('components.modal_devoluciones_hoy', ['devoluciones' => $devolucionesHoy])
@endif

@endsection