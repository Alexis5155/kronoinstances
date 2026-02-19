<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - KronoActes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Centrage parfait de la carte sur tous les écrans */
        body { 
            background-color: #f4f7f6; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-card { 
            width: 100%; 
            max-width: 420px; 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.08); 
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            background: #0d6efd;
            color: white;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 15px rgba(13, 110, 253, 0.2);
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            transition: all 0.2s;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #6c757d;
            border-radius: 8px;
        }

        .btn-login {
            padding: 0.8rem;
            border-radius: 8px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        /* Animation d'apparition */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card card mx-auto fade-in">
        <div class="card-body p-5">
            
            <div class="text-center">
                <div class="brand-logo">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">KronoActes</h3>
                <p class="text-muted small mb-4 fw-bold text-uppercase" style="letter-spacing: 1px;">Registre Numérique</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm py-2 small mb-4">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <div><?= $error; ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= URLROOT ?>/login">
                
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <input type="hidden" name="return" value="<?= htmlspecialchars($_GET['return'] ?? '') ?>">

                <div class="mb-3">
                    <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.7rem; letter-spacing: 0.5px;">Identifiant</label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control border-start-0" 
                               placeholder="Nom d'utilisateur" required autofocus>
                    </div>
                </div>

                <?php if(isset($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success d-flex align-items-center border-0 shadow-sm py-2 small mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <div class="d-flex justify-content-between">
                        <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.7rem; letter-spacing: 0.5px;">Mot de passe</label>
                        <a href="<?= URLROOT ?>/password/forgot" class="text-decoration-none small fw-bold" style="font-size: 0.7rem;">Oublié ?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login fw-bold">
                        Se connecter <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <div class="text-center mt-4 text-muted small fade-in" style="animation-delay: 0.2s;">
        <div class="mb-1">&copy; <?= date('Y') ?> — Logiciel de gestion administrative</div>
        <a href="https://github.com/Alexis5155" target="_blank" class="text-decoration-none text-muted fw-bold">
            <i class="bi bi-github me-1"></i>Dépot GitHub
        </a>
    </div>
</div>

</body>
</html>