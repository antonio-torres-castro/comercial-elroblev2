/**
 * Sistema Estandarizado de Alertas Bootstrap para SETAP
 * Basado en la implementación mejorada de users/list.php
 * Autor: MiniMax Agent
 * Fecha: 2025-10-13
 */

/**
 * Muestra una alerta Bootstrap elegante con auto-desaparición
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de alerta: success, danger, warning, info
 * @param {number} duration - Duración en millisegundos (0 = no auto-close)
 * @param {boolean} fixed - Si debe ser posición fixed (default: true)
 */
function showAlert(message, type = 'info', duration = 3000, fixed = true) {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer(fixed);
    
    // Limpiar alertas previas para evitar acumulación
    alertContainer.innerHTML = '';
    
    // Iconos específicos para cada tipo
    const icons = {
        'success': '✅',
        'danger': '❌', 
        'warning': '⚠️',
        'info': 'ℹ️',
        'primary': '🔵',
        'secondary': '⚫'
    };
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <strong>${icons[type] || icons.info}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    
    alertContainer.innerHTML = alertHtml;
    
    // Auto-desaparición más robusta
    if (duration > 0) {
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }
        }, duration);
    }
    
    return alertContainer;
}

/**
 * Crea el contenedor de alertas si no existe
 * @param {boolean} fixed - Si debe ser posición fixed
 */
function createAlertContainer(fixed = true) {
    let container = document.getElementById('alertContainer');
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'alertContainer';
        
        if (fixed) {
            container.style.cssText = `
                position: fixed; 
                top: 20px; 
                right: 20px; 
                z-index: 9999; 
                max-width: 400px;
                pointer-events: none;
            `;
            container.style.pointerEvents = 'auto'; // Para permitir clicks en botones
        }
        
        document.body.appendChild(container);
    }
    
    return container;
}

/**
 * Muestra una alerta de confirmación con callbacks
 * @param {string} message - Mensaje de confirmación
 * @param {function} onConfirm - Callback para confirmar
 * @param {function} onCancel - Callback para cancelar (opcional)
 * @param {string} confirmText - Texto del botón confirmar (default: "Confirmar")
 * @param {string} cancelText - Texto del botón cancelar (default: "Cancelar")
 */
function showConfirmAlert(message, onConfirm, onCancel = null, confirmText = 'Confirmar', cancelText = 'Cancelar') {
    const modalId = 'confirmAlertModal_' + Date.now();
    
    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">⚠️ Confirmación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>
                        <button type="button" class="btn btn-danger" id="${modalId}_confirm">${confirmText}</button>
                    </div>
                </div>
            </div>
        </div>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    
    // Event listeners
    document.getElementById(`${modalId}_confirm`).addEventListener('click', () => {
        modal.hide();
        if (typeof onConfirm === 'function') onConfirm();
    });
    
    document.getElementById(modalId).addEventListener('hidden.bs.modal', () => {
        if (typeof onCancel === 'function') onCancel();
        document.getElementById(modalId).remove();
    });
    
    modal.show();
    
    return modal;
}

/**
 * Maneja respuestas JSON de fetch de forma estándar
 * @param {Response} response - Respuesta de fetch
 * @param {string} successMessage - Mensaje personalizado de éxito (opcional)
 * @param {function} onSuccess - Callback adicional para éxito (opcional)
 * @param {function} onError - Callback adicional para error (opcional)
 */
async function handleJsonResponse(response, successMessage = null, onSuccess = null, onError = null) {
    try {
        const data = await response.json();
        
        if (data.success) {
            const message = successMessage || data.message || 'Operación completada exitosamente';
            showAlert(message, 'success');
            
            if (typeof onSuccess === 'function') {
                onSuccess(data);
            }
        } else {
            const message = data.message || 'Ocurrió un error en la operación';
            showAlert(message, 'danger');
            
            if (typeof onError === 'function') {
                onError(data);
            }
        }
        
        return data;
    } catch (error) {
        console.error('Error procesando respuesta JSON:', error);
        showAlert('Error inesperado al procesar la respuesta', 'danger');
        
        if (typeof onError === 'function') {
            onError({ success: false, message: 'Error de parsing JSON', error: error });
        }
        
        throw error;
    }
}

/**
 * Wrapper para fetch con manejo automático de errores y alertas
 * @param {string} url - URL del endpoint
 * @param {Object} options - Opciones de fetch
 * @param {string} successMessage - Mensaje personalizado de éxito
 * @param {function} onSuccess - Callback para éxito
 * @param {function} onError - Callback para error
 */
async function fetchWithAlerts(url, options = {}, successMessage = null, onSuccess = null, onError = null) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await handleJsonResponse(response, successMessage, onSuccess, onError);
    } catch (error) {
        console.error('Error en fetch:', error);
        showAlert('Error de conexión al servidor', 'danger');
        
        if (typeof onError === 'function') {
            onError({ success: false, message: 'Error de conexión', error: error });
        }
        
        throw error;
    }
}

// Export para uso global
window.showAlert = showAlert;
window.showConfirmAlert = showConfirmAlert;
window.handleJsonResponse = handleJsonResponse;
window.fetchWithAlerts = fetchWithAlerts;