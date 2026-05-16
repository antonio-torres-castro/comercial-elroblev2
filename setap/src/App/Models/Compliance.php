<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use DateTime;
use Exception;
use PDO;
use PDOException;

class Compliance
{
    private PDO $db;

    private const STATE_CREATED = 1;
    private const STATE_ACTIVE = 2;
    private const STATE_INACTIVE = 3;
    private const STATE_DELETED = 4;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getDocuments(array $filters = []): array
    {
        try {
            $params = [];
            $sql = "SELECT d.*,
                    et.nombre AS estado_nombre,
                    v.id AS version_id,
                    v.version,
                    v.titulo,
                    v.publicado,
                    v.fecha_publicacion,
                    v.fecha_inicio_vigencia,
                    v.fecha_fin_vigencia,
                    (SELECT COUNT(*)
                        FROM cumplimiento_lecturas l
                        INNER JOIN cumplimiento_documento_versiones lv ON lv.id = l.cumplimiento_documento_version_id
                        WHERE lv.cumplimiento_documento_id = d.id) AS lecturas_count,
                    (SELECT COUNT(*)
                        FROM cumplimiento_preguntas p
                        WHERE p.cumplimiento_documento_version_id = v.id
                        AND p.estado_tipo_id = 2) AS preguntas_activas
                FROM cumplimiento_documentos d
                INNER JOIN estado_tipos et ON et.id = d.estado_tipo_id
                LEFT JOIN cumplimiento_documento_versiones v
                    ON v.cumplimiento_documento_id = d.id AND v.publicado = 1
                WHERE 1 = 1";

            if (!empty($filters['proveedor_id'])) {
                $sql .= PHP_EOL . " AND d.proveedor_id = :proveedor_id";
                $params[':proveedor_id'] = (int)$filters['proveedor_id'];
            }

            if (!empty($filters['estado_tipo_id'])) {
                $sql .= PHP_EOL . " AND d.estado_tipo_id = :estado_tipo_id";
                $params[':estado_tipo_id'] = (int)$filters['estado_tipo_id'];
            } elseif (!empty($filters['exclude_deleted'])) {
                $sql .= PHP_EOL . " AND d.estado_tipo_id != 4";
            }

            if (!empty($filters['search'])) {
                $sql .= PHP_EOL . " AND (d.nombre LIKE :search OR d.codigo LIKE :search OR d.descripcion LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            $sql .= PHP_EOL . " ORDER BY d.fecha_modificacion DESC, d.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Compliance::getDocuments: " . $e->getMessage());
            return [];
        }
    }

    public function getDocument(int $id, ?int $proveedorId = null): ?array
    {
        try {
            $params = [':id' => $id];
            $sql = "SELECT d.*, et.nombre AS estado_nombre
                FROM cumplimiento_documentos d
                INNER JOIN estado_tipos et ON et.id = d.estado_tipo_id
                WHERE d.id = :id";

            if ($proveedorId !== null && $proveedorId > 0) {
                $sql .= " AND d.proveedor_id = :proveedor_id";
                $params[':proveedor_id'] = $proveedorId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Logger::error("Compliance::getDocument: " . $e->getMessage());
            return null;
        }
    }

    public function getVersion(int $versionId, ?int $proveedorId = null): ?array
    {
        try {
            $params = [':id' => $versionId];
            $sql = "SELECT v.*, d.nombre AS documento_nombre, d.codigo, d.descripcion,
                    d.requiere_evaluacion, d.puntaje_minimo, d.cantidad_preguntas,
                    d.vigencia_dias, d.estado_tipo_id AS documento_estado_tipo_id,
                    d.proveedor_id AS documento_proveedor_id
                FROM cumplimiento_documento_versiones v
                INNER JOIN cumplimiento_documentos d ON d.id = v.cumplimiento_documento_id
                WHERE v.id = :id";

            if ($proveedorId !== null && $proveedorId > 0) {
                $sql .= " AND d.proveedor_id = :proveedor_id";
                $params[':proveedor_id'] = $proveedorId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Logger::error("Compliance::getVersion: " . $e->getMessage());
            return null;
        }
    }

    public function getPublishedVersionByDocument(int $documentId, ?int $proveedorId = null): ?array
    {
        try {
            $params = [':document_id' => $documentId];
            $sql = "SELECT v.*
                FROM cumplimiento_documento_versiones v
                INNER JOIN cumplimiento_documentos d ON d.id = v.cumplimiento_documento_id
                WHERE v.cumplimiento_documento_id = :document_id
                AND v.publicado = 1";

            if ($proveedorId !== null && $proveedorId > 0) {
                $sql .= " AND d.proveedor_id = :proveedor_id";
                $params[':proveedor_id'] = $proveedorId;
            }

            $sql .= " ORDER BY v.fecha_publicacion DESC, v.id DESC LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Logger::error("Compliance::getPublishedVersionByDocument: " . $e->getMessage());
            return null;
        }
    }

    public function getVersions(int $documentId, ?int $proveedorId = null): array
    {
        try {
            $params = [':document_id' => $documentId];
            $sql = "SELECT v.*,
                    (SELECT COUNT(*) FROM cumplimiento_preguntas p WHERE p.cumplimiento_documento_version_id = v.id) AS preguntas_count
                FROM cumplimiento_documento_versiones v
                INNER JOIN cumplimiento_documentos d ON d.id = v.cumplimiento_documento_id
                WHERE v.cumplimiento_documento_id = :document_id";

            if ($proveedorId !== null && $proveedorId > 0) {
                $sql .= " AND d.proveedor_id = :proveedor_id";
                $params[':proveedor_id'] = $proveedorId;
            }

            $sql .= " ORDER BY v.fecha_creacion DESC, v.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Compliance::getVersions: " . $e->getMessage());
            return [];
        }
    }

    public function createDocument(array $data, int $userId, int $proveedorId): int
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO cumplimiento_documentos (
                    proveedor_id, nombre, codigo, descripcion, requiere_evaluacion,
                    puntaje_minimo, cantidad_preguntas, vigencia_dias, estado_tipo_id
                ) VALUES (
                    :proveedor_id, :nombre, :codigo, :descripcion, :requiere_evaluacion,
                    :puntaje_minimo, :cantidad_preguntas, :vigencia_dias, 1
                )");
            $stmt->execute([
                ':proveedor_id' => $proveedorId,
                ':nombre' => $data['nombre'],
                ':codigo' => $data['codigo'] ?: null,
                ':descripcion' => $data['descripcion'] ?: null,
                ':requiere_evaluacion' => !empty($data['requiere_evaluacion']) ? 1 : 0,
                ':puntaje_minimo' => $data['puntaje_minimo'],
                ':cantidad_preguntas' => $data['cantidad_preguntas'],
                ':vigencia_dias' => $data['vigencia_dias'],
            ]);

            $documentId = (int)$this->db->lastInsertId();

            if (!empty($data['contenido_html'])) {
                $this->createVersion($documentId, [
                    'version' => $data['version'] ?: '1.0',
                    'titulo' => $data['titulo'] ?: $data['nombre'],
                    'resumen' => $data['resumen'] ?? null,
                    'contenido_html' => $data['contenido_html'],
                    'publicado' => 0,
                    'fecha_inicio_vigencia' => $data['fecha_inicio_vigencia'] ?? null,
                    'fecha_fin_vigencia' => $data['fecha_fin_vigencia'] ?? null,
                ], $userId, $proveedorId, false);
            }

            $this->log($userId, 69, 'cumplimiento_documentos', $documentId);
            $this->db->commit();
            return $documentId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Logger::error("Compliance::createDocument: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateDocument(int $documentId, array $data, int $userId, int $proveedorId): bool
    {
        try {
            $document = $this->getDocument($documentId, $proveedorId);
            if (!$document) {
                throw new Exception('Cumplimiento no encontrado');
            }
            if ((int)$document['estado_tipo_id'] !== self::STATE_CREATED) {
                throw new Exception('Solo se pueden modificar cumplimientos en estado creado');
            }
            if ($this->hasUserData($documentId)) {
                throw new Exception('No se puede modificar un cumplimiento con datos de usuario asociados');
            }

            $stmt = $this->db->prepare("UPDATE cumplimiento_documentos
                SET nombre = :nombre,
                    codigo = :codigo,
                    descripcion = :descripcion,
                    requiere_evaluacion = :requiere_evaluacion,
                    puntaje_minimo = :puntaje_minimo,
                    cantidad_preguntas = :cantidad_preguntas,
                    vigencia_dias = :vigencia_dias,
                    fecha_modificacion = NOW()
                WHERE id = :id AND proveedor_id = :proveedor_id");

            $ok = $stmt->execute([
                ':nombre' => $data['nombre'],
                ':codigo' => $data['codigo'] ?: null,
                ':descripcion' => $data['descripcion'] ?: null,
                ':requiere_evaluacion' => !empty($data['requiere_evaluacion']) ? 1 : 0,
                ':puntaje_minimo' => $data['puntaje_minimo'],
                ':cantidad_preguntas' => $data['cantidad_preguntas'],
                ':vigencia_dias' => $data['vigencia_dias'],
                ':id' => $documentId,
                ':proveedor_id' => $proveedorId,
            ]);

            if ($ok) {
                $this->log($userId, 70, 'cumplimiento_documentos', $documentId);
            }

            return $ok;
        } catch (Exception $e) {
            Logger::error("Compliance::updateDocument: " . $e->getMessage());
            throw $e;
        }
    }

    public function changeDocumentStatus(int $documentId, int $stateId, int $userId, int $proveedorId): bool
    {
        try {
            if (!in_array($stateId, [self::STATE_CREATED, self::STATE_ACTIVE, self::STATE_INACTIVE, self::STATE_DELETED], true)) {
                throw new Exception('Estado no valido');
            }

            $document = $this->getDocument($documentId, $proveedorId);
            if (!$document) {
                throw new Exception('Cumplimiento no encontrado');
            }

            if ((int)$document['estado_tipo_id'] === self::STATE_DELETED && $stateId === self::STATE_CREATED && $this->hasUserData($documentId)) {
                throw new Exception('No se puede reciclar un cumplimiento eliminado con datos de usuario asociados');
            }

            if ($stateId === self::STATE_ACTIVE) {
                $published = $this->getPublishedVersionByDocument($documentId, $proveedorId);
                if (!$published) {
                    throw new Exception('Debe publicar una version antes de activar el cumplimiento');
                }
                $activeQuestions = $this->countActiveQuestions((int)$published['id']);
                if (!empty($document['requiere_evaluacion']) && $activeQuestions < (int)$document['cantidad_preguntas']) {
                    throw new Exception('La evaluacion debe tener todas las preguntas definidas antes de activar');
                }
            }

            $stmt = $this->db->prepare("UPDATE cumplimiento_documentos
                SET estado_tipo_id = :state_id, fecha_modificacion = NOW()
                WHERE id = :id AND proveedor_id = :proveedor_id");
            $ok = $stmt->execute([
                ':state_id' => $stateId,
                ':id' => $documentId,
                ':proveedor_id' => $proveedorId,
            ]);

            if ($ok) {
                $action = $stateId === self::STATE_DELETED ? 72 : 70;
                $this->log($userId, $action, 'cumplimiento_documentos', $documentId);
            }

            return $ok;
        } catch (Exception $e) {
            Logger::error("Compliance::changeDocumentStatus: " . $e->getMessage());
            throw $e;
        }
    }

    public function createVersion(int $documentId, array $data, int $userId, int $proveedorId, bool $ownTransaction = true): int
    {
        try {
            if ($ownTransaction) {
                $this->db->beginTransaction();
            }

            if (trim((string)($data['version'] ?? '')) === '' || trim((string)($data['titulo'] ?? '')) === '' || trim((string)($data['contenido_html'] ?? '')) === '') {
                throw new Exception('Version, titulo y contenido HTML son obligatorios');
            }

            $document = $this->getDocument($documentId, $proveedorId);
            if (!$document) {
                throw new Exception('Cumplimiento no encontrado');
            }

            $published = !empty($data['publicado']) ? 1 : 0;
            if ($published) {
                $this->unpublishVersions($documentId);
            }

            $hash = hash('sha256', $data['contenido_html']);
            $stmt = $this->db->prepare("INSERT INTO cumplimiento_documento_versiones (
                    cumplimiento_documento_id, version, titulo, resumen, contenido_html,
                    hash_documento, publicado, fecha_publicacion, fecha_inicio_vigencia,
                    fecha_fin_vigencia, creado_por_usuario_id, proveedor_id
                ) VALUES (
                    :document_id, :version, :titulo, :resumen, :contenido_html,
                    :hash_documento, :publicado, :fecha_publicacion, :fecha_inicio_vigencia,
                    :fecha_fin_vigencia, :user_id, :proveedor_id
                )");
            $stmt->execute([
                ':document_id' => $documentId,
                ':version' => $data['version'],
                ':titulo' => $data['titulo'],
                ':resumen' => $data['resumen'] ?: null,
                ':contenido_html' => $data['contenido_html'],
                ':hash_documento' => $hash,
                ':publicado' => $published,
                ':fecha_publicacion' => $published ? date('Y-m-d H:i:s') : null,
                ':fecha_inicio_vigencia' => $data['fecha_inicio_vigencia'] ?: null,
                ':fecha_fin_vigencia' => $data['fecha_fin_vigencia'] ?: null,
                ':user_id' => $userId,
                ':proveedor_id' => $proveedorId,
            ]);

            $versionId = (int)$this->db->lastInsertId();

            if ($published) {
                $this->log($userId, 71, 'cumplimiento_documento_versiones', $versionId);
            }

            if ($ownTransaction) {
                $this->db->commit();
            }

            return $versionId;
        } catch (Exception $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Logger::error("Compliance::createVersion: " . $e->getMessage());
            throw $e;
        }
    }

    public function publishVersion(int $versionId, int $userId, int $proveedorId): bool
    {
        try {
            $version = $this->getVersion($versionId, $proveedorId);
            if (!$version) {
                throw new Exception('Version no encontrada');
            }

            $this->db->beginTransaction();
            $this->unpublishVersions((int)$version['cumplimiento_documento_id']);
            $stmt = $this->db->prepare("UPDATE cumplimiento_documento_versiones
                SET publicado = 1, fecha_publicacion = NOW()
                WHERE id = :id AND proveedor_id = :proveedor_id");
            $ok = $stmt->execute([
                ':id' => $versionId,
                ':proveedor_id' => $proveedorId,
            ]);

            $this->log($userId, 71, 'cumplimiento_documento_versiones', $versionId);
            $this->db->commit();
            return $ok;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Logger::error("Compliance::publishVersion: " . $e->getMessage());
            throw $e;
        }
    }

    private function unpublishVersions(int $documentId): void
    {
        $stmt = $this->db->prepare("UPDATE cumplimiento_documento_versiones
            SET publicado = 0
            WHERE cumplimiento_documento_id = :document_id");
        $stmt->execute([':document_id' => $documentId]);
    }

    public function createQuestion(int $versionId, array $data, int $proveedorId): int
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO cumplimiento_preguntas (
                    cumplimiento_documento_version_id, pregunta, orden_visualizacion, estado_tipo_id, proveedor_id
                ) VALUES (:version_id, :pregunta, :orden, 2, :proveedor_id)");
            $stmt->execute([
                ':version_id' => $versionId,
                ':pregunta' => $data['pregunta'],
                ':orden' => $data['orden_visualizacion'],
                ':proveedor_id' => $proveedorId,
            ]);
            $questionId = (int)$this->db->lastInsertId();

            $validAlternatives = 0;
            $hasCorrect = false;
            foreach ($data['alternativas'] as $index => $alternative) {
                if (trim((string)$alternative['texto']) === '') {
                    continue;
                }
                $validAlternatives++;
                $hasCorrect = $hasCorrect || !empty($alternative['correcta']);
                $stmt = $this->db->prepare("INSERT INTO cumplimiento_pregunta_alternativas (
                        cumplimiento_pregunta_id, alternativa, es_correcta, orden_visualizacion, proveedor_id
                    ) VALUES (:question_id, :alternative, :correct, :order, :proveedor_id)");
                $stmt->execute([
                    ':question_id' => $questionId,
                    ':alternative' => $alternative['texto'],
                    ':correct' => !empty($alternative['correcta']) ? 1 : 0,
                    ':order' => $index + 1,
                    ':proveedor_id' => $proveedorId,
                ]);
            }

            if ($validAlternatives < 2 || !$hasCorrect) {
                throw new Exception('Debe ingresar al menos dos alternativas y marcar una correcta');
            }

            $this->db->commit();
            return $questionId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Logger::error("Compliance::createQuestion: " . $e->getMessage());
            throw $e;
        }
    }

    public function getQuestions(int $versionId, bool $activeOnly = false): array
    {
        try {
            $sql = "SELECT p.*, et.nombre AS estado_nombre
                FROM cumplimiento_preguntas p
                INNER JOIN estado_tipos et ON et.id = p.estado_tipo_id
                WHERE p.cumplimiento_documento_version_id = :version_id";
            if ($activeOnly) {
                $sql .= " AND p.estado_tipo_id = 2";
            }
            $sql .= " ORDER BY p.orden_visualizacion ASC, p.id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':version_id' => $versionId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($questions as &$question) {
                $question['alternativas'] = $this->getAlternatives((int)$question['id']);
            }

            return $questions;
        } catch (PDOException $e) {
            Logger::error("Compliance::getQuestions: " . $e->getMessage());
            return [];
        }
    }

    public function getAlternatives(int $questionId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT *
                FROM cumplimiento_pregunta_alternativas
                WHERE cumplimiento_pregunta_id = :question_id
                ORDER BY orden_visualizacion ASC, id ASC");
            $stmt->execute([':question_id' => $questionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Compliance::getAlternatives: " . $e->getMessage());
            return [];
        }
    }

    public function getAdminAssignments(int $proveedorId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT u.id AS usuario_id,
                    u.nombre_usuario,
                    p.nombre AS nombre_completo,
                    d.id AS documento_id,
                    d.nombre AS cumplimiento,
                    v.version,
                    l.fecha_inicio_lectura,
                    l.fecha_aceptacion,
                    l.puntaje_obtenido,
                    l.aprobado,
                    l.fecha_vencimiento
                FROM usuarios u
                INNER JOIN personas p ON p.id = u.persona_id
                CROSS JOIN cumplimiento_documentos d
                LEFT JOIN cumplimiento_documento_versiones v
                    ON v.cumplimiento_documento_id = d.id AND v.publicado = 1
                LEFT JOIN cumplimiento_lecturas l
                    ON l.id = (
                        SELECT l2.id
                        FROM cumplimiento_lecturas l2
                        WHERE l2.usuario_id = u.id
                        AND l2.cumplimiento_documento_version_id = v.id
                        ORDER BY l2.fecha_creacion DESC, l2.id DESC
                        LIMIT 1
                    )
                WHERE u.proveedor_id = :proveedor_id
                AND d.proveedor_id = :proveedor_id
                AND u.estado_tipo_id = 2
                AND d.estado_tipo_id = 2
                ORDER BY p.nombre ASC, d.nombre ASC, l.fecha_creacion DESC");
            $stmt->execute([':proveedor_id' => $proveedorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Compliance::getAdminAssignments: " . $e->getMessage());
            return [];
        }
    }

    public function getUserCompliances(int $userId, int $proveedorId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT d.*,
                    v.id AS version_id,
                    v.version,
                    v.titulo,
                    v.fecha_inicio_vigencia,
                    v.fecha_fin_vigencia,
                    l.id AS lectura_id,
                    l.fecha_inicio_lectura,
                    l.fecha_aceptacion,
                    l.password_confirmado,
                    l.puntaje_obtenido,
                    l.aprobado,
                    l.fecha_vencimiento,
                    l.fecha_modificacion AS lectura_fecha_modificacion
                FROM cumplimiento_documentos d
                INNER JOIN cumplimiento_documento_versiones v
                    ON v.cumplimiento_documento_id = d.id AND v.publicado = 1
                LEFT JOIN cumplimiento_lecturas l
                    ON l.id = (
                        SELECT l2.id
                        FROM cumplimiento_lecturas l2
                        WHERE l2.usuario_id = :user_id
                        AND l2.cumplimiento_documento_version_id = v.id
                        ORDER BY l2.fecha_creacion DESC, l2.id DESC
                        LIMIT 1
                    )
                WHERE d.estado_tipo_id = 2
                AND d.proveedor_id = :proveedor_id
                ORDER BY d.nombre ASC");
            $stmt->execute([
                ':user_id' => $userId,
                ':proveedor_id' => $proveedorId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Compliance::getUserCompliances: " . $e->getMessage());
            return [];
        }
    }

    public function startReading(int $userId, int $versionId, int $proveedorId, string $ip, string $userAgent): int
    {
        try {
            $existing = $this->getOpenReading($userId, $versionId);
            if ($existing) {
                return (int)$existing['id'];
            }

            $stmt = $this->db->prepare("INSERT INTO cumplimiento_lecturas (
                    usuario_id, cumplimiento_documento_version_id, fecha_inicio_lectura,
                    ip, user_agent, proveedor_id
                ) VALUES (:user_id, :version_id, NOW(), :ip, :user_agent, :proveedor_id)");
            $stmt->execute([
                ':user_id' => $userId,
                ':version_id' => $versionId,
                ':ip' => $ip,
                ':user_agent' => substr($userAgent, 0, 500),
                ':proveedor_id' => $proveedorId,
            ]);

            $readingId = (int)$this->db->lastInsertId();
            $this->log($userId, 65, 'cumplimiento_lecturas', $readingId);
            return $readingId;
        } catch (PDOException $e) {
            Logger::error("Compliance::startReading: " . $e->getMessage());
            throw $e;
        }
    }

    public function getOpenReading(int $userId, int $versionId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT *
                FROM cumplimiento_lecturas
                WHERE usuario_id = :user_id
                AND cumplimiento_documento_version_id = :version_id
                AND aprobado = 0
                ORDER BY fecha_creacion DESC, id DESC
                LIMIT 1");
            $stmt->execute([
                ':user_id' => $userId,
                ':version_id' => $versionId,
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Logger::error("Compliance::getOpenReading: " . $e->getMessage());
            return null;
        }
    }

    public function getReading(int $readingId, int $userId, int $proveedorId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT l.*, v.cumplimiento_documento_id, d.requiere_evaluacion,
                    d.vigencia_dias, d.puntaje_minimo, d.cantidad_preguntas
                FROM cumplimiento_lecturas l
                INNER JOIN cumplimiento_documento_versiones v ON v.id = l.cumplimiento_documento_version_id
                INNER JOIN cumplimiento_documentos d ON d.id = v.cumplimiento_documento_id
                WHERE l.id = :id
                AND l.usuario_id = :user_id
                AND d.proveedor_id = :proveedor_id");
            $stmt->execute([
                ':id' => $readingId,
                ':user_id' => $userId,
                ':proveedor_id' => $proveedorId,
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Logger::error("Compliance::getReading: " . $e->getMessage());
            return null;
        }
    }

    public function acceptCompliance(int $readingId, int $userId, int $proveedorId): bool
    {
        try {
            $reading = $this->getReading($readingId, $userId, $proveedorId);
            if (!$reading) {
                throw new Exception('Lectura no encontrada');
            }

            $aprobado = empty($reading['requiere_evaluacion']) ? 1 : 0;
            $fechaVencimiento = $aprobado ? date('Y-m-d', strtotime('+' . (int)$reading['vigencia_dias'] . ' days')) : null;

            $stmt = $this->db->prepare("UPDATE cumplimiento_lecturas
                SET fecha_fin_lectura = COALESCE(fecha_fin_lectura, NOW()),
                    fecha_aceptacion = NOW(),
                    password_confirmado = 1,
                    aprobado = :aprobado,
                    fecha_vencimiento = :fecha_vencimiento,
                    fecha_modificacion = NOW()
                WHERE id = :id AND usuario_id = :user_id AND proveedor_id = :proveedor_id");
            $ok = $stmt->execute([
                ':aprobado' => $aprobado,
                ':fecha_vencimiento' => $fechaVencimiento,
                ':id' => $readingId,
                ':user_id' => $userId,
                ':proveedor_id' => $proveedorId,
            ]);

            if ($ok) {
                $this->log($userId, 66, 'cumplimiento_lecturas', $readingId);
            }

            return $ok;
        } catch (Exception $e) {
            Logger::error("Compliance::acceptCompliance: " . $e->getMessage());
            throw $e;
        }
    }

    public function submitEvaluation(int $readingId, int $userId, int $proveedorId, array $answers): array
    {
        try {
            if (!$this->canAttemptEvaluation($userId, $readingId, $proveedorId)) {
                throw new Exception('La evaluacion solo esta disponible entre 01:00 y 22:59, y un intento reprobado habilita el siguiente desde las 01:00 del dia siguiente');
            }

            $reading = $this->getReading($readingId, $userId, $proveedorId);
            if (!$reading) {
                throw new Exception('Lectura no encontrada');
            }
            if (empty($reading['password_confirmado'])) {
                throw new Exception('Debe aceptar la lectura antes de iniciar la evaluacion');
            }

            $questions = $this->getQuestions((int)$reading['cumplimiento_documento_version_id'], true);
            $requiredCount = (int)$reading['cantidad_preguntas'];
            if (count($questions) < $requiredCount) {
                throw new Exception('La evaluacion no tiene todas las preguntas requeridas');
            }

            $this->db->beginTransaction();
            $delete = $this->db->prepare("DELETE FROM cumplimiento_respuestas_usuario WHERE cumplimiento_lectura_id = :reading_id");
            $delete->execute([':reading_id' => $readingId]);

            $correct = 0;
            $answered = 0;

            foreach ($questions as $question) {
                $questionId = (int)$question['id'];
                $alternativeId = (int)($answers[$questionId] ?? 0);
                if ($alternativeId <= 0) {
                    throw new Exception('Debe responder todas las preguntas');
                }

                $isCorrect = $this->isCorrectAlternative($questionId, $alternativeId);
                $answered++;
                if ($isCorrect) {
                    $correct++;
                }

                $insert = $this->db->prepare("INSERT INTO cumplimiento_respuestas_usuario (
                        cumplimiento_lectura_id, cumplimiento_pregunta_id,
                        cumplimiento_pregunta_alternativa_id, correcta, proveedor_id
                    ) VALUES (:reading_id, :question_id, :alternative_id, :correct, :proveedor_id)");
                $insert->execute([
                    ':reading_id' => $readingId,
                    ':question_id' => $questionId,
                    ':alternative_id' => $alternativeId,
                    ':correct' => $isCorrect ? 1 : 0,
                    ':proveedor_id' => $proveedorId,
                ]);
            }

            $score = $answered > 0 ? round(($correct / $answered) * 100, 2) : 0;
            $approved = $score >= (float)$reading['puntaje_minimo'];
            $fechaVencimiento = $approved ? date('Y-m-d', strtotime('+' . (int)$reading['vigencia_dias'] . ' days')) : null;

            $stmt = $this->db->prepare("UPDATE cumplimiento_lecturas
                SET puntaje_obtenido = :score,
                    aprobado = :approved,
                    fecha_vencimiento = :expiration,
                    fecha_modificacion = NOW()
                WHERE id = :reading_id");
            $stmt->execute([
                ':score' => $score,
                ':approved' => $approved ? 1 : 0,
                ':expiration' => $fechaVencimiento,
                ':reading_id' => $readingId,
            ]);

            $this->log($userId, $approved ? 67 : 68, 'cumplimiento_lecturas', $readingId);
            $this->db->commit();

            return [
                'correctas' => $correct,
                'total' => $answered,
                'puntaje' => $score,
                'aprobado' => $approved,
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Logger::error("Compliance::submitEvaluation: " . $e->getMessage());
            throw $e;
        }
    }

    private function isCorrectAlternative(int $questionId, int $alternativeId): bool
    {
        $stmt = $this->db->prepare("SELECT es_correcta
            FROM cumplimiento_pregunta_alternativas
            WHERE id = :alternative_id
            AND cumplimiento_pregunta_id = :question_id");
        $stmt->execute([
            ':alternative_id' => $alternativeId,
            ':question_id' => $questionId,
        ]);
        return (int)($stmt->fetchColumn() ?: 0) === 1;
    }

    public function canAttemptEvaluation(int $userId, int $readingId, int $proveedorId): bool
    {
        $now = new DateTime();
        $time = $now->format('H:i:s');
        if ($time >= '23:00:00' || $time < '01:00:00') {
            return false;
        }

        $reading = $this->getReading($readingId, $userId, $proveedorId);
        if (!$reading) {
            return false;
        }

        if ($reading['puntaje_obtenido'] !== null && empty($reading['aprobado'])) {
            $lastAttemptDate = !empty($reading['fecha_modificacion']) ? date('Y-m-d', strtotime($reading['fecha_modificacion'])) : null;
            if ($lastAttemptDate === date('Y-m-d')) {
                return false;
            }
        }

        $stmt = $this->db->prepare("SELECT COUNT(*)
            FROM cumplimiento_lecturas l
            WHERE l.usuario_id = :user_id
            AND l.cumplimiento_documento_version_id = :version_id
            AND DATE(l.fecha_modificacion) = CURDATE()
            AND l.puntaje_obtenido IS NOT NULL
            AND l.aprobado = 0
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':version_id' => $reading['cumplimiento_documento_version_id'],
        ]);

        return (int)$stmt->fetchColumn() === 0;
    }

    public function prepareNextAttempt(int $userId, int $versionId, int $proveedorId, string $ip, string $userAgent): int
    {
        $existing = $this->getOpenReading($userId, $versionId);
        if ($existing && !empty($existing['password_confirmado']) && $existing['puntaje_obtenido'] === null) {
            return (int)$existing['id'];
        }

        $stmt = $this->db->prepare("INSERT INTO cumplimiento_lecturas (
                usuario_id, cumplimiento_documento_version_id, fecha_inicio_lectura,
                fecha_fin_lectura, fecha_aceptacion, password_confirmado,
                ip, user_agent, proveedor_id
            ) VALUES (:user_id, :version_id, NOW(), NOW(), NOW(), 1, :ip, :user_agent, :proveedor_id)");
        $stmt->execute([
            ':user_id' => $userId,
            ':version_id' => $versionId,
            ':ip' => $ip,
            ':user_agent' => substr($userAgent, 0, 500),
            ':proveedor_id' => $proveedorId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getHistory(array $filters = []): array
    {
        try {
            $params = [];
            $sql = "SELECT l.*,
                    u.nombre_usuario,
                    p.nombre AS nombre_completo,
                    d.nombre AS cumplimiento,
                    d.codigo,
                    v.version,
                    v.titulo
                FROM cumplimiento_lecturas l
                INNER JOIN usuarios u ON u.id = l.usuario_id
                INNER JOIN personas p ON p.id = u.persona_id
                INNER JOIN cumplimiento_documento_versiones v ON v.id = l.cumplimiento_documento_version_id
                INNER JOIN cumplimiento_documentos d ON d.id = v.cumplimiento_documento_id
                WHERE 1 = 1";

            if (!empty($filters['proveedor_id'])) {
                $sql .= " AND d.proveedor_id = :proveedor_id";
                $params[':proveedor_id'] = (int)$filters['proveedor_id'];
            }
            if (!empty($filters['fecha_inicio'])) {
                $sql .= " AND l.fecha_creacion >= :fecha_inicio";
                $params[':fecha_inicio'] = $filters['fecha_inicio'] . ' 00:00:00';
            }
            if (!empty($filters['fecha_fin'])) {
                $sql .= " AND l.fecha_creacion <= :fecha_fin";
                $params[':fecha_fin'] = $filters['fecha_fin'] . ' 23:59:59';
            }

            $sql .= " ORDER BY l.fecha_creacion DESC, l.id DESC LIMIT 300";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Compliance::getHistory: " . $e->getMessage());
            return [];
        }
    }

    public function getComplianceLogs(array $filters = []): array
    {
        try {
            $params = [];
            $sql = "SELECT ul.*, a.nombre AS accion, tr.nombre AS tabla, u.nombre_usuario, p.nombre AS nombre_completo
                FROM usuario_logs ul
                INNER JOIN acciones a ON a.id = ul.accion_id
                LEFT JOIN tablas_referencia tr ON tr.id = ul.tabla_referencia_id
                LEFT JOIN usuarios u ON u.id = ul.usuario_id
                LEFT JOIN personas p ON p.id = u.persona_id
                WHERE ul.accion_id IN (65,66,67,68,69,70,71,72)";

            if (!empty($filters['proveedor_id'])) {
                $sql .= " AND (u.proveedor_id = :proveedor_id OR u.proveedor_id IS NULL)";
                $params[':proveedor_id'] = (int)$filters['proveedor_id'];
            }

            $sql .= " ORDER BY ul.fecha DESC LIMIT 200";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Compliance::getComplianceLogs: " . $e->getMessage());
            return [];
        }
    }

    public function verifyPassword(int $userId, string $password): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT clave_hash FROM usuarios WHERE id = :id AND estado_tipo_id = 2");
            $stmt->execute([':id' => $userId]);
            $hash = $stmt->fetchColumn();
            return $hash && password_verify($password, $hash);
        } catch (PDOException $e) {
            Logger::error("Compliance::verifyPassword: " . $e->getMessage());
            return false;
        }
    }

    public function cleanupUserFlowData(int $documentId, int $userId, int $proveedorId): int
    {
        try {
            $document = $this->getDocument($documentId, $proveedorId);
            if (!$document) {
                throw new Exception('Cumplimiento no encontrado');
            }

            $this->db->beginTransaction();

            $select = $this->db->prepare("SELECT l.id
                FROM cumplimiento_lecturas l
                INNER JOIN cumplimiento_documento_versiones v ON v.id = l.cumplimiento_documento_version_id
                WHERE v.cumplimiento_documento_id = :document_id");
            $select->execute([':document_id' => $documentId]);
            $readingIds = array_map('intval', $select->fetchAll(PDO::FETCH_COLUMN));

            if (!empty($readingIds)) {
                $placeholders = implode(',', array_fill(0, count($readingIds), '?'));
                $deleteAnswers = $this->db->prepare("DELETE FROM cumplimiento_respuestas_usuario WHERE cumplimiento_lectura_id IN ($placeholders)");
                $deleteAnswers->execute($readingIds);

                $deleteReadings = $this->db->prepare("DELETE FROM cumplimiento_lecturas WHERE id IN ($placeholders)");
                $deleteReadings->execute($readingIds);
            }

            $this->log($userId, 72, 'cumplimiento_documentos', $documentId);
            $this->db->commit();
            return count($readingIds);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Logger::error("Compliance::cleanupUserFlowData: " . $e->getMessage());
            throw $e;
        }
    }

    public function hasUserData(int $documentId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*)
            FROM cumplimiento_lecturas l
            INNER JOIN cumplimiento_documento_versiones v ON v.id = l.cumplimiento_documento_version_id
            WHERE v.cumplimiento_documento_id = :document_id");
        $stmt->execute([':document_id' => $documentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function countActiveQuestions(int $versionId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*)
            FROM cumplimiento_preguntas
            WHERE cumplimiento_documento_version_id = :version_id
            AND estado_tipo_id = 2");
        $stmt->execute([':version_id' => $versionId]);
        return (int)$stmt->fetchColumn();
    }

    private function getTableReferenceId(string $table): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM tablas_referencia WHERE nombre = :name LIMIT 1");
        $stmt->execute([':name' => $table]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function log(?int $userId, int $actionId, string $table, ?int $referenceId = null): void
    {
        try {
            $tableReferenceId = $this->getTableReferenceId($table);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt = $this->db->prepare("INSERT INTO usuario_logs (
                    usuario_id, accion_id, fecha, IP, tabla_referencia_id, referencia_id
                ) VALUES (
                    :user_id, :action_id, CURRENT_TIMESTAMP, :ip, :table_reference_id, :reference_id
                )");
            $stmt->execute([
                ':user_id' => $userId,
                ':action_id' => $actionId,
                ':ip' => $ip ?: '0.0.0.0',
                ':table_reference_id' => $tableReferenceId,
                ':reference_id' => $referenceId,
            ]);
        } catch (Exception $e) {
            Logger::error("Compliance::log: " . $e->getMessage());
        }
    }
}
