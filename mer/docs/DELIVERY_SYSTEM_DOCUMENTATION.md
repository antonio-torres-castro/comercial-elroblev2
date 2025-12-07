# Sistema de Entregas - Documentación Completa

## 📋 Descripción General

El Sistema de Entregas es un módulo completo para la gestión de entregas, métodos de envío, repartidores y seguimiento de pedidos. Está diseñado para integrarse seamlessly con el sistema de tiendas existente.

## 🚀 Características Principales

### ✅ Gestión Completa de Entregas
- Creación, edición y seguimiento de entregas
- Estados de entrega actualizables en tiempo real
- Historial completo de actividades
- Integración con órdenes de compra

### ✅ Métodos de Entrega Configurables
- Múltiples tipos: estándar, express, mismo día, programado
- Costos personalizados por peso, distancia y volumen
- Restricciones configurables (peso, volumen, distancia)
- Áreas de cobertura personalizables

### ✅ Gestión de Repartidores
- Registro y administración de repartidores
- Asignación automática o manual de entregas
- Seguimiento de estado de repartidores
- Historial de entregas por repartidor

### ✅ Sistema de Seguimiento
- Actualizaciones en tiempo real del estado
- Notificaciones automáticas al cliente
- Registro detallado de actividades
- Reportes y estadísticas

## 📊 Estructura de Base de Datos

### Tablas Principales

#### 1. `delivery_methods`
Almacena los métodos de entrega disponibles para cada tienda.

```sql
- id: Identificador único
- store_id: ID de la tienda
- name: Nombre del método
- type: Tipo (standard, express, same_day, scheduled)
- base_cost: Costo base
- cost_per_kg: Costo por kilogramo
- cost_per_km: Costo por kilómetro
- delivery_time_days: Días estimados de entrega
- max_weight: Peso máximo
- max_volume: Volumen máximo
- coverage_areas: Áreas de cobertura (JSON)
```

#### 2. `delivery_drivers`
Gestiona la información de los repartidores.

```sql
- id: Identificador único
- store_id: ID de la tienda
- name: Nombre completo
- phone: Teléfono de contacto
- email: Email del repartidor
- vehicle_type: Tipo de vehículo
- license_plate: Patente del vehículo
- status: Estado (active, inactive, busy)
- max_deliveries_per_day: Máximo entregas por día
- coverage_areas: Áreas de cobertura (JSON)
```

#### 3. `deliversies`
Tabla principal que almacena las entregas.

```sql
- id: Identificador único
- store_id: ID de la tienda
- order_id: ID de la orden asociada
- delivery_method_id: Método de entrega
- assigned_driver_id: Repartidor asignado
- customer_name: Nombre del cliente
- customer_phone: Teléfono del cliente
- delivery_address: Dirección de entrega
- status: Estado de la entrega
- scheduled_date: Fecha programada
- delivered_date: Fecha de entrega
- delivery_cost: Costo de entrega
- notes: Notas adicionales
```

#### 4. `delivery_activity_log`
Registro detallado de todas las actividades del sistema.

```sql
- id: Identificador único
- delivery_id: ID de la entrega
- activity_type: Tipo de actividad
- description: Descripción de la actividad
- created_by: Usuario que realizó la actividad
- created_at: Timestamp de la actividad
```

## 🛠️ Instalación

### Paso 1: Ejecutar Script de Base de Datos
```bash
php install_delivery_system.php
```

### Paso 2: Verificar Instalación
```bash
php verify_delivery_system.php
```

### Paso 3: Integrar en la Tienda
El módulo se integra automáticamente en el panel de administración:
- URL: `/public/admin_store_deliveries.php`
- Acceso: Panel de administración de la tienda

## 📖 Guía de Uso

### Para Administradores

#### 1. Configurar Métodos de Entrega
1. Acceder al módulo de entregas
2. Ir a "Métodos de Entrega"
3. Crear nuevo método o editar existente
4. Configurar costos y restricciones
5. Definir áreas de cobertura

#### 2. Gestionar Repartidores
1. Ir a "Repartidores"
2. Agregar nuevo repartidor
3. Configurar datos de contacto y vehículo
4. Establecer áreas de cobertura
5. Activar/desactivar según necesidad

#### 3. Procesar Entregas
1. Crear nueva entrega desde orden
2. Seleccionar método de entrega
3. Asignar repartidor (manual o automático)
4. Actualizar estados según progreso
5. Gestionar incidencias y devoluciones

