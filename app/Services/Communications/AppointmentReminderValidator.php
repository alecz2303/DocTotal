<?php

namespace App\Services\Communications;

use App\Models\Appointment;
use App\Models\Communication;

class AppointmentReminderValidator
{
    public function isCurrent(
        Communication $communication
    ): bool {
        if (
            $communication->type
            !== Communication::TYPE_APPOINTMENT_REMINDER
        ) {
            return true;
        }

        $communication->loadMissing('appointment');

        $appointment = $communication->appointment;

        if (! $appointment) {
            return false;
        }

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

        $originalStartsAt = data_get(
            $communication->metadata,
            'appointment_starts_at'
        );

        if (! $originalStartsAt) {
            return false;
        }

        return $appointment->starts_at
            ->equalTo($originalStartsAt);
    }
}
