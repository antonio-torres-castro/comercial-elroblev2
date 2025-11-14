CREATE TABLE grupo_tipos (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(45) NULL,
  descripcion VARCHAR(250) NULL,
  PRIMARY KEY (id))
COMMENT = 'Grupos tipo, tiene diferentes uso, el primer uso es definir grupos de tipos de usuario que podran realizar tareas segun su grupo a la par o bajo su sus privilegios de usuario tipo';
