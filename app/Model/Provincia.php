<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\ConnectionException;
use GuzzleHttp\Exception\RequestException;

class Provincia extends Model
{
    //
    protected $table='provincia';

    protected $fillable = [
        'id','codigo','nombre'
    ];

    protected $url_json = 'https://geoservicios.indec.gob.ar/geoserver/sig/ows';
    protected $params = ['service'=>'WFS','version'=>'1.0.0','request'=>'GetFeature',
    'typeName'=>'sig:v_provincias','outputFormat'=>'application/json'];

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
        ],
        [
            'url' => 'https://wms.ign.gob.ar/geoserver/ows',
            'params' => ['service'=>'WFS','version'=>'1.0.0','request'=>'GetFeature',
                'typeName'=>'ign:provincia','outputFormat'=>'application/json',
                'srsName'=>'EPSG:4326'],
            'filter_field' => 'in1',
        ],
    ];

    protected $url_svg = 'http://172.26.68.22:8080/geoserver/precenso/wms';
    protected $params_svg = ['service'=>'WMS','version'=>'1.1.0','request'=>'GetMap','layers'=>'precenso:provincias',
    'bbox'=>'-73.9999999999999,-90.000000029,-24.9999999999999,-21.780856764','width'=>'551','height'=>'768',
    'srs'=>'EPSG:4326','format'=>'image/svg xml'];
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
     * Relación con Operativo, diretamente puede tener varios operativos.
     */
    public function operativos()
    {
        return $this->hasMany('App\Model\OperativoProv');
    }

    /**
     * Get the json geoservicios de la provincia.
     */
    public function geojson()
    {
      $sources = array_merge(
        [['url'=>$this->url_json,'params'=>$this->params,'filter_field'=>'link']],
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
          return $response;
        }
      }

      flash('No se pudo obtener el geojson de la provincia desde ninguna fuente disponible.')->warning();
      return null;
    }


    /**
     * Get the json geoservicios de la provincia.
     */
    public function svg()
    {
      try {
       $response = Http::timeout(5)->retry(2, 200)->get($this->url_svg,
         Arr::add($this->params_svg,"cql_filter","link =  '".$this->codigo."'")
        );
      } catch ( ConnectionException $e) {
        flash('Timeout de 5 seg. a: '.$this->url_svg)->warning();
        return null;
      } catch ( RequestException $e) {
        flash('Error de conexión a: '.$this->url_svg.' ('.$e->getMessage().')')->warning();
        return null;
      }
        if ($response->ok()) {
          return $response;
        } else {
          return $response->headers();
        }
    }

}
