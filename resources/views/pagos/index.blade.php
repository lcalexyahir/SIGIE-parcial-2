<?php $titulo = 'Pagos - SIGIE'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Control de pagos</h2>
        <p class="text-muted mb-0">
            Gestión financiera del pago oficial del CUP
        </p>
    </div>

    <a href="<?= e(url('/pagos/create')) ?>" class="btn btn-primary">
        Registrar pago
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="<?= e(url('/pagos')) ?>" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="/pagos">

            <div class="col-md-3">
                <label class="form-label">Gestión / Periodo</label>
                <select name="periodo_id" class="form-select">
                    <option value="">Todas las gestiones</option>
                    <?php foreach ($periodos as $periodo): ?>
                        <option value="<?= e($periodo['id']) ?>" <?= selected_value($periodoId, $periodo['id']) ?>>
                            <?= e($periodo['codigo']) ?> - <?= e($periodo['gestion']) ?>/<?= e($periodo['semestre']) ?>
                            (<?= e($periodo['estado']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Estado de cuenta</label>
                <select name="estado_cuenta" class="form-select">
                    <option value="" <?= selected_value($estadoCuenta ?? '', '') ?>>Todos</option>
                    <option value="Pagado" <?= selected_value($estadoCuenta ?? '', 'Pagado') ?>>Pagados</option>
                    <option value="Pendiente" <?= selected_value($estadoCuenta ?? '', 'Pendiente') ?>>Pendientes</option>
                    <option value="Parcial" <?= selected_value($estadoCuenta ?? '', 'Parcial') ?>>Parciales</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Buscar por nombre, apellido, CI o código</label>
                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    value="<?= e($buscar ?? '') ?>"
                    placeholder="Ej: Diego, 9100007, POST-001"
                >
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    Filtrar
                </button>

                <a href="<?= e(url('/pagos')) ?>" class="btn btn-secondary">
                    Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info">
    Monto oficial del CUP:
    <strong><?= e(number_format((float)$montoOficial, 2, ',', '.')) ?> Bs</strong>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>CI</th>
                    <th>Postulante</th>
                    <th>Gestión</th>
                    <th>Carrera principal</th>
                    <th>Total pagado</th>
                    <th>Saldo</th>
                    <th>Estado cuenta</th>
                    <th>Pagos</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($postulantes)): ?>
                    <?php foreach ($postulantes as $postulante): ?>
                        <?php
                            $estado = trim((string)($postulante['estado_cuenta'] ?? 'Pendiente'));
                            $estadoLower = strtolower($estado);
                        ?>

                        <tr>
                            <td><?= e($postulante['postulante_id']) ?></td>
                            <td><?= e($postulante['codigo_postulante']) ?></td>
                            <td><?= e($postulante['ci']) ?></td>
                            <td><?= e(trim(($postulante['nombres'] ?? '') . ' ' . ($postulante['apellidos'] ?? ''))) ?></td>
                            <td>
                                <?= e($postulante['periodo_codigo'] ?? '-') ?>
                                <?php if (!empty($postulante['gestion'])): ?>
                                    <div class="small text-muted">
                                        <?= e($postulante['gestion']) ?>/<?= e($postulante['semestre']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($postulante['carrera_principal'] ?? '-') ?></td>
                            <td>
                                <?= e(number_format((float)$postulante['total_pagado_aceptado'], 2, ',', '.')) ?> Bs
                            </td>
                            <td>
                                <?= e(number_format((float)$postulante['saldo'], 2, ',', '.')) ?> Bs
                            </td>
                            <td>
                                <?php if ($estadoLower === 'pagado'): ?>
                                    <span class="badge bg-success">Pagado</span>
                                <?php elseif ($estadoLower === 'parcial'): ?>
                                    <span class="badge bg-warning text-dark">Parcial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    Total: <?= e($postulante['total_pagos']) ?><br>
                                    Aceptados: <?= e($postulante['pagos_aceptados']) ?><br>
                                    Pendientes: <?= e($postulante['pagos_pendientes']) ?><br>
                                    Rechazados: <?= e($postulante['pagos_rechazados']) ?>
                                </div>
                            </td>
                            <td>
                                <a href="<?= e(url('/pagos/show') . '&id=' . $postulante['postulante_id']) ?>" class="btn btn-sm btn-primary">
                                    Ver pagos
                                </a>

                                <a href="<?= e(url('/pagos/create') . '&postulante_id=' . $postulante['postulante_id']) ?>" class="btn btn-sm btn-success">
                                    Registrar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted">
                            No se encontraron postulantes con los filtros seleccionados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>