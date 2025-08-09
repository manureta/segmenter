<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LocalidadService;
use App\Services\CacheService;
use App\Model\LocalidadModern as Localidad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

class LocalidadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LocalidadService $service;
    protected $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->service = new LocalidadService($this->cacheService);
    }

    /** @test */
    public function it_can_check_required_tables()
    {
        $localidad = new Localidad(['codigo' => '0200101001']);
        
        // Mock Schema facade
        Schema::shouldReceive('hasTable')
            ->with('e0200101001.arc')
            ->once()
            ->andReturn(true);
            
        Schema::shouldReceive('hasTable')
            ->with('e0200101001.listado')
            ->once()
            ->andReturn(false);
            
        Schema::shouldReceive('hasTable')
            ->with('e0200101001.segmentacion')
            ->once()
            ->andReturn(false);
            
        Schema::shouldReceive('hasTable')
            ->with('e0200101001.radios')
            ->once()
            ->andReturn(true);

        $result = $this->service->checkRequiredTables($localidad);

        $this->assertTrue($result['arc']);
        $this->assertFalse($result['listado']);
        $this->assertFalse($result['segmentacion']);
        $this->assertTrue($result['radios']);
    }

    /** @test */
    public function it_returns_empty_collection_for_localidad_without_listado()
    {
        $localidad = new Localidad(['codigo' => '0200101001']);
        
        // Mock the listado attribute to return false
        $localidad->shouldReceive('getAttribute')
            ->with('listado')
            ->andReturn(false);

        $this->cacheService
            ->shouldReceive('cacheLocalidadRadios')
            ->once()
            ->andReturnUsing(function($codigo, $callback) {
                return $callback();
            });

        $result = $this->service->getRadiosWithStatistics($localidad);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function it_generates_svg_using_cache()
    {
        $localidad = new Localidad(['codigo' => '0200101001']);
        
        $expectedSvg = '<svg>test</svg>';
        
        $this->cacheService
            ->shouldReceive('cacheLocalidadSvg')
            ->once()
            ->with('0200101001', Mockery::type('callable'))
            ->andReturn($expectedSvg);

        $result = $this->service->generateSvg($localidad);

        $this->assertEquals($expectedSvg, $result);
    }

    /** @test */
    public function it_gets_processing_stats()
    {
        $localidad = Mockery::mock(Localidad::class);
        $localidad->shouldReceive('getAttribute')->with('carto')->andReturn(true);
        $localidad->shouldReceive('getAttribute')->with('listado')->andReturn(true);
        $localidad->shouldReceive('getAttribute')->with('segmentadolistado')->andReturn(false);
        $localidad->shouldReceive('getAttribute')->with('segmentadolados')->andReturn(false);
        $localidad->shouldReceive('getAttribute')->with('codigo')->andReturn('0200101001');

        // Mock DB queries
        DB::shouldReceive('table')
            ->with('e0200101001.listado')
            ->once()
            ->andReturnSelf();
            
        DB::shouldReceive('select')
            ->once()
            ->andReturnSelf();
            
        DB::shouldReceive('value')
            ->with('total')
            ->once()
            ->andReturn(5);

        $result = $this->service->getProcessingStats($localidad);

        $this->assertIsArray($result);
        $this->assertTrue($result['has_carto']);
        $this->assertTrue($result['has_listado']);
        $this->assertFalse($result['has_segmentation']);
        $this->assertFalse($result['has_sides']);
        $this->assertEquals(5, $result['radios_count']);
        $this->assertEquals(0, $result['segments_count']);
    }

    /** @test */
    public function it_invalidates_cache()
    {
        $localidad = new Localidad(['codigo' => '0200101001']);
        
        $this->cacheService
            ->shouldReceive('invalidateLocalidad')
            ->once()
            ->with('0200101001');

        $this->service->invalidateCache($localidad);
        
        // No assertions needed - we're just checking the method was called
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        $localidad = new Localidad(['codigo' => '0200101001']);
        
        $this->cacheService
            ->shouldReceive('cacheLocalidadRadios')
            ->once()
            ->andReturnUsing(function($codigo, $callback) {
                return $callback();
            });

        // Mock a database exception
        DB::shouldReceive('table')
            ->andThrow(new \Exception('Database connection failed'));

        $result = $this->service->getRadiosWithStatistics($localidad);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}