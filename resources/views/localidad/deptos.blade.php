<div class="container">
    Información de la Localidad ({{ $localidad->codigo }}) 
    <b> {{ $localidad->nombre }} </b><br />
    <div class="">
     @if($carto)
        La base geográfica está cargada.
     @else
        NO está cargada la base geográfica.
     @endif 
    </div>
    <div class="">
     @if($listado)
        El Listado de viviendas esta cargado.
     @else
        NO está cargado el listado de viviendas.
     @endif 
    </div>

<div class="form-horizontal">
<form action="/grafo/{{ $localidad->id }}" method="GET" enctype="multipart/form-data">
                @csrf

  <div class="form-group">
    <label class="control-label" for="radio">Seleccione una Comuna para ver los
    radios:</label>
    <div class="">
<ul class="nav">
            @foreach($deptos as $depto)
    <li class="btn " >
    @if(true)<a href="{{ url('/radios/'.$localidad->id.'/'.$depto->id) }}">
        {{ trim($depto->codigo) }}: {{ trim($depto->nombre) }}</a> @endif
    </li>
            @endforeach
</ul>
    </div>
  </div>
</form>
</div>


</div>
     @if($carto)
        {!! $svg->concat !!}
     @endif
