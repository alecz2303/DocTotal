<?php

namespace App\Services\Communications;

use App\Models\Appointment;
use App\Models\Communication;
use Illuminate\Support\Facades\DB;

class AppointmentReminderService
{
    public function __construct(
        private AppointmentPublicLinkService $publicLinkService,
    ) {
    }

    public function create(
        Appointment $appointment,
        string $channel = Communication::CHANNEL_WHATSAPP,
    ): ?Communication {
        return DB::transaction(function () use ($appointment, $channel) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $appointment->loadMissing('patient');

            if (! $this->isEligible($appointment)) {
                return null;
            }

            $recipient = $this->resolveRecipient(
                $appointment,
                $channel
            );

            if (! $recipient) {
                return null;
            }

            $idempotencyKey = $this->idempotencyKey(
                $appointment,
                $channel
            );

            $existing = Communication::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }

            $scheduledFor = $appointment->starts_at
                ->copy()
                ->subDay();

            if ($scheduledFor->lessThanOrEqualTo(now())) {
                $scheduledFor = now();
            }

            $publicLink = $this->publicLinkService->issue(
                $appointment
            );

            return Communication::create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'type' => Communication::TYPE_APPOINTMENT_REMINDER,
                'channel' => $channel,
                'recipient' => $recipient,
                'subject' => $channel === Communication::CHANNEL_EMAIL
                    ? $publicLink['subject']
                    : null,
                'body' => $publicLink['message'],
                'status' => Communication::STATUS_PENDING,
                'idempotency_key' => $idempotencyKey,
                'scheduled_for' => $scheduledFor,
                'metadata' => [
                    'appointment_uuid' => $appointment->uuid,
                    'appointment_starts_at' => $appointment->starts_at
                        ->toIso8601String(),
                ],
            ]);
        });
    }

    private function isEligible(
        Appointment $appointment
    ): bool {
        if (! in_array(
            $appointment->status,
            [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
            ],
            true
        )) {
            return false;
        }

        if (! $appointment->starts_at) {
            return false;
        }

        return $appointment->starts_at->isFuture();
    }

    private function resolveRecipient(
        Appointment $appointment,
        string $channel,
    ): ?string {
        return match ($channel) {
            Communication::CHANNEL_EMAIL =>
            $appointment->patient->email,

            Communication::CHANNEL_WHATSAPP =>
            $appointment->patient->whatsapp,

            Communication::CHANNEL_SMS =>
            $appointment->patient->phone,

            default => null,
        };
    }

    private function idempotencyKey(
        Appointment $appointment,
        string $channel,
    ): string {
        return implode(':', [
            'appointment-reminder',
            $appointment->uuid,
            $channel,
            $appointment->starts_at->getTimestamp(),
        ]);
    }

}
