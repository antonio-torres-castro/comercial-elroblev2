ALTER TABLE cumplimiento_documento_versiones 
ADD COLUMN proveedor_id INT NULL AFTER fecha_modificacion,
ADD INDEX fk_doc_version_proveedor_id_idx (proveedor_id ASC) VISIBLE;
;
ALTER TABLE cumplimiento_documento_versiones 
ADD CONSTRAINT fk_doc_version_proveedor
  FOREIGN KEY (proveedor_id)
  REFERENCES proveedores (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE cumplimiento_lecturas 
ADD COLUMN proveedor_id INT NULL AFTER fecha_modificacion,
ADD INDEX fk_lectura_proveedor_id_idx (proveedor_id ASC) VISIBLE;
;
ALTER TABLE cumplimiento_lecturas 
ADD CONSTRAINT fk_lectura_proveedor_id
  FOREIGN KEY (proveedor_id)
  REFERENCES proveedores (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
  
ALTER TABLE cumplimiento_pregunta_alternativas 
ADD COLUMN proveedor_id INT NULL AFTER orden_visualizacion,
ADD INDEX fk_alternativa_pregunta_proveedor_id_idx (proveedor_id ASC) VISIBLE;
;
ALTER TABLE cumplimiento_pregunta_alternativas 
ADD CONSTRAINT fk_alternativa_pregunta_proveedor
  FOREIGN KEY (proveedor_id)
  REFERENCES proveedores (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE cumplimiento_preguntas 
ADD COLUMN proveedor_id INT NULL AFTER fecha_creacion,
ADD INDEX fk_preguntas_proveedor_id_idx (proveedor_id ASC) VISIBLE;
;
ALTER TABLE cumplimiento_preguntas 
ADD CONSTRAINT fk_preguntas_proveedor
  FOREIGN KEY (proveedor_id)
  REFERENCES proveedores (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE cumplimiento_respuestas_usuario 
ADD COLUMN proveedor_id INT NULL AFTER fecha_creacion,
ADD INDEX fk_respuestas_usuario_proveedor_id_idx (proveedor_id ASC) VISIBLE;
;
ALTER TABLE cumplimiento_respuestas_usuario 
ADD CONSTRAINT fk_respuestas_usuario_proveedor
  FOREIGN KEY (proveedor_id)
  REFERENCES proveedores (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
