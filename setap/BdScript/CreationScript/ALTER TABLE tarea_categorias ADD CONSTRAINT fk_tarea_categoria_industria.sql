ALTER TABLE tarea_categorias ADD CONSTRAINT fk_tarea_categoria_industria
FOREIGN KEY (industria_id) REFERENCES industrias(id);