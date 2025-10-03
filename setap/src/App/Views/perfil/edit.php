<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?> - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-setap">
        <div class="container">
            <a class="navbar-brand" href="/home">
                <i class="bi bi-grid-3x3-gap"></i> SETAP
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-light" href="/home">
                    <i class="bi bi-house"></i> Home
                </a>
                <a class="nav-link text-light" href="/perfil">
                    <i class="bi bi-person-circle"></i> Mi Perfil
                </a>
                <a class="nav-link text-light" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/perfil" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo $data['subtitle']; ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="bi bi-tools"></i>
                                    <strong>Formulario en Construcción</strong><br>
                                    El formulario de edición de perfil está en desarrollo. Próximamente podrás actualizar tu información personal.
                                </div>

                                <form method="POST" class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre Completo</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['user']['nombre_completo']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Usuario</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['user']['username']); ?>" disabled>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($data['user']['email']); ?>" disabled>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-setap-primary" disabled>
                                            <i class="bi bi-save"></i> Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>