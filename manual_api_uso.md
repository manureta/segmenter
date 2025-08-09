# Manual de Uso - API Segmenter

## Descripción
Esta API REST permite la gestión de localidades y datos geográficos del sistema de segmentación del INDEC.

## Base URL
```
http://localhost:8090/api/v1
```

## Endpoints Disponibles

### 1. Health Check
Verificar el estado de la API.

```
GET /api/health
```

**Respuesta:**
- 200 OK: La API está funcionando correctamente

---

### 2. Localidades

#### 2.1. Listar todas las localidades
```
GET /api/v1/localidades
```

**Descripción:** Obtiene una lista de todas las localidades disponibles.

**Respuesta:** Array de objetos localidad con información básica.

---

#### 2.2. Obtener localidad específica
```
GET /api/v1/localidades/{codigo}
```

**Parámetros:**
- `codigo`: Código único de la localidad

**Descripción:** Obtiene información detallada de una localidad específica.

**Ejemplo:**
```
GET /api/v1/localidades/12345
```

---

#### 2.3. Obtener estadísticas de localidades
```
GET /api/v1/localidades/stats
```

**Descripción:** Obtiene estadísticas generales de todas las localidades.

---

#### 2.4. Estadísticas masivas por POST
```
POST /api/v1/localidades/bulk-stats
```

**Descripción:** Permite obtener estadísticas de múltiples localidades enviando una lista de códigos.

**Body (JSON):**
```json
{
  "codigos": ["12345", "67890", "11111"]
}
```

---

#### 2.5. Obtener radios de una localidad
```
GET /api/v1/localidades/{codigo}/radios
```

**Parámetros:**
- `codigo`: Código de la localidad

**Descripción:** Obtiene todos los radios asociados a una localidad específica.

---

#### 2.6. Obtener SVG de una localidad
```
GET /api/v1/localidades/{codigo}/svg
```

**Parámetros:**
- `codigo`: Código de la localidad

**Descripción:** Genera y devuelve una representación SVG de la localidad.

**Respuesta:** Contenido SVG (image/svg+xml)

---

#### 2.7. Limpiar caché de localidad
```
DELETE /api/v1/localidades/{codigo}/cache
```

**Parámetros:**
- `codigo`: Código de la localidad

**Descripción:** Elimina los datos en caché de una localidad específica.

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200    | OK - Solicitud exitosa |
| 201    | Created - Recurso creado exitosamente |
| 400    | Bad Request - Error en la solicitud |
| 401    | Unauthorized - Acceso no autorizado |
| 404    | Not Found - Recurso no encontrado |
| 422    | Unprocessable Entity - Error de validación |
| 500    | Internal Server Error - Error interno del servidor |

## Formato de Respuesta

Todas las respuestas de la API siguen un formato JSON estándar:

```json
{
  "data": {
    // Datos solicitados
  },
  "status": "success|error",
  "message": "Mensaje descriptivo"
}
```

## Ejemplos de Uso

### cURL Examples

#### Obtener todas las localidades:
```bash
curl -X GET "http://localhost:8090/api/v1/localidades" \
     -H "Accept: application/json"
```

#### Obtener localidad específica:
```bash
curl -X GET "http://localhost:8090/api/v1/localidades/12345" \
     -H "Accept: application/json"
```

#### Obtener estadísticas masivas:
```bash
curl -X POST "http://localhost:8090/api/v1/localidades/bulk-stats" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"codigos": ["12345", "67890"]}'
```

### JavaScript (Fetch API)

```javascript
// Obtener localidades
const response = await fetch('http://localhost:8090/api/v1/localidades');
const localidades = await response.json();

// Obtener localidad específica
const localidad = await fetch('http://localhost:8090/api/v1/localidades/12345');
const data = await localidad.json();

// Estadísticas masivas
const stats = await fetch('http://localhost:8090/api/v1/localidades/bulk-stats', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    codigos: ['12345', '67890']
  })
});
const statsData = await stats.json();
```

## Notas Adicionales

- Todos los endpoints requieren el header `Accept: application/json`
- Para endpoints POST, usar `Content-Type: application/json`
- Los códigos de localidad deben ser válidos y existir en el sistema
- El endpoint SVG devuelve contenido binario (image/svg+xml)
- La API implementa caché para mejorar el rendimiento
- Usar el endpoint de limpieza de caché solo cuando sea necesario actualizar datos

## Panel de Administración

La aplicación también incluye un panel de administración web con las siguientes funcionalidades:

### Rutas de Administración (Web)
- `/admin/admin-users` - Gestión de usuarios administrativos
- `/admin/departamentos` - Gestión de departamentos
- `/admin/localidades` - Gestión de localidades (versión web)
- `/admin/provincia` - Gestión de provincias
- `/admin/tipos-de-radio` - Gestión de tipos de radio
- `/admin/usuarios` - Gestión de usuarios del sistema

**Nota:** Todas las rutas de administración requieren autenticación administrativa.