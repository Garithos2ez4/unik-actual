@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div id="dashboard-content">
    <x-dashboard_content :user="$user" :registros="$registros" :inventario="$inventario" :stock="$stock" :colors="$colors" :productos="$productos" :stockMin="$stockMin" :productosMostSold="$productosMostSold" :productosMostSoldMonth="$productosMostSoldMonth" :skusMostSoldMonth="$skusMostSoldMonth" :publicacionesMostSold="$publicacionesMostSold" :reclamosUrgentes="$reclamosUrgentes" :productosMostStock="$productosMostStock" :productosOldStock="$productosOldStock" :publicacionesTopMonto="$publicacionesTopMonto" :publicacionesTopMontoHist="$publicacionesTopMontoHist" :productosConFallas="$productosConFallas" :ventas7Dias="$ventas7Dias" />
</div>
<input type="hidden" id="dashboardurl" value="{{route('dashboard')}}">
<script src="{{asset('js/dashboard.js')}}?v=3"></script>

@if(isset($devolucionesHoy) && $devolucionesHoy->count() > 0)
    @include('components.modals.modal_devoluciones_hoy', ['devoluciones' => $devolucionesHoy])
@endif

@if(isset($alertasPrecio) && $alertasPrecio->count() > 0)
    @include('components.modals.modal_alertas_precio', ['alertas' => $alertasPrecio])
@endif

@if(isset($topReabastecimiento) && $topReabastecimiento->count() > 0)
    @include('components.modals.modal_top_reabastecimiento', ['alertas' => $topReabastecimiento, 'almacenes' => $almacenes])
@endif

@endsection