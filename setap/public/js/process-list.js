/**
 * JavaScript para la vista de lista de procesos
 * Maneja la confirmacion de eliminacion y navegacion
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Process List JS loaded');
    
    // Inicializar tooltips
    initTooltips();
});

/**
 * Confirmar eliminacion de proceso
 */
function confirmDelete(processId, processName) {
    if (confirm('Esta seguro que desea eliminar el proceso "' + processName + '"?\n\nEsta accion eliminara todas las tareas asociadas al proceso.')) {
        document.getElementById('deleteId').value = processId;
        document.getElementById('deleteForm').submit();
    }
}

/**
 * Inicializar tooltips de Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}
