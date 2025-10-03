-- Agregar campo display como VARCHAR para mostrar el título del menú al usuario
ALTER TABLE menu 
ADD COLUMN display VARCHAR(150) NULL AFTER orden;

-- Poblar el campo display con valores por defecto basados en el nombre
UPDATE menu SET display = CASE 
    WHEN nombre = 'manage_menus' THEN 'Gestión de Menús'
    WHEN nombre = 'manage_menu' THEN 'Menú'
    WHEN nombre = 'manage_personas' THEN 'Personas'
    WHEN nombre = 'manage_persona' THEN 'Persona'
    WHEN nombre = 'view_perfil' THEN 'Mi Perfil'
    WHEN nombre = 'manage_perfil' THEN 'Editar Perfil'
    WHEN nombre = 'manage_users' THEN 'Usuarios'
    WHEN nombre = 'manage_user' THEN 'Usuario'
    WHEN nombre = 'manage_clients' THEN 'Clientes'
    WHEN nombre = 'manage_client' THEN 'Cliente'
    WHEN nombre = 'manage_client_counterparties' THEN 'Contrapartes'
    WHEN nombre = 'manage_client_counterpartie' THEN 'Contraparte'
    WHEN nombre = 'manage_projects' THEN 'Proyectos'
    WHEN nombre = 'manage_project' THEN 'Proyecto'
    WHEN nombre = 'manage_tasks' THEN 'Tareas'
    WHEN nombre = 'manage_task' THEN 'Tarea'
    ELSE nombre
END
WHERE display IS NULL;