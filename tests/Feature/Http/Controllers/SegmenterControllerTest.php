<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use App\Model\Archivo;
use App\Model\Provincia;
use App\Model\Departamento;
use App\Model\Localidad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SegmenterControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_requires_authentication()
    {
        $response = $this->get('/segmenter');
        $response->assertRedirect('/login');
    }

    public function test_index_displays_view_when_authenticated()
    {
        $response = $this->actingAs($this->user)
                         ->get('/segmenter');

        $response->assertStatus(200);
        $response->assertViewIs('segmenter/index');
        $response->assertViewHas('data');
        $response->assertViewHas('epsgs');
    }

    public function test_index_has_correct_epsg_data()
    {
        $response = $this->actingAs($this->user)
                         ->get('/segmenter');

        $epsgs = $response->viewData('epsgs');
        
        $this->assertArrayHasKey('epsg:22182', $epsgs);
        $this->assertArrayHasKey('epsg:22183', $epsgs);
        $this->assertArrayHasKey('epsg:22184', $epsgs);
        $this->assertArrayHasKey('epsg:22185', $epsgs);
        $this->assertArrayHasKey('epsg:22186', $epsgs);
        $this->assertArrayHasKey('epsg:22187', $epsgs);
        $this->assertArrayHasKey('sr-org:8333', $epsgs);
    }

    public function test_store_requires_authentication()
    {
        $response = $this->post('/segmenter');
        $response->assertRedirect('/login');
    }

    public function test_store_with_no_files_returns_view()
    {
        $response = $this->actingAs($this->user)
                         ->post('/segmenter', [
                             'epsg_id' => 'epsg:22183'
                         ]);

        $response->assertStatus(200);
        $response->assertViewIs('segmenter/index');
    }

    public function test_store_sets_default_epsg_when_none_provided()
    {
        $response = $this->actingAs($this->user)
                         ->post('/segmenter');

        $response->assertStatus(200);
        $response->assertSessionHas('flash_notification');
        
        // Check that default EPSG is used
        $flashData = session('flash_notification');
        $this->assertStringContains('SRS: epsg:22183', $flashData[0]['message']);
    }

    public function test_store_with_c1_file_processes_correctly()
    {
        Storage::fake('local');
        
        $file = UploadedFile::fake()->create('test_c1.csv', 1000);
        
        // Mock the Archivo::cargar method
        $mockArchivo = $this->createMock(Archivo::class);
        $mockArchivo->expects($this->once())
                   ->method('procesar')
                   ->willReturn(true);
        $mockArchivo->procesado = true;
        $mockArchivo->expects($this->once())
                   ->method('moverData')
                   ->willReturn('test_codaglo');

        $this->mock(Archivo::class, function ($mock) use ($mockArchivo) {
            $mock->shouldReceive('cargar')
                 ->once()
                 ->andReturn($mockArchivo);
        });

        $response = $this->actingAs($this->user)
                         ->post('/segmenter', [
                             'c1' => $file,
                             'epsg_id' => 'epsg:22183'
                         ]);

        $response->assertStatus(200);
        $response->assertSessionHas('flash_notification');
    }

    public function test_store_with_shp_file_processes_correctly()
    {
        Storage::fake('local');
        
        $shpFile = UploadedFile::fake()->create('test.shp', 1000);
        $shxFile = UploadedFile::fake()->create('test.shx', 500);
        $dbfFile = UploadedFile::fake()->create('test.dbf', 500);
        $prjFile = UploadedFile::fake()->create('test.prj', 100);

        // Mock the Archivo::cargar method for SHP files
        $mockArchivo = $this->createMock(Archivo::class);
        $mockArchivo->tipo = 'shape';
        $mockArchivo->tabla = 'test_table';
        $mockArchivo->expects($this->once())
                   ->method('procesar')
                   ->willReturn('Success message');
        $mockArchivo->expects($this->once())
                   ->method('pasarData')
                   ->willReturn([]);
        $mockArchivo->expects($this->once())
                   ->method('save');

        $this->mock(Archivo::class, function ($mock) use ($mockArchivo) {
            $mock->shouldReceive('cargar')
                 ->once()
                 ->andReturn($mockArchivo);
        });

        $response = $this->actingAs($this->user)
                         ->post('/segmenter', [
                             'shp' => $shpFile,
                             'shx' => $shxFile,
                             'dbf' => $dbfFile,
                             'prj' => $prjFile,
                             'epsg_id' => 'epsg:22183'
                         ]);

        $response->assertStatus(200);
        $response->assertSessionHas('flash_notification');
    }

    public function test_store_with_caba_epsg_sets_correct_srid()
    {
        $response = $this->actingAs($this->user)
                         ->post('/segmenter', [
                             'epsg_id' => 'sr-org:8333'
                         ]);

        $response->assertStatus(200);
        $response->assertSessionHas('flash_notification');
        
        $flashData = session('flash_notification');
        $this->assertStringContains('SRS: sr-org:8333', $flashData[0]['message']);
    }

    public function test_store_with_pxrad_file_creates_geographic_entities()
    {
        Storage::fake('local');
        
        $pxradFile = UploadedFile::fake()->create('test_pxrad.dbf', 2000);

        // Mock the Archivo and database operations
        $mockArchivo = $this->createMock(Archivo::class);
        $mockArchivo->tipo = 'pxrad/dbf';
        $mockArchivo->tabla = 'test_pxrad_table';
        $mockArchivo->expects($this->once())
                   ->method('procesar')
                   ->willReturn(true);

        $this->mock(Archivo::class, function ($mock) use ($mockArchivo) {
            $mock->shouldReceive('cargar')
                 ->once()
                 ->andReturn($mockArchivo);
        });

        // Mock MyDB methods
        $this->mock('App\MyDB', function ($mock) {
            $mock->shouldReceive('getCodProv')->andReturn('06');
            $mock->shouldReceive('getDataProv')->andReturn((object)[
                'codigo' => '06',
                'nombre' => 'Buenos Aires'
            ]);
            $mock->shouldReceive('getDatadepto')->andReturn([]);
        });

        $response = $this->actingAs($this->user)
                         ->post('/segmenter', [
                             'pxrad' => $pxradFile,
                             'epsg_id' => 'epsg:22183'
                         ]);

        $response->assertStatus(200);
        $response->assertSessionHas('flash_notification');
    }

    public function test_store_with_tabla_segmentos_file_processes_correctly()
    {
        Storage::fake('local');
        
        $tablaSegmentosFile = UploadedFile::fake()->create('tabla_segmentos.csv', 1500);

        $mockArchivo = $this->createMock(Archivo::class);
        $mockArchivo->nombre_original = 'tabla_segmentos.csv';
        $mockArchivo->procesado = true;
        $mockArchivo->expects($this->once())
                   ->method('procesar');
        $mockArchivo->expects($this->once())
                   ->method('moverData')
                   ->willReturn('test_esquema');

        $this->mock(Archivo::class, function ($mock) use ($mockArchivo) {
            $mock->shouldReceive('cargar')
                 ->once()
                 ->andReturn($mockArchivo);
        });

        $response = $this->actingAs($this->user)
                         ->post('/segmenter', [
                             'tabla_segmentos' => $tablaSegmentosFile,
                             'epsg_id' => 'epsg:22183'
                         ]);

        $response->assertStatus(200);
        $response->assertSessionHas('flash_notification');
    }

    public function test_store_handles_file_processing_errors_gracefully()
    {
        Storage::fake('local');
        
        $file = UploadedFile::fake()->create('invalid_file.csv', 100);

        $this->mock(Archivo::class, function ($mock) {
            $mock->shouldReceive('cargar')
                 ->once()
                 ->andReturn(false);
        });

        $response = $this->actingAs($this->user)
                         ->post('/segmenter', [
                             'c1' => $file,
                             'epsg_id' => 'epsg:22183'
                         ]);

        $response->assertStatus(200);
        $response->assertSessionHas('flash_notification');
        
        $flashData = session('flash_notification');
        $hasErrorMessage = collect($flashData)->contains(function ($notification) {
            return strpos($notification['message'], 'Error en el modelo') !== false;
        });
        
        $this->assertTrue($hasErrorMessage);
    }

    public function test_store_validates_epsg_id_parameter()
    {
        $validEpsgIds = [
            'epsg:22182', 'epsg:22183', 'epsg:22184', 
            'epsg:22185', 'epsg:22186', 'epsg:22187', 
            'sr-org:8333'
        ];

        foreach ($validEpsgIds as $epsgId) {
            $response = $this->actingAs($this->user)
                             ->post('/segmenter', [
                                 'epsg_id' => $epsgId
                             ]);

            $response->assertStatus(200);
            $response->assertSessionHas('flash_notification');
            
            $flashData = session('flash_notification');
            $this->assertStringContains("SRS: {$epsgId}", $flashData[0]['message']);
            
            // Clear session for next iteration
            session()->flush();
        }
    }

    public function test_unauthenticated_user_cannot_access_store()
    {
        $response = $this->post('/segmenter', [
            'epsg_id' => 'epsg:22183'
        ]);

        $response->assertRedirect('/login');
    }

    public function test_store_returns_error_message_for_unauthenticated_requests()
    {
        // Test direct access without middleware (bypassing auth middleware for this test)
        Auth::logout();
        
        $controller = new \App\Http\Controllers\SegmenterController();
        $request = new \Illuminate\Http\Request();
        
        $result = $controller->store($request);
        
        $this->assertEquals('No tiene permiso para cargar o no esta logueado', $result);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}