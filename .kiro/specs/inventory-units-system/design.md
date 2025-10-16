# Documento de Diseño - Sistema de Unidades de Inventario

## Resumen

Este documento describe el diseño para expandir el sistema de inventario de Contalink para soportar diferentes tipos de unidades de medida (kilogramos, litros, metros, etc.) manteniendo total compatibilidad con la funcionalidad existente de ventas, compras, devoluciones y eliminación de ventas.

## Arquitectura

### Enfoque de Diseño

El diseño sigue un enfoque **minimalista y compatible hacia atrás** que:

1. **Preserva la estructura existente**: No modifica las tablas actuales de productos, ventas, compras o devoluciones
2. **Agrega funcionalidad incremental**: Introduce una nueva tabla de unidades de medida con relación opcional
3. **Mantiene compatibilidad total**: Todos los productos existentes funcionan sin cambios
4. **Implementación gradual**: Permite migración progresiva de productos a nuevas unidades

### Principios de Diseño

- **Compatibilidad hacia atrás**: 100% compatible con APIs y funcionalidad existente
- **Migración transparente**: Los productos existentes automáticamente usan "Unidad" como tipo predeterminado
- **Validación inteligente**: El sistema valida cantidades según el tipo de unidad
- **Flexibilidad**: Soporte para unidades enteras (unidades) y decimales (kg, litros)

## Componentes y Interfaces

### 1. Nueva Tabla: `measurement_units`

```sql
CREATE TABLE measurement_units (
    id BIGSERIAL PRIMARY KEY,
    unit_name VARCHAR(50) NOT NULL UNIQUE,
    unit_abbreviation VARCHAR(10) NOT NULL,
    allows_decimals BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

**Campos:**
- `unit_name`: Nombre completo (ej: "Kilogramo", "Litro", "Unidad")
- `unit_abbreviation`: Abreviación (ej: "kg", "L", "u")
- `allows_decimals`: Si permite cantidades decimales (kg, litros = true; unidades = false)
- `is_active`: Para activar/desactivar unidades sin eliminarlas
- `deleted_at`: Soft delete para mantener integridad referencial

### 2. Modificación de Tabla: `products`

```sql
ALTER TABLE products 
ADD COLUMN measurement_unit_id BIGINT NULL,
ADD CONSTRAINT fk_products_measurement_unit 
    FOREIGN KEY (measurement_unit_id) REFERENCES measurement_units(id);
```

**Comportamiento:**
- `measurement_unit_id` es **nullable** para compatibilidad
- Si es NULL, el sistema usa "Unidad" como predeterminado
- Los productos existentes mantienen `measurement_unit_id = NULL` inicialmente

### 3. Nuevo Modelo: `MeasurementUnit`

```php
class MeasurementUnit extends Model
{
    use HasFactory, Auditable, SoftDeletes;
    
    protected $fillable = [
        'unit_name',
        'unit_abbreviation', 
        'allows_decimals',
        'is_active'
    ];
    
    protected $casts = [
        'allows_decimals' => 'boolean',
        'is_active' => 'boolean'
    ];
    
    public function products()
    {
        return $this->hasMany(Products::class);
    }
}
```

### 4. Modificación del Modelo: `Products`

```php
// Agregar relación en Products.php
public function measurementUnit()
{
    return $this->belongsTo(MeasurementUnit::class)->withDefault([
        'unit_name' => 'Unidad',
        'unit_abbreviation' => 'u',
        'allows_decimals' => false
    ]);
}

// Accessor para obtener la unidad de medida
public function getUnitNameAttribute()
{
    return $this->measurementUnit->unit_name;
}

