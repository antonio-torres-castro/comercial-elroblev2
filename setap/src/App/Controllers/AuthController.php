<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\AuthViewService;
use App\Services\AuthValidationService;
use App\Helpers\Security;
use App\Constants\AppConstants;
use Exception;

class AuthController extends BaseController
{
    private $authService;
    private $authViewService;
    private $authValidationService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->authViewService = new AuthViewService();
        $this->authValidationService = new AuthValidationService();
    }

    public function showLoginForm()
    {
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
    }

    public function login()
    {
        try {
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
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $_SESSION['login_error'] = AppConstants::ERROR_INTERNAL_SERVER;
            $this->redirectToLogin();
        }
    }

    public function logout()
    {
        try {
            $this->authService->logout();
            $this->redirectToLogin();
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            $this->redirectToLogin();
        }
    }


}
