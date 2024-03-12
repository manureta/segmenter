@extends('layouts.app')

@section('content_main')
    <!-- Modal -->
    <div class="modal fade" id="empModal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Info de Entidad</h4>
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
      <div class="row">
        <div class="col-lg-12">
          <a href="{{ url('/entidades/cargar') }}">Ir a Cargar Entidades</a>
        </div>
      </div>
        <h4>Listado de Entidades</h4>
        <div class="row">
            <div class="col-lg-12">
                <table
                    class="table table-sm table-striped table-bordered dataTable table-hover order-column table-condensed compact"
                    id="laravel_datatable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('footer_scripts')
    <script>
        $(document).ready(function() {
                    var propagacion = false;
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    var table = $('#laravel_datatable').DataTable({
                        "pageLength": -1,
                        language: //{url:'https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json'},
                        {
                            "sProcessing": "Procesando...",
                            "sLengthMenu": "Mostrar _MENU_ registros",
                            "sZeroRecords": "No se encontraron resultados",
                            "sEmptyTable": "Ningún dato disponible en esta tabla =(",
                            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                            "sInfoPostFix": "",
                            "sSearch": "Buscar:",
                            "sUrl": "",
                            "sInfoThousands": ",",
                            "sLoadingRecords": "Cargando...",
                            "oPaginate": {
                                "sFirst": "Primero",
                                "sLast": "Último",
                                "sNext": "Siguiente",
                                "sPrevious": "Anterior"
                            },
                            "oAria": {
                                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                            },
                            "buttons": {
                                "copy": "Copiar",
                                "colvis": "Visibilidad"
                            }
                        },
                        processing: true,
                        serverSide: false,
                        ajax: {
                            url: "{{ url('ents-list') }}",
                            type: 'GET',
                            data: function(d) {
                                d.codigo = $('#codigo').val();
                            }
                        },
                        columns: [{
                                visible: false,
                                data: 'id',
                                name: 'id'
                            },
                            {
                                data: 'codigo',
                                name: 'codigo'
                            },
                            {
                                data: 'nombre',
                                name: 'nombre'
                            },
                            {
                                searchable: false,
                                data: 'action',
                                name: 'action'
                            },
                        ]
                    });

                    table.on('click', 'tr', function() {
                        var data = table.row(this).data();
                        if ((data != null) && (propagacion == false)) {
                            // AJAX request
                            $.ajax({
                                url: "{{ url('ent') }}" + "/" + data.id,
                                type: 'post',
                                data: {
                                    id: data.id,
                                    format: 'html'
                                },
                                success: function(response) {
                                    // Add response in Modal body
                                    $('.modal-body').html(response);

                                    // Display Modal
                                    $('#empModal').modal('show');
                                }
                            });
                            console.log('You clicked on ' + data.id + '\'s row');
                        }
                    });

                    // Función de botón Ver 2.
                    table.on('click', '.btn_prov', function() {
                        var row = $(this).closest('tr');
                        var data = table.row(row).data();
                        console.log('Ver Entidad: ' + data.codigo);
                        if (typeof data !== 'undefined') {
                            url = "{{ url('ent') }}" + "/" + data.id;
                            $(location).attr('href', url);
                        };
                    });

                    $('#btnFiterSubmitSearch').click(function() {
                        $('#laravel_datatable').DataTable().draw(true);
                    });

                    // Función de botón Borrar.
                    table.on('click', '.btn_ent_delete', function() {
                        propagacion = true;
                        var $ele = $(this).parent().parent();
                        var row = $(this).closest('tr');
                        var data = table.row(row).data();
                        if ((typeof data !== 'undefined') &&
                            (confirm('El elemento “' + data.codigo +
                                    '” va a ser borrado de la tabla entidades, ¿es correcto? \n'+
                                    'Selecccionar el motivo por el cual se borra el elemento( '+
                                    'en este caso “Error de Ingreso”)' +
                                    ''))) {
                                    $.ajax({
                                        url: "{{ url('entidad') }}" + "\\" + data.id,
                                        type: "DELETE",
                                        data: {
                                            id: data.id,
                                            _token: '{{ csrf_token() }}'
                                        },
                                        success: function(response) {
                                            // Add response in Modal body
                                            if (response.statusCode == 200) {
                                                row.fadeOut().remove();
                                                alert("Se eliminó el registro de la entidad");
                                                $('.modal-body').html(response.message);
                                            } else if (response.statusCode == 405) {
                                                alert("Error al intentar borrar");
                                            } else if (response.statusCode == 500) {
                                                alert("Error al intentar borrar. En el servidor");
                                            } else {
                                                alert("Mensaje del Servidor: " + response.message);
                                            }
                                            console.log(response);
                                            propagacion = false;
                                        }
                                    });
                                };
                            });

                    });
    </script>
@endsection