public function getUnitAbbreviationAttribute()
{
    return $this->measurementUnit->unit_abbreviation;
}
```

### 5. Nuevo Controlador: `MeasurementUnitsController`

```php
class MeasurementUnitsController extends ApiController
{
    public function index() // Listar unidades activas
    public function store(Request $request) // Crear nueva unidad
    public function show($id) // Ver unidad específica
    public function update(Request $request, $id) // Actualizar unidad
    public function destroy($id) // Soft delete de unidad
    public function activate($id) // Activar unidad
    public function deactivate($id) // Desactivar unidad
}
```

## Modelos de Datos

### Datos Predefinidos

El sistema incluirá estas unidades predefinidas:

```php
$defaultUnits = [
    ['unit_name' => 'Unidad', 'unit_abbreviation' => 'u', 'allows_decimals' => false],
    ['unit_name' => 'Kilogramo', 'unit_abbreviation' => 'kg', 'allows_decimals' => true],
    ['unit_name' => 'Gramo', 'unit_abbreviation' => 'g', 'allows_decimals' => true],
    ['unit_name' => 'Litro', 'unit_abbreviation' => 'L', 'allows_decimals' => true],
    ['unit_name' => 'Mililitro', 'unit_abbreviation' => 'ml', 'allows_decimals' => true],
    ['unit_name' => 'Metro', 'unit_abbreviation' => 'm', 'allows_decimals' => true],
    ['unit_name' => 'Centímetro', 'unit_abbreviation' => 'cm', 'allows_decimals' => true],
    ['unit_name' => 'Caja', 'unit_abbreviation' => 'caja', 'allows_decimals' => false],
    ['unit_name' => 'Paquete', 'unit_abbreviation' => 'paq', 'allows_decimals' => false]
];
```

### Validación de Cantidades

```php
class QuantityValidator
{
    public static function validate($quantity, MeasurementUnit $unit)
    {
        // Validar que sea numérico y positivo
        if (!is_numeric($quantity) || $quantity <= 0) {
            return false;
        }
        
        // Si la unidad no permite decimales, validar que sea entero
        if (!$unit->allows_decimals && floor($quantity) != $quantity) {
            return false;
        }
        
        return true;
    }
}
```

## Manejo de Errores

### Estrategia de Errores

1. **Validación de Entrada**: Validar cantidades según el tipo de unidad
2. **Fallback Graceful**: Si no hay unidad asignada, usar "Unidad" por defecto
3. **Mensajes Específicos**: Errores claros sobre restricciones de unidades
4. **Logging Detallado**: Registrar cambios de unidades para auditoría

### Códigos de Error Específicos

```php
const UNIT_VALIDATION_ERRORS = [
    'INVALID_DECIMAL' => 'La unidad {unit} no permite cantidades decimales',
    'UNIT_NOT_FOUND' => 'La unidad de medida especificada no existe',
    'UNIT_INACTIVE' => 'La unidad de medida está desactivada',
    'QUANTITY_INVALID' => 'La cantidad debe ser un número positivo'
];
```

## Estrategia de Testing

### Tipos de Pruebas

1. **Pruebas unitarias**
   - Validación de cantidades por tipo de unidad
   - Comportamiento de modelos con unidades
   - Fallback a unidad predeterminada

2. **Pruebas de Integración**
   - Flujo completo de ventas con diferentes unidades
   - Actualización de inventario con unidades mixtas
   - Eliminación de ventas con reversión de stock

3. **Pruebas de Compatibilidad**
   - APIs existentes funcionan sin cambios
   - Productos existentes mantienen comportamiento
   - Reportes incluyen información de unidades

### Casos de Prueba Críticos

```php
// Ejemplo de casos de prueba
class InventoryUnitsTest extends TestCase
{
    public function test_existing_products_work_without_units()
    public function test_decimal_validation_for_weight_units()
    public function test_integer_validation_for_piece_units()
    public function test_sale_with_mixed_units()
    public function test_stock_reversal_with_units()
    public function test_api_compatibility()
}
```

## Migración y Compatibilidad

### Estrategia de Migración

1. **Fase 1**: Crear tabla `measurement_units` con datos predefinidos
2. **Fase 2**: Agregar columna `measurement_unit_id` a `products` (nullable)
3. **Fase 3**: Modificar controladores para incluir información de unidades
4. **Fase 4**: Actualizar frontend para mostrar unidades
5. **Fase 5**: Migración opcional de productos existentes

### Script de Migración

```php
// Migration: add_measurement_units_system
public function up()
{
    // Crear tabla measurement_units
    Schema::create('measurement_units', function (Blueprint $table) {
        $table->id();
        $table->string('unit_name', 50)->unique();
        $table->string('unit_abbreviation', 10);
        $table->boolean('allows_decimals')->default(false);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    
    // Insertar unidades predefinidas
    $this->insertDefaultUnits();
    
    // Agregar columna a products
    Schema::table('products', function (Blueprint $table) {
        $table->unsignedBigInteger('measurement_unit_id')->nullable();
        $table->foreign('measurement_unit_id')->references('id')->on('measurement_units');
    });
}

private function insertDefaultUnits()
{
    // Insertar unidades predefinidas...
}
```

## Modificaciones en APIs Existentes

### Respuestas de API Mejoradas

Las APIs existentes mantendrán su formato actual pero incluirán información adicional de unidades:

```json
// Respuesta de producto (antes)
{
    "id": 1,
    "product_name": "Manzana",
    "product_quantity": "100",
    "category": {...}
}

// Respuesta de producto (después - compatible)
{
    "id": 1,
    "product_name": "Manzana",
    "product_quantity": "100",
    "category": {...},
    "measurement_unit": {
        "id": 1,
        "unit_name": "Unidad",
        "unit_abbreviation": "u",
        "allows_decimals": false
    },
    "unit_name": "Unidad",
    "unit_abbreviation": "u"
}
```

### Modificaciones en Controladores

```php
// ProductsController::index() - agregar join con measurement_units
$datos = $query->join('categories','products.category_id','=','categories.id')
    ->join('iva_types','products.iva_type_id','=','iva_types.id')
    ->join('brands','products.brand_id','=', 'brands.id')
    ->leftJoin('measurement_units','products.measurement_unit_id','=','measurement_units.id')
    ->select('products.*','iva_types.iva_type_desc','iva_types.iva_type_percent',
             'categories.cat_desc', 'brands.brand_name',
             'measurement_units.unit_name', 'measurement_units.unit_abbreviation',
             'measurement_units.allows_decimals')
    ->get();
```

## Consideraciones de Rendimiento

### Optimizaciones

1. **Índices de Base de Datos**
   - Índice en `products.measurement_unit_id`
   - Índice compuesto en `measurement_units(is_active, unit_name)`

2. **Caching**
   - Cache de unidades de medida activas
   - Cache de productos con sus unidades

3. **Consultas Optimizadas**
   - Eager loading de relaciones de unidades
   - Uso de `leftJoin` para compatibilidad

## Seguridad

### Validaciones de Seguridad

1. **Autorización**: Solo usuarios autorizados pueden crear/modificar unidades
2. **Validación de Entrada**: Sanitización de nombres y abreviaciones de unidades
3. **Integridad Referencial**: Prevenir eliminación de unidades en uso
4. **Auditoría**: Registro completo de cambios en unidades y cantidades

### Reglas de Negocio

```php
class MeasurementUnitBusinessRules
{
    public static function canDelete(MeasurementUnit $unit)
    {
        // No se puede eliminar si hay productos que la usan
        return $unit->products()->count() === 0;
    }
    
    public static function canDeactivate(MeasurementUnit $unit)
    {
        // Se puede desactivar pero no eliminar si hay productos
        return true;
    }
}
```

## Conclusión

Este diseño proporciona una solución robusta y compatible para agregar soporte de unidades de medida al sistema de inventario de Contalink. La implementación es incremental, mantiene total compatibilidad hacia atrás y permite una migración gradual de la funcionalidad existente.