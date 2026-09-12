@php
    $monthlyAmount = (int) config('billing.plans.monthly.amount', 0);
    $yearlyAmount = (int) config('billing.plans.yearly.amount', 0);
    $monthly = $monthlyAmount / 100;
    $yearly = $yearlyAmount / 100;
    $yearlyEquivalent = $yearly > 0 ? $yearly / 12 : 0;
    $yearlySavings = max(0, ($monthly * 12) - $yearly);
    $whatsapp = preg_replace('/\D+/', '', (string) config('legal.whatsapp_number'));
    $contactEmail = (string) config('legal.contact_email');
    $supportEmail = (string) config('legal.support_email');
@endphp

<section id="planes" class="section soft">
    <div class="wrap">
        <div class="section-head">
            <div class="tag">Planes</div>
            <h2>Todo DocTotal. Dos formas simples de pagarlo.</h2>
            <p>No hay funciones escondidas entre niveles. Elige la periodicidad que mejor se adapte a tu consulta.</p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:22px;max-width:920px;margin:38px auto 0;align-items:stretch">
            <article class="showcase" style="display:flex;flex-direction:column">
                <div class="tag" style="width:max-content">Mensual</div>
                <div style="margin-top:18px;font-size:2.65rem;font-weight:850;letter-spacing:-.05em;color:var(--ink)">${{ number_format($monthly, 0) }} <span style="font-size:.9rem;font-weight:700;letter-spacing:0;color:var(--muted)">MXN / mes</span></div>
                <p style="color:var(--muted);line-height:1.65;margin:10px 0 0">Flexibilidad mes a mes con la experiencia completa de DocTotal.</p>
                <div class="list" style="margin-top:22px">
                    <div class="list-item"><div class="list-icon">✓</div><div><strong>Todo incluido</strong><span>Agenda, pacientes, expediente, consultas, recetas y documentos.</span></div></div>
                    <div class="list-item"><div class="list-icon">✓</div><div><strong>Sin niveles de funciones</strong><span>La periodicidad de pago no limita las herramientas de tu cuenta.</span></div></div>
                </div>
                <div style="margin-top:auto;padding-top:24px"><a href="{{ route('register') }}" class="btn btn-ghost" style="border-color:var(--line);color:var(--ink);background:#fff">Comenzar mensual</a></div>
            </article>

            <article class="showcase" style="position:relative;display:flex;flex-direction:column;border:2px solid var(--blue);box-shadow:0 28px 70px rgba(37,99,235,.16)">
                @if($yearlySavings > 0)
                <div style="position:absolute;right:22px;top:-15px;border-radius:999px;background:var(--green);color:#fff;padding:8px 13px;font-size:.72rem;font-weight:850;box-shadow:0 10px 25px rgba(16,185,129,.22)">Ahorra ${{ number_format($yearlySavings, 0) }} al año</div>
                @endif
                <div class="tag" style="width:max-content">Anual · mejor valor</div>
                <div style="margin-top:18px;font-size:2.65rem;font-weight:850;letter-spacing:-.05em;color:var(--ink)">${{ number_format($yearly, 0) }} <span style="font-size:.9rem;font-weight:700;letter-spacing:0;color:var(--muted)">MXN / año</span></div>
                <p style="margin:8px 0 0;color:var(--green);font-weight:800">Equivale a ${{ number_format($yearlyEquivalent, 0) }} MXN al mes.</p>
                <div class="list" style="margin-top:22px">
                    <div class="list-item"><div class="list-icon">✓</div><div><strong>La misma experiencia completa</strong><span>Incluye las mismas funciones disponibles en el plan mensual.</span></div></div>
                    <div class="list-item"><div class="list-icon">✓</div><div><strong>Mejor costo anual</strong><span>Una sola periodicidad con ahorro frente a doce mensualidades.</span></div></div>
                </div>
                <div style="margin-top:auto;padding-top:24px"><a href="{{ route('register') }}" class="btn btn-primary">Elegir anual →</a></div>
            </article>
        </div>
    </div>
