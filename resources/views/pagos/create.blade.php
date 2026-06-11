<?php $titulo = 'Registrar Pago - SIGIE'; ?>

<?php
$postulanteIdOld = old('postulante_id', $postulanteSeleccionado['id'] ?? '');
$tipoPagoOld = old('tipo_pago_id', '');
$montoOld = old('monto', number_format((float)$montoOficial, 2, '.', ''));
$referenciaOld = old('referencia', '');
$estadoOld = old('estado', 'Pendiente');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Registrar pago</h2>
        <p class="text-muted mb-0">
            Pasarela de pago simulada mediante QR para el CUP.
        </p>
    </div>

    <a href="<?= e(url('/pagos')) ?>" class="btn btn-secondary">
        Volver
    </a>
</div>

<div class="alert alert-info">
    Monto oficial del CUP:
    <strong><?= e(number_format((float)$montoOficial, 2, ',', '.')) ?> Bs</strong>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">
                Datos del pago
            </div>

            <div class="card-body">
                <form action="<?= e(url('/pagos/store')) ?>" method="POST" id="formPago">
                    <div class="mb-3">
                        <label class="form-label">Postulante</label>
                        <select name="postulante_id" id="postulante_id" class="form-select" required>
                            <option value="">Seleccionar postulante</option>

                            <?php foreach ($postulantes as $postulante): ?>
                                <?php
                                    $nombreCompleto = trim(($postulante['nombres'] ?? '') . ' ' . ($postulante['apellidos'] ?? ''));
                                    $textoPostulante = ($postulante['codigo'] ?? '') . ' - ' . ($postulante['ci'] ?? '') . ' - ' . $nombreCompleto;

                                    if (!empty($postulante['periodo_codigo'])) {
                                        $textoPostulante .= ' - ' . $postulante['periodo_codigo'];
                                    }
                                ?>

                                <option
                                    value="<?= e($postulante['id']) ?>"
                                    data-codigo="<?= e($postulante['codigo'] ?? '') ?>"
                                    data-ci="<?= e($postulante['ci'] ?? '') ?>"
                                    data-nombre="<?= e($nombreCompleto) ?>"
                                    <?= selected_value($postulanteIdOld, $postulante['id']) ?>
                                >
                                    <?= e($textoPostulante) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de pago</label>
                        <select name="tipo_pago_id" id="tipo_pago_id" class="form-select" required>
                            <option value="">Seleccionar tipo de pago</option>

                            <?php foreach ($tiposPago as $tipo): ?>
                                <option
                                    value="<?= e($tipo['id']) ?>"
                                    data-nombre="<?= e($tipo['nombre']) ?>"
                                    <?= selected_value($tipoPagoOld, $tipo['id']) ?>
                                >
                                    <?= e($tipo['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="monto"
                            id="monto"
                            class="form-control"
                            value="<?= e($montoOld) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha de pago</label>
                        <input
                            type="datetime-local"
                            name="fecha_pago"
                            class="form-control"
                            value="<?= e(old('fecha_pago')) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Referencia</label>
                        <input
                            type="text"
                            name="referencia"
                            id="referencia"
                            class="form-control"
                            value="<?= e($referenciaOld) ?>"
                            placeholder="Ej: QR-POST-001-700"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado del pago</label>
                        <select name="estado" class="form-select" required>
                            <?php foreach ($estadosPago as $estado): ?>
                                <option value="<?= e($estado) ?>" <?= selected_value($estadoOld, $estado) ?>>
                                    <?= e($estado) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Comprobante</label>
                        <input
                            type="text"
                            name="comprobante"
                            id="comprobante"
                            class="form-control"
                            value="<?= e(old('comprobante')) ?>"
                            placeholder="Ej: Comprobante QR / Captura enviada por WhatsApp"
                        >
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= e(url('/pagos')) ?>" class="btn btn-secondary">
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Guardar pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm" id="panelQr" style="display: none;">
            <div class="card-header bg-primary text-white">
                Pasarela de pago QR
            </div>

            <div class="card-body text-center">
                <p class="text-muted">
                    Escanea este código QR para realizar el pago del CUP.
                </p>

                <div class="border rounded p-3 mb-3 bg-white">
                    <svg width="260" height="260" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
                        <rect width="300" height="300" fill="white"/>

                        <rect x="20" y="20" width="90" height="90" fill="black"/>
                        <rect x="38" y="38" width="54" height="54" fill="white"/>
                        <rect x="56" y="56" width="22" height="22" fill="black"/>

                        <rect x="190" y="20" width="90" height="90" fill="black"/>
                        <rect x="208" y="38" width="54" height="54" fill="white"/>
                        <rect x="226" y="56" width="22" height="22" fill="black"/>

                        <rect x="20" y="190" width="90" height="90" fill="black"/>
                        <rect x="38" y="208" width="54" height="54" fill="white"/>
                        <rect x="56" y="226" width="22" height="22" fill="black"/>

                        <rect x="125" y="30" width="16" height="16" fill="black"/>
                        <rect x="155" y="30" width="32" height="16" fill="black"/>
                        <rect x="125" y="75" width="48" height="16" fill="black"/>
                        <rect x="175" y="90" width="32" height="16" fill="black"/>

                        <rect x="125" y="125" width="32" height="32" fill="black"/>
                        <rect x="165" y="125" width="16" height="48" fill="black"/>
                        <rect x="195" y="125" width="48" height="16" fill="black"/>
                        <rect x="250" y="125" width="24" height="24" fill="black"/>

                        <rect x="125" y="175" width="16" height="48" fill="black"/>
                        <rect x="150" y="190" width="48" height="16" fill="black"/>
                        <rect x="205" y="175" width="16" height="48" fill="black"/>
                        <rect x="230" y="190" width="48" height="16" fill="black"/>
                        <rect x="265" y="165" width="16" height="48" fill="black"/>

                        <rect x="125" y="235" width="32" height="16" fill="black"/>
                        <rect x="165" y="220" width="16" height="48" fill="black"/>
                        <rect x="195" y="235" width="48" height="16" fill="black"/>
                        <rect x="250" y="220" width="16" height="48" fill="black"/>
                        <rect x="270" y="235" width="16" height="16" fill="black"/>

                        <text x="150" y="295" text-anchor="middle" font-size="14" font-weight="bold" font-family="Arial">
                            SIGAUCP - QR
                        </text>
                    </svg>
                </div>

                <div class="text-start small">
                    <p class="mb-1">
                        <strong>Postulante:</strong>
                        <span id="qrPostulante">-</span>
                    </p>

                    <p class="mb-1">
                        <strong>CI:</strong>
                        <span id="qrCi">-</span>
                    </p>

                    <p class="mb-1">
                        <strong>Monto:</strong>
                        <span id="qrMonto">700.00</span> Bs
                    </p>

                    <p class="mb-1">
                        <strong>Referencia:</strong>
                        <span id="qrReferencia">-</span>
                    </p>
                </div>

                <hr>

                <button type="button" class="btn btn-outline-primary w-100" onclick="generarReferenciaQR()">
                    Generar referencia QR
                </button>

                <p class="text-muted small mt-3 mb-0">
                    Esta pasarela es simulada para fines académicos. El administrativo valida el pago cambiando el estado a Aceptado.
                </p>
            </div>
        </div>

        <div class="card shadow-sm" id="panelAyuda">
            <div class="card-header">
                Ayuda
            </div>

            <div class="card-body">
                <p class="mb-2">
                    Para usar la pasarela simulada:
                </p>

                <ol class="mb-0">
                    <li>Selecciona un postulante.</li>
                    <li>Selecciona el tipo de pago <strong>QR</strong>.</li>
                    <li>Genera la referencia QR.</li>
                    <li>Guarda el pago como <strong>Pendiente</strong> o <strong>Aceptado</strong>.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
function obtenerTipoPagoSeleccionado() {
    const select = document.getElementById('tipo_pago_id');
    const option = select.options[select.selectedIndex];

    if (!option) {
        return '';
    }

    return (option.getAttribute('data-nombre') || '').toLowerCase();
}

function obtenerPostulanteSeleccionado() {
    const select = document.getElementById('postulante_id');
    const option = select.options[select.selectedIndex];

    if (!option) {
        return {
            id: '',
            codigo: '',
            ci: '',
            nombre: ''
        };
    }

    return {
        id: option.value || '',
        codigo: option.getAttribute('data-codigo') || '',
        ci: option.getAttribute('data-ci') || '',
        nombre: option.getAttribute('data-nombre') || ''
    };
}

function actualizarPanelQR() {
    const tipoPago = obtenerTipoPagoSeleccionado();
    const panelQr = document.getElementById('panelQr');
    const panelAyuda = document.getElementById('panelAyuda');

    if (tipoPago === 'qr') {
        panelQr.style.display = 'block';
        panelAyuda.style.display = 'none';
        actualizarDatosQR();
    } else {
        panelQr.style.display = 'none';
        panelAyuda.style.display = 'block';
    }
}

function actualizarDatosQR() {
    const postulante = obtenerPostulanteSeleccionado();
    const monto = document.getElementById('monto').value || '0.00';
    const referencia = document.getElementById('referencia').value || '-';

    document.getElementById('qrPostulante').textContent = postulante.nombre || '-';
    document.getElementById('qrCi').textContent = postulante.ci || '-';
    document.getElementById('qrMonto').textContent = parseFloat(monto || 0).toFixed(2);
    document.getElementById('qrReferencia').textContent = referencia;
}

function generarReferenciaQR() {
    const postulante = obtenerPostulanteSeleccionado();
    const monto = document.getElementById('monto').value || '0';
    const referenciaInput = document.getElementById('referencia');
    const comprobanteInput = document.getElementById('comprobante');

    if (!postulante.id) {
        alert('Primero debes seleccionar un postulante.');
        return;
    }

    const fecha = new Date();
    const fechaTexto =
        fecha.getFullYear().toString() +
        String(fecha.getMonth() + 1).padStart(2, '0') +
        String(fecha.getDate()).padStart(2, '0') +
        String(fecha.getHours()).padStart(2, '0') +
        String(fecha.getMinutes()).padStart(2, '0') +
        String(fecha.getSeconds()).padStart(2, '0');

    const codigo = postulante.codigo || ('POST-' + postulante.id);
    const montoEntero = Math.round(parseFloat(monto || 0));

    const referencia = 'QR-' + codigo + '-' + montoEntero + '-' + fechaTexto;

    referenciaInput.value = referencia;
    comprobanteInput.value = 'Pasarela QR SIGAUCP';

    actualizarDatosQR();
}

document.getElementById('tipo_pago_id').addEventListener('change', actualizarPanelQR);
document.getElementById('postulante_id').addEventListener('change', actualizarDatosQR);
document.getElementById('monto').addEventListener('input', actualizarDatosQR);
document.getElementById('referencia').addEventListener('input', actualizarDatosQR);

actualizarPanelQR();
actualizarDatosQR();
</script>