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
    const taskSpace = document.getElementById(`space-${taskId}`).textContent;
    const taskCode = document.getElementById(`code-${taskId}`).textContent;
    const taskLevel = document.getElementById(`level-${taskId}`).textContent;
    const taskOrder = document.getElementById(`order-${taskId}`).textContent;
    const taskParentSpace = document.getElementById(`parent-space-${taskId}`).textContent;


    document.getElementById('detailTaskName').textContent = taskName.trim();
    document.getElementById('detailTaskDescripcion').textContent = taskDetail.trim();
    document.getElementById('detailTaskFechaDuracion').textContent = taskDateHH.trim();
    document.getElementById('detailTaskStateName').textContent = taskState.trim();
    document.getElementById('detailTaskSpaceName').textContent = taskSpace.trim();
    document.getElementById('detailTaskSpaceCode').textContent = taskCode.trim();
    document.getElementById('detailTaskSpaceLevel').textContent = taskLevel.trim();
    document.getElementById('detailTaskSpaceOrder').textContent = taskOrder.trim();
    document.getElementById('detailTaskParentSpaceName').textContent = taskParentSpace.trim();

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

// Autocompletado de tareas
const taskInput = document.getElementById('task_autocomplete');
const resultsContainer = document.getElementById('autocomplete_results');
const proyectoSelect = document.getElementById('proyecto_id');
const direccionSelect = document.getElementById('direccion_id');
const espacioPadreSelect = document.getElementById('espacio_padre_id');
let debounceTimer;

// Dependencia de selectores Proyecto -> Dirección -> Espacio Padre
if (proyectoSelect && direccionSelect) {
    proyectoSelect.addEventListener('change', function() {
        const projectId = this.value;
        if (!projectId) {
            updateSelect(direccionSelect, [], 'Seleccionar...');
            updateSelect(espacioPadreSelect, [], 'Seleccionar...');
            return;
        }
        refreshDirecciones(projectId);
    });
}

if (direccionSelect && espacioPadreSelect) {
    direccionSelect.addEventListener('change', function() {
        const direccionId = this.value;
        if (!direccionId) {
            updateSelect(espacioPadreSelect, [], 'Seleccionar...');
            return;
        }
        refreshEspaciosPadre(direccionId);
    });
}

async function refreshDirecciones(projectId) {
    try {
        const response = await fetch(`/setap/tasks/refreshDireccionSelect?proyecto_id=${projectId}`);
        const data = await response.json();
        if (data.success) {
            updateSelect(direccionSelect, data.direcciones, 'Seleccionar...');
            updateSelect(espacioPadreSelect, [], 'Seleccionar...');
        }
    } catch (error) {
        console.error('Error refreshing direcciones:', error);
    }
}

async function refreshEspaciosPadre(direccionId) {
    try {
        const response = await fetch(`/setap/tasks/refreshEspaciosPadreSelect?direccion_id=${direccionId}`);
        const data = await response.json();
        if (data.success) {
            updateSelect(espacioPadreSelect, data.espacios, 'Seleccionar...');
        }
    } catch (error) {
        console.error('Error refreshing espacios padre:', error);
    }
}

function updateSelect(selectElement, items, placeholder) {
    if (!selectElement) {
        return;
    }
    let html = `<option value="">${placeholder}</option>`;
    items.forEach(item => {
        html += `<option value="${item.id}">${item.nombre}</option>`;
    });
    selectElement.innerHTML = html;
}

if (taskInput) {
    taskInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const term = this.value.trim();

        if (term.length < 2) {
            resultsContainer.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(() => {
            const form = document.getElementById('getFormFilter');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            params.set('term', term); // El controlador espera 'term' para la búsqueda AJAX

            fetch(`/setap/tasks/searchTasksAutocomplete?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.tasks.length > 0) {
                        resultsContainer.innerHTML = '';
                        data.tasks.forEach(task => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = `
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${task.label}</h6>
                                        <small>${task.fecha_inicio}</small>
                                    </div>
                                `;
                            item.addEventListener('click', (e) => {
                                e.preventDefault();
                                // Al seleccionar una tarea de la lista:
                                // 1. Ponemos el nombre en el input
                                taskInput.value = task.label;
                                // 2. Ocultamos los resultados
                                resultsContainer.classList.add('d-none');
                                // 3. Enviamos el formulario para que se apliquen TODOS los filtros
                                form.submit();
                            });
                            resultsContainer.appendChild(item);
                        });
                        resultsContainer.classList.remove('d-none');
                    } else {
                        resultsContainer.classList.add('d-none');
                    }
                });
        }, 300);
    });

    // Cerrar resultados al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!taskInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.classList.add('d-none');
        }
    });
};