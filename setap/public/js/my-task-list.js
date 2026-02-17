let taskToDelete = null;
let taskToChangeState = null;

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
    document.getElementById('changeStatePhotos').value = '';
    document.getElementById('changeStatePhotosCamera').value = '';

    new bootstrap.Modal(document.getElementById('changeStateModal')).show();
}

// GAP 5: Ejecutar cambio de estado
document.getElementById('confirmChangeState').addEventListener('click', function() {
    const confirmBtn = document.getElementById('confirmChangeState');
    const formElement = document.getElementById('changeStateForm');
    const formData = new FormData();

    formData.append('csrf_token', formElement.querySelector('input[name="csrf_token"]').value);
    formData.append('task_id', document.getElementById('changeStateTaskId').value);
    formData.append('new_state', document.getElementById('changeStateNewState').value);
    formData.append('reason', document.getElementById('changeStateReason').value);

    const galleryInput = document.getElementById('changeStatePhotos');
    const cameraInput = document.getElementById('changeStatePhotosCamera');

    Array.from(cameraInput.files || []).forEach(file => {
        formData.append('photos[]', file, file.name);
    });

    Array.from(galleryInput.files || []).forEach(file => {
        formData.append('photos[]', file, file.name);
    });

    confirmBtn.disabled = true;

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
        })
        .finally(() => {
            confirmBtn.disabled = false;
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

// GAP 5: Ver detalle de tarea
function viewDetail(taskId) {
    const taskName = document.querySelector(`#task-row-${taskId} .fw-bold`).textContent;
    const taskDetail = document.querySelector(`#task-row-${taskId} .text-hide`).textContent;
    const taskDateHH = document.getElementById(`date-hh-${taskId}`).textContent;
    const taskState = document.getElementById(`status-badge-${taskId}`).textContent;

    document.getElementById('detailTaskName').textContent = taskName;
    document.getElementById('detailTaskDescripcion').textContent = taskDetail;
    document.getElementById('detailTaskFechaDuracion').textContent = taskDateHH;
    document.getElementById('detailTaskStateName').textContent = taskState;

    new bootstrap.Modal(document.getElementById('detailTaskModal')).show();
}

function refreshMyTasksTableAjax() {
    const url = window.location.href;
    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('#myTasksTable tbody');
            const currentTbody = document.querySelector('#myTasksTable tbody');
            if (newTbody && currentTbody) {
                currentTbody.innerHTML = newTbody.innerHTML;
            }
            const newNav = doc.querySelector('nav[aria-label="Navegación de páginas"]');
            const currentNav = document.querySelector('nav[aria-label="Navegación de páginas"]');
            if (newNav) {
                if (currentNav) {
                    currentNav.innerHTML = newNav.innerHTML;
                } else {
                    const tableContainer = document.getElementById('myTasksTable')?.parentElement;
                    if (tableContainer) tableContainer.insertAdjacentElement('afterend', newNav);
                }
            } else if (currentNav) {
                currentNav.remove();
            }
        })
        .then(() => {
            showAlert('Datos de tareas actualizados', 'info');
        })
        .catch(() => {
            showAlert('Error al actualizar tareas', 'danger');
        });
}

let __lastActivityTs = Date.now();
let __inactive = false;
const __INACTIVITY_MS = 30000;

function __markActivity() {
    if (__inactive) {
        refreshMyTasksTableAjax();
        __inactive = false;
    }
    __lastActivityTs = Date.now();
}

['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(ev => {
    window.addEventListener(ev, __markActivity);
});

setInterval(() => {
    if (Date.now() - __lastActivityTs > __INACTIVITY_MS) {
        __inactive = true;
    }
}, 5000);
