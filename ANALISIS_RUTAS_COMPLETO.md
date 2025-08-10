# Análisis Completo de Rutas - Segmentador INDEC

## 📊 Resumen General
- **Total de Rutas**: 252 rutas registradas
- **Métodos HTTP**: GET, POST, DELETE, PUT
- **Patrones**: API REST, Admin Panel, Web Interface

## 🗂️ Categorización de Rutas

### 🏠 **Rutas Principales / Home**
```
GET  /                     - Página principal
GET  /inicio               - Inicio alternativo  
GET  /home                 - Dashboard principal
GET  /about                - Información del sistema
GET  /gracias              - Página de agradecimiento
GET  /contact              - Contacto
GET  /projects             - Proyectos
```

### 🔐 **Autenticación y Usuarios**
```
GET  /login                - Formulario de login
POST /login                - Procesar login
GET  /logout               - Cerrar sesión
POST /logout               - Procesar logout
GET  /register             - Formulario registro
POST /register             - Procesar registro

# Password Reset
GET  /password/reset       - Solicitar reset
POST /password/email       - Enviar email reset
GET  /password/reset/{token} - Formulario con token
POST /password/reset       - Procesar reset

# Perfil
GET  /perfil               - Ver perfil usuario
POST /perfil/edit-email    - Editar email
POST /perfil/edit-password - Cambiar contraseña
POST /perfil/edit-profile-pic - Cambiar foto
POST /perfil/edit-username - Cambiar username
```

### 👨‍💼 **Panel Administrativo**
```
# Admin Users
GET  /admin/admin-users                    - Lista administradores
POST /admin/admin-users                    - Crear administrador
GET  /admin/admin-users/create             - Formulario crear
GET  /admin/admin-users/{id}/edit          - Formulario editar
POST /admin/admin-users/{id}               - Actualizar
DELETE /admin/admin-users/{id}             - Eliminar
GET  /admin/admin-users/{id}/impersonal-login - Login como usuario

# Departamentos
GET  /admin/departamentos                  - Lista departamentos
POST /admin/departamentos                  - Crear departamento  
GET  /admin/departamentos/create           - Formulario crear
GET  /admin/departamentos/{id}/edit        - Formulario editar
POST /admin/departamentos/{id}             - Actualizar
DELETE /admin/departamentos/{id}           - Eliminar
POST /admin/departamentos/bulk-destroy     - Eliminar masivo

# Localidades (doble implementación)
GET  /admin/localidades                    - Lista localidades
GET  /admin/localidads                     - Lista localidades (alt)
# ... operaciones CRUD similares para ambas

# Provincias
GET  /admin/provincia                      - Lista provincias
POST /admin/provincia                      - Crear provincia
# ... operaciones CRUD completas

# Tipos de Radio
GET  /admin/tipo-de-radios                 - Lista tipos radio
POST /admin/tipo-de-radios                 - Crear tipo
# ... operaciones CRUD completas

# Users
GET  /admin/users                          - Lista usuarios
POST /admin/users                          - Crear usuario
# ... operaciones CRUD completas

# Perfil Admin
GET  /admin/profile                        - Editar perfil admin
POST /admin/profile                        - Actualizar perfil
GET  /admin/password                       - Cambiar contraseña
POST /admin/password                       - Procesar cambio
```

### 🌐 **API Endpoints**
```
# Health Check
GET  /api/health                           - Estado del sistema

# Localidades API v1
GET  /api/v1/localidades                   - Lista paginada
GET  /api/v1/localidades/{codigo}          - Detalle localidad
GET  /api/v1/localidades/{codigo}/radios   - Radios de localidad  
GET  /api/v1/localidades/{codigo}/svg      - Visualización SVG
POST /api/v1/localidades/bulk-stats        - Estadísticas masivas
GET  /api/v1/localidades/stats             - Estadísticas generales
DELETE /api/v1/localidades/{codigo}/cache  - Limpiar cache

# Documentación API
GET  /api-docs                             - Manual de uso API
```

### 🗺️ **Entidades Geográficas**
```
# Provincias
GET  /provs                                - Lista provincias
GET  /provs-list                           - Lista AJAX provincias
GET  /prov/{id}                            - Ver provincia
POST /prov/{id}                            - Procesar provincia
DELETE /provincia/{id}                     - Eliminar provincia

# Departamentos  
GET  /prov/deptos/{provincia}              - Deptos por provincia
GET  /prov/list/{provincia}                - Lista deptos
GET  /depto/{id}                           - Ver departamento
POST /depto/{id}                           - Procesar departamento

# Localidades
GET  /localidades                          - Vista localidades
GET  /localidades_json                     - JSON localidades
GET  /locas-list                           - Lista AJAX
POST /locas-list                           - Filtrar lista
GET  /localidad/codigo/{codigo}            - Por código
GET  /localidad/{id}                       - Ver localidad
POST /localidad/{id}                       - Procesar localidad

# Aglomerados
GET  /aglos                                - Lista aglomerados
GET  /aglos-list                           - Lista AJAX
POST /aglos-list                           - Filtrar aglomerados
GET  /aglo/{id}                            - Ver aglomerado
POST /aglo/{id}                            - Procesar aglomerado

# Radios
GET  /radio/codigo/{codigo}                - Radio por código
GET  /radio/{id}                           - Ver radio
GET  /radios/{localidad}/{depto}           - Radios por localidad/depto
```