</section>

<section id="codigos" class="section dark">
    <div class="wrap split">
        <div class="split-copy">
            <div class="tag">Códigos</div>
            <h2>¿Te compartieron un código?</h2>
            <p>Úsalo al crear tu cuenta. DocTotal distingue el origen del código para mantener claro qué representa cada uno.</p>
            <p style="margin-top:20px;color:rgba(226,232,240,.58);font-size:.82rem;line-height:1.6">Un código de referido y uno promocional no se combinan en el mismo registro.</p>
        </div>

        <div class="list" style="margin-top:0">
            <div class="list-item" style="padding:20px"><div class="list-icon" style="flex-basis:42px;height:42px">↗</div><div><strong style="font-size:1rem">Código de médico / referido</strong><span style="font-size:.82rem">Identifica que otro médico o consultorio te invitó a conocer DocTotal y vincula el registro con esa recomendación.</span></div></div>
            <div class="list-item" style="padding:20px"><div class="list-icon" style="flex-basis:42px;height:42px">%</div><div><strong style="font-size:1rem">Código promocional / asesor</strong><span style="font-size:.82rem">Puede provenir de un asesor, vendedor o campaña y aplica únicamente el beneficio que esté vigente para ese código.</span></div></div>
        </div>
    </div>
</section>

<section id="seguridad" class="section">
    <div class="wrap split">
        <div class="split-copy">
            <div class="tag">Seguridad y confianza</div>
            <h2>Tu operación clínica merece controles serios.</h2>
            <p>DocTotal incorpora controles para proteger el acceso y apoyar la continuidad operativa. La seguridad es un proceso continuo, no una promesa de riesgo cero.</p>
            <div style="margin-top:28px;display:inline-flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid var(--line);border-radius:18px;background:var(--soft)"><div class="list-icon" style="flex-basis:44px;height:44px;font-size:1.15rem">✓</div><div><strong style="display:block">Protección por capas</strong><span style="display:block;margin-top:3px;color:var(--muted);font-size:.78rem">Acceso, archivos, respaldos y monitoreo.</span></div></div>
        </div>

        <div class="showcase">
            <div class="list" style="margin-top:0">
                <div class="list-item"><div class="list-icon">✓</div><div><strong>Acceso protegido</strong><span>Autenticación y opción de autenticación en dos pasos.</span></div></div>
                <div class="list-item"><div class="list-icon">✓</div><div><strong>Documentos privados</strong><span>Archivos clínicos fuera del acceso público directo.</span></div></div>
                <div class="list-item"><div class="list-icon">✓</div><div><strong>Respaldos y recuperación</strong><span>Procesos operativos para respaldar y recuperar información.</span></div></div>
                <div class="list-item"><div class="list-icon">✓</div><div><strong>Monitoreo operativo</strong><span>Registros y alertas que apoyan la respuesta ante eventos.</span></div></div>
            </div>
        </div>
    </div>
</section>

