<?php

namespace App\Support;

use App\Model\Provincia;
use App\Model\Departamento;
use App\Model\Aglomerado;
use App\Model\Localidad;
use App\Model\Fraccion;
use App\Model\Radio;

/**
 * Selección de "objeto actual" (Provincia/Departamento/Aglomerado/
 * Localidad/Fracción/Radio) para acotar reportes - pedido explícito.
 *
 * La idea central: Provincia(2)/Departamento(5)/Fracción(7)/Radio(9) son
 * prefijos exactos unos de otros (el propio código territorial, ver
 * SIGeo-relevamiento-tecnico.md), así que "filtrar por X" es simplemente
 * `codigo LIKE 'prefijo%'` contra cualquier tabla con código territorial
 * (radio, localidad, vias.codloc).
 *
 * Localidad y Aglomerado NO comparten esa convención con fracción/radio
 * (localidad.codigo tiene su propio tercer segmento, no fracción+radio) -
 * para esos dos se acota a nivel de su(s) provincia(s) nomás. Es una
 * simplificación honesta: intentar algo más fino dependería de los mismos
 * pivots incompletos que motivaron media auditoría de esta sesión
 * (localidad_departamento, radio_localidad).
 */
class AlcanceGeografico
{
    public const TIPOS = [
        'provincia' => Provincia::class,
        'departamento' => Departamento::class,
        'aglomerado' => Aglomerado::class,
        'localidad' => Localidad::class,
        'fraccion' => Fraccion::class,
        'radio' => Radio::class,
    ];

    public static function resolver(string $tipo, $id)
    {
        $clase = self::TIPOS[$tipo] ?? null;
        if (!$clase) {
            return null;
        }
        return $clase::withoutGlobalScope('operativo_actual')->find($id);
    }

    /**
     * Prefijos de código territorial que cubre el objeto elegido. Devuelve
     * siempre un array (Aglomerado puede cubrir más de una provincia).
     */
    public static function prefijosDe(string $tipo, $modelo): array
    {
        switch ($tipo) {
            case 'provincia':
            case 'departamento':
            case 'fraccion':
                return [trim($modelo->codigo)];
            case 'radio':
                return [trim($modelo->codigo)];
            case 'localidad':
                return [substr(trim($modelo->codigo), 0, 2)];
            case 'aglomerado':
                return $modelo->localidades->pluck('codigo')
                    ->map(fn ($c) => substr(trim($c), 0, 2))->unique()->values()->all();
            default:
                return [];
        }
    }

    /**
     * Etiqueta legible para el banner ("Filtrado por: (codigo) Nombre [tipo]").
     */
    public static function etiquetaDe(string $tipo, $modelo): string
    {
        $nombres = [
            'provincia' => 'Provincia', 'departamento' => 'Departamento',
            'aglomerado' => 'Aglomerado', 'localidad' => 'Localidad',
            'fraccion' => 'Fracción', 'radio' => 'Radio',
        ];
        return '('.trim($modelo->codigo).') '.($modelo->nombre ?? '—').' ['.($nombres[$tipo] ?? $tipo).']';
    }

    /**
     * true si todos los prefijos del objeto caen dentro de los códigos de
     * provincia permitidos para el usuario (null = sin restricción).
     */
    public static function permitidoPara(?array $codigosPermitidos, array $prefijos): bool
    {
        if ($codigosPermitidos === null) {
            return true;
        }
        foreach ($prefijos as $prefijo) {
            if (!in_array(substr($prefijo, 0, 2), $codigosPermitidos)) {
                return false;
            }
        }
        return !empty($prefijos);
    }

    /**
     * Aplica `columna LIKE 'prefijo%'` (OR entre todos los prefijos) a un
     * query builder o Eloquent builder existente.
     */
    public static function filtrarPorPrefijos($query, string $columna, array $prefijos)
    {
        return $query->where(function ($q) use ($columna, $prefijos) {
            foreach ($prefijos as $prefijo) {
                $q->orWhere($columna, 'like', $prefijo.'%');
            }
        });
    }
}
