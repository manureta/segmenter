# Plan de Revisión de Archivos - API Modernización

## Estado Actual
- **Rama:** api-modernizacion
- **Archivos sin seguimiento:** 80+ archivos
- **Archivos modificados:** 1 submódulo

## Categorización de Archivos

### 🔧 CRÍTICOS - Controllers y API (Prioritarios)
```
app/Http/Controllers/Admin/DepartamentoController.php
app/Http/Controllers/Admin/LocalidadController.php  
app/Http/Controllers/Admin/LocalidadesController.php
app/Http/Controllers/Admin/ProvinciaController.php
app/Http/Controllers/Admin/TipoDeRadioController.php
app/Http/Controllers/Admin/UserController.php
app/Http/Controllers/Admin/UsersController.php
```

### 📝 CRÍTICOS - Request Validators
```
app/Http/Requests/Admin/AdminUser/ImpersonalLoginAdminUser.php
app/Http/Requests/Admin/Departamento/
app/Http/Requests/Admin/Localidad/
app/Http/Requests/Admin/Localidade/
app/Http/Requests/Admin/Provincium/
app/Http/Requests/Admin/TipoDeRadio/
app/Http/Requests/Admin/User/
```

### 📊 IMPORTANTES - Modelos
```
app/Models/AppModelLocalidad.php
app/Models/Departamento.php
app/Models/Localidad.php
app/Models/Localidade.php  
app/Models/Provincium.php
app/Models/TipoDeRadio.php
app/Model/RadioEntidad.php
```

### 🗄️ IMPORTANTES - Base de Datos
```
database/migrations/2020_10_21_000000_add_last_login_at_timestamp_to_admin_users_table.php
database/migrations/2022_04_20_200556_create_media_table.php
database/migrations/2022_09_23_000323_fill_permissions_for_departamento.php
database/migrations/2022_09_23_122950_fill_permissions_for_localidade.php
database/migrations/2022_09_23_235708_fill_permissions_for_provincium.php
database/migrations/2022_09_23_235908_fill_permissions_for_tipo-de-radio.php
database/seeders/
```

### ⚙️ IMPORTANTES - Configuración
```
config/cors.php
vite.config.js
```

### 🎨 MEDIOS - Frontend
```
resources/views/admin/ (5 directorios)
resources/js/admin/ (5 directorios)
resources/views/components/
resources/views/dashboard.blade.php
resources/js/app-modern.js
resources/js/main.js
```

### 📚 DOCUMENTACIÓN
```
CLAUDE.md ✅ INCLUIR
informe_analisis_proyecto.md
informe_analisis_proyecto.pdf
manual_api_uso.html
```

### 🗂️ DATOS/TEMPORALES - REVISAR
```
*.txt (archivos geográficos)
public/images/ (nuevas imágenes)
public/media/
storage/uploads/
app/Model/.Archivo.php.swp ❌ EXCLUIR
```

## Evaluación Completada

### ✅ APROBADOS PARA INCORPORAR

**🔧 Controllers Admin (7 archivos)**
- Estructura estándar Laravel con authorization gates
- Implementan CRUD completo con validaciones
- Usan AdminListing facade para interfaces
- **Recomendación**: ✅ INCLUIR

**📊 Modelos (7 archivos)**
- Modelos Eloquent básicos con fillable/dates
- Configuración correcta de tablas y timestamps
- **Problemas menores**: Departamento.php tiene fillable vacío
- **Recomendación**: ✅ INCLUIR con correcciones

**📝 Request Validators (5+ directorios)**
- FormRequest con authorization gates
- Validaciones apropiadas para campos
- Métodos getSanitized() implementados
- **Recomendación**: ✅ INCLUIR

**🗄️ Migraciones (6 archivos)**
- Migraciones estándar para admin_users, media, permisos
- Estructura correcta up/down
- **Crítico**: Migración permisos usa transacciones seguras
- **Recomendación**: ✅ INCLUIR

**⚙️ Configuración**
- `config/cors.php`: **PROBLEMA SEGURIDAD** - permite todos los orígenes
- `vite.config.js`: Configuración moderna Vue+Laravel
- **Recomendación**: ✅ INCLUIR con fix de CORS

**🎨 Frontend (Views/JS)**
- Vistas blade admin estándar
- JavaScript moderno con Vue/Vite
- **Recomendación**: ✅ INCLUIR

### ❌ NO INCORPORAR

**🗂️ Archivos Temporales/Datos**
```
app/Model/.Archivo.php.swp ❌
*.txt (datos geográficos) ❌
listado_vivs.csv ❌  
cuadras.txt ❌
raros.txt ❌
public/media/ (archivos subidos) ❌
storage/uploads/ ❌
```

### 📚 DOCUMENTACIÓN - REVISAR
- `CLAUDE.md` ✅ INCLUIR
- `informe_analisis_proyecto.*` ⚠️ EVALUAR (contiene análisis detallado)
- `manual_api_uso.html` ⚠️ EVALUAR

## Plan de Incorporación Final

### Fase 1: Seguridad Crítica
1. 🚨 **PRIMERO**: Aplicar fixes de seguridad identificados
2. 🔒 Corregir CORS configuration
3. 🛡️ Validar inputs en controllers existentes

### Fase 2: Incorporación Selectiva
1. ✅ Controllers + Request validators + Modelos
2. ✅ Migraciones (después de review)
3. ✅ Configuración (con fixes)
4. ✅ Frontend modernización

### Fase 3: Limpieza
1. ❌ Excluir archivos temporales (.swp, *.txt)
2. ❌ Excluir uploads/media existentes
3. ✅ Mantener documentación relevante

## Correcciones Requeridas Pre-Commit
- [ ] Fix CORS: cambiar `['*']` por dominios específicos
- [ ] Completar `fillable` en Departamento.php  
- [ ] Aplicar security fixes identificados en scan inicial
- [ ] Validar que no hay credenciales en archivos nuevos