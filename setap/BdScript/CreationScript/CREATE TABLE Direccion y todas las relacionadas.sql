CREATE TABLE paises (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  ISO VARCHAR(2) NULL -- ISO 3166-1 alfa-2
);

CREATE TABLE regiones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  pais_id INT NOT NULL
);

CREATE TABLE provincia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  region_id INT NOT NULL
);

CREATE TABLE comunas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  provincia_id INT NOT NULL
);

CREATE TABLE direcciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  calle VARCHAR(255) NOT NULL,
  letra VARCHAR(3) NULL,
  numero INT NULL,
  ind_sin_numero INT NOT NULL DEFAULT 0, -- 0 tiene numero, 1 no tiene numero
  ind_localidad INT NOT NULL DEFAULT 0, -- 0 no es localidad, 1 es una localidad
  localidad VARCHAR(255) NULL,
  referencia VARCHAR(255) NOT NULL,
  lat DECIMAL(10,8),
  lng DECIMAL(11,8),
  comuna_id INT NOT NULL
);

CREATE TABLE espacios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  direccion_id INT NOT NULL,
  espacio_padre_id INT NULL,

  nombre VARCHAR(150),
  tipo ENUM(
    'edificio',
    'torre',
    'piso',
    'sector',
    'oficina',
    'departamento',
    'bodega',
    'planta',
    'area',
    'galpon',
    'zona'
  ),

  codigo VARCHAR(50), -- ej: OF-403
  descripcion TEXT,

  nivel INT, -- profundidad jerárquica
  orden INT, -- útil para recorridos internos

  FOREIGN KEY (direccion_id) REFERENCES direcciones(id),
  FOREIGN KEY (espacio_padre_id) REFERENCES espacios(id)
);