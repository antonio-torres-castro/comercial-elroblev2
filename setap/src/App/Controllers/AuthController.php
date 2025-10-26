<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\AuthViewService;
use App\Services\AuthValidationService;
use App\Helpers\Security;
use App\Constants\AppConstants;
use App\Helpers\Logger;
use App\Traits\CommonValidationsTrait;
use Exception;
use PhpParser\Node\Name;

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

            // Generar y mostrar la página de login
            echo $this->authViewService->generateLoginPage($error, $csrfToken);
        }, 'showLoginForm');
    }

    public function login()
    {

        return $this->executeWithErrorHandling(function () {
            $errorType = "NA";
            $controllerName = get_class($this);
            // Validar datos de entrada
            $validation = $this->authValidationService->validateLoginCredentials($_POST);

            if (!$validation['isValid']) {
                $validationError = $this->authValidationService->formatErrorsForDisplay($validation['errors']);

                $_SESSION['login_error'] = $validationError;

                Logger::debug("validateLoginCredentials=NotValid: " . $controllerName . "::login:" . $validationError);
                $this->redirectToLogin();
                return;
            }

            $credentials = $validation['data'];

            // Intentar autenticar
            $authResult = $this->authService->authenticate($credentials['identifier'], $credentials['password']);
            $rawError = $authResult['raw_error'] ?? 'Error desconocido';

            if (array_key_exists('error_type', $authResult)) {
                $errorType = $authResult['error_type'] ?? "debia tener";
            }

            if (!$authResult['success']) {
                // Manejar diferentes tipos de error
                switch ($authResult['error_type']) {
                    case 'USER_NOT_FOUND':
                        $_SESSION['login_error'] = "Usuario no encontrado";
                        break;

                    case 'INVALID_PASSWORD':
                        // Para errores de autenticación específicos, mostrar mensaje amigable
                        $_SESSION['login_error'] = $authResult['friendly_message'];
                        break;

                    default:
                        // Para todos los demás errores, mostrar error crudo con mensaje de soporte
                        $_SESSION['login_error'] = "Error, informe a soporte: " . $rawError;
                        break;
                }
                Logger::debug("authenticate=false al login " . $controllerName . "::login:" . $errorType . ":" . $rawError);
                $this->redirectToLogin();
                return;
            }

            // Iniciar sesión
            if ($this->authService->login($authResult['user'])) {
                Logger::debug("Voy al home se ha iniciado sesion " . $controllerName . "::login:");
                $this->redirectToHome();
            } else {
                $_SESSION['login_error'] = 'Error al iniciar sesión';
                Logger::debug("authService->login fallo" . $controllerName . "::login:");
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
