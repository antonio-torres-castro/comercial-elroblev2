-- Esta tabla no tiene url, por que es solo un grupo de menus desplegables para navigation.php
DROP TABLE menu_grupo;

CREATE TABLE menu_grupo (
  id int NOT NULL AUTO_INCREMENT,
  nombre varchar(150) NOT NULL, -- nombre interno del sistema
  descripcion varchar(300) DEFAULT NULL, -- descripcion del grupo de menus
  icono varchar(50) DEFAULT NULL, -- icono para el grupo de menus
  orden int DEFAULT '999', -- Orden transversal de presentacion para el grupo de menu
  display varchar(150) DEFAULT NULL, -- nombre del grupo de menu a exponer en la interfaz de usuario
  fecha_creacion date DEFAULT NULL, -- fecha en que se crea el registro
  fecha_modificacion date DEFAULT NULL, -- fecha en que se modifica el registro
  estado_tipo_id int NOT NULL DEFAULT '1', -- estado del registro del pool de estados solo aplican en esta tabla: creado, inactivo, activo, eliminado
  PRIMARY KEY (id),
  KEY estado_tipo_id (estado_tipo_id),
  CONSTRAINT menu_grupo_ibfk_1 FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

Insert into comerci3_bdsetap.menu_grupo (id, nombre, descripcion, icono, orden, display, fecha_creacion, fecha_modificacion, estado_tipo_id)
VALUES (1, 'module_menus', 'Modulo para gestionar menus', 'list-ul', 1, 'Menus', now(), null, 2),
(2, 'module_persons', 'Modulo para gestionar personas', 'people-fill', 2, 'Personas', now(), null, 2),
(3, 'module_clients', 'Modulo para gestionar clientes', 'building', 3, 'Clientes', now(), null, 2),
(4, 'module_projects', 'Modulo para gestionar proyectos', 'briefcase', 4, 'Proyectos', now(), null, 2);

ALTER TABLE menu ADD menu_grupo_id int not null;

UPDATE menu set menu_grupo_id = 1 WHERE id in (1, 2);
UPDATE menu set menu_grupo_id = 2 WHERE id in (3, 4, 5, 6, 7, 8);
UPDATE menu set menu_grupo_id = 3 WHERE id in (9, 10, 11, 12);
UPDATE menu set menu_grupo_id = 4 WHERE id in (13, 14, 15, 16);