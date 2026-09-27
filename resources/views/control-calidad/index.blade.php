@extends('layouts.app')

@section('title', 'Controles de calidad')

@section('content')
<div class="container">
    <h2>Controles de calidad</h2>
    <p class="text-muted">
        Objetos que no cumplen algún control esperado. Estas tablas ignoran el operativo
        actual seleccionado a propósito — un problema de datos es un problema, esté o no
        vinculado a lo que estés mirando en este momento.
    </p>

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
                    <thead><tr><th>Id</th><th>Código</th><th>Nombre</th></tr></thead>
                    <tbody>
                        @foreach ($controles['radios_sin_fraccion'] as $radio)
                            <tr>
                                <td>{{ $radio->id }}</td>
                                <td>{{ $radio->codigo }}</td>
                                <td>{{ $radio->nombre ?? '—' }}</td>
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
</div>
@endsection
