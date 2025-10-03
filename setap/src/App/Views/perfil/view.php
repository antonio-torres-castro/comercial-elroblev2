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
                <a class="nav-link text-light active" href="/perfil">
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
                        <a href="/perfil/edit" class="btn btn-sm btn-setap-primary">
                            <i class="bi bi-pencil"></i> Editar Perfil
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i>
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Nombre Completo:</strong></td>
                                        <td><?php echo htmlspecialchars($data['user']['nombre_completo']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Usuario:</strong></td>
                                        <td><?php echo htmlspecialchars($data['user']['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($data['user']['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Rol:</strong></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($data['user']['rol']); ?></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo $data['subtitle']; ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Vista de Perfil</strong><br>
                                    Esta es tu información personal. Puedes editarla usando el botón "Editar Perfil".
                                </div>
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