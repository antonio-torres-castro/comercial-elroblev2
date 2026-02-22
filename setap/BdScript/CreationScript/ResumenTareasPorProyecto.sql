SELECT
                count(pt.id) recurrencias,
                t.nombre, 
                DATE_FORMAT( min(pt.fecha_inicio) , '%Y-%m-%d') AS inicio, 
                avg(pt.duracion_horas) AS dura,
                pt.prioridad, e.nombre AS estado, c.razon_social,
                DATE_FORMAT( max(pt.fecha_inicio) , '%Y-%m-%d') AS fin,
                case when (pt.fecha_fin < '20260221' and pt.estado_tipo_id < 8) then 'Si' else '--' end AS atraso,
                tc.nombre AS categoria
            FROM proyecto_tareas pt
            INNER JOIN estado_tipos e ON e.id = pt.estado_tipo_id
            INNER JOIN proyectos p ON p.id = pt.proyecto_id
            INNER JOIN clientes c ON c.id = p.cliente_id
            INNER JOIN tareas t ON t.id = pt.tarea_id
            INNER JOIN tarea_categorias tc ON tc.id = t.tarea_categoria_id
            WHERE pt.estado_tipo_id IN (2,5,6,7,8)
            
            Group by t.nombre, pt.prioridad, e.nombre, c.razon_social, tc.nombre,  case when (pt.fecha_fin < '20260221' and pt.estado_tipo_id < 8) then 'Si' else '--' end;