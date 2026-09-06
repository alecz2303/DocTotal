<?php

namespace App\Services\Commercial;

use App\Models\PromoCode;

class PromoCodeResolver
{
    public function findValid(?string $code): ?PromoCode
    {
        $normalized = strtoupper(trim((string) $code));

        if ($normalized === '') {
            return null;
        }

        $promoCode = PromoCode::query()
            ->with('salesPartner')
            ->where('code', $normalized)
            ->first();

        if (! $promoCode || ! $promoCode->isValidAt()) {
            return null;
        }

        return $promoCode;
    }
}
