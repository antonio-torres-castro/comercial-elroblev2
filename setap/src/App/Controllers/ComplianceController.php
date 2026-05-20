<?php

namespace App\Controllers;

use App\Constants\AppConstants;
use App\Core\ViewRenderer;
use App\Helpers\Logger;
use App\Helpers\Security;
use App\Middlewares\AuthMiddleware;
use App\Models\Compliance;
use App\Services\PermissionService;
use Exception;

class ComplianceController extends BaseController
{
    private Compliance $complianceModel;
    private PermissionService $permissionService;
    private ViewRenderer $viewRenderer;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->complianceModel = new Compliance();
        $this->permissionService = new PermissionService();
        $this->viewRenderer = new ViewRenderer();
    }

    public function index(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_compliance')) {
                return;
            }

            $filters = [
                'search' => trim($_GET['search'] ?? ''),
                'estado_tipo_id' => (int)($_GET['estado_tipo_id'] ?? 0),
                'exclude_deleted' => !isset($_GET['include_deleted']),
            ];
            if (!empty($currentUser['proveedor_id'])) {
                $filters['proveedor_id'] = (int)$currentUser['proveedor_id'];
            }

            $documents = $this->complianceModel->getDocuments($filters);
            $assignments = !empty($currentUser['proveedor_id'])
                ? $this->complianceModel->getAdminAssignments((int)$currentUser['proveedor_id'])
                : [];

            $data = [
                'user' => $currentUser,
                'documents' => $documents,
                'assignments' => $assignments,
                'filters' => $filters,
                'title' => 'Administrar Cumplimientos',
                'success' => $this->consumeFlashMessage('compliance_success'),
                'error' => $this->consumeFlashMessage('compliance_error'),
            ];

            require_once __DIR__ . '/../Views/compliance/list.php';
        } catch (Exception $e) {
            Logger::error("ComplianceController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError($this->formatExceptionMessage($e));
        }
    }

    public function compliances(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'my_compliance')) {
                return;
            }

            $items = $this->complianceModel->getUserCompliances(
                (int)$currentUser['id'],
                (int)$currentUser['proveedor_id']
            );

            $data = [
                'user' => $currentUser,
                'items' => $items,
                'title' => 'Mis Cumplimientos',
                'success' => $this->consumeFlashMessage('compliance_success'),
                'error' => $this->consumeFlashMessage('compliance_error'),
            ];

            require_once __DIR__ . '/../Views/compliance/my.php';
        } catch (Exception $e) {
            Logger::error("ComplianceController::compliances: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError($this->formatExceptionMessage($e));
        }
    }

    public function assessments(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_assessments')) {
                return;
            }

            $filters = ['exclude_deleted' => true];
            if (!empty($currentUser['proveedor_id'])) {
                $filters['proveedor_id'] = (int)$currentUser['proveedor_id'];
            }

            $documents = $this->complianceModel->getDocuments($filters);
            $selectedVersionId = (int)($_GET['version_id'] ?? 0);
            $selectedDocumentId = (int)($_GET['document_id'] ?? 0);

            if ($selectedVersionId <= 0 && $selectedDocumentId > 0) {
                $version = $this->complianceModel->getPublishedVersionByDocument($selectedDocumentId, (int)$currentUser['proveedor_id']);
                $selectedVersionId = $version ? (int)$version['id'] : 0;
            }

            $selectedVersion = $selectedVersionId > 0
                ? $this->complianceModel->getVersion($selectedVersionId, (int)$currentUser['proveedor_id'])
                : null;
            $questions = $selectedVersionId > 0 ? $this->complianceModel->getQuestions($selectedVersionId) : [];

            $data = [
                'user' => $currentUser,
                'documents' => $documents,
                'selectedVersion' => $selectedVersion,
                'questions' => $questions,
                'title' => 'Administrar Evaluaciones',
                'success' => $this->consumeFlashMessage('compliance_success'),
                'error' => $this->consumeFlashMessage('compliance_error'),
            ];

            require_once __DIR__ . '/../Views/compliance/assessments.php';
        } catch (Exception $e) {
            Logger::error("ComplianceController::assessments: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError($this->formatExceptionMessage($e));
        }
    }

    public function history(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'compliance_history')) {
                return;
            }

            $filters = [
                'fecha_inicio' => trim($_GET['fecha_inicio'] ?? date('Y-m-01')),
                'fecha_fin' => trim($_GET['fecha_fin'] ?? date('Y-m-d')),
            ];
            if (!empty($currentUser['proveedor_id'])) {
                $filters['proveedor_id'] = (int)$currentUser['proveedor_id'];
            }

            $data = [
                'user' => $currentUser,
                'history' => $this->complianceModel->getHistory($filters),
                'logs' => $this->complianceModel->getComplianceLogs($filters),
                'filters' => $filters,
                'title' => 'Historia',
            ];

            require_once __DIR__ . '/../Views/compliance/history.php';
        } catch (Exception $e) {
            Logger::error("ComplianceController::history: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError($this->formatExceptionMessage($e));
        }
    }

    public function viewDocument($id): void
    {
        try {
            $currentUser = $this->requireUser();
            $versionId = (int)$id;
            $version = $this->complianceModel->getVersion($versionId, (int)$currentUser['proveedor_id']);
            $isAdminPreview = $this->permissionService->hasMenuAccess((int)$currentUser['id'], 'manage_compliance');
            if (!$version || ((int)$version['documento_estado_tipo_id'] !== 2 && !$isAdminPreview)) {
                $this->redirectComplianceError('/setap/compliance/my', 'Documento no disponible');
                return;
            }

            $readingId = $this->complianceModel->startReading(
                (int)$currentUser['id'],
                $versionId,
                (int)$currentUser['proveedor_id'],
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            $reading = $this->complianceModel->getReading($readingId, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);

            $data = [
                'user' => $currentUser,
                'version' => $version,
                'reading' => $reading,
                'title' => 'Lectura de Cumplimiento',
                'error' => $this->consumeFlashMessage('compliance_error'),
            ];

            require_once __DIR__ . '/../Views/compliance/document.php';
        } catch (Exception $e) {
            Logger::error("ComplianceController::viewDocument: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance/my', $e);
        }
    }

    public function startCompliance($versionId): void
    {
        $this->viewDocument($versionId);
    }

    public function evaluation($readingId): void
    {
        try {
            $currentUser = $this->requireUser();
            $reading = $this->complianceModel->getReading((int)$readingId, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            if (!$reading || empty($reading['password_confirmado'])) {
                $this->redirectComplianceError('/setap/compliance/my', 'Debe aceptar la lectura antes de evaluar');
                return;
            }

            if (!$this->complianceModel->canAttemptEvaluation((int)$currentUser['id'], (int)$readingId, (int)$currentUser['proveedor_id'])) {
                $this->redirectComplianceError('/setap/compliance/my', 'La evaluacion esta disponible entre 01:00 y 22:59. Un nuevo intento se habilita despues de las 01:00');
                return;
            }

            $version = $this->complianceModel->getVersion((int)$reading['cumplimiento_documento_version_id'], (int)$currentUser['proveedor_id']);
            $questions = $this->complianceModel->getQuestions((int)$reading['cumplimiento_documento_version_id'], true);

            $data = [
                'user' => $currentUser,
                'reading' => $reading,
                'version' => $version,
                'questions' => $questions,
                'title' => 'Evaluacion',
            ];

            require_once __DIR__ . '/../Views/compliance/evaluation.php';
        } catch (Exception $e) {
            Logger::error("ComplianceController::evaluation: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance/my', $e);
        }
    }

    public function submitEvaluation(): void
    {
        try {
            $currentUser = $this->requireUser();
            $this->validatePost();

            $readingId = (int)($_POST['reading_id'] ?? 0);
            $answers = $_POST['answers'] ?? [];
            if ($readingId <= 0 || !is_array($answers)) {
                throw new Exception('Datos de evaluacion invalidos');
            }

            $result = $this->complianceModel->submitEvaluation(
                $readingId,
                (int)$currentUser['id'],
                (int)$currentUser['proveedor_id'],
                $answers
            );

            $message = $result['aprobado']
                ? 'Evaluacion aprobada con ' . $result['puntaje'] . '%'
                : 'Evaluacion reprobada con ' . $result['puntaje'] . '%. Puede intentar nuevamente despues de las 01:00';

            $this->redirectComplianceSuccess('/setap/compliance/my', $message);
        } catch (Exception $e) {
            Logger::error("ComplianceController::submitEvaluation: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance/my', $e);
        }
    }

    public function acceptCompliance(): void
    {
        try {
            $currentUser = $this->requireUser();
            $this->validatePost();

            $readingId = (int)($_POST['reading_id'] ?? 0);
            $password = (string)($_POST['password'] ?? '');
            if ($readingId <= 0 || $password === '') {
                throw new Exception('Debe ingresar su contrasena para confirmar la lectura');
            }

            if (!$this->complianceModel->verifyPassword((int)$currentUser['id'], $password)) {
                throw new Exception('Contrasena incorrecta');
            }

            $this->complianceModel->acceptCompliance($readingId, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            $reading = $this->complianceModel->getReading($readingId, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);

            if ($reading && !empty($reading['requiere_evaluacion'])) {
                Security::redirect('/setap/compliance/evaluation/' . $readingId);
                return;
            }

            $this->redirectComplianceSuccess('/setap/compliance/my', 'Lectura aceptada correctamente');
        } catch (Exception $e) {
            Logger::error("ComplianceController::acceptCompliance: " . $e->getMessage());
            $readingId = (int)($_POST['reading_id'] ?? 0);
            $this->redirectComplianceError($readingId > 0 ? '/setap/compliance/document/' . ($_POST['version_id'] ?? '') : '/setap/compliance/my', $e);
        }
    }

    public function store(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_compliance')) {
                return;
            }
            $this->validatePost();

            $data = $this->documentPayload();
            $this->complianceModel->createDocument($data, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            $this->redirectComplianceSuccess('/setap/compliance', 'Cumplimiento creado en estado creado');
        } catch (Exception $e) {
            Logger::error("ComplianceController::store: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance', $e);
        }
    }

    public function update(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_compliance')) {
                return;
            }
            $this->validatePost();

            $documentId = (int)($_POST['document_id'] ?? 0);
            $this->complianceModel->updateDocument($documentId, $this->documentPayload(), (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            $this->redirectComplianceSuccess('/setap/compliance', 'Cumplimiento actualizado');
        } catch (Exception $e) {
            Logger::error("ComplianceController::update: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance', $e);
        }
    }

    public function changeStatus(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_compliance')) {
                return;
            }
            $this->validatePost();

            $documentId = (int)($_POST['document_id'] ?? 0);
            $stateId = (int)($_POST['estado_tipo_id'] ?? 0);
            $this->complianceModel->changeDocumentStatus($documentId, $stateId, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            $this->redirectComplianceSuccess('/setap/compliance', 'Estado actualizado');
        } catch (Exception $e) {
            Logger::error("ComplianceController::changeStatus: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance', $e);
        }
    }

    public function storeVersion(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_compliance')) {
                return;
            }
            $this->validatePost();

            $documentId = (int)($_POST['document_id'] ?? 0);
            $this->complianceModel->createVersion($documentId, [
                'version' => trim($_POST['version'] ?? ''),
                'titulo' => trim($_POST['titulo'] ?? ''),
                'resumen' => trim($_POST['resumen'] ?? ''),
                'contenido_html' => trim($_POST['contenido_html'] ?? ''),
                'publicado' => !empty($_POST['publicado']),
                'fecha_inicio_vigencia' => $_POST['fecha_inicio_vigencia'] ?? null,
                'fecha_fin_vigencia' => $_POST['fecha_fin_vigencia'] ?? null,
            ], (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            $this->redirectComplianceSuccess('/setap/compliance', 'Version creada');
        } catch (Exception $e) {
            Logger::error("ComplianceController::storeVersion: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance', $e);
        }
    }

    public function publishVersion(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_compliance')) {
                return;
            }
            $this->validatePost();

            $versionId = (int)($_POST['version_id'] ?? 0);
            $this->complianceModel->publishVersion($versionId, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            $this->redirectComplianceSuccess('/setap/compliance', 'Version publicada');
        } catch (Exception $e) {
            Logger::error("ComplianceController::publishVersion: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance', $e);
        }
    }

    public function storeQuestion(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_assessments')) {
                return;
            }
            $this->validatePost();

            $versionId = (int)($_POST['version_id'] ?? 0);
            $correctIndex = (int)($_POST['correct_alternative'] ?? -1);
            $alternatives = [];
            foreach (($_POST['alternatives'] ?? []) as $index => $text) {
                $alternatives[] = [
                    'texto' => trim((string)$text),
                    'correcta' => (int)$index === $correctIndex,
                ];
            }

            $this->complianceModel->createQuestion($versionId, [
                'pregunta' => trim($_POST['pregunta'] ?? ''),
                'orden_visualizacion' => (int)($_POST['orden_visualizacion'] ?? 1),
                'alternativas' => $alternatives,
            ], (int)$currentUser['proveedor_id']);

            $this->redirectComplianceSuccess('/setap/compliance/assessments?version_id=' . $versionId, 'Pregunta creada');
        } catch (Exception $e) {
            Logger::error("ComplianceController::storeQuestion: " . $e->getMessage());
            $versionId = (int)($_POST['version_id'] ?? 0);
            $this->redirectComplianceError('/setap/compliance/assessments?version_id=' . $versionId, $e);
        }
    }

    public function cleanupFlow(): void
    {
        try {
            $currentUser = $this->requireUser();
            if (!$this->canAccess($currentUser, 'manage_compliance')) {
                return;
            }
            $this->validatePost();
            $documentId = (int)($_POST['document_id'] ?? 0);
            $count = $this->complianceModel->cleanupUserFlowData($documentId, (int)$currentUser['id'], (int)$currentUser['proveedor_id']);
            $this->redirectComplianceSuccess('/setap/compliance', 'Registros de usuarios eliminados: ' . $count);
        } catch (Exception $e) {
            Logger::error("ComplianceController::cleanupFlow: " . $e->getMessage());
            $this->redirectComplianceError('/setap/compliance', $e);
        }
    }

    private function documentPayload(): array
    {
        $payload = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'codigo' => trim($_POST['codigo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'requiere_evaluacion' => !empty($_POST['requiere_evaluacion']) ? 1 : 0,
            'puntaje_minimo' => (float)($_POST['puntaje_minimo'] ?? 80),
            'cantidad_preguntas' => max(1, (int)($_POST['cantidad_preguntas'] ?? 1)),
            'vigencia_dias' => max(1, (int)($_POST['vigencia_dias'] ?? 365)),
            'version' => trim($_POST['version'] ?? '1.0'),
            'titulo' => trim($_POST['titulo'] ?? ''),
            'resumen' => trim($_POST['resumen'] ?? ''),
            'contenido_html' => trim($_POST['contenido_html'] ?? ''),
            'fecha_inicio_vigencia' => $_POST['fecha_inicio_vigencia'] ?? null,
            'fecha_fin_vigencia' => $_POST['fecha_fin_vigencia'] ?? null,
        ];

        if ($payload['nombre'] === '') {
            throw new Exception('El nombre del cumplimiento es obligatorio');
        }
        if ($payload['puntaje_minimo'] < 0 || $payload['puntaje_minimo'] > 100) {
            throw new Exception('El puntaje minimo debe estar entre 0 y 100');
        }

        return $payload;
    }

    private function validatePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception(AppConstants::ERROR_METHOD_NOT_ALLOWED);
        }
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception(AppConstants::ERROR_INVALID_CSRF_TOKEN);
        }
    }

    private function requireUser(): array
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            $this->redirectToLogin();
            exit;
        }

        if (empty($currentUser['proveedor_id'])) {
            throw new Exception('El usuario debe tener proveedor asociado para usar Compliance');
        }

        return $currentUser;
    }

    private function canAccess(array $currentUser, string $menuName): bool
    {
        if (!$this->permissionService->hasMenuAccess((int)$currentUser['id'], $menuName)) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return false;
        }
        return true;
    }

    private function redirectComplianceSuccess(string $route, string $message): void
    {
        $_SESSION['compliance_success'] = $message;
        Security::redirect($route);
    }

    private function redirectComplianceError(string $route, Exception|string $error): void
    {
        $_SESSION['compliance_error'] = $error instanceof Exception
            ? $this->formatExceptionMessage($error)
            : $error;
        Security::redirect($route);
    }

    private function consumeFlashMessage(string $key): string
    {
        $message = (string)($_SESSION[$key] ?? '');
        unset($_SESSION[$key]);
        return $message;
    }

    private function formatExceptionMessage(Exception $e): string
    {
        $message = $e->getMessage();
        if ($message === '') {
            $message = get_class($e);
        }

        if ($e->getPrevious() && $e->getPrevious()->getMessage() !== '') {
            $message .= ' | Causa original: ' . $e->getPrevious()->getMessage();
        }

        return $message;
    }
}
