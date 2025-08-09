<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Localidad;
use App\Services\LocalidadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LocalidadApiController extends Controller
{
    protected LocalidadService $localidadService;

    public function __construct(LocalidadService $localidadService)
    {
        // $this->middleware('auth:sanctum'); // Temporarily disabled - sanctum not configured
        $this->localidadService = $localidadService;
    }

    /**
     * Display a listing of localidades
     */
    public function index(Request $request): JsonResponse
    {
        $query = Localidad::query();

        // Filters
        if ($request->has('provincia_id')) {
            $query->whereHas('departamentos.provincia', function($q) use ($request) {
                $q->where('id', $request->provincia_id);
            });
        }

        if ($request->has('departamento_id')) {
            $query->whereHas('departamentos', function($q) use ($request) {
                $q->where('id', $request->departamento_id);
            });
        }

        if ($request->has('with_carto') && $request->boolean('with_carto')) {
            $query->withCarto();
        }

        if ($request->has('with_listado') && $request->boolean('with_listado')) {
            $query->withListado();
        }

        if ($request->has('segmented') && $request->boolean('segmented')) {
            $query->segmented();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $localidades = $query->paginate($perPage);

        // Add processing stats if requested
        if ($request->boolean('include_stats')) {
            $localidades->getCollection()->transform(function ($localidad) {
                $localidad->processing_stats = $localidad->getProcessingStats();
                return $localidad;
            });
        }

        return response()->json($localidades);
    }

    /**
     * Display the specified localidad
     */
    public function show(string $codigo): JsonResponse
    {
        $localidad = Localidad::where('codigo', $codigo)->firstOrFail();
        
        $data = [
            'localidad' => $localidad,
            'processing_stats' => $localidad->getProcessingStats(),
            'required_tables' => $this->localidadService->checkRequiredTables($localidad),
        ];

        return response()->json($data);
    }

    /**
     * Get radios for a localidad
     */
    public function radios(string $codigo): JsonResponse
    {
        $localidad = Localidad::where('codigo', $codigo)->firstOrFail();
        
        try {
            $radios = $localidad->getRadiosWithStatistics();
            
            return response()->json([
                'data' => $radios,
                'count' => $radios->count(),
                'localidad' => $localidad->only(['codigo', 'nombre'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting localidad radios', [
                'codigo' => $codigo,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener radios',
                'message' => 'No se pudieron cargar los radios para esta localidad'
            ], 500);
        }
    }

    /**
     * Get SVG representation of localidad
     */
    public function svg(string $codigo): JsonResponse
    {
        $localidad = Localidad::where('codigo', $codigo)->firstOrFail();
        
        try {
            $svg = $localidad->generateSvg();
            
            return response()->json([
                'svg' => $svg,
                'localidad' => $localidad->only(['codigo', 'nombre'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating localidad SVG', [
                'codigo' => $codigo,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al generar SVG',
                'message' => 'No se pudo generar la visualización para esta localidad'
            ], 500);
        }
    }

    /**
     * Get processing statistics for multiple localidades
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'codigos' => 'required|array|max:50',
            'codigos.*' => 'string|size:10'
        ]);

        $codigos = $request->get('codigos');
        
        $stats = Cache::remember(
            'localidades_stats_' . md5(implode(',', $codigos)), 
            300, // 5 minutes
            function() use ($codigos) {
                return Localidad::whereIn('codigo', $codigos)
                    ->get()
                    ->map(function($localidad) {
                        return [
                            'codigo' => $localidad->codigo,
                            'nombre' => $localidad->nombre,
                            'stats' => $localidad->getProcessingStats()
                        ];
                    });
            }
        );

        return response()->json(['data' => $stats]);
    }

    /**
     * Invalidate cache for a localidad
     */
    public function clearCache(string $codigo): JsonResponse
    {
        $localidad = Localidad::where('codigo', $codigo)->firstOrFail();
        
        try {
            $localidad->invalidateCache();
            
            return response()->json([
                'message' => 'Cache cleared successfully',
                'localidad' => $localidad->only(['codigo', 'nombre'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error clearing localidad cache', [
                'codigo' => $codigo,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error clearing cache',
                'message' => 'No se pudo limpiar el cache para esta localidad'
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulkStats(Request $request): JsonResponse
    {
        $request->validate([
            'filters' => 'array'
        ]);

        $query = Localidad::query();

        // Apply bulk filters
        $filters = $request->get('filters', []);
        foreach ($filters as $filter => $value) {
            switch ($filter) {
                case 'with_carto':
                    if ($value) $query->withCarto();
                    break;
                case 'with_listado':
                    if ($value) $query->withListado();
                    break;
                case 'segmented':
                    if ($value) $query->segmented();
                    break;
            }
        }

        $localidades = $query->limit(100)->get();
        
        $stats = $localidades->map(function($localidad) {
            return [
                'codigo' => $localidad->codigo,
                'nombre' => $localidad->nombre,
                'stats' => $localidad->getProcessingStats()
            ];
        });

        return response()->json([
            'data' => $stats,
            'summary' => [
                'total' => $stats->count(),
                'with_carto' => $stats->where('stats.has_carto', true)->count(),
                'with_listado' => $stats->where('stats.has_listado', true)->count(),
                'segmented' => $stats->where('stats.has_segmentation', true)->count(),
            ]
        ]);
    }
}