<?php

namespace App\Constants;

/**
 * Constantes de la aplicación para evitar magic strings
 * Centraliza rutas, mensajes y otros valores utilizados frecuentemente
 */
class AppConstants {
    
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