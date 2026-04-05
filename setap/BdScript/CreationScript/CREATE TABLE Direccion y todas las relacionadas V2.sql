-- ============================================
-- INICIO TRANSACCIÓN (ATÓMICO)
-- ============================================
START TRANSACTION;

-- ============================================
-- TABLAS BASE
-- ============================================

CREATE TABLE IF NOT EXISTS paises (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  ISO VARCHAR(2) NULL,
  UNIQUE KEY uq_pais_iso (ISO),
  UNIQUE KEY uq_pais_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regiones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  pais_id INT NOT NULL,
  UNIQUE KEY uq_region (nombre, pais_id),
  FOREIGN KEY (pais_id) REFERENCES paises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS provincia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  region_id INT NOT NULL,
  UNIQUE KEY uq_provincia (nombre, region_id),
  FOREIGN KEY (region_id) REFERENCES regiones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comunas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  provincia_id INT NOT NULL,
  UNIQUE KEY uq_comuna (nombre, provincia_id),
  FOREIGN KEY (provincia_id) REFERENCES provincia(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ciudades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  comuna_id INT NOT NULL,
  UNIQUE KEY uq_ciudad (nombre, comuna_id),
  FOREIGN KEY (comuna_id) REFERENCES comunas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DIRECCIONES
-- ============================================

CREATE TABLE IF NOT EXISTS direcciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT NOT NULL,
  calle VARCHAR(255) NOT NULL,
  letra VARCHAR(3) NULL,
  numero INT NULL,
  ind_sin_numero TINYINT NOT NULL DEFAULT 0,
  ind_localidad TINYINT NOT NULL DEFAULT 0,
  localidad VARCHAR(255) NULL,
  referencia VARCHAR(255) NOT NULL,
  lat DECIMAL(10,8),
  lng DECIMAL(11,8),
  comuna_id INT NOT NULL,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
  FOREIGN KEY (comuna_id) REFERENCES comunas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ESPACIOS
-- ============================================

CREATE TABLE IF NOT EXISTS tipos_espacio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  tipo_espacio_padre_id INT NOT NULL DEFAULT -1,
  nivel_jerarquico INT DEFAULT 0,  -- ← corregido: nivel_jerarquico
  descripcion TEXT,
  FOREIGN KEY (tipo_espacio_padre_id) REFERENCES tipos_espacio(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS espacios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  direccion_id INT NOT NULL,
  espacio_padre_id INT NULL,
  nombre VARCHAR(150),
  
  tipos_espacio_id INT NOT NULL,
  
  codigo VARCHAR(50),
  descripcion TEXT,
  nivel INT,
  orden INT,

  FOREIGN KEY (direccion_id) REFERENCES direcciones(id),
  FOREIGN KEY (tipos_espacio_id) REFERENCES tipos_espacio(id),
  FOREIGN KEY (espacio_padre_id) REFERENCES espacios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
