<?php

namespace App\Services\Communications;

use App\Models\Appointment;
use App\Models\Tenant;
use DomainException;
use Illuminate\Support\Str;

class AppointmentPublicLinkService
{
    /**
     * @return array{url: string, message: string, subject: string}
     */
    public function issue(Appointment $appointment): array
    {
        if (! $this->canIssue($appointment)) {
            throw new DomainException(
                'La cita no permite generar un enlace público de gestión.'
            );
        }

        $token = $appointment->issuePublicAccessToken();

        $url = route(
            'public.appointments.show',
            ['token' => $token]
        );

        return [
            'url' => $url,
            'message' => $this->buildMessage(
                $appointment,
                $url
            ),
            'subject' => $this->buildSubject($appointment),
        ];
    }

    public function canIssue(Appointment $appointment): bool
    {
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

        return $appointment->starts_at?->isFuture() === true;
    }

    public function buildMessage(
        Appointment $appointment,
        string $url,
    ): string {
        $appointment->loadMissing('doctorProfile');

        $doctorName = $this->doctorName($appointment);
        $clinicName = $this->clinicName($appointment);

        $date = Str::ucfirst(
            $appointment->starts_at
                ->copy()
                ->locale('es')
                ->translatedFormat('l j \\d\\e F')
        );

        $time = $appointment->starts_at->format('H:i');

        $context = match (true) {
            $doctorName !== null && $clinicName !== null =>
                "Le recordamos su cita con el Dr. {$doctorName} en {$clinicName}.",

            $doctorName !== null =>
                "Le recordamos su cita con el Dr. {$doctorName}.",

            $clinicName !== null =>
                "Le recordamos su cita en {$clinicName}.",

            default => 'Le recordamos su cita.',
        };

        return implode("\n", [
            'Hola,',
            '',
            $context,
            '',
            "Fecha: {$date}",
            "Hora: {$time} hrs.",
            '',
            'Puede confirmar o gestionar su cita aquí:',
            $url,
        ]);
    }

    public function buildSubject(Appointment $appointment): string
    {
        $clinicName = $this->clinicName($appointment);

        return $clinicName
            ? "Recordatorio de cita - {$clinicName}"
            : 'Recordatorio de cita';
    }

    private function doctorName(Appointment $appointment): ?string
    {
        $doctor = $appointment->doctorProfile;

        if (! $doctor) {
            return null;
        }

        $name = trim(implode(' ', array_filter([
            $doctor->first_name,
            $doctor->last_name,
            $doctor->second_last_name,
        ])));

        return $name !== '' ? $name : null;
    }

    private function clinicName(Appointment $appointment): ?string
    {
        $name = Tenant::query()
            ->whereKey($appointment->tenant_id)
            ->value('name');

        $name = is_string($name) ? trim($name) : '';

        return $name !== '' ? $name : null;
    }
}
