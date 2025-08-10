# Documentación de Funcionalidades - Segmentador INDEC

## Resumen Ejecutivo

El **Segmentador INDEC** es una aplicación web Laravel diseñada para procesar datos geográficos y realizar segmentación asistida para el Censo de Población, Hogares y Viviendas de Argentina. La aplicación permite cargar archivos geoespaciales (shapefiles, e00, CSV) y generar segmentos censales equilibrados.

## Arquitectura del Sistema

### Backend
- **Framework**: Laravel 9.x con PHP 8.1+
- **Base de Datos**: PostgreSQL con extensión PostGIS
- **Procesamiento GIS**: GDAL, ogr2ogr, funciones PostGIS
- **Colas**: Laravel Horizon con Redis
- **Permisos**: Spatie Laravel Permission

### Frontend
- **Build System**: Vite + Vue.js 3
- **UI Components**: Bootstrap 4, DataTables
- **Visualización**: SVG dinámico, Cytoscape.js, Chart.js
- **Maps**: Integración con sistemas de coordenadas EPSG

## Funcionalidades Principales

### 🗺️ **Carga y Procesamiento de Datos Geográficos**

#### Tipos de Archivos Soportados
1. **Shapefiles (.shp + .dbf + .shx + .prj)**
   - Formato vectorial estándar
   - Soporte para múltiples sistemas de coordenadas
   - Procesamiento automático de geometrías

2. **Archivos E00** 
   - Formato ESRI ARC/INFO Export
   - Conversión automática a formato PostgreSQL

3. **CSV/DBF**
   - Listados de viviendas (C1)
   - Datos PxRad (Provincia x Radio)
   - Tabla de segmentos completa

#### Sistemas de Coordenadas Soportados
```
EPSG:22182 - POSGAR 94/Argentina 2 (Patagonia)
EPSG:22183 - POSGAR 94/Argentina 3 (NOA/Cuyo)
EPSG:22184 - POSGAR 94/Argentina 4 (Centro)
EPSG:22185 - POSGAR 94/Argentina 5 (Litoral/Buenos Aires)
EPSG:22186 - POSGAR 94/Argentina 6 (Corrientes)
EPSG:22187 - POSGAR 94/Argentina 7 (Misiones)
SR-ORG:8333 - Gauss Krugger BA (CABA)
```

### 📊 **API Endpoints Disponibles**

#### API de Localidades (`/api/v1/localidades`)
```
GET    /api/v1/localidades           - Lista todas las localidades
GET    /api/v1/localidades/{codigo}  - Detalle de localidad específica
GET    /api/v1/localidades/{codigo}/radios - Radios de una localidad
GET    /api/v1/localidades/{codigo}/svg    - Visualización SVG
POST   /api/v1/localidades/bulk-stats      - Estadísticas masivas
DELETE /api/v1/localidades/{codigo}/cache  - Limpiar cache
```

#### API de Documentación
```
GET /api-docs - Documentación interactiva de la API
GET /api/health - Estado de salud del sistema
```

### 🔧 **Panel Administrativo**

#### Gestión de Entidades Geográficas
- **Provincias**: CRUD completo con permisos
- **Departamentos**: Administración por provincia
- **Localidades**: Gestión detallada con estadísticas
- **Tipos de Radio**: Clasificación de áreas censales
- **Usuarios**: Control de acceso y permisos

#### Rutas Admin
```
/admin/provincias    - Gestión de provincias
/admin/departamentos - Administración departamental
/admin/localidades   - Panel de localidades
/admin/users         - Control de usuarios
/admin/admin-users   - Administradores del sistema
```

### 🗃️ **Estructura de Base de Datos**

#### Tablas Principales
- `provincia` - Divisiones provinciales
- `departamentos` - Subdivisiones departamentales  
- `localidad` - Centros poblados
- `aglomerados` - Conglomerados urbanos
- `radios` - Áreas censales básicas
- `fracciones` - Subdivisiones de radios
- `archivos` - Registro de cargas de archivos

