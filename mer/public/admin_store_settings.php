<?php
require_once __DIR__ . '/../src/advanced_store_functions.php';

if (!isset($store) || !isset($products)) {
    echo '<div class="alert alert-warning">Error: Datos de la tienda no disponibles.</div>';
    return;
}

$storeId = (int)$store['id'];
$configStats = getConfigStats($storeId);
$permissionLevels = getPermissionLevels();
$configCategories = getConfigCategories();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'configure_transbank':
                $transbankConfig = [
                    'commerce_code' => $_POST['transbank_commerce_code'] ?? '',
                    'api_key' => $_POST['transbank_api_key'] ?? '',
                    'environment' => $_POST['transbank_environment'] ?? 'Integration',
                    'private_key_path' => $_POST['transbank_private_key_path'] ?? '',
                    'public_cert_path' => $_POST['transbank_public_cert_path'] ?? '',
                    'bank_cert_path' => $_POST['transbank_bank_cert_path'] ?? ''
                ];
                
                if (configureTransbank($storeId, $transbankConfig)) {
                    $successMessage = 'Configuración de Transbank actualizada correctamente.';
                } else {
                    $errorMessage = 'Error al configurar Transbank. Verifique los datos.';
                }
                break;
                
            case 'update_language':
                $langConfig = [
                    'default_language' => $_POST['default_language'] ?? 'es',
                    'timezone' => $_POST['timezone'] ?? 'America/Santiago',
                    'date_format' => $_POST['date_format'] ?? 'd/m/Y',
                    'currency' => $_POST['currency'] ?? 'CLP',
                    'decimal_separator' => $_POST['decimal_separator'] ?? ',',
                    'thousands_separator' => $_POST['thousands_separator'] ?? '.'
                ];
                
                if (setLanguageConfig($storeId, $langConfig)) {
                    $successMessage = 'Configuración de idioma actualizada.';
                } else {
                    $errorMessage = 'Error al actualizar configuración de idioma.';
                }
                break;
                
            case 'update_permissions':
                $permissions = [];
                foreach ($permissionLevels as $level => $data) {
                    $permissions[$level] = [
                        'enabled' => isset($_POST["perm_{$level}_enabled"])
                    ];
                }
                
                if (setStorePermissions($storeId, $permissions)) {
                    $successMessage = 'Permisos actualizados correctamente.';
                } else {
                    $errorMessage = 'Error al actualizar permisos.';
                }
                break;
                
            case 'update_notifications':
                $notifications = [
                    'email_enabled' => isset($_POST['email_enabled']) ? 'true' : 'false',
                    'email_admin' => $_POST['email_admin'] ?? '',
                    'email_sales' => $_POST['email_sales'] ?? '',
                    'sms_enabled' => isset($_POST['sms_enabled']) ? 'true' : 'false',
                    'order_confirmations' => isset($_POST['order_confirmations']) ? 'true' : 'false',
                    'delivery_updates' => isset($_POST['delivery_updates']) ? 'true' : 'false'
                ];
                
                if (setNotificationConfig($storeId, $notifications)) {
                    $successMessage = 'Configuración de notificaciones actualizada.';
                } else {
                    $errorMessage = 'Error al actualizar notificaciones.';
                }
                break;
                
            case 'configure_setap':
                $setapConfig = [
                    'enabled' => isset($_POST['setap_enabled']),
                    'api_endpoint' => $_POST['setap_api_endpoint'] ?? '',
                    'api_key' => $_POST['setap_api_key'] ?? ''
                ];
                
                if (configureSETAP($storeId, $setapConfig)) {
                    $successMessage = 'Configuración de SETAP actualizada.';
                } else {
                    $errorMessage = 'Error al configurar SETAP.';
                }
                break;
        }
        
        // Actualizar estadísticas después de cambios
        $configStats = getConfigStats($storeId);
        $permissionLevels = getPermissionLevels();
        
    } catch (Exception $e) {
        $errorMessage = 'Error: ' . $e->getMessage();
    }
}

// Obtener configuraciones actuales
$currentLanguage = getLanguageConfig($storeId);
$currentPermissions = getStorePermissions($storeId);
$currentNotifications = getNotificationConfig($storeId);
$currentIntegrations = getStoreIntegrations($storeId);
$transbankStatus = isTransbankConfigured($storeId);
$configValidation = validateStoreConfig($storeId);
?>

