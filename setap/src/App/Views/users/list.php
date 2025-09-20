<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../Views/layouts/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Gestión de Usuarios</h2>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
                <?php endif; ?>

                <a href="/users/create" class="btn btn-primary mb-3">Nuevo Usuario</a>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>RUT</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['rut']) ?></td>
                                <td><?= htmlspecialchars($user['persona_nombre']) ?></td>
                                <td><?= htmlspecialchars($user['nombre_usuario']) ?></td>
                                <td><?= htmlspecialchars($user['tipo_usuario']) ?></td>
                                <td>
                                    <a href="/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="/users/delete/<?= $user['id'] ?>" class="btn btn-sm btn-danger">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>