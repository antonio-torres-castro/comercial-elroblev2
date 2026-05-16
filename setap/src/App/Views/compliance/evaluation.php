<?php use App\Helpers\Security; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluacion - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body class="bg-light">
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main class="mx-auto" style="max-width: 920px;">
            <div class="mb-3">
                <h2 class="mb-0"><i class="bi bi-ui-checks"></i> Evaluacion</h2>
                <div class="text-muted small"><?= htmlspecialchars($version['titulo']) ?> · minimo <?= htmlspecialchars($reading['puntaje_minimo']) ?>%</div>
            </div>

            <form method="POST" action="/setap/compliance/evaluation" id="evaluationForm">
                <?= Security::renderCsrfField() ?>
                <input type="hidden" name="reading_id" value="<?= (int)$reading['id'] ?>">

                <?php foreach ($questions as $index => $question): ?>
                    <section class="card question-card mb-3 <?= $index === 0 ? '' : 'd-none' ?>" data-question-index="<?= $index ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="mb-0"><?= ($index + 1) ?>. <?= htmlspecialchars($question['pregunta']) ?></h5>
                                <span class="badge bg-secondary"><?= ($index + 1) ?>/<?= count($questions) ?></span>
                            </div>
                            <div class="list-group">
                                <?php foreach ($question['alternativas'] as $alternative): ?>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-2" type="radio" name="answers[<?= (int)$question['id'] ?>]" value="<?= (int)$alternative['id'] ?>" required>
                                        <?= htmlspecialchars($alternative['alternativa']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="prevQuestion" disabled><i class="bi bi-arrow-left"></i> Anterior</button>
                    <button type="button" class="btn btn-setap-primary" id="nextQuestion">Siguiente <i class="bi bi-arrow-right"></i></button>
                    <button type="submit" class="btn btn-success d-none" id="submitEvaluation"><i class="bi bi-send-check"></i> Terminar evaluacion</button>
                </div>
            </form>
        </main>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script>
        const cards = Array.from(document.querySelectorAll('.question-card'));
        let current = 0;
        const prev = document.getElementById('prevQuestion');
        const next = document.getElementById('nextQuestion');
        const submit = document.getElementById('submitEvaluation');

        function showQuestion(index) {
            cards.forEach((card, i) => card.classList.toggle('d-none', i !== index));
            prev.disabled = index === 0;
            next.classList.toggle('d-none', index === cards.length - 1);
            submit.classList.toggle('d-none', index !== cards.length - 1);
        }

        next.addEventListener('click', () => {
            const currentCard = cards[current];
            const selected = currentCard.querySelector('input[type="radio"]:checked');
            if (!selected) {
                currentCard.querySelector('.list-group').classList.add('border', 'border-danger');
                return;
            }
            current = Math.min(cards.length - 1, current + 1);
            showQuestion(current);
        });

        prev.addEventListener('click', () => {
            current = Math.max(0, current - 1);
            showQuestion(current);
        });
    </script>
</body>

</html>
