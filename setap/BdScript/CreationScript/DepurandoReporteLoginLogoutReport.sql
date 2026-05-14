SET @proveedor = 0;
DROP TABLE IF EXISTS tUsuarios;
Create Temporary Table tUsuarios
Select u.id,
	ut.nombre as tipo,
	u.nombre_usuario as nombre,
	c.razon_social as cliente,
	u.cliente_id,
	pr.razon_social as proveedor,
	u.proveedor_id,
	et.nombre as estado,
	u.fecha_Creado
From usuarios u
	Inner Join usuario_tipos ut on ut.id = u.usuario_tipo_id
	Inner Join estado_tipos et on et.id = u.estado_tipo_id
	Left Join clientes c on c.id = u.cliente_id
	Left Join proveedores pr on pr.id = u.proveedor_id
Where (
		@proveedor = 0
		or pr.id = @proveedor
		or c.proveedor_id = @proveedor
	);
Select case
		ul.accion_id
		when 1 then 'login'
		when 2 then 'logout'
	end accion,
	ul.fecha,
	ul.IP,
	t.nombre,
	t.tipo,
	t.cliente,
	t.proveedor,
	t.estado,
	t.fecha_creado
From usuario_logs ul
	Inner Join tUsuarios t on t.id = ul.usuario_id
Where ul.fecha between '2026-05-10' and '2026-05-10 23:59:59';