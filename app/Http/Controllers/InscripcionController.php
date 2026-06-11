<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\PeriodoAcademico;

class InscripcionController
{
    public function index()
    {
        \require_login();

        try {
            $periodos = PeriodoAcademico::obtenerTodos();
            $periodoActivo = PeriodoAcademico::obtenerActivo();

            $periodoId = (int)($_GET['periodo_id'] ?? 0);
            $buscar = trim($_GET['buscar'] ?? '');

            if ($periodoId <= 0 && $periodoActivo) {
                $periodoId = (int)$periodoActivo['id'];
            }

            $postulantes = Inscripcion::obtenerResumenPostulantes($periodoId, $buscar);

            \view('inscripciones.index', [
                'titulo' => 'Inscripciones - SIGIE',
                'postulantes' => $postulantes,
                'periodos' => $periodos,
                'periodoId' => $periodoId,
                'buscar' => $buscar,
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al listar inscripciones: ' . $e->getMessage());

            \view('inscripciones.index', [
                'titulo' => 'Inscripciones - SIGIE',
                'postulantes' => [],
                'periodos' => [],
                'periodoId' => 0,
                'buscar' => '',
            ]);
        }
    }

    public function show()
    {
        \require_login();

        $postulanteId = (int)($_GET['id'] ?? 0);

        if ($postulanteId <= 0) {
            \set_flash('error', 'Postulante no válido.');
            \redirect('/inscripciones');
        }

        try {
            $postulante = Inscripcion::buscarPostulantePorId($postulanteId);

            if (!$postulante) {
                \set_flash('error', 'El postulante no existe.');
                \redirect('/inscripciones');
            }

            $documentos = Inscripcion::obtenerDocumentosPorPostulante($postulanteId);
            $grupos = Inscripcion::obtenerGruposDisponibles();
            $validacion = Inscripcion::validarPostulanteParaInscripcion($postulanteId);

            \view('inscripciones.show', [
                'titulo' => 'Convalidar e Inscribir - SIGIE',
                'postulante' => $postulante,
                'documentos' => $documentos,
                'grupos' => $grupos,
                'validacion' => $validacion,
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al cargar la convalidación: ' . $e->getMessage());
            \redirect('/inscripciones');
        }
    }

    public function store()
    {
        \require_login();

        $postulanteId = (int)($_POST['postulante_id'] ?? 0);
        $grupoId = (int)($_POST['grupo_id'] ?? 0);

        if ($postulanteId <= 0) {
            \set_flash('error', 'Postulante no válido.');
            \redirect('/inscripciones');
        }

        if ($grupoId <= 0) {
            \set_flash('error', 'Debes seleccionar un grupo disponible.');
            \redirect('/inscripciones/show&id=' . $postulanteId);
        }

        try {
            Inscripcion::inscribir($postulanteId, $grupoId);

            \set_flash('success', 'Postulante convalidado e inscrito correctamente.');
            \redirect('/inscripciones/show&id=' . $postulanteId);
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo inscribir al postulante: ' . $e->getMessage());
            \redirect('/inscripciones/show&id=' . $postulanteId);
        }
    }
}