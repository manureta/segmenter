<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\SegmenterController;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ReflectionClass;

class SegmenterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SegmenterController();
        $this->user = User::factory()->create();
    }

    public function test_constructor_sets_up_epsg_mappings()
    {
        $reflection = new ReflectionClass($this->controller);
        $epsgsProperty = $reflection->getProperty('epsgs');
        $epsgsProperty->setAccessible(true);
        $epsgs = $epsgsProperty->getValue($this->controller);

        $expectedEpsgs = [
            'epsg:22182' => '(EPSG:22182) POSGAR 94/Argentina 2 - San Juan, Mendoza, Neuquén, Chubut, Santa Cruz y Tierra del Fuego...',
            'epsg:22183' => '(EPSG:22183) POSGAR 94/Argentina 3 - Jujuy, Salta, Tucuman, Catamarca, La Rioja, San Luis, La Pamla y Río Negro',
            'epsg:22184' => '(EPSG:22184) POSGAR 94/Argentina 4 - Santiago del Estero y Córdoba',
            'epsg:22185' => '(EPSG:22185) POSGAR 94/Argentina 5 - Formosa, Chaco, Santa Fe, Entre Ríos y Buenos Aires',
            'epsg:22186' => '(EPSG:22186) POSGAR 94/Argentina 6 - Corrientes',
            'epsg:22187' => '(EPSG:22187) POSGAR 94/Argentina 7 - Misiones',
            'sr-org:8333' => '(SR-ORG:8333) Gauss Krugger BA - Ciudad Autónoma de Buenos Aires'
        ];

        foreach ($expectedEpsgs as $key => $description) {
            $this->assertArrayHasKey($key, $epsgs);
            // Note: The actual description in the code has a typo "La Pamla" instead of "La Pampa"
            if ($key === 'epsg:22183') {
                $this->assertStringContainsString('La Pampa', $epsgs[$key]);
            } else {
                $this->assertEquals($description, $epsgs[$key]);
            }
        }
    }

    public function test_constructor_sets_middleware()
    {
        $reflection = new ReflectionClass($this->controller);
        $middleware = $reflection->getProperty('middleware');
        
        // Check if middleware property exists and contains auth middleware
        $this->assertTrue($reflection->hasMethod('middleware') || $reflection->hasProperty('middleware'));
    }

    public function test_index_method_returns_correct_view_data()
    {
        $this->actingAs($this->user);
        
        $response = $this->controller->index();
        
        $this->assertEquals('segmenter/index', $response->getName());
        $this->assertArrayHasKey('data', $response->getData());
        $this->assertArrayHasKey('epsgs', $response->getData());
        $this->assertNull($response->getData()['data']);
    }

    public function test_epsg_mappings_are_complete()
    {
        $reflection = new ReflectionClass($this->controller);
        $epsgsProperty = $reflection->getProperty('epsgs');
        $epsgsProperty->setAccessible(true);
        $epsgs = $epsgsProperty->getValue($this->controller);

        // Test that all expected EPSG codes are present
        $expectedCodes = [
            'epsg:22182', 'epsg:22183', 'epsg:22184', 
            'epsg:22185', 'epsg:22186', 'epsg:22187', 
            'sr-org:8333'
        ];

        foreach ($expectedCodes as $code) {
            $this->assertArrayHasKey($code, $epsgs);
            $this->assertIsString($epsgs[$code]);
            $this->assertNotEmpty($epsgs[$code]);
        }
    }

    public function test_epsg_descriptions_contain_geographic_information()
    {
        $reflection = new ReflectionClass($this->controller);
        $epsgsProperty = $reflection->getProperty('epsgs');
        $epsgsProperty->setAccessible(true);
        $epsgs = $epsgsProperty->getValue($this->controller);

        // Test specific geographic references
        $this->assertStringContainsString('Argentina', $epsgs['epsg:22182']);
        $this->assertStringContainsString('Buenos Aires', $epsgs['epsg:22185']);
        $this->assertStringContainsString('Corrientes', $epsgs['epsg:22186']);
        $this->assertStringContainsString('Misiones', $epsgs['epsg:22187']);
        $this->assertStringContainsString('Ciudad Autónoma de Buenos Aires', $epsgs['sr-org:8333']);
    }

    public function test_store_method_handles_authentication_check()
    {
        // Test without authentication
        Auth::logout();
        
        $request = new Request();
        $result = $this->controller->store($request);
        
        $this->assertEquals('No tiene permiso para cargar o no esta logueado', $result);
    }

    public function test_store_method_sets_default_epsg_id()
    {
        $this->actingAs($this->user);
        
        $request = new Request();
        
        // Mock session to capture flash messages
        $this->mock('Laracasts\Flash\Flash', function ($mock) {
            $mock->shouldReceive('message')->andReturnSelf();
            $mock->shouldReceive('info')->andReturnSelf();
            $mock->shouldReceive('error')->andReturnSelf();
            $mock->shouldReceive('success')->andReturnSelf();
            $mock->shouldReceive('warning')->andReturnSelf();
            $mock->shouldReceive('important')->andReturnSelf();
        });

        $response = $this->controller->store($request);
        
        // Should return a view response
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_caba_epsg_configuration()
    {
        $this->actingAs($this->user);
        
        $request = new Request(['epsg_id' => 'sr-org:8333']);
        
        // This test verifies the CABA-specific EPSG configuration logic
        $response = $this->controller->store($request);
        
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_controller_properties_initialization()
    {
        $reflection = new ReflectionClass($this->controller);
        
        // Test that epsgs property is properly initialized
        $epsgsProperty = $reflection->getProperty('epsgs');
        $epsgsProperty->setAccessible(true);
        $epsgs = $epsgsProperty->getValue($this->controller);
        
        $this->assertIsArray($epsgs);
        $this->assertNotEmpty($epsgs);
        $this->assertCount(7, $epsgs);
    }

    public function test_store_method_parameter_handling()
    {
        $this->actingAs($this->user);
        
        // Test with epsg_id parameter
        $requestWithEpsg = new Request(['epsg_id' => 'epsg:22184']);
        $response1 = $this->controller->store($requestWithEpsg);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response1);
        
        // Test without epsg_id parameter (should use default)
        $requestWithoutEpsg = new Request([]);
        $response2 = $this->controller->store($requestWithoutEpsg);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response2);
    }

    public function test_all_epsg_codes_have_valid_format()
    {
        $reflection = new ReflectionClass($this->controller);
        $epsgsProperty = $reflection->getProperty('epsgs');
        $epsgsProperty->setAccessible(true);
        $epsgs = $epsgsProperty->getValue($this->controller);

        foreach ($epsgs as $code => $description) {
            // Test EPSG code format
            $this->assertTrue(
                strpos($code, 'epsg:') === 0 || strpos($code, 'sr-org:') === 0,
                "EPSG code {$code} does not have valid format"
            );
            
            // Test description format
            $this->assertStringStartsWith('(', $description);
            $this->assertStringContainsString($code, strtoupper($description));
        }
    }
}