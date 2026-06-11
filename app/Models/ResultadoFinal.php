<?php

namespace App\Models;

use PDO;
use Exception;

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/PeriodoAcademico.php';

class ResultadoFinal
{
    public const NOTA_MINIMA_APROBACION = 60.00;
    public const TOTAL_MATERIAS_CUP = 4;

    private static function db()
    {
        return Conexion::getConexion();
    }

    public static function obtenerPorPeriodo($periodoId, $estado = null)
    {
        $whereEstado = '';
        $params = [':periodo_id' => (int)$periodoId];

        if ($estado !== null && $estado !== '') {
            $whereEstado = 'AND rf.estado_final = :estado_final';
            $params[':estado_final'] = $estado;
        }

        $sql = "
            SELECT
                rf.id,
                rf.postulante_id,
                rf.periodo_id,
                rf.promedio_general,
                rf.estado_final,
                rf.carrera_admitida_id,
                rf.opcion_admitida,
                COALESCE(rf.observacion, '') AS observacion,
                po.codigo AS codigo_postulante,
                po.estado_postulacion,
                pe.ci,
                pe.nombres,
                pe.apellidos,
                c1.nombre AS carrera_principal,
                c2.nombre AS carrera_secundaria,
                ca.codigo AS codigo_carrera_admitida,
                ca.nombre AS carrera_admitida,
                pa.codigo AS periodo_codigo
            FROM resultado_final rf
            INNER JOIN postulante po ON po.id = rf.postulante_id
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN carrera ca ON ca.id = rf.carrera_admitida_id
            LEFT JOIN periodo_academico pa ON pa.id = rf.periodo_id
            WHERE rf.periodo_id = :periodo_id
            {$whereEstado}
            ORDER BY
                CASE rf.estado_final
                    WHEN 'ADMITIDO' THEN 1
                    WHEN 'APROBADO SIN CUPO' THEN 2
                    WHEN 'REPROBADO' THEN 3
                    ELSE 4
                END,
                rf.promedio_general DESC,
                pe.apellidos ASC,
                pe.nombres ASC
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function buscarPorId($id)
    {
        $sql = "
            SELECT
                rf.id,
                rf.postulante_id,
                rf.periodo_id,
                rf.promedio_general,
                rf.estado_final,
                rf.carrera_admitida_id,
                rf.opcion_admitida,
                COALESCE(rf.observacion, '') AS observacion,
                po.codigo AS codigo_postulante,
                po.estado_postulacion,
                pe.ci,
                pe.nombres,
                pe.apellidos,
                pe.email,
                pe.telefono,
                c1.nombre AS carrera_principal,
                c2.nombre AS carrera_secundaria,
                ca.codigo AS codigo_carrera_admitida,
                ca.nombre AS carrera_admitida,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre
            FROM resultado_final rf
            INNER JOIN postulante po ON po.id = rf.postulante_id
            INNER JOIN persona pe ON pe.id = po.persona_id
            LEFT JOIN carrera c1 ON c1.id = po.carrera_principal_id
            LEFT JOIN carrera c2 ON c2.id = po.carrera_secundaria_id
            LEFT JOIN carrera ca ON ca.id = rf.carrera_admitida_id
            LEFT JOIN periodo_academico pa ON pa.id = rf.periodo_id
            WHERE rf.id = :id
            LIMIT 1
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public static function obtenerResumenPorPeriodo($periodoId)
    {
        $sql = "
            SELECT
                COUNT(*) AS total_resultados,
                COALESCE(SUM(CASE WHEN estado_final = 'ADMITIDO' THEN 1 ELSE 0 END), 0) AS total_admitidos,
                COALESCE(SUM(CASE WHEN estado_final = 'APROBADO SIN CUPO' THEN 1 ELSE 0 END), 0) AS total_no_admitidos,
                COALESCE(SUM(CASE WHEN estado_final = 'REPROBADO' THEN 1 ELSE 0 END), 0) AS total_reprobados,
                COALESCE(ROUND(AVG(promedio_general), 2), 0) AS promedio_general
            FROM resultado_final
            WHERE periodo_id = :periodo_id
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':periodo_id', (int)$periodoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public static function obtenerAdmitidosPorCarrera($periodoId)
    {
        $sql = "
            SELECT
                c.codigo,
                c.nombre,
                COALESCE(cpc.cupo_maximo, c.cupo_maximo, 0) AS cupo_maximo,
                COUNT(rf.id) AS total_admitidos,
                GREATEST(COALESCE(cpc.cupo_maximo, c.cupo_maximo, 0) - COUNT(rf.id), 0) AS cupos_libres
            FROM carrera c
            LEFT JOIN carrera_periodo_cupo cpc
                ON cpc.carrera_id = c.id
               AND cpc.periodo_id = :periodo_id
            LEFT JOIN resultado_final rf
                ON rf.carrera_admitida_id = c.id
               AND rf.periodo_id = :periodo_id
               AND rf.estado_final = 'ADMITIDO'
            GROUP BY c.id, c.codigo, c.nombre, c.cupo_maximo, cpc.cupo_maximo
            ORDER BY c.id ASC
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':periodo_id', (int)$periodoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function generarPorPeriodo($periodoId)
    {
        $periodoId = (int)$periodoId;
        $periodo = PeriodoAcademico::buscarPorId($periodoId);

        if (!$periodo) {
            throw new Exception('El periodo académico seleccionado no existe.');
        }

        if (strtolower((string)$periodo['estado']) === 'activo') {
            throw new Exception('No se puede generar resultado final para una gestión activa. Primero debe finalizar el CUP.');
        }

        $db = self::db();

        try {
            $db->beginTransaction();

            self::eliminarResultadosPeriodo($periodoId, $db);

            $cuposDisponibles = self::obtenerCuposDisponiblesEnTransaccion($periodoId, $db);
            $postulantes = self::obtenerPostulantesConPromedioEnTransaccion($periodoId, $db);

            $totalAdmitidos = 0;
            $totalNoAdmitidos = 0;
            $totalReprobados = 0;

            foreach ($postulantes as $postulante) {
                $postulanteId = (int)$postulante['postulante_id'];
                $promedio = round((float)$postulante['promedio_general'], 2);
                $carreraPrincipalId = (int)$postulante['carrera_principal_id'];
                $carreraSecundariaId = (int)$postulante['carrera_secundaria_id'];
                $materiasReprobadas = (int)($postulante['materias_reprobadas'] ?? 0);
                $detalleMateriasReprobadas = trim((string)($postulante['detalle_materias_reprobadas'] ?? ''));

                $estadoFinal = 'REPROBADO';
                $carreraAdmitidaId = null;
                $opcionAdmitida = null;
                $observacion = 'No alcanzó la nota mínima de aprobación.';

                if ($promedio < self::NOTA_MINIMA_APROBACION) {
                    $estadoFinal = 'REPROBADO';
                    $observacion = 'Promedio general menor a 60.';
                    $totalReprobados++;
                } elseif ($materiasReprobadas > 0) {
                    $estadoFinal = 'REPROBADO';
                    $observacion = 'Reprobó por materia con nota menor a 60: ' . $detalleMateriasReprobadas . '.';
                    $totalReprobados++;
                } else {
                    if (!empty($cuposDisponibles[$carreraPrincipalId]) && $cuposDisponibles[$carreraPrincipalId] > 0) {
                        $estadoFinal = 'ADMITIDO';
                        $carreraAdmitidaId = $carreraPrincipalId;
                        $opcionAdmitida = 1;
                        $observacion = 'Admitido en su primera opción.';
                        $cuposDisponibles[$carreraPrincipalId]--;
                        $totalAdmitidos++;
                    } elseif (!empty($cuposDisponibles[$carreraSecundariaId]) && $cuposDisponibles[$carreraSecundariaId] > 0) {
                        $estadoFinal = 'ADMITIDO';
                        $carreraAdmitidaId = $carreraSecundariaId;
                        $opcionAdmitida = 2;
                        $observacion = 'Admitido en su segunda opción por falta de cupo en la primera.';
                        $cuposDisponibles[$carreraSecundariaId]--;
                        $totalAdmitidos++;
                    } else {
                        $estadoFinal = 'APROBADO SIN CUPO';
                        $observacion = 'Aprobó el promedio general y todas las materias, pero no alcanzó cupo en ninguna de sus dos opciones.';
                        $totalNoAdmitidos++;
                    }
                }

                self::insertarResultadoEnTransaccion(
                    $postulanteId,
                    $periodoId,
                    $promedio,
                    $estadoFinal,
                    $carreraAdmitidaId,
                    $opcionAdmitida,
                    $observacion,
                    $db
                );

                self::actualizarEstadoPostulanteEnTransaccion($postulanteId, $estadoFinal, $db);
            }

            $db->commit();

            return [
                'total_procesados' => count($postulantes),
                'total_admitidos' => $totalAdmitidos,
                'total_no_admitidos' => $totalNoAdmitidos,
                'total_reprobados' => $totalReprobados,
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    private static function eliminarResultadosPeriodo($periodoId, $db)
    {
        $sql = "DELETE FROM resultado_final WHERE periodo_id = :periodo_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':periodo_id', (int)$periodoId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private static function obtenerCuposDisponiblesEnTransaccion($periodoId, $db)
    {
        $sql = "
            SELECT
                c.id AS carrera_id,
                COALESCE(cpc.cupo_maximo, c.cupo_maximo, 0) AS cupo_maximo
            FROM carrera c
            LEFT JOIN carrera_periodo_cupo cpc
                ON cpc.carrera_id = c.id
               AND cpc.periodo_id = :periodo_id
            ORDER BY c.id ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':periodo_id', (int)$periodoId, PDO::PARAM_INT);
        $stmt->execute();

        $cupos = [];

        foreach ($stmt->fetchAll() as $fila) {
            $cupos[(int)$fila['carrera_id']] = (int)$fila['cupo_maximo'];
        }

        return $cupos;
    }

    private static function obtenerPostulantesConPromedioEnTransaccion($periodoId, $db)
    {
        $sql = "
            WITH promedios_materia AS (
                SELECT
                    po.id AS postulante_id,
                    po.carrera_principal_id,
                    po.carrera_secundaria_id,
                    m.id AS materia_id,
                    m.nombre AS materia_nombre,
                    ROUND(SUM(n.nota * (e.porcentaje / 100.0))::numeric, 2) AS promedio_materia
                FROM postulante po
                INNER JOIN nota n ON n.postulante_id = po.id
                INNER JOIN examen e ON e.id = n.examen_id
                INNER JOIN materia m ON m.id = e.materia_id
                WHERE po.periodo_id = :periodo_id
                  AND e.periodo_id = :periodo_id
                GROUP BY
                    po.id,
                    po.carrera_principal_id,
                    po.carrera_secundaria_id,
                    m.id,
                    m.nombre
            )
            SELECT
                postulante_id,
                carrera_principal_id,
                carrera_secundaria_id,
                ROUND(AVG(promedio_materia)::numeric, 2) AS promedio_general,
                COUNT(materia_id) AS total_materias,
                COALESCE(SUM(CASE WHEN promedio_materia < :nota_minima THEN 1 ELSE 0 END), 0) AS materias_reprobadas,
                COALESCE(
                    string_agg(
                        CASE
                            WHEN promedio_materia < :nota_minima
                            THEN materia_nombre || ' (' || promedio_materia::text || ')'
                            ELSE NULL
                        END,
                        ', '
                        ORDER BY materia_nombre
                    ),
                    ''
                ) AS detalle_materias_reprobadas
            FROM promedios_materia
            GROUP BY
                postulante_id,
                carrera_principal_id,
                carrera_secundaria_id
            HAVING COUNT(materia_id) >= :total_materias
            ORDER BY promedio_general DESC, postulante_id ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':periodo_id', (int)$periodoId, PDO::PARAM_INT);
        $stmt->bindValue(':nota_minima', self::NOTA_MINIMA_APROBACION);
        $stmt->bindValue(':total_materias', self::TOTAL_MATERIAS_CUP, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private static function insertarResultadoEnTransaccion($postulanteId, $periodoId, $promedio, $estadoFinal, $carreraAdmitidaId, $opcionAdmitida, $observacion, $db)
    {
        $sql = "
            INSERT INTO resultado_final (
                postulante_id,
                promedio_general,
                estado_final,
                periodo_id,
                carrera_admitida_id,
                opcion_admitida,
                observacion
            ) VALUES (
                :postulante_id,
                :promedio_general,
                :estado_final,
                :periodo_id,
                :carrera_admitida_id,
                :opcion_admitida,
                :observacion
            )
            ON CONFLICT (postulante_id) DO UPDATE SET
                promedio_general = EXCLUDED.promedio_general,
                estado_final = EXCLUDED.estado_final,
                periodo_id = EXCLUDED.periodo_id,
                carrera_admitida_id = EXCLUDED.carrera_admitida_id,
                opcion_admitida = EXCLUDED.opcion_admitida,
                observacion = EXCLUDED.observacion
        ";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            ':postulante_id' => (int)$postulanteId,
            ':promedio_general' => (float)$promedio,
            ':estado_final' => $estadoFinal,
            ':periodo_id' => (int)$periodoId,
            ':carrera_admitida_id' => $carreraAdmitidaId !== null ? (int)$carreraAdmitidaId : null,
            ':opcion_admitida' => $opcionAdmitida !== null ? (int)$opcionAdmitida : null,
            ':observacion' => $observacion,
        ]);
    }

    private static function actualizarEstadoPostulanteEnTransaccion($postulanteId, $estadoFinal, $db)
    {
        $estadoPostulacion = 'Registrado';

        if ($estadoFinal === 'ADMITIDO') {
            $estadoPostulacion = 'Admitido';
        } elseif ($estadoFinal === 'APROBADO SIN CUPO') {
            $estadoPostulacion = 'Aprobado sin cupo';
        } elseif ($estadoFinal === 'REPROBADO') {
            $estadoPostulacion = 'Reprobado';
        }

        $sql = "
            UPDATE postulante
            SET
                estado_postulacion = :estado_postulacion,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            ':estado_postulacion' => $estadoPostulacion,
            ':id' => (int)$postulanteId,
        ]);
    }
}