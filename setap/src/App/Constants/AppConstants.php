<?php

namespace App\Constants;

/**
 * Constantes de la aplicación para evitar magic strings
 * Centraliza rutas, mensajes y otros valores utilizados frecuentemente
 */
class AppConstants
{

    // ===== RUTAS DE REDIRECCIÓN =====

    /** Ruta principal de login */
    const ROUTE_LOGIN = '/login';

    /** Ruta principal de home */
    const ROUTE_HOME = '/home';

    /** Ruta base de usuarios */
    const ROUTE_USERS = '/users';

    /** Ruta base de clientes */
    const ROUTE_CLIENTS = '/clients';

    /** Ruta base de tareas */
    const ROUTE_TASKS = '/tasks';

    /** Ruta base de personas */
    const ROUTE_PERSONAS = '/personas';

    /** Ruta base de menús */
    const ROUTE_MENUS = '/menus';

    /** Ruta base de perfil */
    const ROUTE_PERFIL = '/perfil';

    /** Ruta base de reportes */
    const ROUTE_REPORTS = '/reports';

    /** Ruta base de proyectos */
    const ROUTE_PROJECTS = '/projects';

    /** Ruta de contrapartes de clientes */
    const ROUTE_CLIENT_COUNTERPARTIES = '/client-counterparties';

    /** Ruta de creación de proyectos */
    const ROUTE_PROJECTS_CREATE = '/projects/create';

    // ===== RUTAS CON ACCIONES =====

    /** Ruta de creación de usuarios */
    const ROUTE_USERS_CREATE = '/users/create';

    /** Ruta de creación de personas */
    const ROUTE_PERSONAS_CREATE = '/personas/create';

    // ===== PARÁMETROS DE URL =====

    /** Parámetro de éxito en URL */
    const PARAM_SUCCESS = 'success';

    /** Parámetro de error en URL */
    const PARAM_ERROR = 'error';

    // ===== VALORES DE ÉXITO =====

    /** Mensaje de éxito para creación */
    const SUCCESS_CREATED = 'created';

    /** Mensaje de éxito para actualización */
    const SUCCESS_UPDATED = 'updated';

    /** Mensaje de éxito para eliminación */
    const SUCCESS_DELETED = 'deleted';

    /** Mensaje de éxito para eliminación de usuario */
    const SUCCESS_USER_DELETED = 'Usuario eliminado correctamente';

    /** Mensaje de éxito para eliminación de tarea */
    const SUCCESS_TASK_DELETED = 'Tarea eliminada correctamente';

    /** Mensaje de éxito para cambio de status */
    const SUCCESS_STATUS_CHANGED = 'status_changed';

    // ===== MENSAJES DE ERROR COMUNES =====

    /** Error de ID inválido */
    const ERROR_INVALID_ID = 'ID inválido';

    /** Error de ID de usuario inválido */
    const ERROR_INVALID_USER_ID = 'ID de usuario inválido';

    /** Error de ID de tarea inválido */
    const ERROR_INVALID_TASK_ID = 'ID de tarea inválido';

    /** Error de ID de persona inválido */
    const ERROR_INVALID_PERSONA_ID = 'ID de persona inválido';

    /** Error de usuario no encontrado */
    const ERROR_USER_NOT_FOUND = 'Usuario no encontrado';

    /** Error de usuario no encontrado */
    const ERROR_USER_NOT_AUTHENTICATED = 'No autenticado';

     /** Error de usuario no encontrado */
    const ERROR_USER_NOT_AUTHORIZED = 'No autorizado';

    /** Error de tarea no encontrada */
    const ERROR_TASK_NOT_FOUND = 'Tarea no encontrada';

    /** Error de persona no encontrada */
    const ERROR_PERSONA_NOT_FOUND = 'Persona no encontrada';

    /** Error interno del servidor */
    const ERROR_INTERNAL_SERVER = 'Error interno del servidor';

    /** Error interno del sistema */
    const ERROR_INTERNAL_SYSTEM = 'Error interno del sistema';

    /** Error de servidor */
    const ERROR_SERVER = 'server';

    /** Error de método no permitido */
    const ERROR_METHOD_NOT_ALLOWED = 'Método no permitido';

    /** Error de token de seguridad inválido */
    const ERROR_INVALID_SECURITY_TOKEN = 'Token de seguridad inválido';

