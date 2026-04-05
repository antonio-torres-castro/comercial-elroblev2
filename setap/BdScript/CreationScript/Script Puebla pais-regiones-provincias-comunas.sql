-- =====================================================
-- SCRIPT IDEMPOTENTE PARA REGIONES, PROVINCIAS Y COMUNAS DE CHILE
-- Basado en DPA2018.xlsx
-- =====================================================

-- Deshabilitar revisiones de claves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- Insertar país Chile si no existe
SET @chile_id = ifnull((SELECT id FROM paises WHERE nombre = 'Chile'), 0);

INSERT INTO paises (nombre, ISO) 
SELECT 'Chile', 'CL'
WHERE @chile_id = 0;

-- Obtener ID de Chile
SET @chile_id = (SELECT id FROM paises WHERE nombre = 'Chile');

-- =====================================================
-- LIMPIAR TABLAS EXISTENTES (orden inverso por FK)
-- =====================================================
TRUNCATE TABLE comunas;
TRUNCATE TABLE provincia;
TRUNCATE TABLE regiones;

-- =====================================================
-- INSERTAR REGIONES (NIVEL 1)
-- Basado en las columnas: Nombre Región desde 2018, Código Región desde 2018
-- =====================================================
INSERT INTO regiones (nombre, pais_id) VALUES
('De Arica y Parinacota', @chile_id),
('De Tarapacá', @chile_id),
('De Antofagasta', @chile_id),
('De Atacama', @chile_id),
('De Coquimbo', @chile_id),
('De Valparaíso', @chile_id),
('Del Libertador B. O''Higgins', @chile_id),
('Del Maule', @chile_id),
('Del Bíobío', @chile_id),
('De Ñuble', @chile_id),
('De La Araucanía', @chile_id),
('De Los Ríos', @chile_id),
('De Los Lagos', @chile_id),
('De Aisén del Gral. C. Ibáñez del Campo', @chile_id),
('De Magallanes y de La Antártica Chilena', @chile_id),
('Metropolitana de Santiago', @chile_id);

-- =====================================================
-- OBTENER IDs DE REGIONES
-- =====================================================
SET @region_arica = (SELECT id FROM regiones WHERE nombre = 'De Arica y Parinacota');
SET @region_tarapaca = (SELECT id FROM regiones WHERE nombre = 'De Tarapacá');
SET @region_antofagasta = (SELECT id FROM regiones WHERE nombre = 'De Antofagasta');
SET @region_atacama = (SELECT id FROM regiones WHERE nombre = 'De Atacama');
SET @region_coquimbo = (SELECT id FROM regiones WHERE nombre = 'De Coquimbo');
SET @region_valparaiso = (SELECT id FROM regiones WHERE nombre = 'De Valparaíso');
SET @region_ohiggins = (SELECT id FROM regiones WHERE nombre = 'Del Libertador B. O''Higgins');
SET @region_maule = (SELECT id FROM regiones WHERE nombre = 'Del Maule');
SET @region_biobio = (SELECT id FROM regiones WHERE nombre = 'Del Bíobío');
SET @region_nuble = (SELECT id FROM regiones WHERE nombre = 'De Ñuble');
SET @region_araucania = (SELECT id FROM regiones WHERE nombre = 'De La Araucanía');
SET @region_rios = (SELECT id FROM regiones WHERE nombre = 'De Los Ríos');
SET @region_lagos = (SELECT id FROM regiones WHERE nombre = 'De Los Lagos');
SET @region_aisen = (SELECT id FROM regiones WHERE nombre = 'De Aisén del Gral. C. Ibáñez del Campo');
SET @region_magallanes = (SELECT id FROM regiones WHERE nombre = 'De Magallanes y de La Antártica Chilena');
SET @region_metropolitana = (SELECT id FROM regiones WHERE nombre = 'Metropolitana de Santiago');

