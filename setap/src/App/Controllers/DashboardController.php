<?php

namespace App\Controllers;

use App\Middlewares\AuthMiddleware;

class DashboardController
{
    public function __construct()
    {
        // Aplicar el middleware de autenticación
        (new AuthMiddleware())->handle();
    }

    public function index()
    {
        // Vista simple del dashboard
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Dashboard - SETAP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                <div class="container">
                    <a class="navbar-brand" href="dashboard">SETAP</a>
                    <div class="navbar-nav ms-auto">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle"></i> {$_SESSION['nombre_completo']}
                        </span>
                        <a class="btn btn-outline-light btn-sm" href="logout">
                            <i class="bi bi-box-arrow-right"></i> Salir
                        </a>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">Menú</div>
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action active">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="bi bi-folder"></i> Proyectos
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="bi bi-list-task"></i> Tareas
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="bi bi-people"></i> Clientes
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="bi bi-person"></i> Usuarios
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Dashboard</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6>Bienvenido, {$_SESSION['nombre_completo']}</h6>
                                    <p class="mb-0">Rol: {$_SESSION['user_role']} | RUT: {$_SESSION['rut']}</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card text-white bg-primary mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">Proyectos</h5>
                                                <p class="card-text">0 activos</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-white bg-success mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">Tareas</h5>
                                                <p class="card-text">0 pendientes</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-white bg-warning mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">Clientes</h5>
                                                <p class="card-text">0 activos</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
}
