<?php
/**
 * Vista genérica de error
 */
?>
<div class="alert alert-danger" role="alert">
    <h4 class="alert-heading">Error <?= htmlspecialchars($error_code ?? 500) ?></h4>
    <p><?= htmlspecialchars($error_message ?? 'Ha ocurrido un error inesperado') ?></p>
    <?php if (isset($timestamp)): ?>
        <hr>
        <p class="mb-0"><small class="text-muted">Fecha: <?= htmlspecialchars($timestamp) ?></small></p>
    <?php endif; ?>
</div>