#### Esquemas Temporales
- `e{código}` - Esquemas dinámicos por localidad
- Tablas `arc` - Geometrías de arcos/líneas
- Tablas `lab` - Etiquetas/polígonos
- Tablas `listado` - Datos de viviendas

### ⚙️ **Flujo de Procesamiento**

#### 1. Carga de Archivos (SegmenterController)
```
1. Usuario sube archivos (SHP/E00/CSV)
2. Sistema valida formatos y proyecciones
3. Creación de esquema temporal e{código}
4. Conversión GDAL/ogr2ogr a PostGIS
5. Verificación de integridad geométrica
```

#### 2. Procesamiento GIS
```
1. Carga de topología con funciones PostGIS
2. Generación de adyacencias entre segmentos  
3. Cálculo de conteos de viviendas
4. Aplicación de algoritmos de equilibrado
5. Creación de segmentos optimizados
```

#### 3. Segmentación Asistida
```
Algoritmos implementados:
- segmentar_equilibrado() - Distribución balanceada
- segmentar_excedidos_ffrr() - Manejo de excesos
- juntar_segmentos() - Unión de áreas pequeñas
- muestrear() - Selección estadística
```

## Archivos de Ejemplo Encontrados

### 📁 **Shapefiles de Prueba** (`storage/app/segmentador/`)
Se encontraron **200+ archivos** de ejemplo incluyendo:

**Shapefiles Completos:**
- `t_1khLVKygzccA41RDYYIW1lDroFEH2o8wbGzZ4UaP.*` (POSGAR 94 Zona 5)
- `t_28umNnEn9G4TqWMnclYNRoYb5vcrWzua0uDYW3Ro.*` 
- `t_2hV4hp2uVI8bFn8ZmUk5mozXVmyIlE4rPJLbgh7J.*`
- `t_4lPeH8OHELA0ZOoWhuZYBGx8CswYSyQFzjrAmmdp.*`

**Archivos CSV de Ejemplo:**
- `t_1chmohwfWHv1Zzo59kRrXfWnAbKzcQ5TZWMzPwpA.csv` (11MB)
- `t_2NgCPPPU4Q3xAR765d5v2zpixNsLybODO5iWMPfK.csv`
- `t_4zWS35ZeN6dKiqU8JqPTdQFNa4vKLGvi1FYExdEV.csv`

**Proyecciones Identificadas:**
- **8333.prj**: Gauss Krugger Buenos Aires (CABA)
- **POSGAR 94**: Múltiples zonas argentinas
- **Campo Inchauspe**: Sistema geodésico histórico

## Casos de Uso Típicos

### 🎯 **Segmentación de Localidad**
1. Cargar shapefile de manzanas (.shp + .dbf + .shx + .prj)
2. Cargar listado C1 de viviendas (.csv)  
3. Cargar archivo PxRad (.dbf)
4. Seleccionar EPSG apropiado para la región
5. Ejecutar segmentación equilibrada
6. Generar visualizaciones SVG
7. Exportar resultados para QGIS

### 📈 **Análisis Estadístico**
1. Consultar API de localidades
2. Obtener conteos por radio censal
3. Generar reportes de distribución
4. Visualizar mediante gráficos interactivos

### 🗺️ **Visualización Cartográfica**
1. Generar SVG dinámico por localidad
2. Mostrar segmentos coloreados por densidad
3. Overlays de información censal
4. Integración con sistemas GIS externos

## Configuración de Desarrollo

### Datos de Prueba Disponibles
Los archivos en `storage/app/segmentador/` pueden usarse para:
- **Testing de funcionalidades**
- **Desarrollo de nuevas features**
- **Validación de algoritmos**
- **Casos de prueba automatizados**

### Próximos Pasos Recomendados
1. ✅ Crear tests automatizados con datos reales
2. ⏳ Implementar visualizador web de shapefiles
3. ⏳ API de exportación a múltiples formatos
4. ⏳ Integración con QGIS Plugin mejorada
5. ⏳ Dashboard de monitoreo de procesamiento