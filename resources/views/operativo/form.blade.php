@extends('layouts.app')

@section('title', $operativo->exists ? 'Editar operativo' : 'Nuevo operativo')

@section('content')
<div class="container">
    <div class="card" style="max-width: 40rem;">
        <div class="card-header">{{ $operativo->exists ? 'Editar operativo: '.$operativo->nombre : 'Nuevo operativo' }}</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $operativo->exists ? route('operativos.update', $operativo) : route('operativos.store') }}">
                @csrf
                @if ($operativo->exists)
                    @method('PUT')
                @endif

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control"
                           value="{{ old('nombre', $operativo->nombre) }}" maxlength="50" required>
                </div>

                <div class="form-group">
                    <label for="observacion">Observación</label>
                    <input type="text" name="observacion" id="observacion" class="form-control"
                           value="{{ old('observacion', $operativo->observacion) }}" maxlength="50">
                </div>

                <div class="form-group">
                    <label for="topologia_id">Topología asociada</label>
                    <select name="topologia_id" id="topologia_id" class="form-control">
                        <option value="">— Sin asociar —</option>
                        @foreach ($topologias as $topologia)
                            <option value="{{ $topologia->id }}"
                                @selected(old('topologia_id', $operativo->topologia_id) == $topologia->id)>
                                {{ $topologia->name }} (SRID {{ $topologia->srid }})
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">
                        Hoy es solo referencia: indec.cargartopologia() todavía no la usa para nombrar la topología.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ $operativo->exists ? route('operativos.show', $operativo) : route('operativos.index') }}" class="btn btn-link">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
