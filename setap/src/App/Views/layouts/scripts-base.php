<?php
/**
 * Scripts básicos requeridos por toda la aplicación
 * Incluye: Bootstrap Bundle
 */

// Prevenir carga múltiple
if (!defined('SETAP_BASE_SCRIPTS_LOADED')) {
    define('SETAP_BASE_SCRIPTS_LOADED', true);
?>
    <!-- Scripts Base de SETAP -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema Estandarizado de Alertas SETAP -->
    <script src="/src/App/Views/layouts/alert-system.js"></script>
    
    <script>
        // Variables globales para evitar conflictos
        window.SETAP = window.SETAP || {};
        window.SETAP.scriptsLoaded = window.SETAP.scriptsLoaded || {};
        window.SETAP.scriptsLoaded.bootstrap = true;
        
        // Función helper para confirmar logout
        window.SETAP.confirmLogout = function(e) {
            if (!confirm('¿Está seguro que desea cerrar sesión?')) {
                e.preventDefault();
            }
        };
        
        // Inicialización básica
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar confirmación de logout
            const logoutLink = document.querySelector('a[href="/logout"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', window.SETAP.confirmLogout);
            }
            
            console.log('SETAP Base Scripts cargados correctamente');
        });
    </script>
<?php
}
?>