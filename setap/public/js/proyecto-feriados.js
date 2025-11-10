/**
 * Proyecto Feriados - JavaScript para mantenedor de feriados
 */

// Variables globales
let currentProjectId = null;
let pendingConflicts = [];

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeFormHandlers();
    attachPaginationHandlers();
    initializeModals();
    
    // Obtener ID del proyecto desde el input hidden
    const projectIdInput = document.querySelector('input[name="proyecto_id"]');
    if (projectIdInput) {
        currentProjectId = projectIdInput.value;
    }
});

/**
 * Inicializar manejadores de formularios
 */
function initializeFormHandlers() {
    // Formulario masivo
    const formMasivo = document.getElementById('form-masivo');
    if (formMasivo) {
        formMasivo.addEventListener('submit', handleMasivoSubmit);
    }

    // Formulario específico
    const formEspecifico = document.getElementById('form-especifico');
    if (formEspecifico) {
        formEspecifico.addEventListener('submit', handleEspecificoSubmit);
    }

    // Formulario rango
    const formRango = document.getElementById('form-rango');
    if (formRango) {
        formRango.addEventListener('submit', handleRangoSubmit);
    }

    // Formulario edición
    const editForm = document.getElementById('edit-holiday-form');
    if (editForm) {
        editForm.addEventListener('submit', handleEditSubmit);
    }
}

/**
 * Inicializar modales
 */
function initializeModals() {
    // Botón para mover tareas en modal de conflictos
    const moveTasksBtn = document.getElementById('move-tasks-btn');
    if (moveTasksBtn) {
        moveTasksBtn.addEventListener('click', handleMoveTasksClick);
    }
}

/**
 * Manejar envío del formulario masivo
 */
