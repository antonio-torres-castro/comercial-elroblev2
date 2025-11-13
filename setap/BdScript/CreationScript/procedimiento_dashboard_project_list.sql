DROP PROCEDURE IF EXISTS dashboard_project_list;

DELIMITER //
CREATE PROCEDURE dashboard_project_list (in clienteId int, 
in estadoTipoId int, in tareaTipoId int,
in fechaDesde date, in fechaHasta date)
BEGIN
	-- Primer Query Summary Tasks
    SET clienteId    = IFNULL(clienteId,    0);
    SET estadoTipoId = IFNULL(estadoTipoId, 0);
    SET tareaTipoId  = IFNULL(tareaTipoId,  0);
    SET fechaDesde   = IFNULL(fechaDesde,   19000101);
    SET fechaHasta   = IFNULL(fechaHasta,   curdate());
    
    DROP TABLE IF EXISTS fClientes;
    Create Temporary Table fClientes
    Select c.id as cliente_id, c.razon_social, p.id as proyecto_id
          From clientes  c
    Inner Join proyectos p on p.cliente_id = c.id
    Where (clienteId    = 0 or c.id             = clienteId)
      and (estadoTipoId = 0 or p.estado_tipo_id = estadoTipoId)
      and (tareaTipoId  = 0 or p.tarea_tipo_id  = tareaTipoId);
    
	DROP TABLE IF EXISTS totalProjectTasks;
	Create Temporary Table totalProjectTasks
	Select pt.proyecto_id, count(pt.id) as total
	From proyecto_tareas pt
    Inner Join fClientes c on c.proyecto_id = pt.proyecto_id
	Where pt.fecha_inicio <= fechaHasta
	and pt.estado_tipo_id in (2, 3, 5, 6, 7, 8)
    GROUP BY pt.proyecto_id;
    DROP TABLE IF EXISTS pendingProjectTasks;
	Create Temporary Table pendingProjectTasks
	Select pt.proyecto_id, count(pt.id) as pending
	From proyecto_tareas pt
	Inner Join fClientes c on c.proyecto_id = pt.proyecto_id
    Where pt.fecha_inicio < fechaHasta
	and pt.estado_tipo_id in (2, 5, 6, 7)
    GROUP BY pt.proyecto_id;
	DROP TABLE IF EXISTS completeProjectTasks;
	Create Temporary Table completeProjectTasks
    Select pt.proyecto_id, count(pt.id) as complete
	From proyecto_tareas pt
    Inner Join fClientes c on c.proyecto_id = pt.proyecto_id
	Where pt.fecha_inicio <= fechaHasta
	and pt.estado_tipo_id = 8
    GROUP BY pt.proyecto_id;
    DROP TABLE IF EXISTS progressProjectTasks;
	Create Temporary Table progressProjectTasks
	Select pt.proyecto_id, count(pt.id) as progress
	From proyecto_tareas pt
    Inner Join fClientes c on c.proyecto_id = pt.proyecto_id
	Where pt.fecha_inicio = fechaHasta
	and pt.estado_tipo_id in (2, 5, 6, 7)
    GROUP BY pt.proyecto_id;

	-- Segundo Query Summary Projects
	SELECT  p.id, p.cliente_id, p.direccion, 
            p.fecha_inicio, p.fecha_fin, 
			p.tarea_tipo_id, p.estado_tipo_id, 
            p.contraparte_id, 
            p.fecha_Creado, p.fecha_modificacion,
            c.razon_social as cliente_nombre,
            tt.nombre      as tipo_tarea,
            et.nombre      as estado_nombre,
            CONCAT(per.nombre, ' (', per.rut, ')') as contraparte_nombre,
            cc.email     as contraparte_email,
            cc.telefono  as contraparte_telefono,
            
            tpt.total    as total_tareas,
            ppt.pending  as tareas_pendientes,
            cpt.complete as tareas_completadas,
            prg.progress as tareas_enprogreso
           FROM proyectos            p
	 Inner Join fClientes            c   on c.proyecto_id = p.id
     INNER JOIN tarea_tipos          tt  ON p.tarea_tipo_id   = tt.id
     INNER JOIN estado_tipos         et  ON p.estado_tipo_id  = et.id
     INNER JOIN cliente_contrapartes cc  ON p.contraparte_id  = cc.id
     INNER JOIN personas             per ON cc.persona_id     = per.id
      LEFT JOIN totalProjectTasks    tpt on tpt.proyecto_id   = p.id
      LEFT JOIN pendingProjectTasks  ppt on ppt.proyecto_id   = p.id
      LEFT JOIN completeProjectTasks cpt on cpt.proyecto_id   = p.id
      LEFT JOIN progressProjectTasks prg on prg.proyecto_id   = p.id
     WHERE (fechaDesde = '19000101' and fecha_inicio >= fechaDesde) 
     and p.fecha_inicio   <= fechaHasta 
     and p.fecha_fin      >= fechaHasta 
     and p.estado_tipo_id != 4;

END//
DELIMITER ;
