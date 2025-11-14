<div class="card-header">
    <div class="row">
        <div class="col-md-2">
            <h5><i class="bi bi-list-task"></i> Tareas</h5>
        </div>

        <div class="col-md-4">
            <div class="input-group">
                <label for="fecha_inicio" class="input-group-text">Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio_filtro"
                    value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
            </div>
        </div>

        <div class="col-md-3">
            <div class="input-group">
                <label for="fecha_fin" class="input-group-text">Fin</label>
                <input type="date" class="form-control" name="fecha_fin" id="fecha_fin_filtro"
                    value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
            </div>
        </div>

        <div class="col-md-3">
            <button class="btn btn-outline-setap-primary" id="btn-filtrar-tasks" onclick="refreshCardTasks()">
                <i class="bi bi-search"></i> Filtrar
            </button>
            <button class="btn btn-outline-secondary" id="btn-clean-filters" onclick="filterClear()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

</div>

<div class="card-body">
    <?php if (!empty($error)): ?>
        <div class="text-center py-4">
            <i class="bi bi-list-task display-3 text-muted"></i>
            <h5 class="mt-3">Error</h5>
            <p class="text-muted"><?= $error ?></p>
        </div>
    <?php elseif (empty($tasks)): ?>
        <div class="text-center py-4">
            <i class="bi bi-list-task display-3 text-muted"></i>
            <h5 class="mt-3">No hay tareas asignadas</h5>
            <p class="text-muted">Comienza agregando tareas a este proyecto.</p>
            <?php if ($_GET['show_btn_nuevo']): ?>
                <a href="<?= App\Constants\AppConstants::ROUTE_TASKS_CREATE ?>?project_id=<?= $project['id'] ?>" class="btn btn-setap-primary">
                    <i class="bi bi-plus"></i> Crear Primera Tarea
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <?php
            $taskBorderClass = match ($task['estado_tipo_id']) {
                5 => 'border-setap-primary-light',     // Iniciado
                6 => 'border-warning',  // Terminado
                7 => 'border-danger',   // Rechazado
                8 => 'border-success',  // Aprobado
                default => 'border-secondary'
            };
            ?>
            <div class="task-item <?= $taskBorderClass ?> p-2 mb-1 rounded">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="col-md-10">
                        <h6 class="mb-1"><?= htmlspecialchars($task['tarea_nombre']) ?></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted">
                                    <strong>Ejecuta:</strong> <?= htmlspecialchars($task['ejecutor_nombre'] ?: 'No asignado') ?><br>
                                    <strong>Supervisa:</strong> <?= htmlspecialchars($task['supervisor_nombre'] ?: 'No asignado') ?>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">
                                    <strong>Inicio:</strong> <?= date('Y/m/d', strtotime($task['fecha_inicio'])) ?><br>
                                    <strong>Fin:</strong> <?= date('Y/m/d', strtotime($task['fecha_fin'])) ?>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">
                                    <strong>Dura:</strong> <?= $task['duracion_horas'] ?> <?= $task['duracion_horas'] > 1 ? 'horas' : 'hora'  ?><br>
                                    <strong>Prioridad:</strong> <?= $task['prioridad'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?= match ($task['estado_tipo_id']) {
                                                    2 => 'primary',
                                                    5 => 'info',
                                                    6 => 'warning',
                                                    7 => 'danger',
                                                    8 => 'success',
                                                    default => 'secondary'
                                                } ?>">
                            <?= htmlspecialchars($task['estado_nombre']) ?>
                        </span>

                        <?php if ($_GET['show_btn_ver']): ?>
                            <div class="mt-2">
                                <a href="<?= App\Constants\AppConstants::ROUTE_TASKS_SHOW ?>/<?= $task['id'] ?>" class="btn btn-sm btn-outline-setap-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Paginación -->
<?php if ($totalPages > 1): ?>
    <nav aria-label="Navegación de páginas" class="mt-3">
        <ul class="pagination justify-content-center">
            <!-- Botón Anterior -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link ajax-page" href="#" data-page="<?= $page - 1 ?>">Anterior</a>
            </li>
            <!-- Números -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link ajax-page" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <!-- Botón Siguiente -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link ajax-page" href="#" data-page="<?= $page + 1 ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>