/**
 * Boilerplate compartido de inicialización de DataTables (idioma español,
 * CSRF, processing/serverSide) que estaba duplicado línea por línea en cada
 * vista con listado (locas.blade.php, deptos.blade.php, etc). Nuevas
 * pantallas con listado deberían usar esto en vez de copiar el bloque
 * entero de nuevo.
 */
window.iniciarDataTable = function (selector, ajaxUrl, columns, opciones) {
    opciones = opciones || {};

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });

    return $(selector).DataTable(Object.assign({
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
            sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
            sInfoPostFix: '',
            sSearch: 'Buscar:',
            sUrl: '',
            sInfoThousands: ',',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior',
            },
            oAria: {
                sSortAscending: ': activar para ordenar ascendente',
                sSortDescending: ': activar para ordenar descendente',
            },
        },
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxUrl,
            type: 'POST',
        },
        columns: columns,
    }, opciones));
};
