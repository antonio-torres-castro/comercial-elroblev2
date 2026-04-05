-- Crear la tabla si no existe
CREATE TABLE IF NOT EXISTS tipos_espacio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  tipo_espacio_padre_id INT NOT NULL DEFAULT -1,
  nivel_jerarquico INT DEFAULT 0,
  descripcion TEXT,
  FOREIGN KEY (tipo_espacio_padre_id) REFERENCES tipos_espacio(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deshabilitar revisiones de claves foráneas para inserción masiva
SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar tabla existente (opcional)
TRUNCATE TABLE tipos_espacio;

-- =====================================================
-- NIVEL 0: ESPACIOS PRIMITIVOS (RAÍZ)
-- =====================================================
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('terreno', -1, 0, 'Espacio primitivo base que contiene cualquier tipo de propiedad'),
('fundo', -1, 0, 'Gran extensión de terreno rural para agricultura o ganadería'),
('mega_parcela', -1, 0, 'Gran subdivisión de terreno de grandes dimensiones'),
('parcela', -1, 0, 'Subdivisión de terreno de tamaño mediano'),
('predio', -1, 0, 'Unidad territorial con título de propiedad'),
('sector_geografico', -1, 0, 'División geográfica amplia que puede contener múltiples terrenos'),
('condominio', -1, 0, 'Conjunto de edificios residenciales'),
('conjunto_habitacional', -1, 0, 'Conjunto de viviendas agrupadas'),
('cancha', -1, 0, 'Cancha deportiva'),
('patio', -1, 0, 'Patio o espacio exterior'),
('jardin', -1, 0, 'Jardín o área verde'),
('estacionamiento', -1, 0, 'Estacionamiento o parqueadero'),
('piscina', -1, 0, 'Piscina'),
('quincho', -1, 0, 'Quincho o área de barbacoa'),
('multicancha', -1, 0, 'Multicancha deportiva');

-- =====================================================
-- OBTENER IDs PARA REFERENCIAS FUTURAS
-- =====================================================
SET @terreno_id = (SELECT id FROM tipos_espacio WHERE nombre = 'terreno' LIMIT 1);
SET @fundo_id = (SELECT id FROM tipos_espacio WHERE nombre = 'fundo' LIMIT 1);
SET @mega_parcela_id = (SELECT id FROM tipos_espacio WHERE nombre = 'mega_parcela' LIMIT 1);
SET @parcela_id = (SELECT id FROM tipos_espacio WHERE nombre = 'parcela' LIMIT 1);
SET @predio_id = (SELECT id FROM tipos_espacio WHERE nombre = 'predio' LIMIT 1);
SET @sector_geografico_id = (SELECT id FROM tipos_espacio WHERE nombre = 'sector_geografico' LIMIT 1);
SET @sector_id = (SELECT id FROM tipos_espacio WHERE nombre = 'sector' AND tipo_espacio_padre_id = -1 LIMIT 1);
SET @zona_id = (SELECT id FROM tipos_espacio WHERE nombre = 'zona' AND tipo_espacio_padre_id = -1 LIMIT 1);
SET @area_id = (SELECT id FROM tipos_espacio WHERE nombre = 'area' AND tipo_espacio_padre_id = -1 LIMIT 1);
SET @condominio_id = (SELECT id FROM tipos_espacio WHERE nombre = 'condominio' LIMIT 1);
SET @cancha_id = (SELECT id FROM tipos_espacio WHERE nombre = 'cancha' LIMIT 1);
SET @patio_id = (SELECT id FROM tipos_espacio WHERE nombre = 'patio' LIMIT 1);
SET @jardin_id = (SELECT id FROM tipos_espacio WHERE nombre = 'jardin' LIMIT 1);
SET @estacionamiento_id = (SELECT id FROM tipos_espacio WHERE nombre = 'estacionamiento' LIMIT 1);

-- =====================================================
-- NIVEL 1: ESPACIOS QUE DEPENDEN DE TERRENO Y SUS VARIANTES
-- =====================================================

-- Espacios que pueden ser hijos de terreno
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @terreno_id, 1, 'División de un terreno en áreas específicas'),
('zona', @terreno_id, 1, 'Zona delimitada dentro de un terreno'),
('area', @terreno_id, 1, 'Área específica dentro de un terreno');

-- Actualizar las variables de sector, zona, area con sus nuevos IDs
SET @sector_terreno_id = (SELECT id FROM tipos_espacio WHERE nombre = 'sector' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);
SET @zona_terreno_id = (SELECT id FROM tipos_espacio WHERE nombre = 'zona' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);
SET @area_terreno_id = (SELECT id FROM tipos_espacio WHERE nombre = 'area' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);

