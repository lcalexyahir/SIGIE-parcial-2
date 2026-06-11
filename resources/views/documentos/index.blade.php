<?php $titulo = 'Requisitos de Inscripción - SIGIE'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Requisitos de inscripción</h2>
        <p class="text-muted mb-0">
            Control y validación de requisitos presentados por los postulantes
        </p>
    </div>

    <a href="<?= e(url('/postulantes')) ?>" class="btn btn-secondary">
        Ver postulantes
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="<?= e(url('/documentos')) ?>" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="/documentos">

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

                <a href="<?= e(url('/documentos')) ?>" class="btn btn-secondary">
                    Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="alert alert-info mb-0">
            Desde este módulo puedes revisar los requisitos generados automáticamente al registrar un postulante
            y cambiar su estado a <strong>Pendiente</strong>, <strong>Aceptado</strong> u <strong>Observado</strong>.
        </div>
    </div>
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
                    <th>Estado postulación</th>
                    <th>Requisitos</th>
                    <th>Pendientes</th>
                    <th>Aceptados</th>
                    <th>Observados</th>
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
                            $porcentaje = $total > 0 ? round(($aceptados / $total) * 100) : 0;
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
                                <span class="badge bg-primary">
                                    <?= e($postulante['estado_postulacion']) ?>
                                </span>
                            </td>
                            <td>
                                <div><?= e($aceptados) ?> / <?= e($total) ?></div>
                                <div class="progress" style="height: 8px;">
                                    <div
                                        class="progress-bar"
                                        role="progressbar"
                                        style="width: <?= e($porcentaje) ?>%;"
                                        aria-valuenow="<?= e($porcentaje) ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    ></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= e($pendientes) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= e($aceptados) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark"><?= e($observados) ?></span>
                            </td>
                            <td>
                                <a href="<?= e(url('/documentos/show') . '&id=' . $postulante['postulante_id']) ?>" class="btn btn-sm btn-primary">
                                    Validar
                                </a>

                                <a href="<?= e(url('/postulantes/show') . '&id=' . $postulante['postulante_id']) ?>" class="btn btn-sm btn-info text-white">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted">
                            No se encontraron postulantes con los filtros seleccionados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>