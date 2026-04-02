document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('createTaskByProcessForm');
    const proveedorSelect = document.getElementById('proveedor_id');
    const proyectoSelect = document.getElementById('proyecto_id');
    const procesoSelect = document.getElementById('proceso_id');
    const supervisorSelect = document.getElementById('supervisor_id');
    const btnVerTareas = document.getElementById('btnVerTareas');
    const taskList = document.getElementById('taskList');

    // 1. Al seleccionar un proveedor: filtrar proyectos y procesos
    if (proveedorSelect) {
        proveedorSelect.addEventListener('change', async function () {
            const proveedorId = this.value;
            if (!proveedorId) {
                updateSelect(proyectoSelect, [], 'Seleccionar proyecto...');
                updateSelect(procesoSelect, [], 'Seleccionar proceso...');
                updateSelect(supervisorSelect, [], 'Seleccionar supervisor...');
                return;
            }

            // Cargar proyectos y procesos en paralelo
            await Promise.all([
                refreshProjects(proveedorId),
                refreshProcesses(proveedorId),
                refreshSupervisors(proveedorId, null)
            ]);
        });
    }

    // 2. Al seleccionar un proyecto: filtrar supervisores
    if (proyectoSelect) {
        proyectoSelect.addEventListener('change', function () {
            const proyectoId = this.value;
            const proveedorId = proveedorSelect ? proveedorSelect.value : '';
            refreshSupervisors(proveedorId, proyectoId);
        });
    }

    // 3. Habilitar/deshabilitar botón de ver tareas
    if (procesoSelect) {
        procesoSelect.addEventListener('change', function () {
            btnVerTareas.disabled = !this.value;
        });
    }

    // 4. Ver tareas del proceso en el modal
    if (btnVerTareas) {
        btnVerTareas.addEventListener('click', function () {
            const processId = procesoSelect.value;
            taskList.innerHTML = '<li class="list-group-item text-center">Cargando tareas...</li>';

            fetch(`/setap/tasks/getProcessTasksJson?process_id=${processId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        taskList.innerHTML = '';
                        if (data.tasks && data.tasks.length > 0) {
                            data.tasks.forEach(task => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                                li.innerHTML = `<span>${task.tarea_nombre}</span> <span class="badge bg-primary rounded-pill">${task.hh}h</span>`;
                                taskList.appendChild(li);
                            });
                        } else {
                            taskList.innerHTML = '<li class="list-group-item text-center">No hay tareas asociadas</li>';
                        }
                    } else {
                        taskList.innerHTML = `<li class="list-group-item text-danger text-center">${data.message}</li>`;
                    }
                })
                .catch(error => {
                    taskList.innerHTML = '<li class="list-group-item text-danger text-center">Error al cargar tareas</li>';
                });
        });
    }

    // 5. Validación antes de enviar
    if (form) {
        form.addEventListener('submit', function (e) {
            const occurrence = document.querySelector('input[name="optionOcurrencia"]:checked').value;
            if (occurrence === '1') { // Masivo
                const dias = document.querySelectorAll('input[name="dias[]"]:checked');
                if (dias.length === 0) {
                    e.preventDefault();
                    alert('Debe seleccionar al menos un día de la semana para la creación masiva.');
                    return;
                }
            }

            if (!confirm('¿Deseas asignar este proceso al proyecto?')) {
                e.preventDefault();
            }
        });
    }

    // Helper functions
    async function refreshProjects(proveedorId) {
        try {
            const response = await fetch(`/setap/tasks/refreshProjectsSelect?proveedor=${proveedorId}`);
            const data = await response.json();
            if (data.success) {
                updateSelect(proyectoSelect, data.projects, 'Seleccionar proyecto...');
            }
        } catch (error) {
            console.error('Error refreshing projects:', error);
        }
    }

    async function refreshProcesses(proveedorId) {
        try {
            const response = await fetch(`/setap/tasks/refreshProcessesSelect?proveedor_id=${proveedorId}`);
            const data = await response.json();
            if (data.success) {
                updateSelect(procesoSelect, data.processes, 'Seleccionar proceso...');
            }
        } catch (error) {
            console.error('Error refreshing processes:', error);
        }
    }

    async function refreshSupervisors(proveedorId, proyectoId) {
        try {
            let url = `/setap/tasks/refreshSupervisorSelect?`;
            if (proveedorId) url += `proveedor_id=${proveedorId}&`;
            if (proyectoId) url += `proyecto_id=${proyectoId}`;

            const response = await fetch(url);
            const data = await response.json();
            if (data.success) {
                updateSelect(supervisorSelect, data.supervisors, 'Seleccionar supervisor...', (item) => `${item.nombre_completo} (${item.nombre_usuario})`);
            }
        } catch (error) {
            console.error('Error refreshing supervisors:', error);
        }
    }

    function updateSelect(selectElement, items, placeholder, labelFn = (item) => item.nombre) {
        if (!selectElement) return;
        let html = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
            html += `<option value="${item.id}">${labelFn(item)}</option>`;
        });
        selectElement.innerHTML = html;
    }
});

function selOpt(e, target) {
    const tabButton = document.querySelector(`#${target}-tab`);
    if (tabButton) {
        tabButton.click();
    }
}
