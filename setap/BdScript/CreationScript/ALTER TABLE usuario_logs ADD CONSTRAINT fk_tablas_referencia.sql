ALTER TABLE usuario_logs 
ADD INDEX fk_tablas_referencia_idx (tabla_referencia_id ASC) VISIBLE;
;
ALTER TABLE usuario_logs ADD CONSTRAINT fk_tablas_referencia
  FOREIGN KEY (tabla_referencia_id)
  REFERENCES comerci3_bdsetap.tablas_referencia (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
