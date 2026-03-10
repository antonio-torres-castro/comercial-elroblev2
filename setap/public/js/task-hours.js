document.addEventListener('DOMContentLoaded', function () {
    const estadoTipoSelect = document.getElementById('estado_tipo_id');
    if (estadoTipoSelect) {
        new Choices(estadoTipoSelect, {
            removeItemButton: true,
            searchEnabled: true,
            placeholderValue: 'Seleccionar...',
            noResultsText: 'Sin resultados',
            itemSelectText: 'Seleccionar',
            shouldSort: false,
        });
    }
});

document.addEventListener('click', function (event) {
    const button = event.target.closest('.btn-personas');
    if (!button) return;

    const inicio = button.getAttribute('data-periodo-inicio') || '';
    const fin = button.getAttribute('data-periodo-fin') || '';
    const baseQuery = button.getAttribute('data-query-base') || '';

    const params = new URLSearchParams(baseQuery);
    if (inicio) params.set('fecha_inicio', inicio);
    if (fin) params.set('fecha_fin', fin);

    const modalEl = document.getElementById('personasModal');
    const modalBody = document.getElementById('personasModalBody');
    if (!modalEl || !modalBody) return;

    modalBody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Cargando...</td></tr>';

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    fetch(`/setap/tasks/personas-periodo?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (!data || !data.success) {
                modalBody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Error al cargar</td></tr>';
                return;
            }

            const personas = Array.isArray(data.personas) ? data.personas : [];
            if (personas.length === 0) {
                modalBody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Sin colaboradores</td></tr>';
                return;
            }

            modalBody.innerHTML = personas.map(persona => {
                const nombre = persona.nombre || '';
                const usuario = persona.usuario || '';
                return `<tr><td>${nombre}</td><td>${usuario}</td></tr>`;
            }).join('');
        })
        .catch(() => {
            modalBody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Error de conexión</td></tr>';
        });
});