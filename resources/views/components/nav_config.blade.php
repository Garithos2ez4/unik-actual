<div class="nav_config">
  <ul class="nav nav-tabs">
    <li class="nav-item">
      <a class="nav-link {{$pag == 'web' ? 'bg-sistema-dos text-light active' : 'text-dark'}}"
        href="{{route('configweb')}}">Web</a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{$pag == 'calculos' ? 'bg-sistema-dos text-light active' : 'text-dark'}}"
        href="{{route('configcalculos')}}">C&aacute;lculos</a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{$pag == 'productos' ? 'bg-sistema-dos text-light active' : 'text-dark'}}"
        href="{{route('configproductos')}}">Productos</a>
    </li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle {{$pag == 'especificaciones' ? 'bg-sistema-dos text-light active' : 'text-dark'}}" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Especificaciones</a>
      <ul class="dropdown-menu pb-0 pt-0">
        <li><a class="dropdown-item {{ isset($subDivide) && $subDivide ? ($subDivide == 'GENERAL' ? 'bg-sistema-dos disabled' : '') : '' }}" href="{{route('configespecificacionesgeneral')}}">Generales</a></li>
        <li><a class="dropdown-item {{ isset($subDivide) && $subDivide ? ($subDivide == 'GRUPOS' ? 'bg-sistema-dos disabled' : '') : '' }}" href="{{route('configespecificacionesxgrupo',[encrypt(1)])}}">Categorias</a></li>
      </ul>
    </li>
    <li class="nav-item">
      <a class="nav-link {{$pag == 'inventario' ? 'bg-sistema-dos text-light active' : 'text-dark'}}"
        href="{{route('configinventario')}}">Inventario</a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{$pag == 'pedidosweb' ? 'bg-sistema-dos text-light active' : 'text-dark'}}"
        href="{{route('configpedidosweb')}}">Pedidos Web</a>
    </li>
  </ul>
</div>