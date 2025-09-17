use comerci3_bdsetap;

-- =========================
-- Poblar tabla usuario_tipos
-- =========================
INSERT INTO usuario_tipos (id, nombre, descripcion) VALUES
(1, 'admin',       'Administrador del sistema con acceso completo'),
(2, 'planner',     'Crea planes y tareas, asigna ejecutores'),
(3, 'supervisor',  'Valida tareas, aprueba/rechaza ejecuciones'),
(4, 'executor',    'Ejecuta las tareas asignadas, adjunta fotos'),
(5, 'client',      'Cliente externo con acceso a tableros de control'),
(6, 'counterparty','Contraparte designada en el cliente para monitoreo de proyectos');

-- =========================
-- Poblar tabla estado_tipos
-- =========================
INSERT INTO estado_tipos (id, nombre, descripcion) VALUES
(1, 'creado',    'Registro en proceso de definición, aún no operativo'),
(2, 'activo',    'Registro completo y en operación'),
(3, 'inactivo',  'Registro completo pero suspendido temporalmente'),
(4, 'eliminado', 'Registro dado de baja lógicamente'),
(5, 'iniciado',  'Proyecto o tarea ha comenzado ejecución'),
(6, 'terminado', 'Proyecto o tarea finalizada, en espera de aprobación'),
(7, 'rechazado', 'Tarea rechazada por supervisor'),
(8, 'aprobado',  'Proyecto o tarea validada y aceptada');

-- =========================
-- Poblar tabla tarea_tipos
-- =========================
INSERT INTO tarea_tipos (id, nombre) VALUES
(1, 'intelectual'),
(2, 'physical');