<div class="settings-container">
    <!-- Header con estadísticas -->
    <div class="settings-header">
        <h2 class="settings-title">⚙️ Configuración de Tienda</h2>
        <div class="store-info">
            <strong><?= htmlspecialchars($store['name']) ?></strong>
            <span class="store-id">ID: <?= $storeId ?></span>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <i class="icon-check"></i>
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger">
            <i class="icon-error"></i>
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Validación de configuración -->
    <?php if (!$configValidation['valid'] || !empty($configValidation['warnings'])): ?>
        <div class="config-validation">
            <?php if (!$configValidation['valid']): ?>
                <div class="validation-issues">
                    <h4>🚨 Problemas de Configuración</h4>
                    <ul>
                        <?php foreach ($configValidation['issues'] as $issue): ?>
                            <li><?= htmlspecialchars($issue) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($configValidation['warnings'])): ?>
                <div class="validation-warnings">
                    <h4>⚠️ Advertencias</h4>
                    <ul>
                        <?php foreach ($configValidation['warnings'] as $warning): ?>
                            <li><?= htmlspecialchars($warning) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Dashboard de estadísticas -->
    <div class="stats-dashboard">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">⚙️</div>
                <div class="stat-content">
                    <div class="stat-number"><?= $configStats['total_configs'] ?></div>
                    <div class="stat-label">Configuraciones</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💳</div>
                <div class="stat-content">
                    <div class="stat-number"><?= $configStats['payment_methods'] ?></div>
                    <div class="stat-label">Métodos de Pago</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔐</div>
                <div class="stat-content">
                    <div class="stat-number"><?= $configStats['permissions'] ?></div>
                    <div class="stat-label">Permisos</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔗</div>
                <div class="stat-content">
                    <div class="stat-number"><?= $configStats['active_integrations'] ?>/<?= $configStats['total_integrations'] ?></div>
                    <div class="stat-label">Integraciones Activas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de configuración -->
    <div class="settings-tabs">
        <nav class="tab-nav">
            <button class="tab-button active" data-tab="payment">💳 Métodos de Pago</button>
            <button class="tab-button" data-tab="language">🌍 Idioma</button>
            <button class="tab-button" data-tab="permissions">🔐 Permisos</button>
            <button class="tab-button" data-tab="integrations">🔗 Integraciones</button>
            <button class="tab-button" data-tab="notifications">📧 Notificaciones</button>
        </nav>

        <!-- Pestaña: Métodos de Pago -->
        <div class="tab-content active" id="payment-tab">
            <div class="section-card">
                <h3>Transbank WebPay Plus</h3>
                
                <?php if ($transbankStatus['configured']): ?>
                    <div class="integration-status success">
                        <div class="status-indicator active"></div>
                        <div class="status-text">
                            <strong>✅ Transbank Configurado</strong>
                            <div class="status-details">
                                Ambiente: <code><?= htmlspecialchars($transbankStatus['environment']) ?></code> |
                                Código: <code><?= htmlspecialchars(substr($transbankStatus['commerce_code'], 0, 6) . '****') ?></code>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="integration-status warning">
                        <div class="status-indicator inactive"></div>
                        <div class="status-text">
                            <strong>⚠️ Transbank No Configurado</strong>
                            <div class="status-details"><?= htmlspecialchars($transbankStatus['message']) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="config-form">
                    <input type="hidden" name="action" value="configure_transbank">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transbank_commerce_code">Código de Comercio *</label>
                            <input type="text" id="transbank_commerce_code" name="transbank_commerce_code" 
                                   value="<?= htmlspecialchars(getStoreConfig($storeId, 'payment_methods', 'transbank_commerce_code')['config_value'] ?? '') ?>"
                                   placeholder="597012345678" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="transbank_api_key">API Key *</label>
                            <input type="password" id="transbank_api_key" name="transbank_api_key" 
                                   value="<?= htmlspecialchars(getStoreConfig($storeId, 'payment_methods', 'transbank_api_key')['config_value'] ?? '') ?>"
                                   placeholder="579B532A7440BB69C69EF3E687B7714A" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transbank_environment">Ambiente</label>
                            <select id="transbank_environment" name="transbank_environment">
                                <option value="Integration" <?= (getStoreConfig($storeId, 'payment_methods', 'transbank_environment')['config_value'] ?? 'Integration') === 'Integration' ? 'selected' : '' ?>>
                                    Integration (Pruebas)
                                </option>
                                <option value="Production" <?= (getStoreConfig($storeId, 'payment_methods', 'transbank_environment')['config_value'] ?? '') === 'Production' ? 'selected' : '' ?>>
                                    Production (Producción)
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Certificados (Solo Producción)</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="transbank_private_key_path">Ruta Clave Privada</label>
                                <input type="text" id="transbank_private_key_path" name="transbank_private_key_path" 
                                       value="<?= htmlspecialchars(getStoreConfig($storeId, 'payment_methods', 'transbank_private_key_path')['config_value'] ?? '') ?>"
                                       placeholder="/ruta/a/clave_privada.key">
                            </div>
                            
                            <div class="form-group">
                                <label for="transbank_public_cert_path">Ruta Certificado Público</label>
                                <input type="text" id="transbank_public_cert_path" name="transbank_public_cert_path" 
                                       value="<?= htmlspecialchars(getStoreConfig($storeId, 'payment_methods', 'transbank_public_cert_path')['config_value'] ?? '') ?>"
                                       placeholder="/ruta/a/certificado_publico.crt">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-save"></i>
                            Guardar Configuración Transbank
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pestaña: Idioma -->
        <div class="tab-content" id="language-tab">
            <div class="section-card">
                <h3>Configuración de Idioma y Regionalización</h3>
                
                <form method="POST" class="config-form">
                    <input type="hidden" name="action" value="update_language">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="default_language">Idioma Predeterminado</label>
                            <select id="default_language" name="default_language">
                                <option value="es" <?= $currentLanguage['default_language'] === 'es' ? 'selected' : '' ?>>
                                    Español (Chile)
                                </option>
                                <option value="en" <?= $currentLanguage['default_language'] === 'en' ? 'selected' : '' ?>>
                                    English
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Zona Horaria</label>
                            <select id="timezone" name="timezone">
                                <option value="America/Santiago" <?= $currentLanguage['timezone'] === 'America/Santiago' ? 'selected' : '' ?>>
                                    America/Santiago (GMT-3)
                                </option>
                                <option value="America/Valparaiso" <?= $currentLanguage['timezone'] === 'America/Valparaiso' ? 'selected' : '' ?>>
                                    America/Valparaiso (GMT-3)
                                </option>
                                <option value="UTC" <?= $currentLanguage['timezone'] === 'UTC' ? 'selected' : '' ?>>
                                    UTC (GMT+0)
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_format">Formato de Fecha</label>
                            <select id="date_format" name="date_format">
                                <option value="d/m/Y" <?= $currentLanguage['date_format'] === 'd/m/Y' ? 'selected' : '' ?>>
                                    DD/MM/AAAA (30/11/2025)
                                </option>
                                <option value="Y-m-d" <?= $currentLanguage['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>
                                    AAAA-MM-DD (2025-11-30)
                                </option>
                                <option value="m/d/Y" <?= $currentLanguage['date_format'] === 'm/d/Y' ? 'selected' : '' ?>>
                                    MM/DD/AAAA (11/30/2025)
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="currency">Moneda</label>
                            <select id="currency" name="currency">
                                <option value="CLP" <?= $currentLanguage['currency'] === 'CLP' ? 'selected' : '' ?>>
                                    Peso Chileno (CLP)
                                </option>
                                <option value="USD" <?= $currentLanguage['currency'] === 'USD' ? 'selected' : '' ?>>
                                    Dólar Estadounidense (USD)
                                </option>
                                <option value="EUR" <?= $currentLanguage['currency'] === 'EUR' ? 'selected' : '' ?>>
                                    Euro (EUR)
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="decimal_separator">Separador Decimal</label>
                            <select id="decimal_separator" name="decimal_separator">
                                <option value="," <?= $currentLanguage['decimal_separator'] === ',' ? 'selected' : '' ?>>
                                    Coma (,)
                                </option>
                                <option value="." <?= $currentLanguage['decimal_separator'] === '.' ? 'selected' : '' ?>>
                                    Punto (.)
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="thousands_separator">Separador de Miles</label>
                            <select id="thousands_separator" name="thousands_separator">
                                <option value="." <?= $currentLanguage['thousands_separator'] === '.' ? 'selected' : '' ?>>
                                    Punto (.)
                                </option>
                                <option value="," <?= $currentLanguage['thousands_separator'] === ',' ? 'selected' : '' ?>>
                                    Coma (,)
                                </option>
                                <option value=" " <?= $currentLanguage['thousands_separator'] === ' ' ? 'selected' : '' ?>>
                                    Espacio ( )
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-save"></i>
                            Guardar Configuración de Idioma
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pestaña: Permisos -->
        <div class="tab-content" id="permissions-tab">
            <div class="section-card">
                <h3>Niveles de Permisos de Usuario</h3>
                <p class="section-description">
                    Configure qué niveles de permisos están habilitados para esta tienda.
                </p>
                
                <form method="POST" class="config-form">
                    <input type="hidden" name="action" value="update_permissions">
                    
                    <div class="permissions-grid">
                        <?php foreach ($currentPermissions as $level => $perm): ?>
                            <div class="permission-card">
                                <div class="permission-header">
                                    <h4><?= htmlspecialchars($perm['name']) ?></h4>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="perm_<?= $level ?>_enabled" 
                                               <?= $perm['enabled'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <p class="permission-description"><?= htmlspecialchars($perm['description']) ?></p>
                                <div class="permission-details">
                                    <strong>Permisos:</strong>
                                    <ul>
                                        <?php foreach ($perm['permissions'] as $permission): ?>
                                            <?php if ($permission === 'all'): ?>
                                                <li>Acceso completo al sistema</li>
                                            <?php else: ?>
                                                <li><?= htmlspecialchars(ucwords(str_replace('_', ' ', $permission))) ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-save"></i>
                            Guardar Permisos
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pestaña: Integraciones -->
        <div class="tab-content" id="integrations-tab">
            <div class="section-card">
                <h3>Integraciones con Servicios Externos</h3>
                
                <!-- Transbank -->
                <div class="integration-item">
                    <div class="integration-header">
                        <div class="integration-icon">💳</div>
                        <div class="integration-info">
                            <h4>Transbank</h4>
                            <p>Procesamiento de pagos con tarjetas</p>
                        </div>
                        <div class="integration-status">
                            <?php if ($currentIntegrations['transbank']['enabled']): ?>
                                <span class="status-badge active">Activo</span>
                            <?php else: ?>
                                <span class="status-badge inactive">Inactivo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- SETAP -->
                <div class="integration-item">
                    <div class="integration-header">
                        <div class="integration-icon">🏢</div>
                        <div class="integration-info">
                            <h4>SETAP</h4>
                            <p>Sistema de gestión SETAP (próximamente)</p>
                        </div>
                        <div class="integration-status">
                            <span class="status-badge planned">Planificado</span>
                        </div>
                    </div>
                    
                    <form method="POST" class="config-form integration-form">
                        <input type="hidden" name="action" value="configure_setap">
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="setap_enabled" 
                                       <?= $currentIntegrations['setap']['enabled'] ? 'checked' : '' ?>>
                                Habilitar integración SETAP
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="setap_api_endpoint">Endpoint API</label>
                                <input type="url" id="setap_api_endpoint" name="setap_api_endpoint" 
                                       value="<?= htmlspecialchars(getStoreConfig($storeId, 'integrations', 'setap_api_endpoint')['config_value'] ?? '') ?>"
                                       placeholder="https://api.setap.cl/v1">
                            </div>
                            
                            <div class="form-group">
                                <label for="setap_api_key">API Key</label>
                                <input type="password" id="setap_api_key" name="setap_api_key" 
                                       value="<?= htmlspecialchars(getStoreConfig($storeId, 'integrations', 'setap_api_key')['config_value'] ?? '') ?>"
                                       placeholder="sk_setap_...">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-secondary">
                                <i class="icon-save"></i>
                                Configurar SETAP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pestaña: Notificaciones -->
        <div class="tab-content" id="notifications-tab">
            <div class="section-card">
                <h3>Configuración de Notificaciones</h3>
                
                <form method="POST" class="config-form">
                    <input type="hidden" name="action" value="update_notifications">
                    
                    <!-- Email -->
                    <div class="notification-section">
                        <h4>📧 Notificaciones por Email</h4>
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="email_enabled" 
                                       <?= $currentNotifications['email_enabled'] === 'true' ? 'checked' : '' ?>>
                                Habilitar notificaciones por email
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email_admin">Email de Administrador</label>
                                <input type="email" id="email_admin" name="email_admin" 
                                       value="<?= htmlspecialchars($currentNotifications['email_admin']) ?>"
                                       placeholder="admin@tienda.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="email_sales">Email de Ventas</label>
                                <input type="email" id="email_sales" name="email_sales" 
                                       value="<?= htmlspecialchars($currentNotifications['email_sales']) ?>"
                                       placeholder="ventas@tienda.com">
                            </div>
                        </div>
                    </div>
                    
                    <!-- SMS -->
                    <div class="notification-section">
                        <h4>📱 Notificaciones por SMS</h4>
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="sms_enabled" 
                                       <?= $currentNotifications['sms_enabled'] === 'true' ? 'checked' : '' ?>>
                                Habilitar notificaciones por SMS
                            </label>
                        </div>
                    </div>
                    
                    <!-- Tipos de notificaciones -->
                    <div class="notification-section">
                        <h4>🔔 Tipos de Notificaciones</h4>
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="order_confirmations" 
                                       <?= $currentNotifications['order_confirmations'] === 'true' ? 'checked' : '' ?>>
                                Confirmaciones de pedidos
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="delivery_updates" 
                                       <?= $currentNotifications['delivery_updates'] === 'true' ? 'checked' : '' ?>>
                                Actualizaciones de entrega
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-save"></i>
                            Guardar Configuración de Notificaciones
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript para manejo de pestañas
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Remover clases activas
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Activar pestaña seleccionada
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
    
    // Mostrar/ocultar campos de certificado según ambiente
    const environmentSelect = document.getElementById('transbank_environment');
    const certFields = document.querySelectorAll('#payment-tab .form-section');
    
    if (environmentSelect) {
        function toggleCertFields() {
            const isProduction = environmentSelect.value === 'Production';
            certFields.forEach(section => {
                section.style.display = isProduction ? 'block' : 'none';
            });
        }
        
        environmentSelect.addEventListener('change', toggleCertFields);
        toggleCertFields(); // Ejecutar al cargar
    }
});
</script>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.settings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
}

