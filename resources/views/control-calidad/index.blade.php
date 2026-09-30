@extends('layouts.app')

@section('title', 'Controles de calidad')

@section('content')
<div class="container">
    <h2>Controles de calidad</h2>
    <p class="text-muted">
        Objetos que no cumplen algún control esperado. Estas tablas ignoran el operativo
        actual seleccionado a propósito — un problema de datos es un problema, esté o no
        vinculado a lo que estés mirando en este momento. El alcance <strong>geográfico</strong>
        (provincia/objeto elegido arriba, o tu restricción fija) sí aplica.
    </p>

    <div class="alert {{ $alcance['global'] ? 'alert-info' : 'alert-warning' }} d-flex justify-content-between align-items-center">
        <span>{{ $alcance['etiqueta'] }}</span>
        @if (!$alcance['global'])
            <span class="small text-muted">Cambiá el alcance desde el menú de arriba (Alcance: ...).</span>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Radios sin fracción asignada
            <span class="badge badge-pill {{ $controles['radios_sin_fraccion']->count() ? 'badge-danger' : 'badge-success' }} float-right">
                {{ $controles['radios_sin_fraccion']->count() }}
            </span>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                <code>radio.fraccion_id</code> nulo — sin fracción no hay forma de ubicar el radio en
                ninguna provincia/departamento (ver <code>EscopadoPorOperativoHeredado</code>).
            </p>
            @if ($controles['radios_sin_fraccion']->isEmpty())
                <p class="text-success mb-0">Sin problemas.</p>
            @else
                <table class="table table-sm">
                    <thead><tr><th>Id</th><th>Código</th><th>Nombre</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($controles['radios_sin_fraccion'] as $radio)
                            <tr>
                                <td>{{ $radio->id }}</td>
                                <td>{{ $radio->codigo }}</td>
                                <td>{{ $radio->nombre ?? '—' }}</td>
                                <td>
                                    @if (!$radio->localidades_count && !$radio->entidades_count && !$radio->historico_como_vigente_count && !$radio->historico_como_reemplazado_count)
                                        <form method="POST" action="{{ route('controles-calidad.eliminar-radio', [$radio->codigo, $radio->id]) }}"
                                              onsubmit="return confirm('Eliminar el radio #{{ $radio->id }} ({{ $radio->codigo }})? No tiene fracción, localidades, entidades ni histórico.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Radios con código duplicado
            <span class="badge badge-pill {{ $controles['radios_codigo_duplicado']->count() ? 'badge-danger' : 'badge-success' }} float-right">
                {{ $controles['radios_codigo_duplicado']->count() }}
            </span>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Dos o más radios con el mismo código de 15 dígitos — el código es la identidad
                territorial del radio, no debería repetirse nunca.
            </p>
            @if ($controles['radios_codigo_duplicado']->isEmpty())
                <p class="text-success mb-0">Sin problemas.</p>
            @else
                <table class="table table-sm">
                    <thead><tr><th>Id</th><th>Código</th><th>Nombre</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($controles['radios_codigo_duplicado'] as $radio)
                            <tr>
                                <td>{{ $radio->id }}</td>
                                <td>{{ $radio->codigo }}</td>
                                <td>{{ $radio->nombre ?? '—' }}</td>
                                <td><a href="{{ route('controles-calidad.duplicado', $radio->codigo) }}" class="btn btn-sm btn-outline-primary">Conciliar</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Localidades sin departamento vinculado
            <span class="badge badge-pill {{ $controles['localidades_sin_departamento']->count() ? 'badge-danger' : 'badge-success' }} float-right">
                {{ $controles['localidades_sin_departamento']->count() }}
            </span>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Ninguna fila en <code>localidad_departamento</code> — sin vínculo, el listado de
                Departamentos cuenta 0 localidades ahí aunque existan (ver §2.13). Entrá al detalle del
                departamento correspondiente (<code>/depto/{id}</code>) para reparar el vínculo.
            </p>
            @if ($controles['localidades_sin_departamento']->isEmpty())
                <p class="text-success mb-0">Sin problemas.</p>
            @else
                <table class="table table-sm">
                    <thead><tr><th>Id</th><th>Código</th><th>Nombre</th></tr></thead>
                    <tbody>
                        @foreach ($controles['localidades_sin_departamento']->take(30) as $localidad)
                            <tr>
                                <td>{{ $localidad->id }}</td>
                                <td>{{ $localidad->codigo }}</td>
                                <td>{{ $localidad->nombre ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($controles['localidades_sin_departamento']->count() > 30)
                    <p class="small text-muted mb-0">... y {{ $controles['localidades_sin_departamento']->count() - 30 }} más.</p>
                @endif
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Correcciones de Vías revertidas
            <span class="badge badge-pill {{ $controles['vias_correccion_revertida']->count() ? 'badge-danger' : 'badge-success' }} float-right">
                {{ $controles['vias_correccion_revertida']->count() }}
            </span>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                <code>vias</code> se reconstruye entera desde el origen (ver §2.14) — si un tipo ya
                corregido antes vuelve a aparecer, es que se recargó cartografía sin reaplicar el
                diccionario después.
            </p>
            @if ($controles['vias_correccion_revertida']->isEmpty())
                <p class="text-success mb-0">Sin problemas.</p>
            @else
                <table class="table table-sm">
                    <thead><tr><th>Original</th><th>Corregido</th><th>Filas revertidas</th></tr></thead>
                    <tbody>
                        @foreach ($controles['vias_correccion_revertida'] as $correccion)
                            <tr>
                                <td><code>{{ $correccion->tipo_original }}</code></td>
                                <td>{{ $correccion->tipo_corregido }}</td>
                                <td>{{ $correccion->revertidos }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <a href="{{ route('vias.resumen') }}" class="btn btn-sm btn-outline-primary">Ir a reaplicar</a>
            @endif
        </div>
    </div>

    @can('Administrar Vias')
        <div class="card mb-3">
            <div class="card-header">Actualizar vías desde un esquema</div>
            <div class="card-body">
                <p class="small text-muted">
                    Revisar caso por caso qué cambió en <code>&lt;esquema&gt;.arc</code> respecto a
                    <code>public.vias</code> (altas/bajas/modificaciones) y elegir cuáles aplicar — en
                    vez de reconstruir toda la tabla desde cero con <code>MyDB::juntaVias()</code>.
                </p>
                <a href="{{ route('controles-calidad.vias.index') }}" class="btn btn-sm btn-outline-primary">
                    Selector de esquemas + historial
                </a>
            </div>
        </div>
    @endcan
</div>
@endsection
