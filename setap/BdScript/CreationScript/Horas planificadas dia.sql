SELECT
    'dia' as lapso,
    DATE(pt.fecha_inicio) fecha_inicio,
    
    SUM(pt.duracion_horas) AS total_horas, round(SUM(pt.duracion_horas) / 9, 2) as personas,

    SUM(CASE WHEN pt.estado_tipo_id = 2 THEN pt.duracion_horas ELSE 0 END) AS horas_activo,
    SUM(CASE WHEN pt.estado_tipo_id = 5 THEN pt.duracion_horas ELSE 0 END) AS horas_iniciada,
    SUM(CASE WHEN pt.estado_tipo_id = 6 THEN pt.duracion_horas ELSE 0 END) AS horas_terminada,
    SUM(CASE WHEN pt.estado_tipo_id = 7 THEN pt.duracion_horas ELSE 0 END) AS horas_aprobada,
    SUM(CASE WHEN pt.estado_tipo_id = 8 THEN pt.duracion_horas ELSE 0 END) AS horas_rechazada
    
FROM proyecto_tareas pt
INNER JOIN tareas t ON pt.tarea_id = t.id
INNER JOIN proyectos p ON pt.proyecto_id = p.id
Inner Join proyecto_usuarios_grupo pug on pug.estado_tipo_id = 2 and pug.proyecto_id = p.id
Inner Join grupo_tipos gt on gt.id between 1 and 5 and gt.id = pug.grupo_id
INNER JOIN clientes c ON p.cliente_id = c.id
INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
INNER JOIN usuarios plan ON pt.planificador_id = plan.id
LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
LEFT JOIN usuarios super ON pt.supervisor_id = super.id
                
Where pt.estado_tipo_id in (2, 5, 6, 7, 8) and pug.usuario_id = 1
                
GROUP BY pt.fecha_inicio
ORDER BY pt.fecha_inicio;

SELECT Distinct 
IFNULL(pp.nombre, '') as planner,
IFNULL(pe.nombre, '') as ejecutor, 
IFNULL(ps.nombre, '') as supervisor
FROM proyecto_tareas pt
INNER JOIN tareas t ON pt.tarea_id = t.id
INNER JOIN proyectos p ON pt.proyecto_id = p.id
Inner Join proyecto_usuarios_grupo pug on pug.estado_tipo_id = 2 and pug.proyecto_id = p.id
Inner Join grupo_tipos gt on gt.id between 1 and 5 and gt.id = pug.grupo_id
INNER JOIN clientes c ON p.cliente_id = c.id
INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
INNER JOIN usuarios plan ON pt.planificador_id = plan.id
inner join personas pp ON pp.id = plan.persona_id
LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
inner join personas pe ON pe.id = exec.persona_id
LEFT JOIN usuarios super ON pt.supervisor_id = super.id
inner join personas ps ON ps.id = super.persona_id
                
Where pt.estado_tipo_id in (2, 5, 6, 7, 8) and pug.usuario_id = 1;