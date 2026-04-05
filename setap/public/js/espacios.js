document.addEventListener('DOMContentLoaded', function () {
    const proyectoSelect = document.getElementById('proyecto_id');
    const proveedorSelect = document.getElementById('proveedor_id');
    const direccionSelect = document.getElementById('direccion_id');
    const btnNuevaDireccion = document.getElementById('btnNuevaDireccion');
    const btnNuevoEspacio = document.getElementById('btnNuevoEspacio');
    const listadoEspacios = document.getElementById('listadoEspacios');

    // Modal Dirección
    const regionSelect = document.getElementById('region_id');
    const provinciaSelect = document.getElementById('provincia_id');
    const comunaSelect = document.getElementById('comuna_id');
    const formDireccion = document.getElementById('formDireccion');

    // Modal Espacio
    const formEspacio = document.getElementById('formEspacio');
    const espacioPadreSelect = document.getElementById('espacio_padre_id');

    // 1. Filtrado por Proveedor (Admin)
    if (proveedorSelect) {
        proveedorSelect.addEventListener('change', function () {
            const providerId = this.value;
            proyectoSelect.innerHTML = '<option value="">Cargando...</option>';
            direccionSelect.innerHTML = '<option value="">Seleccione un proyecto primero</option>';
            direccionSelect.disabled = true;
            btnNuevoEspacio.disabled = true;
            listadoEspacios.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Seleccione una dirección para ver sus espacios</td></tr>';

            fetch(`/setap/tasks/refreshProjectsSelect?proveedor=${providerId}`)
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        let html = '<option value="">Seleccionar proyecto...</option>';
                        response.projects.forEach(p => {
                            html += `<option value="${p.id}">${p.nombre}</option>`;
                        });
                        proyectoSelect.innerHTML = html;
                    }
                });
        });
    }

    // 2. Cambio de Proyecto -> Cargar Direcciones
    proyectoSelect.addEventListener('change', function () {
        const proyectoId = this.value;
        direccionSelect.innerHTML = '<option value="">Cargando...</option>';
        direccionSelect.disabled = true;
        btnNuevaDireccion.disabled = !proyectoId;
        btnNuevoEspacio.disabled = true; // Siempre deshabilitar al cambiar proyecto
        listadoEspacios.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Seleccione una dirección para ver sus espacios</td></tr>';

        if (!proyectoId) {
            direccionSelect.innerHTML = '<option value="">Seleccione un proyecto primero</option>';
            btnNuevoEspacio.disabled = true;
            return;
        }

        fetch(`/setap/projects/espacios/getDirecciones?proyecto_id=${proyectoId}`)
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    let html = '<option value="">Seleccionar dirección...</option>';
                    response.data.forEach(d => {
                        html += `<option value="${d.id}">${d.calle} ${d.numero || ''} (${d.comuna_nombre})</option>`;
                    });
                    direccionSelect.innerHTML = html;
                    direccionSelect.disabled = false;
                }
            });
    });

    // 3. Cambio de Dirección -> Cargar Espacios
    direccionSelect.addEventListener('change', function () {
        const direccionId = this.value;
        btnNuevoEspacio.disabled = !direccionId;
        if (!direccionId) {
            listadoEspacios.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Seleccione una dirección para ver sus espacios</td></tr>';
            return;
        }
        cargarEspacios(direccionId);
    });

    // Validación extra al hacer clic en "Agregar Espacio"
    btnNuevoEspacio.addEventListener('click', function (e) {
        if (!direccionSelect.value) {
            e.preventDefault();
            e.stopPropagation();
            alert('Debe seleccionar una dirección primero');
            return false;
        }

        // Limpiar el modal para un nuevo espacio
        formEspacio.reset();
        document.getElementById('espacio_id').value = '';
    });

    function cargarEspacios(direccionId) {
        listadoEspacios.innerHTML = '<tr><td colspan="7" class="text-center">Cargando espacios...</td></tr>';
        fetch(`/setap/projects/espacios/getEspacios?direccion_id=${direccionId}`)
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    renderEspacios(response.data);
                    actualizarSelectPadre(response.data);
                }
            });
    }

    function renderEspacios(espacios) {
        if (espacios.length === 0) {
            listadoEspacios.innerHTML = '<tr><td colspan="7" class="text-center">No hay espacios definidos</td></tr>';
            return;
        }

        let html = '';
        espacios.forEach(e => {
            html += `
                <tr>
                    <td>${'—'.repeat(e.nivel)} ${e.nombre}</td>
                    <td>${e.tipo_nombre}</td>
                    <td>${e.codigo || ''}</td>
                    <td>${e.nivel}</td>
                    <td>${e.orden}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarEspacio(${e.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarEspacio(${e.id})"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `;
        });
        listadoEspacios.innerHTML = html;
    }

    function actualizarSelectPadre(espacios) {
        let html = '<option value="">Ninguno (Raíz)</option>';
        espacios.forEach(e => {
            html += `<option value="${e.id}">${' '.repeat(e.nivel * 2)}${e.nombre}</option>`;
        });
        espacioPadreSelect.innerHTML = html;
    }

    // 4. Lógica de Ubicación (Región -> Provincia -> Comuna)
    regionSelect.addEventListener('change', function () {
        const regionId = this.value;
        provinciaSelect.innerHTML = '<option value="">Cargando...</option>';
        comunaSelect.innerHTML = '<option value="">Seleccione provincia</option>';

        if (!regionId) return;

        fetch(`/setap/projects/espacios/getProvincias?region_id=${regionId}`)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="">Seleccionar provincia...</option>';
                data.forEach(p => html += `<option value="${p.id}">${p.nombre}</option>`);
                provinciaSelect.innerHTML = html;
            });
    });

    provinciaSelect.addEventListener('change', function () {
        const provId = this.value;
        comunaSelect.innerHTML = '<option value="">Cargando...</option>';

        if (!provId) return;

        fetch(`/setap/projects/espacios/getComunas?provincia_id=${provId}`)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="">Seleccionar comuna...</option>';
                data.forEach(c => html += `<option value="${c.id}">${c.nombre}</option>`);
                comunaSelect.innerHTML = html;
            });
    });

    // 5. Guardar Dirección
    formDireccion.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('proyecto_id', proyectoSelect.value);

        fetch('/setap/projects/espacios/storeDireccion', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    bootstrap.Modal.getInstance(document.getElementById('modalDireccion')).hide();
                    proyectoSelect.dispatchEvent(new Event('change')); // Recargar direcciones
                } else {
                    alert(res.message);
                }
            });
    });

    // 6. Guardar Espacio
    formEspacio.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('direccion_id', direccionSelect.value);

        fetch('/setap/projects/espacios/storeEspacio', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    bootstrap.Modal.getInstance(document.getElementById('modalEspacio')).hide();
                    cargarEspacios(direccionSelect.value);
                } else {
                    alert(res.message);
                }
            });
    });

    // Funciones globales para botones de la tabla
    window.editarEspacio = function (id) {
        // Implementar carga de datos en modal y mostrarlo
        fetch(`/setap/projects/espacios/getEspacioById?id=${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    document.getElementById('espacio_id').value = res.data.id;
                    document.getElementById('espacio_nombre').value = res.data.nombre;
                    document.getElementById('tipos_espacio_id').value = res.data.tipos_espacio_id;
                    document.getElementById('espacio_padre_id').value = res.data.espacio_padre_id || '';
                    document.getElementById('espacio_codigo').value = res.data.codigo || '';
                    document.getElementById('espacio_descripcion').value = res.data.descripcion || '';
                    document.getElementById('espacio_nivel').value = res.data.nivel;
                    document.getElementById('espacio_orden').value = res.data.orden;
                    new bootstrap.Modal(document.getElementById('modalEspacio')).show();
                }
            });
    };

    window.eliminarEspacio = function (id) {
        if (confirm('¿Está seguro de eliminar este espacio?')) {
            const formData = new FormData();
            formData.append('id', id);
            fetch('/setap/projects/espacios/deleteEspacio', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(res => {
                    alert(res.message);
                    if (res.success) cargarEspacios(direccionSelect.value);
                });
        }
    };
});
