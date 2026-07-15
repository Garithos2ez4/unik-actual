@extends('layouts.app')

@section('title', 'Publicaciones Falabella')

@section('content')
@include('falabella.partials.productos-table', [
    'pageTitle'         => 'Publicaciones Falabella Seller Center',
    'clearRoute'        => 'plataformas.falabella.publicaciones',
    'paginationRoute'   => 'plataformas.falabella.publicaciones',
    'entityLabel'       => 'publicaciones',
    'showPublicaciones' => true,
])
@endsection