### 🛠️ **Segmentador y Procesamiento**
```
# Segmentador Principal
GET  /segmentador                          - Interfaz principal
POST /segmentador/guardar                  - Procesar carga

# Segmentación por Localidad
GET  /localidad-segmenta/{id}              - Segmentar localidad
POST /localidad-segmenta/{id}              - Procesar segmentación
POST /localidad-segmenta-run/{id}          - Ejecutar segmentación

# Segmentación por Aglomerado
GET  /aglo-segmenta/{id}                   - Segmentar aglomerado
POST /aglo-segmenta/{id}                   - Procesar segmentación  
POST /aglo-segmenta-run/{id}               - Ejecutar segmentación

# Visualización de Segmentación
GET  /localidad/{id}/segmentacion          - Ver segmentación
GET  /localidad/{id}/segmentacion-lados    - Ver lados segmentación
GET  /localidad/{id}/grafico               - Gráfico segmentación
GET  /localidad/{id}/pxseg                 - Ver por segmento
```

### 📋 **Gestión de Archivos**
```
GET  /archivos                             - Lista archivos
POST /archivos                             - Filtrar archivos
GET  /archivo/{id}                         - Ver archivo
POST /archivo/{id}                         - Procesar archivo
DELETE /archivo/{id}                       - Eliminar archivo
GET  /archivo/{id}/descargar               - Descargar archivo
GET  /archivo/{id}/procesar                - Procesar archivo
PUT  /archivo/{id}/detach                  - Desasociar archivo

# Limpieza y Mantenimiento
GET  /archivos/limpiar                     - Limpiar archivos
GET  /archivos/repetidos                   - Archivos duplicados
GET  /archivos/checksums_no_calculados     - Sin checksum
GET  /archivos/checksums_obsoletos         - Checksums obsoletos
GET  /archivos/recalcular_cs/{id?}         - Recalcular checksums
```

### ⚙️ **Setup y Configuración**
```
GET  /setup                                - Panel setup
GET  /setup/test                           - Test flash messages

# Procesamiento de Esquemas
GET  /setup/geo/{esquema}                  - Georeferenciar esquema
GET  /setup/geo/{esquema}/{n}              - Con parámetros
GET  /setup/geo/{esquema}/{n}/{frac}       - Por fracción
GET  /setup/geo/{esquema}/{n}/{frac}/{radio} - Por radio
GET  /setup/geoseg/{esquema}               - Georeferenciar segmentos

# Limpieza y Mantenimiento
GET  /setup/limpia/{esquema}               - Limpiar esquema
GET  /setup/limpiar/Temporales             - Limpiar temporales

# Topología
GET  /setup/topo/{esquema}                 - Cargar topología
GET  /setup/topo/{esquema}/{tolerancia}    - Con tolerancia
GET  /setup/topo/pais                      - Topología país
GET  /setup/topo_drop/{esquema}            - Eliminar topología

# Segmentación
GET  /setup/segmenta/{esquema}             - Segmentar esquema
GET  /setup/junta/{esquema}                - Juntar segmentos
GET  /setup/junta/{esquema}/{frac}/{radio} - Por fracción/radio
GET  /setup/juntaMenores/{esquema}/{frac}/{radio}/{n} - Juntar menores

# Índices y Performance  
GET  /setup/index/{esquema}                - Crear índices
GET  /setup/index/id/{tabla}               - Índice por ID
GET  /setup/index/{esquema}/{tabla}/{cols} - Índices específicos

# Actualizaciones
GET  /setup/update/Localidades             - Actualizar localidades
GET  /setup/update/R3                      - Actualizar R3
GET  /setup/update/Cuadras                 - Actualizar cuadras
GET  /setup/update/Manzanas                - Actualizar manzanas
GET  /setup/update/Vias                    - Actualizar vías
GET  /setup/update/LS                      - Juntar listados
GET  /setup/update/RadiosDeArcs             - Radios de arcos
GET  /setup/update/RadiosDeListados         - Radios de listados

# SRID y Proyecciones
GET  /setup/fixSRID/{esquema}/{srid}       - Corregir SRID
GET  /setup/update/localidad_srid          - Cargar SRIDs localidades
GET  /setup/update/corrige_localidad_srid  - Corregir SRIDs

# Permisos y Grupos
GET  /setup/{esquema}                      - Asignar permisos
GET  /setup/grupogeoestadistica/{usuario}  - Grupo geoestadística
GET  /setup/grupogeoestadistica/tabla/{tabla} - Permisos tabla
```

