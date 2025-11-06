let taskToDelete = null;
let taskToChangeState = null;

// GAP 5: Cargar transiciones válidas para una tarea
function loadValidTransitions(taskId, proyectoId) {
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

    fetch('<?= AppConstants::ROUTE_TASKS; ?>/change-state', {
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
            badgeClass = 'bg-success';
            statusText = 'Activo';
            break;
        case 3:
            badgeClass = 'bg-secondary';
            statusText = 'Inactivo';
            break;
        case 5:
            badgeClass = 'bg-primary';
            statusText = 'Iniciado';
            break;
        case 6:
            badgeClass = 'bg-info text-dark';
            statusText = 'Terminado';
            break;
        case 7:
            badgeClass = 'bg-danger';
            statusText = 'Rechazado';
            break;
        case 8:
            badgeClass = 'bg-dark';
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

        fetch('<?= AppConstants::ROUTE_TASKS; ?>/delete', {
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