-- =====================================================
-- INSERTAR PROVINCIAS (NIVEL 2)
-- Basado en las columnas: Provincia desde 2018, Código Provincia desde 2018
-- =====================================================
INSERT INTO provincia (nombre, region_id) VALUES
-- Región de Arica y Parinacota
('Arica', @region_arica),
('Parinacota', @region_arica),

-- Región de Tarapacá
('Iquique', @region_tarapaca),
('Tamarugal', @region_tarapaca),

-- Región de Antofagasta
('Antofagasta', @region_antofagasta),
('El Loa', @region_antofagasta),
('Tocopilla', @region_antofagasta),

-- Región de Atacama
('Copiapó', @region_atacama),
('Chañaral', @region_atacama),
('Huasco', @region_atacama),

-- Región de Coquimbo
('Elqui', @region_coquimbo),
('Choapa', @region_coquimbo),
('Limari', @region_coquimbo),

-- Región de Valparaíso
('Valparaíso', @region_valparaiso),
('Isla de Pascua', @region_valparaiso),
('Los Andes', @region_valparaiso),
('Petorca', @region_valparaiso),
('Quillota', @region_valparaiso),
('San Antonio', @region_valparaiso),
('San Felipe', @region_valparaiso),
('Marga Marga', @region_valparaiso),

-- Región de O'Higgins
('Cachapoal', @region_ohiggins),
('Cardenal Caro', @region_ohiggins),
('Colchagua', @region_ohiggins),

-- Región del Maule
('Talca', @region_maule),
('Cauquenes', @region_maule),
('Curico', @region_maule),
('Linares', @region_maule),

-- Región del Bíobío
('Concepción', @region_biobio),
('Arauco', @region_biobio),
('Bío- Bío', @region_biobio),

-- Región de Ñuble
('Diguillín', @region_nuble),
('Itata', @region_nuble),
('Punilla', @region_nuble),

-- Región de La Araucanía
('Cautín', @region_araucania),
('Malleco', @region_araucania),

-- Región de Los Ríos
('Valdivia', @region_rios),
('Ranco', @region_rios),

-- Región de Los Lagos
('Llanquihue', @region_lagos),
('Chiloe', @region_lagos),
('Osorno', @region_lagos),
('Palena', @region_lagos),

-- Región de Aisén
('Coihaique', @region_aisen),
('Aisén', @region_aisen),
('Capitan Prat', @region_aisen),
('General Carrera', @region_aisen),

-- Región de Magallanes
('Magallanes', @region_magallanes),
('Antártica Chilena', @region_magallanes),
('Tierra del Fuego', @region_magallanes),
('Ultima Esperanza', @region_magallanes),

-- Región Metropolitana
('Santiago', @region_metropolitana),
('Cordillera', @region_metropolitana),
('Chacabuco', @region_metropolitana),
('Maipo', @region_metropolitana),
('Melipilla', @region_metropolitana),
('Talagante', @region_metropolitana);

