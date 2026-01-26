-- Select * From tareas t where t.descripcion like '%sala%' or t.descripcion like '%Sala%';
-- Select * From tareas t where t.descripcion like '%baño%' or t.descripcion like '%Baño%';
-- Select * From tareas t where t.descripcion like '%pergola%' or t.descripcion like '%Pergola%';
-- Select * From tareas t where t.descripcion like '%patio%' or t.descripcion like '%patio%';
-- Select * From tareas t where (t.descripcion like '%oficina%' or t.descripcion like '%oficina%') and t.descripcion not like '%ante%';

UPDATE tareas t
JOIN (
    SELECT id
    FROM tareas
    WHERE descripcion LIKE '%sala%'
       OR descripcion LIKE '%Sala%'
) x ON x.id = t.id
SET t.tarea_categoria_id = 4;

UPDATE tareas t
JOIN (
    SELECT id
    FROM tareas
    WHERE descripcion LIKE '%baño%'
       OR descripcion LIKE '%Baño%'
) x ON x.id = t.id
SET t.tarea_categoria_id = 1;

UPDATE tareas t
JOIN (
    SELECT id
    FROM tareas
    WHERE descripcion LIKE '%pergola%'
       OR descripcion LIKE '%Pergola%'
) x ON x.id = t.id
SET t.tarea_categoria_id = 6;

UPDATE tareas t
JOIN (
    SELECT id
    FROM tareas
    WHERE descripcion LIKE '%patio%'
       OR descripcion LIKE '%Patio%'
) x ON x.id = t.id
SET t.tarea_categoria_id = 6;

UPDATE tareas t
JOIN (
    SELECT id
    FROM tareas
    WHERE (descripcion LIKE '%oficina%'
       OR descripcion LIKE '%Oficina%') and descripcion not like '%ante %'
) x ON x.id = t.id
SET t.tarea_categoria_id = 4;