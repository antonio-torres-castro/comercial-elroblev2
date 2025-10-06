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
     * Renderizar mensaje de error de forma segura
     */
    public function renderError(string $message, int $code = 500): string
    {
        try {
            $errorData = [
                'error_message' => $message,
                'error_code' => $code,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Intentar usar el sistema de templates
            $viewPath = __DIR__ . '/../Views/errors/generic.php';
            $layoutPath = __DIR__ . '/../Views/layouts/error.php';
            
            if (file_exists($viewPath) && file_exists($layoutPath)) {
                // Extraer variables para las vistas
                extract($errorData);
                
                // Capturar contenido de la vista de error
                ob_start();
                include $viewPath;
                $content = ob_get_clean();
                
                // Renderizar con layout de error
                ob_start();
                include $layoutPath;
                return ob_get_clean();
            }
        } catch (Exception $e) {
            // Si todo falla, HTML básico para evitar recursión infinita
            error_log("Error en renderError: " . $e->getMessage());
        }
        
        // Fallback HTML simple sin dependencias externas
        $timestamp = date('Y-m-d H:i:s');
        return "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Error {$code}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
                .error-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
                .error-title { color: #d32f2f; font-size: 24px; margin-bottom: 15px; }
                .error-message { color: #666; margin-bottom: 20px; }
                .error-details { background: #f8f8f8; padding: 15px; border-radius: 4px; font-size: 14px; color: #888; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <h1 class='error-title'>Error {$code}</h1>
                <p class='error-message'>" . htmlspecialchars($message) . "</p>
                <div class='error-details'>
                    <strong>Timestamp:</strong> {$timestamp}<br>
                    <strong>Error Code:</strong> {$code}
                </div>
            </div>
        </body>
        </html>";
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
