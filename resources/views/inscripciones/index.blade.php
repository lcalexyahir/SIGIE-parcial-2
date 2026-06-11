<?php $titulo = 'Inscripciones - SIGIE'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Convalidación e inscripción</h2>
        <p class="text-muted mb-0">
            Validación final de requisitos, pagos e inscripción de postulantes
        </p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="<?= e(url('/inscripciones')) ?>" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="/inscripciones">

            <div class="col-md-4">
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

            <div class="col-md-5">
                <label class="form-label">Buscar por nombre, apellido, CI o código</label>
                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    value="<?= e($buscar ?? '') ?>"
                    placeholder="Ej: Diego, 9100007, POST-001"
                >
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    Filtrar
                </button>

                <a href="<?= e(url('/inscripciones')) ?>" class="btn btn-secondary">
                    Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info">
    Este módulo muestra a los postulantes por gestión para verificar documentación, pagos e inscripción.
    Los grupos académicos se administran únicamente desde el módulo <strong>Grupos Académicos</strong>.
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
                    <th>Documentos</th>
                    <th>Pago</th>
                    <th>Inscripción</th>
                    <th>Grupo asignado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($postulantes)): ?>
                    <?php foreach ($postulantes as $postulante): ?>
                        <?php
                            $total = (int)($postulante['total_requisitos'] ?? 0);
                            $aceptados = (int)($postulante['requisitos_aceptados'] ?? 0);
                            $pendientes = (int)($postulante['requisitos_pendientes'] ?? 0);
                            $observados = (int)($postulante['requisitos_observados'] ?? 0);
                            $documentosOk = $total > 0 && $aceptados === $total && $pendientes === 0 && $observados === 0;

                            $estadoCuenta = trim((string)($postulante['estado_cuenta'] ?? 'Pendiente'));
                            $saldo = (float)($postulante['saldo'] ?? 700.00);
                            $pagoOk = $estadoCuenta === 'Pagado' && $saldo <= 0;

                            $inscrito = !empty($postulante['inscripcion_id']);
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
                                <?php if ($documentosOk): ?>
                                    <span class="badge bg-success">Aceptados</span>
                                <?php elseif ($observados > 0): ?>
                                    <span class="badge bg-warning text-dark">Observados</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pendientes</span>
                                <?php endif; ?>

                                <div class="small text-muted">
                                    <?= e($aceptados) ?> / <?= e($total) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($pagoOk): ?>
                                    <span class="badge bg-success">Pagado</span>
                                <?php elseif ($estadoCuenta === 'Parcial'): ?>
                                    <span class="badge bg-warning text-dark">Parcial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pendiente</span>
                                <?php endif; ?>

                                <div class="small text-muted">
                                    Saldo: <?= e(number_format($saldo, 2, ',', '.')) ?> Bs
                                </div>
                            </td>
                            <td>
                                <?php if ($inscrito): ?>
                                    <span class="badge bg-success">Inscrito</span>
                                    <div class="small text-muted">
                                        <?= e($postulante['codigo_inscripcion']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin inscripción</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($inscrito): ?>
                                    <?= e($postulante['grupo_codigo'] ?? '-') ?>
                                    <div class="small text-muted">
                                        <?= e($postulante['grupo_nombre'] ?? '') ?>
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= e(url('/inscripciones/show') . '&id=' . $postulante['postulante_id']) ?>" class="btn btn-sm btn-primary">
                                    Convalidar
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