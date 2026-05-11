<?php
$treeTasks = $data['treeTasks'] ?? $data['tasks'] ?? [];
$treeIdPrefix = $treeIdPrefix ?? 'task-space-tree';
$treeShowActions = $treeShowActions ?? false;

$buildStatusBadge = static function (array $task): array {
    switch ((int)($task['estado_tipo_id'] ?? 0)) {
        case 1:
            return ['bg-warning text-dark', 'Creado'];
        case 2:
            return ['bg-primary', 'Activo'];
        case 3:
            return ['bg-secondary', 'Inactivo'];
        case 5:
            return ['bg-info', 'Iniciado'];
        case 6:
            return ['bg-warning', 'Terminado'];
        case 7:
            return ['bg-danger', 'Rechazado'];
        case 8:
            return ['bg-success', 'Aprobado'];
        default:
            return ['bg-secondary', (string)($task['estado'] ?? 'Sin estado')];
    }
};

$buildSpacePath = static function (array $task): array {
    $path = [];

    for ($level = 7; $level >= 1; $level--) {
        $id = $task["espacio_ancestro_{$level}_id"] ?? null;
        if (empty($id)) {
            continue;
        }

        $path[] = [
            'id' => (string)$id,
            'name' => (string)($task["espacio_ancestro_{$level}_nombre"] ?? 'Sin nombre'),
            'level' => $task["espacio_ancestro_{$level}_nivel"] ?? null,
            'order' => $task["espacio_ancestro_{$level}_orden"] ?? null,
        ];
    }

    if (!empty($task['espacio_id'])) {
        $path[] = [
            'id' => (string)$task['espacio_id'],
            'name' => (string)($task['espacio_nombre'] ?? 'Sin espacio'),
            'level' => $task['espacio_nivel'] ?? null,
            'order' => $task['espacio_orden'] ?? null,
        ];
    } else {
        $path[] = [
            'id' => 'sin-espacio',
            'name' => 'Sin espacio',
            'level' => null,
            'order' => null,
        ];
    }

    $uniquePath = [];
    $seen = [];
    foreach ($path as $space) {
        if (isset($seen[$space['id']])) {
            continue;
        }
        $seen[$space['id']] = true;
        $uniquePath[] = $space;
    }

    return $uniquePath;
};

$spaceSorter = static function (array &$nodes) use (&$spaceSorter): void {
    uasort($nodes, static function (array $a, array $b): int {
        $levelA = $a['level'] === null || $a['level'] === '' ? PHP_INT_MAX : (int)$a['level'];
        $levelB = $b['level'] === null || $b['level'] === '' ? PHP_INT_MAX : (int)$b['level'];
        $orderA = $a['order'] === null || $a['order'] === '' ? PHP_INT_MAX : (int)$a['order'];
        $orderB = $b['order'] === null || $b['order'] === '' ? PHP_INT_MAX : (int)$b['order'];

        return [$levelA, $orderA, $a['name']] <=> [$levelB, $orderB, $b['name']];
    });

    foreach ($nodes as &$node) {
        if (!empty($node['children'])) {
            $spaceSorter($node['children']);
        }
    }
    unset($node);
};

$tree = [];
foreach ($treeTasks as $task) {
    $projectKey = (string)($task['proyecto_id'] ?? 'sin-proyecto');
    $directionKey = (string)($task['direccion_id'] ?? 'sin-direccion');

    if (!isset($tree[$projectKey])) {
        $tree[$projectKey] = [
            'name' => (string)($task['proyecto_nombre'] ?? 'Sin proyecto'),
            'directions' => [],
        ];
    }

    if (!isset($tree[$projectKey]['directions'][$directionKey])) {
        $tree[$projectKey]['directions'][$directionKey] = [
            'name' => $buildDireccionLabel($task),
            'spaces' => [],
        ];
    }

    $spaces = &$tree[$projectKey]['directions'][$directionKey]['spaces'];
    foreach ($buildSpacePath($task) as $space) {
        if (!isset($spaces[$space['id']])) {
            $spaces[$space['id']] = [
                'name' => $space['name'],
                'level' => $space['level'],
                'order' => $space['order'],
                'children' => [],
                'tasks' => [],
            ];
        }
        $spaces = &$spaces[$space['id']]['children'];
    }
    unset($spaces);

    $target = &$tree[$projectKey]['directions'][$directionKey]['spaces'];
    foreach ($buildSpacePath($task) as $space) {
        $lastSpaceId = $space['id'];
        $node = &$target[$lastSpaceId];
        $target = &$node['children'];
    }
    $node['tasks'][] = $task;
    unset($target, $node);
}

