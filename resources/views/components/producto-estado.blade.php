@props([
    'estado' => null,
    'producto' => null,
    'asBadge' => false,
])

@php
    $estadoValor = $estado ?? ($producto->estadoProductoWeb ?? 'DISPONIBLE');

    $colorClass = match ($estadoValor) {
        'DISPONIBLE' => 'text-success',
        'OFERTA'     => 'text-danger',
        default      => 'text-danger text-decoration-line-through',
    };

    $badgeClass = match ($estadoValor) {
        'DISPONIBLE'    => 'bg-success',
        'OFERTA'        => 'bg-danger',
        'LIQUIDACION'   => 'bg-warning text-dark',
        'EXCLUSIVO'     => 'bg-primary',
        'AGOTADO'       => 'bg-secondary',
        'DESCONTINUADO' => 'bg-dark',
        default         => 'bg-secondary',
    };
@endphp

@if($asBadge)
    <span {{ ($attributes ?? new \Illuminate\View\ComponentAttributeBag)->merge(['class' => "badge rounded-pill {$badgeClass}"]) }}>
        {{ (isset($slot) && !$slot->isEmpty()) ? $slot : $estadoValor }}
    </span>
@else
    <span {{ ($attributes ?? new \Illuminate\View\ComponentAttributeBag)->merge(['class' => "fw-bold {$colorClass}"]) }}>
        {{ (isset($slot) && !$slot->isEmpty()) ? $slot : $estadoValor }}
    </span>
@endif
