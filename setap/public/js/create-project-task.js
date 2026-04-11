let taskChoicesInstance = null;

// Validación y envío
document.addEventListener('DOMContentLoaded', function() {

    // Mostrar/ocultar campos de nueva tarea
    const tareaSelect = document.getElementById('tarea_id');
    const nuevaTareaFields = document.getElementById('nueva-tarea-fields');
    const nuevaTareaNombre = document.getElementById('nueva_tarea_nombre');
    const proveedorSelect = document.getElementById('proveedor_id');
    const categoriaSelect = document.getElementById('tarea_categoria_id');
    const proyectoSelect = document.getElementById('proyecto_id');

    taskChoicesInstance = new Choices(tareaSelect, {
                                shouldSort: false,
                                searchPlaceholderValue: "Buscar tarea...",
                                itemSelectText: "",
                                searchFields: ['label', 'value'],
                                placeholder: true,
                                allowHTML: true
                                });


    tareaSelect.addEventListener('change', function() {
        if (this.value === 'nueva') {
            nuevaTareaFields.style.display = 'block';
            nuevaTareaNombre.setAttribute('required', 'required');
        } else {
            nuevaTareaFields.style.display = 'none';
            nuevaTareaNombre.removeAttribute('required');
        }
    });
    tareaSelect.dispatchEvent(new Event('change'));

    if (proveedorSelect) {
        proveedorSelect.addEventListener('change', function() {
            refreshTasksAndProjects();
        });
    }

    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            refreshTasksSelect();
        });
    }

    if (proyectoSelect) {
        refreshTasksAndProjects();
    }


    const form = document.getElementById('createTaskForm');
    const createBtn = document.getElementById('createBtn');

    const fechaInicioMasivo = document.getElementById('fecha_inicio_masivo');
    const fechaFinMasivo = document.getElementById('fecha_fin_masivo');
    const fechaEspecificaInicio = document.getElementById('fecha_especifica_inicio');
    const fechaEspecificaFin = document.getElementById('fecha_especifica_fin');
    const fechaInicioRango = document.getElementById('fecha_inicio_rango');
    const fechaFinRango = document.getElementById('fecha_fin_rango');
    const fechaInicioIntervalo = document.getElementById('fecha_inicio_intervalo');
    const fechaFinIntervalo = document.getElementById('fecha_fin_intervalo');

    function validateDates(fechaInicio, fechaFin) {
        if (fechaInicio.value && fechaFin.value) {
            const inicio = new Date(fechaInicio.value);
            const fin = new Date(fechaFin.value);
            if (fin < inicio) {
                fechaFin.setCustomValidity('Fecha fin menor que fecha de inicio');
                return false;
            } else {
                fechaFin.setCustomValidity('');
            }
        }
        return true;
    }

    function validateDatesGetway() {
        var retorno;
        const currentFormData = new FormData(form);
        const tipoOcurrencia = currentFormData.get('optionOcurrencia');
        if (tipoOcurrencia == '1') {
            retorno = validateDates(fechaInicioMasivo, fechaFinMasivo);
        }
        if (tipoOcurrencia == '2') {
            retorno = validateDates(fechaEspecificaInicio, fechaEspecificaFin);
        }
        if (tipoOcurrencia == '3') {
            retorno = validateDates(fechaInicioRango, fechaFinRango);
        }
        if (tipoOcurrencia == '4') {
            retorno = validateDates(fechaInicioIntervalo, fechaFinIntervalo);
        }
        return retorno;
    }

    ///Fecha recurrente
    fechaInicioMasivo.addEventListener('change', () => {
        fechaFinMasivo.min = fechaInicioMasivo.value;
        validateDates(fechaInicioMasivo, fechaFinMasivo);
    });
    fechaFinMasivo.addEventListener('change', () => {
        validateDates(fechaInicioMasivo, fechaFinMasivo);
    });
    ///Fecha Especifica
    fechaEspecificaInicio.addEventListener('change', () => {
        fechaEspecificaFin.min = fechaEspecificaInicio.value;
        validateDates(fechaEspecificaInicio, fechaEspecificaFin);
    });
    fechaEspecificaFin.addEventListener('change', () => {
        validateDates(fechaEspecificaInicio, fechaEspecificaFin);
    });
    ///Rango Fechas
    fechaInicioRango.addEventListener('change', () => {
        fechaFinRango.min = fechaInicioRango.value;
        validateDates(fechaInicioRango, fechaFinRango);
    });
    fechaFinRango.addEventListener('change', () => {
        validateDates(fechaInicioRango, fechaFinRango);
    });

    ///Intervalo Fechas
    fechaInicioIntervalo.addEventListener('change', () => {
        fechaFinIntervalo.min = fechaInicioIntervalo.value;
        validateDates(fechaInicioIntervalo, fechaFinIntervalo);
    });
    fechaFinIntervalo.addEventListener('change', () => {
        validateDates(fechaInicioIntervalo, fechaFinIntervalo);
    });
            // Envío del formulario
    form.addEventListener('submit', function(e) {
        if (!validateDatesGetway()) {
            e.preventDefault();
            alert('Corrige las fechas antes de enviar.');
            return;
        }

        if (!confirm('¿Deseas crear esta tarea?')) {
            e.preventDefault();
            return;
        }

        createBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Enviando...';
        createBtn.disabled = true;
    });
});

function selOpt(evt, nameIor) {
    var i, tablinks;
    tabpane = document.getElementsByName("tabpane");
    for (i = 0; i < tabpane.length; i++) {
        tabpane[i].style.display = "none";
        tabpane[i].className = tabpane[i].className.replace(" show active", "");
    }
    tablinks = document.getElementsByName("button-tab");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    document.getElementById(nameIor + '-tab').className += " active";

    document.getElementById(nameIor).style.display = "";
    document.getElementById(nameIor).className += " show active";
}

