Delete From comerci3_bdsetap.menu Where id > 0;

/*Administrar Menu*/
INSERT INTO
    comerci3_bdsetap.menu (
        `id`,
        `nombre`,
        `descripcion`,
        `fecha_creacion`,
        `fecha_modificacion`,
        `estado_tipo_id`,
        `url`,
        `icono`,
        `orden`,
        `display`
    )
Select 1, 'manage_menus', 'Administrador lista de menus', '20250926', null, 2, '/menus', 'list-ul', 1, 'Gestión de Menús'
union
Select 2, 'manage_menu', 'Administrador detalle menu', '20250926', null, 2, '/menu', 'gear', 2, 'Menú'
union
/*El estado 1 es creado el registro, pero no funcional en web app*/
/*Administrar personas*/
Select 3, 'manage_personas', 'Administrar personas', '20250926', null, 2, '/personas', 'people-fill', 3, 'Personas'
union
Select 4, 'manage_persona', 'Administrar persona', '20250926', null, 2, '/persona', 'person-badge', 4, 'Persona'
union
/*Administrar Usuario*/
Select 5, 'view_perfil', 'Vista perfil usuario', '20250926', null, 2, '/perfil', 'person-circle', 5, 'Mi Perfil'
union
Select 6, 'manage_perfil', 'Administrar perfil usuario', '20250926', null, 2, '/perfil/edit', 'person-gear', 6, 'Editar Perfil'
union
Select 7, 'manage_users', 'Administrar usuarios', '20250926', null, 2, '/users', 'people', 7, 'Usuarios'
union
Select 8, 'manage_user', 'Administrar usuario', '20250926', null, 2, '/user', 'people-plus', 8, 'Usuario'
union
/*Administrar clientes*/
Select 9, 'manage_clients', 'Administrar clientes', '20250926', null, 2, '/clients', 'building', 9, 'Clientes'
union
Select 10, 'manage_client', 'Administrar cliente', '20250926', null, 2, '/client', 'building-add', 10, 'Cliente'
union
Select 11, 'manage_client_counterparties', 'Administrar contrapartes en cliente', '20250926', null, 2, '/client-counterparties', 'people-fill', 11, 'Contrapartes'
union
Select 12, 'manage_client_counterpartie', 'Administrar contraparte en cliente', '20250926', null, 2, '/client-counterpartie', 'person-badge-fill', 12, 'Contraparte'
union
/*Administrar proyectos*/
Select 13, 'manage_projects', 'Administrar proyectos cliente', '20250926', null, 2, '/projects', 'briefcase', 13, 'Proyectos'
union
Select 14, 'manage_project', 'Administrar proyecto cliente', '20250926', null, 2, '/project', 'briefcase-fill', 14, 'Proyecto'
union
/*Administrar tareas*/
Select 15, 'manage_tasks', 'Administrar tareas', '20250926', null, 2, '/tasks', 'list-check', 15, 'Tareas'
union
Select 16, 'manage_task', 'Administrar tarea', '20250926', null, 2, '/task', 'check-square', 16, 'Tarea';

SELECT * FROM comerci3_bdsetap.menu;