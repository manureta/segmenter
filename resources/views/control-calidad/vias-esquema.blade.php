@extends('layouts.app')

@section('title', 'Vías — '.$esquema)

@section('content')
<div class="container">
    <h2>Actualizar vías desde <code>{{ $esquema }}</code></h2>
    <p class="text-muted">
        Compara <code>public.vias</code> (la tabla compartida, usada por todo el sistema) contra la
        cartografía actual de este esquema. No se toca nada hasta que elijas qué casos aplicar abajo.
    </p>

    <a href="{{ route('controles-calidad') }}" class="btn btn-sm btn-outline-secondary mb-3">&larr; Controles de calidad</a>

    @if ($diff['status'] === 'error')
        <div class="alert alert-danger">{{ $diff['mensaje'] }}</div>
    @elseif ($diff['status'] === 'sin_vias')
        <div class="alert alert-info">{{ $diff['mensaje'] }}</div>
    @elseif (empty($diff['casos']))
        <div class="alert alert-success">{{ $diff['mensaje'] }}</div>
    @else
        <form method="POST" action="{{ route('controles-calidad.vias.aplicar', $esquema) }}"
              onsubmit="return confirm('Aplicar los casos tildados? Esto modifica public.vias, compartida por todo el sistema.');">
            @csrf
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">{{ $diff['mensaje'] }}</span>
                <div>
                    <button type="button" class="btn btn-sm btn-link" onclick="marcarTodos(true)">Tildar todos</button>
                    <button type="button" class="btn btn-sm btn-link" onclick="marcarTodos(false)">Destildar todos</button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="marcarCompletados()">Tildar solo "completados"</button>
                </div>
            </div>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th></th>
                        <th>Acción</th>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Antes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($diff['casos'] as $caso)
                        <tr>
                            <td>
                                <input type="checkbox" name="links[]" value="{{ $caso['link'] }}"
                                       class="caso-checkbox {{ $caso['completado'] ? 'caso-completado' : '' }}">
                            </td>
                            <td>
                                @if ($caso['accion'] === 'nueva')
                                    <span class="badge badge-success">nueva</span>
                                @elseif ($caso['accion'] === 'eliminada')
                                    <span class="badge badge-danger">eliminada</span>
                                @elseif ($caso['completado'])
                                    <span class="badge badge-info">completado</span>
                                @else
                                    <span class="badge badge-warning">modificada</span>
                                @endif
                            </td>
                            <td>{{ $caso['tipo'] }}</td>
                            <td>{{ $caso['nombre'] }}</td>
                            <td class="text-muted small">
                                @if ($caso['accion'] === 'modificada')
                                    {{ $caso['tipo_anterior'] }} / {{ $caso['nombre_anterior'] }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="form-group">
                <label for="motivo">Motivo / origen del cambio <span class="text-danger">*</span></label>
                <textarea name="motivo" id="motivo" class="form-control" rows="2" required
                          placeholder="ej. re-carga de cartografía del operativo X, corrección de nombre de calle, etc."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Aplicar casos tildados</button>
        </form>
        <script>
            function marcarTodos(valor) {
                document.querySelectorAll('.caso-checkbox').forEach(function (c) { c.checked = valor; });
            }
            function marcarCompletados() {
                marcarTodos(false);
                document.querySelectorAll('.caso-completado').forEach(function (c) { c.checked = true; });
            }
        </script>
    @endif

    @if (isset($historialEsquema) && $historialEsquema->isNotEmpty())
        <h5 class="mt-4">Historial de este esquema</h5>
        <table class="table table-sm">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Agregados</th><th>Borrados</th><th>Motivo / origen</th></tr></thead>
            <tbody>
                @foreach ($historialEsquema as $h)
                    <tr>
                        <td class="small text-muted">{{ $h->created_at }}</td>
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
@endsection