### 📊 **Visualización y Gráficos**
```
# Grafos de Segmentación
GET  /grafo/{aglomerado}                   - Grafo aglomerado
GET  /grafo/{aglomerado}/{radio}           - Grafo radio específico
GET  /radio/{localidad}/{radio}            - Ver grafo radio

# Visualización de Segmentación
GET  /ver-segmentacion/{aglomerado}        - Ver segmentación
GET  /ver-segmentacion/grafico/{aglomerado} - Gráfico segmentación
POST /ver-segmentacion-grafico/{aglomerado} - Procesar gráfico
GET  /ver-segmentacion/grafico-resumen/{aglomerado} - Resumen gráfico

# Segmentación por Lados
GET  /ver-segmentacion-lados/{aglomerado}   - Ver lados
GET  /ver-segmentacion-lados/grafico-resumen/{aglomerado} - Resumen lados
POST /ver-segmentacion-lados-grafico-resumen/{aglomerado} - Procesar resumen

# Informes y Tableros
GET  /informe/avance                       - Gráfico avance
POST /informe/avance                       - Procesar avance
GET  /informe/avances                      - Gráficos avances
POST /informe/avances                      - Procesar avances
GET  /informe/prov                         - Gráfico provincias
POST /informe/prov                         - Procesar provincias
```

### 🛠️ **Herramientas y Utilidades**
```
# Importación y Exportación
POST /import                               - Importar datos
GET  /csv_file                             - Gestión CSV
GET  /csv_file/export                      - Exportar CSV
POST /csv_file/import                      - Importar CSV

# Búsquedas y Autocompletado  
GET  /search_provincia                     - Buscar provincia
GET  /autocomplete_provincia               - Autocompletar provincia

# Listados
GET  /listado                              - Lista general
GET  /listado/{id}                         - Ver listado específico

# Segmentos
GET  /segmentos                            - Lista segmentos
GET  /segmentos/{id}                       - Ver segmento

# Gestión de Usuarios y Roles
GET  /users                                - Lista usuarios
GET  /users/{id}/filter                    - Editar filtro usuario
GET  /users/{id}/permission                - Editar permisos usuario  
GET  /users/{id}/roles                     - Editar roles usuario

# Roles y Permisos
GET  /roles                                - Lista roles
GET  /roles/new                            - Crear rol
GET  /roles/{id}/detail                    - Detalle rol
GET  /roles/{id}/edit                      - Editar rol
DELETE /roles/{id}                         - Eliminar rol

# Filtros
GET  /filtros                              - Lista filtros
GET  /filtros/new                          - Crear filtro
GET  /filtros/provs                        - Filtros provincias
GET  /filtros/provs/edit                   - Editar filtros provincias
GET  /filtros/{id}/rename                  - Renombrar filtro
DELETE /filtros/{id}                       - Eliminar filtro
```

### 🔧 **Sistema y Monitoreo**
```
# Laravel Horizon (Queues)
GET  /horizon/{view?}                      - Dashboard Horizon
GET  /horizon/api/stats                    - Estadísticas colas
GET  /horizon/api/masters                  - Procesos maestros
GET  /horizon/api/workload                 - Carga trabajo
GET  /horizon/api/jobs/completed           - Trabajos completados
GET  /horizon/api/jobs/failed              - Trabajos fallidos
GET  /horizon/api/jobs/pending             - Trabajos pendientes
POST /horizon/api/jobs/retry/{id}          - Reintentar trabajo

# Sistema
GET  /clear-cache                          - Limpiar cache
GET  /serverinfo                           - Información servidor
GET  /colaboradores                        - Colaboradores
GET  /sala                                 - Sala virtual
```

### 🧪 **Testing y Desarrollo**
```
GET  /testeando                            - Página de pruebas
GET  /tete                                 - Test 2  
GET  /tetete                               - Test 3
GET  /guia                                 - Guía de uso
```

## 🎯 **Rutas Más Importantes para Testing**

### ✅ **APIs Funcionales** (Puerto 8090)
```
✅ GET  /api/health                        - FUNCIONANDO
✅ GET  /api/v1/localidades                - FUNCIONANDO  
✅ GET  /api/v1/localidades/{codigo}       - FUNCIONANDO
✅ GET  /api/v1/localidades/{codigo}/radios - FUNCIONANDO
⏳ GET  /api/v1/localidades/{codigo}/svg   - POR PROBAR
⏳ GET  /api-docs                          - POR PROBAR
```

### 🔧 **Interfaces Principales**
```
⏳ GET  /segmentador                       - Interfaz carga archivos
⏳ POST /segmentador/guardar               - Procesar archivos
⏳ GET  /admin/localidades                 - Panel admin
⏳ GET  /setup                             - Panel configuración
```

### 📊 **Visualización**
```
⏳ GET  /localidad/{id}/segmentacion       - Ver resultados
⏳ GET  /aglo/{id}                         - Ver aglomerado  
⏳ GET  /grafo/{aglomerado}                - Visualización grafo
```

## 📈 **Próximos Tests Recomendados**

1. ✅ **API Básica** - COMPLETADO
2. ⏳ **Interfaz de carga** (`/segmentador`)
3. ⏳ **Panel administrativo** (`/admin/*`)
4. ⏳ **Procesamiento setup** (`/setup/*`)
5. ⏳ **Visualizaciones** (`/localidad/*/segmentacion`)

**Total Rutas Identificadas**: **252 rutas** con funcionalidades completas de GIS, administración y API.