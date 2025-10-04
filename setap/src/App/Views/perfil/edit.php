<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?> - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/setap-theme.css">
</head>
<body>
    <?php
    use App\Helpers\Security; ?>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <main class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h1><?php echo $data['title']; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/perfil" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($data['errors']) && !empty($data['errors'])): ?>
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle"></i> Se encontraron errores:</h6>
                        <ul class="mb-0">
                            <?php foreach ($data['errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-3" id="profileForm">
                    <?php Security::renderCsrfField(); ?>
                    
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($data['user']['nombre_completo'] ?? ''); ?>" 
                               required maxlength="100">
                        <div class="form-text">Tu nombre completo como aparecerá en el sistema</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($data['user']['email'] ?? ''); ?>" 
                               required maxlength="150">
                        <div class="form-text">Tu dirección de correo electrónico</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($data['user']['telefono'] ?? ''); ?>" 
                               maxlength="20">
                        <div class="form-text">Tu número de teléfono (opcional)</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($data['user']['nombre_usuario'] ?? ''); ?>" 
                               disabled>
                        <div class="form-text">El nombre de usuario no se puede modificar</div>
                    </div>
                    
                    <div class="col-12">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" 
                                  rows="3" maxlength="200"><?php echo htmlspecialchars($data['user']['direccion'] ?? ''); ?></textarea>
                        <div class="form-text">Tu dirección completa (opcional)</div>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="/perfil" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validaciones del formulario
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email').value.trim();

            if (!nombre) {
                e.preventDefault();
                alert('El nombre completo es requerido');
                return;
            }

            if (nombre.length < 2) {
                e.preventDefault();
                alert('El nombre debe tener al menos 2 caracteres');
                return;
            }

            if (!email) {
                e.preventDefault();
                alert('El email es requerido');
                return;
            }

            // Validación básica de email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                alert('El email no tiene un formato válido');
                return;
            }
        });

        // Confirmación antes de enviar
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que deseas actualizar tu perfil?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>