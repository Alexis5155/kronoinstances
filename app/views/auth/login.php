<?php
/**
 * Vue unifiée : Connexion / Inscription / Mot de passe oublié / Confirmation
 */
$allowRegister = !empty($allow_register);

$initialPanel = 'login';
if (!empty($register_errors) || !empty($register_old))  $initialPanel = 'register';
if (!empty($forgot_success)  || !empty($forgot_error))  $initialPanel = 'forgot';
if (!empty($register_success))                          $initialPanel = 'register_success';
if (!empty($pending_approval))                          $initialPanel = 'pending_approval';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — KronoInstances</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* ── Fond ──────────────────────────────────────────────── */
        .auth-bg {
            position: fixed; inset: 0; z-index: 0;
            background: linear-gradient(135deg, #03080f, #060f1e, #08172e, #0a1f3d, #0d2856, #0a1f3d, #03080f);
            background-size: 400% 400%;
            animation: bg-shift 20s ease infinite;
        }
        @keyframes bg-shift {
            0%   { background-position:   0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position:   0% 50%; }
        }

        /* ── Grain ─────────────────────────────────────────────── */
        .auth-grain {
            position: fixed; inset: 0; z-index: 1;
            opacity: 0.09; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.50' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            background-size: 180px 180px;
        }

        /* ── Halos ─────────────────────────────────────────────── */
        .auth-halo {
            position: fixed; border-radius: 50%;
            filter: blur(110px); opacity: 0.12; z-index: 1;
            animation: halo-float 10s ease-in-out infinite;
        }
        .h1 { width:500px; height:500px; background:#1d4ed8; top:-150px; left:-150px; animation-delay:0s; }
        .h2 { width:400px; height:400px; background:#4338ca; bottom:-100px; right:-100px; animation-delay:-5s; }
        .h3 { width:250px; height:250px; background:#0369a1; top:50%; left:60%; animation-delay:-2s; }
        @keyframes halo-float {
            0%,100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(30px) scale(1.08); }
        }

        /* ── Wrapper ───────────────────────────────────────────── */
        .auth-wrap {
            position: relative; z-index: 10;
            padding: 1.5rem;
            width: 460px;
            max-width: calc(100vw - 2rem);
            transition: width 0.45s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: auto;
            margin-bottom: auto;
        }
        .auth-wrap.wide { width: 560px; }

        /* ── Carte ─────────────────────────────────────────────── */
        .auth-card {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.12);
            transition: height 0.45s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: visible;
            width: 100%;
        }

        /* Panels clippés entre eux */
        .auth-panels-wrap {
            overflow: hidden;
            border-radius: 24px;
        }

        /* Shake */
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            15%      { transform: translateX(-7px); }
            30%      { transform: translateX(7px); }
            45%      { transform: translateX(-5px); }
            60%      { transform: translateX(5px); }
            75%      { transform: translateX(-3px); }
            90%      { transform: translateX(3px); }
        }
        .auth-card.shake { animation: shake 0.55s cubic-bezier(0.36,0.07,0.19,0.97) both; }

        /* ── Panels ────────────────────────────────────────────── */
        .auth-panel {
            padding: 2.5rem;
            width: 100%;
            opacity: 0;
            position: absolute;
            top: 0; left: 0;
            pointer-events: none;
            transform: scale(0.97);
            transition: opacity 0.35s cubic-bezier(0.4,0,0.2,1), transform 0.35s cubic-bezier(0.4,0,0.2,1);
        }
        .auth-panel.active {
            opacity: 1;
            position: relative;
            pointer-events: auto;
            transform: scale(1);
        }

        /* ── Logo ──────────────────────────────────────────────── */
        .auth-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.7rem; color: white;
            box-shadow: 0 8px 24px rgba(59,130,246,0.45);
            animation: logo-pulse 3s ease-in-out infinite;
        }
        @keyframes logo-pulse {
            0%,100% { box-shadow: 0 8px 24px rgba(59,130,246,0.45); }
            50%      { box-shadow: 0 8px 36px rgba(99,102,241,0.65); }
        }

        /* ── Dots ──────────────────────────────────────────────── */
        .auth-dots { display:flex; gap:6px; justify-content:center; margin-bottom:1.5rem; }
        .auth-dot  { width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,0.2); transition:all 0.3s ease; }
        .auth-dot.active { width:20px; border-radius:3px; background:#6366f1; }

        /* ── Textes ────────────────────────────────────────────── */
        .auth-title    { color:#fff; font-weight:800; font-size:1.4rem; letter-spacing:-0.4px; }
        .auth-subtitle { color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; }
        .auth-label    { color:rgba(255,255,255,0.5); font-size:0.68rem; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; display:block; margin-bottom:0.35rem; }

        /* ── Inputs ────────────────────────────────────────────── */
        .auth-field { position: relative; }
        .auth-field-icon {
            position:absolute; left:14px; top:50%; transform:translateY(-50%);
            color:rgba(255,255,255,0.3); font-size:1rem; pointer-events:none;
            transition:color 0.2s; z-index:2;
        }
        .auth-input {
            width:100%;
            background:rgba(255,255,255,0.08) !important;
            border:1px solid rgba(255,255,255,0.12) !important;
            border-radius:11px !important;
            color:#fff !important;
            padding:0.72rem 1rem 0.72rem 2.6rem !important;
            font-size:0.92rem;
            transition:all 0.25s;
        }
        .auth-input::placeholder { color:rgba(255,255,255,0.22) !important; }
        .auth-input:focus {
            outline:none !important;
            background:rgba(255,255,255,0.13) !important;
            border-color:rgba(99,102,241,0.7) !important;
            box-shadow:0 0 0 3px rgba(99,102,241,0.2) !important;
        }
        .auth-field:focus-within .auth-field-icon { color:#818cf8; }
        .auth-input.input-error {
            border-color: rgba(239,68,68,0.7) !important;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.18) !important;
            animation: input-shake 0.4s cubic-bezier(0.36,0.07,0.19,0.97) both;
        }
        @keyframes input-shake {
            0%,100% { transform: translateX(0); }
            20%      { transform: translateX(-5px); }
            40%      { transform: translateX(5px); }
            60%      { transform: translateX(-3px); }
            80%      { transform: translateX(3px); }
        }
        .auth-input:-webkit-autofill,
        .auth-input:-webkit-autofill:focus {
            -webkit-box-shadow:0 0 0 1000px rgba(4,10,22,.97) inset !important;
            -webkit-text-fill-color:#fff !important;
        }

        /* ── Inline field error ────────────────────────────────── */
        .field-error {
            display: flex; align-items: center; gap: 0.35rem;
            color: #fca5a5; font-size: 0.7rem; font-weight: 600;
            margin-top: 0; padding-left: 0.2rem;
            overflow: hidden;
            max-height: 0; opacity: 0;
            transition: max-height 0.3s ease, opacity 0.3s ease, margin-top 0.3s ease;
        }
        .field-error.show { max-height: 2rem; opacity: 1; margin-top: 0.3rem; }

        /* ── Toasts ────────────────────────────────────────────── */
        .auth-toast-wrap {
            position: fixed;
            top: 1.25rem; left: 50%; transform: translateX(-50%);
            z-index: 200;
            display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
            pointer-events: none;
            width: max-content; max-width: calc(100vw - 2rem);
        }
        .auth-toast {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.7rem 1.1rem;
            border-radius: 14px;
            font-size: 0.83rem; font-weight: 600;
            backdrop-filter: blur(16px);
            box-shadow: 0 6px 28px rgba(0,0,0,0.4);
            pointer-events: auto;
            animation: toast-in 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        .auth-toast.toast-danger  { background:rgba(30,5,5,0.85);   border:1px solid rgba(239,68,68,0.45);  color:#fca5a5; }
        .auth-toast.toast-success { background:rgba(5,25,12,0.85);  border:1px solid rgba(34,197,94,0.4);   color:#86efac; }
        .auth-toast i { font-size:1rem; flex-shrink:0; }
        @keyframes toast-in {
            from { opacity:0; transform:translateY(-16px) scale(0.92); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }
        @keyframes toast-out {
            from { opacity:1; transform:translateY(0) scale(1); }
            to   { opacity:0; transform:translateY(-10px) scale(0.94); }
        }
        .auth-toast.leaving { animation: toast-out 0.3s ease forwards; }

        /* ── Bouton principal ──────────────────────────────────── */
        .auth-btn {
            width:100%; padding:0.85rem;
            border:none; border-radius:11px;
            background:linear-gradient(135deg,#3b82f6 0%,#6366f1 100%);
            background-size:200% 200%;
            color:#fff; font-weight:700; font-size:0.92rem;
            cursor:pointer;
            transition:transform 0.25s, box-shadow 0.25s;
            animation:btn-shimmer 4s ease infinite;
            position:relative; overflow:hidden;
        }
        .auth-btn::after {
            content:''; position:absolute; inset:0;
            background:linear-gradient(135deg,rgba(255,255,255,0.12),transparent);
            opacity:0; transition:opacity 0.3s;
        }
        .auth-btn:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(99,102,241,0.5); }
        .auth-btn:hover::after { opacity:1; }
        .auth-btn:active { transform:translateY(0); }
        @keyframes btn-shimmer {
            0%,100% { background-position:0% 50%; }
            50%      { background-position:100% 50%; }
        }

        /* ── Bouton ghost ──────────────────────────────────────── */
        .auth-btn-ghost {
            width:100%; padding:0.78rem;
            border-radius:11px;
            border:1px solid rgba(255,255,255,0.14);
            background:rgba(255,255,255,0.05);
            color:rgba(255,255,255,0.75);
            font-weight:600; font-size:0.88rem;
            cursor:pointer; text-align:center;
            transition:all 0.25s;
            display:block; text-decoration:none; line-height:1;
        }
        .auth-btn-ghost:hover {
            background:rgba(255,255,255,0.11);
            border-color:rgba(255,255,255,0.25);
            color:#fff; transform:translateY(-1px);
        }

        /* ── Lien texte ────────────────────────────────────────── */
        .auth-link {
            color:rgba(255,255,255,0.38); font-size:0.7rem;
            font-weight:700; text-decoration:none;
            transition:color 0.2s; cursor:pointer;
            background:none; border:none; padding:0;
        }
        .auth-link:hover { color:#93c5fd; }

        /* ── Divider ───────────────────────────────────────────── */
        .auth-divider { display:flex; align-items:center; gap:0.75rem; margin:1.1rem 0; }
        .auth-divider::before, .auth-divider::after { content:''; flex:1; height:1px; background:rgba(255,255,255,0.1); }
        .auth-divider span { color:rgba(255,255,255,0.22); font-size:0.68rem; font-weight:600; letter-spacing:0.5px; text-transform:uppercase; }

        /* ── Grille 2 colonnes ─────────────────────────────────── */
        .auth-row { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }

        /* ── Captcha ───────────────────────────────────────────── */
        .captcha-img { border-radius:8px; cursor:pointer; border:1px solid rgba(255,255,255,0.12); transition:opacity 0.2s; }
        .captcha-img:hover { opacity:0.8; }

        /* ── Entrée carte ──────────────────────────────────────── */
        .auth-card-enter { animation:card-in 0.65s cubic-bezier(.22,.68,0,1.15) both; }
        @keyframes card-in {
            from { opacity:0; transform:translateY(28px) scale(0.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }

        /* ── Footer ────────────────────────────────────────────── */
        .auth-footer { text-align:center; margin-top:1.25rem; color:rgba(255,255,255,0.18); font-size:0.7rem; }
        .auth-footer a { color:rgba(255,255,255,0.28); text-decoration:none; font-weight:600; transition:color 0.2s; }
        .auth-footer a:hover { color:rgba(255,255,255,0.55); }

        /* ── Bouton (?) et bulle ───────────────────────────────── */
        .info-btn {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 100;
            width: 38px; height: 38px; border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(12px);
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem; font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.25s;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }
        .info-btn:hover { background:rgba(99,102,241,0.25); border-color:rgba(99,102,241,0.5); color:#a5b4fc; transform:scale(1.1); }
        .info-btn.open  { background:rgba(99,102,241,0.35); border-color:rgba(99,102,241,0.6); color:#c7d2fe; }

        .info-bubble {
            position: fixed; bottom: 4.5rem; right: 1.5rem; z-index: 99;
            width: 300px;
            background: rgba(10,18,42,0.92); backdrop-filter: blur(20px);
            border: 1px solid rgba(99,102,241,0.3); border-radius: 16px;
            padding: 1.2rem 1.3rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            opacity: 0; transform: translateY(12px) scale(0.96);
            pointer-events: none;
            transition: opacity 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .info-bubble.open { opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
        .info-bubble::after {
            content:''; position:absolute; bottom:-7px; right:12px;
            width:13px; height:13px; background:rgba(10,18,42,0.92);
            border-right:1px solid rgba(99,102,241,0.3); border-bottom:1px solid rgba(99,102,241,0.3);
            transform:rotate(45deg);
        }
        .info-bubble-title { font-size:0.8rem; font-weight:800; color:#a5b4fc; letter-spacing:0.5px; margin-bottom:0.6rem; display:flex; align-items:center; gap:0.4rem; }
        .info-bubble-body  { font-size:0.78rem; line-height:1.55; color:rgba(255,255,255,0.55); }
        .info-bubble-body strong { color:rgba(255,255,255,0.8); }
        .info-bubble hr { border-color:rgba(255,255,255,0.08); margin:0.65rem 0; }

        /* ── Panels de confirmation ─────────────────────────────── */
        .success-icon-wrap {
            position: relative;
            width: 80px; height: 80px;
            margin: 0 auto;
            display: flex; align-items: center; justify-content: center;
        }
        .success-ring {
            position: absolute; inset: 0;
            border-radius: 50%;
            border: 2px solid rgba(99,102,241,0.4);
            animation: ring-pulse 2.5s ease-in-out infinite;
        }
        .success-ring.pending { border-color: rgba(251,191,36,0.4); }
        @keyframes ring-pulse {
            0%,100% { transform: scale(1);    opacity: 0.5; }
            50%      { transform: scale(1.18); opacity: 1;   }
        }
        .success-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #fff;
            box-shadow: 0 8px 24px rgba(99,102,241,0.45);
            animation: icon-pop 0.6s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        .success-icon.pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 8px 24px rgba(251,191,36,0.35);
        }
        @keyframes icon-pop {
            from { transform: scale(0.4); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .success-close-hint {
            display: inline-flex; align-items: center; gap: 0.4rem;
            color: rgba(255,255,255,0.2); font-size: 0.75rem; font-weight: 600;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; padding: 0.4rem 0.9rem;
            animation: hint-fade 0.5s 0.8s ease both;
            opacity: 0;
        }
        @keyframes hint-fade {
            from { opacity:0; transform:translateY(5px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── Mobile petite hauteur ─────────────────────────────── */
        @media (max-height: 700px) {
            body { align-items: flex-start; }
            .auth-wrap { margin-top: 1rem; margin-bottom: 1rem; }
        }

    </style>
</head>
<body>

<div class="auth-bg"></div>
<div class="auth-grain"></div>
<div class="auth-halo h1"></div>
<div class="auth-halo h2"></div>
<div class="auth-halo h3"></div>

<!-- ══ Toasts ═════════════════════════════════════════════════════ -->
<div class="auth-toast-wrap" id="toastWrap"></div>

<!-- ══ BOUTON (?) ═════════════════════════════════════════════════ -->
<button class="info-btn" id="infoBtn" title="Qu'est-ce que KronoInstances ?">?</button>

<!-- ══ BULLE D'INFO ═══════════════════════════════════════════════ -->
<div class="info-bubble" id="infoBubble">
    <div class="info-bubble-title"><i class="bi bi-calendar2-range"></i> KronoInstances</div>
    <div class="info-bubble-body">
        <strong>KronoInstances est le logiciel de gestion des instances paritaires de la collectivité.</strong>
        <hr>Il centralise le suivi des séances, des membres, des ordres du jour et des documents de chaque instance (CST, FSSSCT, CAP, CCP).
    </div>
</div>

<div class="auth-wrap auth-card-enter" id="authWrap">
    <div class="auth-card" id="authCard">
        <div class="auth-panels-wrap" id="panelsWrap">


            <!-- ══ PANEL 0 — CONNEXION ══════════════════════════ -->
            <div class="auth-panel" id="panel-0">

                <div class="text-center mb-4">
                    <div class="auth-logo mb-3"><i class="bi bi-calendar2-range"></i></div>
                    <div class="auth-title">KronoInstances</div>
                    <div class="auth-subtitle mt-1">Gestion des instances paritaires</div>
                </div>

                <div class="auth-dots">
                    <div class="auth-dot" data-panel="0"></div>
                    <?php if ($allowRegister): ?><div class="auth-dot" data-panel="1"></div><?php endif; ?>
                    <div class="auth-dot" data-panel="<?= $allowRegister ? 2 : 1 ?>"></div>
                </div>

                <form method="POST" action="<?= URLROOT ?>/login" id="formLogin"
                      data-server-error="<?= htmlspecialchars($login_error ?? '') ?>"
                      data-verified="<?= isset($_GET['verified']) ? '1' : '' ?>"
                      data-banned="<?= (isset($_GET['error']) && $_GET['error'] === 'banned') ? '1' : '' ?>"
                      data-flash="<?= isset($_SESSION['flash_success']) ? htmlspecialchars($_SESSION['flash_success']) : '' ?>">
                    <?php unset($_SESSION['flash_success']); ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="return"     value="<?= htmlspecialchars($_GET['return'] ?? '') ?>">

                    <div class="mb-3">
                        <label class="auth-label">Identifiant</label>
                        <div class="auth-field">
                            <input type="text" name="username" id="login-username" class="auth-input form-control"
                                   placeholder="Nom d'utilisateur ou e-mail"
                                   required autofocus autocomplete="username">
                            <i class="bi bi-person auth-field-icon"></i>
                        </div>
                        <div class="field-error" id="err-login-username"></div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="auth-label mb-0">Mot de passe</label>
                            <button type="button" class="auth-link" onclick="goPanel(<?= $allowRegister ? 2 : 1 ?>)">Oublié ?</button>
                        </div>
                        <div class="auth-field">
                            <input type="password" name="password" id="login-password" class="auth-input form-control"
                                   placeholder="••••••••" required autocomplete="current-password">
                            <i class="bi bi-lock auth-field-icon"></i>
                        </div>
                        <div class="field-error" id="err-login-password"></div>
                    </div>

                    <button type="submit" class="auth-btn">
                        Se connecter &nbsp;<i class="bi bi-arrow-right-short"></i>
                    </button>
                </form>

                <?php if ($allowRegister): ?>
                    <div class="auth-divider mt-3"><span>Nouveau ?</span></div>
                    <button type="button" class="auth-btn-ghost" onclick="goPanel(1)">
                        <i class="bi bi-person-plus me-2"></i>Créer un compte
                    </button>
                <?php endif; ?>

            </div><!-- /panel-0 -->


            <?php if ($allowRegister): ?>
            <!-- ══ PANEL 1 — INSCRIPTION ════════════════════════ -->
            <div class="auth-panel" id="panel-1">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <button type="button" class="auth-link" onclick="goPanel(0)" style="font-size:1.2rem;">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div>
                        <div class="auth-title" style="font-size:1.2rem;">Créer un compte</div>
                        <div class="auth-subtitle mt-0" style="font-size:0.65rem;">Et accéder au contenu des instances</div>
                    </div>
                </div>

                <div class="auth-dots">
                    <div class="auth-dot" data-panel="0"></div>
                    <div class="auth-dot" data-panel="1"></div>
                    <div class="auth-dot" data-panel="2"></div>
                </div>

                <form method="POST" action="<?= URLROOT ?>/register/submit" id="formRegister"
                      data-server-errors="<?= htmlspecialchars(json_encode($register_errors ?? [])) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

                    <div class="auth-row mb-3">
                        <div>
                            <label class="auth-label">Prénom</label>
                            <div class="auth-field">
                                <input type="text" name="prenom" id="reg-prenom" class="auth-input form-control"
                                       placeholder="Jean"
                                       value="<?= htmlspecialchars($register_old['prenom'] ?? '') ?>" required>
                                <i class="bi bi-person auth-field-icon"></i>
                            </div>
                            <div class="field-error" id="err-prenom"></div>
                        </div>
                        <div>
                            <label class="auth-label">Nom</label>
                            <div class="auth-field">
                                <input type="text" name="nom" id="reg-nom" class="auth-input form-control"
                                       placeholder="Dupont"
                                       value="<?= htmlspecialchars($register_old['nom'] ?? '') ?>" required>
                                <i class="bi bi-person auth-field-icon"></i>
                            </div>
                            <div class="field-error" id="err-nom"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="auth-label">Adresse e-mail</label>
                        <div class="auth-field">
                            <input type="email" name="email" id="reg-email" class="auth-input form-control"
                                   placeholder="jean.dupont@collectivite.fr"
                                   value="<?= htmlspecialchars($register_old['email'] ?? '') ?>" required>
                            <i class="bi bi-envelope auth-field-icon"></i>
                        </div>
                        <div class="field-error" id="err-email"></div>
                    </div>

                    <div class="mb-3">
                        <label class="auth-label">Nom d'utilisateur</label>
                        <div class="auth-field">
                            <input type="text" name="username" id="reg-username" class="auth-input form-control"
                                   placeholder="jean.dupont"
                                   value="<?= htmlspecialchars($register_old['username'] ?? '') ?>" required>
                            <i class="bi bi-at auth-field-icon"></i>
                        </div>
                        <div class="field-error" id="err-username"></div>
                    </div>

                    <div class="auth-row mb-3">
                        <div>
                            <label class="auth-label">Mot de passe</label>
                            <div class="auth-field">
                                <input type="password" name="password" id="reg-password" class="auth-input form-control"
                                       placeholder="8 car. min." required minlength="8">
                                <i class="bi bi-lock auth-field-icon"></i>
                            </div>
                            <div class="field-error" id="err-password"></div>
                        </div>
                        <div>
                            <label class="auth-label">Confirmer</label>
                            <div class="auth-field">
                                <input type="password" name="password2" id="reg-password2" class="auth-input form-control"
                                       placeholder="Confirmer" required>
                                <i class="bi bi-lock-fill auth-field-icon"></i>
                            </div>
                            <div class="field-error" id="err-password2"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="auth-label">Code de sécurité</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="<?= URLROOT ?>/password/captcha?type=register"
                                 id="registerCaptchaImg" class="captcha-img" height="46"
                                 title="Cliquer pour rafraîchir"
                                 onclick="refreshRegisterCaptcha()">
                            <span class="auth-link" onclick="refreshRegisterCaptcha()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Rafraîchir
                            </span>
                        </div>
                        <div class="auth-field">
                            <input type="text" name="captcha_input" id="reg-captcha" class="auth-input form-control"
                                   placeholder="Saisir le code" required autocomplete="off"
                                   style="text-transform:uppercase;letter-spacing:4px;font-weight:700;">
                            <i class="bi bi-shield-check auth-field-icon"></i>
                        </div>
                        <div class="field-error" id="err-captcha"></div>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="bi bi-person-check me-2"></i>Créer mon compte
                    </button>
                </form>

                <div class="text-center mt-3">
                    <button type="button" class="auth-link" onclick="goPanel(0)">
                        Déjà un compte ? Se connecter
                    </button>
                </div>

            </div><!-- /panel-1 -->
            <?php endif; ?>


            <!-- ══ PANEL 2 (ou 1) — MOT DE PASSE OUBLIÉ ════════ -->
            <div class="auth-panel" id="panel-<?= $allowRegister ? 2 : 1 ?>">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <button type="button" class="auth-link" onclick="goPanel(0)" style="font-size:1.2rem;">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div>
                        <div class="auth-title" style="font-size:1.2rem;">Mot de passe oublié</div>
                        <div class="auth-subtitle mt-0" style="font-size:0.65rem;">Un lien vous sera envoyé par e-mail</div>
                    </div>
                </div>

                <div class="auth-dots">
                    <div class="auth-dot" data-panel="0"></div>
                    <?php if ($allowRegister): ?><div class="auth-dot" data-panel="1"></div><?php endif; ?>
                    <div class="auth-dot" data-panel="<?= $allowRegister ? 2 : 1 ?>"></div>
                </div>

                <form method="POST" action="<?= URLROOT ?>/password/forgot" id="formForgot"
                      data-server-error="<?= htmlspecialchars($forgot_error ?? '') ?>"
                      data-server-success="<?= htmlspecialchars($forgot_success ?? '') ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

                    <div class="mb-3">
                        <label class="auth-label">Adresse e-mail du compte</label>
                        <div class="auth-field">
                            <input type="email" name="email" id="forgot-email" class="auth-input form-control"
                                   placeholder="jean.dupont@collectivite.fr" required>
                            <i class="bi bi-envelope auth-field-icon"></i>
                        </div>
                        <div class="field-error" id="err-forgot-email"></div>
                    </div>

                    <div class="mb-4">
                        <label class="auth-label">Code de sécurité</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="<?= URLROOT ?>/password/captcha"
                                 id="captchaImg" class="captcha-img" height="46"
                                 title="Cliquer pour rafraîchir"
                                 onclick="refreshCaptcha()">
                            <span class="auth-link" onclick="refreshCaptcha()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Rafraîchir
                            </span>
                        </div>
                        <div class="auth-field">
                            <input type="text" name="captcha_input" id="forgot-captcha" class="auth-input form-control"
                                   placeholder="Saisir le code" required autocomplete="off"
                                   style="text-transform:uppercase;letter-spacing:4px;font-weight:700;">
                            <i class="bi bi-shield-check auth-field-icon"></i>
                        </div>
                        <div class="field-error" id="err-forgot-captcha"></div>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="bi bi-send me-2"></i>Envoyer le lien
                    </button>
                </form>

                <div class="text-center mt-3">
                    <button type="button" class="auth-link" onclick="goPanel(0)">Retour à la connexion</button>
                </div>

            </div><!-- /panel-forgot -->


            <?php if ($allowRegister): ?>

            <!-- ══ PANEL "COMPTE CRÉÉ — VÉRIFICATION E-MAIL" ════ -->
            <div class="auth-panel" id="panel-success-email">

                <div class="text-center" style="padding: 0.5rem 0 1rem;">
                    <div class="success-icon-wrap mb-4">
                        <div class="success-ring"></div>
                        <div class="success-icon">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>
                    </div>

                    <div class="auth-title mb-2">Compte créé !</div>
                    <div class="auth-subtitle mb-4">Vérification de votre adresse e-mail</div>

                    <p style="color:rgba(255,255,255,0.55); font-size:0.88rem; line-height:1.7; margin-bottom:1.75rem;">
                        Un lien de confirmation a été envoyé à<br>
                        <strong style="color:#a5b4fc;" id="confirm-email-display"></strong><br><br>
                        Cliquez sur ce lien pour activer votre compte.<br>
                        <span style="color:rgba(255,255,255,0.28); font-size:0.76rem;">
                            Pensez à vérifier vos spams si vous ne le recevez pas.
                        </span>
                    </p>

                    <div class="success-close-hint">
                        <i class="bi bi-x-circle"></i>
                        Vous pouvez fermer cette fenêtre
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="button" class="auth-link" onclick="goPanel(0)">
                        Retour à la connexion
                    </button>
                </div>

            </div><!-- /panel-success-email -->


            <!-- ══ PANEL "EN ATTENTE D'APPROBATION" ═════════════ -->
            <div class="auth-panel" id="panel-pending-approval">

                <div class="text-center" style="padding: 0.5rem 0 1rem;">
                    <div class="success-icon-wrap mb-4">
                        <div class="success-ring pending"></div>
                        <div class="success-icon pending">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>

                    <div class="auth-title mb-2">Demande envoyée</div>
                    <div class="auth-subtitle mb-4">En attente d'approbation</div>

                    <p style="color:rgba(255,255,255,0.55); font-size:0.88rem; line-height:1.7; margin-bottom:1.75rem;">
                        Votre compte a bien été créé et est en attente de validation par un administrateur.<br><br>
                        Vous recevrez un e-mail dès que votre accès sera activé.
                    </p>

                    <div class="success-close-hint">
                        <i class="bi bi-x-circle"></i>
                        Vous pouvez fermer cette fenêtre
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="button" class="auth-link" onclick="goPanel(0)">
                        Retour à la connexion
                    </button>
                </div>

            </div><!-- /panel-pending-approval -->

            <?php endif; ?>


        </div><!-- /panelsWrap -->
    </div><!-- /authCard -->

    <div class="auth-footer mt-3">
        <div class="mb-1">&copy; <?= date('Y') ?> — KronoInstances</div>
        <a href="https://github.com/Alexis5155" target="_blank">
            <i class="bi bi-github me-1"></i>Dépôt GitHub
        </a>
    </div>
</div><!-- /authWrap -->

<script>
    const ALLOW_REGISTER = <?= $allowRegister ? 'true' : 'false' ?>;
    const wrap  = document.getElementById('authWrap');
    const card  = document.getElementById('authCard');
    const pwrap = document.getElementById('panelsWrap');

    // Panel initial — peut être un string (success-email, pending-approval) ou un int
    let currentPanel = <?= json_encode(
        $initialPanel === 'register'         ? 1 :
        ($initialPanel === 'forgot'          ? ($allowRegister ? 2 : 1) :
        ($initialPanel === 'register_success'? 'success-email' :
        ($initialPanel === 'pending_approval'? 'pending-approval' : 0)))
    ) ?>;

    // ══ TOASTS ══════════════════════════════════════════════════════════
    const toastWrap = document.getElementById('toastWrap');

    function showToast(message, type = 'danger', duration = 4500) {
        const t = document.createElement('div');
        t.className = `auth-toast toast-${type}`;
        const icon = type === 'danger' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill';
        t.innerHTML = `<i class="bi ${icon}"></i><span>${message}</span>`;
        toastWrap.appendChild(t);
        setTimeout(() => {
            t.classList.add('leaving');
            t.addEventListener('animationend', () => t.remove());
        }, duration);
    }

    // ══ CHAMPS EN ERREUR ════════════════════════════════════════════════
    function setFieldError(inputEl, msgEl, message) {
        inputEl.classList.add('input-error');
        inputEl.addEventListener('input', () => clearFieldError(inputEl, msgEl), { once: true });
        if (msgEl) {
            msgEl.innerHTML = `<i class="bi bi-exclamation-circle-fill" style="font-size:0.65rem;"></i> ${message}`;
            msgEl.classList.add('show');
        }
    }
    function clearFieldError(inputEl, msgEl) {
        inputEl.classList.remove('input-error');
        if (msgEl) msgEl.classList.remove('show');
    }

    // ══ VALIDATION — REGISTER ═══════════════════════════════════════════
    document.getElementById('formRegister')?.addEventListener('submit', function(e) {
        let hasError = false;
        const fields = [
            { id: 'reg-prenom',   err: 'err-prenom',   check: v => v.trim().length > 0,  msg: 'Prénom obligatoire' },
            { id: 'reg-nom',      err: 'err-nom',       check: v => v.trim().length > 0,  msg: 'Nom obligatoire' },
            { id: 'reg-email',    err: 'err-email',     check: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), msg: 'Adresse e-mail invalide' },
            { id: 'reg-username', err: 'err-username',  check: v => v.trim().length >= 3, msg: '3 caractères minimum' },
            { id: 'reg-password', err: 'err-password',  check: v => v.length >= 8,        msg: '8 caractères minimum' },
            { id: 'reg-captcha',  err: 'err-captcha',   check: v => v.trim().length > 0,  msg: 'Code de sécurité requis' },
        ];
        fields.forEach(f => {
            const input = document.getElementById(f.id);
            const errEl = document.getElementById(f.err);
            clearFieldError(input, errEl);
            if (!f.check(input.value)) { setFieldError(input, errEl, f.msg); hasError = true; }
        });
        const p1 = document.getElementById('reg-password');
        const p2 = document.getElementById('reg-password2');
        const ep2 = document.getElementById('err-password2');
        clearFieldError(p2, ep2);
        if (p1.value && p2.value && p1.value !== p2.value) {
            setFieldError(p2, ep2, 'Les mots de passe ne correspondent pas');
            hasError = true;
        }
        if (hasError) {
            e.preventDefault();
            card.classList.remove('shake');
            void card.offsetWidth;
            card.classList.add('shake');
            card.addEventListener('animationend', () => card.classList.remove('shake'), { once: true });
        }
    });

    // ══ VALIDATION — LOGIN ══════════════════════════════════════════════
    document.getElementById('formLogin')?.addEventListener('submit', function(e) {
        let hasError = false;
        const fields = [
            { id: 'login-username', err: 'err-login-username', check: v => v.trim().length > 0, msg: 'Identifiant requis' },
            { id: 'login-password', err: 'err-login-password', check: v => v.length > 0,        msg: 'Mot de passe requis' },
        ];
        fields.forEach(f => {
            const input = document.getElementById(f.id);
            const errEl = document.getElementById(f.err);
            clearFieldError(input, errEl);
            if (!f.check(input.value)) { setFieldError(input, errEl, f.msg); hasError = true; }
        });
        if (hasError) {
            e.preventDefault();
            card.classList.remove('shake');
            void card.offsetWidth;
            card.classList.add('shake');
            card.addEventListener('animationend', () => card.classList.remove('shake'), { once: true });
        }
    });

    // ══ ERREURS SERVEUR AU CHARGEMENT ═══════════════════════════════════
    window.addEventListener('DOMContentLoaded', () => {

        // Login
        const fLogin = document.getElementById('formLogin');
        if (fLogin) {
            if (fLogin.dataset.serverError) showToast(fLogin.dataset.serverError, 'danger');
            if (fLogin.dataset.verified)    showToast('Adresse e-mail vérifiée. Vous pouvez vous connecter.', 'success');
            if (fLogin.dataset.banned)      showToast("Votre compte a été désactivé. Contactez l'administrateur.", 'danger');
            if (fLogin.dataset.flash)       showToast(fLogin.dataset.flash, 'success');
        }

        // Register — erreurs serveur
        const fReg = document.getElementById('formRegister');
        if (fReg) {
            const errors = JSON.parse(fReg.dataset.serverErrors || '[]');
            if (errors.length > 0) {
                showToast(errors.join(' — '), 'danger', 6000);
                const map = [
                    { keys: ['prénom'],                    ids: ['reg-prenom'] },
                    { keys: ['nom'],                       ids: ['reg-nom'] },
                    { keys: ['e-mail', 'email'],           ids: ['reg-email'] },
                    { keys: ["nom d'utilisateur"],         ids: ['reg-username'] },
                    { keys: ['mot de passe', 'correspond'],ids: ['reg-password','reg-password2'] },
                    { keys: ['déjà utilisé'],              ids: ['reg-email','reg-username'] },
                    { keys: ['captcha', 'sécurité', 'code'], ids: ['reg-captcha'] },
                ];
                errors.forEach(msg => {
                    const lower = msg.toLowerCase();
                    map.forEach(m => {
                        if (m.keys.some(k => lower.includes(k))) {
                            m.ids.forEach(id => {
                                const el = document.getElementById(id);
                                if (el) el.classList.add('input-error');
                            });
                        }
                    });
                });
            }
        }

        // Register — succès : afficher l'e-mail dans le panel
        <?php if (!empty($register_success_email)): ?>
        const emailDisplay = document.getElementById('confirm-email-display');
        if (emailDisplay) emailDisplay.textContent = <?= json_encode($register_success_email) ?>;
        <?php endif; ?>

        // Forgot
        const fForgot = document.getElementById('formForgot');
        if (fForgot) {
            if (fForgot.dataset.serverError)   showToast(fForgot.dataset.serverError,   'danger');
            if (fForgot.dataset.serverSuccess) showToast(fForgot.dataset.serverSuccess, 'success', 7000);
        }
    });

    // ══ CAPTCHA ══════════════════════════════════════════════════════════
    function refreshCaptcha() {
        document.getElementById('captchaImg').src = '<?= URLROOT ?>/password/captcha?r=' + Math.random();
    }
    function refreshRegisterCaptcha() {
        document.getElementById('registerCaptchaImg').src = '<?= URLROOT ?>/password/captcha?type=register&r=' + Math.random();
    }

    // ══ AUTO-USERNAME ═════════════════════════════════════════════════════
    (function () {
        const prenom   = document.getElementById('reg-prenom');
        const nom      = document.getElementById('reg-nom');
        const username = document.getElementById('reg-username');
        if (!prenom || !nom || !username) return;

        let userEdited = <?= !empty($register_old['username']) ? 'true' : 'false' ?>;
        username.addEventListener('input', () => { userEdited = true; });

        function slugify(str) {
            return str.trim()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]/g, '');
        }
        function updateUsername() {
            if (userEdited) return;
            const p = slugify(prenom.value);
            const n = slugify(nom.value);
            username.value = (p && n) ? `${p}.${n}` : (p || n);
        }
        prenom.addEventListener('input', updateUsername);
        nom.addEventListener('input',   updateUsername);
        username.addEventListener('keydown', function(e) {
            if ((e.key === 'Backspace' || e.key === 'Delete') && username.value === '') {
                userEdited = false;
            }
        });
    })();

    // ══ NAVIGATION ════════════════════════════════════════════════════════
    function applyWidth(index) {
        wrap.classList.toggle('wide', ALLOW_REGISTER && index === 1);
    }

    function goPanel(index) {
        if (index === currentPanel) return;
        card.style.height = card.offsetHeight + 'px';
        const oldPanel = pwrap.querySelector('.auth-panel.active');
        const newPanel = document.getElementById('panel-' + index);
        if (!newPanel) return;
        if (oldPanel) oldPanel.classList.remove('active');
        newPanel.classList.add('active');
        applyWidth(index);
        requestAnimationFrame(() => {
            card.style.height = newPanel.offsetHeight + 'px';
            card.addEventListener('transitionend', function cleanup(e) {
                if (e.propertyName === 'height') {
                    card.style.height = '';
                    card.removeEventListener('transitionend', cleanup);
                }
            });
        });
        currentPanel = index;
        updateDots(index);
        setTimeout(() => {
            const first = newPanel.querySelector('input:not([type=hidden])');
            if (first) first.focus();
        }, 380);
    }

    function updateDots(active) {
        document.querySelectorAll('.auth-dot').forEach(dot => {
            dot.classList.toggle('active', parseInt(dot.dataset.panel) === active);
        });
    }

    // Init
    const initPanel = document.getElementById('panel-' + currentPanel);
    if (initPanel) initPanel.classList.add('active');
    applyWidth(currentPanel);
    updateDots(currentPanel);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && currentPanel !== 0) goPanel(0);
    });

    // ══ BULLE (?) ══════════════════════════════════════════════════════
    const infoBtn    = document.getElementById('infoBtn');
    const infoBubble = document.getElementById('infoBubble');

    infoBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = infoBubble.classList.toggle('open');
        infoBtn.classList.toggle('open', open);
    });
    document.addEventListener('click', (e) => {
        if (!infoBubble.contains(e.target) && e.target !== infoBtn) {
            infoBubble.classList.remove('open');
            infoBtn.classList.remove('open');
        }
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            infoBubble.classList.remove('open');
            infoBtn.classList.remove('open');
        }
    });
</script>

</body>
</html>
