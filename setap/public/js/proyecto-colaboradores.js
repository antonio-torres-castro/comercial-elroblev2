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

    initCalendarSection(document);

    document.addEventListener('click', function (event) {
        const link = event.target.closest('.btn-view-calendar');
        if (!link) return;
        event.preventDefault();
        loadCalendar(link.href);
    });

    const editForm = document.getElementById('form-edit-day');
    if (editForm) {
        editForm.addEventListener('submit', handleEditDay);
    }
});

function initCalendarSection(root) {
    const saveCalendarForm = root.querySelector('#form-save-calendar');
    if (saveCalendarForm) {
        saveCalendarForm.addEventListener('submit', handleSaveCalendar);
    }

    const deleteCalendarForm = root.querySelector('#form-delete-calendar');
    if (deleteCalendarForm) {
        deleteCalendarForm.addEventListener('submit', handleDeleteCalendar);
    }

    const addDateForm = root.querySelector('#form-add-date');
    if (addDateForm) {
        addDateForm.addEventListener('submit', handleAddDate);
    }

    const editButtons = root.querySelectorAll('.btn-edit-day');
    editButtons.forEach((btn) => {
        btn.addEventListener('click', () => openEditModal(btn));
    });
}

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

async function handleDeleteCalendar(e) {
    e.preventDefault();
    if (!confirm('¿Eliminar el calendario completo de este usuario?')) {
        return;
    }
    const form = e.target;
    const formData = new FormData(form);

    const response = await fetch('/setap/proyecto-colaboradores/delete-calendar', {
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

async function loadCalendar(url) {
    const calendarCard = document.getElementById('calendarCard');
    if (!calendarCard) {
        window.location.href = url;
        return;
    }

    try {
        calendarCard.classList.add('opacity-50');
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newCard = doc.getElementById('calendarCard');
        if (!newCard) {
            window.location.href = url;
            return;
        }
        calendarCard.replaceWith(newCard);
        initCalendarSection(newCard);
        window.history.pushState({}, '', url);
    } catch (error) {
        window.location.href = url;
    }
}
