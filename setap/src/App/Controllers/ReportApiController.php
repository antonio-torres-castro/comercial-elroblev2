<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Logger;
use App\Services\ReportService;
use InvalidArgumentException;
use Throwable;

class ReportApiController extends BaseController
{
    private ?ReportService $reportService = null;

    public function __construct()
    {
    }

    public function projectTasks(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->apiError('METHOD_NOT_ALLOWED', 'Metodo no permitido. Use POST.', 405);
            }

            $payload = $this->readPayload();
            $credentials = $this->resolveCredentials($payload);
            $filters = $this->validateProjectTasksFilters($payload);

            $this->reportService = new ReportService(Database::getInstance());

            $auth = $this->reportService->authenticateApiConsumer($credentials['usuario'], $credentials['clave']);
            if (!$auth['success']) {
                $this->apiError($auth['code'], $auth['message'], $auth['status']);
            }

            $result = $this->reportService->getProjectTasksForApi($auth['user'], $filters);
            if (!$result['success']) {
                $this->apiError($result['code'], $result['message'], $result['status']);
            }

            $this->apiResponse([
                'success' => true,
                'message' => 'Tareas recuperadas correctamente.',
                'data' => $result['data'],
                'meta' => $result['meta']
            ]);
        } catch (InvalidArgumentException $e) {
            $this->apiError('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (Throwable $e) {
            Logger::error("ReportApiController::projectTasks: " . $e->getMessage());
            if (stripos($e->getMessage(), 'bd') !== false || stripos($e->getMessage(), 'database') !== false || stripos($e->getMessage(), 'conex') !== false) {
                $this->apiError('DATABASE_UNAVAILABLE', 'No fue posible conectar con la base de datos.', 503);
            }
            $this->apiError('INTERNAL_SERVER_ERROR', 'Ocurrio un error inesperado en el servicio.', 500);
        }
    }

    private function readPayload(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $rawBody = file_get_contents('php://input') ?: '';

        if (stripos($contentType, 'application/json') !== false && trim($rawBody) !== '') {
            $payload = json_decode($rawBody, true);
            if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('El cuerpo JSON no es valido.');
            }
            return $payload;
        }

        return array_merge($_GET, $_POST);
    }

    private function resolveCredentials(array $payload): array
    {
        $usuario = $payload['usuario'] ?? $payload['username'] ?? $payload['email'] ?? null;
        $clave = $payload['clave'] ?? $payload['password'] ?? null;

        if (
            (empty($usuario) || empty($clave))
            && isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])
        ) {
            $usuario = $_SERVER['PHP_AUTH_USER'];
            $clave = $_SERVER['PHP_AUTH_PW'];
        }

        if (empty($usuario) || empty($clave)) {
            $basicAuth = $this->parseBasicAuthorizationHeader();
            if ($basicAuth !== null) {
                $usuario = $basicAuth['usuario'];
                $clave = $basicAuth['clave'];
            }
        }

        if (!is_string($usuario) || trim($usuario) === '') {
            throw new InvalidArgumentException('El parametro usuario es requerido.');
        }

        if (!is_string($clave) || $clave === '') {
            throw new InvalidArgumentException('El parametro clave es requerido.');
        }

        return [
            'usuario' => trim($usuario),
            'clave' => $clave
        ];
    }

    private function parseBasicAuthorizationHeader(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!is_string($header) || stripos($header, 'Basic ') !== 0) {
            return null;
        }

        $decoded = base64_decode(substr($header, 6), true);
        if (!is_string($decoded) || strpos($decoded, ':') === false) {
            return null;
        }

        [$usuario, $clave] = explode(':', $decoded, 2);
        return [
            'usuario' => $usuario,
            'clave' => $clave
        ];
    }

    private function validateProjectTasksFilters(array $payload): array
    {
        $projectId = $payload['proyecto_id'] ?? $payload['project_id'] ?? null;
        $dateFrom = $payload['fecha_desde'] ?? $payload['date_from'] ?? null;
        $dateTo = $payload['fecha_hasta'] ?? $payload['date_to'] ?? null;
        $limit = $payload['limit'] ?? 100;
        $offset = $payload['offset'] ?? 0;

        if (filter_var($projectId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new InvalidArgumentException('El parametro proyecto_id debe ser un entero mayor que cero.');
        }

        if (!$this->isDate($dateFrom)) {
            throw new InvalidArgumentException('El parametro fecha_desde debe tener formato YYYY-MM-DD.');
        }

        if (!$this->isDate($dateTo)) {
            throw new InvalidArgumentException('El parametro fecha_hasta debe tener formato YYYY-MM-DD.');
        }

        if ($dateFrom > $dateTo) {
            throw new InvalidArgumentException('fecha_desde no puede ser mayor que fecha_hasta.');
        }

        if (filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new InvalidArgumentException('El parametro limit debe ser un entero mayor que cero.');
        }

        if (filter_var($offset, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            throw new InvalidArgumentException('El parametro offset debe ser un entero mayor o igual que cero.');
        }

        return [
            'proyecto_id' => (int)$projectId,
            'fecha_desde' => $dateFrom,
            'fecha_hasta' => $dateTo,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ];
    }

    private function isDate($date): bool
    {
        if (!is_string($date)) {
            return false;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    private function apiResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function apiError(string $code, string $message, int $statusCode): void
    {
        $this->apiResponse([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ], $statusCode);
    }
}
