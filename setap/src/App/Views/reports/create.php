<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Reporte - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .form-section {
            background: var(--setap-bg-light);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-section h5 {
            color: var(--setap-text-muted);
            border-bottom: 2px solid var(--setap-border-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .required {
            color: #dc3545;
        }
        .main-content {
            margin-top: 2rem;
        }
        .report-preview {
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>

<body class="bg-light">
    <?php use App\Helpers\Security; ?>

    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container mt-4">
        <main class="main-content">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2>
                        <i class="bi bi-plus-circle"></i> Crear Nuevo Reporte
                    </h2>
                    <p class="text-muted">Configure los parámetros para generar un reporte personalizado</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="/reports" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver a Reportes
                    </a>
                </div>
            </div>

            <!-- Mensajes de Error -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulario de Configuración -->
            <form method="POST" action="/reports/generate" id="createReportForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Helpers\Security::generateCsrfToken()) ?>">

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <!-- Configuración Básica -->
                        <div class="form-section">
                            <h5><i class="bi bi-gear"></i> Configuración Básica</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="report_type" class="form-label">Tipo de Reporte <span class="required">*</span></label>
                                        <select class="form-select" id="report_type" name="report_type" required>
                                            <option value="">Seleccionar Tipo</option>
                                            <option value="projects_summary">Resumen de Proyectos</option>
                                            <option value="tasks_summary">Resumen de Tareas</option>
                                            <option value="users_activity">Actividad de Usuarios</option>
                                            <option value="clients_summary">Resumen de Clientes</option>
                                            <option value="custom">Reporte Personalizado</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="report_format" class="form-label">Formato de Salida</label>
                                        <select class="form-select" id="report_format" name="report_format">
                                            <option value="html">Vista Web (HTML)</option>
                                            <option value="pdf">Documento PDF</option>
                                            <option value="excel">Hoja de Cálculo (Excel)</option>
                                            <option value="csv">Archivo CSV</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros de Fecha -->
                        <div class="form-section">
                            <h5><i class="bi bi-calendar-range"></i> Período de Tiempo</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_from" class="form-label">Fecha Desde</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from">
                                        <div class="form-text">Deje en blanco para incluir desde el inicio</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_to" class="form-label">Fecha Hasta</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to">
                                        <div class="form-text">Deje en blanco para incluir hasta la fecha actual</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Presets de fechas -->
                            <div class="mb-3">
                                <label class="form-label">Períodos Predefinidos</label>
                                <div class="btn-group" role="group" aria-label="Períodos predefinidos">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('today')">Hoy</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('week')">Esta Semana</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('month')">Este Mes</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('quarter')">Este Trimestre</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('year')">Este Año</button>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros Específicos -->
                        <div class="form-section">
                            <h5><i class="bi bi-funnel"></i> Filtros Específicos</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Cliente Específico</label>
                                        <select class="form-select" id="client_id" name="client_id">
                                            <option value="">Todos los clientes</option>
                                            <!-- Aquí cargarías los clientes dinámicamente -->
                                        </select>
                                        <div class="form-text">Filtrar por un cliente específico</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="project_id" class="form-label">Proyecto Específico</label>
                                        <select class="form-select" id="project_id" name="project_id">
                                            <option value="">Todos los proyectos</option>
                                            <!-- Aquí cargarías los proyectos dinámicamente -->
                                        </select>
                                        <div class="form-text">Filtrar por un proyecto específico</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status_filter" class="form-label">Estado</label>
                                        <select class="form-select" id="status_filter" name="status_filter">
                                            <option value="">Todos los estados</option>
                                            <option value="active">Solo Activos</option>
                                            <option value="completed">Solo Completados</option>
                                            <option value="pending">Solo Pendientes</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="include_details" class="form-label">Nivel de Detalle</label>
                                        <select class="form-select" id="include_details" name="include_details">
                                            <option value="summary">Solo Resumen</option>
                                            <option value="detailed">Información Detallada</option>
                                            <option value="full">Reporte Completo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vista Previa -->
                        <div class="form-section">
                            <h5><i class="bi bi-eye"></i> Vista Previa</h5>

                            <div class="report-preview" id="reportPreview">
                                <i class="bi bi-file-text" style="font-size: 3rem;"></i>
                                <p class="mt-3">Seleccione los parámetros del reporte para ver una vista previa</p>
                                <small class="text-muted">La vista previa se actualizará automáticamente</small>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/reports" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </a>
                            <button type="button" class="btn btn-outline-setap-primary" id="previewBtn">
                                <i class="bi bi-eye"></i> Vista Previa
                            </button>
                            <button type="submit" class="btn btn-success" id="generateBtn">
                                <i class="bi bi-play-circle"></i> Generar Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <!-- Scripts -->
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createReportForm');
            const generateBtn = document.getElementById('generateBtn');
            const previewBtn = document.getElementById('previewBtn');
            const reportType = document.getElementById('report_type');
            const reportPreview = document.getElementById('reportPreview');

            // Establecer fecha máxima como hoy
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date_from').max = today;
            document.getElementById('date_to').max = today;

            // Validación de fechas
            function validateDates() {
                const dateFrom = document.getElementById('date_from').value;
                const dateTo = document.getElementById('date_to').value;

                if (dateFrom && dateTo && dateFrom > dateTo) {
                    document.getElementById('date_to').setCustomValidity('La fecha hasta debe ser posterior a la fecha desde');
                    return false;
                } else {
                    document.getElementById('date_to').setCustomValidity('');
                    return true;
                }
            }

            // Actualizar vista previa
            function updatePreview() {
                const selectedType = reportType.value;
                const dateFrom = document.getElementById('date_from').value;
                const dateTo = document.getElementById('date_to').value;

                if (selectedType) {
                    const typeNames = {
                        'projects_summary': 'Resumen de Proyectos',
                        'tasks_summary': 'Resumen de Tareas',
                        'users_activity': 'Actividad de Usuarios',
                        'clients_summary': 'Resumen de Clientes',
                        'custom': 'Reporte Personalizado'
                    };

                    const period = dateFrom && dateTo ?
                        `del ${dateFrom} al ${dateTo}` :
                        'de todo el período disponible';

                    reportPreview.innerHTML = `
                        <i class="bi bi-file-text text-setap-primary" style="font-size: 3rem;"></i>
                        <h6 class="mt-3">${typeNames[selectedType]}</h6>
                        <p class="text-muted">Datos ${period}</p>
                        <small class="text-success">
                            <i class="bi bi-check-circle"></i> Listo para generar
                        </small>
                    `;
                } else {
                    reportPreview.innerHTML = `
                        <i class="bi bi-file-text" style="font-size: 3rem;"></i>
                        <p class="mt-3">Seleccione los parámetros del reporte para ver una vista previa</p>
                        <small class="text-muted">La vista previa se actualizará automáticamente</small>
                    `;
                }
            }

            // Event listeners
            document.getElementById('date_from').addEventListener('change', function() {
                document.getElementById('date_to').min = this.value;
                validateDates();
                updatePreview();
            });

            document.getElementById('date_to').addEventListener('change', function() {
                validateDates();
                updatePreview();
            });

            reportType.addEventListener('change', updatePreview);

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    alert('Por favor, corrige los errores en las fechas.');
                    return;
                }

                if (!reportType.value) {
                    e.preventDefault();
                    alert('Debe seleccionar un tipo de reporte.');
                    return;
                }

                // Mostrar indicador de carga
                generateBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Generando...';
                generateBtn.disabled = true;
            });

            // Vista previa
            previewBtn.addEventListener('click', function() {
                if (!reportType.value) {
                    alert('Debe seleccionar un tipo de reporte.');
                    return;
                }

                alert('Funcionalidad de vista previa en desarrollo.');
            });
        });

        // Función para establecer rangos de fechas predefinidos
        function setDateRange(period) {
            const today = new Date();
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');

            dateTo.value = today.toISOString().split('T')[0];

            switch(period) {
                case 'today':
                    dateFrom.value = today.toISOString().split('T')[0];
                    break;
                case 'week':
                    const weekAgo = new Date(today);
                    weekAgo.setDate(today.getDate() - 7);
                    dateFrom.value = weekAgo.toISOString().split('T')[0];
                    break;
                case 'month':
                    const monthAgo = new Date(today);
                    monthAgo.setMonth(today.getMonth() - 1);
                    dateFrom.value = monthAgo.toISOString().split('T')[0];
                    break;
                case 'quarter':
                    const quarterAgo = new Date(today);
                    quarterAgo.setMonth(today.getMonth() - 3);
                    dateFrom.value = quarterAgo.toISOString().split('T')[0];
                    break;
                case 'year':
                    const yearAgo = new Date(today);
                    yearAgo.setFullYear(today.getFullYear() - 1);
                    dateFrom.value = yearAgo.toISOString().split('T')[0];
                    break;
            }

            // Disparar eventos para actualizar validación y vista previa
            dateFrom.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>