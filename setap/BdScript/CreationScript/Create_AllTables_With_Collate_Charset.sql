CREATE TABLE `estado_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `notificacion_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `permiso_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `notificacion_medios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `tarea_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `usuario_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Fin tablas autonomas

CREATE TABLE `menu_grupo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `orden` int DEFAULT '999',
  `display` varchar(150) DEFAULT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `menu_grupo_ibfk_1` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `personas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rut` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `personas_ibfk_1` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rut` varchar(20) DEFAULT NULL,
  `razon_social` varchar(150) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_inicio_contrato` date DEFAULT NULL,
  `fecha_facturacion` date DEFAULT NULL,
  `fecha_termino_contrato` date DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `tareas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `tareas_ibfk_1` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Fin Tablas solo dependencia tablas autonomas

CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `persona_id` int NOT NULL,
  `usuario_tipo_id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL,
  `clave_hash` varchar(255) NOT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_termino` date DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  KEY `persona_id` (`persona_id`),
  KEY `usuario_tipo_id` (`usuario_tipo_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`usuario_tipo_id`) REFERENCES `usuario_tipos` (`id`),
  CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '1',
  `url` varchar(100) DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `orden` int DEFAULT '999',
  `display` varchar(150) DEFAULT NULL,
  `menu_grupo_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cliente_contrapartes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `persona_id` int NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cliente_id` (`cliente_id`,`persona_id`),
  KEY `persona_id` (`persona_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `cliente_contrapartes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `cliente_contrapartes_ibfk_2` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`),
  CONSTRAINT `cliente_contrapartes_ibfk_3` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `proyectos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `tarea_tipo_id` int NOT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  `contraparte_id` int NOT NULL,
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `tarea_tipo_id` (`tarea_tipo_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  KEY `contraparte_id` (`contraparte_id`),
  CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `proyectos_ibfk_2` FOREIGN KEY (`tarea_tipo_id`) REFERENCES `tarea_tipos` (`id`),
  CONSTRAINT `proyectos_ibfk_3` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`),
  CONSTRAINT `proyectos_ibfk_4` FOREIGN KEY (`contraparte_id`) REFERENCES `cliente_contrapartes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `proyecto_feriados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto_id` int NOT NULL,
  `fecha` date NOT NULL,
  `tipo_feriado` enum('especifico','recurrente') DEFAULT NULL,
  `ind_irrenunciable` int DEFAULT '0',
  `observaciones` varchar(100) DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proyecto_id` (`proyecto_id`,`fecha`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `proyecto_feriados_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`),
  CONSTRAINT `proyecto_feriados_ibfk_2` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Agrego campo fecha_fin, por que es mas facil calcular al crear o modificar la tarea cuando debiera terminar.
drop table if exists `tarea_fotos`;
drop table if exists `historial_tareas`;
drop table if exists `proyecto_tareas`;
CREATE TABLE `proyecto_tareas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto_id` int NOT NULL,
  `tarea_id` int NOT NULL,
  `planificador_id` int NOT NULL,
  `ejecutor_id` int DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `duracion_horas` decimal(4,2) NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `prioridad` int DEFAULT '0',
  `estado_tipo_id` int NOT NULL,
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proyecto_id` (`proyecto_id`,`tarea_id`,`fecha_inicio`),
  KEY `tarea_id` (`tarea_id`),
  KEY `planificador_id` (`planificador_id`),
  KEY `ejecutor_id` (`ejecutor_id`),
  KEY `supervisor_id` (`supervisor_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `proyecto_tareas_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`),
  CONSTRAINT `proyecto_tareas_ibfk_2` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`),
  CONSTRAINT `proyecto_tareas_ibfk_3` FOREIGN KEY (`planificador_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `proyecto_tareas_ibfk_4` FOREIGN KEY (`ejecutor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `proyecto_tareas_ibfk_5` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `proyecto_tareas_ibfk_6` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `historial_tareas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto_tarea_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `supervisor_id` int DEFAULT NULL,
  `contraparte_id` int DEFAULT NULL,
  `fecha_evento` datetime(3) NOT NULL,
  `comentario` text,
  `estado_tipo_anterior` int DEFAULT NULL,
  `estado_tipo_nuevo` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_tarea_id` (`proyecto_tarea_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `supervisor_id` (`supervisor_id`),
  KEY `contraparte_id` (`contraparte_id`),
  KEY `estado_tipo_anterior` (`estado_tipo_anterior`),
  KEY `estado_tipo_nuevo` (`estado_tipo_nuevo`),
  CONSTRAINT `historial_tareas_ibfk_1` FOREIGN KEY (`proyecto_tarea_id`) REFERENCES `proyecto_tareas` (`id`),
  CONSTRAINT `historial_tareas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `historial_tareas_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `historial_tareas_ibfk_4` FOREIGN KEY (`contraparte_id`) REFERENCES `cliente_contrapartes` (`id`),
  CONSTRAINT `historial_tareas_ibfk_5` FOREIGN KEY (`estado_tipo_anterior`) REFERENCES `estado_tipos` (`id`),
  CONSTRAINT `historial_tareas_ibfk_6` FOREIGN KEY (`estado_tipo_nuevo`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `tarea_fotos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `historial_tarea_id` int NOT NULL,
  `url_foto` varchar(255) NOT NULL,
  `fecha_Creado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_tipo_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `historial_tarea_id` (`historial_tarea_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `tarea_fotos_ibfk_1` FOREIGN KEY (`historial_tarea_id`) REFERENCES `historial_tareas` (`id`),
  CONSTRAINT `tarea_fotos_ibfk_2` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `usuario_notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `notificacion_tipo_id` int NOT NULL,
  `medio_notificacion_id` int NOT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `notificacion_tipo_id` (`notificacion_tipo_id`),
  KEY `medio_notificacion_id` (`medio_notificacion_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `usuario_notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `usuario_notificaciones_ibfk_2` FOREIGN KEY (`notificacion_tipo_id`) REFERENCES `notificacion_tipos` (`id`),
  CONSTRAINT `usuario_notificaciones_ibfk_3` FOREIGN KEY (`medio_notificacion_id`) REFERENCES `notificacion_medios` (`id`),
  CONSTRAINT `usuario_notificaciones_ibfk_4` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `usuario_tipo_menus` (
  `menu_id` int NOT NULL,
  `usuario_tipo_id` int NOT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`menu_id`,`usuario_tipo_id`),
  KEY `usuario_tipo_id` (`usuario_tipo_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `usuario_tipo_menus_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`),
  CONSTRAINT `usuario_tipo_menus_ibfk_2` FOREIGN KEY (`usuario_tipo_id`) REFERENCES `usuario_tipos` (`id`),
  CONSTRAINT `usuario_tipo_menus_ibfk_3` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `usuario_tipo_permisos` (
  `permiso_id` int NOT NULL,
  `usuario_tipo_id` int NOT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `estado_tipo_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`permiso_id`,`usuario_tipo_id`),
  KEY `usuario_tipo_id` (`usuario_tipo_id`),
  KEY `estado_tipo_id` (`estado_tipo_id`),
  CONSTRAINT `usuario_tipo_permisos_ibfk_1` FOREIGN KEY (`permiso_id`) REFERENCES `permiso_tipos` (`id`),
  CONSTRAINT `usuario_tipo_permisos_ibfk_2` FOREIGN KEY (`usuario_tipo_id`) REFERENCES `usuario_tipos` (`id`),
  CONSTRAINT `usuario_tipo_permisos_ibfk_3` FOREIGN KEY (`estado_tipo_id`) REFERENCES `estado_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
