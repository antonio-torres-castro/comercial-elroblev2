/**
 * JavaScript para la vista de edicion de procesos
 * Maneja la busqueda de tareas, agregacion, eliminacion y creacion de nuevas tareas
 */

let processTasks = [];
let selectedTask = null;
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Process Edit JS loaded');
    
    initEventListeners();
    initTaskSearch();
    initTaskModal();
    updateTaskCount();
    populateInitialTasks();
});

/**
 * Inicializar escuchadores de eventos
 */
function initEventListeners() {
    // Boton agregar tarea
    document.getElementById('btnAddTask').addEventListener('click', addTaskToProcess);
    
    // Boton limpiar tareas
    document.getElementById('btnClearTasks').addEventListener('click', clearAllTasks);
    
    // Boton nueva tarea
    document.getElementById('btnNewTask').addEventListener('click', openNewTaskModal);
    
    // Selector de categoria
    document.getElementById('categoria_id').addEventListener('change', function() {
        refreshTaskSearch(true);
    });
    
    // Proveedor cambio - recargar tareas disponibles
    const proveedorSelect = document.getElementById('proveedor_id');
    if (proveedorSelect) {
        proveedorSelect.addEventListener('change', function() {
            clearProcessTasksTable();
            processTasks = [];
            updateTaskCount();
            refreshTaskSearch(true);
        });
    }
    
    // Submit del formulario
    document.getElementById('processForm').addEventListener('submit', prepareFormSubmission);
    
    // Delegacion de eventos para botones dinamicos
    document.getElementById('processTasksBody').addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        
        if (btn.classList.contains('btn-remove-task')) {
            const row = btn.closest('tr');
            const taskId = parseInt(row.dataset.taskId);
            removeTaskFromProcess(taskId);
        }
        
        if (btn.classList.contains('btn-view-task')) {
            const taskId = btn.dataset.taskId;
            viewTaskDetail(taskId);
        }
    });
}

/**
 * Inicializar busqueda de tareas con autocompletado
 */
function initTaskSearch() {
    const searchInput = document.getElementById('task_search');
    const resultsContainer = document.getElementById('taskSearchResults');
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        searchTimeout = setTimeout(() => {
            searchTasks(query);
        }, 300);
    });
    
    searchInput.addEventListener('focus', function() {
        searchTasks(this.value.trim() || '');
    });
    
    // Ocultar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#task_search') && !e.target.closest('#taskSearchResults')) {
            resultsContainer.classList.add('d-none');
        }
    });
}

/**
 * Buscar tareas por nombre (SERVER SIDE)
 */
function searchTasks(query) {
    const proveedorId = document.getElementById('proveedor_id').value;
    const categoriaId = document.getElementById('categoria_id').value;
    
    if (!proveedorId) {
        alert('Por favor seleccione un proveedor primero');
        return;
    }
    
    const params = new URLSearchParams({
        proveedor_id: proveedorId,
        categoria_id: categoriaId || '',
        q: query || ''
    });
    
    fetch('/setap/process/getTasks?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            
            displaySearchResults(data.tasks || []);
        })
        .catch(error => {
            console.error('Error al buscar tareas:', error);
        });
}

/**
 * Mostrar resultados de busqueda
 */
function displaySearchResults(tasks) {
    const resultsContainer = document.getElementById('taskSearchResults');
    
    if (tasks.length === 0) {
        resultsContainer.innerHTML = '<div class="list-group-item text-muted">No se encontraron tareas</div>';
        resultsContainer.classList.remove('d-none');
        return;
    }
    
    resultsContainer.innerHTML = tasks.map(task => `
        <button type="button" class="list-group-item list-group-item-action task-result" 
                data-task-id="${task.id}"
                data-task-nombre="${task.nombre}"
                data-task-descripcion="${task.descripcion || ''}"
                data-task-categoria="${task.categoria_nombre || 'N/A'}">
            <strong>${task.nombre}</strong>
            <br><small class="text-muted">${task.categoria_nombre || 'Sin categoria'}</small>
        </button>
    `).join('');
    
    resultsContainer.classList.remove('d-none');
    
    // Agregar eventos de clic a los resultados
    resultsContainer.querySelectorAll('.task-result').forEach(btn => {
        btn.addEventListener('click', function() {
            selectTask(this);
        });
    });
}