-- =====================================================
-- OBTENER IDs DE PROVINCIAS
-- =====================================================
SET @prov_arica = (SELECT id FROM provincia WHERE nombre = 'Arica' AND region_id = @region_arica);
SET @prov_parinacota = (SELECT id FROM provincia WHERE nombre = 'Parinacota' AND region_id = @region_arica);
SET @prov_iquique = (SELECT id FROM provincia WHERE nombre = 'Iquique' AND region_id = @region_tarapaca);
SET @prov_tamarugal = (SELECT id FROM provincia WHERE nombre = 'Tamarugal' AND region_id = @region_tarapaca);
SET @prov_antofagasta = (SELECT id FROM provincia WHERE nombre = 'Antofagasta' AND region_id = @region_antofagasta);
SET @prov_el_loa = (SELECT id FROM provincia WHERE nombre = 'El Loa' AND region_id = @region_antofagasta);
SET @prov_tocopilla = (SELECT id FROM provincia WHERE nombre = 'Tocopilla' AND region_id = @region_antofagasta);
SET @prov_copiapo = (SELECT id FROM provincia WHERE nombre = 'Copiapó' AND region_id = @region_atacama);
SET @prov_chanaral = (SELECT id FROM provincia WHERE nombre = 'Chañaral' AND region_id = @region_atacama);
SET @prov_huasco = (SELECT id FROM provincia WHERE nombre = 'Huasco' AND region_id = @region_atacama);
SET @prov_elqui = (SELECT id FROM provincia WHERE nombre = 'Elqui' AND region_id = @region_coquimbo);
SET @prov_choapa = (SELECT id FROM provincia WHERE nombre = 'Choapa' AND region_id = @region_coquimbo);
SET @prov_limari = (SELECT id FROM provincia WHERE nombre = 'Limari' AND region_id = @region_coquimbo);
SET @prov_valparaiso = (SELECT id FROM provincia WHERE nombre = 'Valparaíso' AND region_id = @region_valparaiso);
SET @prov_isla_pascua = (SELECT id FROM provincia WHERE nombre = 'Isla de Pascua' AND region_id = @region_valparaiso);
SET @prov_los_andes = (SELECT id FROM provincia WHERE nombre = 'Los Andes' AND region_id = @region_valparaiso);
SET @prov_petorca = (SELECT id FROM provincia WHERE nombre = 'Petorca' AND region_id = @region_valparaiso);
SET @prov_quillota = (SELECT id FROM provincia WHERE nombre = 'Quillota' AND region_id = @region_valparaiso);
SET @prov_san_antonio = (SELECT id FROM provincia WHERE nombre = 'San Antonio' AND region_id = @region_valparaiso);
SET @prov_san_felipe = (SELECT id FROM provincia WHERE nombre = 'San Felipe' AND region_id = @region_valparaiso);
SET @prov_marga_marga = (SELECT id FROM provincia WHERE nombre = 'Marga Marga' AND region_id = @region_valparaiso);
SET @prov_cachapoal = (SELECT id FROM provincia WHERE nombre = 'Cachapoal' AND region_id = @region_ohiggins);
SET @prov_cardenal_caro = (SELECT id FROM provincia WHERE nombre = 'Cardenal Caro' AND region_id = @region_ohiggins);
SET @prov_colchagua = (SELECT id FROM provincia WHERE nombre = 'Colchagua' AND region_id = @region_ohiggins);
SET @prov_talca = (SELECT id FROM provincia WHERE nombre = 'Talca' AND region_id = @region_maule);
SET @prov_cauquenes = (SELECT id FROM provincia WHERE nombre = 'Cauquenes' AND region_id = @region_maule);
SET @prov_curico = (SELECT id FROM provincia WHERE nombre = 'Curico' AND region_id = @region_maule);
SET @prov_linares = (SELECT id FROM provincia WHERE nombre = 'Linares' AND region_id = @region_maule);
SET @prov_concepcion = (SELECT id FROM provincia WHERE nombre = 'Concepción' AND region_id = @region_biobio);
SET @prov_arauco = (SELECT id FROM provincia WHERE nombre = 'Arauco' AND region_id = @region_biobio);
SET @prov_biobio = (SELECT id FROM provincia WHERE nombre = 'Bío- Bío' AND region_id = @region_biobio);
SET @prov_diguillin = (SELECT id FROM provincia WHERE nombre = 'Diguillín' AND region_id = @region_nuble);
SET @prov_itata = (SELECT id FROM provincia WHERE nombre = 'Itata' AND region_id = @region_nuble);
SET @prov_punilla = (SELECT id FROM provincia WHERE nombre = 'Punilla' AND region_id = @region_nuble);
SET @prov_cautin = (SELECT id FROM provincia WHERE nombre = 'Cautín' AND region_id = @region_araucania);
SET @prov_malleco = (SELECT id FROM provincia WHERE nombre = 'Malleco' AND region_id = @region_araucania);
SET @prov_valdivia = (SELECT id FROM provincia WHERE nombre = 'Valdivia' AND region_id = @region_rios);
SET @prov_ranco = (SELECT id FROM provincia WHERE nombre = 'Ranco' AND region_id = @region_rios);
SET @prov_llanquihue = (SELECT id FROM provincia WHERE nombre = 'Llanquihue' AND region_id = @region_lagos);
SET @prov_chiloe = (SELECT id FROM provincia WHERE nombre = 'Chiloe' AND region_id = @region_lagos);
SET @prov_osorno = (SELECT id FROM provincia WHERE nombre = 'Osorno' AND region_id = @region_lagos);
SET @prov_palena = (SELECT id FROM provincia WHERE nombre = 'Palena' AND region_id = @region_lagos);
SET @prov_coihaique = (SELECT id FROM provincia WHERE nombre = 'Coihaique' AND region_id = @region_aisen);
SET @prov_aisen = (SELECT id FROM provincia WHERE nombre = 'Aisén' AND region_id = @region_aisen);
SET @prov_capitan_prat = (SELECT id FROM provincia WHERE nombre = 'Capitan Prat' AND region_id = @region_aisen);
SET @prov_general_carrera = (SELECT id FROM provincia WHERE nombre = 'General Carrera' AND region_id = @region_aisen);
SET @prov_magallanes = (SELECT id FROM provincia WHERE nombre = 'Magallanes' AND region_id = @region_magallanes);
SET @prov_antarctica = (SELECT id FROM provincia WHERE nombre = 'Antártica Chilena' AND region_id = @region_magallanes);
SET @prov_tierra_fuego = (SELECT id FROM provincia WHERE nombre = 'Tierra del Fuego' AND region_id = @region_magallanes);
SET @prov_ultima_esperanza = (SELECT id FROM provincia WHERE nombre = 'Ultima Esperanza' AND region_id = @region_magallanes);
SET @prov_santiago = (SELECT id FROM provincia WHERE nombre = 'Santiago' AND region_id = @region_metropolitana);
SET @prov_cordillera = (SELECT id FROM provincia WHERE nombre = 'Cordillera' AND region_id = @region_metropolitana);
SET @prov_chacabuco = (SELECT id FROM provincia WHERE nombre = 'Chacabuco' AND region_id = @region_metropolitana);
SET @prov_maipo = (SELECT id FROM provincia WHERE nombre = 'Maipo' AND region_id = @region_metropolitana);
SET @prov_melipilla = (SELECT id FROM provincia WHERE nombre = 'Melipilla' AND region_id = @region_metropolitana);
SET @prov_talagante = (SELECT id FROM provincia WHERE nombre = 'Talagante' AND region_id = @region_metropolitana);

