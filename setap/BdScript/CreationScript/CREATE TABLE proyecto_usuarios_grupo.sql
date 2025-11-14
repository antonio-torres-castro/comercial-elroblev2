DROP TABLE IF EXISTS proyecto_usuarios_grupo;

CREATE TABLE proyecto_usuarios_grupo (
  id INT NOT NULL AUTO_INCREMENT,
  proyecto_id INT NOT NULL,
  grupo_id INT NOT NULL,
  usuario_id INT NOT NULL,
  estado_tipo_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_pug_proyecto_id_idx (proyecto_id ASC) VISIBLE,
  INDEX fk_pug_grupo_id_idx (grupo_id ASC) VISIBLE,
  INDEX fk_pug_usuario_id_idx (usuario_id ASC) VISIBLE,
  INDEX fk_pug_estado_tipo_id_idx (estado_tipo_id ASC) VISIBLE,
  CONSTRAINT fk_pug_proyecto_id
    FOREIGN KEY (proyecto_id)
    REFERENCES proyectos (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_pug_grupo_id
    FOREIGN KEY (grupo_id)
    REFERENCES grupo_tipos (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_pug_usuario_id
    FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_pug_estado_tipo_id
    FOREIGN KEY (estado_tipo_id)
    REFERENCES estado_tipos (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
COMMENT = 'Los usuarios que pueden ver el proyecto';
