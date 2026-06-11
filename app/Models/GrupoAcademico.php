<?php

namespace App\Models;

use PDO;
use Exception;

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/PeriodoAcademico.php';

class GrupoAcademico
{
    public const DIVISOR_CALCULO_GRUPOS = 80;

    private static function db()
    {
        return Conexion::getConexion();
    }

    public static function obtenerGrupos()
    {
        $sql = "
            SELECT
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                g.estado,
                pa.codigo AS periodo_codigo,
                pa.gestion,
                pa.semestre,
                COUNT(i.id) AS inscritos_reales,
                (g.capacidad - COUNT(i.id)) AS cupos_libres,
                (COUNT(i.id) - g.cantidad_estudiantes) AS diferencia_registro,
                CASE
                    WHEN g.capacidad > 0 THEN ROUND((COUNT(i.id)::numeric / g.capacidad::numeric) * 100, 2)
                    ELSE 0
                END AS porcentaje_ocupacion
            FROM grupo g
            LEFT JOIN periodo_academico pa ON pa.id = g.periodo_id
            LEFT JOIN inscripcion i ON i.grupo_id = g.id AND i.estado = 'Activa'
            GROUP BY
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                g.estado,
                pa.codigo,
                pa.gestion,
                pa.semestre
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

    public static function obtenerIndicadores()
    {
        $grupos = self::obtenerGrupos();

        $totalGrupos = count($grupos);
        $totalInscritos = 0;
        $capacidadTotal = 0;
        $cuposLibres = 0;
        $gruposActivos = 0;
        $gruposSaturados = 0;
        $gruposInactivos = 0;
        $gruposCerrados = 0;

        foreach ($grupos as $grupo) {
            $inscritos = (int)($grupo['inscritos_reales'] ?? 0);
            $capacidad = (int)($grupo['capacidad'] ?? 0);
            $estado = strtolower(trim((string)($grupo['estado'] ?? '')));

            $totalInscritos += $inscritos;
            $capacidadTotal += $capacidad;

            if ($capacidad > $inscritos) {
                $cuposLibres += ($capacidad - $inscritos);
            }

            if ($estado === 'activo') {
                $gruposActivos++;
            } elseif ($estado === 'saturado') {
                $gruposSaturados++;
            } elseif ($estado === 'inactivo') {
                $gruposInactivos++;
            } elseif ($estado === 'cerrado') {
                $gruposCerrados++;
            }
        }

        $gruposNecesariosDivisor80 = $totalInscritos > 0
            ? (int)ceil($totalInscritos / self::DIVISOR_CALCULO_GRUPOS)
            : 0;

        $porcentajeGeneral = $capacidadTotal > 0
            ? round(($totalInscritos / $capacidadTotal) * 100, 2)
            : 0;

        return [
            'divisor_calculo_grupos' => self::DIVISOR_CALCULO_GRUPOS,
            'total_grupos' => $totalGrupos,
            'total_inscritos' => $totalInscritos,
            'capacidad_total' => $capacidadTotal,
            'cupos_libres' => $cuposLibres,
            'grupos_activos' => $gruposActivos,
            'grupos_saturados' => $gruposSaturados,
            'grupos_inactivos' => $gruposInactivos,
            'grupos_cerrados' => $gruposCerrados,
            'grupos_necesarios_divisor_80' => $gruposNecesariosDivisor80,
            'porcentaje_general' => $porcentajeGeneral,
        ];
    }

    public static function obtenerPeriodos()
    {
        return PeriodoAcademico::obtenerTodos();
    }

    public static function crear($datos)
    {
        $codigo = strtoupper(trim((string)($datos['codigo'] ?? '')));
        $nombre = trim((string)($datos['nombre'] ?? ''));
        $capacidad = (int)($datos['capacidad'] ?? 70);
        $periodoId = (int)($datos['periodo_id'] ?? 0);
        $estado = trim((string)($datos['estado'] ?? 'Activo'));

        if ($codigo === '') {
            throw new Exception('El código del grupo es obligatorio.');
        }

        if ($nombre === '') {
            throw new Exception('El nombre del grupo es obligatorio.');
        }

        if ($capacidad <= 0) {
            throw new Exception('La capacidad debe ser mayor a cero.');
        }

        if ($periodoId <= 0 || !PeriodoAcademico::buscarPorId($periodoId)) {
            throw new Exception('Debes seleccionar una gestión válida.');
        }

        if (!in_array($estado, self::estadosValidos(), true)) {
            throw new Exception('El estado seleccionado no es válido.');
        }

        if (self::existeCodigo($codigo)) {
            throw new Exception('Ya existe un grupo con ese código.');
        }

        $sql = "
            INSERT INTO grupo (
                codigo,
                nombre,
                capacidad,
                cantidad_estudiantes,
                estado,
                periodo_id
            ) VALUES (
                :codigo,
                :nombre,
                :capacidad,
                0,
                :estado,
                :periodo_id
            )
        ";

        $stmt = self::db()->prepare($sql);

        return $stmt->execute([
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':capacidad' => $capacidad,
            ':estado' => $estado,
            ':periodo_id' => $periodoId,
        ]);
    }

    public static function existeCodigo($codigo)
    {
        $sql = "
            SELECT COUNT(*)
            FROM grupo
            WHERE LOWER(codigo) = LOWER(:codigo)
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':codigo', trim((string)$codigo));
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function sincronizarCantidadEstudiantes()
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            $grupos = self::obtenerGruposParaRecalculo($db);
            $actualizados = 0;

            foreach ($grupos as $grupo) {
                $grupoId = (int)$grupo['id'];
                $capacidad = (int)$grupo['capacidad'];
                $inscritos = (int)$grupo['inscritos_reales'];
                $estadoActual = trim((string)($grupo['estado'] ?? 'Activo'));
                $estadoNormalizado = strtolower($estadoActual);

                $nuevoEstado = $estadoActual;

                if ($capacidad > 0 && $inscritos >= $capacidad) {
                    $nuevoEstado = 'Saturado';
                } elseif ($inscritos > 0) {
                    $nuevoEstado = 'Activo';
                } elseif (in_array($estadoNormalizado, ['inactivo', 'cerrado'], true)) {
                    $nuevoEstado = $estadoActual;
                } else {
                    $nuevoEstado = 'Activo';
                }

                $sqlUpdate = "
                    UPDATE grupo
                    SET
                        cantidad_estudiantes = :cantidad_estudiantes,
                        estado = :estado
                    WHERE id = :id
                ";

                $stmtUpdate = $db->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':cantidad_estudiantes' => $inscritos,
                    ':estado' => $nuevoEstado,
                    ':id' => $grupoId,
                ]);

