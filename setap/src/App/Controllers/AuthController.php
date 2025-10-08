<?php
namespace App\Controllers;

use App\Services\AuthService;
use App\Services\AuthViewService;
use App\Services\AuthValidationService;
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
        return $this->executeWithErrorHandling(function() {
            // Si ya está autenticado, redirigir al home
            if (Security::isAuthenticated()) {
                $this->redirectToHome();
                return;
            }

            // Obtener el error de login si existe y luego limpiarlo
            $error = $_SESSION['login_error'] ?? '';
            unset($_SESSION['login_error']);

            $csrfToken = Security::generateCsrfToken();

            // Generar y mostrar la página de login
            echo $this->authViewService->generateLoginPage($error, $csrfToken);
        }, 'showLoginForm');
    }

    public function login()
    {
        return $this->executeWithErrorHandling(function() {
            // Validar datos de entrada
            $validation = $this->authValidationService->validateLoginCredentials($_POST);
            if (!$validation['isValid']) {
                $_SESSION['login_error'] = $this->authValidationService->formatErrorsForDisplay($validation['errors']);
                $this->redirectToLogin();
                return;
            }

            $credentials = $validation['data'];

            // Intentar autenticar
            $userData = $this->authService->authenticate($credentials['identifier'], $credentials['password']);
            if (!$userData) {
                $_SESSION['login_error'] = 'Credenciales incorrectas';
                $this->redirectToLogin();
                return;
            }

            // Iniciar sesión
            if ($this->authService->login($userData)) {
                $this->redirectToHome();
            } else {
                $_SESSION['login_error'] = 'Error al iniciar sesión';
                $this->redirectToLogin();
            }
        }, 'login');
    }

    public function logout()
    {
        return $this->executeWithErrorHandling(function() {
            $this->authService->logout();
            $this->redirectToLogin();
        }, 'logout');
    }
}
