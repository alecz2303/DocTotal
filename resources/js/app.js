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

const createSalesModal = (details, title, description) => {
    const summary = details.querySelector('summary');
    const form = details.querySelector('form');

    if (!summary || !form || details.dataset.dt55Modal === 'true') {
        return;
    }

    details.dataset.dt55Modal = 'true';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = summary.className.replace('cursor-pointer list-none ', '');
    trigger.textContent = summary.textContent.trim();

    const dialog = document.createElement('dialog');
    dialog.className = 'w-[min(94vw,42rem)] max-h-[90vh] overflow-hidden rounded-3xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/55';

    const header = document.createElement('div');
    header.className = 'flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5';

    const heading = document.createElement('div');
    heading.innerHTML = `<h2 class="text-lg font-bold text-slate-950">${title}</h2><p class="mt-1 text-sm leading-6 text-slate-500">${description}</p>`;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-lg text-slate-500 transition hover:bg-slate-50 hover:text-slate-900';
    close.setAttribute('aria-label', 'Cerrar');
    close.textContent = '×';

    const body = document.createElement('div');
    body.className = 'max-h-[calc(90vh-5.5rem)] overflow-y-auto p-6';

    form.classList.add('w-full');
    body.appendChild(form);
    header.appendChild(heading);
    header.appendChild(close);
    dialog.appendChild(header);
    dialog.appendChild(body);

    details.replaceWith(trigger);
    document.body.appendChild(dialog);

    const open = () => {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    };

    const dismiss = () => {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
    };

    trigger.addEventListener('click', open);
    close.addEventListener('click', dismiss);

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dismiss();
        }
    });
};

const labelPromoCodeEditFields = () => {
    const labels = {
        sales_partner_id: 'Vendedor',
        code: 'Código promocional',
        doctor_discount_percent: 'Descuento al médico (%)',
        commission_percent: 'Comisión del vendedor (%)',
    };

    document.querySelectorAll('form[action*="/internal/sales/promo-codes/"]').forEach((form) => {
        if (form.closest('dialog')) {
            return;
        }

        Object.entries(labels).forEach(([name, text]) => {
            const field = form.querySelector(`[name="${name}"]`);

            if (!field || field.closest('[data-dt55-field]')) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.dataset.dt55Field = name;
            wrapper.className = 'min-w-0';

            const label = document.createElement('label');
            label.className = 'mb-1.5 block text-xs font-semibold leading-5 text-slate-600';
            label.textContent = text;

            field.parentNode.insertBefore(wrapper, field);
            wrapper.appendChild(label);
            wrapper.appendChild(field);
            field.classList.add('w-full');
        });

        if (!form.querySelector('[data-dt55-help]')) {
            const help = document.createElement('p');
            help.dataset.dt55Help = 'true';
            help.className = 'sm:col-span-2 text-xs leading-5 text-slate-500';
            help.textContent = 'El descuento se aplica al primer pago del médico. La comisión se calcula sobre el monto efectivamente pagado.';
            form.appendChild(help);
        }
    });
};

const enhanceInternalSales = () => {
    if (window.location.pathname !== '/internal/sales') {
        return;
    }

    const details = Array.from(document.querySelectorAll('details'));

    const partnerDetails = details.find((item) =>
        item.querySelector('summary')?.textContent.includes('Nuevo vendedor')
    );

    const codeDetails = details.find((item) =>
        item.querySelector('summary')?.textContent.includes('Nuevo código')
    );

    if (partnerDetails) {
        createSalesModal(
            partnerDetails,
            'Nuevo vendedor',
            'Registra los datos del vendedor sin salir de la pantalla de ventas.'
        );
    }

    if (codeDetails) {
        createSalesModal(
            codeDetails,
            'Nuevo código promocional',
            'Asigna un vendedor, el descuento al médico y la comisión correspondiente.'
        );
    }

    labelPromoCodeEditFields();
};

const refreshReferralBillingCopy = () => {
    if (window.location.pathname !== '/settings/billing') {
        return;
    }

    const headings = Array.from(document.querySelectorAll('h2'));
    const referralHeading = headings.find((heading) =>
        heading.textContent.trim() === 'Invita y gana'
    );
    const referralSection = referralHeading?.closest('section');

    if (!referralSection) {
        return;
    }

    const walker = document.createTreeWalker(
        referralSection,
        NodeFilter.SHOW_TEXT
    );

    while (walker.nextNode()) {
        const node = walker.currentNode;
        let value = node.nodeValue;

        value = value.replaceAll('$50 MXN por referido', '$100 MXN por referido');
        value = value.replaceAll('$50 MXN de crédito para tus próximos pagos.', '$100 MXN de crédito para tus próximos pagos.');
        value = value.replaceAll('Tu referido recibe $50 MXN de descuento', 'Tu referido recibe $60 MXN de descuento');
        value = value.replaceAll('tú recibes $50 MXN', 'tú recibes $100 MXN');

        node.nodeValue = value;
    }
};

const initializeUiEnhancements = () => {
    loadPublicAnalytics();
    enhanceInternalSales();
    refreshReferralBillingCopy();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeUiEnhancements);
} else {
    initializeUiEnhancements();
}
