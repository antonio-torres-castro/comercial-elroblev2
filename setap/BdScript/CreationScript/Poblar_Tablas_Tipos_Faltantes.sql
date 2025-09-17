-- Poblamiento inicial de permisos
INSERT INTO permiso_tipos (Id, nombre, descripcion) VALUES
(1, 'All', 'Puede hacer todo, normalmente solo el administrador'),
(2, 'All by occur', 'Puede hacer de todo siempre y cuando la tarea esté por ocurrir, el registro esté por quedar activo'),
(3, 'Read', 'Puede leer registros'),
(4, 'Create', 'Puede crear registros'),
(5, 'Modify', 'Puede modificar cualquier registro'),
(6, 'Modify by occur', 'Puede modificar registros que no estén activos y tareas que no ocurran aún'),
(7, 'Assign', 'Puede asignar y re-asignar proyectos, tareas, etc'),
(8, 'Assign by occur', 'Puede asignar todo lo que no haya ocurrido aún'),
(9, 'Register activity', 'Puede registrar actividad de tareas: iniciar o terminar una tarea'),
(10, 'Apruve', 'Puede aprobar tareas'),
(11, 'Eliminate', 'Puede eliminar registros'),
(12, 'Eliminate by occur', 'Puede eliminar registros por ocurrir');

-- Poblamiento tabla: notificacion_tipos
INSERT INTO notificacion_tipos (Id, nombre, descripcion) VALUES
(1, 'Asignacion creada', 'Notificación cuando se crea una asignación de tarea'),
(2, 'Asignacion eliminada', 'Notificación cuando se elimina una asignación de tarea'),
(3, 'Cambio Estado Tarea', 'Notificación cuando cambia el estado de una tarea'),
(4, 'Cambio Estado Cliente', 'Notificación cuando cambia el estado de un cliente'),
(5, 'Cambio Estado Cliente Contraparte', 'Notificación cuando cambia el estado de una contraparte de cliente'),
(6, 'Cambio Estado Persona', 'Notificación cuando cambia el estado de una persona'),
(7, 'Cambio Estado Proyecto', 'Notificación cuando cambia el estado de un proyecto'),
(8, 'Cambio Estado Proyecto Tarea', 'Notificación cuando cambia el estado de una tarea asociada a un proyecto'),
(9, 'Cambio Estado Usuario', 'Notificación cuando cambia el estado de un usuario');

-- Poblamiento tabla: notificacion_medios
INSERT INTO notificacion_medios (Id, nombre, descripcion) VALUES
(1, 'email', 'Notificación enviada por correo electrónico'),
(2, 'whatsapp', 'Notificación enviada por WhatsApp');
