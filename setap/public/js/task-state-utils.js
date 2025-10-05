/**
 * SETAP Task State Validation Utilities (GAP 5)
 * 
 * Utilidades JavaScript para manejar las validaciones de estado de tareas
 * según las reglas de negocio implementadas en el backend.
 */

window.SETAP = window.SETAP || {};
window.SETAP.TaskStates = {
    
    // Definición de estados con sus propiedades visuales
    STATES: {
        1: { name: 'Creado', class: 'bg-warning text-dark', icon: 'bi-plus-circle' },
        2: { name: 'Activo', class: 'bg-success', icon: 'bi-check-circle' },
        3: { name: 'Inactivo', class: 'bg-secondary', icon: 'bi-pause-circle' },
        5: { name: 'Iniciado', class: 'bg-primary', icon: 'bi-play-circle' },
        6: { name: 'Terminado', class: 'bg-info text-dark', icon: 'bi-stop-circle' },
        7: { name: 'Rechazado', class: 'bg-danger', icon: 'bi-x-circle' },
        8: { name: 'Aprobado', class: 'bg-dark', icon: 'bi-check-circle-fill' }
    },

    /**
     * Cargar transiciones válidas para una tarea
     * @param {number} taskId - ID de la tarea
     * @returns {Promise<Object>} - Respuesta con transiciones válidas
     */
    async loadValidTransitions(taskId) {
        try {
            const response = await fetch(`/tasks/valid-transitions?task_id=${taskId}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error cargando transiciones:', error);
            throw error;
        }
    },

    /**
     * Verificar si una tarea puede ejecutarse
     * @param {number} taskId - ID de la tarea
     * @returns {Promise<Object>} - Respuesta con validación de ejecución
     */
    async checkExecutable(taskId) {
        try {
            const response = await fetch(`/tasks/check-executable?task_id=${taskId}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error verificando ejecutabilidad:', error);
            throw error;
        }
    },

    /**
     * Cambiar estado de una tarea
     * @param {number} taskId - ID de la tarea
     * @param {number} newStateId - Nuevo estado
     * @param {string} reason - Motivo del cambio (opcional)
     * @returns {Promise<Object>} - Respuesta del cambio de estado
     */
    async changeState(taskId, newStateId, reason = '') {
        try {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('new_state', newStateId);
            formData.append('reason', reason);

            const response = await fetch('/tasks/change-state', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error cambiando estado:', error);
            throw error;
        }
    },

    /**
     * Poblar dropdown de estado con transiciones válidas
     * @param {HTMLSelectElement} selectElement - Elemento select
     * @param {number} taskId - ID de la tarea
     * @param {number} currentStateId - Estado actual
     */
    async populateStateDropdown(selectElement, taskId, currentStateId) {
        try {
            // Mostrar loading
            selectElement.innerHTML = '<option value="">Cargando...</option>';
            selectElement.disabled = true;

            // Agregar opción del estado actual
            const currentState = this.STATES[currentStateId];
            selectElement.innerHTML = `
                <option value="${currentStateId}" selected>
                    ${currentState ? currentState.name : 'Estado Actual'} (Actual)
                </option>
            `;

            // Cargar transiciones válidas
            const data = await this.loadValidTransitions(taskId);
            
            if (data.transitions && data.transitions.length > 0) {
                data.transitions.forEach(transition => {
                    const option = document.createElement('option');
                    option.value = transition.id;
                    option.textContent = transition.nombre;
                    option.title = transition.descripcion || '';
                    selectElement.appendChild(option);
                });
            }

            selectElement.disabled = false;
            return data;

        } catch (error) {
            selectElement.innerHTML = `
                <option value="${currentStateId}" selected>
                    ${this.STATES[currentStateId]?.name || 'Estado Actual'} (Error al cargar)
                </option>
            `;
            selectElement.disabled = false;
            throw error;
        }
    },

    /**
     * Crear dropdown de transiciones para la tabla de lista
     * @param {number} taskId - ID de la tarea
     * @param {number} currentStateId - Estado actual
     * @returns {Promise<HTMLElement>} - Elemento dropdown creado
     */
    async createTransitionDropdown(taskId, currentStateId) {
        const dropdown = document.createElement('div');
        dropdown.className = 'dropdown d-inline-block ms-1';
        dropdown.innerHTML = `
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                id="stateDropdown${taskId}" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-arrow-repeat"></i>
            </button>
            <ul class="dropdown-menu" id="stateMenu${taskId}">
                <li><span class="dropdown-item-text text-muted">Cargando...</span></li>
            </ul>
        `;

        // Cargar transiciones cuando se hace clic
        const button = dropdown.querySelector('button');
        button.addEventListener('click', async () => {
            await this.loadTransitionsForDropdown(taskId, currentStateId);
        });

        return dropdown;
    },

    /**
     * Cargar transiciones para un dropdown específico
     * @param {number} taskId - ID de la tarea
     * @param {number} currentStateId - Estado actual
     */
    async loadTransitionsForDropdown(taskId, currentStateId) {
        const menu = document.getElementById(`stateMenu${taskId}`);
        
        try {
            const data = await this.loadValidTransitions(taskId);
            
            if (data.transitions && data.transitions.length > 0) {
                menu.innerHTML = '';
                data.transitions.forEach(transition => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <a class="dropdown-item" href="#" onclick="SETAP.TaskStates.showChangeStateModal(${taskId}, ${transition.id}, '${transition.nombre}')">
                            <i class="bi bi-arrow-right"></i> ${transition.nombre}
                        </a>
                    `;
                    menu.appendChild(li);
                });
            } else {
                menu.innerHTML = '<li><span class="dropdown-item-text text-muted">Sin transiciones disponibles</span></li>';
            }
        } catch (error) {
            menu.innerHTML = '<li><span class="dropdown-item-text text-danger">Error al cargar</span></li>';
        }
    },

    /**
     * Mostrar modal para cambiar estado
     * @param {number} taskId - ID de la tarea
     * @param {number} newStateId - Nuevo estado
     * @param {string} newStateName - Nombre del nuevo estado
     */
    showChangeStateModal(taskId, newStateId, newStateName) {
        // Buscar el modal o crearlo si no existe
        let modal = document.getElementById('changeStateModal');
        if (!modal) {
            modal = this.createChangeStateModal();
            document.body.appendChild(modal);
        }

        // Obtener nombre de la tarea
        const taskNameElement = document.querySelector(`#task-row-${taskId} .fw-bold`);
        const taskName = taskNameElement ? taskNameElement.textContent : 'Tarea seleccionada';
        
        // Poblar modal
        document.getElementById('changeStateTaskId').value = taskId;
        document.getElementById('changeStateNewState').value = newStateId;
        document.getElementById('changeStateTaskName').textContent = taskName;
        document.getElementById('changeStateNewStateName').textContent = newStateName;
        document.getElementById('changeStateReason').value = '';
        
        // Mostrar modal
        new bootstrap.Modal(modal).show();
    },

    /**
     * Crear modal para cambio de estado
     * @returns {HTMLElement} - Elemento modal creado
     */
    createChangeStateModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'changeStateModal';
        modal.tabIndex = -1;
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cambiar Estado de Tarea</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="changeStateForm">
                            <input type="hidden" id="changeStateTaskId" name="task_id">
                            <input type="hidden" id="changeStateNewState" name="new_state">
                            
                            <div class="mb-3">
                                <label class="form-label">Tarea:</label>
                                <div id="changeStateTaskName" class="fw-bold text-primary"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nuevo Estado:</label>
                                <div id="changeStateNewStateName" class="fw-bold text-success"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="changeStateReason" class="form-label">Motivo del cambio (opcional):</label>
                                <textarea class="form-control" id="changeStateReason" name="reason" rows="3" 
                                    placeholder="Describe el motivo del cambio de estado..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmChangeState">Cambiar Estado</button>
                    </div>
                </div>
            </div>
        `;

        // Agregar event listener para confirmar cambio
        modal.querySelector('#confirmChangeState').addEventListener('click', async () => {
            await this.executeStateChange();
        });

        return modal;
    },

    /**
     * Ejecutar cambio de estado
     */
    async executeStateChange() {
        const formData = new FormData(document.getElementById('changeStateForm'));
        const taskId = formData.get('task_id');
        const newStateId = formData.get('new_state');
        const reason = formData.get('reason');

        try {
            const result = await this.changeState(taskId, newStateId, reason);
            
            if (result.success) {
                // Actualizar badge de estado en la tabla
                this.updateStatusBadge(taskId, newStateId);
                
                // Mostrar mensaje de éxito
                this.showAlert('success', result.message);
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('changeStateModal'));
                if (modal) {
                    modal.hide();
                }
            } else {
                this.showAlert('danger', 'Error: ' + result.message);
            }
        } catch (error) {
            this.showAlert('danger', 'Error de conexión al servidor');
        }
    },

    /**
     * Actualizar badge de estado en la tabla
     * @param {number} taskId - ID de la tarea
     * @param {number} stateId - Nuevo estado
     */
    updateStatusBadge(taskId, stateId) {
        const badge = document.getElementById(`status-badge-${taskId}`);
        if (badge && this.STATES[stateId]) {
            const state = this.STATES[stateId];
            badge.className = `badge ${state.class}`;
            badge.textContent = state.name;
        }
    },

    /**
     * Mostrar alerta temporal
     * @param {string} type - Tipo de alerta (success, danger, warning, info)
     * @param {string} message - Mensaje a mostrar
     */
    showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.maxWidth = '400px';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    },

    /**
     * Validar permisos para eliminación según estado
     * @param {number} stateId - Estado de la tarea
     * @param {string} userRole - Rol del usuario
     * @returns {Object} - Resultado de validación
     */
    validateDeletePermissions(stateId, userRole) {
        if (stateId === 8) { // Estado aprobado
            if (!['admin', 'planner'].includes(userRole)) {
                return {
                    valid: false,
                    message: 'Solo usuarios Admin y Planner pueden eliminar tareas aprobadas.'
                };
            }
        }
        
        return { valid: true, message: '' };
    }
};

// Event listeners globales cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips para elementos de estado
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach(element => {
        new bootstrap.Tooltip(element);
    });
});