function openTab(evt, nameTab) {
    var i, iors;
    iors = document.getElementsByName("optionOcurrencia");
    for (i = 0; i < iors.length; i++) {
        iors[i].checked = false;
        if (iors[i].id == 'ior' + nameTab) {
            iors[i].checked = true;
        }
    }
    evt.currentTarget.className.replace(" active", "");
    evt.currentTarget.className += " active";

    document.getElementById(nameTab.toLowerCase()).style.display = "";
    document.getElementById(nameTab.toLowerCase()).className.replace(" show active", "");
    document.getElementById(nameTab.toLowerCase()).className += " show active";
}

async function refreshTasksAndProjects() {
    await Promise.all([refreshTasksSelect(), refreshProjectsSelect(), refreshSupervisorSelect()]);
}

async function refreshTasksSelect() {
    try {
        const tareaSelect = document.getElementById('tarea_id');
        const proveedorSelect = document.getElementById('proveedor_id');
        const categoriaSelect = document.getElementById('tarea_categoria_id');

        if (!tareaSelect || !proveedorSelect || !categoriaSelect) {
            return;
        }

        const params = new URLSearchParams({
            proveedor: proveedorSelect.value || '',
            categoria: categoriaSelect.value || ''
        });

        const response = await fetch(`/setap/tasks/refreshTaskSelect?${params.toString()}`);
        const data = await response.json();

        if (!data.success) {
            console.error('Error al cargar tareas:', data.message);
            return;
        }

        updateTaskSelectOptions(data.tareas || []);
    } catch (error) {
        console.error('Error al cargar tareas:', error);
    }
}

function updateTaskSelectOptions(tasks) {
    const tareaSelect = document.getElementById('tarea_id');
    if (!tareaSelect || !taskChoicesInstance) {
        return;
    }

    const previousValue = tareaSelect.value;

    const choicesData = [
        { value: '', label: 'Seleccionar tarea...', selected: true },
        { value: 'nueva', label: '➕ Crear nueva tarea' }
    ];

    tasks.forEach(task => {
        const descripcion = task.descripcion ? ` - ${task.descripcion}` : '';
        choicesData.push({
            value: String(task.id),
            label: `${task.nombre}${descripcion}`
        });
    });

    taskChoicesInstance.clearChoices();
    taskChoicesInstance.setChoices(choicesData, 'value', 'label', true);

    const exists = choicesData.some(option => option.value === previousValue);
    if (exists) {
        taskChoicesInstance.setChoiceByValue(previousValue);
    } else if (choicesData.length > 0) {
        taskChoicesInstance.setChoiceByValue(choicesData[0].value);
    }

    tareaSelect.dispatchEvent(new Event('change'));
}

async function refreshProjectsSelect() {
    try {
        const proveedorSelect = document.getElementById('proveedor_id');
        const proyectoSelect = document.getElementById('proyecto_id');

        if (!proveedorSelect || !proyectoSelect) {
            return;
        }

        const params = new URLSearchParams({
            proveedor: proveedorSelect.value || ''
        });

        const response = await fetch(`/setap/tasks/refreshProjectsSelect?${params.toString()}`);
        const data = await response.json();

        if (!data.success) {
            console.error('Error al cargar proyectos:', data.message);
            return;
        }

        updateProjectSelectOptions(data.projects || []);
    } catch (error) {
        console.error('Error al cargar proyectos:', error);
    }
}

function updateProjectSelectOptions(projects) {
    const proyectoSelect = document.getElementById('proyecto_id');
    if (!proyectoSelect) {
        return;
    }

    const previousValue = proyectoSelect.value;
    let optionsHtml = '<option value="">Seleccionar proyecto...</option>';

    projects.forEach(project => {
        optionsHtml += `<option value="${project.id}">${project.nombre}</option>`;
    });

    proyectoSelect.innerHTML = optionsHtml;

    const stillExists = projects.some(project => String(project.id) === previousValue);
    if (stillExists) {
        proyectoSelect.value = previousValue;
    }

    proyectoSelect.dispatchEvent(new Event('change'));
}

async function refreshSupervisorSelect() {
    try {
        const proveedorSelect = document.getElementById('proveedor_id');
        const supervisorSelect = document.getElementById('supervisor_id');

        if (!proveedorSelect || !supervisorSelect) {
            return;
        }

        const params = new URLSearchParams({
            proveedor_id: proveedorSelect.value || ''
        });

        const response = await fetch(`/setap/tasks/refreshSupervisorSelect?${params.toString()}`);
        const data = await response.json();

        if (!data.success) {
            console.error('Error al cargar supervisores:', data.message);
            return;
        }

        updateSupervisorSelectOptions(data.supervisors || []);
    } catch (error) {
        console.error('Error al cargar supervisores:', error);
    }
}

function updateSupervisorSelectOptions(supervisors) {
    const supervisorSelect = document.getElementById('supervisor_id');
    if (!supervisorSelect) {
        return;
    }

    const previousValue = supervisorSelect.value;
    let optionsHtml = '';

    if (supervisors.length > 1) {
        optionsHtml = '<option value="">Sin supervisor</option>';
    }

    supervisors.forEach(supervisor => {
        optionsHtml += `<option value="${supervisor.id}">${supervisor.nombre_completo + ' (' + supervisor.nombre_usuario + ')'}</option>`;
    });

    supervisorSelect.innerHTML = optionsHtml;

    const stillExists = supervisors.some(supervisor => String(supervisor.id) === previousValue);
    if (stillExists) {
        supervisorSelect.value = previousValue;
    }
}

