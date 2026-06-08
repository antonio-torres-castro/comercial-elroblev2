-- ==========================================
-- INDUSTRIA 2 : EDIFICIOS CORPORATIVOS
-- ==========================================

INSERT INTO tarea_categorias (id, industria_id, parent_id, nombre) VALUES

-- CATEGORIAS
(83, 2, NULL, 'Aseo'),
(84, 2, NULL, 'Mantención'),
(85, 2, NULL, 'Seguridad'),
(86, 2, NULL, 'Áreas Exteriores'),
(87, 2, NULL, 'Gestión'),

-- ==========================================
-- ASEO
-- ==========================================

(88, 2, 83, 'Oficinas'),
(89, 2, 83, 'Áreas Comunes'),
(90, 2, 83, 'Servicios Higiénicos'),
(91, 2, 83, 'Comedores'),

-- Oficinas
(92, 2, 88, 'Limpieza General'),
(93, 2, 88, 'Sanitización'),

-- Áreas Comunes
(94, 2, 89, 'Limpieza Interior'),
(95, 2, 89, 'Sanitización'),

-- Servicios Higiénicos
(96, 2, 90, 'Limpieza'),
(97, 2, 90, 'Reposición Insumos'),

-- Comedores
(98, 2, 91, 'Limpieza'),
(99, 2, 91, 'Gestión Residuos'),

-- ==========================================
-- MANTENCION
-- ==========================================

(100, 2, 84, 'Civil'),
(101, 2, 84, 'Eléctrica'),
(102, 2, 84, 'Sanitaria'),
(103, 2, 84, 'HVAC'),
(104, 2, 84, 'Tecnológica'),

-- Civil
(105, 2, 100, 'Terminaciones'),
(106, 2, 100, 'Pintura'),
(107, 2, 100, 'Carpintería'),

-- Eléctrica
(108, 2, 101, 'Iluminación'),
(109, 2, 101, 'Fuerza'),
(110, 2, 101, 'Respaldo Energético'),

-- Sanitaria
(111, 2, 102, 'Redes Sanitarias'),
(112, 2, 102, 'Bombas'),

-- HVAC
(113, 2, 103, 'Climatización'),
(114, 2, 103, 'Ventilación'),

-- Tecnológica
(115, 2, 104, 'Redes'),
(116, 2, 104, 'Cableado Estructurado'),
(117, 2, 104, 'Control Acceso'),
(118, 2, 104, 'CCTV'),

-- ==========================================
-- SEGURIDAD
-- ==========================================

(119, 2, 85, 'Contra Incendio'),
(120, 2, 85, 'Emergencias'),
(121, 2, 85, 'Señalización'),
(122, 2, 85, 'Evacuación'),

-- Contra Incendio
(123, 2, 119, 'Extinción'),
(124, 2, 119, 'Detección'),

-- Emergencias
(125, 2, 120, 'Respuesta Operacional'),
(126, 2, 120, 'Evacuación'),

-- Señalización
(127, 2, 121, 'Seguridad'),
(128, 2, 121, 'Tránsito'),

-- Evacuación
(129, 2, 122, 'Simulacros'),
(130, 2, 122, 'Planes Emergencia'),

-- ==========================================
-- AREAS EXTERIORES
-- ==========================================

(131, 2, 86, 'Jardinería'),
(132, 2, 86, 'Estacionamientos'),
(133, 2, 86, 'Fachadas'),

-- Jardinería
(134, 2, 131, 'Mantención'),
(135, 2, 131, 'Riego'),

-- Estacionamientos
(136, 2, 132, 'Demarcación'),
(137, 2, 132, 'Control Vehicular'),

-- Fachadas
(138, 2, 133, 'Limpieza'),
(139, 2, 133, 'Conservación'),

-- ==========================================
-- GESTION
-- ==========================================

(140, 2, 87, 'Inspecciones'),
(141, 2, 87, 'Auditorías'),
(142, 2, 87, 'Reportes'),
(143, 2, 87, 'Checklists'),

-- Inspecciones
(144, 2, 140, 'Infraestructura'),
(145, 2, 140, 'Equipamiento'),

-- Auditorías
(146, 2, 141, 'Calidad'),
(147, 2, 141, 'Seguridad'),

-- Reportes
(148, 2, 142, 'Operacionales'),
(149, 2, 142, 'SLA'),

-- Checklists
(150, 2, 143, 'Apertura'),
(151, 2, 143, 'Cierre');