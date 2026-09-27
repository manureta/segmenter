@extends('layouts.app')

@section('title', 'Operativo: '.$operativo->nombre)

@section('content')
<div class="container">
    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            {{ $operativo->nombre }}
            <span class="badge badge-pill {{ $operativo->es_publico ? 'badge-success' : 'badge-secondary' }}">
                {{ $operativo->es_publico ? 'Público' : 'Privado' }}
            </span>
            @if ($puedeEditar)
                <a href="{{ route('operativos.edit', $operativo) }}" class="badge badge-pill badge-secondary float-right ml-1">Editar</a>
            @endif
            @if (!session('operativo_actual_id') || session('operativo_actual_id') != $operativo->id)
                <form action="{{ route('operativos.seleccionar', $operativo) }}" method="POST" class="d-inline float-right">
                    @csrf
                    <button type="submit" class="badge badge-pill badge-primary" style="border:none;">Usar como operativo actual</button>
                </form>
            @else
                <span class="badge badge-pill badge-primary float-right">Es el operativo actual</span>
            @endif
        </div>
        <div class="card-body">
            <p><strong>Dueño:</strong> {{ optional($operativo->usuario)->name ?? optional($operativo->usuario)->email ?? 'sin dueño (preexistente)' }}</p>
            <p><strong>Observación:</strong> {{ $operativo->observacion ?? '—' }}</p>
            <p><strong>Topología asociada:</strong> {{ optional($operativo->topologia)->name ?? 'sin asociar' }}</p>
            <p class="mb-0">
                <strong>Vínculos:</strong>
                {{ $operativo->provincias_count }} provincias ·
                {{ $operativo->localidades_count }} localidades ·
                {{ $operativo->entidades_count }} entidades ·
                {{ $operativo->gobiernos_locales_count }} gob. locales ·
                {{ $operativo->parajes_count }} parajes ·
                {{ $operativo->base_antarticas_count }} bases antárticas
            </p>
            @if ($puedePublicar)
                <form action="{{ route($operativo->es_publico ? 'operativos.despublicar' : 'operativos.publicar', $operativo) }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $operativo->es_publico ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                        {{ $operativo->es_publico ? 'Volver a privado' : 'Publicar (visible para todos)' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">Provincias vinculadas</div>
        <div class="card-body">
            <table class="table table-sm">
                <tbody>
                    @forelse ($provinciasVinculadas as $provincia)
                        <tr>
                            <td>{{ $provincia->nombre }}</td>
                            <td class="text-right">
                                <form action="{{ route('operativos.provincias.detach', [$operativo, $provincia]) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Quitar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">Sin provincias vinculadas todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if ($provinciasDisponibles->count() > 0)
                <form action="{{ route('operativos.provincias.attach', $operativo) }}" method="POST" class="form-inline">
                    @csrf
                    <select name="provincia_id" class="form-control mr-2" required>
                        <option value="">Agregar provincia...</option>
                        @foreach ($provinciasDisponibles as $provincia)
                            <option value="{{ $provincia->id }}">{{ $provincia->nombre }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Vincular</button>
                </form>
            @endif

            <small class="form-text text-muted mt-2">
                Localidades/entidades/gobiernos locales/parajes/bases antárticas se administran igual por código
                (mismo patrón belongsToMany) - no tienen UI todavía, esto cubre lo pedido para Operativo.
            </small>
        </div>
    </div>

    <a href="{{ route('operativos.index') }}" class="btn btn-link mt-3">← Volver al listado</a>
</div>
@endsection
