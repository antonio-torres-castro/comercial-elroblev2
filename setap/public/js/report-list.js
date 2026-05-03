(function () {
    const config = window.SETAP && window.SETAP.reportList ? window.SETAP.reportList : {};
    const routes = config.routes || {};

    const getEl = (id) => document.getElementById(id);

    function today() {
        return new Date().toISOString().split('T')[0];
    }

    function lastMonth() {
        const date = new Date();
        date.setMonth(date.getMonth() - 1);
        return date.toISOString().split('T')[0];
    }

    function getReportFilters() {
        const proveedorSelect = getEl('proveedor_id');
        const fechaDesdeInput = getEl('fecha_desde');
        const fechaHastaInput = getEl('fecha_hasta');
        const proyectoSelect = getEl('proyecto_id');

        return {
            proveedor_id: proveedorSelect ? proveedorSelect.value : '',
            fecha_desde: fechaDesdeInput && fechaDesdeInput.value ? fechaDesdeInput.value : null,
            fecha_hasta: fechaHastaInput && fechaHastaInput.value ? fechaHastaInput.value : today(),
            proyecto_id: proyectoSelect ? proyectoSelect.value : ''
        };
    }

    function addHidden(form, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value || '';
        form.appendChild(input);
    }

    function buildReportUrl({ clearProject = false } = {}) {
        const filters = getReportFilters();
        const params = new URLSearchParams();

        if (filters.proveedor_id) {
            params.set('proveedor_id', filters.proveedor_id);
        }

        if (filters.fecha_desde) {
            params.set('fecha_desde', filters.fecha_desde);
        }

        if (filters.fecha_hasta) {
            params.set('fecha_hasta', filters.fecha_hasta);
        }

        if (!clearProject && filters.proyecto_id) {
            params.set('proyecto_id', filters.proyecto_id);
        }

        const base = routes.base || window.location.pathname;
        const query = params.toString();
        return query ? `${base}?${query}` : base;
    }

    async function loadCurrentUser() {
        const supplierFilter = getEl('report-supplier-filter');
        const proveedorSelect = getEl('proveedor_id');

        if (!routes.currentUser || !supplierFilter) {
            return;
        }

        try {
            const response = await fetch(routes.currentUser, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();

            if (!data.success || !data.user) {
                return;
            }

            const currentProviderId = Number(data.user.proveedor_id || 0);
            supplierFilter.classList.toggle('d-none', currentProviderId !== 0);

            if (currentProviderId !== 0 && proveedorSelect) {
                proveedorSelect.value = String(currentProviderId);
            }
        } catch (error) {
            console.error('Error al cargar usuario actual:', error);
        }
    }

    async function refreshProjectsSelect() {
        const proveedorSelect = getEl('proveedor_id');
        const proyectoSelect = getEl('proyecto_id');

        if (!routes.projects || !proyectoSelect) {
            return;
        }

        const params = new URLSearchParams({
            proveedor_id: proveedorSelect ? proveedorSelect.value : ''
        });

        try {
            proyectoSelect.disabled = true;
            const response = await fetch(`${routes.projects}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();

            if (!data.success) {
                console.error('Error al cargar proyectos:', data.message);
                return;
            }

            updateProjectOptions(data.projects || []);
        } catch (error) {
            console.error('Error al cargar proyectos:', error);
        } finally {
            proyectoSelect.disabled = false;
        }
    }

    function updateProjectOptions(projects) {
        const proyectoSelect = getEl('proyecto_id');
        if (!proyectoSelect) {
            return;
        }

        const previousValue = proyectoSelect.value;
        proyectoSelect.innerHTML = '<option value="">Selecionar proyecto...</option>';

        projects.forEach((project) => {
            const option = document.createElement('option');
            option.value = project.id;
            option.textContent = project.nombre;
            proyectoSelect.appendChild(option);
        });

        if (projects.some((project) => String(project.id) === previousValue)) {
            proyectoSelect.value = previousValue;
        }
    }

    async function reloadStatsAfterSupplierChange() {
        await refreshProjectsSelect();
        window.location.href = buildReportUrl({ clearProject: true });
    }

    function reloadStatsWithCurrentFilters() {
        window.location.href = buildReportUrl();
    }

    function generateReport(reportType) {
        const filters = getReportFilters();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = routes.generate || '/setap/reports/generate';

        addHidden(form, 'csrf_token', config.csrfToken || '');
        addHidden(form, 'report_type', reportType);
        addHidden(form, 'proveedor_id', filters.proveedor_id);
        addHidden(form, 'fecha_desde', filters.fecha_desde);
        addHidden(form, 'fecha_hasta', filters.fecha_hasta);
        addHidden(form, 'date_from', filters.fecha_desde);
        addHidden(form, 'date_to', filters.fecha_hasta);
        addHidden(form, 'proyecto_id', filters.proyecto_id);
        addHidden(form, 'project_id', filters.proyecto_id);

        document.body.appendChild(form);
        form.submit();
    }

    window.generateReport = generateReport;
    window.refreshReportProjectsSelect = refreshProjectsSelect;

    document.addEventListener('DOMContentLoaded', () => {
        const proveedorSelect = getEl('proveedor_id');
        const fechaDesdeInput = getEl('fecha_desde');
        const fechaHastaInput = getEl('fecha_hasta');

        loadCurrentUser();

        if (proveedorSelect) {
            proveedorSelect.addEventListener('change', reloadStatsAfterSupplierChange);
        }

        [fechaDesdeInput, fechaHastaInput].forEach((input) => {
            if (input) {
                input.addEventListener('change', reloadStatsWithCurrentFilters);
            }
        });
    });
})();
