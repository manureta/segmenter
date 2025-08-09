<?php

namespace App\Model;

use App\Services\LocalidadService;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

class LocalidadModern extends Model
{
    protected $table = 'localidad';

    protected $fillable = [
        'codigo', 'nombre'
    ];

    public $timestamps = false;

    protected $appends = [
        'carto', 'listado', 'segmentadolistado', 'segmentadolados'
    ];

    protected $casts = [
        'carto' => 'boolean',
        'listado' => 'boolean', 
        'segmentadolistado' => 'boolean',
        'segmentadolados' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class, 'localidad_departamento');
    }

    public function aglomerado()
    {
        return $this->belongsTo(Aglomerado::class);
    }

    public function radios()
    {
        return $this->belongsToMany(Radio::class, 'radio_localidad');
    }

    /**
     * Computed attributes using modern Laravel syntax
     */
    protected function codigoLoc(): Attribute
    {
        return Attribute::make(
            get: fn () => substr(trim($this->codigo), 5, 3)
        );
    }

    protected function carto(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->localidadService()->checkRequiredTables($this)['arc']
        );
    }

    protected function listado(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->localidadService()->checkRequiredTables($this)['listado']
        );
    }

    protected function segmentadolistado(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->localidadService()->checkRequiredTables($this)['segmentacion']
        );
    }

    protected function segmentadolados(): Attribute
    {
        return Attribute::make(
            get: function () {
                $service = $this->localidadService();
                $tables = $service->checkRequiredTables($this);
                
                if (!$tables['arc']) {
                    return false;
                }
                
                // Check if has segmented sides
                try {
                    $count = \DB::table('e'.$this->codigo.'.arc')
                        ->whereNotNull('segi')
                        ->orWhereNotNull('segd')
                        ->count();
                    return $count > 0;
                } catch (\Exception $e) {
                    return false;
                }
            }
        );
    }

    /**
     * Business logic methods using services
     */
    public function getRadiosWithStatistics(): Collection
    {
        return $this->localidadService()->getRadiosWithStatistics($this);
    }

    public function generateSvg(): string
    {
        return $this->localidadService()->generateSvg($this);
    }

    public function getProcessingStats(): array
    {
        return $this->localidadService()->getProcessingStats($this);
    }

    public function getComboRadios(): Collection
    {
        return $this->getRadiosWithStatistics();
    }

    public function invalidateCache(): void
    {
        $this->localidadService()->invalidateCache($this);
    }

    /**
     * Service accessor
     */
    protected function localidadService(): LocalidadService
    {
        return app(LocalidadService::class);
    }

    /**
     * Scopes for common queries
     */
    public function scopeWithCarto($query)
    {
        return $query->whereRaw("EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'e' || codigo AND table_name = 'arc'
        )");
    }

    public function scopeWithListado($query)
    {
        return $query->whereRaw("EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'e' || codigo AND table_name = 'listado'
        )");
    }

    public function scopeSegmented($query)
    {
        return $query->whereRaw("EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'e' || codigo AND table_name = 'segmentacion'
        )");
    }

    /**
     * Factory method for testing
     */
    public static function factory()
    {
        return new class {
            public function create(array $attributes = [])
            {
                return LocalidadModern::create(array_merge([
                    'codigo' => '0200101001',
                    'nombre' => 'Test Localidad'
                ], $attributes));
            }
        };
    }
}