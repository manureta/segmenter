<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\CommonMark\CommonMarkConverter;

class ApiDocsController extends Controller
{
    public function index()
    {
        $manualPath = base_path('manual_api_uso.md');
        
        if (!file_exists($manualPath)) {
            abort(404, 'Manual de API no encontrado');
        }
        
        $markdownContent = file_get_contents($manualPath);
        
        // Convertir Markdown a HTML usando CommonMark
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        
        $htmlContent = $converter->convertToHtml($markdownContent);
        
        return view('api-docs', [
            'htmlContent' => $htmlContent,
            'title' => 'Manual de Uso API - Segmenter INDEC'
        ]);
    }
}