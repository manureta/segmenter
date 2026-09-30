@extends('layouts.app')

@section('title', 'Vías — esquemas con diferencias')

@section('content')
<div class="container">
    <h2>Vías — esquemas con diferencias</h2>
    <p class="text-muted">
        Compara cada esquema con topología cargada contra <code>public.vias</code>. Puede tardar —
        recorre todos los esquemas. Solo visible para usuarios con permiso "Administrar Vias".
    </p>

    <a href="{{ route('controles-calidad') }}" class="btn btn-sm btn-outline-secondary mb-3">&larr; Controles de calidad</a>

    <div class="card mb-3">
        <div class="card-header">
            Esquemas con diferencias pendientes
            <span class="badge badge-pill {{ count($pendientes) ? 'badge-danger' : 'badge-success' }} float-right">
                {{ count($pendientes) }}
            </span>
        </div>
        <div class="card-body">
            @if (empty($pendientes))
                <p class="text-success mb-0">Ningún esquema tiene diferencias contra vias.</p>
            @else
                <table class="table table-sm">
                    <thead><tr><th>Esquema</th><th>Agregar</th><th>Borrar</th><th>Última actualización</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($pendientes as $p)
                            <tr>
                                <td><code>{{ $p->esquema }}</code></td>
                                <td>{{ $p->agregar }}</td>
                                <td>{{ $p->borrar }}</td>
                                <td class="small text-muted">
                                    {{ $ultimaPorEsquema[$p->esquema] ?? 'nunca' }}
                                </td>
                                <td><a href="{{ route('controles-calidad.vias', $p->esquema) }}" class="btn btn-sm btn-outline-primary">Revisar</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Historial de actualizaciones (últimas 50)</div>
        <div class="card-body">
            @if ($historial->isEmpty())
                <p class="text-muted mb-0">Todavía no se aplicó ninguna actualización de vías.</p>
            @else
                <table class="table table-sm">
                    <thead><tr><th>Fecha</th><th>Esquema</th><th>Usuario</th><th>Agregados</th><th>Borrados</th><th>Motivo / origen</th></tr></thead>
                    <tbody>
                        @foreach ($historial as $h)
                            <tr>
                                <td class="small text-muted">{{ $h->created_at }}</td>
                                <td><a href="{{ route('controles-calidad.vias', $h->esquema) }}"><code>{{ $h->esquema }}</code></a></td>
                                <td>{{ $h->usuario->name ?? '—' }}</td>
                                <td>{{ $h->agregados }}</td>
                                <td>{{ $h->borrados }}</td>
                                <td>{{ $h->motivo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
