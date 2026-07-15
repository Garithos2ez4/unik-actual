@extends('layouts.app')

@section('title', 'Productos Falabella')

@section('content')
@include('falabella.partials.productos-table', [
    'pageTitle'        => 'Catálogo Falabella Seller Center',
    'clearRoute'       => 'plataformas.falabella.productos',
    'paginationRoute'  => 'plataformas.falabella.productos',
    'entityLabel'      => 'productos',
    'showPublicaciones' => false,
])
@endsection
