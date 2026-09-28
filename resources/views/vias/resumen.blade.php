@extends('layouts.app')
@section('content_main')
<div class="container">
    <h2>Vías de circulación — resumen</h2>
    <p><a href="{{ route('vias') }}">Ver listado completo &rarr;</a></p>

    <div class="alert {{ $alcance['global'] ? 'alert-info' : 'alert-warning' }} d-flex justify-content-between align-items-center">
        <span>{{ $alcance['etiqueta'] }}</span>
        @if (!$alcance['global'])
            <span class="small text-muted">
                vias sólo comparte el nivel provincia de forma confiable — el filtro se acota ahí,
                aunque hayas elegido un departamento/fracción/radio más fino.
            </span>
        @endif
    </div>

    <div class="row text-center mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h3>{{ number_format($total) }}</h3>
                <p class="text-muted mb-0">Vías totales</p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h3>{{ number_format($localidadesDistintas) }}</h3>
                <p class="text-muted mb-0">Localidades distintas (por código)</p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h3>{{ number_format(100 * $sinAlturas / max($total, 1), 1) }}%</h3>
                <p class="text-muted mb-0">Sin rango de alturas cargado<br>({{ number_format($sinAlturas) }} de {{ number_format($total) }})</p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h3>{{ $porProvincia->count() }}</h3>
                <p class="text-muted mb-0">Provincias con datos</p>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <h5>Cantidad por tipo de vía</h5>
            <canvas id="chartTipo" height="260"></canvas>
        </div>
        <div class="col-md-6 mb-4">
            <h5>Cantidad por provincia</h5>
            <canvas id="chartProvincia" height="260"></canvas>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <h5>Top 10 tipos más frecuentes</h5>
            <table class="table table-sm table-bordered">
                <thead><tr><th>#</th><th>Tipo</th><th>Cantidad</th></tr></thead>
                <tbody>
                    @foreach ($top10Tipos as $fila)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><a href="{{ route('vias', ['tipo' => $fila->tipo]) }}">{{ $fila->tipo }}</a></td>
                            <td>{{ number_format($fila->cantidad) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-md-6 mb-4">
            <h5>Valores raros / poco frecuentes <span class="badge badge-warning">{{ $tiposRaros->count() }}</span></h5>
            <p class="small text-muted">
                Tipo con ≤10 apariciones en {{ number_format($total) }} filas — a esa frecuencia suelen ser
                errores de tipeo/import (<code>CALE</code>, <code>CLLE</code>, <code>OTRP</code>, el literal
                <code>TIPO</code> filtrado de un encabezado, códigos numéricos sueltos) más que tipos de vía
                reales infrecuentes. Click en el tipo para ver las filas; "Corregir" registra el cambio
                (queda versionado en <code>via_tipo_correccion</code>) y lo aplica ya mismo — <code>vias</code>
                es una tabla derivada que se reconstruye entera desde el origen, así que si el valor sucio
                vuelve a aparecer más abajo se puede reaplicar de nuevo con un click.
            </p>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Tipo</th><th>Cant.</th><th>Corregir a</th></tr></thead>
                <tbody>
                    @foreach ($tiposRaros as $fila)
                        <tr class="table-warning">
                            <td><a href="{{ route('vias', ['tipo' => $fila->tipo]) }}"><code>{{ $fila->tipo }}</code></a></td>
                            <td>{{ $fila->cantidad }}</td>
                            <td>
                                <form method="POST" action="{{ route('vias.corregir-tipo') }}" class="form-inline">
                                    @csrf
                                    <input type="hidden" name="tipo_original" value="{{ $fila->tipo }}">
                                    <input type="text" name="tipo_corregido" class="form-control form-control-sm mr-1" placeholder="ej: CALLE" required style="width: 100px;">
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Corregir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <h5>
                Correcciones de tipo registradas
                <form method="POST" action="{{ route('vias.reaplicar-correcciones') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Reaplicar todas ahora</button>
                </form>
            </h5>
            @if ($correcciones->isEmpty())
                <p class="text-muted small">Ninguna corrección registrada todavía.</p>
            @else
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Original</th><th>Corregido</th><th>Motivo</th><th>Cuándo</th><th>Estado</th></tr></thead>
                    <tbody>
                        @foreach ($correcciones as $correccion)
                            <tr>
                                <td><code>{{ $correccion->tipo_original }}</code></td>
                                <td>{{ $correccion->tipo_corregido }}</td>
                                <td class="small text-muted">{{ $correccion->motivo ?? '—' }}</td>
                                <td class="small">{{ $correccion->created_at }}</td>
                                <td>
                                    @if ($correccion->revertidos > 0)
                                        <span class="badge badge-danger">Volvió a aparecer: {{ $correccion->revertidos }} fila(s)</span>
                                    @else
                                        <span class="badge badge-success">OK, no reapareció</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <h5>Top 25 nombres más repetidos</h5>
            <p class="small text-muted">
                Mismo nombre de calle en distintas localidades (esperable — "San Martín"/"Belgrano" se
                repiten en casi todos los pueblos). Comparado sin distinguir mayúsculas/minúsculas.
            </p>
            <table class="table table-sm table-bordered">
                <thead><tr><th>#</th><th>Nombre</th><th>Repeticiones</th><th>Localidades</th></tr></thead>
                <tbody>
                    @foreach ($top25NombresRepetidos as $fila)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $fila->nombre }}</td>
                            <td>{{ number_format($fila->cantidad) }}</td>
                            <td>{{ number_format($fila->localidades) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-md-6 mb-4">
            <h5>Top 25 por longitud (metros)</h5>
            <p class="small text-muted">
                <code>ST_Length(geom::geography)</code> — geom está en SRID 4326 (WGS84), sin el cast a
                <code>geography</code> daría grados, no metros. Longitudes extremas (cientos de km) suelen
                ser un <code>ST_Union</code> agrupando muchos segmentos reales bajo el mismo nombre/código,
                no una sola calle real de ese largo — dato a revisar, no necesariamente un error de carga.
            </p>
            <table class="table table-sm table-bordered">
                <thead><tr><th>#</th><th>Nombre</th><th>Tipo</th><th>Metros</th></tr></thead>
                <tbody>
                    @foreach ($top25PorMetros as $fila)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $fila->nombre }}</td>
                            <td>{{ $fila->tipo }}</td>
                            <td>{{ number_format($fila->metros) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <h5>Detalle por provincia</h5>
    <table class="table table-sm table-bordered">
        <thead><tr><th>Provincia</th><th>Vías</th><th>Localidades distintas</th></tr></thead>
        <tbody>
            @foreach ($porProvincia as $fila)
                <tr>
                    <td>({{ $fila->prov_codigo }}) {{ $nombresProvincia[$fila->prov_codigo] ?? '(sin provincia)' }}</td>
                    <td>{{ number_format($fila->cantidad) }}</td>
                    <td>{{ number_format($fila->localidades) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
@section('footer_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('chartTipo'), {
        type: 'bar',
        data: {
            labels: @json($porTipo->pluck('tipo')),
            datasets: [{ label: 'Cantidad', data: @json($porTipo->pluck('cantidad')), backgroundColor: '#4139dd' }],
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } },
        },
    });

    new Chart(document.getElementById('chartProvincia'), {
        type: 'bar',
        data: {
            labels: @json($porProvincia->map(fn ($f) => $nombresProvincia[$f->prov_codigo] ?? $f->prov_codigo)),
            datasets: [{ label: 'Vías', data: @json($porProvincia->pluck('cantidad')), backgroundColor: '#00A' }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });
});
</script>
@endsection
