@auth
    <li class="nav-item dropdown">
        <a id="navbarDropdownOperativo" class="nav-link dropdown-toggle" href="#" role="button"
           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @if ($operativoActual ?? null)
                Operativo: {{ $operativoActual->nombre }}
            @else
                Operativo: (sin filtro)
            @endif
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdownOperativo">
            @forelse (\App\Model\Operativo::visiblesPara(auth()->user())->orderBy('nombre')->get() as $op)
                <form action="{{ route('operativos.seleccionar', $op) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="dropdown-item {{ ($operativoActual ?? null) && $operativoActual->id === $op->id ? 'active' : '' }}">
                        {{ $op->nombre }} @if(!$op->es_publico) <small class="text-muted">(privado)</small> @endif
                    </button>
                </form>
            @empty
                <span class="dropdown-item-text text-muted">No tenés operativos visibles todavía.</span>
            @endforelse
            <div class="dropdown-divider"></div>
            @if ($operativoActual ?? null)
                <form action="{{ route('operativos.deseleccionar') }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item">Quitar filtro</button>
                </form>
            @endif
            <a class="dropdown-item" href="{{ route('operativos.index') }}">Administrar operativos</a>
        </div>
    </li>
@endauth
