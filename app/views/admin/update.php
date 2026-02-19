<?php include __DIR__ . '/../header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div id="update-header-bg" class="bg-primary p-4 text-center text-white transition-all">
                    <div id="update-icon-container" class="mb-3">
                        <i id="update-icon" class="bi bi-cloud-arrow-down-fill display-1"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Mise à jour système</h3>
                    <p class="opacity-75 small mb-0" id="current-step-label">Prêt pour le déploiement</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Progression globale</span>
                            <span id="progress-percent" class="fw-bold text-primary small">0%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 12px;">
                            <div id="update-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="text-start bg-dark text-light p-3 rounded-3 font-monospace mb-4 shadow-inner" 
                         style="height: 220px; overflow-y: auto; font-size: 0.8rem; border: 1px solid #343a40;" 
                         id="update-console">
                        <div class="text-muted border-bottom border-secondary pb-1 mb-2" style="font-size: 0.7rem;">TERMINAL DE SORTIE</div>
                        <div class="text-success fw-bold">> Initialisation de KronoActes Update...</div>
                        <div class="text-info">> Prêt pour l'installation.</div>
                    </div>

                    <div id="update-actions" class="text-center">
                        <button onclick="startUpdate()" class="btn btn-primary btn-lg fw-bold px-5 py-3 shadow-sm w-100" id="btn-start">
                            <i class="bi bi-play-fill me-2"></i>Lancer la mise à jour
                        </button>
                    </div>
                    
                    <div id="update-finished" class="text-center d-none">
                        <div class="alert alert-success border-0 py-3">
                            <i class="bi bi-check-circle-fill me-2"></i> Redirection vers les paramètres...
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted small">Ne fermez pas cette fenêtre pendant l'opération.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .transition-all { transition: all 0.5s ease; }
    #update-console::-webkit-scrollbar { width: 6px; }
    #update-console::-webkit-scrollbar-thumb { background: #495057; border-radius: 10px; }
    .shadow-inner { box-shadow: inset 0 2px 4px rgba(0,0,0,0.3); }
    
    /* Animation de rotation pour l'icône de chargement */
    .spin { animation: spin 2s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<script>
const consoleBox = document.getElementById('update-console');
const progressBar = document.getElementById('update-progress');
const progressPercent = document.getElementById('progress-percent');
const stepLabel = document.getElementById('current-step-label');
const updateIcon = document.getElementById('update-icon');
const headerBg = document.getElementById('update-header-bg');

function log(msg, color = 'text-light') {
    const line = document.createElement('div');
    line.className = "mb-1 " + color;
    line.innerHTML = `<span class="opacity-50 me-2">#</span>${msg}`;
    consoleBox.appendChild(line);
    consoleBox.scrollTop = consoleBox.scrollHeight;
}

async function runStep(url, progress, label) {
    stepLabel.innerText = label;
    progressBar.style.width = progress + '%';
    progressPercent.innerText = progress + '%';
    log(label.toUpperCase() + "...", "text-warning fw-bold");
    
    try {
        const response = await fetch('<?= URLROOT ?>/update/' + url);
        const data = await response.json();
        
        if(!data.status) throw new Error(data.message);
        
        log(data.message, 'text-success');
        return true;
    } catch (e) {
        log("ERREUR CRITIQUE : " + e.message, 'text-danger fw-bold');
        updateIcon.className = "bi bi-exclamation-triangle-fill display-1";
        headerBg.className = "bg-danger p-4 text-center text-white transition-all";
        stepLabel.innerText = "Échec de la mise à jour";
        return false;
    }
}

async function startUpdate() {
    // UI Init
    document.getElementById('btn-start').classList.add('disabled');
    document.getElementById('btn-start').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Opération en cours...';
    updateIcon.className = "bi bi-arrow-repeat display-1 d-inline-block spin";
    
    // Étape 1 : Téléchargement
    if (!await runStep('download', 30, "Téléchargement des fichiers")) return;
    
    // Étape 2 : Installation
    if (!await runStep('install', 70, "Déploiement et synchronisation")) return;
    
    // Étape 3 : Nettoyage
    if (!await runStep('cleanup', 100, "Finalisation et nettoyage")) return;

    // Fin de procédure
    stepLabel.innerText = "Système mis à jour !";
    updateIcon.className = "bi bi-check-all display-1";
    headerBg.className = "bg-success p-4 text-center text-white transition-all";
    log("PROCÉDURE TERMINÉE AVEC SUCCÈS.", "text-info fw-bold");
    
    document.getElementById('update-actions').classList.add('d-none');
    document.getElementById('update-finished').classList.remove('d-none');
    
    setTimeout(() => {
        window.location.href = '<?= URLROOT ?>/admin/parametres?section=update';
    }, 3000);
}
</script>

<?php include __DIR__ . '/../footer.php'; ?>