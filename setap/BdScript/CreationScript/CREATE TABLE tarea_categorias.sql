DROP TABLE tarea_categorias;

CREATE TABLE tarea_categorias (
  id int NOT NULL AUTO_INCREMENT,
  nombre varchar(100) DEFAULT NULL,
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Clases de tarea: Aseo, Construcción Menor, Construcción, Electrico, Tecnologica, Instalación, Administración, Gestión, Producción';

Insert Into tarea_categorias (nombre, fecha_creacion, fecha_actualizacion)
Select 'Aseo Baños Menores', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Aseo Baños Mayores', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Aseo Oficinas', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Aseo Salas Menores', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Aseo Salas Mayores', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Aseo Espacios Comunes', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Mantención', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Construcción Menor', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Construcción Mayor', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Instalaciones', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Electricidad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Tecnología', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Producción', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Desarrollo', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Evaluación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Consultoría', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Administración', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP union
Select 'Gestión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP;

 ALTER TABLE comerci3_bdsetap.tareas 
 ADD COLUMN tarea_categoria_id INT NULL AFTER `descripcion`;