-- =====================================================
-- INSERTAR COMUNAS (NIVEL 3)
-- Basado en el archivo DPA2018.xlsx
-- =====================================================
INSERT INTO comunas (nombre, provincia_id) VALUES
-- Región de Arica y Parinacota
('Arica', @prov_arica),
('Camarones', @prov_arica),
('Putre', @prov_parinacota),
('General Lagos', @prov_parinacota),

-- Región de Tarapacá
('Iquique', @prov_iquique),
('Alto Hospicio', @prov_iquique),
('Camiña', @prov_tamarugal),
('Colchane', @prov_tamarugal),
('Huara', @prov_tamarugal),
('Pica', @prov_tamarugal),
('Pozo Almonte', @prov_tamarugal),

-- Región de Antofagasta
('Antofagasta', @prov_antofagasta),
('Mejillones', @prov_antofagasta),
('Sierra Gorda', @prov_antofagasta),
('Taltal', @prov_antofagasta),
('Calama', @prov_el_loa),
('Ollagüe', @prov_el_loa),
('San Pedro de Atacama', @prov_el_loa),
('Tocopilla', @prov_tocopilla),
('María Elena', @prov_tocopilla),

-- Región de Atacama
('Copiapó', @prov_copiapo),
('Caldera', @prov_copiapo),
('Tierra Amarilla', @prov_copiapo),
('Chañaral', @prov_chanaral),
('Diego de Almagro', @prov_chanaral),
('Vallenar', @prov_huasco),
('Alto del Carmen', @prov_huasco),
('Freirina', @prov_huasco),
('Huasco', @prov_huasco),

