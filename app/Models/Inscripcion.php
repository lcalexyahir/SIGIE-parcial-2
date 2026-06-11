<?php

namespace App\Models;

use PDO;
use Exception;

require_once __DIR__ . '/Conexion.php';

class Inscripcion
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
                pe.email,
                pe.telefono,
                c1.nombre AS carrera_principal,
                c2.nombre AS carrera_secundaria,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre,

                COALESCE(doc.total_requisitos, 0) AS total_requisitos,
                COALESCE(doc.requisitos_aceptados, 0) AS requisitos_aceptados,
                COALESCE(doc.requisitos_pendientes, 0) AS requisitos_pendientes,
                COALESCE(doc.requisitos_observados, 0) AS requisitos_observados,

                cc.id AS cuenta_id,
                COALESCE(cc.saldo, 700.00) AS saldo,
                COALESCE(cc.estado, 'Pendiente') AS estado_cuenta,

                i.id AS inscripcion_id,
                i.codigo AS codigo_inscripcion,
                i.fecha_inscripcion,
                i.estado AS estado_inscripcion,
                g.codigo AS grupo_codigo,
                g.nombre AS grupo_nombre
            FROM postulante po
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN periodo_academico pa ON pa.id = po.periodo_id
            LEFT JOIN cuenta_cobrar cc ON cc.postulante_id = po.id
            LEFT JOIN (
                SELECT
                    postulante_id,
                    COUNT(id) AS total_requisitos,
                    COALESCE(SUM(CASE WHEN estado_validacion = 'Aceptado' THEN 1 ELSE 0 END), 0) AS requisitos_aceptados,
                    COALESCE(SUM(CASE WHEN estado_validacion = 'Pendiente' THEN 1 ELSE 0 END), 0) AS requisitos_pendientes,
                    COALESCE(SUM(CASE WHEN estado_validacion = 'Observado' THEN 1 ELSE 0 END), 0) AS requisitos_observados
                FROM documento
                GROUP BY postulante_id
            ) doc ON doc.postulante_id = po.id
            LEFT JOIN (
                SELECT DISTINCT ON (postulante_id)
                    id,
                    codigo,
                    postulante_id,
                    grupo_id,
                    fecha_inscripcion,
                    estado
                FROM inscripcion
                WHERE estado = 'Activa'
                ORDER BY postulante_id, id DESC
            ) i ON i.postulante_id = po.id
            LEFT JOIN grupo g ON g.id = i.grupo_id
            {$whereSql}
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
                po.carrera_principal_id,
                po.carrera_secundaria_id,
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
                pa.semestre,

                COALESCE(doc.total_requisitos, 0) AS total_requisitos,
                COALESCE(doc.requisitos_aceptados, 0) AS requisitos_aceptados,
                COALESCE(doc.requisitos_pendientes, 0) AS requisitos_pendientes,
                COALESCE(doc.requisitos_observados, 0) AS requisitos_observados,

                cc.id AS cuenta_id,
                COALESCE(cc.saldo, 700.00) AS saldo,
                COALESCE(cc.estado, 'Pendiente') AS estado_cuenta,

                i.id AS inscripcion_id,
                i.codigo AS codigo_inscripcion,
                i.fecha_inscripcion,
                i.estado AS estado_inscripcion,
                g.id AS grupo_id,
                g.codigo AS grupo_codigo,
                g.nombre AS grupo_nombre,
                g.capacidad AS grupo_capacidad,
                g.cantidad_estudiantes AS grupo_cantidad_estudiantes
            FROM postulante po
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN periodo_academico pa ON pa.id = po.periodo_id
            LEFT JOIN cuenta_cobrar cc ON cc.postulante_id = po.id
            LEFT JOIN (
                SELECT
                    postulante_id,
                    COUNT(id) AS total_requisitos,
                    COALESCE(SUM(CASE WHEN estado_validacion = 'Aceptado' THEN 1 ELSE 0 END), 0) AS requisitos_aceptados,
                    COALESCE(SUM(CASE WHEN estado_validacion = 'Pendiente' THEN 1 ELSE 0 END), 0) AS requisitos_pendientes,
                    COALESCE(SUM(CASE WHEN estado_validacion = 'Observado' THEN 1 ELSE 0 END), 0) AS requisitos_observados
                FROM documento
                GROUP BY postulante_id
            ) doc ON doc.postulante_id = po.id
            LEFT JOIN (
                SELECT DISTINCT ON (postulante_id)
                    id,
                    codigo,
                    postulante_id,
                    grupo_id,
                    fecha_inscripcion,
                    estado
                FROM inscripcion
                WHERE estado = 'Activa'
                ORDER BY postulante_id, id DESC
            ) i ON i.postulante_id = po.id
            LEFT JOIN grupo g ON g.id = i.grupo_id
            WHERE po.id = :postulante_id
            LIMIT 1
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        $postulante = $stmt->fetch();

        return $postulante ?: null;
    }

    public static function obtenerDocumentosPorPostulante($postulanteId)
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

    public static function obtenerGruposDisponibles()
    {
        $sql = "
            SELECT
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                (g.capacidad - g.cantidad_estudiantes) AS cupos_disponibles,
                g.estado,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre
            FROM grupo g
            LEFT JOIN periodo_academico pa ON pa.id = g.periodo_id
            WHERE LOWER(g.estado) = LOWER('Activo')
            AND g.cantidad_estudiantes < g.capacidad
            ORDER BY
                CASE
                    WHEN g.codigo LIKE 'M%' THEN 1
                    WHEN g.codigo LIKE 'T%' THEN 2
                    WHEN g.codigo LIKE 'N%' THEN 3
                    ELSE 4
                END,
                g.codigo ASC
        ";

        $stmt = self::db()->query($sql);

        return $stmt->fetchAll();
    }

    public static function obtenerTodosGrupos()
    {
        $sql = "
            SELECT
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                (g.capacidad - g.cantidad_estudiantes) AS cupos_disponibles,
                g.estado,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre
            FROM grupo g
            LEFT JOIN periodo_academico pa ON pa.id = g.periodo_id
            ORDER BY
                CASE
                    WHEN g.codigo LIKE 'M%' THEN 1
                    WHEN g.codigo LIKE 'T%' THEN 2
                    WHEN g.codigo LIKE 'N%' THEN 3
                    ELSE 4
                END,
                g.codigo ASC
        ";

        $stmt = self::db()->query($sql);

        return $stmt->fetchAll();
    }

    public static function validarPostulanteParaInscripcion($postulanteId)
    {
        $errores = [];
        $postulante = self::buscarPostulantePorId($postulanteId);

        if (!$postulante) {
            return [
                'puede_inscribirse' => false,
                'errores' => ['El postulante no existe.'],
                'postulante' => null,
            ];
        }

        if (!empty($postulante['inscripcion_id'])) {
            $errores[] = 'El postulante ya tiene una inscripción activa.';
        }

        $totalRequisitos = (int)($postulante['total_requisitos'] ?? 0);
        $requisitosAceptados = (int)($postulante['requisitos_aceptados'] ?? 0);
        $requisitosPendientes = (int)($postulante['requisitos_pendientes'] ?? 0);
        $requisitosObservados = (int)($postulante['requisitos_observados'] ?? 0);

        if ($totalRequisitos <= 0) {
            $errores[] = 'El postulante no tiene requisitos generados.';
        }

        if ($totalRequisitos > 0 && $requisitosAceptados < $totalRequisitos) {
            $errores[] = 'No todos los requisitos del postulante están aceptados.';
        }

        if ($requisitosPendientes > 0) {
            $errores[] = 'El postulante todavía tiene requisitos pendientes.';
        }

        if ($requisitosObservados > 0) {
            $errores[] = 'El postulante todavía tiene requisitos observados.';
        }

        if (empty($postulante['cuenta_id'])) {
            $errores[] = 'El postulante no tiene cuenta por cobrar generada.';
        }

        $saldo = (float)($postulante['saldo'] ?? 700.00);
        $estadoCuenta = trim((string)($postulante['estado_cuenta'] ?? 'Pendiente'));

        if ($estadoCuenta !== 'Pagado' || $saldo > 0) {
            $errores[] = 'La cuenta del postulante todavía no está pagada.';
        }

        return [
            'puede_inscribirse' => empty($errores),
            'errores' => $errores,
            'postulante' => $postulante,
        ];
    }

    public static function inscribir($postulanteId, $grupoId)
    {
        $postulanteId = (int)$postulanteId;
        $grupoId = (int)$grupoId;

        if ($postulanteId <= 0) {
            throw new Exception('Postulante no válido.');
        }

        if ($grupoId <= 0) {
            throw new Exception('Debes seleccionar un grupo válido.');
        }

        $db = self::db();

        try {
            $db->beginTransaction();

            $postulante = self::buscarPostulantePorIdEnTransaccion($postulanteId, $db);

            if (!$postulante) {
                throw new Exception('El postulante no existe.');
            }

            if (self::tieneInscripcionActivaEnTransaccion($postulanteId, $db)) {
                throw new Exception('El postulante ya tiene una inscripción activa.');
            }

            self::validarDocumentosEnTransaccion($postulanteId, $db);
            self::validarCuentaPagadaEnTransaccion($postulanteId, $db);

            $grupo = self::buscarGrupoDisponibleEnTransaccion($grupoId, $db);

            if (!$grupo) {
                throw new Exception('El grupo seleccionado no existe, no está activo o no tiene cupos disponibles.');
            }

            $codigo = self::generarCodigoInscripcionEnTransaccion($db);

            $sqlInscripcion = "
                INSERT INTO inscripcion (
                    codigo,
                    postulante_id,
                    grupo_id,
                    fecha_inscripcion,
                    estado
                ) VALUES (
                    :codigo,
                    :postulante_id,
                    :grupo_id,
                    CURRENT_DATE,
                    'Activa'
                )
            ";

            $stmtInscripcion = $db->prepare($sqlInscripcion);
            $stmtInscripcion->execute([
                ':codigo' => $codigo,
                ':postulante_id' => $postulanteId,
                ':grupo_id' => $grupoId,
            ]);

            $sqlGrupo = "
                UPDATE grupo
                SET cantidad_estudiantes = cantidad_estudiantes + 1
                WHERE id = :grupo_id
            ";

            $stmtGrupo = $db->prepare($sqlGrupo);
            $stmtGrupo->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
            $stmtGrupo->execute();

            $sqlPostulante = "
                UPDATE postulante
                SET
                    estado_postulacion = 'Inscrito',
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :postulante_id
            ";

            $stmtPostulante = $db->prepare($sqlPostulante);
            $stmtPostulante->bindValue(':postulante_id', $postulanteId, PDO::PARAM_INT);
            $stmtPostulante->execute();

            $db->commit();

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function contar()
    {
        $stmt = self::db()->query("SELECT COUNT(*) FROM inscripcion WHERE estado = 'Activa'");
        return (int)$stmt->fetchColumn();
    }

    private static function buscarPostulantePorIdEnTransaccion($postulanteId, $db)
    {
        $sql = "
            SELECT id, codigo, estado_postulacion
            FROM postulante
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        $postulante = $stmt->fetch();

        return $postulante ?: null;
    }

    private static function tieneInscripcionActivaEnTransaccion($postulanteId, $db)
    {
        $sql = "
            SELECT COUNT(*)
            FROM inscripcion
            WHERE postulante_id = :postulante_id
            AND estado = 'Activa'
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    private static function validarDocumentosEnTransaccion($postulanteId, $db)
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

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        $resumen = $stmt->fetch();

        $total = (int)($resumen['total_requisitos'] ?? 0);
        $aceptados = (int)($resumen['requisitos_aceptados'] ?? 0);
        $pendientes = (int)($resumen['requisitos_pendientes'] ?? 0);
        $observados = (int)($resumen['requisitos_observados'] ?? 0);

        if ($total <= 0) {
            throw new Exception('El postulante no tiene requisitos generados.');
        }

        if ($aceptados !== $total || $pendientes > 0 || $observados > 0) {
            throw new Exception('Para inscribir al postulante, todos los requisitos deben estar aceptados.');
        }

        return true;
    }

    private static function validarCuentaPagadaEnTransaccion($postulanteId, $db)
    {
        $sql = "
            SELECT id, saldo, estado
            FROM cuenta_cobrar
            WHERE postulante_id = :postulante_id
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':postulante_id', (int)$postulanteId, PDO::PARAM_INT);
        $stmt->execute();

        $cuenta = $stmt->fetch();

        if (!$cuenta) {
            throw new Exception('El postulante no tiene cuenta por cobrar generada.');
        }

        $saldo = (float)($cuenta['saldo'] ?? 0);
        $estado = trim((string)($cuenta['estado'] ?? ''));

        if ($estado !== 'Pagado' || $saldo > 0) {
            throw new Exception('Para inscribir al postulante, la cuenta debe estar pagada.');
        }

        return true;
    }

    private static function buscarGrupoDisponibleEnTransaccion($grupoId, $db)
    {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                capacidad,
                cantidad_estudiantes,
                estado
            FROM grupo
            WHERE id = :id
            FOR UPDATE
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', (int)$grupoId, PDO::PARAM_INT);
        $stmt->execute();

        $grupo = $stmt->fetch();

        if (!$grupo) {
            return null;
        }

        $estadoActivo = strtolower((string)$grupo['estado']) === 'activo';
        $capacidad = (int)$grupo['capacidad'];
        $cantidad = (int)$grupo['cantidad_estudiantes'];

        if (!$estadoActivo || $cantidad >= $capacidad) {
            return null;
        }

        return $grupo;
    }

    private static function generarCodigoInscripcionEnTransaccion($db)
    {
        $stmt = $db->query("SELECT COALESCE(MAX(id), 0) + 1 FROM inscripcion");
        $numero = (int)$stmt->fetchColumn();

        return 'INS-' . str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
    }
}