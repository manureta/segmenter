@php
    $restringido = auth()->user()->tieneAlcanceRestringido();
    $codigosPermitidos = auth()->user()->codigosProvinciaPermitidos();
@endphp
<li class="nav-item dropdown">
    <a id="navbarDropdownObjeto" class="nav-link dropdown-toggle" href="#" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @if ($objetoActual ?? null)
            Alcance: {{ \App\Support\AlcanceGeografico::etiquetaDe($objetoActualTipo, $objetoActual) }}
        @elseif ($restringido)
            Alcance: tu(s) provincia(s)
        @else
            Alcance: Global
        @endif
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdownObjeto" style="min-width: 280px;">
        @if ($restringido)
            <span class="dropdown-item-text small text-muted">
                Restringido a: {{ implode(', ', $codigosPermitidos) }} — no podés ver Global.
            </span>
            <div class="dropdown-divider"></div>
        @endif

        @if ($objetoActual ?? null)
            <form action="{{ route('objeto-actual.subir') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item">&uarr; Subir un nivel</button>
            </form>

            @php
                $hijos = collect();
                $tipoHijo = null;
                if ($objetoActualTipo === 'provincia') { $hijos = $objetoActual->departamentos()->orderBy('nombre')->limit(20)->get(); $tipoHijo = 'departamento'; }
                elseif ($objetoActualTipo === 'departamento') { $hijos = $objetoActual->fracciones()->orderBy('codigo')->limit(20)->get(); $tipoHijo = 'fraccion'; }
                elseif ($objetoActualTipo === 'fraccion') { $hijos = $objetoActual->radios()->orderBy('codigo')->limit(20)->get(); $tipoHijo = 'radio'; }
            @endphp
            @if ($tipoHijo && $hijos->isNotEmpty())
                <div class="dropdown-divider"></div>
                <span class="dropdown-item-text small text-muted">Bajar a...</span>
                @foreach ($hijos as $hijo)
                    <form action="{{ route('objeto-actual.seleccionar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="tipo" value="{{ $tipoHijo }}">
                        <input type="hidden" name="id" value="{{ $hijo->id }}">
                        <button type="submit" class="dropdown-item">({{ trim($hijo->codigo) }}) {{ $hijo->nombre ?? '—' }}</button>
                    </form>
                @endforeach
            @endif

            <div class="dropdown-divider"></div>
            @if (!$restringido)
                <form action="{{ route('objeto-actual.quitar') }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item">Ver Global</button>
                </form>
            @endif
        @else
            <span class="dropdown-item-text small text-muted">Elegir provincia para empezar a navegar:</span>
            @foreach (\App\Model\Provincia::withoutGlobalScope('operativo_actual')
                ->when($codigosPermitidos, fn ($q) => $q->whereIn('codigo', $codigosPermitidos))
                ->orderBy('nombre')->get() as $prov)
                <form action="{{ route('objeto-actual.seleccionar') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tipo" value="provincia">
                    <input type="hidden" name="id" value="{{ $prov->id }}">
                    <button type="submit" class="dropdown-item">({{ $prov->codigo }}) {{ $prov->nombre }}</button>
                </form>
            @endforeach
        @endif
    </div>
</li>
