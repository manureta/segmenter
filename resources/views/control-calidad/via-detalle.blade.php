@extends('layouts.app')

@section('title', 'Vía — '.$detalle['nombre'])

@section('content')
<div class="container">
    <h2>{{ $detalle['tipo'] }} {{ $detalle['nombre'] }}</h2>
    <p class="text-muted small">
        Esquema <code>{{ $esquema }}</code>
        @if ($detalle['tramos_nuevo'] > 1 || $detalle['tramos_actual'] > 1)
            — unificado desde {{ max($detalle['tramos_actual'], $detalle['tramos_nuevo']) }} tramos con este mismo tipo+nombre
            @if (max($detalle['tramos_actual'], $detalle['tramos_nuevo']) > 3)
                <span class="text-warning">(nombre repetido — confirmá en el mapa que sean la misma vía y no varias sin nombre)</span>
            @endif
        @endif
    </p>

    <a href="{{ route('controles-calidad.vias', $esquema) }}" class="btn btn-sm btn-outline-secondary mb-3">&larr; Volver al diff</a>

    <div class="mb-2 small">
        Fondo:
        <a href="?link={{ urlencode($detalle['link']) }}&fondo=esri" class="{{ $fondo === 'esri' ? 'font-weight-bold' : '' }}">satelital (ESRI)</a>
        ·
        <a href="?link={{ urlencode($detalle['link']) }}&fondo=osm" class="{{ $fondo === 'osm' ? 'font-weight-bold' : '' }}">calles (OSM)</a>
        ·
        <a href="?link={{ urlencode($detalle['link']) }}&fondo=ninguno" class="{{ $fondo === 'ninguno' ? 'font-weight-bold' : '' }}">sin fondo</a>
        ·
        <label class="mb-0">
            <input type="checkbox" id="toggle-fondo" checked onchange="document.querySelectorAll('.svg-fondo').forEach(i=>i.style.display=this.checked?'':'none')">
            mostrar imagen de fondo
        </label>
        <span class="text-muted">— en gris fino, las demás calles del área (solo contexto)</span>
    </div>

    <div class="mb-3">{!! $detalle['svg'] ?? '<p class="text-muted">Sin geometría para mostrar.</p>' !!}</div>

    <div class="row">
        <div class="col-md-6">
            <h5>Actual (en <code>vias</code>)</h5>
            @if ($detalle['actual'])
                <table class="table table-sm">
                    <tr><th>Tramos</th><td>{{ $detalle['tramos_actual'] }}</td></tr>
                    <tr><th>Código</th><td>{{ $detalle['actual']->codigo }}</td></tr>
                    <tr><th>Numeración</th><td>{{ $detalle['actual']->desdei }}–{{ $detalle['actual']->hastai }} / {{ $detalle['actual']->desded }}–{{ $detalle['actual']->hastad }}</td></tr>
                    <tr><th>Largo total</th><td>{{ $detalle['actual']->longitud_m }} m</td></tr>
                </table>
            @else
                <p class="text-muted small">No está en <code>vias</code> todavía (es una vía nueva, sin aplicar).</p>
            @endif
        </div>
        <div class="col-md-6">
            <h5>Nuevo (en la cartografía del esquema)</h5>
            @if ($detalle['nuevo'])
                <table class="table table-sm">
                    <tr><th>Tramos</th><td>{{ $detalle['tramos_nuevo'] }}</td></tr>
                    <tr><th>Código</th><td>{{ $detalle['nuevo']->codigo }}</td></tr>
                    <tr><th>Numeración</th><td>{{ $detalle['nuevo']->desdei }}–{{ $detalle['nuevo']->hastai }} / {{ $detalle['nuevo']->desded }}–{{ $detalle['nuevo']->hastad }}</td></tr>
                    <tr><th>Largo total</th><td>{{ $detalle['nuevo']->longitud_m }} m</td></tr>
                </table>
            @else
                <p class="text-muted small">Ya no está en la cartografía actual del esquema (sería una baja).</p>
            @endif
        </div>
    </div>

    <h5 class="mt-3">Historial de esta vía</h5>
    @if ($detalle['historial']->isEmpty())
        <p class="text-muted small">Todavía no se aplicó ningún cambio sobre esta vía puntual.</p>
    @else
        <table class="table table-sm">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Motivo / origen</th></tr></thead>
            <tbody>
                @foreach ($detalle['historial'] as $h)
                    <tr>
                        <td class="small text-muted">{{ $h->created_at }}</td>
                        <td>{{ $h->usuario->name ?? '—' }}</td>
                        <td>{{ $h->motivo }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
