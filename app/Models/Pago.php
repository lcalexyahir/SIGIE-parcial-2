<?php

namespace App\Models;

use PDO;
use Exception;

require_once __DIR__ . '/Conexion.php';

class Pago
{
    public const MONTO_OFICIAL_CUP = 700.00;

    private static function db()
    {
        return Conexion::getConexion();
    }

    public static function obtenerResumenPostulantes($periodoId = null, $estadoCuenta = '', $buscar = '')
    {
        $where = [];
        $params = [
            ':monto_oficial' => self::MONTO_OFICIAL_CUP,
        ];

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

        $estadoCuenta = trim((string)$estadoCuenta);

        if ($estadoCuenta !== '') {
            $where[] = "COALESCE(
                cc.estado,
                CASE
                    WHEN COALESCE(pg.total_pagado_aceptado, 0) >= :monto_oficial THEN 'Pagado'
                    WHEN COALESCE(pg.total_pagado_aceptado, 0) > 0 THEN 'Parcial'
                    ELSE 'Pendiente'
                END
            ) = :estado_cuenta";

            $params[':estado_cuenta'] = $estadoCuenta;
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

                cc.id AS cuenta_id,
                COALESCE(pg.total_pagado_aceptado, 0) AS total_pagado_aceptado,
                COALESCE(pg.total_pagos, 0) AS total_pagos,
                COALESCE(pg.pagos_pendientes, 0) AS pagos_pendientes,
                COALESCE(pg.pagos_aceptados, 0) AS pagos_aceptados,
                COALESCE(pg.pagos_rechazados, 0) AS pagos_rechazados,

                COALESCE(
                    cc.saldo,
                    GREATEST(:monto_oficial - COALESCE(pg.total_pagado_aceptado, 0), 0)
                ) AS saldo,

                COALESCE(
                    cc.estado,
                    CASE
                        WHEN COALESCE(pg.total_pagado_aceptado, 0) >= :monto_oficial THEN 'Pagado'
                        WHEN COALESCE(pg.total_pagado_aceptado, 0) > 0 THEN 'Parcial'
                        ELSE 'Pendiente'
                    END
                ) AS estado_cuenta

            FROM postulante po
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN periodo_academico pa ON pa.id = po.periodo_id
            LEFT JOIN cuenta_cobrar cc ON cc.postulante_id = po.id
            LEFT JOIN (
                SELECT
                    postulante_id,
                    COUNT(id) AS total_pagos,
                    COALESCE(SUM(CASE WHEN estado = 'Aceptado' THEN monto ELSE 0 END), 0) AS total_pagado_aceptado,
                    COALESCE(SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END), 0) AS pagos_pendientes,
                    COALESCE(SUM(CASE WHEN estado = 'Aceptado' THEN 1 ELSE 0 END), 0) AS pagos_aceptados,
                    COALESCE(SUM(CASE WHEN estado = 'Rechazado' THEN 1 ELSE 0 END), 0) AS pagos_rechazados
                FROM pago
                GROUP BY postulante_id
            ) pg ON pg.postulante_id = po.id
            {$whereSql}
            ORDER BY po.id ASC
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function obtenerPostulantesParaSelect()
    {
        $sql = "
            SELECT
                po.id,
                po.codigo,
                pe.ci,
                pe.nombres,
                pe.apellidos,
                c1.nombre AS carrera_principal,
                pa.codigo AS periodo_codigo
            FROM postulante po
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN periodo_academico pa ON pa.id = po.periodo_id
            ORDER BY po.id ASC
        ";

        $stmt = self::db()->query($sql);

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
                po.periodo_id,
                pe.ci,
                pe.nombres,
                pe.apellidos,
                pe.email,
                pe.telefono,
                c1.nombre AS carrera_principal,
                c2.nombre AS carrera_secundaria,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre,

                cc.id AS cuenta_id,
                cc.fecha_vencimiento,
                COALESCE(pg.total_pagado_aceptado, 0) AS total_pagado_aceptado,

                COALESCE(
                    cc.saldo,
                    GREATEST(:monto_oficial - COALESCE(pg.total_pagado_aceptado, 0), 0)
                ) AS saldo,

                COALESCE(
                    cc.estado,
                    CASE
                        WHEN COALESCE(pg.total_pagado_aceptado, 0) >= :monto_oficial THEN 'Pagado'
                        WHEN COALESCE(pg.total_pagado_aceptado, 0) > 0 THEN 'Parcial'
                        ELSE 'Pendiente'
                    END
                ) AS estado_cuenta

            FROM postulante po
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN periodo_academico pa ON pa.id = po.periodo_id
            LEFT JOIN cuenta_cobrar cc ON cc.postulante_id = po.id
            LEFT JOIN (
                SELECT
                    postulante_id,
                    COALESCE(SUM(CASE WHEN estado = 'Aceptado' THEN monto ELSE 0 END), 0) AS total_pagado_aceptado
                FROM pago
                GROUP BY postulante_id
            ) pg ON pg.postulante_id = po.id
            WHERE po.id = :postulante_id
            LIMIT 1
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            ':postulante_id' => (int)$postulanteId,
            ':monto_oficial' => self::MONTO_OFICIAL_CUP,
        ]);

        $postulante = $stmt->fetch();

        return $postulante ?: null;
    }

    public static function obtenerTiposPago()
    {
        $sql = "
            SELECT id, nombre, descripcion
            FROM tipo_pago
            ORDER BY id ASC
        ";

        $stmt = self::db()->query($sql);

        return $stmt->fetchAll();
    }

    public static function tipoPagoExiste($tipoPagoId)
    {
        $sql = "
            SELECT COUNT(*)
            FROM tipo_pago
            WHERE id = :id
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':id', (int)$tipoPagoId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function obtenerPagosPorPostulante($postulanteId)
    {
        $sql = "
            SELECT
                p.id,
                p.codigo,
                p.postulante_id,
                p.tipo_pago_id,
                tp.nombre AS tipo_pago,
                p.monto,
                p.fecha_pago,
                p.referencia,
                p.estado,
                p.comprobante
            FROM pago p
            LEFT JOIN tipo_pago tp ON tp.id = p.tipo_pago_id
            WHERE p.postulante_id = :postulante_id
            ORDER BY p.fecha_pago DESC, p.id DESC
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function buscarPagoPorId($pagoId)
    {
        $sql = "
            SELECT
                id,
                codigo,
                postulante_id,
                tipo_pago_id,
                monto,
                fecha_pago,
                referencia,
                estado,
                comprobante
            FROM pago
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':id', (int)$pagoId, PDO::PARAM_INT);
        $stmt->execute();

        $pago = $stmt->fetch();

        return $pago ?: null;
    }

    public static function registrarPago($datos)
    {
        $postulante = self::buscarPostulantePorId($datos['postulante_id']);

        if (!$postulante) {
            throw new Exception('El postulante seleccionado no existe.');
        }

        if (!self::tipoPagoExiste($datos['tipo_pago_id'])) {
            throw new Exception('El tipo de pago seleccionado no existe.');
        }

        if (!empty($datos['referencia']) && self::existeReferencia($datos['referencia'])) {
            throw new Exception('Ya existe un pago registrado con esa referencia.');
        }

        if (!in_array($datos['estado'], self::estadosPagoValidos(), true)) {
            throw new Exception('El estado del pago no es válido.');
        }

        $db = self::db();

        try {
            $db->beginTransaction();

            self::asegurarCuentaPorCobrarEnTransaccion($datos['postulante_id'], $db);

            $codigo = self::generarCodigoPago();

            $sql = "
                INSERT INTO pago (
                    codigo,
                    postulante_id,
                    tipo_pago_id,
                    monto,
                    fecha_pago,
                    referencia,
                    estado,
                    comprobante
                ) VALUES (
                    :codigo,
                    :postulante_id,
                    :tipo_pago_id,
                    :monto,
                    COALESCE(NULLIF(:fecha_pago, '')::timestamp, CURRENT_TIMESTAMP),
                    :referencia,
                    :estado,
                    :comprobante
                )
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':codigo' => $codigo,
                ':postulante_id' => (int)$datos['postulante_id'],
                ':tipo_pago_id' => (int)$datos['tipo_pago_id'],
                ':monto' => (float)$datos['monto'],
                ':fecha_pago' => self::normalizarFechaHora($datos['fecha_pago'] ?? ''),
                ':referencia' => self::nullSiVacio($datos['referencia'] ?? null),
                ':estado' => $datos['estado'],
                ':comprobante' => self::nullSiVacio($datos['comprobante'] ?? null),
            ]);

            self::recalcularCuentaPorPostulanteEnTransaccion($datos['postulante_id'], $db);

            $db->commit();

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function actualizarEstadoPago($pagoId, $estado)
    {
        if (!in_array($estado, self::estadosPagoValidos(), true)) {
            throw new Exception('El estado del pago no es válido.');
        }

        $pago = self::buscarPagoPorId($pagoId);

        if (!$pago) {
            throw new Exception('El pago seleccionado no existe.');
        }

        $db = self::db();

        try {
            $db->beginTransaction();

            $sql = "
                UPDATE pago
                SET estado = :estado
                WHERE id = :id
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':estado' => $estado,
                ':id' => (int)$pagoId,
            ]);

            self::recalcularCuentaPorPostulanteEnTransaccion($pago['postulante_id'], $db);

            $db->commit();

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function asegurarCuentaPorCobrar($postulanteId)
    {
        $postulante = self::buscarPostulantePorId($postulanteId);

        if (!$postulante) {
            throw new Exception('El postulante seleccionado no existe.');
        }

        $db = self::db();

        try {
            $db->beginTransaction();

            self::asegurarCuentaPorCobrarEnTransaccion($postulanteId, $db);
            self::recalcularCuentaPorPostulanteEnTransaccion($postulanteId, $db);

            $db->commit();

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    private static function asegurarCuentaPorCobrarEnTransaccion($postulanteId, $db)
    {
        $sqlExiste = "
            SELECT COUNT(*)
            FROM cuenta_cobrar
            WHERE postulante_id = :postulante_id
        ";

        $stmtExiste = $db->prepare($sqlExiste);
        $stmtExiste->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmtExiste->execute();

        if ((int)$stmtExiste->fetchColumn() === 0) {
            $sqlInsert = "
                INSERT INTO cuenta_cobrar (
                    postulante_id,
                    saldo,
                    fecha_vencimiento,
                    estado
                ) VALUES (
                    :postulante_id,
                    :saldo,
                    CURRENT_DATE + INTERVAL '15 days',
                    'Pendiente'
                )
            ";

            $stmtInsert = $db->prepare($sqlInsert);
            $stmtInsert->execute([
                ':postulante_id' => (int)$postulanteId,
                ':saldo' => self::MONTO_OFICIAL_CUP,
            ]);
        }

        return true;
    }

    private static function recalcularCuentaPorPostulanteEnTransaccion($postulanteId, $db)
    {
        self::asegurarCuentaPorCobrarEnTransaccion($postulanteId, $db);

        $sqlTotal = "
            SELECT COALESCE(SUM(monto), 0)
            FROM pago
            WHERE postulante_id = :postulante_id
            AND estado = 'Aceptado'
        ";

        $stmtTotal = $db->prepare($sqlTotal);
        $stmtTotal->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmtTotal->execute();

        $totalPagado = (float)$stmtTotal->fetchColumn();
        $saldo = self::MONTO_OFICIAL_CUP - $totalPagado;

        if ($saldo < 0) {
            $saldo = 0;
        }

        if ($saldo <= 0) {
            $estadoCuenta = 'Pagado';
        } elseif ($totalPagado > 0) {
            $estadoCuenta = 'Parcial';
        } else {
            $estadoCuenta = 'Pendiente';
        }

        $sqlUpdate = "
            UPDATE cuenta_cobrar
            SET
                saldo = :saldo,
                estado = :estado
            WHERE postulante_id = :postulante_id
        ";

        $stmtUpdate = $db->prepare($sqlUpdate);

        return $stmtUpdate->execute([
            ':saldo' => $saldo,
            ':estado' => $estadoCuenta,
            ':postulante_id' => (int)$postulanteId,
        ]);
    }

    public static function existeReferencia($referencia, $exceptoPagoId = null)
    {
        $referencia = trim((string)($referencia ?? ''));

        if ($referencia === '') {
            return false;
        }

        $sql = "
            SELECT COUNT(*)
            FROM pago
            WHERE LOWER(referencia) = LOWER(:referencia)
        ";

        $params = [
            ':referencia' => $referencia,
        ];

        if ($exceptoPagoId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = (int)$exceptoPagoId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function estadosPagoValidos()
    {
        return [
            'Pendiente',
            'Aceptado',
            'Rechazado',
        ];
    }

    private static function generarCodigoPago()
    {
        $stmt = self::db()->query("SELECT COALESCE(MAX(id), 0) + 1 FROM pago");
        $numero = (int)$stmt->fetchColumn();

        return 'PAGO-' . str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
    }

    private static function normalizarFechaHora($fecha)
    {
        $fecha = trim((string)($fecha ?? ''));

        if ($fecha === '') {
            return '';
        }

        $fecha = str_replace('T', ' ', $fecha);

        if (strlen($fecha) === 16) {
            $fecha .= ':00';
        }

        if (strtotime($fecha) === false) {
            return '';
        }

        return date('Y-m-d H:i:s', strtotime($fecha));
    }

    private static function nullSiVacio($valor)
    {
        $valor = trim((string)($valor ?? ''));

        return $valor === '' ? null : $valor;
    }
}