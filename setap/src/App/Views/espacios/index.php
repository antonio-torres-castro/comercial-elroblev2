<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?> - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-geo-alt"></i> <?= $data['title'] ?></h5>
            </div>
            <div class="card-body">
                <!-- Filtros Principales -->
                <div class="row g-3 mb-4">
                    <?php if ($data['user']['usuario_tipo_id'] == 1): ?>
                        <div class="col-md-3">
                            <label class="form-label">Proveedor</label>
                            <select class="form-select" id="proveedor_id">
                                <option value="">Seleccionar proveedor...</option>
                                <?php foreach ($data['suppliers'] as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($data['provider_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-4">
                        <label class="form-label">Proyecto</label>
                        <select class="form-select" id="proyecto_id">
                            <option value="">Seleccionar proyecto...</option>
                            <?php foreach ($data['projects'] as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Dirección</label>
                        <div class="input-group">
                            <select class="form-select" id="direccion_id" disabled>
                                <option value="">Seleccione un proyecto primero</option>
                            </select>
                            <button class="btn btn-outline-success" type="button" id="btnNuevaDireccion" disabled data-bs-toggle="modal" data-bs-target="#modalDireccion">
                                <i class="bi bi-plus-lg"></i> Nueva
                            </button>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Listado de Espacios -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Jerarquía de Espacios</h6>
                    <button class="btn btn-primary btn-sm" id="btnNuevoEspacio" disabled data-bs-toggle="modal" data-bs-target="#modalEspacio">
                        <i class="bi bi-plus-circle"></i> Agregar Espacio
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Código</th>
                                <th>Nivel</th>
                                <th>Orden</th>
                                <th style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="listadoEspacios">
                            <tr>
                                <td colspan="6" class="text-center text-muted">Seleccione una dirección para ver sus espacios</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dirección -->
    <div class="modal fade" id="modalDireccion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formDireccion">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Dirección</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Calle *</label>
                                <input type="text" class="form-control" name="calle" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Número</label>
                                <input type="number" class="form-control" name="numero">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Letra</label>
                                <input type="text" class="form-control" name="letra">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Región *</label>
                                <select class="form-select" id="region_id" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($data['regiones'] as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Provincia *</label>
                                <select class="form-select" id="provincia_id" required>
                                    <option value="">Seleccione región</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Comuna *</label>
                                <select class="form-select" name="comuna_id" id="comuna_id" required>
                                    <option value="">Seleccione provincia</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Referencia *</label>
                                <input type="text" class="form-control" name="referencia" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Dirección</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Espacio -->
    <div class="modal fade" id="modalEspacio" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEspacio">
                    <input type="hidden" name="id" id="espacio_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Gestión de Espacio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Tipo de Espacio *</label>
                                <select class="form-select" name="tipos_espacio_id" id="tipos_espacio_id" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($data['tiposEspacio'] as $te): ?>
                                        <option value="<?= $te['id'] ?>"><?= htmlspecialchars($te['nombre']) ?> - <?= htmlspecialchars($te['descripcion']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Espacio Padre (Opcional)</label>
                                <select class="form-select" name="espacio_padre_id" id="espacio_padre_id">
                                    <option value="">Ninguno (Raíz)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" id="espacio_nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Código</label>
                                <input type="text" class="form-control" name="codigo" id="espacio_codigo">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nivel *</label>
                                <input type="number" class="form-control" name="nivel" id="espacio_nivel" value="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="orden" id="espacio_orden" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="espacio_descripcion" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Espacio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/setap/public/js/espacios.js"></script>
</body>

</html>