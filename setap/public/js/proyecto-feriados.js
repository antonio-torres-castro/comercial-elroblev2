/**
 * Proyecto Feriados - JavaScript para mantenedor de feriados
 */

// Variables globales
let currentProjectId = null;
let pendingConflicts = [];

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeFormHandlers();
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
            showAlert('error', 'Debe seleccionar al menos un día de la semana');
            return;
        }

        const formData = new FormData(e.target);
        const response = await fetch('/proyecto-feriados/create-masivo', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                // Mostrar modal de conflictos
                showConflictModal(data.conflicts);
            } else {
                showAlert('success', data.message);
                refreshHolidaysTable();
                e.target.reset();
                // Marcar sábado y domingo por defecto
                document.getElementById('sabado').checked = true;
                document.getElementById('domingo').checked = true;
            }
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión. Intente nuevamente.');
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
        const response = await fetch('/proyecto-feriados/create-especifico', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                showConflictModal(data.conflicts);
            } else {
                showAlert('success', data.message);
                refreshHolidaysTable();
                e.target.reset();
            }
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión. Intente nuevamente.');
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
        const response = await fetch('/proyecto-feriados/create-rango', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                showConflictModal(data.conflicts);
            } else {
                showAlert('success', data.message);
                refreshHolidaysTable();
                e.target.reset();
            }
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión. Intente nuevamente.');
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

        const response = await fetch('/proyecto-feriados/move-tasks', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', `Tareas movidas exitosamente. ${data.moved_tasks} tareas han sido reprogramadas.`);
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
            modal.hide();
            
            refreshHolidaysTable();
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error al mover tareas. Intente nuevamente.');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

/**
 * Actualizar tabla de feriados
 */
async function refreshHolidaysTable() {
    try {
        const response = await fetch(`/proyecto-feriados/list?proyecto_id=${currentProjectId}`);
        const data = await response.json();
        
        if (data.success) {
            updateHolidaysTable(data.feriados);
            updateStats(data.stats);
        } else {
            console.error('Error al cargar feriados:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
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
        showAlert('error', 'Error al cargar datos del feriado');
    }
}

/**
 * Manejar envío del formulario de edición
 */
async function handleEditSubmit(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const response = await fetch('/proyecto-feriados/update', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editHolidayModal'));
            modal.hide();
            
            refreshHolidaysTable();
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error al actualizar feriado');
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

        const response = await fetch('/proyecto-feriados/delete', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            refreshHolidaysTable();
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error al eliminar feriado');
    }
}

/**
 * Utilidades
 */

/**
 * Mostrar alerta
 */
function showAlert(type, message) {
    // Crear elemento de alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar al inicio del container
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

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
        showAlert('error', 'La fecha de inicio debe ser menor o igual a la fecha fin');
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