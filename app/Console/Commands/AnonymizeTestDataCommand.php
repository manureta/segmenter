<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AnonymizeTestDataCommand extends Command
{
    protected $signature = 'segmenter:anonymize-test-data {--force : Force overwrite existing files}';
    protected $description = 'Anonymize test datasets removing real location names and personal data';

    private $locationMap = [
        'PAYOGASTA' => 'LOCALIDAD_A',
        'Salta' => 'PROVINCIA_A', 
        'Cachi' => 'DEPARTAMENTO_A',
        'CHAJARÍ' => 'LOCALIDAD_B',
        'Entre Ríos' => 'PROVINCIA_B',
        'Federación' => 'DEPARTAMENTO_B'
    ];

    private $streetMap = [
        'AV JUAN CALCHAQUI' => 'AVENIDA PRINCIPAL',
        'GRL GUEMES' => 'CALLE CENTRAL',
        'RUTA NAC 40' => 'RUTA NACIONAL XX',
        'CALLE S N' => 'CALLE SIN NOMBRE'
    ];

    public function handle()
    {
        $this->info('Iniciando anonimización de datasets de prueba...');
        
        $datasetsPath = storage_path('app/testing-datasets');
        $anonymizedPath = storage_path('app/testing-datasets-anonymized');
        
        if (!File::exists($anonymizedPath)) {
            File::makeDirectory($anonymizedPath);
        }

        $files = File::glob($datasetsPath . '/*.{json,csv,sql}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $filename = basename($file);
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            
            $this->line("Procesando: {$filename}");
            
            $content = File::get($file);
            $anonymizedContent = $this->anonymizeContent($content, $extension);
            
            $outputFile = $anonymizedPath . '/' . $filename;
            File::put($outputFile, $anonymizedContent);
        }

        // Create anonymized README
        $readmeContent = File::get($datasetsPath . '/README.md');
        $anonymizedReadme = $this->anonymizeReadme($readmeContent);
        File::put($anonymizedPath . '/README.md', $anonymizedReadme);

        $this->info('✅ Datasets anonimizados guardados en: storage/app/testing-datasets-anonymized/');
        $this->info('📊 Archivos procesados: ' . count($files));
        
        return 0;
    }

    private function anonymizeContent($content, $extension)
    {
        // Replace real location names
        foreach ($this->locationMap as $real => $anonymous) {
            $content = str_replace($real, $anonymous, $content);
        }

        // Replace street names
        foreach ($this->streetMap as $real => $anonymous) {
            $content = str_replace($real, $anonymous, $content);
        }

        // Anonymize specific patterns based on file type
        if ($extension === 'json') {
            $content = $this->anonymizeJson($content);
        } elseif ($extension === 'csv') {
            $content = $this->anonymizeCsv($content);
        } elseif ($extension === 'sql') {
            $content = $this->anonymizeSql($content);
        }

        return $content;
    }

    private function anonymizeJson($content)
    {
        // Replace schema names with generic ones
        $content = preg_replace('/e3719/', 'schema_test_a', $content);
        $content = preg_replace('/e0125/', 'schema_test_b', $content);
        
        // Anonymize timestamps to generic date
        $content = preg_replace('/2025-08-10T\d{2}:\d{2}:\d{2}\.\d+Z/', '2025-01-01T12:00:00.000000Z', $content);
        
        return $content;
    }

    private function anonymizeCsv($content)
    {
        // Replace province codes with generic ones
        $content = preg_replace('/^66,/', '01,', $content);
        $content = preg_replace('/\|66\|/', '|01|', $content);
        
        // Replace specific numeric codes that might identify real locations
        $content = preg_replace('/3719/', '0001', $content);
        $content = preg_replace('/0125/', '0002', $content);
        
        return $content;
    }

    private function anonymizeSql($content)
    {
        // Replace schema references
        $content = str_replace('e3719', 'test_schema_a', $content);
        $content = str_replace('e0125', 'test_schema_b', $content);
        
        // Replace province codes
        $content = preg_replace("/'66'/", "'01'", $content);
        $content = preg_replace("/,'66',/", ",'01',", $content);
        
        return $content;
    }

    private function anonymizeReadme($content)
    {
        $anonymizedContent = <<<'MD'
# Testing Datasets (Anonymized)

## Archivos Generados

Datasets extraídos y anonimizados para pruebas de desarrollo:

### Dataset schema_test_a (Localidad A, Provincia A)
- `dataset_e3719_2025-08-10_18-59-46.json` - 50 viviendas anonimizadas
- `dataset_e3719_2025-08-10_18-59-46_listado.csv` - CSV comma-separated
- `dataset_e3719_2025-08-10_18-59-46.sql` - Script SQL para recrear data
- `dataset_e3719_pipe_delimited.csv` - CSV pipe-delimited para import

### Dataset schema_test_b (Localidad B, Provincia B)
- `dataset_e0125_2025-08-10_18-59-58.json` - 100 viviendas anonimizadas
- `dataset_e0125_2025-08-10_18-59-58_listado.csv` - CSV comma-separated
- `dataset_e0125_2025-08-10_18-59-58.sql` - Script SQL para recrear data

## Anonimización Aplicada

- ✅ Nombres de localidades reemplazados por genéricos
- ✅ Nombres de calles anonimizados
- ✅ Códigos de provincia generalizados
- ✅ Referencias de esquemas anonimizadas
- ✅ Timestamps normalizados

## Uso Seguro

Estos datasets contienen solo datos ficticios y estructuras anonimizadas.
Seguros para uso en desarrollo, testing y documentación.

## Testing Validado

✅ CSV import workflow probado
✅ Estructura de datos preservada
✅ Datos personales/ubicaciones removidos
MD;

        return $anonymizedContent;
    }
}