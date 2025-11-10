let currentProjectId = null;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Obtener ID del proyecto desde el input hidden
    const projectIdInput = document.querySelector('input[name="proyecto_id"]');
    if (projectIdInput) {
        currentProjectId = projectIdInput.value;
    }
    refreshCardTasks()
    attachPaginationHandlers();
});


// Auto-hide alerts after 5 seconds (excepto los que están dentro de modales)
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert:not(.modal .alert)');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

/**
 * Actualiza la tabla de feriados con AJAX y admite paginación.
 */
async function refreshCardTasks(page = 1) {
    try {
        const formData = new FormData();
        formData.append('proyecto_id', currentProjectId);
        formData.append('page', page);

        const response = await fetch('/setap/project/refreshCardTasks', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            const tableContainer = document.getElementById('card-tasks');
            if (tableContainer) {
                // reemplaza solo tbody y paginación
                tableContainer.innerHTML = data.html;
                attachPaginationHandlers();
            }
        } else {
            showAlert(data.message || 'No se pudieron cargar las tareas.', 'error');
        }
    } catch (error) {
        console.error(error);
        showAlert('Error al actualizar la tabla.', 'error');
    }
}

/**
 * Asocia los eventos click de paginación AJAX
 */
function attachPaginationHandlers() {
    document.querySelectorAll('.ajax-page').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = e.target.getAttribute('data-page');
            if (page) refreshCardTasks(page);
        });
    });
}