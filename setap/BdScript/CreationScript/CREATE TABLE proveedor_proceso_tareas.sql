-- DROP TABLE proveedor_proceso_tareas;
CREATE TABLE proveedor_proceso_tareas (
  id INT NOT NULL AUTO_INCREMENT,
  proveedor_proceso_id INT NULL DEFAULT NULL,
  tarea_id INT NULL DEFAULT NULL,
  hh decimal DEFAULT 0.5,
  PRIMARY KEY (id),
  INDEX fk_ppt_proceso_id_idx (proveedor_proceso_id ASC) VISIBLE,
  INDEX fk_ppt_tarea_id_idx (tarea_id ASC) VISIBLE,
  CONSTRAINT fk_ppt_proceso_id
    FOREIGN KEY (proveedor_proceso_id)
    REFERENCES proveedor_procesos (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_ppt_tarea_id
    FOREIGN KEY (tarea_id)
    REFERENCES tareas (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);

Select * From proveedor_proceso_tareas;