Update tareas 
set tarea_categoria_id = null
where id > 0;

Delete From tarea_categorias where id > 0;

ALTER TABLE tarea_categorias AUTO_INCREMENT = 1;

Select * From tareas where tarea_categoria_id > 0;
Select * From tarea_categorias where id > 0;