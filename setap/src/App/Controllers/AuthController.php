<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\AuthViewService;
use App\Services\AuthValidationService;
use App\Services\CustomLogger;
use App\Helpers\Security;
use App\Constants\AppConstants;
use App\Traits\CommonValidationsTrait;
use Exception;

/**
 * AuthController - Refactorizado
 * Eliminación de código duplicado y estandarización
 */
class AuthController extends AbstractBaseController
{
    use CommonValidationsTrait;

    private $authService;
    private $authViewService;
    private $authValidationService;

    protected function isAuthExempt(): bool
    {
        return true; // AuthController está exento de autenticación
    }

    protected function initializeController(): void
    {
        $this->authService = new AuthService();
        $this->authViewService = new AuthViewService();
        $this->authValidationService = new AuthValidationService();
    }

    public function showLoginForm()
    {
        return $this->executeWithErrorHandling(function () {
            // Si ya está autenticado, redirigir al home
            if (Security::isAuthenticated()) {
                $this->redirectToHome();
                return;
            }

            // Obtener el error de login si existe y luego limpiarlo
            $error = $_SESSION['login_error'] ?? '';
            unset($_SESSION['login_error']);

            $csrfToken = Security::generateCsrfToken();
            
            CustomLogger::debug("🔐 [LOGIN FORM] Generated CSRF token: " . substr($csrfToken, 0, 10) . "...");
            CustomLogger::debug("🔐 [LOGIN FORM] Session ID: " . session_id());

            // Generar y mostrar la página de login
            echo $this->authViewService->generateLoginPage($error, $csrfToken);
        }, 'showLoginForm');
    }

    public function login()
    {
        return $this->executeWithErrorHandling(function () {
            CustomLogger::debug("🔐 [LOGIN DEBUG] Iniciando proceso de login");
            CustomLogger::debug("🔐 [LOGIN DEBUG] POST data: " . json_encode($_POST));
            CustomLogger::debug("🔐 [LOGIN DEBUG] Session data: " . json_encode($_SESSION));
            
            // Validar datos de entrada
            $validation = $this->authValidationService->validateLoginCredentials($_POST);
            CustomLogger::debug("🔐 [LOGIN DEBUG] Validation result: " . json_encode($validation));
            
            if (!$validation['isValid']) {
                $validationError = $this->authValidationService->formatErrorsForDisplay($validation['errors']);
                CustomLogger::debug("🔐 [LOGIN DEBUG] Validation failed: " . $validationError);
                $_SESSION['login_error'] = $validationError;
                $this->redirectToLogin();
                return;
            }

            $credentials = $validation['data'];
            CustomLogger::debug("🔐 [LOGIN DEBUG] Credentials validated for identifier: " . $credentials['identifier']);

            // Intentar autenticar
            $authResult = $this->authService->authenticate($credentials['identifier'], $credentials['password']);
            CustomLogger::debug("🔐 [LOGIN DEBUG] Auth result: " . json_encode($authResult));
            
            if (!$authResult['success']) {
                // Manejar diferentes tipos de error
                switch ($authResult['error_type']) {
                    case 'USER_NOT_FOUND':
                    case 'INVALID_PASSWORD':
                        // Para errores de autenticación específicos, mostrar mensaje amigable
                        CustomLogger::debug("🔐 [LOGIN DEBUG] Auth error (specific): " . $authResult['friendly_message']);
                        $_SESSION['login_error'] = $authResult['friendly_message'];
                        break;
                        
                    default:
                        // Para todos los demás errores, mostrar error crudo con mensaje de soporte
                        $rawError = $authResult['raw_error'] ?? 'Error desconocido';
                        CustomLogger::debug("🔐 [LOGIN DEBUG] Auth error (raw): " . $rawError);
                        $_SESSION['login_error'] = "Error, informe a soporte: " . $rawError;
                        break;
                }
                
                $this->redirectToLogin();
                return;
            }

            // Iniciar sesión
            CustomLogger::debug("🔐 [LOGIN DEBUG] Starting session for user: " . $authResult['user']['nombre_usuario']);
            if ($this->authService->login($authResult['user'])) {
                CustomLogger::debug("🔐 [LOGIN DEBUG] Session started successfully, redirecting to home");
                $this->redirectToHome();
            } else {
                CustomLogger::debug("🔐 [LOGIN DEBUG] Failed to start session");
                $_SESSION['login_error'] = 'Error al iniciar sesión';
                $this->redirectToLogin();
            }
        }, 'login');
    }

    public function logout()
    {
        return $this->executeWithErrorHandling(function () {
            $this->authService->logout();
            $this->redirectToLogin();
        }, 'logout');
    }
}
