document.addEventListener('DOMContentLoaded', function() {
    const proyectoSelect = document.getElementById('proyecto_id');
    const espacioSelect = document.getElementById('espacio_id');
    const espacioHelp = document.getElementById('espacio_help');

    if (!proyectoSelect || !espacioSelect) {
        return;
    }

    function buildDireccionLabel(espacio) {
        const calle = espacio.calle ? String(espacio.calle) : '';
        const numero = espacio.numero ? ` ${espacio.numero}` : '';
        const letra = espacio.letra ? ` ${espacio.letra}` : '';
        const referencia = espacio.referencia ? ` (${espacio.referencia})` : '';
        return `${calle}${numero}${letra}${referencia}`.trim();
    }

    function buildEspacioLabel(espacio) {
        const nombre = espacio.nombre ? String(espacio.nombre) : 'Sin nombre';
        const codigo = espacio.codigo ? ` [${espacio.codigo}]` : '';
        const tipo = espacio.tipo_nombre ? ` - ${espacio.tipo_nombre}` : '';
        const direccion = buildDireccionLabel(espacio);
        if (direccion) {
            return `${direccion} - ${nombre}${codigo}${tipo}`;
        }
        return `${nombre}${codigo}${tipo}`;
    }

    function updateHelp(text) {
        if (espacioHelp) {
            espacioHelp.textContent = text;
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

    async function loadEspacios() {
        const projectId = proyectoSelect.value;
        const selectedValue = espacioSelect.dataset.selected || espacioSelect.value || '';

        if (!projectId) {
            renderEspacios([], '');
            updateHelp('Selecciona un proyecto para cargar espacios.');
            return;
        }

        try {
            const response = await fetch(`/setap/tasks/refreshSpacesSelect?proyecto_id=${encodeURIComponent(projectId)}`);
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

    proyectoSelect.addEventListener('change', loadEspacios);
    loadEspacios();
});

