import './bootstrap';
import './register-promo';
import Swal from 'sweetalert2';
import { loadStripe } from '@stripe/stripe-js';

window.Swal = Swal;
window.loadStripe = loadStripe;

const loadPublicAnalytics = () => {
    if (window.location.pathname !== '/') {
        return;
    }

    const measurementId = 'G-QM1NBBX7N8';
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () {
        window.dataLayer.push(arguments);
    };

    window.gtag('js', new Date());
    window.gtag('config', measurementId, {
        anonymize_ip: true,
        allow_google_signals: false,
    });

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
    document.head.appendChild(script);

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');

        if (!link) {
            return;
        }

        const href = link.getAttribute('href') || '';
        const label = (link.textContent || '').trim().toLowerCase();
        let eventName = null;
        const eventData = {};

        if (href.includes('/register')) {
            eventName = 'public_register_click';
        } else if (href.includes('/login')) {
            eventName = 'public_login_click';
        } else if (href.startsWith('https://wa.me/')) {
            eventName = 'public_whatsapp_click';
        }

        if (label.includes('mensual')) {
            eventName = 'public_plan_select';
            eventData.plan = 'monthly';
        } else if (label.includes('anual')) {
            eventName = 'public_plan_select';
            eventData.plan = 'annual';
        }

        if (eventName) {
            window.gtag('event', eventName, eventData);
        }
    });
};

const clarifyPromoCodeFields = () => {
    if (window.location.pathname !== '/internal/sales') {
        return;
    }

    const labels = {
        sales_partner_id: 'Vendedor',
        code: 'Código promocional',
        doctor_discount_percent: 'Descuento al médico (%)',
        commission_percent: 'Comisión del vendedor (%)',
    };

    document.querySelectorAll('form[action*="/internal/sales/promo-codes/"]').forEach((form) => {
        Object.entries(labels).forEach(([name, text]) => {
            const field = form.querySelector(`[name="${name}"]`);

            if (!field || field.previousElementSibling?.dataset.dt53Label === name) {
                return;
            }

            const label = document.createElement('label');
            label.className = 'mb-1 block text-xs font-semibold text-slate-600';
            label.dataset.dt53Label = name;
            label.textContent = text;
            field.parentNode.insertBefore(label, field);
        });

        if (!form.querySelector('[data-dt53-help]')) {
            const help = document.createElement('p');
            help.dataset.dt53Help = 'true';
            help.className = 'col-span-full text-xs leading-5 text-slate-500';
            help.textContent = 'El descuento se aplica al primer pago del médico. La comisión se calcula sobre el monto efectivamente pagado.';
            form.appendChild(help);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        loadPublicAnalytics();
        clarifyPromoCodeFields();
    });
} else {
    loadPublicAnalytics();
    clarifyPromoCodeFields();
}