-- Espacios que pueden ser hijos de fundo
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @fundo_id, 1, 'Sector productivo del fundo'),
('zona', @fundo_id, 1, 'Zona delimitada del fundo'),
('area', @fundo_id, 1, 'Área específica del fundo'),
('parcela', @fundo_id, 1, 'Parcelas internas del fundo');

-- Espacios que pueden ser hijos de mega_parcela
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @mega_parcela_id, 1, 'Sector dentro de la mega parcela'),
('zona', @mega_parcela_id, 1, 'Zona dentro de la mega parcela'),
('parcela', @mega_parcela_id, 1, 'Subparcelas dentro de la mega parcela');

-- Espacios que pueden ser hijos de parcela
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @parcela_id, 1, 'Sector dentro de la parcela'),
('zona', @parcela_id, 1, 'Zona dentro de la parcela'),
('area', @parcela_id, 1, 'Área específica de la parcela');

-- Espacios que pueden ser hijos de predio
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @predio_id, 1, 'Sector dentro del predio'),
('zona', @predio_id, 1, 'Zona dentro del predio'),
('area', @predio_id, 1, 'Área específica del predio');

-- Espacios que pueden ser hijos de sector_geografico
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('terreno', @sector_geografico_id, 1, 'Terrenos dentro del sector geográfico'),
('fundo', @sector_geografico_id, 1, 'Fundos dentro del sector geográfico'),
('mega_parcela', @sector_geografico_id, 1, 'Mega parcelas dentro del sector geográfico'),
('parcela', @sector_geografico_id, 1, 'Parcelas dentro del sector geográfico');

-- =====================================================
-- NIVEL 2: CONSTRUCCIONES Y EDIFICACIONES
-- =====================================================

-- Obtener IDs actualizados de sector, zona, area según sus padres
SET @sector_fundo_id = (SELECT id FROM tipos_espacio WHERE nombre = 'sector' AND tipo_espacio_padre_id = @fundo_id LIMIT 1);
SET @sector_parcela_id = (SELECT id FROM tipos_espacio WHERE nombre = 'sector' AND tipo_espacio_padre_id = @parcela_id LIMIT 1);
SET @zona_parcela_id = (SELECT id FROM tipos_espacio WHERE nombre = 'zona' AND tipo_espacio_padre_id = @parcela_id LIMIT 1);

-- Construcciones dentro de terreno
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('edificio', @terreno_id, 2, 'Edificación vertical dentro del terreno'),
('torre', @terreno_id, 2, 'Torre o bloque dentro del terreno'),
('galpon', @terreno_id, 2, 'Nave industrial o galpón dentro del terreno'),
('planta_productiva', @terreno_id, 2, 'Planta de producción dentro del terreno'),
('casa', @terreno_id, 2, 'Vivienda unifamiliar'),
('casona', @terreno_id, 2, 'Casa de gran tamaño o hacienda');

-- Construcciones dentro de fundo
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('edificio', @fundo_id, 2, 'Edificación dentro del fundo'),
('torre', @fundo_id, 2, 'Torre dentro del fundo'),
('galpon', @fundo_id, 2, 'Galpón dentro del fundo'),
('planta_productiva', @fundo_id, 2, 'Planta productiva dentro del fundo');

-- Construcciones dentro de parcela
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('edificio', @parcela_id, 2, 'Edificación dentro de la parcela'),
('casa', @parcela_id, 2, 'Vivienda dentro de la parcela'),
('galpon', @parcela_id, 2, 'Galpón dentro de la parcela');

-- Construcciones dentro de sector (que está dentro de terreno)
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('edificio', @sector_terreno_id, 2, 'Edificio dentro del sector'),
('torre', @sector_terreno_id, 2, 'Torre dentro del sector'),
('galpon', @sector_terreno_id, 2, 'Galpón dentro del sector'),
('planta_productiva', @sector_terreno_id, 2, 'Planta productiva dentro del sector'),
('casa', @sector_terreno_id, 2, 'Casa dentro del sector');

-- Construcciones dentro de zona
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('edificio', @zona_terreno_id, 2, 'Edificio dentro de la zona'),
('torre', @zona_terreno_id, 2, 'Torre dentro de la zona'),
('galpon', @zona_terreno_id, 2, 'Galpón dentro de la zona');

-- Construcciones dentro de area
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('edificio', @area_terreno_id, 2, 'Edificio dentro del área'),
('galpon', @area_terreno_id, 2, 'Galpón dentro del área');

-- Construcciones dentro de condominio
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('edificio', @condominio_id, 1, 'Edificio dentro del condominio'),
('torre', @condominio_id, 1, 'Torre dentro del condominio'),
('area_comun', @condominio_id, 1, 'Área común del condominio');

