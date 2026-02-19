<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - KronoActes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style> /* Même style que forgot.php */
        body { background-color: #f4f7f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { width: 100%; max-width: 420px; border: none; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .brand-logo { width: 60px; height: 60px; background: #198754; color: white; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .form-control { padding: 0.75rem 1rem; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card card mx-auto">
        <div class="card-body p-5">
            <div class="text-center">
                <div class="brand-logo"><i class="bi bi-shield-lock"></i></div>
                <h3 class="fw-bold text-dark mb-1">Sécurité</h3>
                <p class="text-muted small mb-4">Choisissez un nouveau mot de passe.</p>
            </div>

            <?php if(!empty($data['error'])): ?>
                <div class="alert alert-danger py-2 small mb-4 border-0 shadow-sm"><?= $data['error']; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.7rem;">Nouveau mot de passe</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 8 caractères" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.7rem;">Confirmez le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success fw-bold py-2">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>