-- Región de Coquimbo
('La Serena', @prov_elqui),
('Coquimbo', @prov_elqui),
('Andacollo', @prov_elqui),
('La Higuera', @prov_elqui),
('Paiguano', @prov_elqui),
('Vicuña', @prov_elqui),
('Illapel', @prov_choapa),
('Canela', @prov_choapa),
('Los Vilos', @prov_choapa),
('Salamanca', @prov_choapa),
('Ovalle', @prov_limari),
('Combarbalá', @prov_limari),
('Monte Patria', @prov_limari),
('Punitaqui', @prov_limari),
('Río Hurtado', @prov_limari),

-- Región de Valparaíso
('Valparaíso', @prov_valparaiso),
('Casablanca', @prov_valparaiso),
('Concón', @prov_valparaiso),
('Juan Fernández', @prov_valparaiso),
('Puchuncaví', @prov_valparaiso),
('Quintero', @prov_valparaiso),
('Viña del Mar', @prov_valparaiso),
('Isla de Pascua', @prov_isla_pascua),
('Los Andes', @prov_los_andes),
('Calle Larga', @prov_los_andes),
('Rinconada', @prov_los_andes),
('San Esteban', @prov_los_andes),
('La Ligua', @prov_petorca),
('Cabildo', @prov_petorca),
('Papudo', @prov_petorca),
('Petorca', @prov_petorca),
('Zapallar', @prov_petorca),
('Quillota', @prov_quillota),
('La Calera', @prov_quillota),
('Hijuelas', @prov_quillota),
('La Cruz', @prov_quillota),
('Nogales', @prov_quillota),
('San Antonio', @prov_san_antonio),
('Algarrobo', @prov_san_antonio),
('Cartagena', @prov_san_antonio),
('El Quisco', @prov_san_antonio),
('El Tabo', @prov_san_antonio),
('Santo Domingo', @prov_san_antonio),
('San Felipe', @prov_san_felipe),
('Catemu', @prov_san_felipe),
('Llaillay', @prov_san_felipe),
('Panquehue', @prov_san_felipe),
('Putaendo', @prov_san_felipe),
('Santa María', @prov_san_felipe),
('Quilpué', @prov_marga_marga),
('Villa Alemana', @prov_marga_marga),
('Limache', @prov_marga_marga),
('Olmué', @prov_marga_marga),

-- Región de O'Higgins
('Rancagua', @prov_cachapoal),
('Codegua', @prov_cachapoal),
('Coinco', @prov_cachapoal),
('Coltauco', @prov_cachapoal),
('Doñihue', @prov_cachapoal),
('Graneros', @prov_cachapoal),
('Las Cabras', @prov_cachapoal),
('Machalí', @prov_cachapoal),
('Malloa', @prov_cachapoal),
('Mostazal', @prov_cachapoal),
('Olivar', @prov_cachapoal),
('Peumo', @prov_cachapoal),
('Pichidegua', @prov_cachapoal),
('Quinta de Tilcoco', @prov_cachapoal),
('Rengo', @prov_cachapoal),
('Requínoa', @prov_cachapoal),
('San Vicente', @prov_cachapoal),
('Pichilemu', @prov_cardenal_caro),
('La Estrella', @prov_cardenal_caro),
('Litueche', @prov_cardenal_caro),
('Marchihue', @prov_cardenal_caro),
('Navidad', @prov_cardenal_caro),
('Paredones', @prov_cardenal_caro),
('San Fernando', @prov_colchagua),
('Chépica', @prov_colchagua),
('Chimbarongo', @prov_colchagua),
('Lolol', @prov_colchagua),
('Nancagua', @prov_colchagua),
('Palmilla', @prov_colchagua),
('Peralillo', @prov_colchagua),
('Placilla', @prov_colchagua),
('Pumanque', @prov_colchagua),
('Santa Cruz', @prov_colchagua),

