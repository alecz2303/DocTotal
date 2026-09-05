<?php

namespace App\Services\Communications;

use App\Models\Appointment;
use App\Models\Communication;

class PatientCommunicationEligibilityService
{
    public function recipientFor(
        Appointment $appointment,
        string $channel,
    ): ?string {
        $appointment->loadMissing('patient');

        $patient = $appointment->patient;

        if (! $patient) {
            return null;
        }

        return match ($channel) {
            Communication::CHANNEL_EMAIL =>
                $patient->allow_email_communications
                    ? $patient->email
                    : null,

            Communication::CHANNEL_WHATSAPP =>
                $patient->allow_whatsapp_communications
                    ? $patient->whatsapp
                    : null,

            Communication::CHANNEL_SMS =>
                $patient->allow_sms_communications
                    ? $patient->phone
                    : null,

            default => null,
        };
    }
}