/**
 * Seleccionar tarea de los resultados
 */
function selectTask(btn) {
    selectedTask = {
        id: btn.dataset.taskId,
        nombre: btn.dataset.taskNombre,
        descripcion: btn.dataset.taskDescripcion,
        categoria: btn.dataset.taskCategoria
    };
    
    document.getElementById('task_search').value = selectedTask.nombre;
    document.getElementById('taskSearchResults').classList.add('d-none');
}

/**
 * Refrescar busqueda
 */
function refreshTaskSearch(force = false) {
    const query = document.getElementById('task_search').value.trim();
    if (force || query.length >= 0) {
        searchTasks(query);
    }
}

/**
 * Agregar tarea al proceso
 */
function addTaskToProcess() {
    if (!selectedTask) {
        alert('Por favor seleccione una tarea de la lista');
        return;
    }
    
    const hh = parseFloat(document.getElementById('tarea_hh').value);
    const prioridad = parseInt(document.getElementById('prioridad').value);
    if (isNaN(hh) || hh < 0.5) {
        alert('La duracion debe ser al menos 0.5 horas');
        return;
    }
    
    // Verificar si la tarea ya esta agregada
    if (processTasks.some(t => t.tarea_id === parseInt(selectedTask.id))) {
        alert('Esta tarea ya esta agregada al proceso');
        return;
    }
    
    processTasks.push({
        tarea_id: parseInt(selectedTask.id),
        nombre: selectedTask.nombre,
        descripcion: selectedTask.descripcion,
        categoria: selectedTask.categoria,
        hh: hh,
        prioridad: prioridad // Valor por defecto, se puede modificar para permitir seleccion
    });
    
    renderProcessTasksTable();
    updateTaskCount();
    
    // Limpiar seleccion
    selectedTask = null;
    document.getElementById('task_search').value = '';
    document.getElementById('tarea_hh').value = '0.5';
    document.getElementById('prioridad').value = '5';
}

/**
 * Remover tarea del proceso
 */
function removeTaskFromProcess(taskId) {
    processTasks = processTasks.filter(t => t.tarea_id !== taskId);
    renderProcessTasksTable();
    updateTaskCount();
}

/**
 * Limpiar todas las tareas del proceso
 */
function clearAllTasks() {
    if (processTasks.length === 0) return;
    
    if (confirm('Esta seguro que desea eliminar todas las tareas del proceso?')) {
        processTasks = [];
        renderProcessTasksTable();
        updateTaskCount();
    }
}

/**
 * Renderizar tabla de tareas del proceso
 */
