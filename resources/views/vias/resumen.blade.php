@extends('layouts.app')
@section('content_main')
<div class="container">
    <h2>Vías de circulación — resumen</h2>
    <p><a href="{{ route('vias') }}">Ver listado completo &rarr;</a></p>

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
            <h5>Top 25 tipos más frecuentes</h5>
            <table class="table table-sm table-bordered">
                <thead><tr><th>#</th><th>Tipo</th><th>Cantidad</th></tr></thead>
                <tbody>
                    @foreach ($top25Tipos as $fila)
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
