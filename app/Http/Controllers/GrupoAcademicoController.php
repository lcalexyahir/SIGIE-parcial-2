<?php

namespace App\Http\Controllers;

use App\Models\GrupoAcademico;

class GrupoAcademicoController
{
    public function index()
    {
        \require_login();

        try {
            $indicadores = GrupoAcademico::obtenerIndicadores();
            $grupos = GrupoAcademico::obtenerGrupos();

            \view('grupos_academicos.index', [
                'titulo' => 'Grupos Académicos - SIGIE',
                'indicadores' => $indicadores,
                'grupos' => $grupos,
                'estadosValidos' => GrupoAcademico::estadosValidos(),
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al cargar grupos académicos: ' . $e->getMessage());

            \view('grupos_academicos.index', [
                'titulo' => 'Grupos Académicos - SIGIE',
                'indicadores' => [
                    'divisor_calculo_grupos' => GrupoAcademico::DIVISOR_CALCULO_GRUPOS,
                    'total_grupos' => 0,
                    'total_inscritos' => 0,
                    'capacidad_total' => 0,
                    'cupos_libres' => 0,
                    'grupos_activos' => 0,
                    'grupos_saturados' => 0,
                    'grupos_inactivos' => 0,
                    'grupos_cerrados' => 0,
                    'grupos_necesarios_divisor_80' => 0,
                    'porcentaje_general' => 0,
                ],
                'grupos' => [],
                'estadosValidos' => GrupoAcademico::estadosValidos(),
            ]);
        }
    }

    public function create()
    {
        \require_login();

        try {
            $periodos = GrupoAcademico::obtenerPeriodos();

            \view('grupos_academicos.create', [
                'titulo' => 'Nuevo Grupo Académico - SIGIE',
                'periodos' => $periodos,
                'estadosValidos' => GrupoAcademico::estadosValidos(),
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al cargar formulario de grupo: ' . $e->getMessage());
            \redirect('/grupos-academicos');
        }
    }

    public function store()
    {
        \require_login();

        $datos = [
            'codigo' => trim($_POST['codigo'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'capacidad' => trim($_POST['capacidad'] ?? '70'),
            'periodo_id' => trim($_POST['periodo_id'] ?? ''),
            'estado' => trim($_POST['estado'] ?? 'Activo'),
        ];

        if ($datos['codigo'] === '') {
            \set_flash('error', 'El código del grupo es obligatorio.');
            \keep_old($_POST);
            \redirect('/grupos-academicos/create');
        }

        if ($datos['nombre'] === '') {
            \set_flash('error', 'El nombre del grupo es obligatorio.');
            \keep_old($_POST);
            \redirect('/grupos-academicos/create');
        }

        if ($datos['capacidad'] === '' || !ctype_digit($datos['capacidad']) || (int)$datos['capacidad'] <= 0) {
            \set_flash('error', 'La capacidad debe ser un número entero mayor a cero.');
            \keep_old($_POST);
            \redirect('/grupos-academicos/create');
        }

        if ($datos['periodo_id'] === '' || (int)$datos['periodo_id'] <= 0) {
            \set_flash('error', 'Debes seleccionar una gestión válida.');
            \keep_old($_POST);
            \redirect('/grupos-academicos/create');
        }

        if (!in_array($datos['estado'], GrupoAcademico::estadosValidos(), true)) {
            \set_flash('error', 'El estado seleccionado no es válido.');
            \keep_old($_POST);
            \redirect('/grupos-academicos/create');
        }

        try {
            GrupoAcademico::crear($datos);

            \set_flash('success', 'Grupo académico creado correctamente.');
            \redirect('/grupos-academicos');
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo crear el grupo académico: ' . $e->getMessage());
            \keep_old($_POST);
            \redirect('/grupos-academicos/create');
        }
    }

    public function recalcular()
    {
        \require_login();

        try {
            $total = GrupoAcademico::sincronizarCantidadEstudiantes();

            \set_flash('success', 'Grupos académicos recalculados correctamente. Total de grupos actualizados: ' . $total . '.');
            \redirect('/grupos-academicos');
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo recalcular los grupos académicos: ' . $e->getMessage());
            \redirect('/grupos-academicos');
        }
    }

    public function cambiarEstado()
    {
        \require_login();

        $grupoId = (int)($_POST['grupo_id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');

        if ($grupoId <= 0) {
            \set_flash('error', 'Grupo académico no válido.');
            \redirect('/grupos-academicos');
        }

        if ($estado === '') {
            \set_flash('error', 'Debes seleccionar un estado.');
            \redirect('/grupos-academicos');
        }

        try {
            GrupoAcademico::cambiarEstado($grupoId, $estado);

            \set_flash('success', 'Estado del grupo actualizado correctamente.');
            \redirect('/grupos-academicos');
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo cambiar el estado del grupo: ' . $e->getMessage());
            \redirect('/grupos-academicos');
        }
    }
}