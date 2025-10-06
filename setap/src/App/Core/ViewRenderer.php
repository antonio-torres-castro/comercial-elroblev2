<?php

namespace App\Core;

use Exception;

/**
 * Servicio especializado en renderizado de vistas
 * Responsabilidad única: Gestionar la presentación de datos
 */
class ViewRenderer
{
    private array $data = [];
    private string $layoutPath = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->layoutPath = __DIR__ . '/../Views/layouts/';
    }

    /**
     * Asignar datos a la vista
     */
    public function setData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Asignar un dato específico
     */
    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Renderizar vista con layout
     */
    public function render(string $viewPath, array $data = [], string $layout = 'main'): string
    {
        try {
            // Combinar datos pasados como parámetro con los datos existentes
            $allData = array_merge($this->data, $data);
            
            // Extraer datos para que estén disponibles en la vista
            extract($allData);

            // Capturar contenido de la vista
            ob_start();
            $fullViewPath = __DIR__ . '/../Views/' . ltrim($viewPath, '/');
            
            if (file_exists($fullViewPath)) {
                include $fullViewPath;
            } else {
                throw new Exception("Vista no encontrada: {$fullViewPath}");
            }
            
            $content = ob_get_clean();

            // Si no se especifica layout, devolver solo el contenido
            if (empty($layout)) {
                return $content;
            }

            // Renderizar con layout
            $layoutFile = $this->layoutPath . $layout . '.php';
            if (file_exists($layoutFile)) {
                ob_start();
                include $layoutFile;
                return ob_get_clean();
            } else {
                throw new Exception("Layout no encontrado: {$layoutFile}");
            }

        } catch (Exception $e) {
            error_log("Error renderizando vista: " . $e->getMessage());
            return $this->renderError("Error al cargar la vista");
        }
    }

    /**
     * Renderizar vista parcial (sin layout)
     */
    public function renderPartial(string $viewPath): string
    {
        return $this->render($viewPath, []);
    }

    /**
     * Renderizar vista con datos específicos
     */
    public function renderWith(string $viewPath, array $data, string $layout = 'main'): string
    {
        return $this->setData($data)->render($viewPath, [], $layout);
    }

    /**
     * Renderizar mensaje de error
     */
    public function renderError(string $message, int $code = 500): string
    {
        $errorData = [
            'error_message' => $message,
            'error_code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->renderWith('errors/generic.php', $errorData, 'error');
    }

    /**
     * Renderizar mensaje de éxito
     */
    public function renderSuccess(string $message, array $data = []): string
    {
        $successData = array_merge($data, [
            'success_message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return $this->renderWith('messages/success.php', $successData);
    }

    /**
     * Renderizar tabla de datos
     */
    public function renderTable(array $data, array $columns, array $options = []): string
    {
        $tableData = [
            'data' => $data,
            'columns' => $columns,
            'options' => array_merge([
                'striped' => true,
                'bordered' => true,
                'hover' => true,
                'responsive' => true,
                'pagination' => false
            ], $options)
        ];

        return $this->renderPartial('components/table.php');
    }

    /**
     * Renderizar formulario
     */
    public function renderForm(array $fields, array $options = []): string
    {
        $formData = [
            'fields' => $fields,
            'options' => array_merge([
                'method' => 'POST',
                'class' => 'needs-validation',
                'novalidate' => true,
                'csrf' => true
            ], $options)
        ];

        return $this->renderWith('components/form.php', $formData);
    }

    /**
     * Renderizar paginación
     */
    public function renderPagination(int $currentPage, int $totalPages, string $baseUrl, array $params = []): string
    {
        $paginationData = [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'base_url' => $baseUrl,
            'params' => $params,
            'show_numbers' => 5 // Cantidad de números de página a mostrar
        ];

        return $this->renderWith('components/pagination.php', $paginationData);
    }

    /**
     * Renderizar breadcrumb
     */
    public function renderBreadcrumb(array $items): string
    {
        $breadcrumbData = ['items' => $items];
        return $this->renderWith('components/breadcrumb.php', $breadcrumbData);
    }

    /**
     * Renderizar alert/mensaje
     */
    public function renderAlert(string $message, string $type = 'info', bool $dismissible = true): string
    {
        $alertData = [
            'message' => $message,
            'type' => $type, // success, danger, warning, info
            'dismissible' => $dismissible
        ];

        return $this->renderWith('components/alert.php', $alertData);
    }

    /**
     * Renderizar modal
     */
    public function renderModal(string $id, string $title, string $content, array $options = []): string
    {
        $modalData = [
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'options' => array_merge([
                'size' => 'md', // sm, md, lg, xl
                'centered' => false,
                'scrollable' => false,
                'backdrop' => 'static'
            ], $options)
        ];

        return $this->renderWith('components/modal.php', $modalData);
    }

    /**
     * Renderizar respuesta JSON
     */
    public function renderJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Renderizar respuesta de error JSON
     */
    public function renderJsonError(string $message, int $statusCode = 400, array $details = []): void
    {
        $errorData = [
            'error' => true,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('c')
        ];

        $this->renderJson($errorData, $statusCode);
    }

    /**
     * Renderizar respuesta de éxito JSON
     */
    public function renderJsonSuccess(string $message, array $data = [], int $statusCode = 200): void
    {
        $successData = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];

        $this->renderJson($successData, $statusCode);
    }

    /**
     * Sanitizar output HTML
     */
    public function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Formatear fecha para display
     */
    public function formatDate(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        try {
            $dateTime = new \DateTime($date);
            return $dateTime->format($format);
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Truncar texto
     */
    public function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Formatear número
     */
    public function formatNumber(float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, ',', '.');
    }

    /**
     * Obtener datos asignados
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Limpiar datos
     */
    public function clearData(): self
    {
        $this->data = [];
        return $this;
    }
}
