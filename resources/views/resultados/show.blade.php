<?php $titulo = 'Detalle de Resultado Final - SIGIE'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Detalle de resultado final</h2>
        <p class="text-muted mb-0">
            Información del postulante y estado final de admisión.
        </p>
    </div>

    <a href="<?= e(url('/resultados')) ?>" class="btn btn-secondary">
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
                <strong>Código postulante:</strong>
                <div><?= e($resultado['codigo_postulante']) ?></div>
            </div>

            <div class="col-md-4">
                <strong>CI:</strong>
                <div><?= e($resultado['ci']) ?></div>
            </div>

            <div class="col-md-4">
                <strong>Nombre completo:</strong>
                <div><?= e(trim(($resultado['nombres'] ?? '') . ' ' . ($resultado['apellidos'] ?? ''))) ?></div>
            </div>

            <div class="col-md-4">
                <strong>Email:</strong>
                <div><?= e($resultado['email'] ?: '-') ?></div>
            </div>

            <div class="col-md-4">
                <strong>Teléfono:</strong>
                <div><?= e($resultado['telefono'] ?: '-') ?></div>
            </div>

            <div class="col-md-4">
                <strong>Gestión:</strong>
                <div>
                    <?= e($resultado['periodo_codigo'] ?? '-') ?>
                    <?php if (!empty($resultado['gestion'])): ?>
                        - <?= e($resultado['gestion']) ?>/<?= e($resultado['semestre']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Carreras elegidas
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Primera opción:</strong>
                <div><?= e($resultado['carrera_principal'] ?: '-') ?></div>
            </div>

            <div class="col-md-6">
                <strong>Segunda opción:</strong>
                <div><?= e($resultado['carrera_secundaria'] ?: '-') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Resultado final
    </div>

    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <strong>Promedio general:</strong>
                <div class="fs-4">
                    <?= e(number_format((float)$resultado['promedio_general'], 2, ',', '.')) ?>
                </div>
            </div>

            <div class="col-md-4">
                <strong>Estado final:</strong>
                <div>
                    <?php if ($resultado['estado_final'] === 'ADMITIDO'): ?>
                        <span class="badge bg-success fs-6">ADMITIDO</span>
                    <?php elseif ($resultado['estado_final'] === 'APROBADO SIN CUPO'): ?>
                        <span class="badge bg-warning text-dark fs-6">APROBADO SIN CUPO</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6">REPROBADO</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <strong>Estado de postulación:</strong>
                <div><?= e($resultado['estado_postulacion'] ?: '-') ?></div>
            </div>

            <div class="col-md-6">
                <strong>Carrera admitida:</strong>
                <div><?= e($resultado['carrera_admitida'] ?: '-') ?></div>
            </div>

            <div class="col-md-6">
                <strong>Opción admitida:</strong>
                <div>
                    <?php if ((int)($resultado['opcion_admitida'] ?? 0) === 1): ?>
                        Primera opción
                    <?php elseif ((int)($resultado['opcion_admitida'] ?? 0) === 2): ?>
                        Segunda opción
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        Observación
    </div>

    <div class="card-body">
        <?php if (!empty($resultado['observacion'])): ?>
            <p class="mb-0"><?= e($resultado['observacion']) ?></p>
        <?php else: ?>
            <p class="text-muted mb-0">Sin observación registrada.</p>
        <?php endif; ?>
    </div>
</div>