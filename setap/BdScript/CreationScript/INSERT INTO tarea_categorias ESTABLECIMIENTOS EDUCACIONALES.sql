-- ==========================================
-- INDUSTRIA 4 : ESTABLECIMIENTOS EDUCACIONALES
-- ==========================================

INSERT INTO tarea_categorias (id, industria_id, parent_id, nombre) VALUES

-- CATEGORIAS
(224, 4, NULL, 'Aseo'),
(225, 4, NULL, 'Mantención'),
(226, 4, NULL, 'Seguridad'),
(227, 4, NULL, 'Áreas Exteriores'),
(228, 4, NULL, 'Gestión'),

-- ==========================================
-- ASEO
-- ==========================================

(229, 4, 224, 'Salas de Clase'),
(230, 4, 224, 'Laboratorios'),
(231, 4, 224, 'Áreas Comunes'),
(232, 4, 224, 'Servicios Higiénicos'),
(233, 4, 224, 'Gimnasios'),

-- Salas de Clase
(234, 4, 229, 'Limpieza General'),
(235, 4, 229, 'Sanitización'),

-- Laboratorios
(236, 4, 230, 'Limpieza Técnica'),
(237, 4, 230, 'Sanitización'),

-- Áreas Comunes
(238, 4, 231, 'Limpieza General'),
(239, 4, 231, 'Sanitización'),

-- Servicios Higiénicos
(240, 4, 232, 'Limpieza'),
(241, 4, 232, 'Reposición Insumos'),

-- Gimnasios
(242, 4, 233, 'Limpieza'),
(243, 4, 233, 'Conservación'),

-- ==========================================
-- MANTENCION
-- ==========================================

(244, 4, 225, 'Civil'),
(245, 4, 225, 'Eléctrica'),
(246, 4, 225, 'Sanitaria'),
(247, 4, 225, 'HVAC'),
(248, 4, 225, 'Tecnológica'),

-- Civil
(249, 4, 244, 'Infraestructura Educacional'),
(250, 4, 244, 'Pintura'),
(251, 4, 244, 'Carpintería'),

-- Eléctrica
(252, 4, 245, 'Iluminación'),
(253, 4, 245, 'Distribución Eléctrica'),

-- Sanitaria
(254, 4, 246, 'Redes Sanitarias'),
(255, 4, 246, 'Bombas'),

-- HVAC
(256, 4, 247, 'Climatización'),
(257, 4, 247, 'Ventilación'),

-- Tecnológica
(258, 4, 248, 'Computación'),
(259, 4, 248, 'Redes'),
(260, 4, 248, 'Equipamiento Audiovisual'),

-- ==========================================
-- SEGURIDAD
-- ==========================================

(261, 4, 226, 'Contra Incendio'),
(262, 4, 226, 'Emergencias'),
(263, 4, 226, 'Señalización'),
(264, 4, 226, 'Evacuación'),

-- Contra Incendio
(265, 4, 261, 'Extinción'),
(266, 4, 261, 'Detección'),

-- Emergencias
(267, 4, 262, 'Primeros Auxilios'),
(268, 4, 262, 'Respuesta Escolar'),

-- Señalización
(269, 4, 263, 'Seguridad Escolar'),
(270, 4, 263, 'Evacuación'),

-- Evacuación
(271, 4, 264, 'Simulacros'),
(272, 4, 264, 'Procedimientos'),

-- ==========================================
-- AREAS EXTERIORES
-- ==========================================

(273, 4, 227, 'Jardinería'),
(274, 4, 227, 'Canchas Deportivas'),
(275, 4, 227, 'Patios'),
(276, 4, 227, 'Estacionamientos'),

-- Jardinería
(277, 4, 273, 'Mantención'),
(278, 4, 273, 'Riego'),

-- Canchas Deportivas
(279, 4, 274, 'Conservación'),
(280, 4, 274, 'Demarcación'),

-- Patios
(281, 4, 275, 'Limpieza'),
(282, 4, 275, 'Conservación'),

-- Estacionamientos
(283, 4, 276, 'Demarcación'),
(284, 4, 276, 'Seguridad'),

-- ==========================================
-- GESTION
-- ==========================================

(285, 4, 228, 'Inspecciones'),
(286, 4, 228, 'Auditorías'),
(287, 4, 228, 'Reportes'),
(288, 4, 228, 'Checklists'),

-- Inspecciones
(289, 4, 285, 'Infraestructura'),
(290, 4, 285, 'Seguridad Escolar'),

-- Auditorías
(291, 4, 286, 'Cumplimiento'),
(292, 4, 286, 'Seguridad'),

-- Reportes
(293, 4, 287, 'Incidencias'),
(294, 4, 287, 'Mantenimiento'),

-- Checklists
(295, 4, 288, 'Apertura'),
(296, 4, 288, 'Cierre');