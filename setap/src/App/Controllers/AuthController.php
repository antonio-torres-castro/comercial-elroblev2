<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Security;

class AuthController
{
    public function showLogin()
    {
        // Si ya está autenticado, redirigir al dashboard
        if (Security::isAuthenticated()) {
            Security::redirect('/dashboard');
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
            <style>
                body { background-color: #f8f9fa; }
                .login-container { max-width: 400px; margin: 100px auto; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="login-container">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <h2 class="text-center mb-4">SETAP</h2>
                            <p class="text-center text-muted">Sistema de Seguimiento de Tareas</p>
                            
                            {$this->showError($error)}
                            
                            <form method="post" action="/login">
                                <input type="hidden" name="csrf_token" value="{$csrfToken}">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Usuario</label>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                           autocomplete="username" autofocus>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                           autocomplete="current-password">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Ingresar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    public function login()
    {
        // Verificar token CSRF
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['login_error'] = 'Token de seguridad inválido.';
            Security::redirect('/login');
        }

        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Por favor, complete todos los campos';
            Security::redirect('/login');
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT u.id, u.nombre_usuario, u.clave_hash, ut.nombre as rol, 
                       p.nombre as nombre_completo, p.rut
                FROM usuarios u 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id 
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE u.nombre_usuario = ? AND u.estado_tipo_id = 2
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['clave_hash'])) {
                // Iniciar sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nombre_usuario'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['rut'] = $user['rut'];
                $_SESSION['last_activity'] = time();

                // Redirigir a la URL guardada o al dashboard por defecto
                $redirectUrl = $_SESSION['redirect_url'] ?? '/dashboard';
                unset($_SESSION['redirect_url']);
                Security::redirect($redirectUrl);
            } else {
                $_SESSION['login_error'] = 'Credenciales incorrectas';
                Security::redirect('/login');
            }
        } catch (\PDOException $e) {
            error_log('Error de login: ' . $e->getMessage());
            $_SESSION['login_error'] = 'Error del sistema. Intente más tarde.';
            Security::redirect('/login');
        }
    }

    public function logout()
    {
        // Destruir sesión
        $_SESSION = [];
        session_destroy();

        // Redirigir al login
        Security::redirect('/login');
    }

    private function showError($error)
    {
        if (empty($error)) return '';

        return <<<HTML
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {$error}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        HTML;
    }
}
