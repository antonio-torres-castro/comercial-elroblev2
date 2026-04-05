<?php

namespace App\Controllers;

use App\Models\Espacios;
use App\Models\Task;
use App\Helpers\Logger;
use App\Helpers\Security;
use App\Constants\AppConstants;
use Exception;

class EspaciosController extends BaseController
{
    private $espaciosModel;
    private $taskModel;

    public function __construct()
    {
        $this->espaciosModel = new Espacios();
        $this->taskModel = new Task();
    }

    /**
     * Vista principal de administración de espacios
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];
            $filters = [
                'current_usuario_tipo_id' => $uti,
                'current_usuario_id' => $currentUser['id']
            ];

            // Si es admin, puede ver proveedores para filtrar proyectos
            $suppliers = [];
            if ($uti == 1) {
                $suppliers = $this->taskModel->getSuppliers($filters);
            } else {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            }

            $projects = $this->taskModel->getProjects($filters);
            $regiones = $this->espaciosModel->getRegiones();
            $tiposEspacio = $this->espaciosModel->getTiposEspacio();

            $data = [
                'user' => $currentUser,
                'title' => 'Administración de Espacios',
                'suppliers' => $suppliers,
                'projects' => $projects,
                'regiones' => $regiones,
                'tiposEspacio' => $tiposEspacio,
                'provider_id' => $_GET['proveedor_id'] ?? $currentUser['proveedor_id'] ?? 0
            ];

            require_once __DIR__ . '/../Views/espacios/index.php';
        } catch (Exception $e) {
            Logger::error("EspaciosController::index: " . $e->getMessage());
            echo $this->renderError("Error interno del servidor");
        }
    }

    /**
     * AJAX: Obtener direcciones por proyecto
     */
    public function getDirecciones()
    {
        try {
            $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
            if ($proyectoId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Proyecto no válido']);
                return;
            }

            $direcciones = $this->espaciosModel->getDireccionesByProyecto($proyectoId);
            echo json_encode(['success' => true, 'data' => $direcciones]);
        } catch (Exception $e) {
            Logger::error("EspaciosController::getDirecciones: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener direcciones']);
        }
    }

    /**
     * AJAX: Obtener espacios por dirección
     */
    public function getEspacios()
    {
        try {
            $direccionId = (int)($_GET['direccion_id'] ?? 0);
            if ($direccionId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dirección no válida']);
                return;
            }

            $espacios = $this->espaciosModel->getEspaciosByDireccion($direccionId);
            echo json_encode(['success' => true, 'data' => $espacios]);
        } catch (Exception $e) {
            Logger::error("EspaciosController::getEspacios: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener espacios']);
        }
    }

    public function getEspacioById()
    {
        try {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID no válido']);
                return;
            }

            $espacio = $this->espaciosModel->getEspacioById($id);
            if ($espacio) {
                echo json_encode(['success' => true, 'data' => $espacio]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Espacio no encontrado']);
            }
        } catch (Exception $e) {
            Logger::error("EspaciosController::getEspacioById: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener espacio']);
        }
    }

    /**
     * AJAX: Guardar nueva dirección
     */
    public function storeDireccion()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método no permitido");

            $data = [
                'proyecto_id' => (int)$_POST['proyecto_id'],
                'calle' => $_POST['calle'],
                'letra' => $_POST['letra'] ?? null,
                'numero' => !empty($_POST['numero']) ? (int)$_POST['numero'] : null,
                'ind_sin_numero' => isset($_POST['ind_sin_numero']) ? 1 : 0,
                'ind_localidad' => isset($_POST['ind_localidad']) ? 1 : 0,
                'localidad' => $_POST['localidad'] ?? null,
                'referencia' => $_POST['referencia'] ?? '',
                'lat' => !empty($_POST['lat']) ? (float)$_POST['lat'] : null,
                'lng' => !empty($_POST['lng']) ? (float)$_POST['lng'] : null,
                'comuna_id' => (int)$_POST['comuna_id']
            ];

            $id = $this->espaciosModel->createDireccion($data);
            if ($id > 0) {
                echo json_encode(['success' => true, 'message' => 'Dirección creada', 'id' => $id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear dirección']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Guardar/Actualizar espacio
     */
    public function storeEspacio()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método no permitido");

            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $data = [
                'direccion_id' => (int)$_POST['direccion_id'],
                'espacio_padre_id' => !empty($_POST['espacio_padre_id']) ? (int)$_POST['espacio_padre_id'] : null,
                'nombre' => $_POST['nombre'],
                'tipos_espacio_id' => (int)$_POST['tipos_espacio_id'],
                'codigo' => $_POST['codigo'] ?? null,
                'descripcion' => $_POST['descripcion'] ?? null,
                'nivel' => (int)$_POST['nivel'],
                'orden' => (int)($_POST['orden'] ?? 0)
            ];

            if ($id) $data['id'] = $id;

            // Validar duplicados
            if ($this->espaciosModel->existeEspacio($data)) {
                echo json_encode(['success' => false, 'message' => 'Ya existe un espacio con los mismos criterios (Nombre, Código, Nivel) en esta ubicación.']);
                return;
            }

            if ($id) {
                $success = $this->espaciosModel->updateEspacio($data);
                $message = $success ? 'Espacio actualizado' : 'Error al actualizar';
            } else {
                $newId = $this->espaciosModel->createEspacio($data);
                $success = $newId > 0;
                $message = $success ? 'Espacio creado' : 'Error al crear';
            }

            echo json_encode(['success' => $success, 'message' => $message]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Eliminar espacio
     */
    public function deleteEspacio()
    {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID no válido");

            $this->espaciosModel->deleteEspacio($id);
            echo json_encode(['success' => true, 'message' => 'Espacio eliminado']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Obtener provincias
     */
    public function getProvincias()
    {
        $id = (int)$_GET['region_id'];
        echo json_encode($this->espaciosModel->getProvincias($id));
    }

    /**
     * AJAX: Obtener comunas
     */
    public function getComunas()
    {
        $id = (int)$_GET['provincia_id'];
        echo json_encode($this->espaciosModel->getComunas($id));
    }
}
