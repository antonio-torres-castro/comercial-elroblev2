<?php

use App\Helpers\Security;
use App\Constants\AppConstants;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Accesos - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
    <style>
        .search-box {
            max-width: 300px;
        }

        .main-content {
            margin-top: 2rem;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main class="main-content">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2>
                        <i class="bi bi-clock-history"></i> Registro de Accesos
                        <span class="badge bg-secondary ms-2"><?= $totalRows ?> registros</span>
                    </h2>
                </div>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="<?= AppConstants::ROUTE_USERS_LOGS ?>" id="filterForm" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <div class="input-group search-box">
                                <input type="text" class="form-control" name="search" id="searchInput"
                                    placeholder="Buscar usuarios..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" id="roleFilter" name="role">
                                <option value="">Todos los roles</option>
                                <?php if (!empty($userTypes)): ?>
                                    <?php foreach ($userTypes as $type): ?>
                                        <option value="<?= htmlspecialchars($type['nombre']) ?>" <?= ($filters['role'] ?? '') === $type['nombre'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucfirst($type['nombre'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($filters['fecha_inicio'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fin</label>
                            <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($filters['fecha_fin'] ?? '') ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-outline-setap-primary">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <a href="<?= AppConstants::ROUTE_USERS_LOGS ?>" class="btn btn-outline-secondary" id="clearFilters">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                        </div>
                    </form>

                    <div class="row mt-3">
                        <div class="col-md-12 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="exportBtn">
                                    <i class="bi bi-download"></i> Exportar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="refreshBtn">
                                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Logs -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="userLogsTable">
                            <thead class="table-setap-primary">
                                <tr>
                                    <th>Fecha</th>
                                    <th>IP</th>
                                    <th>Usuario</th>
                                    <th>Persona</th>
                                    <th>Rol</th>
                                    <th>Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox"></i> No hay registros
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr data-role="<?= htmlspecialchars($log['rol'] ?? '') ?>">
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['fecha']))) ?></td>
                                            <td><?= htmlspecialchars($log['IP']) ?></td>
                                            <td><?= htmlspecialchars($log['nombre_usuario']) ?></td>
                                            <td><?= htmlspecialchars($log['nombre']) ?></td>
                                            <td><?= htmlspecialchars($log['rol']) ?></td>
                                            <td><?= htmlspecialchars($log['tipo']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Navegación de páginas" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php
                                $queryString = $_GET;
                                unset($queryString['page']);
                                $baseUrl = '?' . http_build_query($queryString);
                                ?>

                                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $baseUrl . '&page=' . ($currentPage - 1) ?>">&laquo;</a>
                                </li>

                                <?php
                                $start = max(1, $currentPage - 1);
                                $end = min($totalPages, $currentPage + 1);
                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $baseUrl . '&page=' . $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $baseUrl . '&page=' . ($currentPage + 1) ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script>
        // Actualizar página
        document.getElementById('refreshBtn').addEventListener('click', function() {
            window.location.reload();
        });

        // Exportar datos visibles
        document.getElementById('exportBtn').addEventListener('click', function() {
            exportToCSV();
        });

        function exportToCSV() {
            const table = document.getElementById('userLogsTable');
            const rows = Array.from(table.querySelectorAll('tr'));

            let csv = [];

            // Headers
            const headers = Array.from(rows[0].querySelectorAll('th'))
                .map(th => th.textContent.trim());
            csv.push(headers.join(','));

            // Data rows
            rows.slice(1).forEach(row => {
                if (row.querySelectorAll('td').length) {
                    const cells = Array.from(row.querySelectorAll('td'))
                        .map(td => `"${td.textContent.trim().replace(/"/g, '""')}"`);
                    csv.push(cells.join(','));
                }
            });

            const blob = new Blob([csv.join('\n')], {
                type: 'text/csv'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `user_logs_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>

</html>