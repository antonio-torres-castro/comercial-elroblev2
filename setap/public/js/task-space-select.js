document.addEventListener('DOMContentLoaded', function() {
    const proyectoSelect = document.getElementById('proyecto_id');
    const direccionSelect = document.getElementById('direccion_id');
    const direccionHelp = document.getElementById('direccion_help');
    const espacioSelect = document.getElementById('espacio_id');
    const espacioHelp = document.getElementById('espacio_help');

    if (!proyectoSelect || !espacioSelect) {
        return;
    }

    function buildDireccionLabel(direccion) {
        const calle = direccion.calle ? String(direccion.calle) : '';
        const numero = direccion.numero ? ` ${direccion.numero}` : '';
        const letra = direccion.letra ? ` ${direccion.letra}` : '';
        const comuna = direccion.comuna ? ` (${direccion.comuna})` : '';
        const provincia = direccion.provincia ? ` (${direccion.provincia})` : '';
        const region = direccion.region ? ` (${direccion.region})` : '';
        return `${calle}${numero}${letra}${comuna}${provincia}${region}`.trim();
    }

    function buildEspacioLabel(espacio) {
        const nombre = espacio.nombre ? String(espacio.nombre) : 'Sin nombre';
        const codigo = espacio.codigo ? ` [${espacio.codigo}]` : '';
        const nivel = espacio.nivel ? ` (Nivel ${espacio.nivel})` : '';
        const orden = espacio.orden ? ` - Orden ${espacio.orden}` : '';
        const tipo = espacio.tipo_nombre ? ` - ${espacio.tipo_nombre}` : '';
        const espacioPadre1 = espacio.espacio_padre1 ? ` - ${espacio.espacio_padre1}` : '';
        const espacioPadre2 = espacio.espacio_padre2 ? ` - ${espacio.espacio_padre2}` : '';
        const espacioPadre3 = espacio.espacio_padre3 ? ` - ${espacio.espacio_padre3}` : '';
        const espacioPadre4 = espacio.espacio_padre4 ? ` - ${espacio.espacio_padre4}` : '';
        const espacioPadre5 = espacio.espacio_padre5 ? ` - ${espacio.espacio_padre5}` : '';
        const espacioPadre6 = espacio.espacio_padre6 ? ` - ${espacio.espacio_padre6}` : '';
        const espacioPadre7 = espacio.espacio_padre7 ? ` - ${espacio.espacio_padre7}` : '';
        return `${nombre}${codigo}${nivel}${orden}${tipo}${espacioPadre1}${espacioPadre2}${espacioPadre3}${espacioPadre4}${espacioPadre5}${espacioPadre6}${espacioPadre7}`;
    }

    function updateHelp(text) {
        if (espacioHelp) {
            espacioHelp.textContent = text;
        }

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

    function renderEspacios(espacios, selectedValue) {
        let optionsHtml = '<option value="">Sin espacio (opcional)</option>';
        espacios.forEach(espacio => {
            optionsHtml += `<option value="${espacio.id}">${buildEspacioLabel(espacio)}</option>`;
        });
        espacioSelect.innerHTML = optionsHtml;

        if (selectedValue) {
            const exists = espacios.some(espacio => String(espacio.id) === String(selectedValue));
            espacioSelect.value = exists ? String(selectedValue) : '';
        } else {
            espacioSelect.value = '';
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

    async function loadEspacios() {
        const direccionId = direccionSelect.value;
        const selectedValue = espacioSelect.dataset.selected || espacioSelect.value || '';

        if (!direccionId) {
            renderEspacios([], '');
            updateHelp('Selecciona una dirección para cargar espacios.');
            return;
        }

        try {
            const response = await fetch(`/setap/tasks/refreshSpacesSelect?direccion_id=${encodeURIComponent(direccionId)}`);
            const data = await response.json();

            if (!data.success) {
                console.error('Error al cargar espacios:', data.message || 'Respuesta no válida');
                renderEspacios([], '');
                updateHelp('No fue posible cargar los espacios.');
                return;
            }

            const espacios = data.espacios || [];
            renderEspacios(espacios, selectedValue);

            if (espacios.length === 0) {
                updateHelp('No hay espacios disponibles para este proyecto.');
            } else {
                updateHelp('Selecciona un espacio si aplica.');
            }
        } catch (error) {
            console.error('Error al cargar espacios:', error);
            renderEspacios([], '');
            updateHelp('No fue posible cargar los espacios.');
        }

        espacioSelect.dataset.selected = '';
    }

    proyectoSelect.addEventListener('change', loadDirecciones);
    loadDirecciones();

    direccionSelect.addEventListener('change', loadEspacios);
    loadEspacios();
});

