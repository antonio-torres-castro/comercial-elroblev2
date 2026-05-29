Drop table if exists servicio_categorias;

CREATE TABLE IF NOT EXISTS servicio_categorias (
  id int NOT NULL AUTO_INCREMENT,
  parent_id int NULL,
  nombre varchar(100) DEFAULT NULL,
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Clases de servicio';
