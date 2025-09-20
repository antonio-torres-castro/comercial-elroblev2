<?php

namespace App\Controllers;

use App\Models\User;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Helpers\Security;

class UserController
{
    private $userModel;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        (new PermissionMiddleware('admin'))->handle();
        $this->userModel = new User();
    }

    public function index()
    {
        $users = $this->userModel->getAll();
        // Cargar vista de listado de usuarios
        $this->view('users/list', ['users' => $users]);
    }

    public function create()
    {
        // Cargar vista de creación de usuario
        $this->view('users/create');
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $personaData = [
                'rut' => Security::sanitizeInput($_POST['rut']),
                'nombre' => Security::sanitizeInput($_POST['nombre']),
                'telefono' => Security::sanitizeInput($_POST['telefono']),
                'direccion' => Security::sanitizeInput($_POST['direccion'])
            ];

            $usuarioData = [
                'usuario_tipo_id' => (int)$_POST['usuario_tipo_id'],
                'email' => Security::sanitizeInput($_POST['email']),
                'nombre_usuario' => Security::sanitizeInput($_POST['nombre_usuario']),
                'password' => $_POST['password']
            ];

            if ($this->userModel->create($personaData, $usuarioData)) {
                Security::redirect('/users?success=Usuario creado correctamente');
            } else {
                Security::redirect('/users/create?error=Error al crear usuario');
            }
        }
    }

    private function view($view, $data = [])
    {
        extract($data);
        require __DIR__ . "/../Views/{$view}.php";
    }
}
