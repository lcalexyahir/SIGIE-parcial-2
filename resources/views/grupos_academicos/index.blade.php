<?php $titulo = 'Grupos Académicos - SIGIE'; ?>

<?php
$totalGrupos = (int)($indicadores['total_grupos'] ?? 0);
$totalInscritos = (int)($indicadores['total_inscritos'] ?? 0);
$capacidadTotal = (int)($indicadores['capacidad_total'] ?? 0);
$cuposLibres = (int)($indicadores['cupos_libres'] ?? 0);
$divisorCalculo = (int)($indicadores['divisor_calculo_grupos'] ?? 80);
$gruposNecesarios = (int)($indicadores['grupos_necesarios_divisor_80'] ?? 0);
$porcentajeGeneral = (float)($indicadores['porcentaje_general'] ?? 0);
$gruposActivos = (int)($indicadores['grupos_activos'] ?? 0);
$gruposSaturados = (int)($indicadores['grupos_saturados'] ?? 0);
$gruposInactivos = (int)($indicadores['grupos_inactivos'] ?? 0);
$gruposCerrados = (int)($indicadores['grupos_cerrados'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Grupos académicos</h2>
        <p class="text-muted mb-0">
            CU08 - Cálculo y habilitación de grupos académicos con divisor <?= e($divisorCalculo) ?>
        </p>
    </div>

    <div class="d-flex gap-2">
        <a href="<?= e(url('/grupos-academicos/create')) ?>" class="btn btn-success">
            Nuevo grupo
        </a>

        <form action="<?= e(url('/grupos-academicos/recalcular')) ?>" method="POST">
            <button type="submit" class="btn btn-primary">
                Recalcular grupos
            </button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total grupos</div>
                <h3><?= e($totalGrupos) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Inscritos</div>
                <h3><?= e($totalInscritos) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Capacidad total</div>
                <h3><?= e($capacidadTotal) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Cupos libres</div>
                <h3><?= e($cuposLibres) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Divisor de cálculo</div>
                <h3><?= e($divisorCalculo) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Grupos necesarios</div>
                <h3><?= e($gruposNecesarios) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-warning shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Ocupación general</div>
                <h3><?= e($porcentajeGeneral) ?>%</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Activos / Saturados</div>
                <h3><?= e($gruposActivos) ?> / <?= e($gruposSaturados) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        Estado de grupos
    </div>

    <div class="card-body">
        <p class="mb-1"><strong>Activos:</strong> <?= e($gruposActivos) ?></p>
        <p class="mb-1"><strong>Saturados:</strong> <?= e($gruposSaturados) ?></p>
        <p class="mb-1"><strong>Inactivos:</strong> <?= e($gruposInactivos) ?></p>
        <p class="mb-0"><strong>Cerrados:</strong> <?= e($gruposCerrados) ?></p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        Listado de grupos académicos
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Gestión</th>
                    <th>Capacidad</th>
                    <th>Inscritos</th>
                    <th>Cupos libres</th>
                    <th>Ocupación</th>
                    <th>Estado</th>
                    <th>Cambiar estado</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($grupos)): ?>
                    <?php foreach ($grupos as $grupo): ?>
                        <?php
                            $estado = trim((string)($grupo['estado'] ?? ''));
                            $estadoLower = strtolower($estado);
                        ?>
                        <tr>
                            <td><?= e($grupo['codigo']) ?></td>
                            <td><?= e($grupo['nombre']) ?></td>
                            <td>
                                <?= e($grupo['periodo_codigo'] ?? '-') ?>
                                <?php if (!empty($grupo['gestion'])): ?>
                                    <div class="small text-muted">
                                        <?= e($grupo['gestion']) ?>/<?= e($grupo['semestre']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($grupo['capacidad']) ?></td>
                            <td><?= e($grupo['inscritos_reales']) ?></td>
                            <td><?= e($grupo['cupos_libres']) ?></td>
                            <td><?= e($grupo['porcentaje_ocupacion']) ?>%</td>
                            <td>
                                <?php if ($estadoLower === 'activo'): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php elseif ($estadoLower === 'saturado'): ?>
                                    <span class="badge bg-danger">Saturado</span>
                                <?php elseif ($estadoLower === 'inactivo'): ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php elseif ($estadoLower === 'cerrado'): ?>
                                    <span class="badge bg-dark">Cerrado</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark"><?= e($estado) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="<?= e(url('/grupos-academicos/cambiar-estado')) ?>" method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="grupo_id" value="<?= e($grupo['id']) ?>">

                                    <select name="estado" class="form-select form-select-sm">
                                        <?php foreach ($estadosValidos as $estadoValido): ?>
                                            <option value="<?= e($estadoValido) ?>" <?= selected_value($grupo['estado'], $estadoValido) ?>>
                                                <?= e($estadoValido) ?>
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
                        <td colspan="9" class="text-center text-muted">
                            No hay grupos académicos registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>