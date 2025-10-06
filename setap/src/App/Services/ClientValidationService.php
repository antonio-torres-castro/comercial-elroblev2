<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Persona;
use App\Controllers\BaseController;

/**
 * ClientValidationService
 *
 * Servicio especializado en la validación de datos de clientes y contrapartes.
 * Centraliza toda la lógica de validación que anteriormente estaba en ClientController.
 */
class ClientValidationService extends BaseController
{
    private $clientModel;
    private $personaModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->personaModel = new Persona();
    }

    /**
     * Validar datos de cliente
     *
     * @param array $data Datos del cliente a validar
     * @param int|null $excludeId ID del cliente a excluir (para ediciones)
     * @return array Lista de errores encontrados
     */
    public function validateClientData(array $data, int $excludeId = null): array
    {
        $errors = [];

        // Razón social requerida
        if (empty($data['razon_social'])) {
            $errors[] = 'La razón social es requerida';
        } elseif (strlen($data['razon_social']) > 150) {
            $errors[] = 'La razón social no puede exceder 150 caracteres';
        }

        // Validar RUT si se proporciona
        if (!empty($data['rut'])) {
            if (!$this->clientModel->validateRut($data['rut'])) {
                $errors[] = 'El formato del RUT es inválido';
            } elseif ($this->clientModel->rutExists($data['rut'], $excludeId)) {
                $errors[] = 'El RUT ya está registrado para otro cliente';
            }

            if (strlen($data['rut']) > 20) {
                $errors[] = 'El RUT no puede exceder 20 caracteres';
            }
        }

        // Validar email si se proporciona
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El formato del email es inválido';
            } elseif (strlen($data['email']) > 150) {
                $errors[] = 'El email no puede exceder 150 caracteres';
            }
        }

        // Validar longitudes
        if (!empty($data['direccion']) && strlen($data['direccion']) > 255) {
            $errors[] = 'La dirección no puede exceder 255 caracteres';
        }

        if (!empty($data['telefono']) && strlen($data['telefono']) > 20) {
            $errors[] = 'El teléfono no puede exceder 20 caracteres';
        }

        // Validar fechas
        if (!empty($data['fecha_inicio_contrato']) && !$this->isValidDate($data['fecha_inicio_contrato'])) {
            $errors[] = 'La fecha de inicio de contrato no es válida';
        }

        if (!empty($data['fecha_facturacion']) && !$this->isValidDate($data['fecha_facturacion'])) {
            $errors[] = 'La fecha de facturación no es válida';
        }

        if (!empty($data['fecha_termino_contrato']) && !$this->isValidDate($data['fecha_termino_contrato'])) {
            $errors[] = 'La fecha de término de contrato no es válida';
        }

        // Validar que fecha de término sea posterior a fecha de inicio
        if (!empty($data['fecha_inicio_contrato']) && !empty($data['fecha_termino_contrato'])) {
            if (strtotime($data['fecha_termino_contrato']) <= strtotime($data['fecha_inicio_contrato'])) {
                $errors[] = 'La fecha de término debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }

    /**
     * Validar datos de contraparte
     *
     * @param array $data Datos de la contraparte a validar
     * @param int|null $excludeId ID de la contraparte a excluir (para ediciones)
     * @return array Lista de errores encontrados
     */
    public function validateCounterpartieData(array $data, int $excludeId = null): array
    {
        $errors = [];

        // Cliente requerido
        if (empty($data['cliente_id'])) {
            $errors[] = 'El cliente es requerido';
        } elseif (!is_numeric($data['cliente_id'])) {
            $errors[] = 'El cliente seleccionado no es válido';
        } else {
            // Verificar que el cliente existe
            $client = $this->clientModel->find((int)$data['cliente_id']);
            if (!$client) {
                $errors[] = 'El cliente seleccionado no existe';
            }
        }

        // Persona requerida
        if (empty($data['persona_id'])) {
            $errors[] = 'La persona es requerida';
        } elseif (!is_numeric($data['persona_id'])) {
            $errors[] = 'La persona seleccionada no es válida';
        } else {
            // Verificar que la persona existe
            $persona = $this->personaModel->find((int)$data['persona_id']);
            if (!$persona) {
                $errors[] = 'La persona seleccionada no existe';
            }
        }

        // Verificar que la combinación cliente-persona no exista ya
        if (!empty($data['cliente_id']) && !empty($data['persona_id'])) {
            if ($this->clientModel->counterpartieExists((int)$data['cliente_id'], (int)$data['persona_id'], $excludeId)) {
                $errors[] = 'Esta persona ya es contraparte de este cliente';
            }
        }

        // Validar cargo si se proporciona
        if (!empty($data['cargo']) && strlen($data['cargo']) > 100) {
            $errors[] = 'El cargo no puede exceder 100 caracteres';
        }

        // Validar teléfono si se proporciona
        if (!empty($data['telefono']) && strlen($data['telefono']) > 20) {
            $errors[] = 'El teléfono no puede exceder 20 caracteres';
        }

        // Validar email si se proporciona
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El formato del email es inválido';
            } elseif (strlen($data['email']) > 150) {
                $errors[] = 'El email no puede exceder 150 caracteres';
            }
        }

        // Estado requerido
        if (empty($data['estado_tipo_id'])) {
            $errors[] = 'El estado es requerido';
        } elseif (!is_numeric($data['estado_tipo_id'])) {
            $errors[] = 'El estado seleccionado no es válido';
        }

        return $errors;
    }
}