### Estados de Entrega

| Estado | Descripción |
|--------|-------------|
| `pending` | Entrega pendiente de procesamiento |
| `confirmed` | Entrega confirmada |
| `assigned` | Repartidor asignado |
| `picked_up` | Producto recogido |
| `in_transit` | En camino al destino |
| `delivered` | Entrega completada |
| `failed` | Entrega fallida |
| `returned` | Producto devuelto |
| `cancelled` | Entrega cancelada |

## 🔧 Funciones del Sistema

### Funciones Disponibles

#### `hasStorePermission($store_id, $user_id, $action = 'view')`
Verifica si un usuario tiene permisos para realizar acciones en una tienda.

#### `getStoreDeliveryDrivers($store_id, $status = 'active')`
Obtiene la lista de repartidores activos para una tienda.

#### `createDelivery($data)`
Crea una nueva entrega en el sistema.

#### `updateDeliveryStatus($delivery_id, $status, $notes = null)`
Actualiza el estado de una entrega.

#### `assignDriverToDelivery($delivery_id, $driver_id)`
Asigna un repartidor a una entrega específica.

## 🎯 API y Integración

### Endpoints AJAX Disponibles

#### Actualizar Estado de Entrega
```javascript
POST /public/admin_store_deliveries.php
{
  "action": "update_delivery_status",
  "delivery_id": 123,
  "status": "in_transit",
  "notes": "Salió a las 14:30"
}
```

#### Asignar Repartidor
```javascript
POST /public/admin_store_deliveries.php
{
  "action": "assign_driver",
  "delivery_id": 123,
  "driver_id": 45
}
```

#### Obtener Lista de Entregas
```javascript
POST /public/admin_store_deliveries.php
{
  "action": "get_deliveries",
  "store_id": 1,
  "status": "pending"
}
```

## 📊 Reportes y Analytics

### Métricas Disponibles
- Entregas por período
- Tiempo promedio de entrega
- Tasa de éxito de entregas
- Repartidores más eficientes
- Métodos de entrega más utilizados
- Áreas de mayor demanda

### Generación de Reportes
Los reportes se pueden generar desde el módulo administrativo o mediante consultas SQL personalizadas.

## 🔒 Seguridad

### Permisos
- Ver entregas: `view_deliveries`
- Crear entregas: `create_deliveries`
- Editar entregas: `edit_deliveries`
- Eliminar entregas: `delete_deliveries`
- Gestionar repartidores: `manage_drivers`
- Configurar métodos: `manage_delivery_methods`

### Validaciones
- Todas las entradas se validan contra SQL injection
- Los permisos se verifican en cada operación
- Los logs de actividad se registran automáticamente

## 🐛 Solución de Problemas

### Problemas Comunes

#### 1. "Error de conexión a base de datos"
**Solución**: Verificar credenciales en `src/config.php`

#### 2. "Métodos de entrega no aparecen"
**Solución**: Verificar que las tablas estén creadas correctamente

#### 3. "Repartidores no se pueden asignar"
**Solución**: Verificar que el repartidor esté activo y disponible

#### 4. "Estados no se actualizan"
**Solución**: Verificar que JavaScript esté habilitado y no haya errores en consola

### Logs de Error
Los errores se registran en:
- Logs del servidor web
- Base de datos: tabla `delivery_activity_log`
- Logs PHP si están habilitados

## 🔄 Actualizaciones y Mantenimiento

### Backup
Antes de cualquier actualización, hacer backup de:
- Base de datos
- Archivos del módulo
- Configuraciones personalizadas

### Proceso de Actualización
1. Hacer backup completo
2. Ejecutar nuevos scripts SQL
3. Actualizar archivos PHP
4. Verificar funcionamiento
5. Probar funcionalidades críticas

## 📞 Soporte

### Información de Contacto
- Desarrollador: MiniMax Agent
- Versión: 1.0
- Fecha: 2025-12-07

### Recursos Adicionales
- Documentación técnica: Este archivo
- Scripts de instalación: `install_delivery_system.php`
- Verificación del sistema: `verify_delivery_system.php`
- Código fuente: `public/admin_store_deliveries.php`

---

**¡El Sistema de Entregas está listo para operar!** 🚀

Para más información o soporte, consulte la documentación técnica o contacte al desarrollador.