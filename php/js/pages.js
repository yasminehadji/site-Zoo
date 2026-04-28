document.addEventListener('DOMContentLoaded', () => {
    initLogin();
    initAjoutAnimal();
    initPersonnel();
    initGestionPersonnel();
    initAnimalDetail();
    initAccueil();
    initAssignResponsable();
    initEmployesBoutique();
});

//LOGIN
function initLogin() {
    const password = document.getElementById('mot_de_passe');
    if (!password) return;

    const wrapper = password.parentElement;
    if (!wrapper || wrapper.querySelector('.password-toggle-btn')) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = '👁';
    btn.className = 'btn btn-light password-toggle-btn';

    btn.addEventListener('click', () => {
        const visible = password.type === 'text';
        password.type = visible ? 'password' : 'text';
        btn.textContent = visible ? '👁' : '🙈';
    });

    wrapper.appendChild(btn);
}

// AJOUT ANIMAL

function initAjoutAnimal() {
    const form = document.querySelector('form[action="ajout_animal.php"]');
    if (!form) return;

    const fields = {
        id: document.getElementById('id_animal'),
        nom: document.getElementById('nom'),
        poids: document.getElementById('poids'),
        date: document.getElementById('date_naissance'),
        regime: document.getElementById('regime_alimentaire'),
        espece: document.getElementById('id_espece'),
        enclos: document.getElementById('id_enclos')
    };

    const summary = document.createElement('div');
    summary.className = 'info-card';

    form.insertBefore(summary, form.firstChild);

    function update() {
        summary.innerHTML = `
            <strong>Aperçu :</strong><br>
            ID : ${fields.id?.value || '-'}<br>
            Nom : ${fields.nom?.value || '-'}<br>
            Poids : ${fields.poids?.value || '-'} kg<br>
            Date : ${fields.date?.value || '-'}<br>
            Régime : ${fields.regime?.value || '-'}<br>
            Espèce : ${fields.espece?.selectedOptions[0]?.text || '-'}<br>
            Enclos : ${fields.enclos?.selectedOptions[0]?.text || '-'}
        `;
    }

    Object.values(fields).forEach(f => {
        if (!f) return;
        f.addEventListener('input', update);
        f.addEventListener('change', update);
    });

    update();
}

// FORMULAIRE PERSONNEL

function initPersonnel() {
    const form = document.getElementById('personnelForm');
    if (!form) return;

    const password = document.getElementById('mot_de_passe');
    const toggle = document.getElementById('togglePassword');

    if (toggle && password) {
        toggle.addEventListener('click', () => {
            password.type = password.type === 'password' ? 'text' : 'password';
        });
    }

    const fonction = document.getElementById('fonction');
    const preview = document.getElementById('rolePreview');

    if (fonction && preview) {
        fonction.addEventListener('change', () => {
            preview.textContent = fonction.value
                ? 'Fonction : ' + fonction.value
                : 'Aucune fonction';
        });
    }
}

// GESTION PERSONNEL 
function initGestionPersonnel() {
    const form = document.getElementById('filtreForm');
    const nom = document.getElementById('nom');
    const fonction = document.getElementById('fonction');

    if (!form || !nom || !fonction) return;

    fonction.addEventListener('change', () => form.submit());

    let timer;
    nom.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 600);
    });
}

// DETAIL ANIMAL

function initAnimalDetail() {
    const title = document.querySelector('h2');
    
    // ✅ On vérifie que c'est bien la page animal
    if (!title || !title.textContent.toLowerCase().includes("animal")) return;

    const rows = document.querySelectorAll('table tr');
    if (!rows.length) return;
    if (title.dataset.countApplied === '1') return;

    const span = document.createElement('span');
    span.textContent = ` (${rows.length - 1} soin(s))`;
    span.style.opacity = '0.7';
    title.dataset.countApplied = '1';

    title.appendChild(span);
}

// PAGE ACCUEIL (effet carte)

function initAccueil() {
    const cards = document.querySelectorAll('.choice-card, .info-card');

    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const rotateX = (y / rect.height - 0.5) * -6;
            const rotateY = (x / rect.width - 0.5) * 6;

            card.style.transform = `
                perspective(800px)
                rotateX(${rotateX}deg)
                rotateY(${rotateY}deg)
            `;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'none';
        });
    });
}

// AFFECTATION RESPONSABLE BOUTIQUE

function initAssignResponsable() {
    const form = document.getElementById('assignResponsableForm');
    const preview = document.getElementById('assignmentPreview');
    const boutique = document.getElementById('id_boutique');
    const personnel = document.getElementById('id_personnel');

    if (!form || !preview || !boutique || !personnel) return;

    function updatePreview() {
        const boutiqueText = boutique.selectedOptions[0]?.text || 'Non sélectionnée';
        const personnelText = personnel.selectedOptions[0]?.text || 'Non sélectionné';

        preview.innerHTML = `
            <h3>✨ Aperçu de l'affectation</h3>
            <ul class="compact-list">
                <li><strong>Boutique :</strong> ${boutique.value ? boutiqueText : 'Non sélectionnée'}</li>
                <li><strong>Responsable :</strong> ${personnel.value ? personnelText : 'Non sélectionné'}</li>
            </ul>
            <p class="page-note">Les effets visuels et l'aperçu se font côté interface uniquement.</p>
        `;
    }

    boutique.addEventListener('change', updatePreview);
    personnel.addEventListener('change', updatePreview);
    updatePreview();
}

//EMPLOYÉS BOUTIQUE

function initEmployesBoutique() {
    const form = document.getElementById('employesBoutiqueForm');
    const preview = document.getElementById('employeBoutiquePreview');
    const boutique = document.getElementById('id_boutique');
    const personnel = document.getElementById('id_personnel');
    const responsable = document.getElementById('est_responsable');

    if (!form || !preview || !personnel) return;

    function updatePreview() {
        const boutiqueText = boutique?.selectedOptions[0]?.text || form.dataset.boutiqueNom || 'Boutique non définie';
        const personnelValue = personnel.value.trim();
        const role = responsable?.checked ? 'Responsable boutique' : 'Employé boutique';

        preview.innerHTML = `
            <h3>🧾 Aperçu</h3>
            <ul class="compact-list">
                <li><strong>Boutique :</strong> ${boutiqueText}</li>
                <li><strong>ID employé :</strong> ${personnelValue || 'Non renseigné'}</li>
                <li><strong>Rôle attribué :</strong> ${role}</li>
            </ul>
            <p class="page-note">Un seul responsable est autorisé par boutique.</p>
        `;
    }

    boutique?.addEventListener('change', updatePreview);
    personnel.addEventListener('input', updatePreview);
    responsable?.addEventListener('change', updatePreview);
    updatePreview();
}
