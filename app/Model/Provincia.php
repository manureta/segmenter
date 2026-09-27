<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\ConnectionException;
use GuzzleHttp\Exception\RequestException;

class Provincia extends Model
{
    use \App\Model\Concerns\EscopadoPorOperativo;

    //
    protected $table='provincia';

    protected $fillable = [
        'id','codigo','nombre'
    ];

    protected $url_json = 'https://geoservicios.indec.gob.ar/geoserver/sig/ows';
    protected $params = ['service'=>'WFS','version'=>'1.0.0','request'=>'GetFeature',
    'typeName'=>'sig:v_provincias','outputFormat'=>'application/json'];

    // fuente_id referencia la tabla fuente ya existente (5 = INDEC, 4 = IGN).
    protected $fuente_id_primaria = 5;

    // Fuentes alternativas de geojson de provincias, se prueban en orden ante
    // fallas de la fuente primaria (geoservicios.indec.gob.ar viene fallando
    // con cURL error 56 / SSL_read EOF del lado del servidor).
    protected $json_fallback_sources = [
        [
            'url' => 'https://geonode.indec.gob.ar/geoserver/ows',
            'params' => ['service'=>'WFS','version'=>'1.0.0','request'=>'GetFeature',
                'typeName'=>'geonode:jurisdicciones','outputFormat'=>'application/json',
                'srsName'=>'EPSG:4326'],
            'filter_field' => 'cpr',
            'fuente_id' => 5,
        ],
        [
            'url' => 'https://wms.ign.gob.ar/geoserver/ows',
            'params' => ['service'=>'WFS','version'=>'1.0.0','request'=>'GetFeature',
                'typeName'=>'ign:provincia','outputFormat'=>'application/json',
                'srsName'=>'EPSG:4326'],
            'filter_field' => 'in1',
            'fuente_id' => 4,
        ],
    ];

    // Sin fecha de creación o modificación
    //
    public $timestamps = false;

    public function scopeFilter($query, $params)
    {
        if ( isset($params['name']) && trim($params['name'] !== '') ) {
            $query->where('nombre', 'LIKE', trim($params['name']) . '%');
        }

        //$query->where('operativo.',) // Operativo x default.

        return $query;
    }

     /**
     * Get the departamentos de la provincia.
     */
    public function departamentos()
    {
        return $this->hasMany('App\Model\Departamento');
    }

    /**
     * Relación con Fracciones, a través de departamentos puede tener muchas fracciones.
     */
    public function fracciones()
    {
        return $this->hasManyThrough('App\Model\Fraccion','App\Model\Departamento');
    }

    /**
     * Relación con Fracciones, a través de departamentos puede tener muchas fracciones.
     */
//    public function radios()
//    {
//        return $this->hasManyThrough('App\Model\Radio','App\Model\Departamento');
//    }

    /**
     * Relación con Operativos, una Provincia puede participar de varios.
     * Antes devolvía hasMany(OperativoProv) - filas del pivot en vez de
     * Operativo real, sin columnas nombre/observacion que provinfo.blade.php
     * ya intentaba leer (bug latente, corregido de paso acá).
     */
    public function operativos()
    {
        return $this->belongsToMany('App\Model\Operativo', 'operativo_provincia')->withTimestamps();
    }

    /**
     * Consulta la cadena de geoservers (primario + fallbacks) y devuelve la
     * geometría (GeoJSON) de la primera fuente que responda, junto con la
     * fuente_id usada. No cachea nada, solo hace el fetch remoto.
     */
    protected function fetchGeometriaRemota()
    {
      $sources = array_merge(
        [['url'=>$this->url_json,'params'=>$this->params,'filter_field'=>'link','fuente_id'=>$this->fuente_id_primaria]],
        $this->json_fallback_sources
      );

      foreach ($sources as $source) {
        try {
          $response = Http::timeout(5)->retry(2, 200)->get(
            $source['url'],
            Arr::add($source['params'],"cql_filter",$source['filter_field']." =  '".$this->codigo."'")
          );
        } catch ( ConnectionException $e) {
          continue;
        } catch ( RequestException $e) {
          continue;
        }
        if ($response->ok()) {
          $feature = Arr::get($response->json(), 'features.0');
          if ($feature) {
            return [
              'geometry' => json_encode($feature['geometry']),
              'fuente_id' => $source['fuente_id'],
            ];
          }
        }
      }

      return null;
    }

