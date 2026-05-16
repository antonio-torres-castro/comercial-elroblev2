<?php use App\Helpers\Security; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Evaluaciones - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body class="bg-light">
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main>
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h2 class="mb-0"><i class="bi bi-ui-checks"></i> Administrar Evaluaciones</h2>
                    <div class="text-muted small">Preguntas y alternativas asociadas a la version vigente del documento.</div>
                </div>
                <a href="/setap/compliance" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Administrar</a>
            </div>

            <?php if (!empty($data['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($data['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (!empty($data['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($data['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label" for="version_id">Documento / version</label>
                            <select class="form-select" id="version_id" name="version_id" onchange="this.form.submit()">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($data['documents'] as $document): ?>
                                    <?php if (!empty($document['version_id'])): ?>
                                        <option value="<?= (int)$document['version_id'] ?>" <?= (($data['selectedVersion']['id'] ?? 0) == $document['version_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($document['nombre']) ?> - v<?= htmlspecialchars($document['version']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-setap-primary" type="submit"><i class="bi bi-search"></i> Cargar</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($data['selectedVersion'])): ?>
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-header bg-white"><strong>Nueva pregunta</strong></div>
                            <div class="card-body">
                                <form method="POST" action="/setap/compliance/assessments">
                                    <?= Security::renderCsrfField() ?>
                                    <input type="hidden" name="version_id" value="<?= (int)$data['selectedVersion']['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label" for="pregunta">Pregunta</label>
                                        <textarea class="form-control" id="pregunta" name="pregunta" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="orden_visualizacion">Orden</label>
                                        <input type="number" class="form-control" id="orden_visualizacion" name="orden_visualizacion" value="<?= count($data['questions']) + 1 ?>" min="1" required>
                                    </div>
                                    <div class="mb-2 fw-semibold">Alternativas</div>
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">
                                                <input class="form-check-input mt-0" type="radio" name="correct_alternative" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?> aria-label="Correcta">
                                            </span>
                                            <input type="text" class="form-control" name="alternatives[<?= $i ?>]" placeholder="Alternativa <?= $i + 1 ?>" <?= $i < 2 ? 'required' : '' ?>>
                                        </div>
                                    <?php endfor; ?>
                                    <button class="btn btn-setap-primary" type="submit"><i class="bi bi-plus-lg"></i> Agregar pregunta</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between">
                                <strong>Preguntas vigentes</strong>
                                <span class="badge bg-secondary"><?= count($data['questions']) ?></span>
                            </div>
                            <div class="card-body">
                                <?php foreach ($data['questions'] as $question): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div class="fw-semibold"><?= (int)$question['orden_visualizacion'] ?>. <?= htmlspecialchars($question['pregunta']) ?></div>
                                            <span class="badge <?= (int)$question['estado_tipo_id'] === 2 ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($question['estado_nombre']) ?></span>
                                        </div>
                                        <ul class="list-unstyled mt-2 mb-0">
                                            <?php foreach ($question['alternativas'] as $alternative): ?>
                                                <li class="small">
                                                    <i class="bi <?= !empty($alternative['es_correcta']) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                                                    <?= htmlspecialchars($alternative['alternativa']) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($data['questions'])): ?>
                                    <div class="text-center text-muted py-4"><i class="bi bi-inbox"></i> Sin preguntas registradas</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card"><div class="card-body text-center text-muted py-5"><i class="bi bi-arrow-up-circle display-4"></i><h5 class="mt-3">Seleccione una version publicada</h5></div></div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
</body>

</html>
