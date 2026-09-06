document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[action$="/register"]');

    if (! form || form.querySelector('[name="promo_code"]')) {
        return;
    }

    const referralInput = form.querySelector('[name="referral_code"]');
    const referralContainer = referralInput?.closest('div');

    if (! referralContainer) {
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const promoFromUrl = (params.get('promo') ?? '').trim().toUpperCase();

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <label for="promo_code" class="mb-1.5 block text-sm font-medium text-slate-700">
            Código promocional
            <span class="font-normal text-slate-400">(opcional)</span>
        </label>
        <input
            id="promo_code"
            name="promo_code"
            type="text"
            value="${promoFromUrl.replace(/[&<>'"]/g, '')}"
            autocomplete="off"
            placeholder="Ej. VENDE20"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm uppercase outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
        <p class="mt-1.5 text-xs leading-5 text-slate-500">
            Si recibiste un código de promoción de DocTotal, escríbelo aquí. No puede combinarse con un código de referido.
        </p>
    `;

    referralContainer.insertAdjacentElement('afterend', wrapper);

    const promoInput = wrapper.querySelector('[name="promo_code"]');

    const syncExclusiveCodes = () => {
        if (! referralInput || ! promoInput) {
            return;
        }

        if (promoInput.value.trim() !== '') {
            referralInput.disabled = true;
        } else {
            referralInput.disabled = false;
        }

        if (referralInput.value.trim() !== '') {
            promoInput.disabled = true;
        } else {
            promoInput.disabled = false;
        }
    };

    referralInput?.addEventListener('input', syncExclusiveCodes);
    promoInput?.addEventListener('input', syncExclusiveCodes);
    syncExclusiveCodes();
});