-- Región del Maule
('Talca', @prov_talca),
('Constitución', @prov_talca),
('Curepto', @prov_talca),
('Empedrado', @prov_talca),
('Maule', @prov_talca),
('Pelarco', @prov_talca),
('Pencahue', @prov_talca),
('Río Claro', @prov_talca),
('San Clemente', @prov_talca),
('San Rafael', @prov_talca),
('Cauquenes', @prov_cauquenes),
('Chanco', @prov_cauquenes),
('Pelluhue', @prov_cauquenes),
('Curicó', @prov_curico),
('Hualañé', @prov_curico),
('Licantén', @prov_curico),
('Molina', @prov_curico),
('Rauco', @prov_curico),
('Romeral', @prov_curico),
('Sagrada Familia', @prov_curico),
('Teno', @prov_curico),
('Vichuquén', @prov_curico),
('Linares', @prov_linares),
('Colbún', @prov_linares),
('Longaví', @prov_linares),
('Parral', @prov_linares),
('Retiro', @prov_linares),
('San Javier', @prov_linares),
('Villa Alegre', @prov_linares),
('Yerbas Buenas', @prov_linares),

-- Región del Bíobío
('Concepción', @prov_concepcion),
('Coronel', @prov_concepcion),
('Chiguayante', @prov_concepcion),
('Florida', @prov_concepcion),
('Hualqui', @prov_concepcion),
('Lota', @prov_concepcion),
('Penco', @prov_concepcion),
('San Pedro de la Paz', @prov_concepcion),
('Santa Juana', @prov_concepcion),
('Talcahuano', @prov_concepcion),
('Tomé', @prov_concepcion),
('Hualpén', @prov_concepcion),
('Lebu', @prov_arauco),
('Arauco', @prov_arauco),
('Cañete', @prov_arauco),
('Contulmo', @prov_arauco),
('Curanilahue', @prov_arauco),
('Los Álamos', @prov_arauco),
('Tirúa', @prov_arauco),
('Los Ángeles', @prov_biobio),
('Antuco', @prov_biobio),
('Cabrero', @prov_biobio),
('Laja', @prov_biobio),
('Mulchén', @prov_biobio),
('Nacimiento', @prov_biobio),
('Negrete', @prov_biobio),
('Quilaco', @prov_biobio),
('Quilleco', @prov_biobio),
('San Rosendo', @prov_biobio),
('Santa Bárbara', @prov_biobio),
('Tucapel', @prov_biobio),
('Yumbel', @prov_biobio),
('Alto Biobío', @prov_biobio),

-- Región de Ñuble
('Chillán', @prov_diguillin),
('Bulnes', @prov_diguillin),
('Chillán Viejo', @prov_diguillin),
('El Carmen', @prov_diguillin),
('Pemuco', @prov_diguillin),
('Pinto', @prov_diguillin),
('Quillón', @prov_diguillin),
('San Ignacio', @prov_diguillin),
('Yungay', @prov_diguillin),
('Cobquecura', @prov_itata),
('Coelemu', @prov_itata),
('Ninhue', @prov_itata),
('Portezuelo', @prov_itata),
('Quirihue', @prov_itata),
('Ránquil', @prov_itata),
('Treguaco', @prov_itata),
('Coihueco', @prov_punilla),
('Ñiquén', @prov_punilla),
('San Carlos', @prov_punilla),
('San Fabián', @prov_punilla),
('San Nicolás', @prov_punilla),

