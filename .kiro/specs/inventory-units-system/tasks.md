# Plan de Implementación - Sistema de Unidades de Inventario

- [ ] 1. Crear estructura base del sistema de unidades de medida
  - Crear migración para tabla `measurement_units` con campos requeridos
  - Crear modelo `MeasurementUnit` con relaciones y validaciones
  - Insertar datos predefinidos de unidades comunes (Unidad, Kilogramo, Litro, etc.)
  - _Requisitos: 1.1, 1.2, 1.3, 1.4_

- [ ] 2. Integrar unidades de medida con el modelo de productos
  - Crear migración para agregar columna `measurement_unit_id` a tabla `products`
  - Modificar modelo `Products` para incluir relación con `MeasurementUnit`
  - Implementar accessors para obtener información de unidad con fallback a "Unidad"
  - _Requisitos: 2.1, 2.2, 2.4_

- [ ] 3. Implementar controlador de gestión de unidades de medida
  - Crear `MeasurementUnitsController` con operaciones CRUD completas
  - Implementar validaciones para nombres únicos y datos requeridos
  - Agregar endpoints para activar/desactivar unidades sin eliminarlas
  - _Requisitos: 1.1, 1.4_

- [ ] 4. Actualizar controlador de productos para soporte de unidades
  - Modificar `ProductsController::index()` para incluir información de unidades en consultas
  - Actualizar `ProductsController::store()` para validar y asignar unidades de medida
  - Modificar `ProductsController::update()` para permitir cambios de unidad de medida
  - Implementar validación de cantidades según tipo de unidad (enteras vs decimales)
  - _Requisitos: 2.1, 2.2, 2.3, 2.5_

- [ ] 5. Adaptar sistema de ventas para manejar unidades de medida
  - Modificar `SaleStoreController` para validar cantidades según unidad del producto
  - Actualizar `SalesController::show()` para incluir información de unidades en respuestas
  - Modificar `ProductsController::updatePriceAndQty()` para manejar cantidades con unidades
  - _Requisitos: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 6. Adaptar sistema de compras para manejar unidades de medida
  - Modificar `PurchaseStoreController` para validar cantidades según unidad del producto
  - Actualizar `PurchasesController::show()` para incluir información de unidades
  - Asegurar que las compras actualicen inventario correctamente con unidades
  - _Requisitos: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 7. Adaptar sistema de devoluciones para manejar unidades de medida
  - Modificar controladores de `Refunds` para mostrar unidades en detalles de devolución
  - Validar que cantidades devueltas no excedan cantidades originales por unidad
  - Actualizar inventario correctamente al procesar devoluciones con unidades
  - _Requisitos: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 8. Actualizar sistema de eliminación de ventas para unidades de medida
  - Modificar `SaleDeleteController::reverseProductStock()` para manejar diferentes unidades
  - Asegurar que la reversión de stock funcione correctamente con cantidades decimales
  - Validar que las operaciones matemáticas sean precisas para diferentes tipos de unidades
  - _Requisitos: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 9. Implementar validador de cantidades por tipo de unidad
  - Crear clase `QuantityValidator` para validar cantidades según unidad de medida
  - Implementar reglas para unidades que permiten decimales vs enteras únicamente
  - Integrar validador en todos los controladores que manejan cantidades de productos
  - _Requisitos: 2.5, 3.2, 4.2, 5.2_

- [ ] 10. Actualizar rutas API para incluir endpoints de unidades de medida
  - Agregar rutas RESTful para `MeasurementUnitsController` en `routes/api.php`
  - Documentar nuevos endpoints en comentarios para Swagger
  - Mantener compatibilidad total con rutas existentes
  - _Requisitos: 7.1, 7.2_

- [ ] 11. Crear seeder para datos iniciales de unidades de medida
  - Implementar `MeasurementUnitsSeeder` con unidades predefinidas del sistema
  - Asegurar que el seeder sea idempotente (no duplicar datos en múltiples ejecuciones)
  - Incluir seeder en `DatabaseSeeder` principal
  - _Requisitos: 1.2_

- [ ] 12. Implementar migración de compatibilidad para productos existentes
  - Crear comando Artisan para migrar productos existentes a unidad "Unidad"
  - Implementar lógica para asignar automáticamente unidad predeterminada
  - Agregar logging para rastrear productos migrados
  - _Requisitos: 1.5, 7.3, 7.4_

- [ ]* 13. Crear pruebas unitarias para validación de cantidades
  - Escribir tests para `QuantityValidator` con diferentes tipos de unidades
  - Probar validación de cantidades decimales vs enteras
  - Verificar manejo de casos edge (cantidades negativas, cero, no numéricas)
  - _Requisitos: 2.5, 3.2, 4.2_

- [ ]* 14. Crear pruebas de integración para flujos completos
  - Probar flujo completo de venta con productos de diferentes unidades
  - Verificar actualización correcta de inventario con unidades mixtas
  - Probar eliminación de ventas con reversión de stock para diferentes unidades
  - _Requisitos: 3.1-3.5, 6.1-6.5_

- [ ]* 15. Implementar pruebas de compatibilidad hacia atrás
  - Verificar que APIs existentes funcionen sin cambios
  - Probar que productos sin unidad asignada usen fallback correcto
  - Validar que reportes incluyan información de unidades sin romper formato
  - _Requisitos: 7.1, 7.2, 7.4, 7.5_