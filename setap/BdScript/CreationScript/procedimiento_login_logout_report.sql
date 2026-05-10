USE comerci3_bdsetap;
DROP procedure IF EXISTS login_logout_report;

DELIMITER $$
USE comerci3_bdsetap $$
CREATE PROCEDURE login_logout_report (
    IN p_proveedor_id INT,
    IN p_desde DATE,
    IN p_hasta DATE
)
BEGIN   
	DROP TABLE IF EXISTS tUsuarios;
	Create Temporary Table tUsuarios
	Select u.id, 
	ut.nombre as tipo,
	u.nombre_usuario as nombre, 
	c.razon_social as cliente, u.cliente_id,
	pr.razon_social as proveedor, u.proveedor_id,
	et.nombre as estado, 
	u.fecha_Creado
		  From usuarios    u
	Inner Join usuario_tipos ut on ut.id = u.usuario_tipo_id
	Inner Join estado_tipos  et on et.id = u.estado_tipo_id
	 Left Join clientes      c  on  c.id = u.cliente_id
	 Left Join proveedores   pr on pr.id = u.proveedor_id
	Where (p_proveedor_id = 0 or pr.id = p_proveedor_id or c.proveedor_id = p_proveedor_id);

	Select * From tUsuarios;

	Select 
	case ul.tipo_registro when 1 then 'login' when 2 then 'logout'
                          when 3 then 'Creo usuario' when 4 then 'Modifico Usuario' when 5 then 'Elimino Usuario' 
                          when 6 then 'Crea tarea tipo en proyecto'
                          when 7 then 'Crea proyecto-tarea masivo' when 8 then 'Crea proyecto-tarea especifica' 
                          when 9 then 'Crea proyecto-tarea rango' when 10 then 'Crea proyecto-tarea intervalo'
                          when 11 then 'Crea tarea tipo'
                          when 12 then 'Actualiza proyecto-tarea'
                          when 13 then 'Eliminación masiva proyecto-tarea'  when 14 then 'Eliminación de proyecto-tarea'
                          when 15 then 'Eliminación tarea tipo'
                          when 16 then 'Cambio estado de tarea' when 17 then 'Carga foto de evidencia' when 18 then 'Cambio estado grupo tareas'
                          end accion, 
	ul.fecha, ul.IP,
	t.nombre,
	t.tipo,
	t.cliente,
	t.proveedor,
	t.estado, t.fecha_Creado
		  From usuario_logs ul
	Inner Join tUsuarios    t  on t.id = ul.usuario_id
	Where ul.fecha between p_desde and p_hasta;
END$$

DELIMITER ;