-- Región de La Araucanía
('Temuco', @prov_cautin),
('Carahue', @prov_cautin),
('Cunco', @prov_cautin),
('Curarrehue', @prov_cautin),
('Freire', @prov_cautin),
('Galvarino', @prov_cautin),
('Gorbea', @prov_cautin),
('Lautaro', @prov_cautin),
('Loncoche', @prov_cautin),
('Melipeuco', @prov_cautin),
('Nueva Imperial', @prov_cautin),
('Padre Las Casas', @prov_cautin),
('Perquenco', @prov_cautin),
('Pitrufquén', @prov_cautin),
('Pucón', @prov_cautin),
('Saavedra', @prov_cautin),
('Teodoro Schmidt', @prov_cautin),
('Toltén', @prov_cautin),
('Vilcún', @prov_cautin),
('Villarrica', @prov_cautin),
('Cholchol', @prov_cautin),
('Angol', @prov_malleco),
('Collipulli', @prov_malleco),
('Curacautín', @prov_malleco),
('Ercilla', @prov_malleco),
('Lonquimay', @prov_malleco),
('Los Sauces', @prov_malleco),
('Lumaco', @prov_malleco),
('Purén', @prov_malleco),
('Renaico', @prov_malleco),
('Traiguén', @prov_malleco),
('Victoria', @prov_malleco),

-- Región de Los Ríos
('Valdivia', @prov_valdivia),
('Corral', @prov_valdivia),
('Lanco', @prov_valdivia),
('Los Lagos', @prov_valdivia),
('Máfil', @prov_valdivia),
('Mariquina', @prov_valdivia),
('Paillaco', @prov_valdivia),
('Panguipulli', @prov_valdivia),
('Futrono', @prov_ranco),
('La Unión', @prov_ranco),
('Lago Ranco', @prov_ranco),
('Río Bueno', @prov_ranco),

-- Región de Los Lagos
('Puerto Montt', @prov_llanquihue),
('Calbuco', @prov_llanquihue),
('Cochamó', @prov_llanquihue),
('Fresia', @prov_llanquihue),
('Frutillar', @prov_llanquihue),
('Los Muermos', @prov_llanquihue),
('Llanquihue', @prov_llanquihue),
('Maullín', @prov_llanquihue),
('Puerto Varas', @prov_llanquihue),
('Castro', @prov_chiloe),
('Ancud', @prov_chiloe),
('Chonchi', @prov_chiloe),
('Curaco de Vélez', @prov_chiloe),
('Dalcahue', @prov_chiloe),
('Puqueldón', @prov_chiloe),
('Queilén', @prov_chiloe),
('Quellón', @prov_chiloe),
('Quemchi', @prov_chiloe),
('Quinchao', @prov_chiloe),
('Osorno', @prov_osorno),
('Puerto Octay', @prov_osorno),
('Purranque', @prov_osorno),
('Puyehue', @prov_osorno),
('Río Negro', @prov_osorno),
('San Juan de la Costa', @prov_osorno),
('San Pablo', @prov_osorno),
('Chaitén', @prov_palena),
('Futaleufú', @prov_palena),
('Hualaihué', @prov_palena),
('Palena', @prov_palena),

-- Región de Aisén
('Coihaique', @prov_coihaique),
('Lago Verde', @prov_coihaique),
('Aisén', @prov_aisen),
('Cisnes', @prov_aisen),
('Guaitecas', @prov_aisen),
('Cochrane', @prov_capitan_prat),
('O''Higgins', @prov_capitan_prat),
('Tortel', @prov_capitan_prat),
('Chile Chico', @prov_general_carrera),
('Río Ibáñez', @prov_general_carrera),

-- Región de Magallanes
('Punta Arenas', @prov_magallanes),
('Laguna Blanca', @prov_magallanes),
('Río Verde', @prov_magallanes),
('San Gregorio', @prov_magallanes),
('Cabo de Hornos', @prov_antarctica),
('Antártica', @prov_antarctica),
('Porvenir', @prov_tierra_fuego),
('Primavera', @prov_tierra_fuego),
('Timaukel', @prov_tierra_fuego),
('Natales', @prov_ultima_esperanza),
('Torres del Paine', @prov_ultima_esperanza),

