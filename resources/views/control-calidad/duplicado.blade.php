@extends('layouts.app')

@section('title', 'Conciliar código duplicado')

@section('content')
<div class="container">
    <p><a href="{{ route('controles-calidad') }}">&larr; Volver a controles de calidad</a></p>
    <h2>Código duplicado: <code>{{ $codigo }}</code></h2>
    <p class="text-muted">
        {{ $radios->count() }} radios comparten este código.
        Depto {{ substr($codigo, 0, 5) }} / Fracción {{ substr($codigo, 5, 2) }} / Radio {{ substr($codigo, 7, 2) }}.
    </p>

    @if (!$depto)
        <div class="alert alert-warning">
            No existe el departamento <code>{{ substr($codigo, 0, 5) }}</code> en la base — no se puede
            derivar ni crear la fracción a partir de este código.
        </div>
    @elseif (!$fraccionExistente)
        <div class="card mb-3 border-warning">
            <div class="card-header">Falta la fracción {{ substr($codigo, 5, 2) }} de {{ $depto->nombre }}</div>
            <div class="card-body">
                <p>
                    No existe todavía la fracción <code>{{ substr($codigo, 0, 5).substr($codigo, 5, 2) }}</code>
                    del departamento <strong>{{ $depto->nombre }}</strong>. Se puede crear y asignar automáticamente
                    a los radios de este código que tengan <code>fraccion_id</code> nulo.
                </p>
                <form method="POST" action="{{ route('controles-calidad.completar-fraccion', $codigo) }}"
                      onsubmit="return confirm('Esto crea la fracción {{ substr($codigo, 0, 5).substr($codigo, 5, 2) }} y actualiza fraccion_id en los radios sin fracción. ¿Confirmás?');">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">Crear fracción y completar</button>
                </form>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">Diff entre las filas</div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Campo</th>
                        @foreach ($radios as $radio)
                            <th>Radio #{{ $radio->id }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($diff as $campo => $difiere)
                        <tr class="{{ $difiere ? 'table-warning' : '' }}">
                            <td><code>{{ $campo }}</code></td>
                            @foreach ($radios as $radio)
                                <td>{{ $radio->getRawOriginal($campo) ?? '—' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-0">
                Filas resaltadas: campos que difieren entre las copias. Si no hay ninguna resaltada
                (aparte de las que ya se explican por depto/fracción), son duplicados exactos.
            </p>
        </div>
    </div>

    <div class="row">
        @foreach ($radios as $radio)
            @php $info = $infoRelacionada[$radio->id]; @endphp
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        Radio #{{ $radio->id }}
                        @if ($radio->historicoComoReemplazado)
                            <span class="badge badge-secondary">Histórico (reemplazado por #{{ $radio->historicoComoReemplazado->nuevo_id }})</span>
                        @elseif ($radio->historicoComoVigente->isNotEmpty())
                            <span class="badge badge-success">Vigente (reemplazó a {{ $radio->historicoComoVigente->pluck('viejo_id')->implode(', #') }})</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-3">
                            <li><strong>Nombre:</strong> {{ $radio->nombre ?? '—' }}</li>
                            <li><strong>Tipo de radio:</strong> {{ optional($radio->tipo)->nombre ?? '—' }}</li>
                            <li><strong>Fracción:</strong>
                                @if ($radio->fraccion)
                                    {{ $radio->fraccion->codigo }} — {{ optional($radio->fraccion->departamento)->nombre }},
                                    {{ optional(optional($radio->fraccion->departamento)->provincia)->nombre }}
                                @else
                                    <span class="text-danger">sin asignar</span>
                                @endif
                            </li>
                            <li><strong>Observación:</strong> {{ optional($radio->observacion)->texto ?? '—' }}</li>
                            <li><strong>Localidades vinculadas:</strong> {{ $info['localidades'] }}</li>
                            <li><strong>Entidades vinculadas:</strong> {{ $info['entidades'] }}</li>
                            <li>
                                <strong>Esquema {{ $info['esquema'] }}:</strong>
                                listado {{ $info['tiene_listado'] ? 'sí' : 'no' }},
                                segmentación {{ $info['tiene_segmentacion'] ? 'sí' : 'no' }}
                            </li>
                        </ul>

                        @if ($info['tiene_listado'])
                            <div class="border rounded p-2 bg-light">
                                {!! $radio->getSVG() !!}
                            </div>
                        @else
                            <p class="text-muted small mb-0">Sin listado cargado en ningún esquema — no hay gráfico disponible.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($radios->filter(fn ($r) => !$r->historicoComoReemplazado)->count() > 1)
        <div class="card mb-3">
            <div class="card-header">Conciliar: marcar el resto como histórico</div>
            <div class="card-body">
                <p class="small text-muted">
                    Elegí cuál de las filas queda como vigente. Las demás no se borran — quedan
                    registradas en <code>historico_radio</code> apuntando a la vigente, para que
                    cualquier otra tabla que las referencie por id no se rompa.
                </p>
                <form method="POST" action="{{ route('controles-calidad.marcar-historico', $codigo) }}">
                    @csrf
                    @foreach ($radios as $radio)
                        @if (!$radio->historicoComoReemplazado)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vigente_id"
                                       id="vigente_{{ $radio->id }}" value="{{ $radio->id }}"
                                       {{ $loop->first ? 'checked' : '' }}>
                                <label class="form-check-label" for="vigente_{{ $radio->id }}">
                                    Radio #{{ $radio->id }} queda como vigente
                                </label>
                            </div>
                        @endif
                    @endforeach
                    <div class="form-group mt-2">
                        <label for="motivo">Motivo (opcional)</label>
                        <textarea class="form-control" name="motivo" id="motivo" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Conciliar</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