    /** Error de token CSRF inválido */
    const ERROR_INVALID_CSRF_TOKEN = 'Token CSRF inválido';

    /** Error al crear persona */
    const ERROR_CREATE_PERSONA = 'Error al crear persona';

    /** Error al eliminar usuario */
    const ERROR_DELETE_USER = 'Error al eliminar el usuario';

    /** Error al eliminar tarea */
    const ERROR_DELETE_TASK = 'Error al eliminar la tarea';

    /** Error de autorización para eliminar propio usuario */
    const ERROR_CANNOT_DELETE_OWN_USER = 'No puedes eliminar tu propio usuario';

    /** Error de permisos para eliminar tareas aprobadas */
    const ERROR_CANNOT_DELETE_APPROVED_TASK = 'Solo usuarios Admin y Planner pueden eliminar tareas aprobadas';

    /** Error de persona en uso */
    const ERROR_PERSONA_IN_USE = 'No se pudo eliminar la persona. Puede estar siendo utilizada en otros registros';

    /** Error de ID de proyecto inválido */
    const ERROR_INVALID_PROJECT_ID = 'ID de proyecto inválido';

    /** Error de proyecto no encontrado */
    const ERROR_PROJECT_NOT_FOUND = 'Proyecto no encontrado';

    /** Error de datos inválidos */
    const ERROR_INVALID_DATA = 'Datos inválidos';

    /** Error al crear proyecto */
    const ERROR_CREATE_PROJECT = 'Error al crear proyecto';

    /** Error al eliminar proyecto */
    const ERROR_DELETE_PROJECT = 'Error al eliminar proyecto';

    /** Error de término de búsqueda corto */
    const ERROR_SEARCH_TERM_TOO_SHORT = 'El término de búsqueda debe tener al menos 3 caracteres';

    // ===== ERRORES DE ACCESO Y PERMISOS =====

    /** Error de acceso denegado */
    const ERROR_ACCESS_DENIED = 'No tienes acceso a esta sección.';

    /** Error de permisos insuficientes */
    const ERROR_NO_PERMISSIONS = 'No tienes permisos para acceder a esta sección.';

    /** Error de permisos para realizar acción */
    const ERROR_NO_ACTION_PERMISSIONS = 'No tienes permisos para realizar esta acción.';

    /** Error de permisos para editar perfil */
    const ERROR_NO_EDIT_PERMISSIONS = 'No tienes permisos para editar tu perfil.';

    // ===== ERRORES DE RECURSOS NO ENCONTRADOS =====

    /** Error de cliente no encontrado */
    const ERROR_CLIENT_NOT_FOUND = 'Cliente no encontrado';

    /** Error de menú no encontrado */
    const ERROR_MENU_NOT_FOUND = 'Menú no encontrado';

    /** Error de grupo no encontrado */
    const ERROR_GROUP_NOT_FOUND = 'Grupo no encontrado';

    // ===== ERRORES DE IDS INVÁLIDOS ADICIONALES =====

    /** Error de ID de cliente inválido */
    const ERROR_INVALID_CLIENT_ID = 'ID de cliente inválido';

    /** Error de ID de menú inválido */
    const ERROR_INVALID_MENU_ID = 'ID de menú inválido';

    // ===== ERRORES DE VALIDACIÓN =====

    /** Error de login requerido */
    const ERROR_LOGIN_REQUIRED = 'Usuario y contraseña son requeridos';

    /** Error de proyecto y fecha requeridos */
    const ERROR_PROJECT_DATE_REQUIRED = 'Proyecto y fecha son requeridos';

    /** Error de campos requeridos */
    const ERROR_REQUIRED_FIELDS = 'Por favor, completa todos los campos obligatorios.';

    /** Error de RUT inválido */
    const ERROR_INVALID_RUT = 'Por favor, ingresa un RUT válido.';

    /** Error de fechas inválidas */
    const ERROR_INVALID_DATES = 'Por favor, corrige los errores en las fechas.';

    /** Error de cliente requerido */
    const ERROR_CLIENT_REQUIRED = 'Por favor, selecciona un cliente para este tipo de usuario.';

    /** Error de email inválido */
    const ERROR_INVALID_EMAIL = 'Por favor, ingresa un email válido.';

    /** Error de campos no disponibles */
    const ERROR_UNAVAILABLE_FIELDS = 'Por favor, corrija los campos marcados como no disponibles antes de continuar.';