-- Región Metropolitana
('Santiago', @prov_santiago),
('Cerrillos', @prov_santiago),
('Cerro Navia', @prov_santiago),
('Conchalí', @prov_santiago),
('El Bosque', @prov_santiago),
('Estación Central', @prov_santiago),
('Huechuraba', @prov_santiago),
('Independencia', @prov_santiago),
('La Cisterna', @prov_santiago),
('La Florida', @prov_santiago),
('La Granja', @prov_santiago),
('La Pintana', @prov_santiago),
('La Reina', @prov_santiago),
('Las Condes', @prov_santiago),
('Lo Barnechea', @prov_santiago),
('Lo Espejo', @prov_santiago),
('Lo Prado', @prov_santiago),
('Macul', @prov_santiago),
('Maipú', @prov_santiago),
('Ñuñoa', @prov_santiago),
('Pedro Aguirre Cerda', @prov_santiago),
('Peñalolén', @prov_santiago),
('Providencia', @prov_santiago),
('Pudahuel', @prov_santiago),
('Quilicura', @prov_santiago),
('Quinta Normal', @prov_santiago),
('Recoleta', @prov_santiago),
('Renca', @prov_santiago),
('San Joaquín', @prov_santiago),
('San Miguel', @prov_santiago),
('San Ramón', @prov_santiago),
('Vitacura', @prov_santiago),
('Puente Alto', @prov_cordillera),
('Pirque', @prov_cordillera),
('San José de Maipo', @prov_cordillera),
('Colina', @prov_chacabuco),
('Lampa', @prov_chacabuco),
('Tiltil', @prov_chacabuco),
('San Bernardo', @prov_maipo),
('Buin', @prov_maipo),
('Calera de Tango', @prov_maipo),
('Paine', @prov_maipo),
('Melipilla', @prov_melipilla),
('Alhué', @prov_melipilla),
('Curacaví', @prov_melipilla),
('María Pinto', @prov_melipilla),
('San Pedro', @prov_melipilla),
('Talagante', @prov_talagante),
('El Monte', @prov_talagante),
('Isla de Maipo', @prov_talagante),
('Padre Hurtado', @prov_talagante),
('Peñaflor', @prov_talagante);

-- =====================================================
-- VERIFICACIÓN Y ESTADÍSTICAS
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- Mostrar estadísticas
SELECT 
    'RESUMEN DE CARGA DE DATOS' as reporte;
    
SELECT 
    (SELECT COUNT(*) FROM regiones) as total_regiones,
    (SELECT COUNT(*) FROM provincia) as total_provincias,
    (SELECT COUNT(*) FROM comunas) as total_comunas;

-- Mostrar ejemplo por región
SELECT 
    r.nombre as region,
    COUNT(DISTINCT p.id) as provincias,
    COUNT(c.id) as comunas
FROM regiones r
LEFT JOIN provincia p ON p.region_id = r.id
LEFT JOIN comunas c ON c.provincia_id = p.id
GROUP BY r.id, r.nombre
ORDER BY r.nombre;

-- Verificar que no hay datos huérfanos
SELECT 
    'VERIFICACIÓN DE INTEGRIDAD REFERENCIAL' as check_integrity;
    
SELECT 
    'Regiones sin provincias' as check_type,
    r.nombre
FROM regiones r
LEFT JOIN provincia p ON p.region_id = r.id
WHERE p.id IS NULL;

SELECT 
    'Provincias sin comunas' as check_type,
    p.nombre,
    r.nombre as region
FROM provincia p
INNER JOIN regiones r ON r.id = p.region_id
LEFT JOIN comunas c ON c.provincia_id = p.id
WHERE c.id IS NULL;