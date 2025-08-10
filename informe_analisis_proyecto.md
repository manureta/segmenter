# Informe de Análisis - Proyecto Segmentador INDEC

## Resumen Ejecutivo
**Proyecto Mandarina** es una aplicación web Laravel diseñada para el segmentador asistido del Censo de Población, Hogares y Viviendas 2020 de Argentina. Funciona como interfaz web para el **Segmentador-core** y permite la carga de datos geoestadísticos necesarios para el plugin de QGIS.

## Tecnologías y Arquitectura

### Backend
- **Framework**: Laravel 8.x 
- **PHP**: 8.1+
- **Base de datos**: PostgreSQL con PostGIS
- **Dependencias clave**: 
  - Doctrine DBAL para esquemas
  - Spatie Laravel Permission (roles/permisos)
  - Laravel Horizon (colas)
  - Livewire para componentes reactivos
  - Maatwebsite Excel para importación/exportación

### Frontend  
- **Build**: Laravel Mix + Webpack
- **CSS**: Bootstrap 4 + Sass
- **JavaScript**: jQuery, DataTables, Cytoscape.js, Chart.js
- **Visualización**: SVG generado dinámicamente, mapas interactivos

### Infraestructura
- **GIS**: GDAL, ogr2ogr, pgdbf
- **Node.js**: v12 para build de assets
- **Supervisor**: Para procesos en background

## Estructura del Proyecto

### Modelos de Datos Principales
- **Provincia**: Divisiones administrativas principales
- **Departamento**: Subdivisiones provinciales (Comuna/Partido según provincia)
- **Localidad**: Entidades territoriales con soporte GIS
- **Radio**: Unidades censales básicas
- **Fracción**: Divisiones territoriales intermedias
- **Segmento**: Unidades de segmentación resultado del procesamiento

### Funcionalidades Core
1. **Gestión Territorial**: Jerarquía Provincia → Departamento → Localidad → Radio
2. **Procesamiento GIS**: Integración con servicios geoestadísticos INDEC
3. **Segmentación Automática**: Algoritmos de división territorial equilibrada
4. **Visualización**: Generación SVG dinámica de cartografía
5. **Importación/Exportación**: CSV, Shapefile, múltiples formatos

### Componentes Especializados

#### Controllers Principales
- `SegmenterController`: Núcleo del sistema de segmentación
- `SetupController`: Configuración y preparación de esquemas
- `LocalidadController`: Gestión de localidades con capacidades GIS
- `SegmentacionController`: Procesamiento de segmentación

#### Funcionalidades GIS
- Esquemas dinámicos por entidad (`e{codigo_localidad}`)
- Integración con servicios WMS/WFS del INDEC
- Generación automática de topologías
- Soporte múltiples sistemas de coordenadas POSGAR

## Estado del Proyecto

### Branch Strategy
- **master**: Desarrollo principal
- **dev**: Integración 
- **uat**: Testing
- **prd**: Producción

### Cambios Recientes
- Eliminación de campos comentados
- Actualizaciones de seguridad (loader-utils)
- Mejoras en manejo de permisos de archivos
- Implementación de timeouts para servicios OGC

## Arquitectura de Base de Datos

### Características PostgreSQL/PostGIS
- **Esquemas dinámicos**: Un esquema por localidad procesada
- **Funciones especializadas**: 140+ migraciones con funciones PL/pgSQL
- **Índices espaciales**: Optimización para consultas geográficas
- **Permisos granulares**: Sistema de roles integrado con Laravel

### Tablas Core
- Sistema de usuarios con roles administrativos
- Estructura jerárquica territorial
- Tablas de segmentación dinámicas
- Logs y auditoria de procesos

## Observaciones Técnicas

### Fortalezas
- Arquitectura robusta con separación de responsabilidades
- Integración profunda con tecnologías GIS
- Sistema de permisos granular
- Submodulo independiente (segmentacion-core)
- Documentación técnica extensa

### Áreas de Atención
- Dependencia de servicios externos (geoservicios INDEC)
- Complejidad de configuración inicial
- Gran cantidad de archivos temporales en storage
- Múltiples esquemas de coordenadas a gestionar

## Seguridad
El código analizado no presenta características maliciosas. Es un sistema legítimo de procesamiento geoestadístico para censo poblacional.

## Conclusiones
El proyecto representa una solución técnica sólida para el procesamiento geoestadístico del censo poblacional argentino, con arquitectura escalable y funcionalidades especializadas para el dominio geográfico.

---
*Informe generado el: 2025-08-09*
*Directorio analizado: /media/manuel/thorin/laburo/laburo/indec/segmenter*