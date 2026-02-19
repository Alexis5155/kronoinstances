<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupération - KronoActes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* On reprend exactement le style de login.php */
        body { background-color: #f4f7f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { width: 100%; max-width: 420px; border: none; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .brand-logo { width: 60px; height: 60px; background: #0d6efd; color: white; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .form-control { padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #dee2e6; background-color: #f8f9fa; }
        .fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card card mx-auto fade-in">
        <div class="card-body p-5">
            <div class="text-center">
                <div class="brand-logo"><i class="bi bi-envelope-at"></i></div>
                <h3 class="fw-bold text-dark mb-1">Récupération</h3>
                <p class="text-muted small mb-4">Saisissez votre email pour recevoir un lien.</p>
            </div>

            <?php if(!empty($data['error'])): ?>
                <div class="alert alert-danger py-2 small mb-4 border-0 shadow-sm"><i class="bi bi-exclamation-circle me-2"></i><?= $data['error']; ?></div>
            <?php endif; ?>

            <?php if(!empty($data['success'])): ?>
                <div class="alert alert-success py-2 small mb-4 border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i><?= $data['success']; ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.7rem;">Votre adresse email</label>
                        <input type="email" name="email" class="form-control" placeholder="exemple@mairie.fr" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.7rem;">Code de sécurité</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img src="<?= URLROOT ?>/password/captcha" alt="Captcha" class="rounded border" id="captcha-img" style="height: 40px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('captcha-img').src='<?= URLROOT ?>/password/captcha?'+Math.random();">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <input type="text" name="captcha_input" class="form-control" placeholder="Recopiez le code" required autocomplete="off">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary fw-bold py-2">Envoyer le lien</button>
                        <a href="<?= URLROOT ?>/login" class="btn btn-link btn-sm text-decoration-none text-muted">Retour à la connexion</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>