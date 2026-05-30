/* INGECOSMOS — JS propio del taller */

// Flash messages auto-dismiss
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000);
    });

    // Confirmaciones destructivas
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Submit de formularios con confirm
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});

// Formatear número como moneda COP
function formatCOP(num) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP',
        minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(num);
}
