-- 190.151.120.170
-- 192.141.168.45
use comerci3_bdsetap;

-- Tabla de tipos de usuario
CREATE TABLE usuario_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(500) NOT NULL
);

-- Tabla de estados generales
CREATE TABLE estado_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(500) NOT NULL
);

-- Tabla de personas
CREATE TABLE personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(255),
    
    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 1 = Activo, 2 = Inactivo, 3 = Eliminado */
    
    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL,
    usuario_tipo_id INT NOT NULL,
    cliente_id INT NULL,
    
    email VARCHAR(150) NOT NULL,
    nombre_usuario VARCHAR(100) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    
    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 1 = Activo, 2 = Inactivo, 3 = Eliminado */

    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATE NULL,
    fecha_termino DATE NULL,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (persona_id) REFERENCES personas(id),
    FOREIGN KEY (usuario_tipo_id) REFERENCES usuario_tipos(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    rut VARCHAR(20) NULL,
    razon_social VARCHAR(150) NOT NULL,
    direccion VARCHAR(255) NULL,
    email VARCHAR(150) NULL,
    telefono VARCHAR(20),
    
    fecha_inicio_contrato DATE NULL,
    fecha_facturacion DATE NULL,
    fecha_termino_contrato DATE NULL,

    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 1 = Activo, 2 = Inactivo, 3 = Eliminado */
    
    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla de contrapartes del cliente
CREATE TABLE cliente_contrapartes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    cliente_id INT NOT NULL,
    persona_id INT NOT NULL,
    /*Relacionado al trabajo-cliente*/
    telefono VARCHAR(20),
    email VARCHAR(150),
    cargo VARCHAR(100),
    
    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 1 = Activo, 2 = Inactivo, 3 = Eliminado */
    
    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE(cliente_id, persona_id),
    
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (persona_id) REFERENCES personas(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla de tipos de tarea
CREATE TABLE tarea_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Tabla de proyectos
CREATE TABLE proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    cliente_id INT NOT NULL,

    direccion VARCHAR(255),
    
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    
    tarea_tipo_id INT NOT NULL,    
    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 1 = Activo, 2 = Inactivo, 3 = Eliminado */
    
    contraparte_id INT NOT NULL,
    
    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cliente_id)     REFERENCES clientes(id),
    FOREIGN KEY (tarea_tipo_id)  REFERENCES tarea_tipos(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id),
    FOREIGN KEY (contraparte_id) REFERENCES cliente_contrapartes(id)
);

-- Tabla de feriados
CREATE TABLE proyecto_feriados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    fecha date NOT NULL,
    
    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 3 = Eliminado */
    
    UNIQUE(proyecto_id, fecha),
    
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla de tareas (catálogo general de tareas)
CREATE TABLE tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    nombre VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT NOT NULL,
    
    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 3 = Eliminado */
    
    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla de tareas asociadas a proyectos
CREATE TABLE proyecto_tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    tarea_id INT NOT NULL,
    
    planificador_id INT NOT NULL,
    ejecutor_id INT NULL,
    supervisor_id INT NULL,
    
    fecha_inicio DATETIME NOT NULL,
    duracion_horas DECIMAL(4,2) NOT NULL,
    prioridad INT DEFAULT 0,
    
    estado_tipo_id INT NOT NULL,
    
    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE(proyecto_id, tarea_id, fecha_inicio),
    
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (tarea_id) REFERENCES tareas(id),
    FOREIGN KEY (planificador_id) REFERENCES usuarios(id),
    FOREIGN KEY (ejecutor_id) REFERENCES usuarios(id),
    FOREIGN KEY (supervisor_id) REFERENCES usuarios(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

-- Tabla de historial de tareas
CREATE TABLE historial_tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    proyecto_tarea_id INT NOT NULL,
    usuario_id INT NOT NULL,
    supervisor_id INT NULL,
    contraparte_id INT NULL,
    
    fecha_evento DATETIME(3) NOT NULL,
    
    comentario TEXT,
    
    estado_tipo_anterior INT,
    estado_tipo_nuevo INT,
    
    FOREIGN KEY (proyecto_tarea_id) REFERENCES proyecto_tareas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (supervisor_id) REFERENCES usuarios(id),
    FOREIGN KEY (contraparte_id) REFERENCES cliente_contrapartes(id),
    FOREIGN KEY (estado_tipo_anterior) REFERENCES estado_tipos(id),
    FOREIGN KEY (estado_tipo_nuevo) REFERENCES estado_tipos(id)
);

-- Tabla de fotos de tareas
CREATE TABLE tarea_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    historial_tarea_id INT NOT NULL,
    url_foto VARCHAR(255) NOT NULL,
    fecha_Creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    estado_tipo_id INT DEFAULT 0 NOT NULL, /* 0 = Creado, 3 = Eliminado */
    
    FOREIGN KEY (historial_tarea_id) REFERENCES historial_tareas(id),
    FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
);

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
    url VARCHAR(100) NULL,
    icono VARCHAR(50) NULL,
    orden INT DEFAULT 999,
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
