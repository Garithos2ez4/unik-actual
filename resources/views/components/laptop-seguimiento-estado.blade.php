@props([
    'estado' => 'NORMAL',
    'marca' => '',
])

@php
    $estadosConfig = config('laptop_seguimiento.estados', []);
    $cfg = $estadosConfig[$estado] ?? $estadosConfig['NORMAL'] ?? [
        'label' => $estado,
        'short' => $estado,
        'bg'    => '#F8F9FA',
        'text'  => '#212529',
        'badge' => 'secondary',
    ];

    $style = match ($estado) {
        'CON_STOCK'     => 'background-color: #66FF33; color: #000000; border: 1px solid #28a745;',
        'AGOTADO'       => 'background-color: #FF0000; color: #FFFFFF; border: 1px solid #b02a37;',
        'EN_TRANSITO'   => 'background-color: #FFC000; color: #000000; border: 1px solid #d39e00;',
        'DESHABILITADA' => 'background-color: #7030A0; color: #FFFFFF; border: 1px solid #4d1f70;',
        'PAUSADA'       => 'background-color: #495057; color: #FFFFFF; border: 1px solid #343a40;',
        default         => strtoupper($marca) === 'ACER'
            ? 'background-color: #BFBFBF; color: #000000; border: 1px solid #999999;'
            : (strtoupper($marca) === 'ASUS'
                ? 'background-color: #F6C6AD; color: #000000; border: 1px solid #e0a587;'
                : 'background-color: #E9ECEF; color: #212529; border: 1px solid #ced4da;'),
    };
@endphp

<span {{ ($attributes ?? new \Illuminate\View\ComponentAttributeBag)->merge(['class' => 'badge rounded-pill px-2 py-1 fw-bold', 'style' => $style]) }} title="{{ $cfg['label'] }}">
    {{ (isset($slot) && !$slot->isEmpty()) ? $slot : $cfg['short'] }}
</span>