<section id="contacto" class="section soft">
    <div class="wrap">
        <div class="showcase" style="max-width:980px;margin:auto;padding:clamp(28px,5vw,54px);background:linear-gradient(135deg,#fff 0%,#f8fbff 100%)">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:38px;align-items:center">
                <div>
                    <div class="tag">Contacto</div>
                    <h2 style="margin:14px 0 0;font-size:clamp(2rem,4vw,3rem);line-height:1.03;letter-spacing:-.045em">¿Quieres hablar con nosotros?</h2>
                    <p style="margin:16px 0 0;color:var(--muted);line-height:1.7">Ya sea que estés evaluando DocTotal o necesites ayuda con tu cuenta, aquí tienes canales directos para comunicarte con nosotros.</p>
                </div>

                <div class="list" style="margin-top:0">
                    @if($whatsapp)
                    <a class="list-item" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" style="align-items:center"><div class="list-icon" style="background:#ecfdf5;color:var(--green)">↗</div><div><strong>{{ config('legal.whatsapp_label') }}</strong><span>961 112 0913</span></div></a>
                    @endif
                    @if($contactEmail)
                    <a class="list-item" href="mailto:{{ $contactEmail }}" style="align-items:center"><div class="list-icon">@</div><div><strong>Correo de contacto</strong><span>{{ $contactEmail }}</span></div></a>
                    @endif
                    @if($supportEmail && $supportEmail !== $contactEmail)
                    <a class="list-item" href="mailto:{{ $supportEmail }}" style="align-items:center"><div class="list-icon">?</div><div><strong>Soporte</strong><span>{{ $supportEmail }}</span></div></a>
                    @endif
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:18px;margin-top:32px;padding-top:24px;border-top:1px solid var(--line);font-size:.82rem;font-weight:750;color:var(--blue)">
                <a href="{{ route('privacy') }}">Aviso de Privacidad →</a>
                <a href="{{ route('terms') }}">Términos y Condiciones →</a>
            </div>
        </div>
    </div>
</section>

@if($whatsapp)
<a href="https://wa.me/{{ $whatsapp }}?text={{ urlencode('Hola, quiero más información sobre DocTotal.') }}" target="_blank" rel="noopener" aria-label="Contáctanos por WhatsApp" style="position:fixed;right:22px;bottom:22px;z-index:70;display:inline-flex;align-items:center;gap:10px;padding:12px 17px 12px 13px;border-radius:999px;background:#25D366;color:#fff;font-size:.82rem;font-weight:850;box-shadow:0 18px 45px rgba(15,23,42,.28);transition:transform .2s ease,box-shadow .2s ease">
    <span style="display:flex;width:34px;height:34px;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,.16)" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 11.5a8 8 0 0 1-11.8 7l-4.2 1.1 1.1-4A8 8 0 1 1 20 11.5Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" /><path d="M8.4 8.2c.2 2.8 2.6 5.2 5.4 5.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" /></svg></span>
    <span>Contáctanos</span>
</a>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const desktopNav = document.querySelector('.nav-links');
    if (desktopNav && !desktopNav.querySelector('a[href="#planes"]')) {
        const benefits = desktopNav.querySelector('a[href="#beneficios"]');
        if (benefits) {
            benefits.textContent = 'Planes';
            benefits.setAttribute('href', '#planes');
        }
        const contact = document.createElement('a');
        contact.href = '#contacto';
        contact.textContent = 'Contacto';
        desktopNav.appendChild(contact);
    }

    const mobileNav = document.getElementById('mobileMenu');
    if (mobileNav && !mobileNav.querySelector('a[href="#planes"]')) {
        const benefits = mobileNav.querySelector('a[href="#beneficios"]');
        if (benefits) {
            benefits.innerHTML = 'Planes <span>→</span>';
            benefits.setAttribute('href', '#planes');
        }
        const contact = document.createElement('a');
        contact.href = '#contacto';
        contact.innerHTML = 'Contacto <span>→</span>';
        mobileNav.insertBefore(contact, mobileNav.querySelector('a[href*="login"], a[href*="dashboard"]'));
    }

    const footer = document.querySelector('.footer-links');
    if (footer && !footer.querySelector('a[href="#planes"]')) {
        const links = [
            ['#planes', 'Planes'],
            ['#codigos', 'Códigos'],
            ['#contacto', 'Contacto'],
            ['{{ route('privacy') }}', 'Aviso de Privacidad'],
            ['{{ route('terms') }}', 'Términos y Condiciones']
        ];
        links.forEach(([href, label]) => {
            const link = document.createElement('a');
            link.href = href;
            link.textContent = label;
            footer.appendChild(link);
        });
    }
});
</script>
