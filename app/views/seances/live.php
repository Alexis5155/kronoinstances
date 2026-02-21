<?php 
// Mode "SPA" (Single Page Application) sans le header/footer habituel
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Séance en direct - KronoActes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; height: 100vh; overflow: hidden; }
        
        /* RESPONSIVE LAYOUT */
        .sidebar { background: #fff; border-right: 1px solid #e9ecef; display: flex; flex-direction: column; }
        @media (min-width: 768px) {
            .sidebar { height: 100vh; overflow-y: auto; position: fixed; width: 25%; z-index: 1000; }
            .main-content { margin-left: 25%; height: 100vh; overflow-y: auto; padding: 2rem; width: 75%; }
        }
        @media (max-width: 767.98px) {
            .main-content { height: calc(100vh - 60px); overflow-y: auto; padding: 1rem; }
        }

        /* LIVE ICON ANIMATION */
        @keyframes pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .live-indicator {
            display: inline-block; width: 12px; height: 12px; border-radius: 50%;
            background-color: #dc3545; animation: pulse-red 2s infinite;
        }

        .nav-point { cursor: pointer; padding: 1rem; border-bottom: 1px solid #f1f3f5; transition: 0.2s; border-left: 4px solid transparent; }
        .nav-point:hover { background: #f8f9fa; }
        .nav-point.active { background: #e0f2fe; border-left-color: #0d6efd; font-weight: bold; }
        .quorum-gauge { height: 8px; border-radius: 4px; background: #e9ecef; overflow: hidden; }
        .quorum-fill { height: 100%; transition: width 0.4s ease; }
        textarea.debats { resize: none; border-radius: 12px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); background: #fafafa; }
        textarea.debats:focus { background: #fff; }
        .vote-card { border: 1px solid #dee2e6; border-radius: 12px; background: #fff; }
        .saving-indicator { font-size: 0.8rem; color: #adb5bd; transition: opacity 0.3s; opacity: 0; }
        .saving-indicator.show { opacity: 1; }
    </style>
</head>
<body>

<!-- HEADER MOBILE (Visible uniquement sur petits écrans) -->
<div class="d-md-none bg-dark text-white p-3 d-flex justify-content-between align-items-center shadow-sm">
    <div class="fw-bold text-truncate" style="max-width: 70%;">
        <span class="live-indicator me-2"></span> <?= htmlspecialchars($seance['instance_nom']) ?>
    </div>
    <button class="btn btn-sm btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
        <i class="bi bi-list"></i> Menu
    </button>
</div>

<div class="container-fluid p-0">
    <div class="row g-0">
        
        <!-- SIDEBAR GAUCHE : NAVIGATION (Offcanvas sur mobile) -->
        <div class="col-md-3 sidebar offcanvas-md offcanvas-start" tabindex="-1" id="sidebarMenu">
            <div class="p-3 border-bottom bg-dark text-white sticky-top d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 fw-bold d-flex align-items-center">
                        <span class="live-indicator me-2"></span> EN DIRECT
                    </h5>
                    <div>
                        <button type="button" class="btn-close btn-close-white d-md-none me-2" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu"></button>
                        <a href="<?= URLROOT ?>/seances/view/<?= $seance['id'] ?>" class="btn btn-sm btn-outline-light border-0" title="Quitter le mode Live">
                            <i class="bi bi-box-arrow-left"></i>
                        </a>
                    </div>
                </div>
                <div class="small opacity-75 text-truncate" title="<?= htmlspecialchars($seance['instance_nom']) ?>">
                    <?= htmlspecialchars($seance['instance_nom']) ?>
                </div>
                
                <!-- QUORUM DYNAMIQUE PAR COLLÈGE -->
                <div class="mt-3 bg-white bg-opacity-10 p-2 rounded">
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span style="font-size: 0.7rem;">Admin.</span>
                            <span id="q-admin-txt" class="fw-bold" style="font-size: 0.75rem;">0 / 0</span>
                        </div>
                        <div class="quorum-gauge" style="height: 6px;">
                            <div id="q-admin-bar" class="quorum-fill bg-danger" style="width: 0%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span style="font-size: 0.7rem;">Personnel</span>
                            <span id="q-pers-txt" class="fw-bold" style="font-size: 0.75rem;">0 / 0</span>
                        </div>
                        <div class="quorum-gauge" style="height: 6px;">
                            <div id="q-pers-bar" class="quorum-fill bg-danger" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-grow-1" id="nav-points-container" style="overflow-y: auto;">
                <!-- Généré par JS -->
            </div>
            
            <div class="p-3 border-top bg-light mt-auto">
                <a href="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=terminee" 
                   class="btn btn-success w-100 fw-bold"
                   onclick="return confirm('Voulez-vous vraiment clôturer cette séance ?')">
                    <i class="bi bi-stop-circle-fill me-2"></i>Clôturer la séance
                </a>
            </div>
        </div>

        <!-- ZONE PRINCIPALE -->
        <div class="col-md-9 main-content" id="main-view">
            <!-- Généré par JS -->
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- ========================================== -->
<!-- SCRIPTS LOGIQUES                           -->
<!-- ========================================== -->
<script>
    const API_BASE = "<?= URLROOT ?>/seances";
    const SEANCE_ID = <?= $seance['id'] ?>;
    const QUORUM_REQUIS = <?= $seance['quorum_requis'] ?>;
    const INSTANCE_NOM = "<?= addslashes(htmlspecialchars($seance['instance_nom'])) ?>";
    
    // Données initiales
    let points = <?= json_encode($points) ?>;
    let membres = <?= json_encode($membres) ?>;
    
    let rawPresences = <?= json_encode($presences) ?>;
    let presences = {};
    if(Array.isArray(rawPresences)) {
        rawPresences.forEach(p => presences[p.membre_id] = p);
    } else { presences = rawPresences; }

    let rawVotes = <?= json_encode($votes) ?>;
    let votes = {};
    for (let ptId in rawVotes) {
        votes[ptId] = {};
        rawVotes[ptId].forEach(v => { votes[ptId][v.college] = v; });
    }

    let currentIndex = -1; 
    let saveTimeout = null;
    let isTyping = false; // Permet de savoir si on tape pour ne pas écraser le texte via l'auto-refresh

    document.addEventListener("DOMContentLoaded", () => {
        renderNavigation();
        updateQuorumUI();
        renderCurrentView();
        
        // Polling : Actualisation toutes les 5 secondes
        setInterval(fetchLiveState, 5000);
    });

    // ==========================================
    // MOTEUR DE RENDU
    // ==========================================

    function setView(index) {
        currentIndex = index;
        renderNavigation();
        renderCurrentView();
        
        // Fermer le menu offcanvas sur mobile après un clic
        let offcanvasEl = document.getElementById('sidebarMenu');
        let bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (bsOffcanvas) { bsOffcanvas.hide(); }
    }

    function renderNavigation() {
        const container = document.getElementById('nav-points-container');
        let html = `
            <div class="nav-point ${currentIndex === -1 ? 'active' : ''}" onclick="setView(-1)">
                <div class="fw-bold"><i class="bi bi-people-fill me-2"></i>Appel des présents</div>
            </div>
        `;
        points.forEach((pt, idx) => {
            let isVote = ['vote', 'deliberation', 'avis'].includes((pt.type_point || '').toLowerCase());
            let badge = isVote ? '<span class="badge bg-danger ms-2" style="font-size:0.6rem">Vote</span>' : '';
            html += `
                <div class="nav-point ${currentIndex === idx ? 'active' : ''}" onclick="setView(${idx})">
                    <div class="small text-muted mb-1">Point ${idx + 1}</div>
                    <div class="fw-bold lh-sm">${pt.titre} ${badge}</div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function renderCurrentView() {
        const container = document.getElementById('main-view');
        if (currentIndex === -1) {
            container.innerHTML = renderAppel();
        } else {
            container.innerHTML = renderPoint(points[currentIndex], currentIndex);
        }
    }

    // ==========================================
    // ACTUALISATION TEMPS RÉEL (POLLING)
    // ==========================================
    function fetchLiveState() {
        fetch(`${API_BASE}/getLiveState/${SEANCE_ID}`)
        .then(res => res.json())
        .then(data => {
            points = data.points;
            
            // Mise à jour des présences (sans re-rendre si on est sur la page Appel pour éviter les sauts)
            if(Array.isArray(data.presences)) {
                data.presences.forEach(p => presences[p.membre_id] = p);
            } else { presences = data.presences; }
            
            // Mise à jour des votes
            for (let ptId in data.votes) {
                if(!votes[ptId]) votes[ptId] = {};
                data.votes[ptId].forEach(v => { votes[ptId][v.college] = v; });
            }

            // Si on est sur un point et qu'on ne tape PAS, on met à jour les champs
            if (currentIndex >= 0 && !isTyping) {
                let currentPt = points[currentIndex];
                let txtArea = document.getElementById('debats-textarea');
                
                if (txtArea && txtArea.value !== currentPt.debats) {
                    txtArea.value = currentPt.debats || '';
                }

                ['administration', 'personnel'].forEach(col => {
                    if(votes[currentPt.id] && votes[currentPt.id][col]) {
                        let v = votes[currentPt.id][col];
                        updateInputIfDiff(`v_${currentPt.id}_${col}_pour`, v.nb_pour);
                        updateInputIfDiff(`v_${currentPt.id}_${col}_contre`, v.nb_contre);
                        updateInputIfDiff(`v_${currentPt.id}_${col}_abst`, v.nb_abstention);
                        updateInputIfDiff(`v_${currentPt.id}_${col}_refus`, v.nb_refus_vote);
                    }
                });
            }

            updateQuorumUI();
        });
    }

    function updateInputIfDiff(id, val) {
        let el = document.getElementById(id);
        if(el && parseInt(el.value) !== parseInt(val)) { el.value = val; }
    }

    // ==========================================
    // VUE 1 : APPEL DES PRÉSENTS
    // ==========================================

    function renderAppel() {
        let html = `
            <div class="mb-4 border-bottom pb-3">
                <span class="text-primary fw-bold small text-uppercase letter-spacing-1">${INSTANCE_NOM}</span>
                <h2 class="fw-bold mb-0 mt-1"><i class="bi bi-people-fill text-primary me-2"></i>Appel des présents</h2>
            </div>
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary fw-bold" onclick="setView(0)">Commencer l'ordre du jour <i class="bi bi-arrow-right ms-2"></i></button>
            </div>
            <div class="row g-4">
        `;

        ['administration', 'personnel'].forEach(college => {
            let titulaires = membres.filter(m => m.college === college && m.type_mandat === 'titulaire');
            let suppleants = membres.filter(m => m.college === college && m.type_mandat === 'suppleant');
            
            html += `
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold py-3 text-uppercase small letter-spacing-1">Collège ${college}</div>
                    <div class="card-body p-0"><ul class="list-group list-group-flush">
            `;

            titulaires.forEach(tit => {
                let p = presences[tit.id] || { est_present: 0, remplace_par_id: null };
                let isPresent = p.est_present == 1;
                
                let suppleantOptions = `<option value="">-- Aucun remplacement --</option>`;
                suppleants.forEach(sup => {
                    let sel = p.remplace_par_id == sup.id ? 'selected' : '';
                    suppleantOptions += `<option value="${sup.id}" ${sel}>${sup.nom} ${sup.prenom}</option>`;
                });

                html += `
                    <li class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold">${tit.nom} ${tit.prenom}</div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input" type="checkbox" id="pres_${tit.id}" 
                                       ${isPresent ? 'checked' : ''} onchange="togglePresence(${tit.id}, this.checked)">
                                <label class="form-check-label ms-2 small fw-bold ${isPresent ? 'text-success' : 'text-danger'}" for="pres_${tit.id}">
                                    ${isPresent ? 'Présent' : 'Absent'}
                                </label>
                            </div>
                        </div>
                        <div id="remplacement_${tit.id}" class="bg-light p-2 rounded mt-2" style="display: ${isPresent ? 'none' : 'block'};">
                            <label class="small text-muted fw-bold mb-1"><i class="bi bi-arrow-return-right me-1"></i>Remplacé par (Suppléant) :</label>
                            <select class="form-select form-select-sm" onchange="setRemplacement(${tit.id}, this.value)">
                                ${suppleantOptions}
                            </select>
                        </div>
                    </li>
                `;
            });
            html += `</ul></div></div></div>`;
        });

        html += `</div>`;
        return html;
    }

    function togglePresence(titulaireId, isPresent) {
        const divRemp = document.getElementById(`remplacement_${titulaireId}`);
        const label = document.querySelector(`label[for="pres_${titulaireId}"]`);
        
        divRemp.style.display = isPresent ? 'none' : 'block';
        label.textContent = isPresent ? 'Présent' : 'Absent';
        label.className = `form-check-label ms-2 small fw-bold ${isPresent ? 'text-success' : 'text-danger'}`;

        let p = presences[titulaireId] || { est_present: 0, remplace_par_id: null };
        p.est_present = isPresent ? 1 : 0;
        if(isPresent) p.remplace_par_id = null; 
        presences[titulaireId] = p;

        sendPresenceUpdate(titulaireId, p.est_present, p.remplace_par_id);
    }

    function setRemplacement(titulaireId, suppleantId) {
        let p = presences[titulaireId] || { est_present: 0, remplace_par_id: null };
        p.remplace_par_id = suppleantId ? parseInt(suppleantId) : null;
        presences[titulaireId] = p;
        sendPresenceUpdate(titulaireId, p.est_present, p.remplace_par_id);
    }

    function sendPresenceUpdate(membreId, estPresent, remplaceParId) {
        fetch(`${API_BASE}/togglePresence`, {
            method: 'POST',
            body: JSON.stringify({ seance_id: SEANCE_ID, membre_id: membreId, est_present: estPresent, remplace_par: remplaceParId })
        }).then(() => updateQuorumUI());
    }

    function updateQuorumUI() {
        ['administration', 'personnel'].forEach(college => {
            let titulaires = membres.filter(m => m.college === college && m.type_mandat === 'titulaire');
            let total = titulaires.length;
            
            // Le quorum requis pour ce collège (généralement la moitié, arrondi au supérieur)
            let quorumRequis = Math.ceil(total / 2); 
            if (quorumRequis === 0) quorumRequis = 1; // Sécurité

            let votants = 0;
            titulaires.forEach(tit => {
                let p = presences[tit.id];
                if (p && (p.est_present == 1 || p.remplace_par_id)) { votants++; }
            });

            // Mise à jour de l'UI
            let prefix = college === 'administration' ? 'q-admin' : 'q-pers';
            const bar = document.getElementById(`${prefix}-bar`);
            const text = document.getElementById(`${prefix}-txt`);
            
            if(bar && text) {
                let pct = total > 0 ? Math.min((votants / quorumRequis) * 100, 100) : 0;
                bar.style.width = pct + '%';
                text.textContent = `${votants} / ${quorumRequis}`;
                bar.className = 'quorum-fill ' + (votants >= quorumRequis ? 'bg-success' : 'bg-danger');
            }
        });
    }


    // ==========================================
    // VUE 2 : POINTS ODJ (DÉBATS & VOTES)
    // ==========================================

    function renderPoint(pt, idx) {
        let isVote = ['vote', 'deliberation', 'avis'].includes((pt.type_point || '').toLowerCase());
        
        let html = `
            <div class="mb-4 border-bottom pb-3">
                <span class="text-primary fw-bold small text-uppercase letter-spacing-1">${INSTANCE_NOM}</span>
                <h2 class="fw-bold mb-1 mt-1">${pt.titre}</h2>
                <h6 class="text-muted mb-0">Point ${idx + 1} à l'ordre du jour</h6>
            </div>

            <!-- Débats / Prise de note -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Prise de notes partagée</h6>
                    <span id="save-indicator" class="saving-indicator"><i class="bi bi-cloud-check me-1"></i>Enregistré</span>
                </div>
                <div class="card-body p-0">
                    <textarea id="debats-textarea" class="form-control border-0 p-4 debats" rows="8" 
                              placeholder="Saisissez ici les débats, interventions et décisions prises"
                              onfocus="isTyping=true" onblur="isTyping=false"
                              oninput="debouncedSaveDebats(${pt.id}, this.value)">${pt.debats || ''}</textarea>
                </div>
            </div>
        `;

        if (isVote) {
            html += `
            <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-danger me-2"></i>Scrutin / Votes</h5>
            <div class="row g-4 mb-4">
            `;
            
            ['administration', 'personnel'].forEach(college => {
                let maxVotants = getVotantsActifs(college);
                let currentVotes = (votes[pt.id] && votes[pt.id][college]) ? votes[pt.id][college] : {nb_pour:0, nb_contre:0, nb_abstention:0, nb_refus_vote:0};
                
                html += `
                <div class="col-md-6">
                    <div class="vote-card p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-uppercase mb-0">Collège ${college}</h6>
                            <span class="badge bg-secondary">${maxVotants} votants</span>
                        </div>
                        
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-sm btn-outline-success fw-bold w-100" onclick="unanimite(${pt.id}, '${college}', ${maxVotants})">
                                <i class="bi bi-stars me-1"></i>Unanimité POUR
                            </button>
                        </div>

                        <div class="row g-2 text-center align-items-center mb-2">
                            <div class="col-4 small fw-bold text-success">Pour</div>
                            <div class="col-8"><input type="number" id="v_${pt.id}_${college}_pour" class="form-control text-center" value="${currentVotes.nb_pour}" min="0" max="${maxVotants}" onchange="saveVote(${pt.id}, '${college}')" onfocus="isTyping=true" onblur="isTyping=false"></div>
                        </div>
                        <div class="row g-2 text-center align-items-center mb-2">
                            <div class="col-4 small fw-bold text-danger">Contre</div>
                            <div class="col-8"><input type="number" id="v_${pt.id}_${college}_contre" class="form-control text-center" value="${currentVotes.nb_contre}" min="0" max="${maxVotants}" onchange="saveVote(${pt.id}, '${college}')" onfocus="isTyping=true" onblur="isTyping=false"></div>
                        </div>
                        <div class="row g-2 text-center align-items-center mb-2">
                            <div class="col-4 small fw-bold text-warning">Abstention</div>
                            <div class="col-8"><input type="number" id="v_${pt.id}_${college}_abst" class="form-control text-center" value="${currentVotes.nb_abstention}" min="0" max="${maxVotants}" onchange="saveVote(${pt.id}, '${college}')" onfocus="isTyping=true" onblur="isTyping=false"></div>
                        </div>
                        <div class="row g-2 text-center align-items-center">
                            <div class="col-4 small fw-bold text-secondary">Refus</div>
                            <div class="col-8"><input type="number" id="v_${pt.id}_${college}_refus" class="form-control text-center" value="${currentVotes.nb_refus_vote}" min="0" max="${maxVotants}" onchange="saveVote(${pt.id}, '${college}')" onfocus="isTyping=true" onblur="isTyping=false"></div>
                        </div>
                    </div>
                </div>`;
            });
            html += `</div>`;
        }

        let prevBtn = idx === 0 
            ? `<button class="btn btn-light" onclick="setView(-1)"><i class="bi bi-arrow-left me-2"></i>Appel</button>` 
            : `<button class="btn btn-light" onclick="setView(${idx - 1})"><i class="bi bi-arrow-left me-2"></i>Point précédent</button>`;
        
        let nextBtn = idx === points.length - 1 
            ? `` 
            : `<button class="btn btn-primary" onclick="setView(${idx + 1})">Point suivant <i class="bi bi-arrow-right ms-2"></i></button>`;

        html += `
            <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                ${prevBtn}
                ${nextBtn}
            </div>
            <div style="height: 100px;"></div>
        `;
        return html;
    }

    function getVotantsActifs(college) {
        let count = 0;
        membres.filter(m => m.college === college && m.type_mandat === 'titulaire').forEach(tit => {
            let p = presences[tit.id];
            if (p && (p.est_present == 1 || p.remplace_par_id)) count++;
        });
        return count;
    }

    function debouncedSaveDebats(pointId, texte) {
        const ind = document.getElementById('save-indicator');
        ind.classList.remove('show');
        ind.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...';
        ind.classList.add('show');

        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            fetch(`${API_BASE}/autoSaveDebats/${pointId}`, {
                method: 'POST',
                body: JSON.stringify({ debats: texte })
            }).then(() => {
                let pt = points.find(p => p.id == pointId);
                if(pt) pt.debats = texte;
                ind.innerHTML = '<i class="bi bi-cloud-check text-success me-1"></i>Enregistré';
                setTimeout(() => ind.classList.remove('show'), 2000);
            });
        }, 1000); 
    }

    function unanimite(pointId, college, maxVotants) {
        document.getElementById(`v_${pointId}_${college}_pour`).value = maxVotants;
        document.getElementById(`v_${pointId}_${college}_contre`).value = 0;
        document.getElementById(`v_${pointId}_${college}_abst`).value = 0;
        document.getElementById(`v_${pointId}_${college}_refus`).value = 0;
        saveVote(pointId, college);
    }

    function saveVote(pointId, college) {
        let pour = document.getElementById(`v_${pointId}_${college}_pour`).value;
        let contre = document.getElementById(`v_${pointId}_${college}_contre`).value;
        let abst = document.getElementById(`v_${pointId}_${college}_abst`).value;
        let refus = document.getElementById(`v_${pointId}_${college}_refus`).value;

        if(!votes[pointId]) votes[pointId] = {};
        votes[pointId][college] = { nb_pour: pour, nb_contre: contre, nb_abstention: abst, nb_refus_vote: refus };

        fetch(`${API_BASE}/saveVote`, {
            method: 'POST',
            body: JSON.stringify({ point_id: pointId, college: college, pour: pour, contre: contre, abstention: abst, refus: refus })
        });
    }

</script>
</body>
</html>
