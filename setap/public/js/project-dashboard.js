let currentProjectId = null;
let currentStatusFilter = null;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Obtener ID del proyecto desde el input hidden
    const projectIdInput = document.querySelector('input[name="proyecto_id"]');
    if (projectIdInput) {
        currentProjectId = projectIdInput.value;
    }
    refreshCardTasks();
    attachPaginationHandlers();
    attachStatusFilterHandlers();
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
        if (currentStatusFilter) {
            formData.append('estado_tipo_id', currentStatusFilter);
        }
        if (document.getElementById('fecha_inicio_filtro') != null){
            formData.append('fecha_inicio', document.getElementById('fecha_inicio_filtro').value)
        }
        if (document.getElementById('fecha_fin_filtro') != null){
            formData.append('fecha_fin', document.getElementById('fecha_fin_filtro').value)
        }

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

function filterClear(){
    document.getElementById('fecha_inicio_filtro').value = '';
    document.getElementById('fecha_fin_filtro').value = '';
    currentStatusFilter = null;
    updateStatusFilterUI();
    refreshCardTasks();
}

function attachStatusFilterHandlers() {
    const cards = document.querySelectorAll('.stat-card-filter');
    cards.forEach(card => {
        card.addEventListener('click', () => handleStatusFilterSelection(card));
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                handleStatusFilterSelection(card);
            }
        });
    });
    updateStatusFilterUI();
}

function handleStatusFilterSelection(card) {
    const status = card.getAttribute('data-task-status');
    currentStatusFilter = status ? status : null;
    updateStatusFilterUI();
    refreshCardTasks();
}

function updateStatusFilterUI() {
    document.querySelectorAll('.stat-card-filter').forEach(card => {
        const status = card.getAttribute('data-task-status');
        const isActive = currentStatusFilter === (status || null);
        card.classList.toggle('border-2', isActive);
        card.classList.toggle('shadow-sm', isActive);
        card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}
