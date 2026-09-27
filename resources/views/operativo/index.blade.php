@extends('layouts.app')

@section('title', 'Operativos')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            Operativos
            <a href="{{ route('operativos.create') }}" class="badge badge-pill badge-success float-right">+ Nuevo operativo</a>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
            @endif

            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Observación</th>
                        <th>Topología</th>
                        <th>Provincias</th>
                        <th>Localidades</th>
                        <th>Entidades</th>
                        <th>Gob. Locales</th>
                        <th>Parajes</th>
                        <th>Bases Antárticas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($operativos as $operativo)
                        <tr>
                            <td><a href="{{ route('operativos.show', $operativo) }}">{{ $operativo->nombre }}</a></td>
                            <td>{{ $operativo->observacion }}</td>
                            <td>{{ optional($operativo->topologia)->name ?? '—' }}</td>
                            <td>{{ $operativo->provincias_count }}</td>
                            <td>{{ $operativo->localidades_count }}</td>
                            <td>{{ $operativo->entidades_count }}</td>
                            <td>{{ $operativo->gobiernos_locales_count }}</td>
                            <td>{{ $operativo->parajes_count }}</td>
                            <td>{{ $operativo->base_antarticas_count }}</td>
                            <td class="text-right">
                                <a href="{{ route('operativos.edit', $operativo) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form action="{{ route('operativos.destroy', $operativo) }}" method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar {{ $operativo->nombre }}? Se desvinculan sus relaciones primero.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted">Sin operativos cargados todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
