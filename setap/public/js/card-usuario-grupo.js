document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('usuarioGrupoModal');
  const card = document.getElementById('cardUsuarioGrupo');
  if (!modal || !card) return;

  const projectId = parseInt(card.getAttribute('data-project-id'), 10);
  const ugAlert = document.getElementById('ugAlert');
  const usuarioSelect = document.getElementById('ug_usuario_id');
  const grupoSelect = document.getElementById('ug_grupo_id');
  const addBtn = document.getElementById('ugAddBtn');
  const ugTableBody = document.querySelector('#ugTable tbody');
  const csrfInput = document.querySelector('#ugForm input[name="csrf_token"]');

  let gruposCatalog = [];
  let projectActive = true;

  function showAlert(type, message) {
    ugAlert.className = `alert alert-${type}`;
    ugAlert.textContent = message;
    ugAlert.classList.remove('d-none');
    setTimeout(() => ugAlert.classList.add('d-none'), 3000);
  }

  async function loadData() {
    try {
      const res = await fetch(`/setap/project/usuarios-grupo-list?id=${projectId}`, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      if (!data.success) {
        showAlert('danger', data.message || 'Error al cargar datos');
        return;
      }

      // Fill selects
      usuarioSelect.innerHTML = '';
      data.users.forEach(u => {
        const opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = u.nombre_usuario;
        usuarioSelect.appendChild(opt);
      });

      grupoSelect.innerHTML = '';
      gruposCatalog = data.grupos || [];
      gruposCatalog.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g.id;
        opt.textContent = g.nombre;
        grupoSelect.appendChild(opt);
      });

      projectActive = !!data.projectActive;
      addBtn.disabled = !projectActive;
      usuarioSelect.disabled = !projectActive;
      grupoSelect.disabled = !projectActive;
      if (!projectActive) {
        showAlert('warning', 'Proyecto inactivo, no se puede modificar');
      }

      // Fill table
      ugTableBody.innerHTML = '';
      (data.assigned || []).forEach(row => {
        const tr = document.createElement('tr');
        const tdUser = document.createElement('td');
        tdUser.textContent = row.username;
        const tdGrupo = document.createElement('td');
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        gruposCatalog.forEach(g => {
          const opt = document.createElement('option');
          opt.value = g.id;
          opt.textContent = g.nombre;
          if (parseInt(row.grupo_id, 10) === parseInt(g.id, 10)) opt.selected = true;
          select.appendChild(opt);
        });
        select.disabled = !projectActive; // no update when inactive
        tdGrupo.appendChild(select);

        const tdAcc = document.createElement('td');
        tdAcc.className = 'text-end';
        const btnUpd = document.createElement('button');
        btnUpd.className = 'btn btn-sm btn-outline-primary me-2';
        btnUpd.innerHTML = '<i class="bi bi-check-lg"></i>';
        btnUpd.title = 'Actualizar';
        btnUpd.disabled = !projectActive;
        btnUpd.addEventListener('click', async () => {
          await updateRow(row.id, parseInt(select.value, 10));
        });

        const btnDel = document.createElement('button');
        btnDel.className = 'btn btn-sm btn-outline-danger';
        btnDel.innerHTML = '<i class="bi bi-trash"></i>';
        btnDel.title = 'Eliminar';
        btnDel.addEventListener('click', async () => {
          await deleteRow(row.id);
        });

        tdAcc.appendChild(btnUpd);
        tdAcc.appendChild(btnDel);

        tr.appendChild(tdUser);
        tr.appendChild(tdGrupo);
        tr.appendChild(tdAcc);
        ugTableBody.appendChild(tr);
      });
    } catch (e) {
      showAlert('danger', 'Error de red al cargar datos');
    }
  }

  async function addRow() {
    const usuario_id = parseInt(usuarioSelect.value, 10);
    const grupo_id = parseInt(grupoSelect.value, 10);
    if (!usuario_id || !grupo_id) {
      showAlert('warning', 'Seleccione usuario y grupo');
      return;
    }
    try {
      const formData = new FormData();
      formData.append('csrf_token', csrfInput ? csrfInput.value : '');
      formData.append('project_id', projectId);
      formData.append('usuario_id', usuario_id);
      formData.append('grupo_id', grupo_id);
      const res = await fetch('/setap/project/usuarios-grupo-add', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showAlert('success', 'Creado');
        await loadData();
      } else {
        showAlert('danger', data.message || 'No permitido');
      }
    } catch (e) {
      showAlert('danger', 'Error de red');
    }
  }

  async function updateRow(id, grupo_id) {
    try {
      const formData = new FormData();
      formData.append('csrf_token', csrfInput ? csrfInput.value : '');
      formData.append('id', id);
      formData.append('project_id', projectId);
      formData.append('grupo_id', grupo_id);
      const res = await fetch('/setap/project/usuarios-grupo-update', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showAlert('success', 'Actualizado');
        await loadData();
      } else {
        showAlert('danger', data.message || 'No permitido');
      }
    } catch (e) {
      showAlert('danger', 'Error de red');
    }
  }

  async function deleteRow(id) {
    try {
      const formData = new FormData();
      formData.append('csrf_token', csrfInput ? csrfInput.value : '');
      formData.append('id', id);
      const res = await fetch('/setap/project/usuarios-grupo-delete', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showAlert('success', 'Eliminado');
        await loadData();
      } else {
        showAlert('danger', data.message || 'No permitido');
      }
    } catch (e) {
      showAlert('danger', 'Error de red');
    }
  }

  modal.addEventListener('shown.bs.modal', loadData);
  addBtn.addEventListener('click', () => {
    if (!projectActive) {
      showAlert('warning', 'Proyecto inactivo, no se puede modificar');
      return;
    }
    addRow();
  });
});