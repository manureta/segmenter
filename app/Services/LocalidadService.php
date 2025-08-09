<?php

namespace App\Services;

use App\Model\Localidad;
use App\Model\Radio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class LocalidadService
{
    protected CacheService $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get radios with statistics for a localidad
     */
    public function getRadiosWithStatistics(Localidad $localidad): Collection
    {
        return $this->cache->cacheLocalidadRadios($localidad->codigo, function() use ($localidad) {
            if (!$localidad->listado) {
                return collect();
            }

            try {
                $radios = DB::table('e'.$localidad->codigo.'.listado')
                    ->select(DB::raw("
                        prov||dpto||frac||radio as link,
                        codloc,
                        '('||dpto||') '||max(nom_dpto)||': '||frac||' '||radio as nombre,
                        count(distinct mza) as cant_mzas,
                        count(*) as registros,
                        count(indec.contar_vivienda(tipoviv)) as vivs,
                        count(CASE WHEN tipoviv='A' THEN 1 else null END) as vivs_a,
                        count(CASE WHEN (tipoviv='B1' or tipoviv='B2') THEN 1 else null END) as vivs_b,
                        count(CASE WHEN tipoviv='CA/CP' THEN 1 else null END) as vivs_c,
                        count(CASE WHEN tipoviv='CO' THEN 1 else null END) as vivs_co,
                        count(CASE WHEN (tipoviv='D' or tipoviv='J' or tipoviv='VE') THEN 1 else null END) as vivs_djve,
                        count(CASE WHEN tipoviv='' THEN 1 else null END) as vivs_unclas
                    "))
                    ->groupBy('prov', 'dpto', 'codloc', 'frac', 'radio')
                    ->orderBy('prov')->orderBy('dpto')->orderBy('codloc')->orderBy('frac')->orderBy('radio')
                    ->get();

                return $this->enrichRadiosWithModels($radios);

            } catch (\Exception $e) {
                Log::error('Error getting radios statistics', [
                    'localidad' => $localidad->codigo,
                    'error' => $e->getMessage()
                ]);
                return collect();
            }
        });
    }

    /**
     * Generate SVG for localidad
     */
    public function generateSvg(Localidad $localidad): string
    {
        return $this->cache->cacheLocalidadSvg($localidad->codigo, function() use ($localidad) {
            if (!$localidad->carto) {
                return "No se puede previsualizar";
            }

            if ($localidad->radios->count() > 20) {
                return $this->generateRadiosSvg($localidad);
            }

            return $this->generateCartographySvg($localidad);
        });
    }

    /**
     * Check if localidad has required tables
     */
    public function checkRequiredTables(Localidad $localidad): array
    {
        $tables = [
            'arc' => Schema::hasTable('e'.$localidad->codigo.'.arc'),
            'listado' => Schema::hasTable('e'.$localidad->codigo.'.listado'),
            'segmentacion' => Schema::hasTable('e'.$localidad->codigo.'.segmentacion'),
            'radios' => Schema::hasTable('e'.$localidad->codigo.'.radios'),
        ];

        return $tables;
    }

    /**
     * Get localidad processing statistics
     */
    public function getProcessingStats(Localidad $localidad): array
    {
        $stats = [
            'has_carto' => $localidad->carto,
            'has_listado' => $localidad->listado,
            'has_segmentation' => $localidad->segmentadolistado,
            'has_sides' => $localidad->segmentadolados,
            'radios_count' => 0,
            'segments_count' => 0,
        ];

        if ($localidad->listado) {
            try {
                $stats['radios_count'] = DB::table('e'.$localidad->codigo.'.listado')
                    ->select(DB::raw('count(distinct prov||dpto||frac||radio) as total'))
                    ->value('total');
            } catch (\Exception $e) {
                Log::warning('Could not get radios count', ['localidad' => $localidad->codigo]);
            }
        }

        if ($localidad->segmentadolistado) {
            try {
                $stats['segments_count'] = DB::table('e'.$localidad->codigo.'.segmentacion')
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Could not get segments count', ['localidad' => $localidad->codigo]);
            }
        }

        return $stats;
    }

    /**
     * Invalidate localidad cache
     */
    public function invalidateCache(Localidad $localidad): void
    {
        $this->cache->invalidateLocalidad($localidad->codigo);
    }

    /**
     * Private helper methods
     */
    private function enrichRadiosWithModels(Collection $radios): Collection
    {
        $links = $radios->pluck('link')->toArray();
        
        if (empty($links)) {
            return collect();
        }

        $radioModels = Radio::whereIn('codigo', $links)->get()->keyBy('codigo');
        
        return $radios->map(function($radio) use ($radioModels) {
            $radio->model = $radioModels->get($radio->link);
            return $radio;
        });
    }

    private function generateRadiosSvg(Localidad $localidad): string
    {
        // Implementation for radios SVG generation
        return $this->executeSvgQuery($localidad, 'radios');
    }

    private function generateCartographySvg(Localidad $localidad): string
    {
        // Implementation for cartography SVG generation  
        return $this->executeSvgQuery($localidad, 'arc');
    }

    private function executeSvgQuery(Localidad $localidad, string $table): string
    {
        try {
            $height = 800;
            $width = 900;
            
            // Get extent
            $extent = DB::select("SELECT box2d(st_collect(wkb_geometry)) box FROM e".$localidad->codigo.".".$table);
            
            if (empty($extent)) {
                return "No se pudo generar SVG";
            }
            
            $extent = $extent[0]->box;
            $viewBox = $this->calculateViewBox($extent, $height, $width);
            
            // Generate SVG paths based on table type
            $svgQuery = $this->buildSvgQuery($localidad->codigo, $table, $viewBox, $height, $width);
            
            $result = DB::select($svgQuery);
            
            return $result[0]->concat ?? "No se pudo generar SVG";
            
        } catch (\Exception $e) {
            Log::error('SVG generation failed', [
                'localidad' => $localidad->codigo,
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return "Error generando SVG";
        }
    }

    private function calculateViewBox(string $extent, int $height, int $width): string
    {
        [$x0, $y0, $x1, $y1] = sscanf($extent, 'BOX(%f %f,%f %f)');
        $Dx = $x1 - $x0;
        $Dy = $y1 - $y0;
        
        $margin = 0.1;
        $m_izq = $margin * $Dx;
        $m_der = $margin * $Dx;
        $m_arr = $margin * $Dy;
        $m_aba = $margin * $Dy;
        
        return ($x0 - $m_izq) . " " . (-$y1 - $m_arr) . " " . ($Dx + $m_izq + $m_der) . " " . ($Dy + $m_arr + $m_aba);
    }

    private function buildSvgQuery(string $codigo, string $table, string $viewBox, int $height, int $width): string
    {
        $epsilon = 0.5; // Default epsilon for stroke width
        
        return "
            WITH shapes (geom, attribute, bgcolor) AS (
                SELECT wkb_geometry, 
                       CASE WHEN segi IS NOT NULL THEN segi ELSE 0 END,
                       'none' 
                FROM e{$codigo}.{$table}
            ),
            paths (svg) as (
                SELECT concat(
                    '<path d=\"',
                    ST_AsSVG(st_union(geom), 0), '\" ',
                    CASE 
                        WHEN attribute = 0 THEN 'stroke=\"gray\" stroke-width=\"2\" fill=\"lightgray\"'
                        ELSE 'stroke=\"black\" stroke-width=\"{$epsilon}\" fill=\"orange\"'
                    END,
                    ' />') 
                FROM shapes 
                GROUP BY attribute
            )
            SELECT concat(
                '<svg id=\"localidad_{$codigo}\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"{$viewBox}\" height=\"{$height}\" width=\"{$width}\">',
                array_to_string(array_agg(svg),''),
                '</svg>') as concat
            FROM paths;
        ";
    }
}