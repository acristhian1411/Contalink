# Especificación de Requisitos - Sistema de Unidades de Inventario

## Introducción

El sistema actual de inventario de Contalink maneja únicamente unidades simples (1 manzana, 1 huevo). Esta especificación define los requisitos para expandir el sistema para soportar diferentes tipos de unidades de medida como kilogramos, litros, metros, etc., manteniendo la compatibilidad con la funcionalidad existente de ventas, compras, devoluciones y eliminación de ventas.

## Glosario

- **Sistema_Inventario**: El módulo de gestión de productos e inventario de Contalink
- **Unidad_Medida**: Tipo de medida utilizada para cuantificar un producto (unidad, kilogramo, litro, metro, etc.)
- **Cantidad_Producto**: Valor numérico que representa la cantidad de un producto en su unidad de medida correspondiente
- **Detalle_Venta**: Registro individual de un producto vendido dentro de una venta
- **Detalle_Compra**: Registro individual de un producto comprado dentro de una compra
- **Detalle_Devolucion**: Registro individual de un producto devuelto dentro de una devolución
- **Producto_Existente**: Productos ya registrados en el sistema que actualmente usan unidades simples

## Requisitos

### Requisito 1

**Historia de Usuario:** Como administrador del sistema, quiero definir diferentes tipos de unidades de medida, para que los productos puedan ser gestionados con sus unidades apropiadas.

#### Criterios de Aceptación

1. THE Sistema_Inventario SHALL permitir crear nuevos tipos de Unidad_Medida con nombre y abreviación
2. THE Sistema_Inventario SHALL incluir unidades predefinidas como "Unidad", "Kilogramo", "Litro", "Metro"
3. THE Sistema_Inventario SHALL validar que cada Unidad_Medida tenga un nombre único
4. THE Sistema_Inventario SHALL permitir activar o desactivar tipos de Unidad_Medida
5. THE Sistema_Inventario SHALL mantener compatibilidad con Producto_Existente asignándoles automáticamente la unidad "Unidad"

### Requisito 2

**Historia de Usuario:** Como usuario del sistema, quiero asignar una unidad de medida específica a cada producto, para que el inventario refleje correctamente las cantidades en sus unidades apropiadas.

#### Criterios de Aceptación

1. WHEN se crea un nuevo producto, THE Sistema_Inventario SHALL requerir la selección de una Unidad_Medida
2. THE Sistema_Inventario SHALL mostrar la Unidad_Medida junto con la Cantidad_Producto en todas las vistas de productos
3. THE Sistema_Inventario SHALL permitir modificar la Unidad_Medida de un producto existente
4. WHERE un producto no tiene Unidad_Medida asignada, THE Sistema_Inventario SHALL usar "Unidad" como valor predeterminado
5. THE Sistema_Inventario SHALL validar que las cantidades sean números positivos para cualquier Unidad_Medida

### Requisito 3

**Historia de Usuario:** Como vendedor, quiero registrar ventas especificando cantidades en las unidades correctas de cada producto, para que el inventario se actualice apropiadamente.

#### Criterios de Aceptación

1. WHEN se registra un Detalle_Venta, THE Sistema_Inventario SHALL mostrar la Unidad_Medida del producto seleccionado
2. THE Sistema_Inventario SHALL validar que la Cantidad_Producto en el Detalle_Venta sea compatible con la Unidad_Medida del producto
3. THE Sistema_Inventario SHALL actualizar el inventario restando la Cantidad_Producto vendida
4. THE Sistema_Inventario SHALL mostrar la Unidad_Medida en los reportes de ventas
5. THE Sistema_Inventario SHALL prevenir ventas cuando la Cantidad_Producto solicitada exceda el inventario disponible

### Requisito 4

**Historia de Usuario:** Como encargado de compras, quiero registrar compras especificando cantidades en las unidades correctas de cada producto, para que el inventario se incremente apropiadamente.

#### Criterios de Aceptación

1. WHEN se registra un Detalle_Compra, THE Sistema_Inventario SHALL mostrar la Unidad_Medida del producto seleccionado
2. THE Sistema_Inventario SHALL validar que la Cantidad_Producto en el Detalle_Compra sea compatible con la Unidad_Medida del producto
3. THE Sistema_Inventario SHALL actualizar el inventario sumando la Cantidad_Producto comprada
4. THE Sistema_Inventario SHALL mostrar la Unidad_Medida en los reportes de compras
5. THE Sistema_Inventario SHALL permitir cantidades decimales para unidades como kilogramos y litros

### Requisito 5

**Historia de Usuario:** Como usuario del sistema, quiero procesar devoluciones especificando cantidades en las unidades correctas, para que el inventario se restaure apropiadamente.

#### Criterios de Aceptación

1. WHEN se registra un Detalle_Devolucion, THE Sistema_Inventario SHALL mostrar la Unidad_Medida del producto devuelto
2. THE Sistema_Inventario SHALL validar que la Cantidad_Producto devuelta no exceda la cantidad originalmente vendida
3. THE Sistema_Inventario SHALL actualizar el inventario sumando la Cantidad_Producto devuelta
4. THE Sistema_Inventario SHALL mostrar la Unidad_Medida en los reportes de devoluciones
5. THE Sistema_Inventario SHALL mantener trazabilidad de las cantidades devueltas por Unidad_Medida

### Requisito 6

**Historia de Usuario:** Como administrador, quiero que la eliminación de ventas revierta correctamente las cantidades de inventario según sus unidades de medida, para mantener la integridad del inventario.

#### Criterios de Aceptación

1. WHEN se elimina una venta, THE Sistema_Inventario SHALL restaurar las cantidades de inventario para cada producto según su Unidad_Medida
2. THE Sistema_Inventario SHALL validar que la operación de reversión sea matemáticamente correcta
3. THE Sistema_Inventario SHALL registrar en auditoría los cambios de inventario por eliminación de ventas
4. THE Sistema_Inventario SHALL manejar eliminaciones de ventas que incluyan productos con diferentes Unidad_Medida
5. THE Sistema_Inventario SHALL prevenir eliminaciones que resulten en inventarios negativos

### Requisito 7

**Historia de Usuario:** Como usuario del sistema, quiero que todas las funcionalidades existentes continúen funcionando sin interrupciones después de implementar las unidades de medida, para mantener la continuidad operativa.

#### Criterios de Aceptación

1. THE Sistema_Inventario SHALL mantener compatibilidad total con todas las APIs existentes
2. THE Sistema_Inventario SHALL preservar el formato de respuesta actual agregando información de Unidad_Medida
3. THE Sistema_Inventario SHALL migrar automáticamente todos los Producto_Existente a la unidad "Unidad"
4. THE Sistema_Inventario SHALL mantener el comportamiento actual para productos sin Unidad_Medida especificada
5. THE Sistema_Inventario SHALL asegurar que los reportes existentes incluyan información de Unidad_Medida sin romper la funcionalidad