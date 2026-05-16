<?php $prefix = $prefix ?? ''; ?>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="<?= $prefix ?>nombre">Nombre *</label>
        <input type="text" class="form-control" id="<?= $prefix ?>nombre" name="nombre" maxlength="250" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="<?= $prefix ?>codigo">Codigo</label>
        <input type="text" class="form-control" id="<?= $prefix ?>codigo" name="codigo" maxlength="100">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="<?= $prefix ?>vigencia_dias">Vigencia dias</label>
        <input type="number" class="form-control" id="<?= $prefix ?>vigencia_dias" name="vigencia_dias" value="365" min="1" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="<?= $prefix ?>descripcion">Descripcion</label>
        <textarea class="form-control" id="<?= $prefix ?>descripcion" name="descripcion" rows="2" maxlength="500"></textarea>
    </div>
    <div class="col-md-4">
        <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" id="<?= $prefix ?>requiere_evaluacion" name="requiere_evaluacion" value="1" checked>
            <label class="form-check-label" for="<?= $prefix ?>requiere_evaluacion">Requiere evaluacion</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="<?= $prefix ?>puntaje_minimo">Puntaje minimo %</label>
        <input type="number" class="form-control" id="<?= $prefix ?>puntaje_minimo" name="puntaje_minimo" value="80" min="0" max="100" step="0.01" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="<?= $prefix ?>cantidad_preguntas">Cantidad preguntas</label>
        <input type="number" class="form-control" id="<?= $prefix ?>cantidad_preguntas" name="cantidad_preguntas" value="5" min="1" required>
    </div>
</div>
