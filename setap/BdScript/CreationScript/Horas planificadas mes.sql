SELECT
    'mes' AS lapso,

    DATE_FORMAT(pt.fecha_inicio, '%Y-%m') AS mes,

    SUM(pt.duracion_horas) AS total_horas, ROUND( SUM(pt.duracion_horas) / (COUNT(DISTINCT pt.fecha_inicio) * 9), 2) AS personas,

    SUM(CASE WHEN pt.estado_tipo_id = 2 THEN pt.duracion_horas ELSE 0 END) AS horas_activo,
    SUM(CASE WHEN pt.estado_tipo_id = 5 THEN pt.duracion_horas ELSE 0 END) AS horas_iniciada,
    SUM(CASE WHEN pt.estado_tipo_id = 6 THEN pt.duracion_horas ELSE 0 END) AS horas_terminada,
    SUM(CASE WHEN pt.estado_tipo_id = 7 THEN pt.duracion_horas ELSE 0 END) AS horas_aprobada,
    SUM(CASE WHEN pt.estado_tipo_id = 8 THEN pt.duracion_horas ELSE 0 END) AS horas_rechazada

FROM proyecto_tareas pt
INNER JOIN tareas t ON pt.tarea_id = t.id
INNER JOIN proyectos p ON pt.proyecto_id = p.id
INNER JOIN proyecto_usuarios_grupo pug 
    ON pug.estado_tipo_id = 2 AND pug.proyecto_id = p.id
INNER JOIN grupo_tipos gt 
    ON gt.id BETWEEN 1 AND 5 AND gt.id = pug.grupo_id
INNER JOIN clientes c ON p.cliente_id = c.id
INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
INNER JOIN usuarios plan ON pt.planificador_id = plan.id
LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
LEFT JOIN usuarios super ON pt.supervisor_id = super.id

WHERE pt.estado_tipo_id IN (2,5,6,7,8)
AND pug.usuario_id = 1

GROUP BY DATE_FORMAT(pt.fecha_inicio, '%Y-%m')
ORDER BY mes;