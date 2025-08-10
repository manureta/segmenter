# Codificación de Aglomerados y Localidades - Sistema INDEC

## 📊 **Estructura de Códigos**

### 🏘️ **Aglomerados**
**Formato**: `XXXX` (4 dígitos)
- Los archivos E00 usan nomenclatura: `eXXXX.e00`
- Ejemplo: `e0810.e00` → Aglomerado código `0810`

### 🏠 **Localidades** 
**Formato**: `PPDDDLLL` (8 dígitos)
- **PP** = Código Provincia (2 dígitos, completado con 0)
- **DDD** = Código Departamento (3 dígitos, completado con 0) 
- **LLL** = Código Localidad (3 dígitos, completado con 0)

**Ejemplo**: `66084020`
- Provincia: `66` 
- Departamento: `084`
- Localidad: `020`
- Resultado: El Tala, Salta

## 🗺️ **Archivos E00 Analizados**

| Archivo | Código Aglo | Localidad Principal | Provincia+Depto+Loc | Ubicación |
|---------|-------------|-------------------|-------------------|-----------|
| `e0810.e00` | 0810 | El Tala | 66+084+020 | Salta |
| `e3721.e00` | 3721 | La Candelaria | 66+084+030 | Salta |
| `e1373.e00` | 1373 | Lanteri | 82+049+110 | Santa Fe |
| `e0009.e00` | 0009 | Salta + Villa San Lorenzo | 66+028+050/060 | Salta Capital |
| `e0967.e00` | 0967 | *(No encontrado en BD)* | - | - |

## 🏛️ **Estructura Provincial**

### **Provincia 66 - Salta**
- **Departamento 084**: El Tala (020), La Candelaria (030)
- **Departamento 028**: Salta Capital (050), Villa San Lorenzo (060)

### **Provincia 82 - Santa Fe**  
- **Departamento 049**: Lanteri (110)

## 📋 **Ejemplos de Codificación**

### **Archivo e0810.e00**
```
Aglomerado: 0810
└── Localidad: 66084020
    ├── Provincia: 66 (Salta)
    ├── Departamento: 084 (El Tala) 
    └── Localidad: 020 (El Tala)
```

### **Archivo e0009.e00** 
```
Aglomerado: 0009
├── Localidad: 66028050
│   ├── Provincia: 66 (Salta)
│   ├── Departamento: 028 (Capital)
│   └── Localidad: 050 (Salta)
└── Localidad: 66028060
    ├── Provincia: 66 (Salta)  
    ├── Departamento: 028 (Capital)
    └── Localidad: 060 (Villa San Lorenzo)
```

## 🔧 **Para Testing y Desarrollo**

### **Conjunto Completo Aglomerado 0810**
- **Geometría**: `e0810.e00` (ID: 5) - 177 KB
- **Viviendas**: `c1_0810.dbf` (ID: 4) - 827 KB  
- **Localidad**: El Tala (66084020)
- **Esquema BD**: `e_t_KduN4kAdH8PbgHoq60NfNmuBhJUWryeIH06Ztgpd`

### **Conjunto Completo Aglomerado 1373**
- **Geometría**: `e1373.e00` (ID: 11) - 146 KB
- **Viviendas**: `c1_1373.dbf` (ID: 10) - 802 KB
- **Localidad**: Lanteri (82049110) 
- **Esquema BD**: `e_t_QAPDP7Ayx5QKqbM6vzgJbS5aNVWelbplhPVNPwAy`

### **Aglomerado Metropolitano 0009**
- **Geometría**: `e0009.e00` (ID: ???)
- **Localidades**: Salta Capital + Villa San Lorenzo
- **Tipo**: Aglomerado urbano multi-localidad

## 💡 **Observaciones**

1. **Aglomerados sin nombre**: Muchos aparecen como "Sin Nombre" en BD
2. **Relación 1:N**: Un aglomerado puede tener múltiples localidades
3. **Códigos faltantes**: `e0967.e00` no tiene registro en BD
4. **Nomenclatura consistente**: Los archivos siguen patrón `e{codigo}.e00`
5. **Complementos**: Los archivos `c1_{codigo}.dbf` contienen listados de viviendas del mismo aglomerado

## 📈 **Uso en Segmentación**

Los códigos permiten:
- **Identificar región geográfica** por provincia/departamento
- **Asociar datos censales** (viviendas, población)
- **Generar esquemas temporales** `e{codigo}` en PostgreSQL
- **Procesar cartografía** específica por aglomerado
- **Aplicar proyecciones EPSG** apropiadas por zona