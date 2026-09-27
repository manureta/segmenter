@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Panel de desarrollo — endpoints de /setup</h3>
    <p class="text-muted">
        Arma la URL con los valores de cada campo y la abre en una pestaña nueva. No agrega
        lógica nueva, es un índice clickeable de las rutas que ya existen en <code>routes/web.php</code>.
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    @foreach ($grupos as $nombreGrupo => $endpoints)
        <div class="card mb-3">
            <div class="card-header">{{ $nombreGrupo }}</div>
            <div class="card-body">
                @foreach ($endpoints as $endpoint)
                    @php
                        preg_match_all('/\{([a-zA-Z_]+)\}/', $endpoint['path'], $matches);
                        $params = $matches[1];
                    @endphp
                    <div class="row align-items-center mb-2 endpoint-row" data-path="{{ $endpoint['path'] }}">
                        <div class="col-md-4">
                            <strong>{{ $endpoint['label'] }}</strong><br>
                            <code class="text-muted">{{ $endpoint['path'] }}</code>
                        </div>
                        <div class="col-md-6">
                            <div class="form-row">
                                @foreach ($params as $param)
                                    <div class="col-auto">
                                        <input type="text" class="form-control form-control-sm param-input"
                                               data-param="{{ $param }}" placeholder="{{ $param }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-2 text-right">
                            <button type="button" class="btn btn-primary btn-sm btn-ejecutar">Ejecutar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.endpoint-row').forEach(function (row) {
        row.querySelector('.btn-ejecutar').addEventListener('click', function () {
            var path = row.dataset.path;
            var faltantes = [];
            row.querySelectorAll('.param-input').forEach(function (input) {
                var valor = input.value.trim();
                if (!valor) {
                    faltantes.push(input.dataset.param);
                    return;
                }
                path = path.replace('{' + input.dataset.param + '}', encodeURIComponent(valor));
            });
            if (faltantes.length > 0) {
                alert('Faltan completar: ' + faltantes.join(', '));
                return;
            }
            window.open(path, '_blank');
        });
    });
});
</script>
@endsection
