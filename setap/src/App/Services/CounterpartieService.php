<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Persona;
use Exception;

/**
 * CounterpartieService
 *
 * Servicio especializado en la lógica de negocio de contrapartes de clientes.
 * Centraliza las operaciones CRUD y lógica específica de contrapartes.
 */
class CounterpartieService
{
    private $clientModel;
    private $personaModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->personaModel = new Persona();
    }

    /**
     * Obtener todas las contrapartes con filtros
     *
     * @param array $filters Filtros a aplicar
     * @return array Lista de contrapartes
     */
    public function getAllCounterparties(array $filters = []): array
    {
        return $this->clientModel->getAllCounterparties($filters);
    }

    /**
     * Obtener contrapartes de un cliente específico
     *
     * @param int $clientId ID del cliente
     * @return array Lista de contrapartes del cliente
     */
    public function getClientCounterparties(int $clientId): array
    {
        return $this->clientModel->getCounterparties($clientId);
    }

    /**
     * Buscar una contraparte por ID
     *
     * @param int $id ID de la contraparte
     * @return array|null Datos de la contraparte o null si no existe
     */
    public function findCounterpartie(int $id): ?array
    {
        return $this->clientModel->findCounterpartie($id);
    }

    /**
     * Crear nueva contraparte
     *
     * @param array $data Datos de la contraparte
     * @return int ID de la contraparte creada
     * @throws Exception Si no se puede crear
     */
    public function createCounterpartie(array $data): int
    {
        $counterpartieId = $this->clientModel->addCounterpartie($data);

        if (!$counterpartieId) {
            throw new Exception('No se pudo crear la contraparte');
        }

        return $counterpartieId;
    }

    /**
     * Actualizar contraparte existente
     *
     * @param int $id ID de la contraparte
     * @param array $data Nuevos datos
     * @return bool True si se actualizó correctamente
     * @throws Exception Si no se puede actualizar
     */
    public function updateCounterpartie(int $id, array $data): bool
    {
        $success = $this->clientModel->updateCounterpartie($id, $data);

        if (!$success) {
            throw new Exception('No se pudo actualizar la contraparte');
        }

        return $success;
    }

    /**
     * Eliminar contraparte
     *
     * @param int $id ID de la contraparte
     * @return bool True si se eliminó correctamente
     * @throws Exception Si no se puede eliminar
     */
    public function deleteCounterpartie(int $id): bool
    {
        $success = $this->clientModel->deleteCounterpartie($id);

        if (!$success) {
            throw new Exception('No se pudo eliminar la contraparte');
        }

        return $success;
    }

    /**
     * Obtener datos necesarios para formularios de contrapartes
     *
     * @return array Datos para formularios (clientes, personas, estados)
     */
    public function getFormData(): array
    {
        return [
            'clients' => $this->clientModel->getAll(),
            'personas' => $this->personaModel->getAll(),
            'statusTypes' => $this->clientModel->getStatusTypes()
        ];
    }

    /**
     * Verificar si una combinación cliente-persona ya existe
     *
     * @param int $clientId ID del cliente
     * @param int $personaId ID de la persona
     * @param int|null $excludeId ID a excluir (para ediciones)
     * @return bool True si la combinación ya existe
     */
    public function counterpartieExists(int $clientId, int $personaId, ?int $excludeId = null): bool
    {
        return $this->clientModel->counterpartieExists($clientId, $personaId, $excludeId);
    }
}