-- =====================================================
-- NIVEL 3: DIVISIONES INTERNAS DE EDIFICACIONES
-- =====================================================

-- Obtener IDs de construcciones
SET @edificio_id = (SELECT id FROM tipos_espacio WHERE nombre = 'edificio' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);
SET @torre_id = (SELECT id FROM tipos_espacio WHERE nombre = 'torre' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);
SET @galpon_id = (SELECT id FROM tipos_espacio WHERE nombre = 'galpon' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);
SET @planta_productiva_id = (SELECT id FROM tipos_espacio WHERE nombre = 'planta_productiva' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);
SET @casa_id = (SELECT id FROM tipos_espacio WHERE nombre = 'casa' AND tipo_espacio_padre_id = @terreno_id LIMIT 1);
SET @edificio_condominio_id = (SELECT id FROM tipos_espacio WHERE nombre = 'edificio' AND tipo_espacio_padre_id = @condominio_id LIMIT 1);

-- Divisiones de edificio
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('piso', @edificio_id, 3, 'Piso o nivel del edificio'),
('sector', @edificio_id, 3, 'Sector dentro del edificio'),
('ala', @edificio_id, 3, 'Ala del edificio'),
('block', @edificio_id, 3, 'Block o módulo del edificio');

-- Divisiones de torre
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('piso', @torre_id, 3, 'Piso de la torre'),
('sector', @torre_id, 3, 'Sector dentro de la torre');

-- Divisiones de galpon
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @galpon_id, 3, 'Sector dentro del galpón'),
('area', @galpon_id, 3, 'Área dentro del galpón'),
('bahia', @galpon_id, 3, 'Bahía o dársena dentro del galpón'),
('nave', @galpon_id, 3, 'Nave industrial dentro del galpón');

-- Divisiones de planta_productiva
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('area', @planta_productiva_id, 3, 'Área productiva'),
('sector', @planta_productiva_id, 3, 'Sector productivo'),
('linea_produccion', @planta_productiva_id, 3, 'Línea de producción'),
('celda_trabajo', @planta_productiva_id, 3, 'Celda de trabajo');

-- Divisiones de casa
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('piso', @casa_id, 3, 'Piso de la casa'),
('sector', @casa_id, 3, 'Sector de la casa');

-- Divisiones de edificio en condominio
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('piso', @edificio_condominio_id, 2, 'Piso del edificio en condominio'),
('sector', @edificio_condominio_id, 2, 'Sector del edificio en condominio');

-- =====================================================
-- NIVEL 4: ESPACIOS INTERIORES (HABITACIONES, OFICINAS, ETC)
-- =====================================================

-- Obtener IDs de pisos
SET @piso_edificio_id = (SELECT id FROM tipos_espacio WHERE nombre = 'piso' AND tipo_espacio_padre_id = @edificio_id LIMIT 1);
SET @piso_torre_id = (SELECT id FROM tipos_espacio WHERE nombre = 'piso' AND tipo_espacio_padre_id = @torre_id LIMIT 1);
SET @piso_casa_id = (SELECT id FROM tipos_espacio WHERE nombre = 'piso' AND tipo_espacio_padre_id = @casa_id LIMIT 1);
SET @sector_edificio_id = (SELECT id FROM tipos_espacio WHERE nombre = 'sector' AND tipo_espacio_padre_id = @edificio_id LIMIT 1);
SET @area_galpon_id = (SELECT id FROM tipos_espacio WHERE nombre = 'area' AND tipo_espacio_padre_id = @galpon_id LIMIT 1);

-- Espacios dentro de piso (de edificio)
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('oficina', @piso_edificio_id, 4, 'Oficina dentro del piso'),
('sala', @piso_edificio_id, 4, 'Sala dentro del piso'),
('salon', @piso_edificio_id, 4, 'Salón dentro del piso'),
('gimnasio', @piso_edificio_id, 4, 'Gimnasio dentro del piso'),
('departamento', @piso_edificio_id, 4, 'Departamento dentro del piso'),
('bodega', @piso_edificio_id, 4, 'Bodega dentro del piso'),
('laboratorio', @piso_edificio_id, 4, 'Laboratorio dentro del piso'),
('vestidor', @piso_edificio_id, 4, 'Vestidor dentro del piso'),
('comedor', @piso_edificio_id, 4, 'Comedor dentro del piso'),
('cocina', @piso_edificio_id, 4, 'Cocina dentro del piso'),
('banio', @piso_edificio_id, 4, 'Baño dentro del piso'),
('terraza', @piso_edificio_id, 4, 'Terraza dentro del piso'),
('azotea', @piso_edificio_id, 4, 'Azotea dentro del piso'),
('pasillo', @piso_edificio_id, 4, 'Pasillo dentro del piso');

