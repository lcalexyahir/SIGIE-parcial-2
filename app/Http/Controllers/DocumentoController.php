<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\PeriodoAcademico;

class DocumentoController
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

            $postulantes = Documento::obtenerResumenPostulantes($periodoId, $buscar);

            \view('documentos.index', [
                'titulo' => 'Requisitos de Inscripción - SIGIE',
                'postulantes' => $postulantes,
                'periodos' => $periodos,
                'periodoId' => $periodoId,
                'buscar' => $buscar,
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al listar requisitos: ' . $e->getMessage());

            \view('documentos.index', [
                'titulo' => 'Requisitos de Inscripción - SIGIE',
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
            \redirect('/documentos');
        }

        try {
            $postulante = Documento::buscarPostulantePorId($postulanteId);

            if (!$postulante) {
                \set_flash('error', 'El postulante no existe.');
                \redirect('/documentos');
            }

            $documentos = Documento::obtenerPorPostulante($postulanteId);
            $resumen = Documento::obtenerResumenPorPostulante($postulanteId);

            \view('documentos.show', [
                'titulo' => 'Validar Requisitos - SIGIE',
                'postulante' => $postulante,
                'documentos' => $documentos,
                'resumen' => $resumen,
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al cargar requisitos: ' . $e->getMessage());
            \redirect('/documentos');
        }
    }

    public function update()
    {
        \require_login();

        $postulanteId = (int)($_POST['postulante_id'] ?? 0);
        $documentoId = (int)($_POST['documento_id'] ?? 0);
        $estadoValidacion = trim($_POST['estado_validacion'] ?? '');
        $observacion = trim($_POST['observacion'] ?? '');

        if ($postulanteId <= 0) {
            \set_flash('error', 'Postulante no válido.');
            \redirect('/documentos');
        }

        if ($documentoId <= 0) {
            \set_flash('error', 'Requisito no válido.');
            \redirect('/documentos/show&id=' . $postulanteId);
        }

        if (!in_array($estadoValidacion, Documento::estadosValidos(), true)) {
            \set_flash('error', 'El estado seleccionado no es válido.');
            \redirect('/documentos/show&id=' . $postulanteId);
        }

        if ($estadoValidacion === 'Observado' && $observacion === '') {
            \set_flash('error', 'Debes escribir una observación cuando el requisito está observado.');
            \keep_old($_POST);
            \redirect('/documentos/show&id=' . $postulanteId);
        }

        try {
            Documento::actualizarEstado($documentoId, $estadoValidacion, $observacion);

            \set_flash('success', 'Requisito actualizado correctamente.');
            \redirect('/documentos/show&id=' . $postulanteId);
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo actualizar el requisito: ' . $e->getMessage());
            \keep_old($_POST);
            \redirect('/documentos/show&id=' . $postulanteId);
        }
    }
}