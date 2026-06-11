<?php $titulo = 'Nuevo Grupo Académico - SIGIE'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Nuevo grupo académico</h2>
        <p class="text-muted mb-0">
            Habilitación manual de grupos cuando los grupos actuales se llenen.
        </p>
    </div>

    <a href="<?= e(url('/grupos-academicos')) ?>" class="btn btn-secondary">
        Volver
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        Datos del grupo
    </div>

    <div class="card-body">
        <form action="<?= e(url('/grupos-academicos/store')) ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Código del grupo</label>
                    <input
                        type="text"
                        name="codigo"
                        class="form-control"
                        value="<?= e(old('codigo')) ?>"
                        placeholder="Ej: M006, T006, N003"
                        required
                    >
                </div>

                <div class="col-md-8">
                    <label class="form-label">Nombre del grupo</label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        value="<?= e(old('nombre')) ?>"
                        placeholder="Ej: Grupo M006 - Mañana"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Capacidad</label>
                    <input
                        type="number"
                        name="capacidad"
                        class="form-control"
                        value="<?= e(old('capacidad', 70)) ?>"
                        min="1"
                        required
                    >
                    <div class="form-text">
                        La capacidad actual de aula es 70. El cálculo general usa divisor 80.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Gestión / Periodo académico</label>
                    <select name="periodo_id" class="form-select" required>
                        <option value="">Seleccionar gestión</option>
                        <?php foreach ($periodos as $periodo): ?>
                            <option value="<?= e($periodo['id']) ?>" <?= selected_value(old('periodo_id'), $periodo['id']) ?>>
                                <?= e($periodo['codigo']) ?> - <?= e($periodo['gestion']) ?>/<?= e($periodo['semestre']) ?>
                                (<?= e($periodo['estado']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <?php foreach ($estadosValidos as $estado): ?>
                            <option value="<?= e($estado) ?>" <?= selected_value(old('estado', 'Activo'), $estado) ?>>
                                <?= e($estado) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= e(url('/grupos-academicos')) ?>" class="btn btn-secondary">
                    Cancelar
                </a>

                <button type="submit" class="btn btn-primary">
                    Guardar grupo
                </button>
            </div>
        </form>
    </div>
</div>