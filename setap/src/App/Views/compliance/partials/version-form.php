<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="version">Version *</label>
        <input type="text" class="form-control" id="version" name="version" value="1.0" maxlength="50" required>
    </div>
    <div class="col-md-9">
        <label class="form-label" for="titulo">Titulo *</label>
        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="250" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="resumen">Resumen</label>
        <textarea class="form-control" id="resumen" name="resumen" rows="2" maxlength="1000"></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="fecha_inicio_vigencia">Inicio vigencia</label>
        <input type="date" class="form-control" id="fecha_inicio_vigencia" name="fecha_inicio_vigencia">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="fecha_fin_vigencia">Fin vigencia</label>
        <input type="date" class="form-control" id="fecha_fin_vigencia" name="fecha_fin_vigencia">
    </div>
    <div class="col-12">
        <label class="form-label" for="contenido_html">Documento HTML *</label>
        <textarea class="form-control font-monospace" id="contenido_html" name="contenido_html" rows="12" required></textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="publicado" name="publicado" value="1">
            <label class="form-check-label" for="publicado">Publicar esta version al guardar</label>
        </div>
    </div>
</div>
