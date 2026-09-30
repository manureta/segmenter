<?php

namespace Database\Seeders;

use App\MyDB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ejercita el pipeline REAL de carga de listado de viviendas (el mismo que
 * usa /segmentador vía SegmenterController -> Archivo -> MyDB) sobre un
 * esquema con nomenclatura real: "e98001001" (provincia 98 depto 001,
 * codloc demo 001 -- ver DemoProvinciaSeeder para el resto de la provincia
 * demo, que usa un esquema propio "demo98" sin este nivel de detalle).
 *
 * No inventa parser propio: carga arc/lab/listado con las columnas exactas
 * que usan indec.cargarTopologia(), indec.cargar_lados(), indec.cargar_conteos()
 * y MyDB::georeferenciar_listado() (ver app/MyDB.php:1385-1557 y
 * app/developer_docs/segmentacion-core/lados_completos/cargar_lados.sql,
 * cargar_conteos.sql), y termina llamando exactamente esas funciones reales.
 *
 * Fixtures fuente (también pensadas para subir tal cual por /segmentador,
 * conviertiendo arc/lab a shapefile): app/developer_docs/demo/
 *   arc_e98001001.geojson, lab_e98001001.geojson, c1_e98001001.csv
 *
 * Uso: php artisan db:seed --class=DemoListadoSeeder
 * (requiere haber corrido antes, o no, DemoProvinciaSeeder -- son esquemas
 * independientes, no se pisan.)
 */
class DemoListadoSeeder extends Seeder
{
    const ESQUEMA = 'e98001001';
    const SRID = 5344;

    private string $fixturesPath;

    public function __construct()
    {
        $this->fixturesPath = app_path('developer_docs/demo');
    }

    public function run()
    {
        $esquema = self::ESQUEMA;

        DB::statement('DROP SCHEMA IF EXISTS "'.$esquema.'" CASCADE');
        DB::statement('CREATE SCHEMA "'.$esquema.'"');

        $this->crearYCargarArc($esquema);
        $this->crearYCargarLab($esquema);

        MyDB::cargarTopologia($esquema);

        $nVivs = $this->crearYCargarListado($esquema);

        DB::unprepared("SELECT indec.cargar_lados('".$esquema."')");
        DB::unprepared("SELECT indec.cargar_conteos('".$esquema."')");

        MyDB::georeferenciar_listado($esquema);

        $nGeo = DB::table($esquema.'.listado_geo')->count();
        $nGeoConGeom = DB::table($esquema.'.listado_geo')->whereNotNull('wkb_geometry')->count();

        $this->command->info(
            "Demo listado sembrado en esquema $esquema: $nVivs viviendas cargadas, ".
            "listado_geo tiene $nGeo filas ($nGeoConGeom con geometría resuelta)."
        );
    }

    private function crearYCargarArc(string $esquema): void
    {
        DB::statement('CREATE TABLE "'.$esquema.'".arc (
            ogc_fid serial PRIMARY KEY,
            nomencla varchar(20), codigo10 integer, codigo20 integer,
            tipo varchar(20), nombre varchar(60), ancho integer, anchomed numeric(6,1),
            ladoi integer, ladod integer,
            desdei integer, desded integer, hastai integer, hastad integer,
            mzai varchar(15), mzad varchar(15),
            wkb_geometry geometry(LineString, '.self::SRID.')
        )');

        foreach ($this->leerGeoJson('arc_e98001001.geojson')['features'] as $f) {
            $p = $f['properties'];
            DB::insert(
                'INSERT INTO "'.$esquema.'".arc
                 (nomencla, codigo10, codigo20, tipo, nombre, ancho, anchomed,
                  ladoi, ladod, desdei, desded, hastai, hastad, mzai, mzad, wkb_geometry)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                         ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), '.self::SRID.'))',
                [
                    $p['nomencla'], $p['codigo10'], $p['codigo20'], $p['tipo'], $p['nombre'],
                    $p['ancho'], $p['anchomed'], $p['ladoi'], $p['ladod'],
                    $p['desdei'], $p['desded'], $p['hastai'], $p['hastad'],
                    $p['mzai'], $p['mzad'], json_encode($f['geometry']),
                ]
            );
        }
    }

    private function crearYCargarLab(string $esquema): void
    {
        DB::statement('CREATE TABLE "'.$esquema.'".lab (
            id serial PRIMARY KEY,
            prov varchar(2), depto varchar(3), codloc varchar(3),
            frac varchar(2), radio varchar(2), mza varchar(3),
            wkb_geometry geometry(Point, '.self::SRID.')
        )');

        foreach ($this->leerGeoJson('lab_e98001001.geojson')['features'] as $f) {
            $p = $f['properties'];
            DB::insert(
                'INSERT INTO "'.$esquema.'".lab (prov, depto, codloc, frac, radio, mza, wkb_geometry)
                 VALUES (?, ?, ?, ?, ?, ?, ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), '.self::SRID.'))',
                [$p['prov'], $p['depto'], $p['codloc'], $p['frac'], $p['radio'], $p['mza'], json_encode($f['geometry'])]
            );
        }
    }

    /**
     * Carga directa (no pasa por Archivo::procesarDBF()/CsvImport): el CSV
     * en sí (mismo delimitador "|" y mismas columnas finales) queda como
     * artefacto descargable para subir de verdad por /segmentador.
     */
    private function crearYCargarListado(string $esquema): int
    {
        $path = $this->fixturesPath.'/c1_e98001001.csv';
        $fh = fopen($path, 'r');
        $columnas = fgetcsv($fh, 0, '|');

        DB::statement('CREATE TABLE "'.$esquema.'".listado (
            id serial PRIMARY KEY,
            '.implode(', ', array_map(fn ($c) => "$c varchar(60)", $columnas)).'
        )');

        $placeholders = implode(',', array_fill(0, count($columnas), '?'));
        $sql = 'INSERT INTO "'.$esquema.'".listado ('.implode(',', $columnas).") VALUES ($placeholders)";

        $n = 0;
        while (($fila = fgetcsv($fh, 0, '|')) !== false) {
            DB::insert($sql, $fila);
            $n++;
        }
        fclose($fh);

        return $n;
    }

    private function leerGeoJson(string $archivo): array
    {
        return json_decode(file_get_contents($this->fixturesPath.'/'.$archivo), true);
    }
}
