-- Tabla: menu - Catalogo de Menu de sistema web, es importante que el nombre sea simple
DROP TABLE IF EXISTS usuario_tipo_menus;
DROP TABLE IF EXISTS menu;
CREATE TABLE menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(300) NULL,
    fecha_creacion DATE DEFAULT NULL,
    fecha_modificacion DATE DEFAULT NULL,
    estado_tipo_id INT DEFAULT 1 NOT NULL,
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla: usuario_tipo_menus
CREATE TABLE usuario_tipo_menus (
    menu_id INT NOT NULL,
    usuario_tipo_id INT NOT NULL,
    fecha_creacion DATE DEFAULT NULL,
    fecha_modificacion DATE DEFAULT NULL,
    estado_tipo_id INT DEFAULT 1 NOT NULL,
    PRIMARY KEY (menu_id, usuario_tipo_id),
    FOREIGN KEY (menu_id) REFERENCES menu(id),
    FOREIGN KEY (usuario_tipo_id) REFERENCES usuario_tipos(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla: permisos
DROP TABLE IF EXISTS usuario_tipo_permisos;
DROP TABLE IF EXISTS permiso_tipos;
CREATE TABLE permiso_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(300) NULL
);

-- Tabla: usuario_tipo_permisos
CREATE TABLE usuario_tipo_permisos (
    permiso_id INT NOT NULL,
    usuario_tipo_id INT NOT NULL,
    fecha_creacion DATE DEFAULT NULL,
    fecha_modificacion DATE DEFAULT NULL,
    estado_tipo_id INT DEFAULT 1 NOT NULL,
    PRIMARY KEY (permiso_id, usuario_tipo_id),
    FOREIGN KEY (permiso_id) REFERENCES permiso_tipos(id),
    FOREIGN KEY (usuario_tipo_id) REFERENCES usuario_tipos(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla: notificacion_tipos
DROP TABLE IF EXISTS usuario_notificaciones;
DROP TABLE IF EXISTS notificacion_medios;
DROP TABLE IF EXISTS notificacion_tipos;
CREATE TABLE notificacion_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(300)
);

-- Tabla: notificacion_medios
CREATE TABLE notificacion_medios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(300)
);

-- Tabla: usuario_notificaciones
CREATE TABLE usuario_notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    notificacion_tipo_id INT NOT NULL,
    medio_notificacion_id INT NOT NULL,
	fecha_creacion DATE DEFAULT NULL,
    fecha_modificacion DATE DEFAULT NULL,
    estado_tipo_id INT DEFAULT 1 NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (notificacion_tipo_id) REFERENCES notificacion_tipos(id),
    FOREIGN KEY (medio_notificacion_id) REFERENCES notificacion_medios(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);
