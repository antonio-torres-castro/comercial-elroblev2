document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('createTaskByProcessForm');
    const proveedorSelect = document.getElementById('proveedor_id');
    const proyectoSelect = document.getElementById('proyecto_id');

    const direccionSelect = document.getElementById('direccion_id');
    const direccionHelp = document.getElementById('direccion_help');

    const procesoSelect = document.getElementById('proceso_id');
    const supervisorSelect = document.getElementById('supervisor_id');
    const btnVerTareas = document.getElementById('btnVerTareas');
    const taskList = document.getElementById('taskList');

    function buildDireccionLabel(direccion) {
        const calle = direccion.calle ? String(direccion.calle) : '';
        const numero = direccion.numero ? ` ${direccion.numero}` : '';
        const letra = direccion.letra ? ` ${direccion.letra}` : '';
        const comuna = direccion.comuna ? ` (${direccion.comuna})` : '';
        const provincia = direccion.provincia ? ` (${direccion.provincia})` : '';
        const region = direccion.region ? ` (${direccion.region})` : '';
        return `${calle}${numero}${letra}${comuna}${provincia}${region}`.trim();
    }

    function updateHelp(text) {
        if (direccionHelp) {
            direccionHelp.textContent = text;
        }
    }

    function renderDirecciones(direcciones, selectedValue) {
        let optionsHtml = '<option value="">Sin direccion (opcional)</option>';
        direcciones.forEach(direccion => {
            optionsHtml += `<option value="${direccion.id}">${buildDireccionLabel(direccion)}</option>`;
        });
        direccionSelect.innerHTML = optionsHtml;

        if (selectedValue) {
            const exists = direcciones.some(direccion => String(direccion.id) === String(selectedValue));
            direccionSelect.value = exists ? String(selectedValue) : '';
        } else {
            direccionSelect.value = '';
        }
    }

    async function loadDirecciones() {
        const projectId = proyectoSelect.value;
        const selectedValue = direccionSelect.dataset.selected || direccionSelect.value || '';

        if (!projectId) {
            renderDirecciones([], '');
            updateHelp('Selecciona un proyecto para cargar direcciones.');
            return;
        }

        try {
            const response = await fetch(`/setap/tasks/refreshDireccionSelect?proyecto_id=${encodeURIComponent(projectId)}`);
            const data = await response.json();

            if (!data.success) {
                console.error('Error al cargar direcciones:', data.message || 'Respuesta no válida');
                renderDirecciones([], '');
                updateHelp('No fue posible cargar las direcciones.');
                return;
            }

            const direcciones = data.direcciones || [];
            renderDirecciones(direcciones, selectedValue);

            if (direcciones.length === 0) {
                updateHelp('No hay direcciones disponibles para este proyecto.');
            } else {
                updateHelp('Selecciona una dirección si aplica.');
            }
        } catch (error) {
            console.error('Error al cargar direcciones:', error);
            renderDirecciones([], '');
            updateHelp('No fue posible cargar las direcciones.');
        }

        direccionSelect.dataset.selected = '';
    }

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
            loadDirecciones();
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
        btnVerTareas.addEventListener('click', async function () {

            const processId = procesoSelect.value;
            const direccionId = direccionSelect.value;

            // Estado de carga
            taskList.innerHTML = `
                <div class="text-center p-2">Cargando tareas...</div>
            `;

            try {
                // Cargar tareas y espacios en paralelo
                const [tasksRes, spacesRes] = await Promise.all([
                    fetch(`/setap/tasks/getProcessTasksJson?process_id=${processId}`).then(r => r.json()),
                    direccionId ? fetch(`/setap/tasks/getEspaciosByDireccionJson?direccion_id=${direccionId}`).then(r => r.json()) : Promise.resolve({ success: true, data: { spaces: [] } })
                ]);

                if (!tasksRes.success) throw new Error(tasksRes.message || 'Error al cargar tareas');

                const tasks = tasksRes.tasks || [];
                const spaces = spacesRes?.spaces || [];

                if (tasks.length === 0) {
                    taskList.innerHTML = `<div class="text-center p-2">No hay tareas asociadas</div>`;
                    return;
                }

                // =========================
                // Helpers
                // =========================
                const getPrioridadTexto = (valor) => {
                    switch (parseInt(valor)) {
                        case 0: return 'Baja';
                        case 3: return 'Normal';
                        case 5: return 'Media';
                        case 7: return 'Alta';
                        case 10: return 'Crítica';
                        default: return 'Desconocida';
                    }
                };

                const getPrioridadBadge = (valor) => {
                    switch (parseInt(valor)) {
                        case 0: return 'bg-success';
                        case 3: return 'bg-primary';
                        case 5: return 'bg-info';
                        case 7: return 'bg-warning';
                        case 10: return 'bg-danger';
                        default: return 'bg-secondary';
                    }
                };

                // =========================
                // Construcción tabla
                // =========================
                let totalHH = 0;
                let html = `
                    <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-center">HH</th>
                                    <th class="text-center">Prioridad</th>
                                    <th class="text-center">Espacio</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                tasks.forEach((task, index) => {
                    const hh = parseFloat(task.hh) || 0;
                    totalHH += hh;

                    html += `
                        <tr>
                            <td>
                                ${task.tarea_nombre}
                                <input type="hidden" name="tasks_process[${index}][tarea_id]" value="${task.tarea_id}">
                                <input type="hidden" name="tasks_process[${index}][hh]" value="${hh}">
                                <input type="hidden" name="tasks_process[${index}][prioridad]" value="${task.prioridad}">
                            </td>
                            <td class="text-center">${hh}</td>
                            <td class="text-center">
                                <span class="badge ${getPrioridadBadge(task.prioridad)}">
                                    ${getPrioridadTexto(task.prioridad)}
                                </span>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="tasks_process[${index}][espacio_id]">
                                    <option value="">Seleccionar espacio...</option>
                                    ${spaces.map(space => `
                                        <option value="${space.id}">${space.nombre}</option>
                                    `).join('')}
                                </select>
                            </td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">${totalHH}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;

                taskList.innerHTML = html;

            } catch (error) {
                console.error('Error al cargar datos del modal:', error);
                taskList.innerHTML = `<div class="text-danger text-center p-2">${error.message}</div>`;
            }
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
