-- DROP TABLE proveedor_procesos;
CREATE TABLE proveedor_procesos (
  id INT NOT NULL AUTO_INCREMENT,
  proveedor_id INT NULL DEFAULT 0,
  nombre VARCHAR(100) NULL,
  descripcion LONGTEXT NULL,
  PRIMARY KEY (id));

ALTER TABLE proveedor_procesos 
ADD INDEX fk_pp_proveedor_id_idx (proveedor_id ASC) VISIBLE;

ALTER TABLE proveedor_procesos 
ADD CONSTRAINT fk_pp_proveedor_id
  FOREIGN KEY (proveedor_id)
  REFERENCES proveedores (id)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;