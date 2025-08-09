<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Model\LocalidadModern as Localidad;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class LocalidadApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_localidades()
    {
        Localidad::factory()->create([
            'codigo' => '0200101001',
            'nombre' => 'Test Localidad 1'
        ]);

        Localidad::factory()->create([
            'codigo' => '0200101002', 
            'nombre' => 'Test Localidad 2'
        ]);

        $response = $this->getJson('/api/v1/localidades');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['codigo', 'nombre']
                    ],
                    'links',
                    'meta'
                ]);
    }

    /** @test */
    public function it_can_show_single_localidad()
    {
        $localidad = Localidad::factory()->create([
            'codigo' => '0200101001',
            'nombre' => 'Test Localidad'
        ]);

        $response = $this->getJson("/api/v1/localidades/{$localidad->codigo}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'localidad' => ['codigo', 'nombre'],
                    'processing_stats',
                    'required_tables'
                ])
                ->assertJson([
                    'localidad' => [
                        'codigo' => '0200101001',
                        'nombre' => 'Test Localidad'
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_localidad_radios()
    {
        $localidad = Localidad::factory()->create([
            'codigo' => '0200101001',
            'nombre' => 'Test Localidad'
        ]);

        $response = $this->getJson("/api/v1/localidades/{$localidad->codigo}/radios");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'count',
                    'localidad' => ['codigo', 'nombre']
                ]);
    }

    /** @test */
    public function it_can_get_localidad_svg()
    {
        $localidad = Localidad::factory()->create([
            'codigo' => '0200101001',
            'nombre' => 'Test Localidad'
        ]);

        $response = $this->getJson("/api/v1/localidades/{$localidad->codigo}/svg");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'svg',
                    'localidad' => ['codigo', 'nombre']
                ]);
    }

    /** @test */
    public function it_can_clear_localidad_cache()
    {
        $localidad = Localidad::factory()->create([
            'codigo' => '0200101001',
            'nombre' => 'Test Localidad'
        ]);

        $response = $this->deleteJson("/api/v1/localidades/{$localidad->codigo}/cache");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'localidad' => ['codigo', 'nombre']
                ]);
    }

    /** @test */
    public function it_can_get_bulk_stats()
    {
        $localidad1 = Localidad::factory()->create(['codigo' => '0200101001']);
        $localidad2 = Localidad::factory()->create(['codigo' => '0200101002']);

        $response = $this->postJson('/api/v1/localidades/bulk-stats', [
            'codigos' => [$localidad1->codigo, $localidad2->codigo]
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['codigo', 'nombre', 'stats']
                    ]
                ]);
    }

    /** @test */
    public function it_can_filter_localidades()
    {
        Localidad::factory()->create(['codigo' => '0200101001', 'nombre' => 'Buenos Aires']);
        Localidad::factory()->create(['codigo' => '0200101002', 'nombre' => 'Córdoba']);

        $response = $this->getJson('/api/v1/localidades?search=Buenos');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Buenos Aires', $data[0]['nombre']);
    }

    /** @test */
    public function it_requires_authentication_for_protected_routes()
    {
        // Remove authentication
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/localidades');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_bulk_stats_request()
    {
        // Too many codes
        $codes = array_fill(0, 60, '0200101001');
        
        $response = $this->postJson('/api/v1/localidades/bulk-stats', [
            'codigos' => $codes
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['codigos']);
    }

    /** @test */
    public function it_handles_404_for_non_existent_localidad()
    {
        $response = $this->getJson('/api/v1/localidades/9999999999');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_paginate_results()
    {
        // Create 25 localidades
        for ($i = 1; $i <= 25; $i++) {
            Localidad::factory()->create([
                'codigo' => sprintf('020010100%d', $i),
                'nombre' => "Localidad {$i}"
            ]);
        }

        $response = $this->getJson('/api/v1/localidades?per_page=10');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(10, $data['data']);
        $this->assertEquals(25, $data['meta']['total']);
        $this->assertEquals(3, $data['meta']['last_page']);
    }
}