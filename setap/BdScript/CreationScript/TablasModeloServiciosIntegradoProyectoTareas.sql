CREATE TABLE IF NOT EXISTS servicio_categorias (
  id int NOT NULL AUTO_INCREMENT,
  parent_id int DEFAULT NULL,
  nombre varchar(100) DEFAULT NULL,
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY parent_id (parent_id),
  CONSTRAINT servicio_categorias_parent_fk FOREIGN KEY (parent_id) REFERENCES servicio_categorias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Clases de servicio';

CREATE TABLE IF NOT EXISTS servicio_tipos (
  id int NOT NULL AUTO_INCREMENT,
  proveedor_id int NOT NULL,
  servicio_categoria_id int DEFAULT NULL,
  codigo varchar(50) DEFAULT NULL,
  nombre varchar(150) NOT NULL,
  descripcion text,
  color varchar(20) DEFAULT NULL,
  requiere_aprobacion_cliente tinyint DEFAULT '1',
  requiere_firma_cliente tinyint DEFAULT '1',
  genera_proyecto_servicio tinyint DEFAULT '1',
  duracion_estimada_dias int DEFAULT NULL,
  estado_tipo_id int DEFAULT NULL,
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_servicio_categoria (servicio_categoria_id),
  KEY fk_servicio_tipo_estado (estado_tipo_id),
  KEY fk_servicio_tipo_proveedor (proveedor_id),
  CONSTRAINT fk_servicio_tipo_estado FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id),
  CONSTRAINT fk_servicio_tipo_categoria FOREIGN KEY (servicio_categoria_id) REFERENCES servicio_categorias (id),
  CONSTRAINT fk_servicio_tipo_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios (
  id int NOT NULL AUTO_INCREMENT,
  proveedor_id int NOT NULL,
  servicio_tipo_id int NOT NULL,
  codigo varchar(50) DEFAULT NULL,
  nombre varchar(150) NOT NULL,
  descripcion text,
  activo tinyint DEFAULT '1',
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_tipo_id (servicio_tipo_id),
  KEY servicio_proveedor_id (proveedor_id),
  CONSTRAINT servicios_tipo_fk FOREIGN KEY (servicio_tipo_id) REFERENCES servicio_tipos (id),
  CONSTRAINT servicios_proveedor_fk FOREIGN KEY (proveedor_id) REFERENCES proveedores (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios_versiones (
  id int NOT NULL AUTO_INCREMENT,
  servicio_id int NOT NULL,
  version int NOT NULL,
  nombre_version varchar(150) DEFAULT NULL,
  descripcion text,
  precio_base decimal(12,2) DEFAULT NULL,
  tiempo_estimado_dias int DEFAULT NULL,
  vigente_desde date DEFAULT NULL,
  vigente_hasta date DEFAULT NULL,
  activo tinyint DEFAULT '1',
  ind_version_actual tinyint DEFAULT '1',
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_id (servicio_id),
  CONSTRAINT servicios_versiones_servicio_fk FOREIGN KEY (servicio_id) REFERENCES servicios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios_detalle (
  id int NOT NULL AUTO_INCREMENT,
  servicio_version_id int NOT NULL,
  proveedor_proceso_id int NOT NULL,
  orden_ejecucion int DEFAULT '1',
  dias_desde_inicio int DEFAULT '0',
  obligatorio tinyint DEFAULT '1',
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_version_id (servicio_version_id),
  KEY proveedor_proceso_id (proveedor_proceso_id),
  CONSTRAINT servicios_detalle_version_fk FOREIGN KEY (servicio_version_id) REFERENCES servicios_versiones (id),
  CONSTRAINT servicios_detalle_proceso_fk FOREIGN KEY (proveedor_proceso_id) REFERENCES proveedor_procesos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios_detalle_insumos (
  id int NOT NULL AUTO_INCREMENT,
  servicio_detalle_id int NOT NULL,
  nombre varchar(150) NOT NULL,
  descripcion text,
  cantidad decimal(10,2) DEFAULT '1.00',
  unidad_medida varchar(50) DEFAULT NULL,
  obligatorio tinyint DEFAULT '1',
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_detalle_id (servicio_detalle_id),
  CONSTRAINT servicios_detalle_insumos_detalle_fk FOREIGN KEY (servicio_detalle_id) REFERENCES servicios_detalle (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios_detalle_activos (
  id int NOT NULL AUTO_INCREMENT,
  servicio_detalle_id int NOT NULL,
  nombre varchar(150) NOT NULL,
  descripcion text,
  cantidad int DEFAULT '1',
  obligatorio tinyint DEFAULT '1',
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_detalle_id (servicio_detalle_id),
  CONSTRAINT servicios_detalle_activos_detalle_fk FOREIGN KEY (servicio_detalle_id) REFERENCES servicios_detalle (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios_planificados (
  id int NOT NULL AUTO_INCREMENT,
  proveedor_id int NOT NULL,
  cliente_id int NOT NULL,
  servicio_version_id int NOT NULL,
  version_nombre_snapshot varchar(150) DEFAULT NULL,
  nombre varchar(200) DEFAULT NULL,
  descripcion text,
  fecha_inicio date NOT NULL,
  fecha_termino_estimada date NOT NULL,
  fecha_termino_real date DEFAULT NULL,
  porcentaje_avance decimal(5,2) DEFAULT '0.00',
  estado_id int DEFAULT NULL,
  presupuesto_id int DEFAULT NULL,
  observaciones text,
  estado_operacional_id int DEFAULT NULL,
  usuario_creacion_id int DEFAULT NULL,
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_generacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_version_id (servicio_version_id),
  KEY proveedor_id (proveedor_id),
  KEY cliente_id (cliente_id),
  KEY usuario_creacion_id (usuario_creacion_id),
  KEY estado_operacional_id (estado_operacional_id),
  CONSTRAINT servicios_planificados_version_fk FOREIGN KEY (servicio_version_id) REFERENCES servicios_versiones (id),
  CONSTRAINT servicios_planificados_usuario_fk FOREIGN KEY (usuario_creacion_id) REFERENCES usuarios (id),
  CONSTRAINT servicios_planificados_estado_fk FOREIGN KEY (estado_operacional_id) REFERENCES estado_tipos (id),
  CONSTRAINT servicios_planificados_proveedor_fk FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
  CONSTRAINT servicios_planificados_cliente_fk FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios_planificados_proyectos (
  id int NOT NULL AUTO_INCREMENT,
  servicio_planificado_id int NOT NULL,
  proyecto_id int NOT NULL,
  orden int DEFAULT '1',
  fecha_inicio date NOT NULL,
  fecha_termino date NOT NULL,
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_planificado_id (servicio_planificado_id),
  KEY proyecto_id (proyecto_id),
  CONSTRAINT servicios_planificados_proyectos_plan_fk FOREIGN KEY (servicio_planificado_id) REFERENCES servicios_planificados (id),
  CONSTRAINT servicios_planificados_proyectos_proyecto_fk FOREIGN KEY (proyecto_id) REFERENCES proyectos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS servicios_planificados_tareas (
  id int NOT NULL AUTO_INCREMENT,
  servicio_planificado_id int NOT NULL,
  proyecto_tarea_id int NOT NULL,
  orden_visualizacion int DEFAULT '1',
  ind_replanificada tinyint DEFAULT '0',
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_programada_original date DEFAULT NULL,
  PRIMARY KEY (id),
  KEY servicio_planificado_id (servicio_planificado_id),
  KEY proyecto_tarea_id (proyecto_tarea_id),
  CONSTRAINT servicios_planificados_tareas_plan_fk FOREIGN KEY (servicio_planificado_id) REFERENCES servicios_planificados (id),
  CONSTRAINT servicios_planificados_tareas_tarea_fk FOREIGN KEY (proyecto_tarea_id) REFERENCES proyecto_tareas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS historial_servicios (
  id int NOT NULL AUTO_INCREMENT,
  servicio_planificado_id int NOT NULL,
  usuario_id int DEFAULT NULL,
  accion varchar(80) NOT NULL,
  estado_anterior varchar(80) DEFAULT NULL,
  estado_nuevo varchar(80) DEFAULT NULL,
  snapshot_json json DEFAULT NULL,
  fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY servicio_planificado_id (servicio_planificado_id),
  KEY usuario_id (usuario_id),
  CONSTRAINT historial_servicios_plan_fk FOREIGN KEY (servicio_planificado_id) REFERENCES servicios_planificados (id),
  CONSTRAINT historial_servicios_usuario_fk FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET @clientes_ind_servicio_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'clientes'
    AND COLUMN_NAME = 'ind_cliente_servicio'
);

SET @clientes_ind_servicio_sql = IF(
  @clientes_ind_servicio_exists = 0,
  'ALTER TABLE clientes ADD COLUMN ind_cliente_servicio int NOT NULL DEFAULT 0',
  'SELECT 1'
);

PREPARE clientes_ind_servicio_stmt FROM @clientes_ind_servicio_sql;
EXECUTE clientes_ind_servicio_stmt;
DEALLOCATE PREPARE clientes_ind_servicio_stmt;
