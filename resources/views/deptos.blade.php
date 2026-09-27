@extends('layouts.app')
@section ('content_main')
   <!-- Modal -->
   <div class="modal fade" id="empModal" role="dialog">
    <div class="modal-dialog">

     <!-- Modal content-->
     <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Info de Departamento</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">

      </div>
      <div class="modal-footer">
       <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
     </div>
    </div>
   </div>

 <div class="container">
   <h2>Listado de Departamentos {{ $provincia ? 'de '.$provincia->nombre : '(todas las provincias)' }}</h2>
    <table class="table table-bordered table-striped table-hover order-column" id="laravel_datatable">
       <thead>
          <tr>
             <th>Id</th>
             <th>Código</th>
             <th>Nombre</th>
             @if (!$provincia)
             <th>Provincia</th>
             @endif
             <th>Localidades</th>
             <th>Fracciones</th>
             <th>Radios</th>
             <th>Radios Segmentados</th>
          </tr>
       </thead>
    </table>
 </div>
@endsection
@section('footer_scripts')
 <script>
 document.addEventListener('DOMContentLoaded',  function () {
      var columnas = [
          { visible: false, data: 'id', name: 'id' },
          { data: 'codigo', name: 'codigo' },
          { data: 'nombre', name: 'nombre' },
          @if (!$provincia)
          { data: 'provincia', name: 'provincia' },
          @endif
          { searchable: false, data: 'localidades_count', name: 'localidades_count' },
          { searchable: false, data: 'fracciones_count', name: 'fracciones_count' },
          { searchable: false, data: 'radios_count', name: 'radios_count' },
          { searchable: false, data: 'segmentados', name: 'segmentados' }
      ];

      var table = iniciarDataTable(
          '#laravel_datatable',
          @if ($provincia)
              '{{ url("prov/list/{$provincia->id}") }}',
          @else
              '{{ route("deptos-list") }}',
          @endif
          columnas,
          { pageLength: -1 }
      );

   table.on( 'click', 'tr', function () {
        var data = table.row( this ).data();
        if (typeof data === 'undefined') { return; }
        $.ajax({
            url: "{{ url('depto') }}/"+data.id,
            type: 'post',
            data: {id: data.id, format: 'html'},
            success: function(response){
              $('.modal-body').html(response);
              $('#empModal').modal('show');
            }
        });
   });

} );

</script>
@endsection
