@extends('layouts.app')
@section('content_main')
 <div class="container">
   <h2>Listado de Vías de circulación <a href="{{ route('vias.resumen') }}" class="btn btn-sm btn-outline-primary float-right">Ver resumen</a></h2>
   <p class="text-muted small">
       159 mil+ filas — la columna Localidad/Aglomerado puede aparecer vacía si esa localidad
       todavía no está cargada en esta base (dataset parcial), no es necesariamente un error.
   </p>
   @if ($tipoFiltro)
       <div class="alert alert-info">
           Filtrando por tipo exacto: <code>{{ $tipoFiltro }}</code>
           <a href="{{ route('vias') }}" class="float-right">Quitar filtro</a>
       </div>
   @endif
    <table class="table table-bordered table-striped table-hover order-column" id="laravel_datatable">
       <thead>
          <tr>
             <th>Tipo</th>
             <th>Nombre</th>
             <th>Código</th>
             <th>Desde (impar)</th>
             <th>Hasta (impar)</th>
             <th>Desde (par)</th>
             <th>Hasta (par)</th>
             <th>Localidad</th>
             <th>Aglomerado</th>
          </tr>
       </thead>
    </table>
 </div>
@endsection
@section('footer_scripts')
 <script>
 document.addEventListener('DOMContentLoaded',  function () {
      var columnas = [
          { data: 'tipo', name: 'tipo' },
          { data: 'nombre', name: 'nombre' },
          { data: 'codigo', name: 'codigo' },
          { data: 'desdei', name: 'desdei' },
          { data: 'hastai', name: 'hastai' },
          { data: 'desded', name: 'desded' },
          { data: 'hastad', name: 'hastad' },
          { searchable: false, data: 'localidad', name: 'localidad' },
          { searchable: false, data: 'aglomerado', name: 'aglomerado' }
      ];

      var tipoFiltro = @json($tipoFiltro);
      iniciarDataTable('#laravel_datatable', '{{ route("vias-list") }}', columnas, {
          pageLength: 25,
          ajax: {
              url: '{{ route("vias-list") }}',
              type: 'POST',
              data: function (d) { if (tipoFiltro) { d.tipo = tipoFiltro; } },
          },
      });
} );
</script>
@endsection