function renderProcessTasksTable() {
    const tbody = document.getElementById('processTasksBody');
    const noTasksMsg = document.getElementById('noTasksMessage');
    
    if (processTasks.length === 0) {
        tbody.innerHTML = '';
        if (noTasksMsg!=null) {
            noTasksMsg.classList.remove('d-none');
        }
        return;
    }

    if (noTasksMsg!=null) {
            noTasksMsg.classList.add('d-none');
    }
    
    tbody.innerHTML = processTasks.map(task => `
        <tr data-task-id="${task.tarea_id}" data-hh="${task.hh}">
            <td>${escapeHtml(task.nombre)}</td>
            <td>${task.hh.toFixed(1)} hrs</td>
            <td>${task.prioridad}</td>
            <td>${escapeHtml(task.categoria || 'N/A')}</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-info btn-view-task" 
                        data-task-id="${task.tarea_id}">
                    <i class="bi bi-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-task">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Actualizar contador de tareas
 */
function updateTaskCount() {
    const totalHrs = processTasks.reduce((sum, t) => sum + t.hh, 0);
    document.getElementById('taskCount').textContent = 
        `${processTasks.length} tarea${processTasks.length !== 1 ? 's' : ''} (${totalHrs.toFixed(1)} hrs)`;
}

/**
 * Preparar envio del formulario
 */
function prepareFormSubmission(e) {
    document.getElementById('processTasksJson').value = JSON.stringify(processTasks);
    
    const proveedorId = document.getElementById('proveedor_id').value;
    const nombre = document.getElementById('nombre').value.trim();
    
    if (!proveedorId) {
        e.preventDefault();
        alert('Por favor seleccione un proveedor');
        return;
    }
    
    if (!nombre) {
        e.preventDefault();
        alert('Por favor ingrese el nombre del proceso');
        return;
    }
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
            
            const task = data.task;
            document.getElementById('viewTaskNombre').textContent = task.nombre || '-';
            document.getElementById('viewTaskDescripcion').textContent = task.descripcion || '-';
            document.getElementById('viewTaskCategoria').textContent = task.categoria_nombre || 'N/A';
            document.getElementById('viewTaskEstado').textContent = task.estado_nombre || '-';
            
            new bootstrap.Modal(document.getElementById('viewTaskModal')).show();
        })
        .catch(error => {
            console.error('Error al obtener detalle:', error);
            alert('Error al obtener detalle de tarea');
        });
}

/**
 * Inicializar modal de nueva tarea
 */
function initTaskModal() {
    document.getElementById('newTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createNewTask();
    });
}

/**
 * Abrir modal de nueva tarea
 */
function openNewTaskModal() {
    document.getElementById('newTaskForm').reset();
    new bootstrap.Modal(document.getElementById('newTaskModal')).show();
}

/**
 * Crear nueva tarea via AJAX
 */
function createNewTask() {
    const nombre = document.getElementById('nueva_tarea_nombre').value.trim();
    const categoriaId = document.getElementById('nueva_tarea_categoria').value;
    const estadoId = document.getElementById('nueva_tarea_estado').value;
    const descripcion = document.getElementById('nueva_tarea_descripcion').value.trim();
    const proveedorId = document.getElementById('proveedor_id').value;
    
    if (!nombre) {
        alert('Por favor ingrese el nombre de la tarea');
        return;
    }
    
    if (!proveedorId) {
        alert('No se puede crear tarea: Falta informacion del proveedor');
        return;
    }
    
    const formData = new FormData();
    formData.append('nueva_tarea_nombre', nombre);
    formData.append('tarea_categoria_id', categoriaId);
    formData.append('estado_tipo_id', estadoId);
    formData.append('nueva_tarea_descripcion', descripcion);
    formData.append('proveedor_id', proveedorId);
    
    // Agregar CSRF token
    const csrfToken = document.querySelector('#newTaskForm input[name="csrf_token"]');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken.value);
    }
    
    fetch('/setap/tasks/storetp', {
        method: 'POST',
        body: formData
    })
        .then(async response => {
                const data = await response.json();

                if (!response.ok) {
                    // Lanza error con el mensaje real del backend
                    throw {
                            message: data.error || 'Error desconocido',
                            status: response.status
                        };
                }

                return data;
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                alert('Tarea creada exitosamente');
                
                // Actualizar busqueda de tareas
                refreshTaskSearch(true);
            } else {
                alert('Error al crear tarea: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
                            console.error(error);
                            alert(`Error (${error.status}): ${error.message}`);
                        });
}

/**
 * Poblar tareas iniciales (para modo edicion con datos existentes)
 */
function populateInitialTasks() {
    const rows = document.querySelectorAll('#processTasksBody tr');
    rows.forEach(row => {
        processTasks.push({
            tarea_id: parseInt(row.dataset.taskId),
            hh: parseFloat(row.dataset.hh) || 0.5,
            prioridad: parseInt(row.dataset.prioridad) || 5,
            nombre: row.querySelector('td:first-child').textContent.trim(),
            categoria: row.querySelector('td:nth-child(3)').textContent.trim()
        });
    });
}

/**
 * Limpiar tabla de tareas del proceso
 */
function clearProcessTasksTable() {
    processTasks = [];
    renderProcessTasksTable();
}

/**
 * Escapar HTML para prevenir XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
