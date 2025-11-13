-- Primer Query Summary Tasks
SET @total = 0, @pending = 0, @complete = 0, @progress = 0, @fecha = curdate(); SET @project_id = 0;
Select count(pt.id) Into @total
From proyecto_tareas pt
Where pt.fecha_inicio <= @fecha 
and (pt.proyecto_id = @projet_id or @project_id = 0) 
and pt.estado_tipo_id in (2, 3, 5, 6, 7, 8);
Select count(pt.id) Into @pending
From proyecto_tareas pt
Where pt.fecha_inicio < @fecha
and (pt.proyecto_id = @projet_id or @project_id = 0) 
and pt.estado_tipo_id in (2, 5, 6, 7);
Select count(pt.id) Into @complete
From proyecto_tareas pt
Where pt.fecha_inicio <= @fecha
and (pt.proyecto_id = @projet_id or @project_id = 0)
and pt.estado_tipo_id = 8;
Select count(pt.id) Into @progress
From proyecto_tareas pt
Where pt.fecha_inicio = @fecha
and (pt.proyecto_id = @projet_id or @project_id = 0)
and pt.estado_tipo_id in (2, 5, 6, 7);
Select @total as total, @pending as pending, @complete as complete, @progress as progress;

-- Segundo Query Summary Tasks
DROP TABLE IF EXISTS SummaryTasks;
Create Temporary Table SummaryTasks
Select pt.id, pt.tarea_id, pt.proyecto_id, pt.fecha_inicio as inicio, pt.duracion_horas as dura, pt.prioridad, pt.estado_tipo_id
From proyecto_tareas pt
Where pt.fecha_inicio <= @fecha
and (pt.proyecto_id = @projet_id or @project_id = 0) 
and pt.estado_tipo_id in (2, 5, 6, 7);

Select st.id, t.nombre, st.inicio, st.dura, st.prioridad, e.nombre as estado,
c.razon_social, p.fecha_inicio, p.fecha_fin, tt.nombre as tipo_tarea
From SummaryTasks st
Inner Join estado_tipos e  on e.id  = st.estado_tipo_id
Inner Join proyectos    p  on p.id  = st.proyecto_id
Inner Join clientes     c  on c.id  = p.cliente_id
Inner Join Tareas       t  on t.id  = st.tarea_id
Inner Join tarea_tipos  tt on tt.id = p.tarea_tipo_id
