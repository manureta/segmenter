<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Geometria extends Model
{
    protected $table = 'geometria';

    protected $fillable = [
        'fuente_id'
    ];

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    /**
     * Fuente cartográfica de la que proviene esta geometría (IGN, INDEC, etc).
     */
    public function fuente()
    {
        return $this->belongsTo('App\Model\Fuente');
    }

    /**
     * Crea un registro a partir de un geometry GeoJSON (string) y la fuente_id
     * que lo originó. La columna geom es PostGIS puro, no manejable por el
     * query builder de Eloquent, de ahí el SQL crudo puntual.
     */
    public static function crearDesdeGeoJson($geometryJson, $fuenteId)
    {
        $id = DB::selectOne(
            "INSERT INTO geometria (geom, fuente_id, updated_at)
             VALUES (ST_Multi(ST_GeomFromGeoJSON(?)), ?, now())
             RETURNING id",
            [$geometryJson, $fuenteId]
        )->id;

        return static::find($id);
    }

    /**
     * Geometría como GeoJSON, con precisión recortada (menos decimales =
     * menor tamaño de intercambio).
     */
    public function asGeoJson($decimales = 5)
    {
        return DB::selectOne(
            "SELECT ST_AsGeoJSON(geom, ?) AS geojson FROM geometria WHERE id = ?",
            [$decimales, $this->id]
        )->geojson;
    }

    /**
     * Path SVG (atributo d de un <path>) de la geometría.
     */
    public function asSvgPath()
    {
        return DB::selectOne(
            "SELECT ST_AsSVG(geom, 0) AS path FROM geometria WHERE id = ?",
            [$this->id]
        )->path;
    }

    /**
     * Bounding box como string "BOX(x0 y0,x1 y1)".
     */
    public function extent()
    {
        return DB::selectOne(
            "SELECT box2d(geom) box FROM geometria WHERE id = ?",
            [$this->id]
        )->box;
    }
}
