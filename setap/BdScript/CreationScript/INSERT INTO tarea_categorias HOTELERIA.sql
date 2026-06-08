-- ==========================================
-- INDUSTRIA 1 : HOTELERIA
-- ==========================================

INSERT INTO tarea_categorias (id, industria_id, parent_id, nombre) VALUES

-- CATEGORIAS
(1, 1, NULL, 'Aseo'),
(2, 1, NULL, 'Mantención'),
(3, 1, NULL, 'Seguridad'),
(4, 1, NULL, 'Áreas Exteriores'),
(5, 1, NULL, 'Gestión'),

-- ==========================================
-- ASEO
-- ==========================================

(6, 1, 1, 'Habitaciones'),
(7, 1, 1, 'Áreas Comunes'),
(8, 1, 1, 'Restaurantes y Cocinas'),
(9, 1, 1, 'Lavandería'),

-- Habitaciones
(10, 1, 6, 'Limpieza'),
(11, 1, 6, 'Sanitización'),
(12, 1, 6, 'Housekeeping'),

-- Áreas Comunes
(13, 1, 7, 'Limpieza Interior'),
(14, 1, 7, 'Sanitización'),
(15, 1, 7, 'Ornamentación'),

-- Restaurantes y Cocinas
(16, 1, 8, 'Limpieza Operacional'),
(17, 1, 8, 'Sanitización Alimentaria'),
(18, 1, 8, 'Gestión de Residuos'),

-- Lavandería
(19, 1, 9, 'Lavado'),
(20, 1, 9, 'Planchado'),
(21, 1, 9, 'Gestión de Ropa'),

-- ==========================================
-- MANTENCION
-- ==========================================

(22, 1, 2, 'Civil'),
(23, 1, 2, 'Eléctrica'),
(24, 1, 2, 'Sanitaria'),
(25, 1, 2, 'HVAC'),
(26, 1, 2, 'Tecnológica'),

-- Civil
(27, 1, 22, 'Pintura'),
(28, 1, 22, 'Albañilería'),
(29, 1, 22, 'Carpintería'),
(30, 1, 22, 'Techumbres'),

-- Eléctrica
(31, 1, 23, 'Iluminación'),
(32, 1, 23, 'Fuerza'),
(33, 1, 23, 'Tableros'),
(34, 1, 23, 'Respaldo Energético'),

-- Sanitaria
(35, 1, 24, 'Agua Potable'),
(36, 1, 24, 'Alcantarillado'),
(37, 1, 24, 'Bombas'),
(38, 1, 24, 'Agua Caliente'),

-- HVAC
(39, 1, 25, 'Climatización'),
(40, 1, 25, 'Ventilación'),
(41, 1, 25, 'Extracción'),

-- Tecnológica
(42, 1, 26, 'Redes'),
(43, 1, 26, 'CCTV'),
(44, 1, 26, 'Control de Acceso'),
(45, 1, 26, 'Entretenimiento'),

-- ==========================================
-- SEGURIDAD
-- ==========================================

(46, 1, 3, 'Contra Incendio'),
(47, 1, 3, 'Emergencias'),
(48, 1, 3, 'Señalización'),
(49, 1, 3, 'Evacuación'),

-- Contra Incendio
(50, 1, 46, 'Extinción'),
(51, 1, 46, 'Detección'),
(52, 1, 46, 'Alarmas'),

-- Emergencias
(53, 1, 47, 'Primeros Auxilios'),
(54, 1, 47, 'Respuesta Operacional'),

-- Señalización
(55, 1, 48, 'Seguridad'),
(56, 1, 48, 'Evacuación'),

-- Evacuación
(57, 1, 49, 'Planificación'),
(58, 1, 49, 'Simulacros'),

-- ==========================================
-- AREAS EXTERIORES
-- ==========================================

(59, 1, 4, 'Jardinería'),
(60, 1, 4, 'Piscinas'),
(61, 1, 4, 'Estacionamientos'),
(62, 1, 4, 'Fachadas'),

-- Jardinería
(63, 1, 59, 'Mantención Áreas Verdes'),
(64, 1, 59, 'Riego'),

-- Piscinas
(65, 1, 60, 'Tratamiento Agua'),
(66, 1, 60, 'Equipamiento'),

-- Estacionamientos
(67, 1, 61, 'Demarcación'),
(68, 1, 61, 'Iluminación'),

-- Fachadas
(69, 1, 62, 'Limpieza'),
(70, 1, 62, 'Conservación'),

-- ==========================================
-- GESTION
-- ==========================================

(71, 1, 5, 'Inspecciones'),
(72, 1, 5, 'Auditorías'),
(73, 1, 5, 'Reportes'),
(74, 1, 5, 'Checklists'),

-- Inspecciones
(75, 1, 71, 'Operacionales'),
(76, 1, 71, 'Infraestructura'),

-- Auditorías
(77, 1, 72, 'Calidad'),
(78, 1, 72, 'Cumplimiento'),

-- Reportes
(79, 1, 73, 'Operacionales'),
(80, 1, 73, 'Incidencias'),

-- Checklists
(81, 1, 74, 'Preventivos'),
(82, 1, 74, 'Operacionales');