.settings-title {
    margin: 0;
    font-size: 2rem;
    font-weight: 600;
}

.store-info {
    text-align: right;
}

.store-info strong {
    display: block;
    font-size: 1.1rem;
}

.store-id {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Alertas */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Validación de configuración */
.config-validation {
    display: grid;
    gap: 15px;
    margin-bottom: 20px;
}

.validation-issues, .validation-warnings {
    padding: 15px;
    border-radius: 8px;
}

.validation-issues {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
}

.validation-warnings {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
}

.validation-issues h4, .validation-warnings h4 {
    margin: 0 0 10px 0;
    color: #721c24;
}

.validation-warnings h4 {
    color: #856404;
}

.validation-issues ul, .validation-warnings ul {
    margin: 0;
    padding-left: 20px;
}

/* Dashboard de estadísticas */
.stats-dashboard {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    margin-top: 5px;
}

/* Sistema de pestañas */
.settings-tabs {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    overflow-x: auto;
}

.tab-button {
    padding: 15px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    color: #666;
    transition: all 0.2s;
    white-space: nowrap;
    border-bottom: 3px solid transparent;
}

.tab-button:hover {
    background: #e9ecef;
    color: #333;
}

.tab-button.active {
    color: #667eea;
    background: white;
    border-bottom-color: #667eea;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

/* Secciones de configuración */
.section-card {
    margin-bottom: 20px;
}

.section-card h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.3rem;
}

.section-description {
    color: #666;
    margin-bottom: 20px;
}

/* Formularios */
.config-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.form-section h4 {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 1rem;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Botones */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

/* Checkboxes y toggles */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: normal;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #667eea;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

/* Estados de integración */
.integration-status {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-indicator.active {
    background: #28a745;
}

.status-indicator.inactive {
    background: #dc3545;
}

.status-text {
    flex: 1;
}

.status-details {
    font-size: 0.9rem;
    color: #666;
    margin-top: 5px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.planned {
    background: #fff3cd;
    color: #856404;
}

/* Grid de permisos */
.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.permission-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    background: #f8f9fa;
}

.permission-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.permission-header h4 {
    margin: 0;
    color: #333;
}

.permission-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.permission-details {
    font-size: 0.9rem;
}

.permission-details ul {
    margin: 5px 0 0 20px;
    padding: 0;
}

.permission-details li {
    margin-bottom: 3px;
}

/* Items de integración */
.integration-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.integration-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.integration-icon {
    font-size: 2rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
}

.integration-info {
    flex: 1;
}

.integration-info h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.integration-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.integration-form {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Secciones de notificación */
.notification-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.notification-section:last-child {
    border-bottom: none;
}

.notification-section h4 {
    margin: 0 0 15px 0;
    color: #333;
}

/* Responsive */
@media (max-width: 768px) {
    .settings-container {
        padding: 10px;
    }
    
    .settings-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .tab-nav {
        flex-wrap: wrap;
    }
    
    .tab-button {
        flex: 1;
        min-width: 120px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .permissions-grid {
        grid-template-columns: 1fr;
    }
}

/* Iconos */
.icon-check:before { content: "✓"; }
.icon-error:before { content: "✗"; }
.icon-save:before { content: "💾"; }
</style>