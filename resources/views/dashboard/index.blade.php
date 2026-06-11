<?php $titulo = 'Dashboard - SIGIE'; ?>

<style>
    /* ===== SIGIE - DASHBOARD - SISTEMA DE ADMISIÓN ESTUDIANTIL ===== */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap');

    :root {
        --navy:        #0d1f35;
        --navy-mid:    #162d47;
        --blue:        #1a5f9c;
        --blue-light:  #2e86d4;
        --gold:        #c89b3c;
        --gold-light:  #e8c06a;
        --cream:       #f4f7fb;
        --white:       #ffffff;
        --muted:       #6b82a0;
        --border:      #dce6f0;
        --success:     #1a7a4a;
        --success-bg:  #e8f5ee;
        --warn:        #b45309;
        --warn-bg:     #fef3e2;
        --info:        #1a5f9c;
        --info-bg:     #e8f0fb;
        --purple:      #5b4fcf;
        --purple-bg:   #eeecfb;
        --red:         #b91c1c;
        --red-bg:      #fde8e8;
        --teal:        #0f7d6b;
        --teal-bg:     #e6f5f3;
    }

    /* Reset solo para el contenido del dashboard, sin afectar el layout global */
    .sigie-dash * {
        box-sizing: border-box;
    }

    .sigie-dash {
        font-family: 'DM Sans', system-ui, sans-serif;
        background: var(--cream);
        min-height: 100vh;
        padding: 0;
    }

    /* ── Barra superior ── */
    .dash-topbar {
        background: var(--white);
        border-bottom: 1px solid var(--border);
        padding: 0.9rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 8px rgba(13,31,53,0.06);
        animation: slideDown 0.4s ease-out both;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .topbar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .topbar-logo {
        width: 38px;
        height: 38px;
        object-fit: contain;
    }

    .topbar-name {
        font-family: 'Playfair Display', serif;
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--navy);
        line-height: 1;
    }

    .topbar-sub {
        font-size: 0.68rem;
        color: var(--muted);
        font-weight: 400;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .topbar-date {
        font-size: 0.78rem;
        color: var(--muted);
        display: none;
    }

    @media (min-width: 640px) { .topbar-date { display: block; } }

    .topbar-user {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: var(--cream);
        border: 1px solid var(--border);
        border-radius: 100px;
        padding: 0.35rem 0.85rem 0.35rem 0.45rem;
    }

    .user-avatar {
        width: 30px;
        height: 30px;
        background: linear-gradient(135deg, var(--navy), var(--blue));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        color: white;
        font-weight: 700;
        flex-shrink: 0;
    }

    .user-name {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--navy-mid);
        max-width: 140px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Contenido principal ── */
    .dash-content {
        padding: 2rem;
        max-width: 1280px;
        margin: 0 auto;
    }

    @media (max-width: 640px) { .dash-content { padding: 1.25rem; } }

    /* ── Hero header ── */
    .dash-hero {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 50%, var(--blue) 100%);
        border-radius: 20px;
        padding: 2rem 2.25rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        animation: fadeUp 0.5s 0.1s ease-out both;
    }

    .dash-hero::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(200,155,60,0.18) 0%, transparent 70%);
    }

    .dash-hero::after {
        content: '';
        position: absolute;
        bottom: -40px; left: 30%;
        width: 160px; height: 160px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(46,134,212,0.2) 0%, transparent 70%);
    }

    .dash-hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .hero-left h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.4rem, 4vw, 1.85rem);
        font-weight: 700;
        color: var(--white);
        margin-bottom: 0.3rem;
        line-height: 1.2;
    }

    .hero-left h2 span {
        color: var(--gold-light);
    }

    .hero-left p {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.65);
        font-weight: 300;
    }

    .hero-badge {
        background: rgba(200,155,60,0.18);
        border: 1px solid rgba(200,155,60,0.4);
        border-radius: 100px;
        padding: 0.5rem 1.1rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gold-light);
        letter-spacing: 0.05em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    /* ── Sección de título ── */
    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--navy);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    /* ── Grid de tarjetas estadísticas ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    }

    .stat-card {
        background: var(--white);
        border-radius: 16px;
        padding: 1.25rem 1.4rem;
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        animation: fadeUp 0.5s ease-out both;
    }

    .stat-card:nth-child(1) { animation-delay: 0.15s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s;  }
    .stat-card:nth-child(3) { animation-delay: 0.25s; }
    .stat-card:nth-child(4) { animation-delay: 0.3s;  }
    .stat-card:nth-child(5) { animation-delay: 0.35s; }
    .stat-card:nth-child(6) { animation-delay: 0.4s;  }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(13,31,53,0.1);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 16px 16px 0 0;
    }

    /* Colores por tipo */
    .stat-card.c-blue   { --c: var(--info);    --cbg: var(--info-bg);    }
    .stat-card.c-green  { --c: var(--success);  --cbg: var(--success-bg); }
    .stat-card.c-purple { --c: var(--purple);   --cbg: var(--purple-bg);  }
    .stat-card.c-warn   { --c: var(--warn);     --cbg: var(--warn-bg);    }
    .stat-card.c-teal   { --c: var(--teal);     --cbg: var(--teal-bg);    }
    .stat-card.c-red    { --c: var(--red);      --cbg: var(--red-bg);     }

    .stat-card::before { background: var(--c); }

    .stat-icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--cbg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 0.85rem;
    }

    .stat-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--c);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.3rem;
    }

    .stat-value {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.9rem, 5vw, 2.4rem);
        font-weight: 700;
        color: var(--navy);
        line-height: 1;
    }

    /* ── Tarjeta info ── */
    .info-card {
        background: var(--white);
        border-radius: 18px;
        border: 1px solid var(--border);
        padding: 1.75rem 2rem;
        animation: fadeUp 0.5s 0.45s ease-out both;
        position: relative;
        overflow: hidden;
    }

    .info-card::after {
        content: '';
        position: absolute;
        right: -30px; bottom: -30px;
        width: 140px; height: 140px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(26,95,156,0.05) 0%, transparent 70%);
        pointer-events: none;
    }

    .info-card-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.1rem;
    }

    .info-card-icon {
        width: 46px;
        height: 46px;
        background: linear-gradient(135deg, var(--navy), var(--blue));
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(13,31,53,0.2);
    }

    .info-card-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--navy);
        line-height: 1.3;
        margin-bottom: 0.15rem;
    }

    .info-card-sub {
        font-size: 0.8rem;
        color: var(--muted);
        font-weight: 400;
    }

    .gold-rule {
        width: 40px;
        height: 2px;
        background: linear-gradient(90deg, var(--gold), var(--gold-light));
        border-radius: 2px;
        margin: 1rem 0;
    }

    .info-card p {
        font-size: 0.88rem;
        color: #4a5f78;
        line-height: 1.7;
        margin-bottom: 0.5rem;
    }

    .info-card p:last-child { margin-bottom: 0; }

    .modules-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.75rem;
    }

    .module-chip {
        background: var(--info-bg);
        color: var(--info);
        border-radius: 100px;
        padding: 0.28rem 0.75rem;
        font-size: 0.73rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        border: 1px solid rgba(26,95,156,0.15);
    }

    /* Animaciones */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Touch devices */
    @media (hover: none) and (pointer: coarse) {
        .stat-card:active { transform: scale(0.97); }
    }
