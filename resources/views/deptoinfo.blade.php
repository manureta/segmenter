<div class="container">
    @if ($provincia = $departamento->provincia)
    <a class="btn btn-outline-secondary btn-sm mb-2" href="{{ url("/prov/{$provincia->id}") }}">
        ({{ $provincia->codigo }}) <b>{{ $provincia->nombre }}</b>
    </a>
    @endif

    <h3>({{ $departamento->codigo }}) {{ $departamento->nombre }}</h3>
    <p class="text-muted">{{ $departamento->denominacion }}</p>

    <div class="row text-center mb-3">
        <div class="col"><div class="card"><div class="card-body p-2">
            <h4 class="mb-0">{{ $departamento->localidades_count }}</h4>
            <small class="text-muted">Localidades (vinculadas)</small>
        </div></div></div>
        <div class="col"><div class="card"><div class="card-body p-2">
            <h4 class="mb-0">{{ $departamento->fracciones_count }}</h4>
            <small class="text-muted">Fracciones</small>
        </div></div></div>
        <div class="col"><div class="card"><div class="card-body p-2">
            <h4 class="mb-0">{{ $departamento->radios_count }}</h4>
            <small class="text-muted">Radios</small>
        </div></div></div>
        <div class="col"><div class="card"><div class="card-body p-2">
            <h4 class="mb-0">{{ $departamento->segmentados }}</h4>
            <small class="text-muted">Radios segmentados</small>
        </div></div></div>
    </div>

    @if ($departamento->localidades_count != $departamento->localidadesPorCodigo->count())
        <div class="alert alert-warning">
            <strong>Ojo:</strong> hay {{ $departamento->localidadesPorCodigo->count() }} localidades cuyo código
            empieza con <code>{{ $departamento->codigo }}</code>, pero sólo {{ $departamento->localidades_count }}
            están vinculadas a este departamento en <code>localidad_departamento</code>. La tabla de vínculo real
            es la que manda (scope, permisos, etc.), pero el listado de abajo muestra <em>ambas</em> para que la
            brecha sea visible.
        </div>
    @endif

    <h5>Localidades vinculadas ({{ $departamento->localidades->count() }})</h5>
    @if ($departamento->localidades->isEmpty())
        <p class="text-muted small">Ninguna — ver el cross-check por código más abajo.</p>
    @else
        <ul class="list-unstyled">
            @foreach ($departamento->localidades->sortBy('codigo') as $localidad)
                <li>
                    <a href="{{ url("/localidad/{$localidad->id}") }}">({{ $localidad->codigo }}) <b>{{ $localidad->nombre }}</b></a>
                    @if ($localidad->aglomerado) <span class="text-muted small">— aglo. {{ $localidad->aglomerado->nombre }}</span> @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($departamento->localidades_count != $departamento->localidadesPorCodigo->count())
        <h5>Localidades por código (cross-check, código empieza con {{ $departamento->codigo }})</h5>
        <ul class="list-unstyled">
            @foreach ($departamento->localidadesPorCodigo as $localidad)
                <li>
                    <a href="{{ url("/localidad/{$localidad->id}") }}">({{ $localidad->codigo }}) <b>{{ $localidad->nombre }}</b></a>
                    @if (!$departamento->localidades->contains('id', $localidad->id))
                        <span class="badge badge-warning">sin vínculo en localidad_departamento</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($departamento->cabeceraVigente)
        <p><strong>Cabecera:</strong> {{ $departamento->cabeceraVigente->localidad->nombre ?? '—' }}</p>
    @endif

    @if ($departamento->gobiernosLocalesVigentes->isNotEmpty())
        <p><strong>Gobierno(s) local(es):</strong>
            {{ $departamento->gobiernosLocalesVigentes->pluck('nombre')->implode(', ') }}</p>
    @endif

    @if ($departamento->observacion)
        <p><strong>Observación:</strong> {{ $departamento->observacion->texto }}</p>
    @endif
</div>
