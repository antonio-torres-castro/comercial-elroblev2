let taskToDelete = null;
let taskToChangeState = null;


document.addEventListener('DOMContentLoaded', function () {
    const estadoTipoSelect = document.getElementById('estado_tipo_id');

    const choices = new Choices(estadoTipoSelect, {
        removeItemButton: true,
        searchEnabled: true,
        placeholderValue: 'Seleccionar...',
        noResultsText: 'Sin resultados',
        itemSelectText: 'Seleccionar',
        shouldSort: false,
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const table = document.getElementById("tasksTable");
    if (!table) return;

    table.addEventListener("click", function (e) {

        // Buscar si el clic ocurrió dentro de la columna task-column
        const taskColumn = e.target.closest("#task-column");
        if (!taskColumn) return;

        // Obtener la fila
        const row = taskColumn.closest(".clickable-row");
        if (!row) return;

        // Alternar selección
        row.classList.toggle("table-active");
    });
});


// GAP 5: Cargar transiciones válidas para una tarea
function loadValidTransitions(taskId) {
    fetch(`/setap/tasks/valid-transitions?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            const menu = document.getElementById(`stateMenu${taskId}`);
            if (data.transitions && data.transitions.length > 0) {
                menu.innerHTML = '';
                data.transitions.forEach(transition => {
                    const li = document.createElement('li');
                    li.innerHTML = `<a class="dropdown-item" href="#" onclick="confirmStateChange(${taskId}, ${transition.id}, '${transition.nombre}')">
                                    <i class="bi bi-arrow-right"></i> ${transition.nombre}
                                </a>`;
                    menu.appendChild(li);
                });
            } else {
                menu.innerHTML = '<li><span class="dropdown-item-text text-muted">Sin transiciones disponibles</span></li>';
            }
        })
        .catch(error => {
            console.error('Error cargando transiciones:', error);
            const menu = document.getElementById(`stateMenu${taskId}`);
            menu.innerHTML = '<li><span class="dropdown-item-text text-danger">Error al cargar</span></li>';
        });
}

// GAP 5: Confirmar cambio de estado
function confirmStateChange(taskId, newStateId, newStateName) {
    const taskName = document.querySelector(`#task-row-${taskId} .fw-bold`).textContent;

    document.getElementById('changeStateTaskId').value = taskId;
    document.getElementById('changeStateNewState').value = newStateId;
    document.getElementById('changeStateTaskName').textContent = taskName;
    document.getElementById('changeStateNewStateName').textContent = newStateName;
    document.getElementById('changeStateReason').value = '';

    new bootstrap.Modal(document.getElementById('changeStateModal')).show();
}

// GAP 5: Ejecutar cambio de estado
document.getElementById('confirmChangeState').addEventListener('click', function() {
    const formData = new FormData(document.getElementById('changeStateForm'));

    fetch('/setap/tasks/change-state', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar badge de estado en la tabla
                const taskId = formData.get('task_id');
                const newStateId = formData.get('new_state');
                updateStatusBadge(taskId, newStateId);

                // Mostrar mensaje de éxito
                showAlert(data.message, 'success');

                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('changeStateModal')).hide();
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión al servidor', 'danger');
        });
});

// GAP 5: Actualizar badge de estado
function updateStatusBadge(taskId, stateId) {
    const badge = document.getElementById(`status-badge-${taskId}`);
    let badgeClass = 'bg-secondary';
    let statusText = '';

    switch (parseInt(stateId)) {
        case 1:
            badgeClass = 'bg-warning text-dark';
            statusText = 'Creado';
            break;
        case 2:
            badgeClass = 'bg-primary';
            statusText = 'Activo';
            break;
        case 3:
            badgeClass = 'bg-secondary';
            statusText = 'Inactivo';
            break;
        case 5:
            badgeClass = 'bg-info';
            statusText = 'Iniciado';
            break;
        case 6:
            badgeClass = 'bg-warning';
            statusText = 'Terminado';
            break;
        case 7:
            badgeClass = 'bg-danger';
            statusText = 'Rechazado';
            break;
        case 8:
            badgeClass = 'bg-success';
            statusText = 'Aprobado';
            break;
    }

    badge.className = `badge ${badgeClass}`;
    badge.textContent = statusText;
}

// Función para eliminar tareas con validación GAP 5
function deleteTask(id, name, stateId) {
    taskToDelete = id;
    document.getElementById('deleteTaskName').textContent = name;

    // GAP 5: Mostrar warning si es tarea aprobada
    const warning = document.getElementById('deleteWarning');
    if (stateId === 8) { // Estado aprobado
        warning.classList.remove('d-none');
        document.getElementById('deleteWarningMessage').textContent =
            'Esta tarea está aprobada. Solo Admin y Planner pueden eliminarla.';
    } else {
        warning.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (taskToDelete) {
        const formData = new FormData();
        formData.append('id', taskToDelete);

        fetch('/setap/tasks/delete', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remover fila de la tabla
                    document.getElementById(`task-row-${taskToDelete}`).remove();
                    showAlert(data.message, 'success');
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión al servidor', 'danger');
            });
    }
    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
});

function getSelectedTasks() {
    return [...document.querySelectorAll(".clickable-row.table-active")]
        .map(row => ({ id: row.id.replace("task-row-", ""), stateId: parseInt(row.dataset.stateId, 10) }))
        .filter(item => !isNaN(item.stateId));
}

// GAP 5: Confirmar cambio de estado a grupo
function confirmStateChangeForSelectedRows(nStateId) {
    const nStateName = nStateId == 8 ? 'Aprobado' : nStateId == 6 ? 'Terminado' : '';
    const taskName = 'Grupo de tareas';

    const selected = getSelectedTasks();
    if (!selected || selected.length === 0) {
        showAlert('No se encontraron filas seleccionadas', 'warning');
        return;
    }

    const allowedStates = nStateId == 8 ? [5, 6, 7] : nStateId == 6 ? [5, 7] : [];

    const eligible = selected.filter(item => allowedStates.includes(item.stateId)).map(item => item.id);

    if (eligible.length === 0) {
        showAlert('Las tareas seleccionadas no son elegibles para aprobación', 'warning');
        return;
    }

    document.getElementById('changeStateTaskIdsFSR').value = JSON.stringify(eligible);
    document.getElementById('changeStateNewStateFSR').value = nStateId;
    document.getElementById('changeStateNewStateNameFSR').textContent = nStateName;
    document.getElementById('changeStateTaskNameFSR').textContent = taskName + ' (' + eligible.length + ')';
    document.getElementById('changeStateReasonFSR').value = eligible.length + ' tareas revisadas por el supervisor';

    new bootstrap.Modal(document.getElementById('changeStateFSRModal')).show();
}

// GAP 5: Ejecutar cambio de estado
document.getElementById('confirmChangeStateFSR').addEventListener('click', function() {
    const form = document.getElementById('changeStateFormFSR');
    const formData = new FormData(form);

    fetch('/setap/tasks/change-stateFSR', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const newStateId = formData.get('new_state');
            const updatedIds = Array.isArray(data.updated_ids) ? data.updated_ids : [];
            updatedIds.forEach(id => {
                updateStatusBadge(id, newStateId);
                const row = document.getElementById(`task-row-${id}`);
                if (row) row.classList.remove('table-active');
            });

            showAlert(data.message || 'Tareas aprobadas', 'success');
            const modalEl = document.getElementById('changeStateFSRModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            form.reset();
        } else {
            showAlert('Error: ' + (data.message || 'No fue posible aprobar las tareas'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error de conexión al servidor', 'danger');
    });
});