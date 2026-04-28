USE `comerci3_bdsetap`;
DROP procedure IF EXISTS `stats_reports_projects`;

DELIMITER $$
USE `comerci3_bdsetap`$$
CREATE PROCEDURE `stats_reports_projects` (in clienteId int, in proveedorId int,
in fechaDesde date, in fechaHasta date)
BEGIN
    DECLARE total_users int default 0;
    DECLARE total_clients int default 0;
    DECLARE total_projects int default 0;
    DECLARE total_tasks int default 0;
    
    -- Primer Query Summary Tasks
    SET clienteId    = IFNULL(clienteId,    0);
    SET proveedorId  = IFNULL(proveedorId,  0);
    SET fechaDesde   = IFNULL(fechaDesde,   19000101);
    SET fechaHasta   = IFNULL(fechaHasta,   curdate());
    
    DROP TABLE IF EXISTS fClientes;
    Create Temporary Table fClientes
    Select c.id as c_id, p.id as p_id, c.proveedor_id, p.fecha_inicio, p.fecha_fin
          From clientes  c
    Inner Join proyectos p on p.cliente_id = c.id
    Inner Join proveedores pr on pr.id = c.proveedor_id
    Where (clienteId    = 0 or c.id             = clienteId)
      and (proveedorId  = 0 or c.proveedor_id   = proveedorId);
    

    Select count(distinct pug.usuario_id) into total_users
          From fClientes  c
    Inner Join proveedores pr on pr.id = c.proveedor_id
    Inner Join proyecto_usuarios_grupo pug on pug.proyecto_id = c.p_id
    Where (clienteId    = 0 or c.c_id           = clienteId)
      and (proveedorId  = 0 or c.proveedor_id   = proveedorId)
      and (fechaDesde = '19000101' or c.fecha_inicio < fechaDesde) 
      and c.fecha_fin >= fechaHasta;
    
    Select count(c.c_id) into total_clients
          From fClientes  c
    Inner Join proveedores pr on pr.id = c.proveedor_id
    Where (clienteId    = 0 or c.c_id           = clienteId)
      and (proveedorId  = 0 or c.proveedor_id   = proveedorId)
      and (fechaDesde = '19000101' or c.fecha_inicio < fechaDesde) 
      and c.fecha_fin >= fechaHasta;
    
    Select count(c.p_id) into total_projects
          From fClientes  c
    Inner Join proveedores pr on pr.id = c.proveedor_id
    Where (clienteId    = 0 or c.c_id           = clienteId)
      and (proveedorId  = 0 or c.proveedor_id   = proveedorId)
      and (fechaDesde = '19000101' or c.fecha_inicio < fechaDesde) 
      and c.fecha_fin >= fechaHasta;
    
	Select count(pt.id) into total_tasks
	From proyecto_tareas pt
    Inner Join fClientes c on c.p_id = pt.proyecto_id
	Where pt.fecha_inicio <= fechaHasta
	and pt.estado_tipo_id in (2, 3, 5, 6, 7, 8);

	-- Segundo Query Summary Projects
	Select total_users, total_clients, total_projects, total_tasks, fechaDesde, fechaHasta;
END$$

DELIMITER ;

