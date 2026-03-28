/**
 * Proyecto Colaboradores - JS
 */

document.addEventListener('DOMContentLoaded', function () {
    const projectSelect = document.getElementById('projectSelect');
    if (projectSelect) {
        projectSelect.addEventListener('change', function () {
            const projectId = this.value;
            if (projectId) {
                window.location.href = `?proyecto_id=${projectId}`;
            }
        });
    }

    const addExecutorForm = document.getElementById('form-add-executor');
    if (addExecutorForm) {
        addExecutorForm.addEventListener('submit', handleAddExecutor);
    }

    const saveCalendarForm = document.getElementById('form-save-calendar');
    if (saveCalendarForm) {
        saveCalendarForm.addEventListener('submit', handleSaveCalendar);
    }

    const addDateForm = document.getElementById('form-add-date');
    if (addDateForm) {
        addDateForm.addEventListener('submit', handleAddDate);
    }

    const editButtons = document.querySelectorAll('.btn-edit-day');
    editButtons.forEach((btn) => {
        btn.addEventListener('click', () => openEditModal(btn));
    });

    const editForm = document.getElementById('form-edit-day');
    if (editForm) {
        editForm.addEventListener('submit', handleEditDay);
    }
});

async function handleAddExecutor(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const response = await fetch('/setap/proyecto-colaboradores/add-executor', {
        method: 'POST',
        body: formData,
    });

    await handleJsonResponse(response, null, () => {
        window.location.reload();
    });
}

async function handleSaveCalendar(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const response = await fetch('/setap/proyecto-colaboradores/save-calendar', {
        method: 'POST',
        body: formData,
    });

    await handleJsonResponse(response, null, () => {
        window.location.reload();
    });
}

async function handleAddDate(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const response = await fetch('/setap/proyecto-colaboradores/add-date', {
        method: 'POST',
        body: formData,
    });

    await handleJsonResponse(response, null, () => {
        window.location.reload();
    });
}

function openEditModal(button) {
    const fecha = button.getAttribute('data-fecha');
    const hh = button.getAttribute('data-hh');
    const tipoId = button.getAttribute('data-tipo-id');

    const fechaInput = document.getElementById('editFecha');
    const hhInput = document.getElementById('editHh');
    const tipoSelect = document.getElementById('editTipoFecha');

    if (fechaInput) fechaInput.value = fecha || '';
    if (hhInput) hhInput.value = hh || 0;
    if (tipoSelect) tipoSelect.value = tipoId || 1;

    const modalEl = document.getElementById('editDayModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

async function handleEditDay(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const response = await fetch('/setap/proyecto-colaboradores/update-day', {
        method: 'POST',
        body: formData,
    });

    await handleJsonResponse(response, null, () => {
        window.location.reload();
    });
}
