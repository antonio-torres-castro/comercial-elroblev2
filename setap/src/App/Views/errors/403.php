<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="bi bi-shield-exclamation"></i>
                            Acceso Denegado
                        </h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="bi bi-shield-x text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h5>Sin Permisos</h5>
                        <p class="text-muted mb-4">
                            No tienes los permisos necesarios para acceder a esta sección.
                            Si crees que esto es un error, contacta al administrador del sistema.
                        </p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/home" class="btn btn-setap-primary">
                                <i class="bi bi-house"></i> Ir al Home
                            </a>
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>