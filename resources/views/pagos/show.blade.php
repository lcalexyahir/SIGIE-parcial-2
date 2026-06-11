<?php $titulo = 'Detalle de Pagos - SIGIE'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Detalle de pagos</h2>
        <p class="text-muted mb-0">
            Historial financiero del postulante y validación de pagos.
        </p>
    </div>

    <a href="<?= e(url('/pagos')) ?>" class="btn btn-secondary">
        Volver
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Datos del postulante
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <strong>Código:</strong>
                <div><?= e($postulante['codigo']) ?></div>
            </div>

            <div class="col-md-4">
                <strong>CI:</strong>
                <div><?= e($postulante['ci']) ?></div>
            </div>

            <div class="col-md-4">
                <strong>Postulante:</strong>
                <div><?= e(trim(($postulante['nombres'] ?? '') . ' ' . ($postulante['apellidos'] ?? ''))) ?></div>
            </div>

            <div class="col-md-4">
                <strong>Gestión:</strong>
                <div>
                    <?= e($postulante['periodo_codigo'] ?? '-') ?>
                    <?php if (!empty($postulante['gestion'])): ?>
                        <span class="text-muted">
                            <?= e($postulante['gestion']) ?>/<?= e($postulante['semestre']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <strong>Carrera principal:</strong>
                <div><?= e($postulante['carrera_principal'] ?? '-') ?></div>
            </div>

            <div class="col-md-4">
                <strong>Carrera secundaria:</strong>
                <div><?= e($postulante['carrera_secundaria'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-primary">
            <div class="card-body">
                <div class="text-muted small">Monto oficial CUP</div>
                <h4><?= e(number_format((float)$montoOficial, 2, ',', '.')) ?> Bs</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-success">
            <div class="card-body">
                <div class="text-muted small">Total pagado aceptado</div>
                <h4><?= e(number_format((float)($postulante['total_pagado_aceptado'] ?? 0), 2, ',', '.')) ?> Bs</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-warning">
            <div class="card-body">
                <div class="text-muted small">Saldo</div>
                <h4><?= e(number_format((float)($postulante['saldo'] ?? 0), 2, ',', '.')) ?> Bs</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-info">
            <div class="card-body">
                <div class="text-muted small">Estado cuenta</div>

                <?php $estadoCuenta = trim((string)($postulante['estado_cuenta'] ?? 'Pendiente')); ?>

                <?php if ($estadoCuenta === 'Pagado'): ?>
                    <h4><span class="badge bg-success">Pagado</span></h4>
                <?php elseif ($estadoCuenta === 'Parcial'): ?>
                    <h4><span class="badge bg-warning text-dark">Parcial</span></h4>
                <?php else: ?>
                    <h4><span class="badge bg-danger">Pendiente</span></h4>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mb-4 d-flex gap-2">
    <a href="<?= e(url('/pagos/create') . '&postulante_id=' . $postulante['id']) ?>" class="btn btn-primary">
        Registrar nuevo pago
    </a>

    <?php if (empty($postulante['cuenta_id'])): ?>
        <form action="<?= e(url('/pagos/generar-cuenta')) ?>" method="POST">
            <input type="hidden" name="postulante_id" value="<?= e($postulante['id']) ?>">
            <button type="submit" class="btn btn-warning">
                Generar cuenta por cobrar
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        Historial de pagos
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th>Referencia</th>
                    <th>Comprobante</th>
                    <th>Estado</th>
                    <th>Cambiar estado</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($pagos)): ?>
                    <?php foreach ($pagos as $pago): ?>
                        <?php
                            $tipoPago = trim((string)($pago['tipo_pago'] ?? ''));
                            $esQR = strtolower($tipoPago) === 'qr';
                        ?>

                        <tr>
                            <td><?= e($pago['codigo']) ?></td>

                            <td>
                                <?php if ($esQR): ?>
                                    <span class="badge bg-primary">QR</span>
                                    <div class="small text-muted">Pasarela simulada</div>
                                <?php else: ?>
                                    <?= e($tipoPago ?: '-') ?>
                                <?php endif; ?>
                            </td>

                            <td><?= e(number_format((float)$pago['monto'], 2, ',', '.')) ?> Bs</td>

                            <td><?= e(format_date($pago['fecha_pago'])) ?></td>

                            <td>
                                <?= e($pago['referencia'] ?: '-') ?>

                                <?php if ($esQR && !empty($pago['referencia'])): ?>
                                    <div class="small text-muted">
                                        Referencia generada por QR
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($pago['comprobante'] ?: '-') ?>

                                <?php if ($esQR): ?>
                                    <div class="small text-muted">
                                        Comprobante de pasarela QR
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($pago['estado'] === 'Aceptado'): ?>
                                    <span class="badge bg-success">Aceptado</span>
                                <?php elseif ($pago['estado'] === 'Rechazado'): ?>
                                    <span class="badge bg-danger">Rechazado</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <form action="<?= e(url('/pagos/cambiar-estado')) ?>" method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="pago_id" value="<?= e($pago['id']) ?>">
                                    <input type="hidden" name="postulante_id" value="<?= e($postulante['id']) ?>">

                                    <select name="estado" class="form-select form-select-sm">
                                        <?php foreach ($estadosPago as $estado): ?>
                                            <option value="<?= e($estado) ?>" <?= selected_value($pago['estado'], $estado) ?>>
                                                <?= e($estado) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Guardar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No existen pagos registrados para este postulante.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>