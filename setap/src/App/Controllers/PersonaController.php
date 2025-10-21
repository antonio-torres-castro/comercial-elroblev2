<?php
namespace App\Controllers;

use App\Models\Persona;
use App\Helpers\Security;
use App\Constants\AppConstants;
use Exception;

class PersonaController extends AbstractBaseController
{
    private $personaModel;

    /**
     * Hook para inicialización específica del controlador
     */
    protected function initializeController(): void
    {
        $this->personaModel = new Persona();
    }

    /**
     * Lista de personas (plural) - Para administradores
     */
    public function index()
    {
        return $this->executeWithErrorHandling(function() {
            // Verificar autenticación y permisos
            if (!$this->requireAuthAndPermission('manage_personas')) {
                return;
            }

            // Aplicar filtros si están presentes
            $filters = $this->extractFilters(['estado_tipo_id', 'search']);
            
            $personas = $this->personaModel->getAll($filters);
            $estadosTipo = $this->getEstadosTipo();
            $stats = $this->personaModel->getStats();

            $this->render('personas/list', [
                'personas' => $personas,
                'estadosTipo' => $estadosTipo,
                'stats' => $stats,
                'filters' => $filters,
                'success' => $_GET['success'] ?? '',
                'error' => $_GET['error'] ?? ''
            ]);
        }, 'index');
    }

    /**
     * Mostrar formulario de creación de persona
     */
    public function create()
    {
        return $this->executeWithErrorHandling(function() {
            // Verificar autenticación y permisos
            if (!$this->requireAuthAndPermission('manage_persona')) {
                return;
            }

            $estadosTipo = $this->getEstadosTipo();

            $this->render('personas/create', [
                'estadosTipo' => $estadosTipo,
                'error' => $_GET['error'] ?? ''
            ]);
        }, 'create');
    }

    /**
     * Procesar creación de persona
     */
    public function store()
    {
        return $this->executeWithErrorHandling(function() {
            // Verificar autenticación y permisos
            if (!$this->requireAuthAndPermission('manage_persona')) {
                return;
            }

            // Validar método POST y token CSRF centralizadamente
            $errors = $this->validatePostRequest();
            if (!empty($errors)) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS_CREATE, implode(', ', $errors));
                return;
            }

            // Validar datos de la persona
            $validationErrors = $this->validatePersonaData($_POST);
            if (!empty($validationErrors)) {
                $errorMsg = implode(', ', $validationErrors);
                Security::redirect("/personas/create?error=" . urlencode($errorMsg));
                return;
            }

            $personaData = [
                'rut' => Security::sanitizeInput($_POST['rut_clean'] ?? $_POST['rut']),
                'nombre' => Security::sanitizeInput($_POST['nombre']),
                'telefono' => Security::sanitizeInput($_POST['telefono'] ?? ''),
                'direccion' => Security::sanitizeInput($_POST['direccion'] ?? ''),
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 2)
            ];

