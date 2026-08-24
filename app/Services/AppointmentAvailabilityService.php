<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Schedule;
use App\Models\ScheduleException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AppointmentAvailabilityService
{
    /**
     * Estados que ocupan espacio dentro de la agenda.
     */
    private const BLOCKING_STATUSES = [
        'scheduled',
        'confirmed',
        'checked_in',
        'in_progress',
    ];

    public function isAvailable(
        DoctorProfile $doctor,
        CarbonInterface|string $startsAt,
        ?int $durationMinutes = null,
        ?Appointment $ignoreAppointment = null,
    ): bool {
        $startsAt = $startsAt instanceof CarbonInterface
            ? Carbon::instance($startsAt)
            : Carbon::parse($startsAt);

        $dayOfWeek = $startsAt->dayOfWeek;

        $schedules = Schedule::query()
            ->where('doctor_profile_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('active', true)
            ->orderBy('start_time')
            ->get();

        $exceptions = ScheduleException::query()
            ->where('doctor_profile_id', $doctor->id)
            ->whereDate('date', $startsAt->toDateString())
            ->get();

        /*
         * Un bloqueo de día completo tiene prioridad.
         */
        if ($this->hasFullDayBlock($exceptions)) {
            return false;
        }

        /*
         * Primero intentamos encontrar una ventana regular
         * en la que quepa la cita.
         */
        foreach ($schedules as $schedule) {
            $duration = $durationMinutes
                ?? $schedule->appointment_duration;

            $endsAt = $startsAt
                ->copy()
                ->addMinutes($duration);

            if (! $this->fitsInsideSchedule(
                $schedule,
                $startsAt,
                $endsAt
            )) {
                continue;
            }

            if ($this->overlapsBlockedException(
                $exceptions,
                $startsAt,
                $endsAt
            )) {
                return false;
            }

            if ($this->overlapsExistingAppointment(
                $doctor,
                $startsAt,
                $endsAt,
                $schedule->buffer_before,
                $schedule->buffer_after,
                $ignoreAppointment
            )) {
                return false;
            }

            return true;
        }

        /*
         * Si no existe horario regular, una excepción
         * "available" puede abrir disponibilidad extraordinaria.
         */
        foreach (
            $exceptions->where('type', 'available')
            as $exception
        ) {
            if (
                ! $exception->start_time
                || ! $exception->end_time
            ) {
                continue;
            }

            $duration = $durationMinutes ?? 30;

            $endsAt = $startsAt
                ->copy()
                ->addMinutes($duration);

            if (! $this->fitsInsideException(
                $exception,
                $startsAt,
                $endsAt
            )) {
                continue;
            }

            if ($this->overlapsBlockedException(
                $exceptions,
                $startsAt,
                $endsAt
            )) {
                return false;
            }

            if ($this->overlapsExistingAppointment(
                $doctor,
                $startsAt,
                $endsAt,
                0,
                0,
                $ignoreAppointment
            )) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function hasFullDayBlock(
        Collection $exceptions
    ): bool {
        return $exceptions
            ->where('type', 'blocked')
            ->contains(
                fn(ScheduleException $exception) =>
                ! $exception->start_time
                    && ! $exception->end_time
            );
    }

    private function fitsInsideSchedule(
        Schedule $schedule,
        Carbon $startsAt,
        Carbon $endsAt,
    ): bool {
        $scheduleStart = Carbon::parse(
            $startsAt->toDateString()
                . ' '
                . $schedule->start_time
        );

        $scheduleEnd = Carbon::parse(
            $startsAt->toDateString()
                . ' '
                . $schedule->end_time
        );

        /*
         * Los buffers también deben caber dentro del
         * horario laboral.
         */
        $effectiveStart = $startsAt
            ->copy()
            ->subMinutes($schedule->buffer_before);

        $effectiveEnd = $endsAt
            ->copy()
            ->addMinutes($schedule->buffer_after);

        return $effectiveStart->greaterThanOrEqualTo(
            $scheduleStart
        )
            && $effectiveEnd->lessThanOrEqualTo(
                $scheduleEnd
            );
    }

    private function fitsInsideException(
        ScheduleException $exception,
        Carbon $startsAt,
        Carbon $endsAt,
    ): bool {
        $windowStart = Carbon::parse(
            $startsAt->toDateString()
                . ' '
                . $exception->start_time
        );

        $windowEnd = Carbon::parse(
            $startsAt->toDateString()
                . ' '
                . $exception->end_time
        );

        return $startsAt->greaterThanOrEqualTo(
            $windowStart
        )
            && $endsAt->lessThanOrEqualTo(
                $windowEnd
            );
    }

    private function overlapsBlockedException(
        Collection $exceptions,
        Carbon $startsAt,
        Carbon $endsAt,
    ): bool {
        foreach (
            $exceptions->where('type', 'blocked')
            as $exception
        ) {
            if (
                ! $exception->start_time
                || ! $exception->end_time
            ) {
                continue;
            }

            $blockedStart = Carbon::parse(
                $startsAt->toDateString()
                    . ' '
                    . $exception->start_time
            );

            $blockedEnd = Carbon::parse(
                $startsAt->toDateString()
                    . ' '
                    . $exception->end_time
            );

            if (
                $startsAt->lt($blockedEnd)
                && $endsAt->gt($blockedStart)
            ) {
                return true;
            }
        }

        return false;
    }

    private function overlapsExistingAppointment(
        DoctorProfile $doctor,
        Carbon $startsAt,
        Carbon $endsAt,
        int $bufferBefore,
        int $bufferAfter,
        ?Appointment $ignoreAppointment,
    ): bool {
        $effectiveStart = $startsAt
            ->copy()
            ->subMinutes($bufferBefore);

        $effectiveEnd = $endsAt
            ->copy()
            ->addMinutes($bufferAfter);

        return Appointment::query()
            ->where('doctor_profile_id', $doctor->id)
            ->whereIn(
                'status',
                self::BLOCKING_STATUSES
            )
            ->when(
                $ignoreAppointment,
                fn($query) => $query->where(
                    'id',
                    '!=',
                    $ignoreAppointment->id
                )
            )
            ->where(
                'starts_at',
                '<',
                $effectiveEnd
            )
            ->where(
                'ends_at',
                '>',
                $effectiveStart
            )
            ->exists();
    }

    public function slotsForDate(
        DoctorProfile $doctor,
        CarbonInterface|string $date,
        ?int $durationMinutes = null,
        ?Appointment $ignoreAppointment = null,
    ): Collection {
        $date = $date instanceof CarbonInterface
            ? Carbon::instance($date)->startOfDay()
            : Carbon::parse($date)->startOfDay();

        $dayOfWeek = $date->dayOfWeek;

        $slots = collect();

        $schedules = Schedule::query()
            ->where('doctor_profile_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('active', true)
            ->orderBy('start_time')
            ->get();

        $exceptions = ScheduleException::query()
            ->where('doctor_profile_id', $doctor->id)
            ->whereDate('date', $date->toDateString())
            ->get();

        if ($this->hasFullDayBlock($exceptions)) {
            return collect();
        }

        foreach ($schedules as $schedule) {
            $duration = $durationMinutes
                ?? $schedule->appointment_duration;

            $cursor = Carbon::parse(
                $date->toDateString() . ' ' . $schedule->start_time
            )->addMinutes($schedule->buffer_before);

            $scheduleEnd = Carbon::parse(
                $date->toDateString() . ' ' . $schedule->end_time
            )->subMinutes($schedule->buffer_after);

            while (
                $cursor
                ->copy()
                ->addMinutes($duration)
                ->lessThanOrEqualTo($scheduleEnd)
            ) {
                if (
                    $this->isAvailable(
                        $doctor,
                        $cursor,
                        $duration,
                        $ignoreAppointment
                    )
                ) {
                    $slots->push(
                        $cursor->copy()
                    );
                }

                $cursor->addMinutes($duration);
            }
        }

        /*
        * Disponibilidad extraordinaria.
        */
        foreach (
            $exceptions->where('type', 'available')
            as $exception
        ) {
            if (
                ! $exception->start_time
                || ! $exception->end_time
            ) {
                continue;
            }

            $duration = $durationMinutes ?? 30;

            $cursor = Carbon::parse(
                $date->toDateString()
                    . ' '
                    . $exception->start_time
            );

            $windowEnd = Carbon::parse(
                $date->toDateString()
                    . ' '
                    . $exception->end_time
            );

            while (
                $cursor
                ->copy()
                ->addMinutes($duration)
                ->lessThanOrEqualTo($windowEnd)
            ) {
                if (
                    $this->isAvailable(
                        $doctor,
                        $cursor,
                        $duration,
                        $ignoreAppointment
                    )
                ) {
                    $slots->push(
                        $cursor->copy()
                    );
                }

                $cursor->addMinutes($duration);
            }
        }

        return $slots
            ->unique(
                fn(Carbon $slot) =>
                $slot->format('Y-m-d H:i:s')
            )
            ->sortBy(
                fn(Carbon $slot) =>
                $slot->timestamp
            )
            ->filter(
                fn(Carbon $slot) =>
                $slot->greaterThan(
                    now()->addMinutes(5)
                )
            )
            ->values();
    }
}
