@extends('layouts.app')
@section('content_main')
 <div class="container">
   <h2>Listado de Vías de circulación</h2>
   <p class="text-muted small">
       159 mil+ filas — la columna Localidad/Aglomerado puede aparecer vacía si esa localidad
       todavía no está cargada en esta base (dataset parcial), no es necesariamente un error.
   </p>
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

      iniciarDataTable('#laravel_datatable', '{{ route("vias-list") }}', columnas, { pageLength: 25 });
} );
</script>
@endsection
