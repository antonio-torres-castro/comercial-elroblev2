ALTER TABLE proyecto_tareas ADD COLUMN espacio_id INT NULL DEFAULT NULL AFTER prioridad;

ALTER TABLE proyecto_tareas 
ADD INDEX proyecto_tareas_ibfk_7_idx (espacio_id ASC) VISIBLE;
;
ALTER TABLE proyecto_tareas 
ADD CONSTRAINT proyecto_tareas_ibfk_7
  FOREIGN KEY (espacio_id)
  REFERENCES espacios (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;