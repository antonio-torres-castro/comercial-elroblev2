/*Administrar Menu*/
INSERT INTO
    menu (
        id,
        nombre,
        descripcion,
        fecha_creacion,
        fecha_modificacion,
        estado_tipo_id,
        url,
        icono,
        orden,
        display
    )
Values (1, 'manage_menus', 'Administrador lista de menus', '20250926', null, 2, '/menus', 'list-ul', 1, 'Gestión de Menús'),
(2, 'manage_menu', 'Administrador detalle menu', '20250926', null, 2, '/menu', 'gear', 2, 'Menú');

/*El estado 1 es creado el registro, pero no funcional en web app*/
/*Administrar personas*/
INSERT INTO
    menu (
        id,
        nombre,
        descripcion,
        fecha_creacion,
        fecha_modificacion,
        estado_tipo_id,
        url,
        icono,
        orden,
        display
    )
Values (3, 'manage_personas', 'Administrar personas', '20250926', null, 2, '/personas', 'people-fill', 3, 'Personas'),
       (4, 'manage_persona', 'Administrar persona', '20250926', null, 2, '/persona', 'person-badge', 4, 'Persona');

/*Administrar Usuario*/
INSERT INTO
    menu (
        id,
        nombre,
        descripcion,
        fecha_creacion,
        fecha_modificacion,
        estado_tipo_id,
        url,
        icono,
        orden,
        display
    )
Values (5, 'view_perfil', 'Vista perfil usuario', '20250926', null, 2, '/perfil', 'person-circle', 5, 'Mi Perfil'),
(6, 'manage_perfil', 'Administrar perfil usuario', '20250926', null, 2, '/perfil/edit', 'person-gear', 6, 'Editar Perfil'),
(7, 'manage_users', 'Administrar usuarios', '20250926', null, 2, '/users', 'people', 7, 'Usuarios'),
(8, 'manage_user', 'Administrar usuario', '20250926', null, 2, '/user', 'person-plus', 8, 'Usuario');

/*Administrar clientes*/
INSERT INTO
    menu (
        id,
        nombre,
        descripcion,
        fecha_creacion,
        fecha_modificacion,
        estado_tipo_id,
        url,
        icono,
        orden,
        display
    )
Values (9, 'manage_clients', 'Administrar clientes', '20250926', null, 2, '/clients', 'building', 9, 'Clientes'),
(10, 'manage_client', 'Administrar cliente', '20250926', null, 2, '/client', 'building-add', 10, 'Cliente'),
(11, 'manage_client_counterparties', 'Administrar contrapartes en cliente', '20250926', null, 2, '/client-counterparties', 'people-fill', 11, 'Contrapartes'),
(12, 'manage_client_counterpartie', 'Administrar contraparte en cliente', '20250926', null, 2, '/client-counterpartie', 'person-badge-fill', 12, 'Contraparte');

/*Administrar proyectos*/
INSERT INTO
    menu (
        id,
        nombre,
        descripcion,
        fecha_creacion,
        fecha_modificacion,
        estado_tipo_id,
        url,
        icono,
        orden,
        display
    )
Values (13, 'manage_projects', 'Administrar proyectos cliente', '20250926', null, 2, '/projects', 'briefcase', 13, 'Proyectos'),
(14, 'manage_project', 'Administrar proyecto cliente', '20250926', null, 2, '/project', 'briefcase-fill', 14, 'Proyecto');

/*Administrar tareas*/
INSERT INTO
    menu (
        id,
        nombre,
        descripcion,
        fecha_creacion,
        fecha_modificacion,
        estado_tipo_id,
        url,
        icono,
        orden,
        display
    )
Values (15, 'manage_tasks', 'Administrar tareas', '20250926', null, 2, '/tasks', 'list-check', 15, 'Tareas'),
(16, 'manage_task', 'Administrar tarea', '20250926', null, 2, '/task', 'check-square', 16, 'Tarea');

Insert Into Menu
Values
('17', 'manage_access', 'Administra los menus que accede un Usuario Tipo', '2025-10-07', NULL, '2', '/setap/accesos', 'menu-button', '17', 'Accesos', '1'),
('18', 'manage_permissions', 'Administra los permisos de un Usuario Tipo', '2025-10-07', NULL, '2', '/setap/permisos', 'shield-lock', '18', 'Permisos', '2');