    /** Error de contraseña corta */
    const ERROR_INVALID_PASSWORD_LENGTH = 'Datos inválidos o contraseña muy corta';

    // ===== MENSAJES DE ÉXITO ESPECÍFICOS =====

    /** Éxito al crear usuario */
    const SUCCESS_USER_CREATED = 'Usuario creado exitosamente';

    /** Éxito al crear cliente */
    const SUCCESS_CLIENT_CREATED = 'Cliente creado exitosamente';

    /** Éxito al crear menú */
    const SUCCESS_MENU_CREATED = 'Menú creado exitosamente';

    /** Éxito al crear feriado */
    const SUCCESS_HOLIDAY_CREATED = 'Feriado creado exitosamente';

    /** Éxito al actualizar cliente */
    const SUCCESS_CLIENT_UPDATED = 'Cliente actualizado exitosamente';

    /** Éxito al actualizar menú */
    const SUCCESS_MENU_UPDATED = 'Menú actualizado exitosamente';

    /** Éxito al actualizar estado de menú */
    const SUCCESS_MENU_STATUS_UPDATED = 'Estado del menú actualizado exitosamente';

    /** Éxito al actualizar feriado */
    const SUCCESS_HOLIDAY_UPDATED = 'Feriado actualizado exitosamente';

    /** Éxito al eliminar cliente */
    const SUCCESS_CLIENT_DELETED = 'Cliente eliminado exitosamente';

    /** Éxito al eliminar menú */
    const SUCCESS_MENU_DELETED = 'Menú eliminado exitosamente';

    /** Éxito al eliminar feriado */
    const SUCCESS_HOLIDAY_DELETED = 'Feriado eliminado exitosamente';

    // ===== CONSTANTES DE INTERFAZ DE USUARIO =====

    /** Texto de carga */
    const UI_LOADING = 'Cargando...';

    /** Título de confirmación de eliminación */
    const UI_CONFIRM_DELETE = 'Confirmar Eliminación';

    /** Pregunta de confirmación de eliminación */
    const UI_CONFIRM_DELETE_QUESTION = '¿Estás seguro de que deseas eliminar';

    /** Confirmación de eliminación de menú */
    const UI_CONFIRM_DELETE_MENU = '¿Está seguro de que desea eliminar este menú?';

    /** Etiqueta de confirmar contraseña */
    const UI_CONFIRM_PASSWORD = 'Confirmar Contraseña';

    /** Texto de acción irreversible */
    const UI_ACTION_IRREVERSIBLE = 'Esta acción no se puede deshacer';

    /** Título de filtros de búsqueda */
    const UI_SEARCH_FILTERS = 'Filtros de Búsqueda';

    /** Placeholder buscar por nombre */
    const UI_SEARCH_BY_NAME = 'Buscar por nombre...';

    /** Placeholder buscar por RUT */
    const UI_SEARCH_BY_RUT = 'Buscar por RUT';

    /** Placeholder buscar usuarios */
    const UI_SEARCH_USERS = 'Buscar usuarios...';

    /** Título búsqueda avanzada */
    const UI_ADVANCED_SEARCH = 'Búsqueda Avanzada';

    const UI_TITLE_VIEW_MENU = 'Gestión de Menú';
    const UI_TITLE_VIEW_PERFIL_EDIT = 'Editar Perfil';
    const UI_SUBTITLE_VIEW_PERFIL_EDIT = 'Actualiza tu información personal';

    // ===== ERRORES DE CARGA =====

    /** Error cargando permisos */
    const ERROR_LOADING_PERMISSIONS = 'Error cargando permisos';

    /** Error cargando menús */
    const ERROR_LOADING_MENUS = 'Error cargando menús';

    /** Error cargando transiciones */
    const ERROR_LOADING_TRANSITIONS = 'Error cargando transiciones';

    // ===== MÉTODOS DE UTILIDAD =====

    /**
     * Construye una URL con parámetros de éxito
     */
    public static function buildSuccessUrl(string $baseRoute, string $message): string {
        return $baseRoute . '?' . self::PARAM_SUCCESS . '=' . $message;
    }

    /**
     * Construye una URL con parámetros de error
     */
    public static function buildErrorUrl(string $baseRoute, string $message): string {
        return $baseRoute . '?' . self::PARAM_ERROR . '=' . urlencode($message);
    }
}
