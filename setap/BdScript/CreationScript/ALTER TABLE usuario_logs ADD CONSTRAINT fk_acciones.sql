ALTER TABLE usuario_logs 
ADD INDEX fk_acciones_idx (accion_id ASC) VISIBLE;
;
ALTER TABLE usuario_logs ADD CONSTRAINT fk_acciones
  FOREIGN KEY (accion_id)
  REFERENCES acciones (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
