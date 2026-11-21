<div class="buscador_producto">
    <div class="row">
        <form action="{{route('buscarproducto')}}" method="GET">
        <div class="input-group input-group-sm mb-3">
          <span class="input-group-text" id="inputGroup-sizing-sm"><button class="btn btn-sm pb-0 mb-0" type="submit"><i class="bx bx-search-alt bx-sm" ></i></button></span>
          <input type="text" name="search" class="form-control form-control-sm" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm" placeholder="ID, Marca, Modelo o PartNumber..." value="{{ request('search') }}">
        </div>
        </form>
    </div>
</div>