async function handleMasivoSubmit(e) {
    e.preventDefault();
    
    const button = document.getElementById('btn-create-masivo');
    const originalText = button.innerHTML;
    
    try {
        // Mostrar estado de carga
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        button.disabled = true;

        // Validar que al menos un día esté seleccionado
        const dias = document.querySelectorAll('input[name="dias[]"]:checked');
        if (dias.length === 0) {
            showAlert('Debe seleccionar al menos un día de la semana', 'error');
            return;
        }

        const formData = new FormData(e.target);
        const response = await fetch('/setap/proyecto-feriados/create-masivo', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                // Mostrar modal de conflictos
                showConflictModal(data.conflicts);
            } else {
                showAlert(data.message, 'success');
                refreshHolidaysTable();
                e.target.reset();
                // Marcar sábado y domingo por defecto
                document.getElementById('sabado').checked = true;
                document.getElementById('domingo').checked = true;
            }
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión. Intente nuevamente.', 'error');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

/**
 * Manejar envío del formulario específico
 */
async function handleEspecificoSubmit(e) {
    e.preventDefault();
    
    const button = document.getElementById('btn-create-especifico');
    const originalText = button.innerHTML;
    
    try {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        button.disabled = true;

        const formData = new FormData(e.target);
        const response = await fetch('/setap/proyecto-feriados/create-especifico', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                showConflictModal(data.conflicts);
            } else {
                showAlert(data.message, 'success');
                refreshHolidaysTable();
                e.target.reset();
            }
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión. Intente nuevamente.', 'error');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

/**
 * Manejar envío del formulario de rango
 */
async function handleRangoSubmit(e) {
    e.preventDefault();
    
    const button = document.getElementById('btn-create-rango');
    const originalText = button.innerHTML;
    
    try {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        button.disabled = true;

        const formData = new FormData(e.target);
        const response = await fetch('/setap/proyecto-feriados/create-rango', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                showConflictModal(data.conflicts);
            } else {
                showAlert(data.message, 'success');
                refreshHolidaysTable();
                e.target.reset();
            }
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión. Intente nuevamente.', 'error');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

/**
 * Mostrar modal de conflictos con tareas
 */
function showConflictModal(conflicts) {
    pendingConflicts = conflicts;
    
    const conflictList = document.getElementById('conflict-list');
    let html = '';
    
    conflicts.forEach(conflict => {
        html += `<div class="alert alert-warning">
            <strong>Fecha:</strong> ${formatDate(conflict.fecha)}<br>
            <strong>Tareas afectadas:</strong>
            <ul class="mb-0">`;
        
        conflict.tasks.forEach(task => {
            html += `<li>${task.tarea_nombre} (Estado: ${task.estado_nombre})</li>`;
        });
        
        html += `</ul></div>`;
    });
    
    conflictList.innerHTML = html;
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('conflictModal'));
    modal.show();
}

/**
 * Manejar click en botón de mover tareas
 */
async function handleMoveTasksClick() {
    const button = document.getElementById('move-tasks-btn');
    const originalText = button.innerHTML;
    
    try {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Moviendo...';
        button.disabled = true;

        // Recopilar IDs de todas las tareas conflictivas
        const taskIds = [];
        pendingConflicts.forEach(conflict => {
            conflict.tasks.forEach(task => {
                taskIds.push(task.id);
            });
        });

        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        formData.append('proyecto_id', currentProjectId);
        formData.append('task_ids', taskIds.join(','));
        formData.append('dias_a_mover', '1');

        const response = await fetch('/setap/proyecto-feriados/move-tasks', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert(`Tareas movidas exitosamente. ${data.moved_tasks} tareas han sido reprogramadas.`, 'success');
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
            modal.hide();
            
            refreshHolidaysTable();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al mover tareas. Intente nuevamente.', 'error');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

/**
 * Actualizar tabla de feriados
 */
async function refreshHolidaysTable(page = 1) {
    try {
        const response = await fetch(`/setap/proyecto-feriados/refreshHolidaysTable?proyecto_id=${currentProjectId}&page=${page}`);
        const data = await response.json();
        
        if (data.success) {
            updateHolidaysTable(data.feriados);
            updatePaginationNav(data.currentPage, data.totalPages);
            updateStats(data.stats);
        } else {
            console.error('Error al cargar feriados:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
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
            if (page) refreshHolidaysTable(page);
        });
    });
}

/**
 * Actualizar contenido de la tabla de feriados
 */
function updateHolidaysTable(feriados) {
    const tbody = document.getElementById('holidays-tbody');
    let html = '';
    
    feriados.forEach(feriado => {
        html += `
        <tr>
            <td>${formatDate(feriado.fecha)}</td>
            <td>${feriado.dia_semana}</td>
            <td>
                <span class="badge bg-${feriado.tipo_feriado === 'recurrente' ? 'info' : 'primary'}">
                    ${capitalize(feriado.tipo_feriado)}
                </span>
            </td>
            <td>
                ${feriado.ind_irrenunciable ? 
                    '<span class="badge bg-warning">Irrenunciable</span>' : 
                    '<span class="badge bg-success">Renunciable</span>'}
            </td>
            <td>${feriado.observaciones || ''}</td>
            <td>
                <span class="badge bg-${feriado.estado_tipo_id == 2 ? 'success' : 'secondary'}">
                    ${feriado.estado_nombre}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editHoliday(${feriado.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteHoliday(${feriado.id})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    tbody.innerHTML = html;
}

/**
 * Regenera el componente de paginación dinámicamente.
 * @param {number} currentPage - Página actual
 * @param {number} totalPages - Total de páginas disponibles
 */
function updatePaginationNav(currentPage, totalPages) {
    const pagination = document.querySelector('.pagination.justify-content-center');
    if (!pagination) return;

    let html = '';

    // Botón "Anterior"
    html += `
        <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link ajax-page" href="#" data-page="${currentPage - 1}">Anterior</a>
        </li>
    `;

    // Números de página
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link ajax-page" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Botón "Siguiente"
    html += `
        <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link ajax-page" href="#" data-page="${currentPage + 1}">Siguiente</a>
        </li>
    `;

    pagination.innerHTML = html;

    // Volver a enlazar eventos AJAX
    attachPaginationHandlers();
}


/**
 * Actualizar estadísticas
 */
function updateStats(stats) {
    // Esta función podría actualizar las estadísticas mostradas en la parte superior
    // Por ahora, simplemente refrescamos la página para mostrar stats actualizadas
}

/**
 * Editar feriado
 */
async function editHoliday(id) {
    try {
        // Aquí podrías cargar los datos del feriado específico
        // Por simplicidad, usaremos los datos de la tabla
        const modal = new bootstrap.Modal(document.getElementById('editHolidayModal'));
        
        // Configurar el ID en el formulario
        document.getElementById('edit-holiday-id').value = id;
        
        modal.show();
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar datos del feriado', 'error');
    }
}

/**
 * Manejar envío del formulario de edición
 */
async function handleEditSubmit(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const response = await fetch('/setap/proyecto-feriados/update', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editHolidayModal'));
            modal.hide();
            
            refreshHolidaysTable();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al actualizar feriado', 'error');
    }
}

/**
 * Eliminar feriado
 */
async function deleteHoliday(id) {
    if (!confirm('¿Está seguro de que desea eliminar este feriado?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        formData.append('id', id);

        const response = await fetch('/setap/proyecto-feriados/delete', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            refreshHolidaysTable();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al eliminar feriado', 'error');
    }
}

/**
 * Utilidades
 */

/**
 * Formatear fecha para mostrar
 */
function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-ES');
}

/**
 * Capitalizar primera letra
 */
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Obtener token CSRF
 */
function getCsrfToken() {
    const tokenInput = document.querySelector('input[name="csrf_token"]');
    return tokenInput ? tokenInput.value : '';
}

/**
 * Validar fechas
 */
function validateDates(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (start > end) {
        showAlert('La fecha de inicio debe ser menor o igual a la fecha fin', 'error');
        return false;
    }
    
    return true;
}

/**
 * Preseleccionar fechas del proyecto en formularios
 */
function setProjectDateLimits() {
    // Esta función podría establecer límites mínimos y máximos basados en las fechas del proyecto
    // Se puede implementar si es necesario
}

// Exportar funciones principales para uso global
window.refreshHolidaysTable = refreshHolidaysTable;
window.editHoliday = editHoliday;
window.deleteHoliday = deleteHoliday;