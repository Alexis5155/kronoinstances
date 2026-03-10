<?php include __DIR__ . '/../layouts/header.php'; ?>
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#f8f9ff 0%,#eef2ff 100%);">
    <div class="text-center" style="max-width:440px;">
        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width:80px;height:80px;">
            <i class="bi bi-envelope-check" style="font-size:2.2rem;"></i>
        </div>
        <h3 class="fw-bold text-dark mb-2">Vérifiez votre e-mail</h3>
        <p class="text-muted mb-1">Un lien de vérification a été envoyé à</p>
        <p class="fw-bold text-dark mb-4"><?= htmlspecialchars($email ?? 'votre adresse e-mail') ?></p>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <p class="text-muted small mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Cliquez sur le lien dans l'e-mail pour activer votre compte. Pensez à vérifier vos spams.</p>
        </div>
        <a href="<?= URLROOT ?>/login" class="btn btn-light border fw-bold rounded-pill px-4 mt-4 shadow-sm">
            <i class="bi bi-arrow-left me-2"></i>Retour à la connexion
        </a>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
