CREATE TABLE proveedores (
  id int NOT NULL AUTO_INCREMENT,
  rut varchar(20) DEFAULT NULL,
  razon_social varchar(150) NOT NULL,
  direccion varchar(255) DEFAULT NULL,
  email varchar(150) DEFAULT NULL,
  telefono varchar(20) DEFAULT NULL,
  fecha_inicio_contrato date DEFAULT NULL,
  fecha_facturacion date DEFAULT NULL,
  fecha_termino_contrato date DEFAULT NULL,
  estado_tipo_id int NOT NULL DEFAULT '0',
  fecha_Creado timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_modificacion timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY estado_tipo_id (estado_tipo_id),
  CONSTRAINT proveedores_ibfk_1 FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
