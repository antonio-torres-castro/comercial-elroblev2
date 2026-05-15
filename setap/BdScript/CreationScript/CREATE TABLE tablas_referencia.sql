CREATE TABLE tablas_referencia (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(300) NULL,
    PRIMARY KEY(id),
    UNIQUE(nombre)
);