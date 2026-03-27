CREATE TABLE tipos_fecha (
  id int NOT NULL AUTO_INCREMENT,
  nombre varchar(45) DEFAULT NULL,
  descripcion varchar(250) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci 
COMMENT='Tipos de fechas: laboral, vacacion, licencia, permiso, ausencia, feriado laborable';

INSERT INTO tipos_fecha (nombre, descripcion) Select 'laboral', 'dia laborable';
INSERT INTO tipos_fecha (nombre, descripcion) Select 'vacacion', 'Vacaciones legales';
INSERT INTO tipos_fecha (nombre, descripcion) Select 'licencia', 'Beneficio social legal';
INSERT INTO tipos_fecha (nombre, descripcion) Select 'permiso', 'Beneficio social empresa';
INSERT INTO tipos_fecha (nombre, descripcion) Select 'ausencia', 'dia no laborado por fuerza';
INSERT INTO tipos_fecha (nombre, descripcion) Select 'feriado laborable', 'dia laborable mutuo acuerdo';

Select * from tipos_fecha;