   // Validación y envío
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createTaskForm');
            const createBtn = document.getElementById('createBtn');

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!confirm('¿Crear esta tarea?')) {
                    e.preventDefault();
                    return;
                }

                createBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Enviando...';
                createBtn.disabled = true;
            });

            initializeFormHandlers();

            handlerCategory();
            handlerFilterCategory();
            handlerEditFormCategory();

            refreshTasksTable();
        });

        /**
         * Inicializar manejador de formulario
         */
        function initializeFormHandlers() {
            // Formulario edición
            const editForm = document.getElementById('edit-task-form');
            if (editForm) {
                editForm.addEventListener('submit', handleEditSubmit);
            }
        }

        /**
         * Actualizar tabla de tareas
         */
        async function refreshTasksTable() {
            try {
                const idCategoria = document.getElementById('filtro_tarea_categoria_id').value;
                const idProveedor = document.getElementById('filtro_proveedor_id').value;

                const params = new URLSearchParams({
                    categoria: idCategoria,
                    proveedor: idProveedor
                });

                const response = await fetch(`/setap/tasks/refreshTasksTable?${params.toString()}`);
                const data = await response.json();

                if (data.success) {
                    updateTasksTable(data.tareas);
                } else {
                    console.error('Error al cargar tareas:', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        /**
         * Actualizar contenido de la tabla de tareas
         */
        function updateTasksTable(tareas) {
            const tbody = document.getElementById('tasks-tbody');
            let html = '';

            tareas.forEach(tarea => {
                html += `
        <tr>
            <td id="tdNombre${tarea.id}">${tarea.nombre}</td>
            <td id="tdDescripcion${tarea.id}">${tarea.descripcion}</td>
            <td id="tdCategoriaId${tarea.id}" hidden>${tarea.tarea_categoria_id}</td>
            <td id="tdIndustriaId${tarea.id}" hidden>${tarea.industria_id}</td>
            <td id="tdEstadoTipoId${tarea.id}" hidden>${tarea.estado_tipo_id}</td>
            <td id="tdEstado${tarea.id}">
                <span class="badge bg-${tarea.estado_tipo_id == 2 ? 'success' : 'secondary'}">
                    ${tarea.estado}
                </span>
            </td>
            <td id="tdAccionId${tarea.id}">
                <button id="tdBtnEdit${tarea.id}}" class="btn btn-sm btn-outline-primary" onclick="editTask(${tarea.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button id="tdBtnDel${tarea.id}" class="btn btn-sm btn-outline-danger" onclick="deleteTask(${tarea.id})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
            });

            tbody.innerHTML = html;
        }

        /**
         * Editar task
         */
        async function editTask(id) {
            try {
                // Por simplicidad, usaremos los datos de la tabla
                const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));

                // Configurar el formulario modal
                document.getElementById('edit-task-id').value = id;
                document.getElementById('editTareaNombre').value = document.getElementById('tdNombre' + id).textContent;
                document.getElementById('editTareaDescripcion').value = document.getElementById('tdDescripcion' + id).textContent;
                document.getElementById('editEstadoTipoId').value = document.getElementById('tdEstadoTipoId' + id).textContent;
                document.getElementById('editIndustriaId').value = document.getElementById('tdIndustriaId' + id).textContent;
                document.getElementById('editCategoriaId').value = document.getElementById('tdCategoriaId' + id).textContent;

                modal.show();
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error al cargar datos de tarea', 'error');
            }
        }

        /**
         * Manejar envío del formulario de edición
         */
        async function handleEditSubmit(e) {
            e.preventDefault();

            try {
                const formData = new FormData(e.target);
                const response = await fetch('/setap/tasks/updatet', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
                    modal.hide();
                    refreshTasksTable();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error al actualizar feriado', 'error');
            }
        }

        /**
         * Eliminar tarea
         */
        async function deleteTask(id) {
            if (!confirm('¿Está seguro de que desea eliminar esta tarea?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', getCsrfToken());
                formData.append('id', id);

                const response = await fetch('/setap/tasks/deleteT', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    refreshTasksTable();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error al eliminar tarea', 'error');
            }
        }

        /**
         * Utilidades
         */
        function handlerCategory(){
            const filtroIndustriaSelect = document.getElementById('industria_id');
            const categoriaSelect = document.getElementById('tarea_categoria_id');
            const allCategoriaOptions = Array.from(categoriaSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                parentId: opt.dataset.parentId || ''
            }));

            if (filtroIndustriaSelect && categoriaSelect) {
                filtroIndustriaSelect.addEventListener('change', async () => {
                    const industriaId = filtroIndustriaSelect.value;
                    categoriaSelect.innerHTML = '<option value="">Sin categoria</option>';
                    if (!industriaId) {
                        allCategoriaOptions.forEach(opt => {
                            if (opt.value !== '') {
                                categoriaSelect.add(new Option(opt.text, opt.value));
                            }
                        });
                        return;
                    }
                    try {
                        const serviceBaseRoute = '/setap/tasks';
                        const response = await fetch(`${serviceBaseRoute}/category-parent-by-industry?industria_id=${industriaId}`);
                        const json = await response.json();
                        const categories = json.data || [];
                        categories.forEach(cat => categoriaSelect.add(new Option((cat.parent1_name ? cat.parent1_name + ' | ' : '') + (cat.parent2_name ? cat.parent2_name + ' | ' : '') + cat.nombre, cat.id)));
                    } catch (e) {
                        console.error('Error al cargar categorias:', e);
                    }
                });
            }
        }
        function handlerFilterCategory(){
            const filtroIndustriaSelect = document.getElementById('filtro_categoria_industria_id');
            const categoriaSelect = document.getElementById('filtro_tarea_categoria_id');
            const allCategoriaOptions = Array.from(categoriaSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                parentId: opt.dataset.parentId || ''
            }));

            if (filtroIndustriaSelect && categoriaSelect) {
                filtroIndustriaSelect.addEventListener('change', async () => {
                    const industriaId = filtroIndustriaSelect.value;
                    categoriaSelect.innerHTML = '<option value="">Sin categoria</option>';
                    if (!industriaId) {
                        allCategoriaOptions.forEach(opt => {
                            if (opt.value !== '') {
                                categoriaSelect.add(new Option(opt.text, opt.value));
                            }
                        });
                        return;
                    }
                    try {
                        const serviceBaseRoute = '/setap/tasks';
                        const response = await fetch(`${serviceBaseRoute}/category-parent-by-industry?industria_id=${industriaId}`);
                        const json = await response.json();
                        const categories = json.data || [];
                        categories.forEach(cat => categoriaSelect.add(new Option((cat.parent1_name ? cat.parent1_name + ' | ' : '') + (cat.parent2_name ? cat.parent2_name + ' | ' : '') + cat.nombre, cat.id)));
                    } catch (e) {
                        console.error('Error al cargar categorias:', e);
                    }
                });
            }
        }
        function handlerEditFormCategory(){
            const filtroIndustriaSelect = document.getElementById('editIndustriaId');
            const categoriaSelect = document.getElementById('editCategoriaId');
            const allCategoriaOptions = Array.from(categoriaSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                parentId: opt.dataset.parentId || ''
            }));

            if (filtroIndustriaSelect && categoriaSelect) {
                filtroIndustriaSelect.addEventListener('change', async () => {
                    const industriaId = filtroIndustriaSelect.value;
                    categoriaSelect.innerHTML = '<option value="">Sin categoria</option>';
                    if (!industriaId) {
                        allCategoriaOptions.forEach(opt => {
                            if (opt.value !== '') {
                                categoriaSelect.add(new Option(opt.text, opt.value));
                            }
                        });
                        return;
                    }
                    try {
                        const serviceBaseRoute = '/setap/tasks';
                        const response = await fetch(`${serviceBaseRoute}/category-parent-by-industry?industria_id=${industriaId}`);
                        const json = await response.json();
                        const categories = json.data || [];
                        categories.forEach(cat => categoriaSelect.add(new Option((cat.parent1_name ? cat.parent1_name + ' | ' : '') + (cat.parent2_name ? cat.parent2_name + ' | ' : '') + cat.nombre, cat.id)));
                    } catch (e) {
                        console.error('Error al cargar categorias:', e);
                    }
                });
            }
        }

        /**
         * Obtener token CSRF
         */
        function getCsrfToken() {
            const tokenInput = document.querySelector('input[name="csrf_token"]');
            return tokenInput ? tokenInput.value : '';
        }