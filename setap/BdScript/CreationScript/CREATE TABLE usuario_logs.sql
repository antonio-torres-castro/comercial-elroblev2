CREATE TABLE comerci3_bdsetap.usuario_logs (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NULL,
  tipo_registro INT NOT NULL DEFAULT 1, -- Estos puede ser 1 para login y 2 para logout
  fecha TIMESTAMP NULL,
  IP VARCHAR(45) NULL, -- en caso que no se logre obtener se registran ceros.
  PRIMARY KEY (id))
COMMENT = 'Registro de login=1 y logout=2';
