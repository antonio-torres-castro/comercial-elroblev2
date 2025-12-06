CREATE TABLE addresses (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  type enum('billing','shipping','both') NOT NULL DEFAULT 'both',
  label varchar(100) DEFAULT NULL,
  street_address varchar(255) NOT NULL,
  city varchar(100) NOT NULL,
  state_province varchar(100) NOT NULL,
  postal_code varchar(20) NOT NULL,
  country varchar(100) NOT NULL DEFAULT 'Chile',
  is_default tinyint(1) DEFAULT '0',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_addresses (user_id),
  KEY idx_default_addresses (user_id,is_default),
  CONSTRAINT addresses_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE appointment_reminders (
  id int NOT NULL AUTO_INCREMENT,
  appointment_id int NOT NULL,
  reminder_type enum('confirmation','reminder','follow_up') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reminder',
  reminder_date datetime NOT NULL COMMENT 'Fecha y hora del recordatorio',
  message text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mensaje del recordatorio',
  status enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  sent_at timestamp NULL DEFAULT NULL COMMENT 'Fecha de envío',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_appointment_id (appointment_id),
  KEY idx_reminder_date (reminder_date),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recordatorios automáticos para citas';

CREATE TABLE appointment_status_history (
  id int NOT NULL AUTO_INCREMENT,
  appointment_id int NOT NULL,
  old_status varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  new_status varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  reason text COLLATE utf8mb4_unicode_ci,
  changed_by int DEFAULT NULL,
  changed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_appointment_id (appointment_id),
  KEY idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de cambios de estado de citas';

CREATE TABLE appointment_time_slots (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  date date NOT NULL,
  start_time time NOT NULL,
  end_time time NOT NULL,
  is_available tinyint(1) NOT NULL DEFAULT '1',
  notes text COLLATE utf8mb4_unicode_ci,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_time_slot (store_id,date,start_time),
  KEY idx_date (date),
  KEY idx_time_slots_store_date_available (store_id,date,is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Disponibilidad específica de horarios';

CREATE TABLE config_definitions (
  id int NOT NULL AUTO_INCREMENT,
  category varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  config_key varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  display_name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  description text COLLATE utf8mb4_unicode_ci,
  data_type enum('string','integer','boolean','json','email','url') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  default_value text COLLATE utf8mb4_unicode_ci,
  validation_rules json DEFAULT NULL,
  options json DEFAULT NULL,
  is_encrypted tinyint(1) NOT NULL DEFAULT '0',
  is_required tinyint(1) NOT NULL DEFAULT '0',
  sort_order int NOT NULL DEFAULT '0',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_category_key (category,config_key),
  KEY idx_category (category)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuration_logs (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  user_id int DEFAULT NULL,
  action varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  category varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  config_key varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  old_value text COLLATE utf8mb4_unicode_ci,
  new_value text COLLATE utf8mb4_unicode_ci,
  ip_address varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  user_agent text COLLATE utf8mb4_unicode_ci,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store_id (store_id),
  KEY idx_action_date (action,created_at),
  KEY idx_category (category),
  KEY idx_logs_created_at (created_at)
) ENGINE=InnoDB AUTO_INCREMENT=379 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons (
  id int NOT NULL AUTO_INCREMENT,
  code varchar(50) NOT NULL,
  type enum('free_shipping','percent','amount') NOT NULL,
  value decimal(10,2) DEFAULT NULL,
  expires_at datetime NOT NULL,
  active tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (id),
  UNIQUE KEY code (code)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE deliveries (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL COMMENT 'ID de la tienda',
  order_id int DEFAULT NULL COMMENT 'ID de la orden asociada',
  order_number varchar(50) DEFAULT NULL COMMENT 'Número de orden',
  delivery_method_id int DEFAULT NULL COMMENT 'ID del método de entrega',
  assigned_driver_id int DEFAULT NULL COMMENT 'ID del repartidor asignado',
  customer_name varchar(200) NOT NULL COMMENT 'Nombre completo del cliente',
  customer_phone varchar(50) NOT NULL COMMENT 'Teléfono del cliente',
  customer_email varchar(200) DEFAULT NULL COMMENT 'Email del cliente',
  delivery_address text NOT NULL COMMENT 'Dirección de entrega completa',
  delivery_city varchar(100) NOT NULL COMMENT 'Ciudad de entrega',
  delivery_zip_code varchar(20) DEFAULT NULL COMMENT 'Código postal',
  delivery_instructions text COMMENT 'Instrucciones especiales de entrega',
  order_total decimal(10,2) DEFAULT NULL COMMENT 'Total de la orden',
  delivery_cost decimal(10,2) DEFAULT '0.00' COMMENT 'Costo de entrega',
  items_count int DEFAULT NULL COMMENT 'Cantidad de productos',
  total_weight decimal(10,2) DEFAULT NULL COMMENT 'Peso total en kg',
  scheduled_date date DEFAULT NULL COMMENT 'Fecha programada de entrega',
  scheduled_time_slot varchar(50) DEFAULT NULL COMMENT 'Franja horaria programada (ej: 09:00-12:00)',
  estimated_delivery_time timestamp NULL DEFAULT NULL COMMENT 'Tiempo estimado de entrega',
  actual_delivery_time timestamp NULL DEFAULT NULL COMMENT 'Tiempo real de entrega',
  delivery_duration_minutes int DEFAULT NULL COMMENT 'Duración de la entrega en minutos',
  status enum('pendiente','programada','en_transito','entregada','fallida','cancelada') NOT NULL DEFAULT 'pendiente' COMMENT 'Estado de la entrega',
  priority enum('baja','normal','alta','urgente') DEFAULT 'normal' COMMENT 'Prioridad de la entrega',
  is_fragile tinyint(1) DEFAULT '0' COMMENT 'Indica si el paquete es frágil',
  requires_signature tinyint(1) DEFAULT '0' COMMENT 'Requiere firma de recepción',
  delivery_latitude decimal(10,8) DEFAULT NULL COMMENT 'Latitud del destino',
  delivery_longitude decimal(11,8) DEFAULT NULL COMMENT 'Longitud del destino',
  driver_current_latitude decimal(10,8) DEFAULT NULL COMMENT 'Latitud actual del repartidor',
  driver_current_longitude decimal(11,8) DEFAULT NULL COMMENT 'Longitud actual del repartidor',
  last_location_update timestamp NULL DEFAULT NULL COMMENT 'Última actualización de ubicación',
  tracking_number varchar(100) DEFAULT NULL COMMENT 'Número de seguimiento',
  notes text COMMENT 'Notas internas de la entrega',
  delivery_proof_url varchar(500) DEFAULT NULL COMMENT 'URL de la foto de entrega',
  recipient_signature_url varchar(500) DEFAULT NULL COMMENT 'URL de la firma del receptor',
  failure_reason text COMMENT 'Razón del fallo de entrega',
  return_address text COMMENT 'Dirección de devolución en caso de fallo',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store_status (store_id,status),
  KEY idx_order_id (order_id),
  KEY idx_scheduled_date (scheduled_date),
  KEY idx_delivery_city (delivery_city),
  KEY idx_assigned_driver (assigned_driver_id),
  KEY idx_delivery_method (delivery_method_id),
  KEY idx_tracking_number (tracking_number),
  KEY idx_customer_phone (customer_phone),
  KEY idx_status_date (status,scheduled_date),
  KEY idx_deliveries_status_date_store (status,scheduled_date,store_id),
  KEY idx_deliveries_customer_search (customer_name,customer_phone,delivery_city),
  CONSTRAINT deliveries_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT deliveries_ibfk_2 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL,
  CONSTRAINT deliveries_ibfk_3 FOREIGN KEY (delivery_method_id) REFERENCES delivery_methods (id) ON DELETE SET NULL,
  CONSTRAINT deliveries_ibfk_4 FOREIGN KEY (assigned_driver_id) REFERENCES delivery_drivers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tabla principal de entregas';

CREATE TABLE delivery_activity_log (
  id int NOT NULL AUTO_INCREMENT,
  delivery_id int NOT NULL COMMENT 'ID de la entrega',
  action varchar(100) NOT NULL COMMENT 'Acción realizada (creado, actualizado, asignado, etc.)',
  description text NOT NULL COMMENT 'Descripción detallada de la actividad',
  user_id int DEFAULT NULL COMMENT 'ID del usuario que realizó la acción',
  user_type enum('admin','store_admin','customer','driver','system') DEFAULT NULL COMMENT 'Tipo de usuario',
  old_values json DEFAULT NULL COMMENT 'Valores anteriores (JSON)',
  new_values json DEFAULT NULL COMMENT 'Valores nuevos (JSON)',
  changed_fields json DEFAULT NULL COMMENT 'Campos que cambiaron (JSON)',
  ip_address varchar(45) DEFAULT NULL COMMENT 'Dirección IP del usuario',
  user_agent text COMMENT 'User agent del navegador',
  session_id varchar(100) DEFAULT NULL COMMENT 'ID de sesión',
  request_id varchar(100) DEFAULT NULL COMMENT 'ID de la petición',
  latitude decimal(10,8) DEFAULT NULL COMMENT 'Latitud cuando ocurrió la actividad',
  longitude decimal(11,8) DEFAULT NULL COMMENT 'Longitud cuando ocurrió la actividad',
  duration_seconds int DEFAULT NULL COMMENT 'Duración de la acción en segundos',
  priority enum('low','normal','high') DEFAULT 'normal' COMMENT 'Prioridad del log',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_delivery_id (delivery_id),
  KEY idx_action (action),
  KEY idx_user_id (user_id),
  KEY idx_created_at (created_at),
  KEY idx_delivery_action (delivery_id,action),
  KEY idx_user_action (user_id,action),
  KEY idx_date_action (created_at,action),
  KEY idx_activity_logs_date_range (delivery_id,created_at,action),
  CONSTRAINT delivery_activity_log_ibfk_1 FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Historial detallado de actividades de entregas';

CREATE TABLE delivery_coupons (
  id int NOT NULL AUTO_INCREMENT,
  code varchar(50) NOT NULL,
  discount_type enum('fixed','percentage') NOT NULL,
  discount_value decimal(10,2) NOT NULL,
  min_order_amount decimal(10,2) DEFAULT '0.00',
  max_discount_amount decimal(10,2) DEFAULT NULL,
  usage_limit int DEFAULT NULL,
  used_count int DEFAULT '0',
  valid_from timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  valid_until timestamp NULL DEFAULT NULL,
  is_active tinyint(1) DEFAULT '1',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY code (code),
  KEY idx_code (code),
  KEY idx_valid (valid_from,valid_until)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE delivery_drivers (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL COMMENT 'ID de la tienda',
  name varchar(200) NOT NULL COMMENT 'Nombre completo del repartidor',
  phone varchar(50) NOT NULL COMMENT 'Teléfono de contacto',
  email varchar(200) DEFAULT NULL COMMENT 'Email del repartidor',
  license_number varchar(100) DEFAULT NULL COMMENT 'Número de licencia de conducir',
  license_expiry date DEFAULT NULL COMMENT 'Fecha de vencimiento de licencia',
  vehicle_type enum('motorcycle','car','bicycle','walking','other') NOT NULL COMMENT 'Tipo de vehículo',
  vehicle_make varchar(100) DEFAULT NULL COMMENT 'Marca del vehículo',
  vehicle_model varchar(100) DEFAULT NULL COMMENT 'Modelo del vehículo',
  vehicle_year int DEFAULT NULL COMMENT 'Año del vehículo',
  vehicle_plate varchar(20) DEFAULT NULL COMMENT 'Patente del vehículo',
  vehicle_color varchar(50) DEFAULT NULL COMMENT 'Color del vehículo',
  max_weight_capacity decimal(8,2) DEFAULT NULL COMMENT 'Capacidad máxima de peso en kg',
  max_volume_capacity decimal(8,2) DEFAULT NULL COMMENT 'Capacidad máxima de volumen en litros',
  max_distance_per_day decimal(8,2) DEFAULT NULL COMMENT 'Distancia máxima diaria en km',
  active tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado activo/inactivo',
  status enum('available','busy','offline','break','maintenance') DEFAULT 'available' COMMENT 'Estado actual',
  current_latitude decimal(10,8) DEFAULT NULL COMMENT 'Latitud actual',
  current_longitude decimal(11,8) DEFAULT NULL COMMENT 'Longitud actual',
  last_location_update timestamp NULL DEFAULT NULL COMMENT 'Última actualización de ubicación',
  working_hours_start time DEFAULT NULL COMMENT 'Hora de inicio de trabajo',
  working_hours_end time DEFAULT NULL COMMENT 'Hora de fin de trabajo',
  working_days varchar(50) DEFAULT '1,2,3,4,5' COMMENT 'Días de trabajo',
  max_deliveries_per_day int DEFAULT NULL COMMENT 'Máximo de entregas por día',
  delivery_radius_km decimal(8,2) DEFAULT NULL COMMENT 'Radio de entrega máximo en km',
  total_deliveries int DEFAULT '0' COMMENT 'Total de entregas realizadas',
  successful_deliveries int DEFAULT '0' COMMENT 'Entregas exitosas',
  failed_deliveries int DEFAULT '0' COMMENT 'Entregas fallidas',
  average_delivery_time int DEFAULT NULL COMMENT 'Tiempo promedio de entrega en minutos',
  customer_rating decimal(3,2) DEFAULT NULL COMMENT 'Calificación promedio (1.0-5.0)',
  total_earnings decimal(10,2) DEFAULT '0.00' COMMENT 'Ganancias totales',
  can_handle_fragile tinyint(1) DEFAULT '0' COMMENT 'Puede manejar paquetes frágiles',
  can_handle_cod tinyint(1) DEFAULT '0' COMMENT 'Puede manejar pagos contra entrega',
  preferred_zones json DEFAULT NULL COMMENT 'Zonas preferidas de entrega',
  excluded_zones json DEFAULT NULL COMMENT 'Zonas excluidas',
  notes text COMMENT 'Notas sobre el repartidor',
  emergency_contact varchar(200) DEFAULT NULL COMMENT 'Contacto de emergencia',
  emergency_phone varchar(50) DEFAULT NULL COMMENT 'Teléfono de emergencia',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store_active (store_id,active),
  KEY idx_status (status),
  KEY idx_phone (phone),
  KEY idx_vehicle_type (vehicle_type),
  KEY idx_location (current_latitude,current_longitude),
  KEY idx_working_hours (working_hours_start,working_hours_end),
  KEY idx_drivers_availability (store_id,active,status,current_latitude,current_longitude),
  CONSTRAINT delivery_drivers_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Repartidores asociados a las tiendas';

CREATE TABLE delivery_group_items (
  id int NOT NULL AUTO_INCREMENT,
  delivery_group_id int NOT NULL,
  order_item_id int NOT NULL,
  quantity int NOT NULL,
  unit_price decimal(10,2) NOT NULL,
  subtotal decimal(10,2) NOT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_item_group (delivery_group_id,order_item_id),
  KEY order_item_id (order_item_id),
  KEY idx_group (delivery_group_id),
  CONSTRAINT delivery_group_items_ibfk_1 FOREIGN KEY (delivery_group_id) REFERENCES delivery_groups (id) ON DELETE CASCADE,
  CONSTRAINT delivery_group_items_ibfk_2 FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE delivery_groups (
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  group_name varchar(100) NOT NULL,
  group_description text,
  delivery_address text NOT NULL,
  delivery_city varchar(100) NOT NULL,
  delivery_contact_name varchar(200) NOT NULL,
  delivery_contact_phone varchar(50) NOT NULL,
  delivery_contact_email varchar(200) DEFAULT NULL,
  pickup_location_id int DEFAULT NULL,
  delivery_date date DEFAULT NULL,
  delivery_time_slot time DEFAULT NULL,
  shipping_cost decimal(10,2) DEFAULT '0.00',
  status enum('pending','preparing','ready','dispatched','delivered','cancelled') DEFAULT 'pending',
  delivery_notes text,
  estimated_delivery_time timestamp NULL DEFAULT NULL,
  actual_delivery_time timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY pickup_location_id (pickup_location_id),
  KEY idx_order (order_id),
  KEY idx_status (status),
  KEY idx_delivery_date (delivery_date),
  CONSTRAINT delivery_groups_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT delivery_groups_ibfk_2 FOREIGN KEY (pickup_location_id) REFERENCES pickup_locations (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE delivery_methods (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL COMMENT 'ID de la tienda',
  name varchar(100) NOT NULL COMMENT 'Nombre del método',
  description text COMMENT 'Descripción detallada del método',
  type enum('standard','express','same_day','scheduled') DEFAULT 'standard' COMMENT 'Tipo de entrega',
  base_cost decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Costo base de entrega',
  cost_per_kg decimal(10,2) DEFAULT '0.00' COMMENT 'Costo adicional por kilogramo',
  cost_per_km decimal(10,2) DEFAULT '0.00' COMMENT 'Costo adicional por kilómetro',
  delivery_time_days int NOT NULL DEFAULT '1' COMMENT 'Días de entrega estimados',
  min_delivery_time_hours int DEFAULT NULL COMMENT 'Tiempo mínimo de entrega en horas',
  max_delivery_time_hours int DEFAULT NULL COMMENT 'Tiempo máximo de entrega en horas',
  max_weight decimal(8,2) DEFAULT NULL COMMENT 'Peso máximo en kg',
  max_volume decimal(8,2) DEFAULT NULL COMMENT 'Volumen máximo en litros',
  max_distance_km decimal(8,2) DEFAULT NULL COMMENT 'Distancia máxima en km',
  min_order_amount decimal(10,2) DEFAULT '0.00' COMMENT 'Monto mínimo de orden',
  coverage_areas json DEFAULT NULL COMMENT 'Áreas de cobertura (JSON con ciudades/regiones)',
  working_hours_start time DEFAULT NULL COMMENT 'Hora de inicio de operaciones',
  working_hours_end time DEFAULT NULL COMMENT 'Hora de fin de operaciones',
  working_days varchar(50) DEFAULT '1,2,3,4,5' COMMENT 'Días laborales (1=lunes, 7=domingo)',
  booking_advance_hours int DEFAULT '24' COMMENT 'Horas mínimas de anticipación para reservar',
  max_daily_orders int DEFAULT NULL COMMENT 'Máximo de órdenes por día',
  active tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado activo/inactivo',
  auto_assign_drivers tinyint(1) DEFAULT '1' COMMENT 'Asignar repartidores automáticamente',
  requires_driver_signature tinyint(1) DEFAULT '0' COMMENT 'Requiere firma del repartidor',
  allows_cod tinyint(1) DEFAULT '0' COMMENT 'Permite pago contra entrega',
  max_cod_amount decimal(10,2) DEFAULT NULL COMMENT 'Monto máximo para pago contra entrega',
  send_sms_confirmation tinyint(1) DEFAULT '1' COMMENT 'Enviar SMS de confirmación',
  send_email_confirmation tinyint(1) DEFAULT '1' COMMENT 'Enviar email de confirmación',
  send_sms_updates tinyint(1) DEFAULT '1' COMMENT 'Enviar SMS de actualizaciones',
  send_email_updates tinyint(1) DEFAULT '1' COMMENT 'Enviar email de actualizaciones',
  sort_order int DEFAULT '0' COMMENT 'Orden de visualización',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store_active (store_id,active),
  KEY idx_type_active (type,active),
  KEY idx_name (name),
  KEY idx_sort_order (sort_order),
  CONSTRAINT delivery_methods_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Métodos de entrega disponibles por tienda';

CREATE TABLE email_verifications (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  token varchar(255) NOT NULL,
  expires_at timestamp NOT NULL,
  verified_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY token (token),
  KEY idx_verification_token (token),
  KEY idx_verification_user (user_id),
  CONSTRAINT email_verifications_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE group_shipping_methods (
  group_id int NOT NULL,
  shipping_method_id int NOT NULL,
  PRIMARY KEY (group_id,shipping_method_id),
  KEY shipping_method_id (shipping_method_id),
  CONSTRAINT group_shipping_methods_ibfk_1 FOREIGN KEY (group_id) REFERENCES product_groups (id) ON DELETE CASCADE,
  CONSTRAINT group_shipping_methods_ibfk_2 FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE order_items (
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  product_id int NOT NULL,
  store_id int NOT NULL,
  qty int NOT NULL,
  unit_price decimal(10,2) NOT NULL,
  shipping_method_id int DEFAULT NULL,
  shipping_cost_per_unit decimal(10,2) NOT NULL,
  line_subtotal decimal(10,2) NOT NULL,
  line_shipping decimal(10,2) NOT NULL,
  line_total decimal(10,2) NOT NULL,
  delivery_address varchar(255) DEFAULT NULL,
  delivery_city varchar(100) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  KEY product_id (product_id),
  KEY store_id (store_id),
  KEY shipping_method_id (shipping_method_id),
  CONSTRAINT order_items_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT order_items_ibfk_2 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT order_items_ibfk_3 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE RESTRICT,
  CONSTRAINT order_items_ibfk_4 FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE order_notifications (
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  store_id int NOT NULL,
  channel enum('email','log') NOT NULL,
  content text NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  KEY store_id (store_id),
  CONSTRAINT order_notifications_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT order_notifications_ibfk_2 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE order_store_totals (
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  store_id int NOT NULL,
  subtotal decimal(10,2) NOT NULL,
  discount decimal(10,2) NOT NULL,
  shipping decimal(10,2) NOT NULL,
  total decimal(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  KEY store_id (store_id),
  CONSTRAINT order_store_totals_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT order_store_totals_ibfk_2 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE orders (
  id int NOT NULL AUTO_INCREMENT,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  customer_name varchar(150) NOT NULL,
  email varchar(150) DEFAULT NULL,
  phone varchar(50) DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  city varchar(100) DEFAULT NULL,
  notes text,
  coupon_id int DEFAULT NULL,
  subtotal decimal(10,2) NOT NULL,
  discount decimal(10,2) NOT NULL,
  shipping decimal(10,2) NOT NULL,
  total decimal(10,2) NOT NULL,
  payment_method enum('transbank','transfer','cash') DEFAULT NULL,
  payment_status enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  payment_reference varchar(100) DEFAULT NULL,
  delivery_address text,
  delivery_city varchar(100) DEFAULT NULL,
  delivery_contact_name varchar(200) DEFAULT NULL,
  delivery_contact_phone varchar(50) DEFAULT NULL,
  delivery_contact_email varchar(200) DEFAULT NULL,
  pickup_location_id int DEFAULT NULL,
  delivery_date date DEFAULT NULL,
  delivery_time_slot time DEFAULT NULL,
  PRIMARY KEY (id),
  KEY coupon_id (coupon_id),
  CONSTRAINT orders_ibfk_1 FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE password_resets (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  token varchar(255) NOT NULL,
  expires_at timestamp NOT NULL,
  used_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY token (token),
  KEY idx_reset_token (token),
  KEY idx_reset_user (user_id),
  CONSTRAINT password_resets_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE payments (
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  method enum('transbank','transfer','cash') NOT NULL,
  amount decimal(10,2) NOT NULL,
  status enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  transaction_id varchar(100) DEFAULT NULL,
  transfer_code varchar(100) DEFAULT NULL,
  pickup_location_id int DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at datetime DEFAULT NULL,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  KEY pickup_location_id (pickup_location_id),
  CONSTRAINT payments_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT payments_ibfk_2 FOREIGN KEY (pickup_location_id) REFERENCES pickup_locations (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE pickup_locations (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  name varchar(200) NOT NULL,
  address text NOT NULL,
  city varchar(100) NOT NULL,
  phone varchar(50) DEFAULT NULL,
  hours_start time DEFAULT NULL,
  hours_end time DEFAULT NULL,
  days_of_week varchar(50) DEFAULT NULL,
  is_active tinyint(1) DEFAULT '1',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store (store_id),
  CONSTRAINT pickup_locations_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_appointments (
  id int NOT NULL AUTO_INCREMENT,
  product_id int NOT NULL,
  store_id int NOT NULL,
  appointment_date date NOT NULL,
  appointment_time time NOT NULL,
  quantity_ordered int NOT NULL DEFAULT '1',
  capacity_consumed int NOT NULL DEFAULT '1',
  status enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  order_id int DEFAULT NULL,
  customer_notes text,
  estimated_completion_time time DEFAULT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store_date (store_id,appointment_date),
  KEY idx_product_date (product_id,appointment_date),
  KEY idx_order (order_id),
  CONSTRAINT product_appointments_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT product_appointments_ibfk_2 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT product_appointments_ibfk_3 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_daily_capacity (
  id int NOT NULL AUTO_INCREMENT,
  product_id int NOT NULL,
  store_id int NOT NULL,
  capacity_date date NOT NULL,
  available_capacity int NOT NULL DEFAULT '0',
  booked_capacity int NOT NULL DEFAULT '0',
  notes text,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_product_date (product_id,capacity_date),
  KEY idx_store_date (store_id,capacity_date),
  KEY idx_product_date (product_id,capacity_date),
  CONSTRAINT product_daily_capacity_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT product_daily_capacity_ibfk_2 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_default_schedule (
  id int NOT NULL AUTO_INCREMENT,
  product_id int NOT NULL,
  day_of_week int NOT NULL COMMENT '0=domingo, 1=lunes, 2=martes, 3=miércoles, 4=jueves, 5=viernes, 6=sábado',
  start_time time NOT NULL,
  end_time time NOT NULL,
  active tinyint(1) DEFAULT '1',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_product_day (product_id,day_of_week),
  KEY idx_product_active (product_id,active),
  KEY idx_day_active (day_of_week,active),
  CONSTRAINT product_default_schedule_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_groups (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  name varchar(100) NOT NULL,
  PRIMARY KEY (id),
  KEY store_id (store_id),
  CONSTRAINT product_groups_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE product_shipping_methods (
  product_id int NOT NULL,
  shipping_method_id int NOT NULL,
  PRIMARY KEY (product_id,shipping_method_id),
  KEY shipping_method_id (shipping_method_id),
  CONSTRAINT product_shipping_methods_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT product_shipping_methods_ibfk_2 FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE products (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  name varchar(150) NOT NULL,
  description text,
  price decimal(10,2) NOT NULL,
  group_id int DEFAULT NULL,
  active tinyint(1) NOT NULL DEFAULT '1',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  stock_quantity int DEFAULT '0',
  stock_min_threshold int DEFAULT '5',
  delivery_days_min int DEFAULT '1',
  delivery_days_max int DEFAULT '3',
  service_type enum('producto','servicio','ambos') DEFAULT 'producto',
  requires_appointment tinyint(1) DEFAULT '0',
  image_url varchar(500) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY store_id (store_id),
  KEY group_id (group_id),
  CONSTRAINT products_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT products_ibfk_2 FOREIGN KEY (group_id) REFERENCES product_groups (id)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE shipping_methods (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  name varchar(100) NOT NULL,
  type enum('delivery','pickup') NOT NULL DEFAULT 'delivery',
  pickup_location_id int DEFAULT NULL,
  lead_time_days int DEFAULT NULL,
  cost decimal(10,2) NOT NULL,
  active tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (id),
  KEY store_id (store_id),
  KEY pickup_location_id (pickup_location_id),
  CONSTRAINT shipping_methods_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT shipping_methods_ibfk_2 FOREIGN KEY (pickup_location_id) REFERENCES pickup_locations (id) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE stock_movements (
  id int NOT NULL AUTO_INCREMENT,
  product_id int NOT NULL,
  store_id int NOT NULL,
  movement_type enum('in','out','adjustment') NOT NULL,
  quantity int NOT NULL,
  reference_type enum('purchase','sale','adjustment','return','damage') NOT NULL,
  reference_id int DEFAULT NULL,
  notes text,
  created_by int DEFAULT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_product (product_id),
  KEY idx_store (store_id),
  KEY idx_date (created_at),
  KEY idx_type (movement_type),
  CONSTRAINT stock_movements_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT stock_movements_ibfk_2 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE store_appointment_policies (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  hours_before_cancellation int NOT NULL DEFAULT '24' COMMENT 'Horas mínimas antes de la cita para cancelar',
  require_cancellation_reason tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Indica si se requiere razón para cancelar',
  auto_confirm_appointments tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Confirmar citas automáticamente',
  max_daily_appointments int NOT NULL DEFAULT '20' COMMENT 'Máximo número de citas por día',
  allow_double_booking tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Permitir múltiples citas simultáneas',
  penalty_amount decimal(10,2) DEFAULT NULL COMMENT 'Monto de penalización por cancelación tardía',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_store_policies (store_id)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Políticas de gestión de citas por tienda';

CREATE TABLE store_appointment_settings (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  require_cancellation_reason tinyint(1) NOT NULL DEFAULT '1',
  send_confirmation_sms tinyint(1) NOT NULL DEFAULT '1',
  send_reminder_sms tinyint(1) NOT NULL DEFAULT '1',
  reminder_hours_before int NOT NULL DEFAULT '24',
  enable_online_booking tinyint(1) NOT NULL DEFAULT '0',
  booking_advance_days int NOT NULL DEFAULT '30',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_store_appointment_settings (store_id)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración general de citas por tienda';

CREATE TABLE store_appointments (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  customer_name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre completo del cliente',
  customer_phone varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Teléfono del cliente',
  customer_email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email del cliente',
  service_id int NOT NULL COMMENT 'ID del servicio',
  appointment_date datetime NOT NULL COMMENT 'Fecha y hora de la cita',
  duration_hours decimal(4,2) NOT NULL DEFAULT '1.00' COMMENT 'Duración en horas (mínimo 0.5)',
  status enum('programada','confirmada','en_proceso','completada','cancelada','no_asistio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'programada',
  status_reason text COLLATE utf8mb4_unicode_ci COMMENT 'Razón del cambio de estado',
  notes text COLLATE utf8mb4_unicode_ci COMMENT 'Notas adicionales',
  created_by int DEFAULT NULL COMMENT 'ID del usuario que creó la cita',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_appointment (store_id,appointment_date),
  KEY idx_store_id (store_id),
  KEY idx_service_id (service_id),
  KEY idx_status (status),
  KEY idx_appointment_date (appointment_date),
  KEY idx_customer_phone (customer_phone),
  KEY idx_appointments_store_status_date (store_id,status,appointment_date),
  KEY idx_appointments_customer_date (customer_phone,appointment_date),
  CONSTRAINT chk_duration_minimum CHECK ((duration_hours >= 0.5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Citas y reservas programadas';

CREATE TABLE store_configurations (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  category varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  config_key varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  config_value text COLLATE utf8mb4_unicode_ci NOT NULL,
  description text COLLATE utf8mb4_unicode_ci,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_store_category_key (store_id,category,config_key),
  KEY idx_store_category (store_id,category),
  KEY idx_category_key (category,config_key),
  KEY idx_config_created_at (created_at),
  KEY idx_config_updated_at (updated_at)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE store_holidays (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  date date NOT NULL COMMENT 'Fecha del feriado',
  name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre del feriado',
  is_recurring tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indica si es un feriado recurrente',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_store_holiday (store_id,date),
  KEY idx_date (date)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Feriados por tienda';

CREATE TABLE store_payouts (
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  store_id int NOT NULL,
  amount decimal(10,2) NOT NULL,
  status enum('scheduled','paid','cancelled') NOT NULL DEFAULT 'scheduled',
  scheduled_at datetime DEFAULT NULL,
  paid_at datetime DEFAULT NULL,
  method varchar(50) DEFAULT NULL,
  reference varchar(100) DEFAULT NULL,
  commission_percent decimal(5,2) NOT NULL,
  commission_min decimal(10,2) NOT NULL,
  commission_amount decimal(10,2) NOT NULL,
  commission_vat_percent decimal(5,2) NOT NULL,
  commission_vat_amount decimal(10,2) NOT NULL,
  commission_gross_amount decimal(10,2) NOT NULL,
  net_amount decimal(10,2) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  KEY store_id (store_id),
  CONSTRAINT store_payouts_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT store_payouts_ibfk_2 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE store_schedule_config (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  start_time time NOT NULL DEFAULT '09:00:00' COMMENT 'Hora de inicio de atención',
  end_time time NOT NULL DEFAULT '18:00:00' COMMENT 'Hora de fin de atención',
  appointment_interval int NOT NULL DEFAULT '30' COMMENT 'Intervalo entre citas en minutos',
  working_days varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1,2,3,4,5,6' COMMENT 'Días laborales (1=Lunes, 7=Domingo)',
  timezone varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Santiago' COMMENT 'Zona horaria',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_store_schedule (store_id)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración de horarios por tienda';

CREATE TABLE store_service_zones (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  zone_name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  zone_type enum('ciudad','comuna','region') COLLATE utf8mb4_unicode_ci NOT NULL,
  city varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  region varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  max_services_per_day int NOT NULL DEFAULT '1',
  active tinyint(1) DEFAULT '1',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store_active (store_id,active),
  KEY idx_zone_type (zone_type),
  CONSTRAINT store_service_zones_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE store_services (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre del servicio',
  description text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Descripción detallada del servicio',
  default_duration_hours decimal(4,2) NOT NULL DEFAULT '1.00' COMMENT 'Duración por defecto en horas',
  price decimal(10,2) DEFAULT NULL COMMENT 'Precio del servicio',
  is_recurring tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indica si es un servicio recurrente',
  cancellation_hours_before int DEFAULT '24' COMMENT 'Horas mínimas para cancelar sin penalización',
  is_active tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado del servicio',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_store_id (store_id),
  KEY idx_name (name),
  KEY idx_active (is_active),
  KEY idx_services_store_active (store_id,is_active)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Servicios disponibles para citas';

CREATE TABLE store_settings (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  setting_key varchar(100) NOT NULL,
  setting_value text NOT NULL,
  setting_type enum('text','number','boolean','json') DEFAULT 'text',
  description text,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_store_setting (store_id,setting_key),
  KEY idx_store (store_id),
  CONSTRAINT store_settings_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE stores (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(120) NOT NULL,
  slug varchar(80) NOT NULL,
  logo_url varchar(255) DEFAULT NULL,
  primary_color varchar(20) DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  delivery_time_days_min int DEFAULT NULL,
  delivery_time_days_max int DEFAULT NULL,
  contact_email varchar(150) DEFAULT NULL,
  payout_delay_days int DEFAULT NULL,
  commission_rate_percent decimal(5,2) DEFAULT NULL,
  commission_min_amount decimal(10,2) DEFAULT NULL,
  tax_rate_percent decimal(5,2) DEFAULT NULL,
  config_count int DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE user_profiles (
  user_id int NOT NULL,
  first_name varchar(100) NOT NULL,
  last_name varchar(100) NOT NULL,
  phone varchar(20) DEFAULT NULL,
  birth_date date DEFAULT NULL,
  preferences json DEFAULT NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT user_profiles_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE user_roles (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  role enum('admin','store_admin','customer') NOT NULL,
  store_id int DEFAULT NULL,
  granted_by int DEFAULT NULL,
  granted_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_user_store_role (user_id,store_id,role),
  KEY granted_by (granted_by),
  KEY idx_user_role (user_id,role),
  KEY idx_store_admin (store_id,role),
  CONSTRAINT user_roles_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT user_roles_ibfk_2 FOREIGN KEY (granted_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE users (
  id int NOT NULL AUTO_INCREMENT,
  email varchar(255) NOT NULL,
  password_hash varchar(255) NOT NULL,
  email_verified_at timestamp NULL DEFAULT NULL,
  status enum('active','inactive','suspended','pending_verification') DEFAULT 'pending_verification',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY email (email),
  KEY idx_email (email),
  KEY idx_status (status)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