</style>

<div class="sigie-dash">

    <!-- Barra superior -->
    <div class="dash-topbar">
        <div class="topbar-brand">
            <img src="https://www.fictt.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png"
                 alt="FICCT" class="topbar-logo"
                 onerror="this.style.display='none'">
            <div>
                <div class="topbar-name">SIGAUCP</div>
                <div class="topbar-sub">FICCT · UAGRM</div>
            </div>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="dash-date"></span>
            <div class="topbar-user">
                <div class="user-avatar" id="user-initials">U</div>
                <span class="user-name"><?= e($_SESSION['usuario']['nombre_completo'] ?? 'Usuario') ?></span>
            </div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="dash-content">

        <!-- Hero -->
        <div class="dash-hero">
            <div class="dash-hero-inner">
                <div class="hero-left">
                    <h2>Panel <span>Principal</span></h2>
                    <p>Sistema de Gestion de Informacion Estudiantil y Admisiones — SIGAUCP</p>
                </div>
                <div class="hero-badge">Dashboard</div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="section-title">Resumen general</div>

        <div class="stats-grid">

            <div class="stat-card c-blue">
                <div class="stat-icon-wrap">🎓</div>
                <div class="stat-label">Total usuarios</div>
                <div class="stat-value"><?= e($totalUsuarios ?? 0) ?></div>
            </div>

            <div class="stat-card c-green">
                <div class="stat-icon-wrap">✅</div>
                <div class="stat-label">Usuarios activos</div>
                <div class="stat-value"><?= e($usuariosActivos ?? 0) ?></div>
            </div>

            <div class="stat-card c-purple">
                <div class="stat-icon-wrap">🔐</div>
                <div class="stat-label">Roles registrados</div>
                <div class="stat-value"><?= e($totalRoles ?? 0) ?></div>
            </div>

            <div class="stat-card c-warn">
                <div class="stat-icon-wrap">🏫</div>
                <div class="stat-label">Carreras registradas</div>
                <div class="stat-value"><?= e($totalCarreras ?? 0) ?></div>
            </div>

            <div class="stat-card c-teal">
                <div class="stat-icon-wrap">📌</div>
                <div class="stat-label">Cupos disponibles</div>
                <div class="stat-value"><?= e($totalCupos ?? 0) ?></div>
            </div>

            <div class="stat-card c-red">
                <div class="stat-icon-wrap">🧾</div>
                <div class="stat-label">Postulantes registrados</div>
                <div class="stat-value"><?= e($totalPostulantes ?? 0) ?></div>
            </div>

        </div>

        <!-- Tarjeta de bienvenida -->
        <div class="section-title">Información del sistema</div>

        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">📘</div>
                <div>
                    <div class="info-card-title">
                        Bienvenido, <?= e($_SESSION['usuario']['nombre_completo'] ?? 'Usuario') ?>
                    </div>
                    <div class="info-card-sub">SIGAUCP</div>
                </div>
            </div>
            <div class="gold-rule"></div>
            <p>Este es el panel principal del sistema SIGAUCP.</p>
            <p>Casos de uso activos:</p>
            <div class="modules-list">
                <span class="module-chip">🔑 Login / Logout</span>
                <span class="module-chip">👥 Gestión de Usuarios</span>
                <span class="module-chip">🔐 Roles</span>
                <span class="module-chip">🏫 Carreras</span>
                <span class="module-chip">📌 Cupos por Carrera</span>
                <span class="module-chip">🧾 Registro de Postulantes</span>
            </div>
        </div>

    </div><!-- /dash-content -->

</div><!-- /sigie-dash -->

<script>
    // Fecha actual en la barra superior
    (function() {
        var d = new Date();
        var opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        var el = document.getElementById('dash-date');
        if (el) el.textContent = d.toLocaleDateString('es-BO', opts);

        // Iniciales del usuario
        var nameEl = document.querySelector('.user-name');
        var avEl   = document.getElementById('user-initials');
        if (nameEl && avEl) {
            var parts = nameEl.textContent.trim().split(' ');
            avEl.textContent = (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
        }
    })();
</script>