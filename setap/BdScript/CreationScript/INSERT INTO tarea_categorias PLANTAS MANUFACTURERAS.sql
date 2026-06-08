-- ==========================================
-- INDUSTRIA 3 : PLANTAS MANUFACTURERAS
-- ==========================================

INSERT INTO tarea_categorias (id, industria_id, parent_id, nombre) VALUES

-- CATEGORIAS
(152, 3, NULL, 'Aseo'),
(153, 3, NULL, 'Mantención'),
(154, 3, NULL, 'Seguridad'),
(155, 3, NULL, 'Áreas Exteriores'),
(156, 3, NULL, 'Gestión'),

-- ==========================================
-- ASEO
-- ==========================================

(157, 3, 152, 'Producción'),
(158, 3, 152, 'Bodegas'),
(159, 3, 152, 'Áreas Comunes'),
(160, 3, 152, 'Vestidores'),

-- Producción
(161, 3, 157, 'Limpieza Industrial'),
(162, 3, 157, 'Sanitización Industrial'),

-- Bodegas
(163, 3, 158, 'Limpieza Operacional'),
(164, 3, 158, 'Gestión Residuos'),

-- Áreas Comunes
(165, 3, 159, 'Limpieza General'),
(166, 3, 159, 'Sanitización'),

-- Vestidores
(167, 3, 160, 'Limpieza'),
(168, 3, 160, 'Sanitización'),

-- ==========================================
-- MANTENCION
-- ==========================================

(169, 3, 153, 'Mecánica'),
(170, 3, 153, 'Eléctrica'),
(171, 3, 153, 'Instrumentación'),
(172, 3, 153, 'Sanitaria'),
(173, 3, 153, 'HVAC'),
(174, 3, 153, 'Civil'),

-- Mecánica
(175, 3, 169, 'Equipos Rotativos'),
(176, 3, 169, 'Sistemas Transmisión'),
(177, 3, 169, 'Lubricación'),

-- Eléctrica
(178, 3, 170, 'Distribución Eléctrica'),
(179, 3, 170, 'Fuerza Motriz'),
(180, 3, 170, 'Protecciones'),

-- Instrumentación
(181, 3, 171, 'Sensores'),
(182, 3, 171, 'Automatización'),
(183, 3, 171, 'Control Industrial'),

-- Sanitaria
(184, 3, 172, 'Redes Industriales'),
(185, 3, 172, 'Tratamiento Aguas'),

-- HVAC
(186, 3, 173, 'Ventilación Industrial'),
(187, 3, 173, 'Extracción Industrial'),

-- Civil
(188, 3, 174, 'Infraestructura Industrial'),
(189, 3, 174, 'Pisos Industriales'),

-- ==========================================
-- SEGURIDAD
-- ==========================================

(190, 3, 154, 'Contra Incendio'),
(191, 3, 154, 'Emergencias'),
(192, 3, 154, 'Señalización'),
(193, 3, 154, 'Evacuación'),

-- Contra Incendio
(194, 3, 190, 'Sistemas Fijos'),
(195, 3, 190, 'Sistemas Portátiles'),

-- Emergencias
(196, 3, 191, 'Respuesta Incidentes'),
(197, 3, 191, 'Derrames'),

-- Señalización
(198, 3, 192, 'Riesgos Operacionales'),
(199, 3, 192, 'Seguridad Industrial'),

-- Evacuación
(200, 3, 193, 'Procedimientos'),
(201, 3, 193, 'Simulacros'),

-- ==========================================
-- AREAS EXTERIORES
-- ==========================================

(202, 3, 155, 'Patios Industriales'),
(203, 3, 155, 'Estacionamientos'),
(204, 3, 155, 'Áreas Verdes'),

-- Patios Industriales
(205, 3, 202, 'Conservación'),
(206, 3, 202, 'Limpieza'),

-- Estacionamientos
(207, 3, 203, 'Demarcación'),
(208, 3, 203, 'Seguridad'),

-- Áreas Verdes
(209, 3, 204, 'Mantención'),
(210, 3, 204, 'Riego'),

-- ==========================================
-- GESTION
-- ==========================================

(211, 3, 156, 'Inspecciones'),
(212, 3, 156, 'Auditorías'),
(213, 3, 156, 'Reportes'),
(214, 3, 156, 'Checklists'),

-- Inspecciones
(215, 3, 211, 'Equipos Críticos'),
(216, 3, 211, 'Seguridad'),

-- Auditorías
(217, 3, 212, 'Calidad'),
(218, 3, 212, 'Seguridad'),
(219, 3, 212, 'Medio Ambiente'),

-- Reportes
(220, 3, 213, 'Producción'),
(221, 3, 213, 'Mantenimiento'),

-- Checklists
(222, 3, 214, 'Preoperacionales'),
(223, 3, 214, 'Operacionales');