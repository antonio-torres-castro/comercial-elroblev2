ALTER TABLE acciones 
ADD INDEX fk_acciones_tipo_idx (acciones_tipo_id ASC) VISIBLE;
;
ALTER TABLE acciones ADD CONSTRAINT fk_acciones_tipo
  FOREIGN KEY (acciones_tipo_id)
  REFERENCES acciones_tipo (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