            $personaId = $this->personaModel->create($personaData);
            if ($personaId) {
                Security::logSecurityEvent('persona_created', [
                    'persona_id' => $personaId,
                    'created_by' => $_SESSION['username']
                ]);
                $this->redirectWithSuccess(AppConstants::ROUTE_PERSONAS, 'Persona creada correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS_CREATE, AppConstants::ERROR_CREATE_PERSONA);
            }
        }, 'store');
    }

    /**
     * Mostrar formulario de edición de persona
     */
    public function edit()
    {
        return $this->executeWithErrorHandling(function() {
            // Verificar autenticación y permisos
            if (!$this->requireAuthAndPermission('manage_persona')) {
                return;
            }

            $id = $this->validateId($_GET['id'] ?? null, AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_INVALID_PERSONA_ID);

            $persona = $this->personaModel->find($id);
            if (!$persona) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_PERSONA_NOT_FOUND);
                return;
            }

            $estadosTipo = $this->getEstadosTipo();

            $this->render('personas/edit', [
                'persona' => $persona,
                'estadosTipo' => $estadosTipo,
                'error' => $_GET['error'] ?? ''
            ]);
        }, 'edit');
    }

    /**
     * Procesar actualización de persona
     */
    public function update()
    {
        return $this->executeWithErrorHandling(function() {
            // Verificar autenticación y permisos
            if (!$this->requireAuthAndPermission('manage_persona')) {
                return;
            }

            // Validar método POST y token CSRF centralizadamente
            $errors = $this->validatePostRequest();
            if (!empty($errors)) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, implode(', ', $errors));
                return;
            }

            $id = $this->validateId($_POST['id'] ?? null, AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_INVALID_PERSONA_ID);

            $validationErrors = $this->validatePersonaData($_POST, $id);
            if (!empty($validationErrors)) {
                $errorMsg = implode(', ', $validationErrors);
                Security::redirect("/personas/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            $personaData = [
                'rut' => Security::sanitizeInput($_POST['rut_clean'] ?? $_POST['rut']),
                'nombre' => Security::sanitizeInput($_POST['nombre']),
                'telefono' => Security::sanitizeInput($_POST['telefono'] ?? ''),
                'direccion' => Security::sanitizeInput($_POST['direccion'] ?? ''),
                'estado_tipo_id' => (int)$_POST['estado_tipo_id']
            ];

            if ($this->personaModel->update($id, $personaData)) {
                Security::logSecurityEvent('persona_updated', [
                    'persona_id' => $id,
                    'updated_by' => $_SESSION['username']
                ]);
                $this->redirectWithSuccess(AppConstants::ROUTE_PERSONAS, 'Persona actualizada correctamente');
            } else {
                Security::redirect("/personas/edit?id={$id}&error=Error al actualizar persona");
            }
        }, 'update');
    }

    /**
     * Eliminar persona (soft delete)
     */
    public function delete()
    {
        return $this->executeWithErrorHandling(function() {
            if (!$this->requireAuthAndPermission('manage_persona')) {
                return;
            }

            $errors = $this->validatePostRequest();
            if (!empty($errors)) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, implode(', ', $errors));
                return;
            }

            $id = $this->validateId($_POST['id'] ?? null, AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_INVALID_PERSONA_ID);

            if ($this->personaModel->delete($id)) {
                Security::logSecurityEvent('persona_deleted', [
                    'persona_id' => $id,
                    'deleted_by' => $_SESSION['username']
                ]);
                $this->redirectWithSuccess(AppConstants::ROUTE_PERSONAS, 'Persona eliminada correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_PERSONA_IN_USE);
            }
        }, 'delete');
    }

    /**
     * Mostrar/editar persona individual (singular) - Legacy
     */
    public function show($id = null)
    {
        return $this->executeWithErrorHandling(function() use ($id) {
            // Verificar autenticación y permisos
            if (!$this->requireAuthAndPermission('manage_persona')) {
                return;
            }

            if ($id) {
                // Redirigir al método edit
                Security::redirect("/personas/edit?id={$id}");
                return;
            } else {
                // Redirigir al método create
                $this->redirectToRoute(AppConstants::ROUTE_PERSONAS_CREATE);
                return;
            }
        }, 'show');
    }

    /**
     * Validar datos de persona
     */
    private function validatePersonaData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar RUT
        if (empty($data['rut'])) {
            $errors[] = 'El RUT es obligatorio';
        } elseif (!Security::validateRut($data['rut'])) {
            $errors[] = 'El RUT no es válido';
        } elseif ($this->personaModel->rutExists($data['rut'], $excludeId)) {
            $errors[] = 'El RUT ya está registrado';
        }

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['nombre']) > 150) {
            $errors[] = 'El nombre no puede tener más de 150 caracteres';
        }

        // Validar teléfono (opcional)
        if (!empty($data['telefono'])) {
            if (strlen($data['telefono']) > 20) {
                $errors[] = 'El teléfono no puede tener más de 20 caracteres';
            } elseif (!preg_match('/^[+]?[0-9\s\-\(\)]{8,15}$/', $data['telefono'])) {
                $errors[] = 'El formato del teléfono no es válido';
            }
        }

        // Validar dirección (opcional)
        if (!empty($data['direccion']) && strlen($data['direccion']) > 255) {
            $errors[] = 'La dirección no puede tener más de 255 caracteres';
        }

        // Validar estado
        if (empty($data['estado_tipo_id']) || !is_numeric($data['estado_tipo_id'])) {
            $errors[] = 'Debe seleccionar un estado válido';
        }

        return $errors;
    }

    /**
     * Obtener tipos de estado - Usa método heredado de AbstractBaseController
     */
    private function getEstadosTipo(): array
    {
        return parent::getEstadosTipo();
    }
}
