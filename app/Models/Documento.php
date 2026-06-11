<?php

namespace App\Models;

use PDO;
use Exception;

require_once __DIR__ . '/Conexion.php';

class Documento
{
    private static function db()
    {
        return Conexion::getConexion();
    }

    public static function obtenerResumenPostulantes($periodoId = null, $buscar = '')
    {
        $where = [];
        $params = [];

        if (!empty($periodoId)) {
            $where[] = 'po.periodo_id = :periodo_id';
            $params[':periodo_id'] = (int)$periodoId;
        }

        $buscar = trim((string)$buscar);

        if ($buscar !== '') {
            $where[] = "(
                LOWER(pe.ci) LIKE LOWER(:buscar)
                OR LOWER(pe.nombres) LIKE LOWER(:buscar)
                OR LOWER(pe.apellidos) LIKE LOWER(:buscar)
                OR LOWER(CONCAT(pe.nombres, ' ', pe.apellidos)) LIKE LOWER(:buscar)
                OR LOWER(po.codigo) LIKE LOWER(:buscar)
            )";

            $params[':buscar'] = '%' . $buscar . '%';
        }

        $whereSql = '';

        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "
            SELECT
                po.id AS postulante_id,
                po.codigo AS codigo_postulante,
                po.estado_postulacion,
                po.fecha_registro,
                po.periodo_id,
                pe.ci,
                pe.nombres,
                pe.apellidos,
                c1.nombre AS carrera_principal,
                c2.nombre AS carrera_secundaria,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre,
                COUNT(d.id) AS total_requisitos,
                COALESCE(SUM(CASE WHEN d.estado_validacion = 'Aceptado' THEN 1 ELSE 0 END), 0) AS requisitos_aceptados,
                COALESCE(SUM(CASE WHEN d.estado_validacion = 'Pendiente' THEN 1 ELSE 0 END), 0) AS requisitos_pendientes,
                COALESCE(SUM(CASE WHEN d.estado_validacion = 'Observado' THEN 1 ELSE 0 END), 0) AS requisitos_observados
            FROM postulante po
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN periodo_academico pa ON pa.id = po.periodo_id
            LEFT JOIN documento d ON d.postulante_id = po.id
            {$whereSql}
            GROUP BY
                po.id,
                po.codigo,
                po.estado_postulacion,
                po.fecha_registro,
                po.periodo_id,
                pe.ci,
                pe.nombres,
                pe.apellidos,
                c1.nombre,
                c2.nombre,
                pa.codigo,
                pa.gestion,
                pa.semestre
            ORDER BY po.id ASC
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function buscarPostulantePorId($postulanteId)
    {
        $sql = "
            SELECT
                po.id,
                po.codigo,
                po.estado_postulacion,
                po.fecha_registro,
                po.colegio,
                po.ciudad,
                po.titulo_bachiller,
                po.periodo_id,
                pe.ci,
                pe.nombres,
                pe.apellidos,
                pe.email,
                pe.telefono,
                pe.direccion,
                c1.nombre AS carrera_principal,
                c2.nombre AS carrera_secundaria,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre
            FROM postulante po
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN periodo_academico pa ON pa.id = po.periodo_id
            WHERE po.id = :postulante_id
            LIMIT 1
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        $postulante = $stmt->fetch();

        return $postulante ?: null;
    }

    public static function obtenerPorPostulante($postulanteId)
    {
        $sql = "
            SELECT
                id,
                postulante_id,
                tipo_documento,
                archivo,
                estado_validacion,
                COALESCE(observacion, '') AS observacion
            FROM documento
            WHERE postulante_id = :postulante_id
            ORDER BY id ASC
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function buscarPorId($documentoId)
    {
        $sql = "
            SELECT
                id,
                postulante_id,
                tipo_documento,
                archivo,
                estado_validacion,
                COALESCE(observacion, '') AS observacion
            FROM documento
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':id', (int)$documentoId, PDO::PARAM_INT);
        $stmt->execute();

        $documento = $stmt->fetch();

        return $documento ?: null;
    }

    public static function actualizarEstado($documentoId, $estadoValidacion, $observacion = null)
    {
        if (!in_array($estadoValidacion, self::estadosValidos(), true)) {
            throw new Exception('El estado de validación seleccionado no es válido.');
        }

        $documento = self::buscarPorId($documentoId);

        if (!$documento) {
            throw new Exception('El requisito seleccionado no existe.');
        }

        if ($estadoValidacion === 'Observado' && trim((string)$observacion) === '') {
            throw new Exception('Debes registrar una observación cuando el requisito está observado.');
        }

        if ($estadoValidacion !== 'Observado') {
            $observacion = null;
        }

        $db = self::db();

        try {
            $db->beginTransaction();

            $sql = "
                UPDATE documento
                SET
                    estado_validacion = :estado_validacion,
                    observacion = :observacion
                WHERE id = :id
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':estado_validacion' => $estadoValidacion,
                ':observacion' => self::nullSiVacio($observacion),
                ':id' => (int)$documentoId,
            ]);

            self::actualizarEstadoPostulante($documento['postulante_id']);

            $db->commit();

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function obtenerResumenPorPostulante($postulanteId)
    {
        $sql = "
            SELECT
                COUNT(id) AS total_requisitos,
                COALESCE(SUM(CASE WHEN estado_validacion = 'Aceptado' THEN 1 ELSE 0 END), 0) AS requisitos_aceptados,
                COALESCE(SUM(CASE WHEN estado_validacion = 'Pendiente' THEN 1 ELSE 0 END), 0) AS requisitos_pendientes,
                COALESCE(SUM(CASE WHEN estado_validacion = 'Observado' THEN 1 ELSE 0 END), 0) AS requisitos_observados
            FROM documento
            WHERE postulante_id = :postulante_id
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    private static function actualizarEstadoPostulante($postulanteId)
    {
        $resumen = self::obtenerResumenPorPostulante($postulanteId);

        $total = (int)($resumen['total_requisitos'] ?? 0);
        $aceptados = (int)($resumen['requisitos_aceptados'] ?? 0);
        $observados = (int)($resumen['requisitos_observados'] ?? 0);

        if ($total > 0 && $aceptados === $total) {
            $nuevoEstado = 'Documentación validada';
        } elseif ($observados > 0) {
            $nuevoEstado = 'Documentación observada';
        } else {
            $nuevoEstado = 'Registrado';
        }

        $sql = "
            UPDATE postulante
            SET
                estado_postulacion = :estado_postulacion,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = self::db()->prepare($sql);

        return $stmt->execute([
            ':estado_postulacion' => $nuevoEstado,
            ':id' => (int)$postulanteId,
        ]);
    }

    public static function estadosValidos()
    {
        return [
            'Pendiente',
            'Aceptado',
            'Observado',
        ];
    }

    private static function nullSiVacio($valor)
    {
        $valor = trim((string)($valor ?? ''));

        return $valor === '' ? null : $valor;
    }
}