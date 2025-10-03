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
                <a class="nav-link text-light" href="/menus">
                    <i class="bi bi-list-ul"></i> Menús
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
                        <a href="/menus" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo $data['subtitle']; ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($data['errors']) && !empty($data['errors'])): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php foreach ($data['errors'] as $error): ?>
                                                <li><?= htmlspecialchars($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($data['success']) && $data['success']): ?>
                                    <div class="alert alert-success">
                                        <?= htmlspecialchars($data['success']) ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="<?= $data['menu_id'] ? '/menus/update/' . $data['menu_id'] : '/menus/store' ?>" id="menuForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <?php if ($data['menu_id']): ?>
                                        <input type="hidden" name="id" value="<?= $data['menu_id'] ?>">
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nombre" class="form-label">Nombre interno *</label>
                                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                                       value="<?= htmlspecialchars($data['menu']['nombre'] ?? '') ?>" 
                                                       required maxlength="150">
                                                <div class="form-text">Nombre interno del sistema (ej: manage_users)</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="display" class="form-label">Título de visualización *</label>
                                                <input type="text" class="form-control" id="display" name="display" 
                                                       value="<?= htmlspecialchars($data['menu']['display'] ?? '') ?>" 
                                                       required maxlength="150">
                                                <div class="form-text">Nombre que verá el usuario (ej: Usuarios)</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="descripcion" class="form-label">Descripción</label>
                                                <textarea class="form-control" id="descripcion" name="descripcion" 
                                                          maxlength="300" rows="3"><?= htmlspecialchars($data['menu']['descripcion'] ?? '') ?></textarea>
                                                <div class="form-text">Descripción de las funcionalidades (opcional)</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="url" class="form-label">URL *</label>
                                                <input type="text" class="form-control" id="url" name="url" 
                                                       value="<?= htmlspecialchars($data['menu']['url'] ?? '') ?>" 
                                                       required maxlength="100">
                                                <div class="form-text">Ruta relativa del menú (ej: /users, /projects)</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="icono" class="form-label">Icono</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="bi bi-<?= htmlspecialchars($data['menu']['icono'] ?? 'circle') ?>" id="icon-preview"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="icono" name="icono" 
                                                           value="<?= htmlspecialchars($data['menu']['icono'] ?? '') ?>" 
                                                           maxlength="50" placeholder="circle">
                                                </div>
                                                <div class="form-text">Icono de Bootstrap Icons (sin prefijo 'bi bi-')</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="orden" class="form-label">Orden *</label>
                                                <input type="number" class="form-control" id="orden" name="orden" 
                                                       value="<?= htmlspecialchars($data['menu']['orden'] ?? '1') ?>" 
                                                       required min="1" max="999">
                                                <div class="form-text">Orden de aparición en el menú</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="estado_tipo_id" class="form-label">Estado</label>
                                                <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                                    <?php if (!empty($data['estados'])): ?>
                                                        <?php foreach ($data['estados'] as $estado): ?>
                                                            <option value="<?= $estado['id'] ?>" 
                                                                <?= ((isset($data['menu']['estado_tipo_id']) && $data['menu']['estado_tipo_id'] == $estado['id']) || 
                                                                    (!isset($data['menu']) && $estado['id'] == 2)) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($estado['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <div class="form-text">Estado del menú (activo = visible en el sistema)</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between">
                                                <a href="/menus" class="btn btn-secondary">
                                                    <i class="bi bi-arrow-left"></i> Cancelar
                                                </a>
                                                <button type="submit" class="btn btn-setap-primary">
                                                    <i class="bi bi-save"></i> 
                                                    <?= $data['menu_id'] ? 'Actualizar Menú' : 'Crear Menú' ?>
                                                </button>
                                            </div>
                                        </div>
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
    <script>
        // Actualizar preview del icono
        document.getElementById('icono').addEventListener('input', function() {
            const iconInput = this.value.trim();
            const iconPreview = document.getElementById('icon-preview');
            
            if (iconInput) {
                iconPreview.className = `bi bi-${iconInput}`;
            } else {
                iconPreview.className = 'bi bi-circle';
            }
        });

        // Validaciones del formulario
        document.getElementById('menuForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const display = document.getElementById('display').value.trim();
            const url = document.getElementById('url').value.trim();
            const orden = document.getElementById('orden').value;

            if (!nombre) {
                e.preventDefault();
                alert('El nombre interno es requerido');
                return;
            }

            if (!display) {
                e.preventDefault();
                alert('El título de visualización es requerido');
                return;
            }

            if (!url) {
                e.preventDefault();
                alert('La URL es requerida');
                return;
            }

            if (!url.startsWith('/')) {
                e.preventDefault();
                alert('La URL debe comenzar con "/"');
                return;
            }

            if (!orden || orden < 1) {
                e.preventDefault();
                alert('El orden debe ser un número mayor a 0');
                return;
            }
        });

        // Auto-generar URL basado en nombre
        document.getElementById('nombre').addEventListener('input', function() {
            const urlField = document.getElementById('url');
            
            // Solo auto-generar si el campo URL está vacío
            if (!urlField.value.trim()) {
                const nombre = this.value.trim().toLowerCase();
                const url = '/' + nombre.replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
                urlField.value = url;
            }
        });
    </script>
</body>
</html>