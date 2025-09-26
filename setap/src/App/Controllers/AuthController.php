<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Helpers\Security;
use Exception;

class AuthController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLoginForm()
    {
        // Si ya está autenticado, redirigir al dashboard
        if (Security::isAuthenticated()) {
            Security::redirect('/dashboard');
            return;
        }

        // Obtener el error de login si existe y luego limpiarlo
        $error = $_SESSION['login_error'] ?? '';
        unset($_SESSION['login_error']);

        $csrfToken = Security::generateCsrfToken();

        // Vista simple de login
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login - SETAP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
            <style>
                body { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                }
                .login-card {
                    border: none;
                    border-radius: 15px;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                }
                .login-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 15px 15px 0 0;
                    padding: 2rem;
                    text-align: center;
                    color: white;
                }
                .btn-login {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    border-radius: 25px;
                    padding: 0.75rem 2rem;
                    font-weight: 600;
                    transition: transform 0.2s;
                }
                .btn-login:hover {
                    transform: translateY(-2px);
                    background: linear-gradient(135deg, #5a6bd8 0%, #6a4190 100%);
                }
                .form-control {
                    border-radius: 10px;
                    border: 2px solid #e9ecef;
                    padding: 0.75rem 1rem;
                }
                .form-control:focus {
                    border-color: #667eea;
                    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="card login-card">
                            <div class="login-header">
                                <i class="bi bi-building" style="font-size: 3rem;"></i>
                                <h3 class="mt-2 mb-0">SETAP</h3>
                                <p class="mb-0">Sistema de Gestión</p>
                            </div>
                            <div class="card-body p-4">
                                {$this->getErrorAlert($error)}
                                
                                <form method="POST" action="/login">
                                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                                    
                                    <div class="mb-3">
                                        <label for="identifier" class="form-label">
                                            <i class="bi bi-person"></i> Usuario o Email
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="identifier" 
                                               name="identifier" 
                                               placeholder="Ingrese su usuario o email"
                                               required 
                                               autocomplete="username">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="password" class="form-label">
                                            <i class="bi bi-lock"></i> Contraseña
                                        </label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               placeholder="Ingrese su contraseña"
                                               required 
                                               autocomplete="current-password">
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-login">
                                            <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-center text-muted py-3">
                                <small>&copy; 2025 Comercial El Roble - SETAP</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
HTML;
    }

    public function login()
    {
        try {
            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['login_error'] = 'Token de seguridad inválido';
                Security::redirect('/login');
                return;
            }

            // Obtener credenciales
            $identifier = Security::sanitizeInput($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($identifier) || empty($password)) {
                $_SESSION['login_error'] = 'Usuario y contraseña son requeridos';
                Security::redirect('/login');
                return;
            }

            // Intentar autenticar
            $userData = $this->authService->authenticate($identifier, $password);

            if (!$userData) {
                $_SESSION['login_error'] = 'Credenciales incorrectas';
                Security::redirect('/login');
                return;
            }

            // Iniciar sesión
            if ($this->authService->login($userData)) {
                Security::redirect('/dashboard');
            } else {
                $_SESSION['login_error'] = 'Error al iniciar sesión';
                Security::redirect('/login');
            }

        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $_SESSION['login_error'] = 'Error interno del servidor';
            Security::redirect('/login');
        }
    }

    public function logout()
    {
        try {
            $this->authService->logout();
            Security::redirect('/login');
            
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            Security::redirect('/login');
        }
    }

    private function getErrorAlert(string $error): string
    {
        if (empty($error)) {
            return '';
        }

        return <<<HTML
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {$error}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
HTML;
    }
}