<?php

use App\Constants\AppConstants;
use App\Helpers\Security;
?>

<!-- Modal Mantenedor Proyecto-Usuarios-Grupo -->
<div class="modal fade" id="usuarioGrupoModal" tabindex="-1" aria-labelledby="usuarioGrupoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="usuarioGrupoModalLabel"><i class="bi bi-pencil"></i> Usuario Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="ugAlert" class="alert d-none" role="alert"></div>

                <form id="ugForm" class="mb-3">
                    <?= Security::renderCsrfField() ?>
                    <input type="hidden" name="project_id" id="ug_project_id" value="<?= (int)$project['id'] ?>">

                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Usuario</label>
                            <select class="form-select" name="usuario_id" id="ug_usuario_id"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grupo</label>
                            <select class="form-select" name="grupo_id" id="ug_grupo_id"></select>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-primary w-100" id="ugAddBtn">
                                <i class="bi bi-plus-lg"></i> Nuevo
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="ugTable">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Grupo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="/setap/public/js/card-usuario-grupo.js"></script>