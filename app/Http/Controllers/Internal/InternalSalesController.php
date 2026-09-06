<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\SalesCommission;
use App\Models\SalesPartner;
use App\Models\TenantPromoAttribution;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InternalSalesController extends Controller
{
    public function index(): View
    {
        return view('internal.sales.index', [
            'partners' => SalesPartner::query()
                ->withCount(['promoCodes', 'commissions'])
                ->latest('id')
                ->get(),
            'promoCodes' => PromoCode::query()
                ->with('salesPartner')
                ->withCount('attributions')
                ->latest('id')
                ->get(),
            'attributions' => TenantPromoAttribution::query()
                ->with(['tenant', 'promoCode', 'salesPartner'])
                ->latest('attributed_at')
                ->limit(100)
                ->get(),
            'commissions' => SalesCommission::query()
                ->with(['salesPartner', 'promoCode', 'tenant', 'payment'])
                ->latest('accrued_at')
                ->limit(100)
                ->get(),
            'metrics' => [
                'registrations' => TenantPromoAttribution::query()->count(),
                'paid_conversions' => SalesCommission::query()
                    ->distinct('tenant_id')
                    ->count('tenant_id'),
                'attributed_revenue' => SalesCommission::query()
                    ->where('status', '!=', SalesCommission::STATUS_REVERTED)
                    ->sum('base_amount'),
                'commission_accrued' => SalesCommission::query()
                    ->where('status', SalesCommission::STATUS_ACCRUED)
                    ->sum('commission_amount'),
                'commission_paid' => SalesCommission::query()
                    ->where('status', SalesCommission::STATUS_PAID)
                    ->sum('commission_amount'),
            ],
        ]);
    }

    public function storePartner(
        Request $request,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $this->validatePartner($request);

        $partner = SalesPartner::query()->create([
            ...$validated,
            'active' => true,
        ]);

        $auditLogger->safeLogGlobal(
            action: 'internal.sales_partner.created',
            auditable: $partner,
            description: 'Vendedor o promotor comercial creado.',
        );

        return back()->with('status', 'Vendedor creado correctamente.');
    }

    public function updatePartner(
        Request $request,
        SalesPartner $partner,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $partner->update($this->validatePartner($request));

        $auditLogger->safeLogGlobal(
            action: 'internal.sales_partner.updated',
            auditable: $partner,
            description: 'Datos de vendedor o promotor actualizados.',
        );

        return back()->with('status', 'Vendedor actualizado correctamente.');
    }

    public function togglePartner(
        SalesPartner $partner,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $partner->update(['active' => ! $partner->active]);

        $auditLogger->safeLogGlobal(
            action: 'internal.sales_partner.status_updated',
            auditable: $partner,
            description: 'Estado de vendedor o promotor actualizado.',
            metadata: ['active' => $partner->active],
        );

        return back()->with('status', 'Estado del vendedor actualizado.');
    }

    public function storePromoCode(
        Request $request,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $this->validatePromoCode($request);

        $promoCode = PromoCode::query()->create([
            ...$validated,
            'active' => true,
            'commission_scope' => PromoCode::SCOPE_FIRST_SUCCESSFUL_PAYMENT,
        ]);

        $auditLogger->safeLogGlobal(
            action: 'internal.promo_code.created',
            auditable: $promoCode,
            description: 'Código promocional comercial creado.',
            metadata: [
                'doctor_discount_percent' => $promoCode->doctor_discount_percent,
                'commission_percent' => $promoCode->commission_percent,
            ],
        );

        return back()->with('status', 'Código promocional creado correctamente.');
    }

    public function updatePromoCode(
        Request $request,
        PromoCode $promoCode,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $promoCode->update(
            $this->validatePromoCode($request, $promoCode)
        );

        $auditLogger->safeLogGlobal(
            action: 'internal.promo_code.updated',
            auditable: $promoCode,
            description: 'Código promocional comercial actualizado.',
            metadata: [
                'doctor_discount_percent' => $promoCode->doctor_discount_percent,
                'commission_percent' => $promoCode->commission_percent,
            ],
        );

        return back()->with('status', 'Código promocional actualizado.');
    }

    public function togglePromoCode(
        PromoCode $promoCode,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $promoCode->update(['active' => ! $promoCode->active]);

        $auditLogger->safeLogGlobal(
            action: 'internal.promo_code.status_updated',
            auditable: $promoCode,
            description: 'Estado de código promocional actualizado.',
            metadata: ['active' => $promoCode->active],
        );

        return back()->with('status', 'Estado del código promocional actualizado.');
    }

    public function markCommissionPaid(
        SalesCommission $commission,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        if ($commission->status !== SalesCommission::STATUS_ACCRUED) {
            return back()->withErrors([
                'commission' => 'Sólo una comisión devengada puede marcarse como pagada.',
            ]);
        }

        $commission->update([
            'status' => SalesCommission::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $auditLogger->safeLogGlobal(
            action: 'internal.sales_commission.paid',
            auditable: $commission,
            description: 'Comisión comercial marcada como pagada.',
            metadata: [
                'commission_amount' => $commission->commission_amount,
                'currency' => $commission->currency,
            ],
        );

        return back()->with('status', 'Comisión marcada como pagada.');
    }

    private function validatePartner(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validatePromoCode(
        Request $request,
        ?PromoCode $promoCode = null,
    ): array {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        return $request->validate([
            'sales_partner_id' => [
                'required',
                Rule::exists('sales_partners', 'id'),
            ],
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('promo_codes', 'code')->ignore($promoCode?->id),
            ],
            'doctor_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
