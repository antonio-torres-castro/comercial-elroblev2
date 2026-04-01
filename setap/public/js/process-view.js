/**
 * JavaScript para la vista de visualizacion de procesos
 * Maneja la visualizacion de detalles de tareas
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Process View JS loaded');
    
    initEventListeners();
});

/**
 * Inicializar escuchadores de eventos
 */
function initEventListeners() {
    // Delegacion de eventos para botones de ver tarea
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view-task');
        if (btn) {
            const taskId = btn.dataset.taskId;
            viewTaskDetail(taskId);
        }
    });
}

/**
 * Ver detalle de tarea
 */
function viewTaskDetail(taskId) {
    const params = new URLSearchParams({ tarea_id: taskId });
    
    fetch('/setap/process/getTaskDetail?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.error || !data.task) {
                alert('Error al obtener detalle de tarea');
                return;
            }
            
            const task = data.task[0];
            document.getElementById('viewTaskNombre').textContent = task.nombre || '-';
            document.getElementById('viewTaskDescripcion').textContent = task.descripcion || '-';
            document.getElementById('viewTaskCategoria').textContent = task.categoria || 'N/A';
            document.getElementById('viewTaskEstado').textContent = task.estado || '-';
            
            new bootstrap.Modal(document.getElementById('viewTaskModal')).show();
        })
        .catch(error => {
            console.error('Error al obtener detalle:', error);
            alert('Error al obtener detalle de tarea');
        });
}