                $actualizados++;
            }

            $db->commit();

            return $actualizados;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function buscarPorId($id)
    {
        $sql = "
            SELECT
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                g.estado,
                COUNT(i.id) AS inscritos_reales
            FROM grupo g
            LEFT JOIN inscripcion i ON i.grupo_id = g.id AND i.estado = 'Activa'
            WHERE g.id = :id
            GROUP BY
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                g.estado
            LIMIT 1
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();

        $grupo = $stmt->fetch();

        return $grupo ?: null;
    }

    public static function cambiarEstado($grupoId, $estado)
    {
        if (!in_array($estado, self::estadosValidos(), true)) {
            throw new Exception('El estado seleccionado no es válido.');
        }

        $grupo = self::buscarPorId($grupoId);

        if (!$grupo) {
            throw new Exception('El grupo académico no existe.');
        }

        $inscritos = (int)($grupo['inscritos_reales'] ?? 0);
        $capacidad = (int)($grupo['capacidad'] ?? 0);

        if (in_array($estado, ['Inactivo', 'Cerrado'], true) && $inscritos > 0) {
            throw new Exception('No se puede cerrar o inactivar un grupo que ya tiene postulantes inscritos.');
        }

        if ($estado === 'Activo' && $capacidad > 0 && $inscritos >= $capacidad) {
            throw new Exception('No se puede activar un grupo que ya está lleno. Debe quedar como Saturado.');
        }

        if ($estado === 'Saturado' && $capacidad > 0 && $inscritos < $capacidad) {
            throw new Exception('No se puede marcar como Saturado un grupo que todavía tiene cupos disponibles.');
        }

        $sql = "
            UPDATE grupo
            SET estado = :estado
            WHERE id = :id
        ";

        $stmt = self::db()->prepare($sql);

        return $stmt->execute([
            ':estado' => $estado,
            ':id' => (int)$grupoId,
        ]);
    }

    public static function estadosValidos()
    {
        return [
            'Activo',
            'Inactivo',
            'Saturado',
            'Cerrado',
        ];
    }

    private static function obtenerGruposParaRecalculo($db)
    {
        $sql = "
            SELECT
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                g.estado,
                COUNT(i.id) AS inscritos_reales
            FROM grupo g
            LEFT JOIN inscripcion i ON i.grupo_id = g.id AND i.estado = 'Activa'
            GROUP BY
                g.id,
                g.codigo,
                g.nombre,
                g.capacidad,
                g.cantidad_estudiantes,
                g.estado
            ORDER BY g.id ASC
            FOR UPDATE
        ";

        $stmt = $db->query($sql);

        return $stmt->fetchAll();
    }
}