    /**
     * Relación con la geometría cacheada (tabla geometria, vía columna
     * geometria_id ya existente en provincia).
     */
    public function geometria()
    {
        return $this->belongsTo('App\Model\Geometria');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    public function capitalVigente()
    {
        return $this->hasOne('App\Model\CapitalDeProvincia')->vigente();
    }

    public function historialCapital()
    {
        return $this->hasMany('App\Model\CapitalDeProvincia');
    }

    /**
     * Devuelve la Geometria cacheada para esta provincia, consultando la
     * cadena de geoservers remota y persistiendo el resultado la primera
     * vez que se necesita.
     */
    protected function geometriaCache()
    {
      if ($this->geometria_id && $this->geometria) {
        return $this->geometria;
      }

      $remota = $this->fetchGeometriaRemota();
      if (!$remota) {
        flash('No se pudo obtener la geometría de la provincia desde ninguna fuente disponible.')->warning();
        return null;
      }

      $geometria = Geometria::crearDesdeGeoJson($remota['geometry'], $remota['fuente_id']);
      $this->geometria_id = $geometria->id;
      $this->save();

      return $geometria;
    }

    /**
     * Get the json geoservicios de la provincia, servido desde la cache local
     * (tabla geometria) con precisión recortada a 5 decimales (~1.1m), en vez
     * de relayear el geojson crudo del geoserver remoto.
     */
    public function geojson()
    {
      $geometria = $this->geometriaCache();
      return $geometria ? $geometria->asGeoJson(5) : null;
    }

    /**
     * Genera el SVG del contorno de la provincia a partir de la geometría
     * cacheada localmente, con el mismo patrón que Aglomerado/Localidad/Radio
     * (ST_AsSVG + viewBox calculado del extent), en vez de pedirlo al WMS
     * interno (172.26.68.22, IP privada no siempre alcanzable).
     */
    public function svg()
    {
      $geometria = $this->geometriaCache();
      if (!$geometria) {
        return "Por el momento no se puede previsualizar la provincia.";
      }

      $height = 500;
      $extent = $geometria->extent();
      list($x0, $y0, $x1, $y1) = sscanf($extent, 'BOX(%f %f,%f %f)');
      $Dx = $x1 - $x0;
      $Dy = $y1 - $y0;
      $width = round($height * $Dx / $Dy);
      $epsilon = min($Dx / $width, $Dy / $height);
      $strokeWidth = 2 * $epsilon;
      $viewBox = $this->viewBox($extent, $height, $width);

      $path = $geometria->asSvgPath();
      $fuenteNombre = optional($geometria->fuente)->nombre ?? 'desconocida';
      $legend = $this->svgLegend('Fuente: '.$fuenteNombre, $x1, $y0, $epsilon);

      return '<svg id="provincia_'.$this->codigo.'" xmlns="http://www.w3.org/2000/svg" viewBox="'.$viewBox.'" height="'.$height.'" width="'.$width.'">'
           . '<path d="'.$path.'" stroke="#333" stroke-width="'.$strokeWidth.'" fill="#ccc" />'
           . $legend
           . '</svg>';
    }

    /**
     * Leyenda pequeña con recuadro, ubicada en el margen inferior derecho ya
     * reservado por viewBox() (10% de Dy/Dx), similar al cartel de escala
     * usado como referencia en provinfo.blade.php.
     */
    private function svgLegend($texto, $x1, $y0, $epsilon)
    {
      $fontSize = $epsilon * 10;
      $padding = $epsilon * 4;
      $textWidth = strlen($texto) * $fontSize * 0.55;
      $boxWidth = $textWidth + $padding * 2;
      $boxHeight = $fontSize + $padding * 2;
      $boxX = $x1 - $boxWidth;
      $boxY = -$y0 + $padding;

      return '<rect x="'.$boxX.'" y="'.$boxY.'" width="'.$boxWidth.'" height="'.$boxHeight.'" '
           . 'fill="white" fill-opacity="0.75" stroke="#999" stroke-width="'.($epsilon*0.5).'" />'
           . '<text x="'.($x1-$padding).'" y="'.($boxY+$boxHeight-$padding).'" text-anchor="end" '
           . 'font-size="'.$fontSize.'" fill="#333">'.$texto.'</text>';
    }

    private function viewBox($extent, $height, $width)
    {
      list($x0, $y0, $x1, $y1) = sscanf($extent, 'BOX(%f %f,%f %f)');
      $Dx = $x1 - $x0;
      $Dy = $y1 - $y0;
      $m_izq = .1*$Dx; $m_der = .1*$Dx; $m_arr = .1*$Dy; $m_aba = .1*$Dy;
      return ($x0 - $m_izq) . " " . (- $y1 - $m_arr) . " " . ($Dx + $m_izq + $m_der) . " " . ($Dy + $m_arr + $m_aba);
    }

}
