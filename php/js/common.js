document.addEventListener('DOMContentLoaded', () => {
    initFadeIn();
    initFieldEffects();
    initLogoutConfirm();
    initDeleteConfirm();
});

//ANIMATIONS GÉNÉRALES
function initFadeIn() {
    const elements = document.querySelectorAll(
        'h1, h2, form, table, .form-card, .login-container, .choice-card, .info-card, .hero-panel, .management-panel, .summary-card, .form-shell, .status-panel'
    );
    
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';

        setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 100 + index * 80);
    });
}

//INPUTS 
function initFieldEffects() {
    const fields = document.querySelectorAll('input, select, textarea');

    fields.forEach(field => {
        field.addEventListener('focus', () => {
            field.style.transform = 'scale(1.01)';
        });

        field.addEventListener('blur', () => {
            field.style.transform = 'scale(1)';
        });

        const syncState = () => {
            const value = typeof field.value === 'string' ? field.value.trim() : '';
            field.style.boxShadow = value !== ''
                ? '0 0 0 4px rgba(18,183,106,0.10)'
                : '';
        };

        field.addEventListener('input', syncState);
        field.addEventListener('change', syncState);
        syncState();
    });
}

//LOGOUT CONFIRM
function initLogoutConfirm() {
    document.querySelectorAll('.btn-logout').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Tu veux vraiment te déconnecter ?')) {
                e.preventDefault();
            }
        });
    });
}

//CONFIRMATION SUPPRESSION 
function initDeleteConfirm() {
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Confirmer la suppression ?')) {
                e.preventDefault();
            }
        });
    });
}