foreach ($tree as &$project) {
    foreach ($project['directions'] as &$direction) {
        $spaceSorter($direction['spaces']);
    }
    unset($direction);
}
unset($project);

$renderTasks = static function (array $tasks) use ($buildStatusBadge, $treeShowActions): void {
    foreach ($tasks as $task):
        [$badgeClass, $statusText] = $buildStatusBadge($task);
        $dateText = !empty($task['fecha_inicio']) ? date('d/m/Y', strtotime($task['fecha_inicio'])) : 'Sin fecha';
?>
        <li class="task-tree-task">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <i class="bi bi-check2-square text-setap-primary"></i>
                <span class="fw-semibold"><?= htmlspecialchars($task['tarea_nombre'] ?? 'Sin nombre') ?></span>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusText) ?></span>
                <small class="text-muted"><?= htmlspecialchars($dateText) ?></small>
                <?php if (!empty($task['duracion_horas'])): ?>
                    <small class="text-muted">HH: <?= htmlspecialchars((string)$task['duracion_horas']) ?></small>
                <?php endif; ?>
                <?php if ($treeShowActions): ?>
                    <a href="<?= \App\Constants\AppConstants::ROUTE_TASKS_SHOW ?>/<?= (int)$task['id'] ?>" class="btn btn-sm btn-outline-info ms-auto" title="Ver detalles">
                        <i class="bi bi-eye"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($task['descripcion'])): ?>
                <small class="text-muted d-block ms-4"><?= htmlspecialchars(substr((string)$task['descripcion'], 0, 120)) ?><?= strlen((string)$task['descripcion']) > 120 ? '...' : '' ?></small>
            <?php endif; ?>
        </li>
<?php
    endforeach;
};

$countNodeTasks = static function (array $node) use (&$countNodeTasks): int {
    $count = count($node['tasks']);
    foreach ($node['children'] as $child) {
        $count += $countNodeTasks($child);
    }
    return $count;
};

$renderSpaces = static function (array $nodes, int $depth = 0) use (&$renderSpaces, $renderTasks, $countNodeTasks): void {
    foreach ($nodes as $node):
        $taskCount = $countNodeTasks($node);
?>
        <li class="task-tree-space" style="--tree-depth: <?= $depth ?>;">
            <details open>
                <summary>
                    <span class="task-tree-line"></span>
                    <i class="bi bi-box-seam"></i>
                    <span class="fw-semibold"><?= htmlspecialchars($node['name']) ?></span>
                    <?php if ($node['level'] !== null && $node['level'] !== ''): ?>
                        <small class="text-muted">Nivel <?= htmlspecialchars((string)$node['level']) ?></small>
                    <?php endif; ?>
                    <?php if ($node['order'] !== null && $node['order'] !== ''): ?>
                        <small class="text-muted">Orden <?= htmlspecialchars((string)$node['order']) ?></small>
                    <?php endif; ?>
                    <span class="badge rounded-pill text-bg-light"><?= $taskCount ?> tareas</span>
                </summary>
                <?php if (!empty($node['tasks'])): ?>
                    <ul class="task-tree-tasks">
                        <?php $renderTasks($node['tasks']); ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($node['children'])): ?>
                    <ul class="task-tree-spaces">
                        <?php $renderSpaces($node['children'], $depth + 1); ?>
                    </ul>
                <?php endif; ?>
            </details>
        </li>
<?php
    endforeach;
};
?>
<div class="card">
    <div class="card-body">
        <?php if (!empty($tree)): ?>
            <div class="task-tree">
                <?php foreach ($tree as $projectIndex => $project): ?>
                    <section class="task-tree-project mb-4">
                        <h5 class="task-tree-heading">
                            <i class="bi bi-folder2-open"></i>
                            <?= htmlspecialchars($project['name']) ?>
                        </h5>
                        <?php foreach ($project['directions'] as $directionIndex => $direction): ?>
                            <details class="task-tree-direction" open>
                                <summary>
                                    <i class="bi bi-geo-alt"></i>
                                    <span><?= htmlspecialchars($direction['name']) ?></span>
                                </summary>
                                <ul class="task-tree-spaces">
                                    <?php $renderSpaces($direction['spaces']); ?>
                                </ul>
                            </details>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-diagram-3 display-4 text-muted"></i>
                <h4 class="mt-3">No hay tareas para mostrar en el árbol</h4>
            </div>
        <?php endif; ?>
    </div>
</div>



