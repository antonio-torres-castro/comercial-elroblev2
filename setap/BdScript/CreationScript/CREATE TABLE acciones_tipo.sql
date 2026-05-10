CREATE TABLE acciones_tipo (
  id INT NOT NULL,
  nombre VARCHAR(50) NULL,
  PRIMARY KEY (id));
  
INSERT INTO acciones_tipo Select 1, 'login';
INSERT INTO acciones_tipo Select 2, 'logout';
INSERT INTO acciones_tipo Select 3, 'crear';
INSERT INTO acciones_tipo Select 4, 'modificar';
INSERT INTO acciones_tipo Select 5, 'eliminar';