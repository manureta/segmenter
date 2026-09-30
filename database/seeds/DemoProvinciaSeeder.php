<?php

namespace Database\Seeders;

use App\MyDB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Provincia, departamentos, topología (arc/lab reales vía cargarTopologia()),
 * fracciones/radios y un operativo — todo ficticio, bajo código de provincia
 * 98 (98 no lo usa ningún código real de INDEC; 99 está reservado para "sin
 * provincia", por eso no se usó acá).
 *
 * No toca nada fuera de lo propio: sólo filas con codigo LIKE '98%' (o
 * codigo='98'), su esquema de topología "demo98" y su fila en operativo. Se
 * puede correr las veces que haga falta: limpia sus propios datos antes de
 * volver a sembrarlos.
 *
 * Fixtures editables en QGIS: app/developer_docs/demo/{provincia,arc,lab}.geojson
 * (WGS84 / EPSG:4326). arc/lab se reproyectan a un CRS métrico (SRID 5344,
 * POSGAR 2007 faja 3) antes de cargarlos, porque cargarTopologia() usa una
 * tolerancia de nodo de 0.5 en las unidades del SRID de arc/lab — 0.5° sería
 * ~55km y fusionaría toda la grilla en un solo nodo. Ese SRID no lo usa
 * ninguna topología real hoy (ver `select name,srid from topology.topology`),
 * así que cargarTopologia() crea una topología nueva y aislada, "5344_topo",
 * sin mezclarse con datos reales de ninguna provincia.
 *
 * Uso: php artisan db:seed --class=DemoProvinciaSeeder
 * A propósito NO está colgado del DatabaseSeeder por defecto, para que nadie
 * lo dispare sin querer contra una base que no sea de desarrollo.
 */
class DemoProvinciaSeeder extends Seeder
{
    const PROVINCIA_CODIGO = '98';
    const ESQUEMA = 'demo98';
    const SRID = 5344;
    const ID_BASE = 900000;

    private string $fixturesPath;

    public function __construct()
    {
        $this->fixturesPath = app_path('developer_docs/demo');
    }

    public function run()
    {
        $this->limpiarDatosPrevios();

        $fuenteId = $this->fuenteDemo();
        $provinciaId = $this->seedProvincia($fuenteId);
        $deptoIds = $this->seedDepartamentos($provinciaId);
        [$fracciones, $radios] = $this->seedTopologiaFraccionesYRadios($fuenteId, $deptoIds);
        $operativoId = $this->seedOperativo($provinciaId);

        $this->command->info(
            "Demo provincia 98 sembrada: provincia=$provinciaId, ".
            'departamentos='.count($deptoIds).", fracciones=$fracciones, radios=$radios, operativo=$operativoId."
        );
    }

    private function limpiarDatosPrevios(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS "'.self::ESQUEMA.'" CASCADE');

        $geomIds = DB::table('radio')->where('codigo', 'like', self::PROVINCIA_CODIGO.'%')->pluck('geometria_id')
            ->merge(DB::table('fraccion')->where('codigo', 'like', self::PROVINCIA_CODIGO.'%')->pluck('geometria_id'))
            ->merge(DB::table('provincia')->where('codigo', self::PROVINCIA_CODIGO)->pluck('geometria_id'))
            ->filter()->unique()->values();

        DB::table('radio')->where('codigo', 'like', self::PROVINCIA_CODIGO.'%')->delete();
        DB::table('fraccion')->where('codigo', 'like', self::PROVINCIA_CODIGO.'%')->delete();

        $provinciaIdsPrevias = DB::table('provincia')->where('codigo', self::PROVINCIA_CODIGO)->pluck('id');
        DB::table('operativo_provincia')->whereIn('provincia_id', $provinciaIdsPrevias)->delete();
        DB::table('operativo')->where('nombre', 'Operativo Demo')->delete();

        DB::table('departamentos')->where('codigo', 'like', self::PROVINCIA_CODIGO.'%')->delete();
        DB::table('provincia')->where('codigo', self::PROVINCIA_CODIGO)->delete();

        if ($geomIds->isNotEmpty()) {
            DB::table('geometria')->whereIn('id', $geomIds)->delete();
        }
    }

    private function fuenteDemo(): int
    {
        return DB::table('fuente')->where('nombre', 'Demo / datos ficticios')->value('id')
            ?? DB::table('fuente')->insertGetId(['nombre' => 'Demo / datos ficticios']);
    }

    private function seedProvincia(int $fuenteId): int
    {
        $feature = $this->leerGeoJson('provincia.geojson')['features'][0];

        $geometriaId = DB::selectOne(
            'INSERT INTO geometria (geom, fuente_id, updated_at)
             VALUES (ST_Multi(ST_GeomFromGeoJSON(?)), ?, now())
             RETURNING id',
            [json_encode($feature['geometry']), $fuenteId]
        )->id;

        // id explícito, no insertGetId: la secuencia provincia_id_seq está
        // desincronizada en esta base (quedó en 1 pese a haber filas hasta
        // id 24, típico de cargas por SQL crudo que nunca la avanzan) — no
        // es este seeder quien debe corregir eso sobre una tabla real.
        DB::table('provincia')->insert([
            'id' => self::ID_BASE,
            'codigo' => self::PROVINCIA_CODIGO,
            'nombre' => 'Provincia Demo',
            'geometria_id' => $geometriaId,
        ]);

        return self::ID_BASE;
    }

    private function seedDepartamentos(int $provinciaId): array
    {
        // geometria_id queda NULL a propósito: en esta app hoy nada llena
        // departamentos.geometria_id tampoco para datos reales (columna
        // declarada "a futuro" en create_geometria_table).
        $nombres = ['001' => 'Departamento Demo Oeste', '002' => 'Departamento Demo Este'];
        $ids = [];
        $id = self::ID_BASE;

        foreach ($nombres as $segmento => $nombre) {
            $id++;
            DB::table('departamentos')->insert([
                'id' => $id,
                'codigo' => self::PROVINCIA_CODIGO.$segmento,
                'nombre' => $nombre,
                'provincia_id' => $provinciaId,
            ]);
            $ids[$segmento] = $id;
        }

        return $ids;
    }

    private function seedTopologiaFraccionesYRadios(int $fuenteId, array $deptoIds): array
    {
        $esquema = self::ESQUEMA;

        DB::statement('CREATE SCHEMA "'.$esquema.'"');
        DB::statement('CREATE TABLE "'.$esquema.'".arc (
            id serial PRIMARY KEY,
            wkb_geometry geometry(LineString, '.self::SRID.')
        )');
        DB::statement('CREATE TABLE "'.$esquema.'".lab (
            id serial PRIMARY KEY,
            prov varchar(2), depto varchar(3), codloc varchar(10),
            frac varchar(2), radio varchar(2), mza varchar(2),
            wkb_geometry geometry(Point, '.self::SRID.')
        )');

        foreach ($this->leerGeoJson('arc.geojson')['features'] as $feature) {
            DB::insert(
                'INSERT INTO "'.$esquema.'".arc (wkb_geometry)
                 VALUES (ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), '.self::SRID.'))',
                [json_encode($feature['geometry'])]
            );
        }

        foreach ($this->leerGeoJson('lab.geojson')['features'] as $feature) {
            $p = $feature['properties'];
            DB::insert(
                'INSERT INTO "'.$esquema.'".lab (prov, depto, codloc, frac, radio, mza, wkb_geometry)
                 VALUES (?, ?, ?, ?, ?, ?, ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), '.self::SRID.'))',
                [$p['prov'], $p['depto'], $p['codloc'], $p['frac'], $p['radio'], $p['mza'], json_encode($feature['geometry'])]
            );
        }

        MyDB::cargarTopologia($esquema);

        $fraccionIds = [];
        $id = self::ID_BASE;
        foreach (DB::select('SELECT dpto, frac, wkb_geometry FROM "'.$esquema.'".fracciones') as $row) {
            $id++;
            $geometriaId = $this->geometriaDesdeTopologia($row->wkb_geometry, $fuenteId);

            DB::table('fraccion')->insert([
                'id' => $id,
                'codigo' => self::PROVINCIA_CODIGO.$row->dpto.$row->frac,
                'departamento_id' => $deptoIds[$row->dpto],
                'geometria_id' => $geometriaId,
            ]);
            $fraccionIds[$row->dpto.'-'.$row->frac] = $id;
        }

        $radioId = self::ID_BASE;
        foreach (DB::select('SELECT dpto, frac, radio, wkb_geometry FROM "'.$esquema.'".radios') as $row) {
            $radioId++;
            $geometriaId = $this->geometriaDesdeTopologia($row->wkb_geometry, $fuenteId);

            DB::table('radio')->insert([
                'id' => $radioId,
                'codigo' => self::PROVINCIA_CODIGO.$row->dpto.$row->frac.$row->radio,
                'fraccion_id' => $fraccionIds[$row->dpto.'-'.$row->frac],
                'geometria_id' => $geometriaId,
                'tipo_de_radio_id' => 3, // Urbano
                'issegmentado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [count($fraccionIds), $radioId - self::ID_BASE];
    }

    /**
     * geometria.geom exige MultiPolygon en SRID 4326 (typmod de la tabla);
     * lo que sale de la topología está en el SRID métrico (self::SRID), así
     * que se reproyecta de vuelta antes de guardar.
     */
    private function geometriaDesdeTopologia(string $wkbGeometry, int $fuenteId): int
    {
        return DB::selectOne(
            'INSERT INTO geometria (geom, fuente_id, updated_at)
             VALUES (ST_Multi(ST_Transform(ST_SetSRID(?::geometry, '.self::SRID.'), 4326)), ?, now())
             RETURNING id',
            [$wkbGeometry, $fuenteId]
        )->id;
    }

    private function seedOperativo(int $provinciaId): int
    {
        $operativoId = DB::table('operativo')->insertGetId([
            'nombre' => 'Operativo Demo',
            'observacion' => 'Operativo ficticio (provincia demo 98).',
            'es_publico' => true,
        ]);

        DB::table('operativo_provincia')->insert([
            'operativo_id' => $operativoId,
            'provincia_id' => $provinciaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $operativoId;
    }

    private function leerGeoJson(string $archivo): array
    {
        return json_decode(file_get_contents($this->fixturesPath.'/'.$archivo), true);
    }
}
