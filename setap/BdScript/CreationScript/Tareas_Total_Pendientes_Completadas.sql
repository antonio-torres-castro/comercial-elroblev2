SET @fecha = curdate();
Select pt.proyecto_id, count(pt.id) as total 
From proyecto_tareas pt
Where pt.fecha_inicio <= @fecha 
and pt.estado_tipo_id in (2, 3, 5, 6, 7, 8);

Select pt.proyecto_id, count(pt.id) as pending
From proyecto_tareas pt
Where pt.fecha_inicio < @fecha 
and pt.estado_tipo_id in (2, 5, 6, 7);

Select pt.proyecto_id, count(pt.id) as complete
From proyecto_tareas pt
Where pt.fecha_inicio <= @fecha
and pt.estado_tipo_id = 8;

Select pt.proyecto_id, count(pt.id) as progress
From proyecto_tareas pt
Where pt.fecha_inicio = @fecha
and pt.estado_tipo_id in (2, 5, 6, 7);