<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')
            ->where(function ($query): void {
                $query
                    ->whereNull('referral_code')
                    ->orWhere(
                        'referral_code',
                        ''
                    );
            })
            ->orderBy('id')
            ->chunkById(
                100,
                function ($tenants): void {
                    foreach ($tenants as $tenant) {
                        DB::table('tenants')
                            ->where(
                                'id',
                                $tenant->id
                            )
                            ->update([
                                'referral_code' =>
                                $this->generateUniqueReferralCode(),
                            ]);
                    }
                }
            );
    }

    public function down(): void
    {
        /*
         * Data migration intentionally irreversible.
         *
         * No eliminamos los referral_code al hacer rollback,
         * porque una vez asignados pueden haber sido
         * compartidos o utilizados por usuarios reales.
         */
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code =
                Str::upper(
                    Str::random(8)
                );
        } while (
            DB::table('tenants')
            ->where(
                'referral_code',
                $code
            )
            ->exists()
        );

        return $code;
    }
};
