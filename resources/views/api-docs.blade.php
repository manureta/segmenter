@extends('layouts.app')

@section('title', '- Manual de Uso API')

@section('header_scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
<link href="{{ mix('css/api-docs.css') }}" rel="stylesheet">
<style>
    .api-docs-container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .api-header {
        border-bottom: 3px solid #007bff;
        margin-bottom: 30px;
        padding-bottom: 20px;
    }
    
    .api-header h1 {
        color: #007bff;
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .status-indicator {
        position: fixed;
        top: 80px;
        right: 20px;
        background-color: #28a745;
        color: white;
        padding: 10px 15px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
        z-index: 1000;
    }
    
    .api-content h2 {
        color: #495057;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 10px;
        margin-top: 40px;
        margin-bottom: 20px;
    }
    
    .api-content h3 {
        color: #6c757d;
        margin-top: 30px;
    }
    
    .api-content h4 {
        color: #6f42c1;
        margin-top: 25px;
    }
    
    .api-content code {
        background-color: #f8f9fa;
        color: #e83e8c;
        padding: 2px 4px;
        border-radius: 3px;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    }
    
    .api-content pre {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 15px;
        overflow-x: auto;
        margin: 15px 0;
    }
    
    .api-content pre code {
        background: none;
        color: inherit;
        padding: 0;
    }
    
    .endpoint-method {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-weight: bold;
        font-size: 12px;
        margin-right: 10px;
        color: white;
    }
    
    .method-get { background-color: #28a745; }
    .method-post { background-color: #007bff; }
    .method-delete { background-color: #dc3545; }
    
    .api-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    .api-content th, .api-content td {
        border: 1px solid #dee2e6;
        padding: 12px;
        text-align: left;
    }
    
    .api-content th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .toc {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .toc h3 {
        margin-top: 0;
        color: #495057;
    }
    
    .toc ul {
        list-style-type: none;
        padding-left: 0;
    }
    
    .toc li {
        margin: 5px 0;
    }
    
    .toc a {
        color: #007bff;
        text-decoration: none;
    }
    
    .toc a:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 768px) {
        .api-docs-container {
            margin: 10px;
            padding: 15px;
        }
        
        .api-header h1 {
            font-size: 2rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="status-indicator">
        🟢 API Online - localhost:8090
    </div>
    
    <div class="api-docs-container">
        <div class="api-header">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Manual API</li>
                </ol>
            </nav>
            
            <h1>📖 {{ $title }}</h1>
            <p class="lead">Documentación completa de la API REST del Sistema de Segmentación INDEC</p>
            
            <div class="alert alert-info" role="alert">
                <strong>🚀 Servidor activo:</strong> La API está disponible en <code>http://localhost:8090/api/v1</code>
            </div>
        </div>
        
        <div class="toc">
            <h3>📋 Tabla de contenidos</h3>
            <ul>
                <li><a href="#descripción">Descripción</a></li>
                <li><a href="#base-url">Base URL</a></li>
                <li><a href="#endpoints-disponibles">Endpoints Disponibles</a></li>
                <li><a href="#ejemplos-de-uso">Ejemplos de Uso</a></li>
                <li><a href="#códigos-de-respuesta-http">Códigos de Respuesta</a></li>
                <li><a href="#panel-de-administración">Panel de Administración</a></li>
            </ul>
        </div>
        
        <div class="api-content">
            {!! $htmlContent !!}
        </div>
        
        <div class="alert alert-success mt-4" role="alert">
            <h5 class="alert-heading">¿Necesitas ayuda?</h5>
            <p>Si tienes dudas sobre el uso de la API o encuentras algún problema, puedes:</p>
            <hr>
            <p class="mb-0">
                <a href="{{ url('/') }}" class="btn btn-sm btn-outline-success">Volver al panel principal</a>
                <a href="mailto:soporte@indec.gob.ar" class="btn btn-sm btn-outline-primary ml-2">Contactar soporte</a>
            </p>
        </div>
    </div>
</div>

<script src="{{ mix('js/api-docs.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mejorar el renderizado de métodos HTTP
    const content = document.querySelector('.api-content');
    if (content) {
        let html = content.innerHTML;
        
        // Detectar y resaltar métodos HTTP
        html = html.replace(/\b(GET|POST|PUT|DELETE)\s+([^\n<]+)/g, function(match, method, url) {
            const methodClass = 'method-' + method.toLowerCase();
            return '<p><span class="endpoint-method ' + methodClass + '">' + method + '</span>' + url + '</p>';
        });
        
        content.innerHTML = html;
    }
    
    // Smooth scroll para los enlaces del TOC
    document.querySelectorAll('.toc a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>
@endsection