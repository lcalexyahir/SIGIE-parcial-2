<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\PeriodoAcademico;

class PagoController
{
    public function index()
    {
        \require_login();

        try {
            $periodos = PeriodoAcademico::obtenerTodos();
            $periodoActivo = PeriodoAcademico::obtenerActivo();

            $periodoId = (int)($_GET['periodo_id'] ?? 0);
            $estadoCuenta = trim($_GET['estado_cuenta'] ?? '');
            $buscar = trim($_GET['buscar'] ?? '');

            if ($periodoId <= 0 && $periodoActivo) {
                $periodoId = (int)$periodoActivo['id'];
            }

            $postulantes = Pago::obtenerResumenPostulantes($periodoId, $estadoCuenta, $buscar);

            \view('pagos.index', [
                'titulo' => 'Pagos - SIGIE',
                'postulantes' => $postulantes,
                'montoOficial' => Pago::MONTO_OFICIAL_CUP,
                'periodos' => $periodos,
                'periodoId' => $periodoId,
                'estadoCuenta' => $estadoCuenta,
                'buscar' => $buscar,
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al listar pagos: ' . $e->getMessage());

            \view('pagos.index', [
                'titulo' => 'Pagos - SIGIE',
                'postulantes' => [],
                'montoOficial' => Pago::MONTO_OFICIAL_CUP,
                'periodos' => [],
                'periodoId' => 0,
                'estadoCuenta' => '',
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
            \redirect('/pagos');
        }

        try {
            Pago::asegurarCuentaPorCobrar($postulanteId);

            $postulante = Pago::buscarPostulantePorId($postulanteId);

            if (!$postulante) {
                \set_flash('error', 'El postulante no existe.');
                \redirect('/pagos');
            }

            $pagos = Pago::obtenerPagosPorPostulante($postulanteId);

            \view('pagos.show', [
                'titulo' => 'Detalle de Pagos - SIGIE',
                'postulante' => $postulante,
                'pagos' => $pagos,
                'montoOficial' => Pago::MONTO_OFICIAL_CUP,
                'estadosPago' => Pago::estadosPagoValidos(),
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al cargar pagos: ' . $e->getMessage());
            \redirect('/pagos');
        }
    }

    public function create()
    {
        \require_login();

        $postulanteId = (int)($_GET['postulante_id'] ?? 0);

        try {
            $postulantes = Pago::obtenerPostulantesParaSelect();
            $tiposPago = Pago::obtenerTiposPago();
            $postulanteSeleccionado = null;

            if ($postulanteId > 0) {
                $postulanteSeleccionado = Pago::buscarPostulantePorId($postulanteId);
            }

            \view('pagos.create', [
                'titulo' => 'Registrar Pago - SIGIE',
                'postulantes' => $postulantes,
                'tiposPago' => $tiposPago,
                'postulanteSeleccionado' => $postulanteSeleccionado,
                'montoOficial' => Pago::MONTO_OFICIAL_CUP,
                'estadosPago' => Pago::estadosPagoValidos(),
            ]);
        } catch (\Throwable $e) {
            \set_flash('error', 'Error al cargar formulario de pago: ' . $e->getMessage());
            \redirect('/pagos');
        }
    }

    public function store()
    {
        \require_login();

        $datos = $this->obtenerDatosFormulario();
        $errores = $this->validar($datos);

        if (!empty($errores)) {
            \set_flash('error', implode(' ', $errores));
            \keep_old($_POST);

            if ((int)$datos['postulante_id'] > 0) {
                \redirect('/pagos/create&postulante_id=' . (int)$datos['postulante_id']);
            }

            \redirect('/pagos/create');
        }

        try {
            Pago::registrarPago($datos);

            \set_flash('success', 'Pago registrado correctamente.');
            \redirect('/pagos/show&id=' . (int)$datos['postulante_id']);
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo registrar el pago: ' . $e->getMessage());
            \keep_old($_POST);

            if ((int)$datos['postulante_id'] > 0) {
                \redirect('/pagos/create&postulante_id=' . (int)$datos['postulante_id']);
            }

            \redirect('/pagos/create');
        }
    }

    public function cambiarEstado()
    {
        \require_login();

        $pagoId = (int)($_POST['pago_id'] ?? 0);
        $postulanteId = (int)($_POST['postulante_id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');

        if ($pagoId <= 0) {
            \set_flash('error', 'Pago no válido.');
            \redirect('/pagos');
        }

        if ($postulanteId <= 0) {
            \set_flash('error', 'Postulante no válido.');
            \redirect('/pagos');
        }

        if (!in_array($estado, Pago::estadosPagoValidos(), true)) {
            \set_flash('error', 'El estado del pago no es válido.');
            \redirect('/pagos/show&id=' . $postulanteId);
        }

        try {
            Pago::actualizarEstadoPago($pagoId, $estado);

            \set_flash('success', 'Estado del pago actualizado correctamente.');
            \redirect('/pagos/show&id=' . $postulanteId);
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo actualizar el estado del pago: ' . $e->getMessage());
            \redirect('/pagos/show&id=' . $postulanteId);
        }
    }

    public function generarCuenta()
    {
        \require_login();

        $postulanteId = (int)($_POST['postulante_id'] ?? 0);

        if ($postulanteId <= 0) {
            \set_flash('error', 'Postulante no válido.');
            \redirect('/pagos');
        }

        try {
            Pago::asegurarCuentaPorCobrar($postulanteId);

            \set_flash('success', 'Cuenta por cobrar generada correctamente.');
            \redirect('/pagos/show&id=' . $postulanteId);
        } catch (\Throwable $e) {
            \set_flash('error', 'No se pudo generar la cuenta por cobrar: ' . $e->getMessage());
            \redirect('/pagos');
        }
    }

    private function obtenerDatosFormulario()
    {
        $monto = trim($_POST['monto'] ?? '');
        $monto = str_replace(',', '.', $monto);

        return [
            'postulante_id' => trim($_POST['postulante_id'] ?? ''),
            'tipo_pago_id' => trim($_POST['tipo_pago_id'] ?? ''),
            'monto' => $monto,
            'fecha_pago' => trim($_POST['fecha_pago'] ?? ''),
            'referencia' => trim($_POST['referencia'] ?? ''),
            'estado' => trim($_POST['estado'] ?? 'Pendiente'),
            'comprobante' => trim($_POST['comprobante'] ?? ''),
        ];
    }

    private function validar($datos)
    {
        $errores = [];

        if ($datos['postulante_id'] === '' || (int)$datos['postulante_id'] <= 0) {
            $errores[] = 'Debes seleccionar un postulante.';
        } elseif (!Pago::buscarPostulantePorId($datos['postulante_id'])) {
            $errores[] = 'El postulante seleccionado no existe.';
        }

        if ($datos['tipo_pago_id'] === '' || (int)$datos['tipo_pago_id'] <= 0) {
            $errores[] = 'Debes seleccionar un tipo de pago.';
        } elseif (!Pago::tipoPagoExiste($datos['tipo_pago_id'])) {
            $errores[] = 'El tipo de pago seleccionado no existe.';
        }

        if ($datos['monto'] === '') {
            $errores[] = 'El monto es obligatorio.';
        } elseif (!is_numeric($datos['monto']) || (float)$datos['monto'] <= 0) {
            $errores[] = 'El monto debe ser un número mayor a cero.';
        }

        if (!in_array($datos['estado'], Pago::estadosPagoValidos(), true)) {
            $errores[] = 'El estado del pago no es válido.';
        }

        if ($datos['referencia'] !== '' && strlen($datos['referencia']) > 100) {
            $errores[] = 'La referencia no debe superar los 100 caracteres.';
        }

        if ($datos['referencia'] !== '' && Pago::existeReferencia($datos['referencia'])) {
            $errores[] = 'Ya existe un pago con esa referencia.';
        }

        if ($datos['comprobante'] !== '' && strlen($datos['comprobante']) > 255) {
            $errores[] = 'El comprobante no debe superar los 255 caracteres.';
        }

        if ($datos['fecha_pago'] !== '') {
            $fecha = str_replace('T', ' ', $datos['fecha_pago']);

            if (strtotime($fecha) === false) {
                $errores[] = 'La fecha de pago no tiene un formato válido.';
            }
        }

        return $errores;
    }
}