-- Espacios dentro de sector
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('oficina', @sector_edificio_id, 4, 'Oficina dentro del sector'),
('sala', @sector_edificio_id, 4, 'Sala dentro del sector'),
('bodega', @sector_edificio_id, 4, 'Bodega dentro del sector');

-- Espacios dentro de area (de galpon)
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('oficina', @area_galpon_id, 4, 'Oficina dentro del área'),
('sala', @area_galpon_id, 4, 'Sala dentro del área'),
('bodega', @area_galpon_id, 4, 'Bodega dentro del área');

-- =====================================================
-- NIVEL 5: SUBESPACIOS DE OFICINAS Y DETALLES FINOS
-- =====================================================

-- Obtener ID de oficina
SET @oficina_id = (SELECT id FROM tipos_espacio WHERE nombre = 'oficina' AND tipo_espacio_padre_id = @piso_edificio_id LIMIT 1);

-- Espacios adicionales para oficinas modernas
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('cubiculos', @oficina_id, 5, 'Cubículos dentro de la oficina'),
('salas_reunion', @oficina_id, 5, 'Salas de reunión'),
('kitchenette', @oficina_id, 5, 'Kitchenette o coffee break'),
('servicio_higienico', @oficina_id, 5, 'Servicio higiénico'),
('archivo', @oficina_id, 5, 'Sala de archivo'),
('servidor', @oficina_id, 5, 'Sala de servidores');

-- =====================================================
-- ESPACIOS EXTERIORES Y SUS SUBESPACIOS
-- =====================================================

-- Subespacios de cancha
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @cancha_id, 1, 'Sector de la cancha'),
('area', @cancha_id, 1, 'Área de la cancha');

-- Subespacios de patio
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @patio_id, 1, 'Sector del patio'),
('jardin', @patio_id, 1, 'Jardín dentro del patio');

-- Subespacios de jardin
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('sector', @jardin_id, 1, 'Sector del jardín'),
('area', @jardin_id, 1, 'Área del jardín');

-- Subespacios de estacionamiento
INSERT INTO tipos_espacio (nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion) VALUES
('nivel_estacionamiento', @estacionamiento_id, 1, 'Nivel del estacionamiento'),
('sector_estacionamiento', @estacionamiento_id, 1, 'Sector del estacionamiento'),
('bahia_estacionamiento', @estacionamiento_id, 2, 'Bahía de estacionamiento individual');

-- Rehabilitar revisiones de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CONSULTAS DE VERIFICACIÓN
-- =====================================================

-- Mostrar todos los tipos de espacio ordenados jerárquicamente
SELECT 
    id,
    nombre,
    CASE 
        WHEN tipo_espacio_padre_id = -1 THEN 'RAÍZ'
        ELSE (SELECT nombre FROM tipos_espacio t2 WHERE t2.id = t1.tipo_espacio_padre_id)
    END as nombre_padre,
    nivel_jerarquico,
    descripcion
FROM tipos_espacio t1
ORDER BY nivel_jerarquico, nombre_padre, nombre;

-- Estadísticas generales
SELECT 
    COUNT(*) as total_tipos_espacio,
    COUNT(DISTINCT nombre) as nombres_unicos,
    SUM(CASE WHEN tipo_espacio_padre_id = -1 THEN 1 ELSE 0 END) as espacios_raiz,
    MAX(nivel_jerarquico) as max_nivel_jerarquico
FROM tipos_espacio;

-- Jerarquía completa mostrada como árbol (CTE Recursiva)
WITH RECURSIVE espacio_tree AS (
    -- Nodos raíz
    SELECT 
        id,
        nombre,
        tipo_espacio_padre_id,
        nivel_jerarquico,
        CAST(nombre AS CHAR(1000)) as ruta,
        0 as profundidad
    FROM tipos_espacio
    WHERE tipo_espacio_padre_id = -1
    
    UNION ALL
    
    -- Nodos hijos
    SELECT 
        te.id,
        te.nombre,
        te.tipo_espacio_padre_id,
        te.nivel_jerarquico,
        CONCAT(et.ruta, ' → ', te.nombre) as ruta,
        et.profundidad + 1
    FROM tipos_espacio te
    INNER JOIN espacio_tree et ON te.tipo_espacio_padre_id = et.id
    WHERE te.tipo_espacio_padre_id != -1
)
SELECT 
    id,
    CONCAT(REPEAT('  ', profundidad), '└─ ', nombre) as arbol,
    ruta,
    nivel_jerarquico,
    profundidad
FROM espacio_tree
ORDER BY ruta;