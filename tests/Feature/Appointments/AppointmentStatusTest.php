<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class AppointmentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_appointment_can_be_confirmed(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        $appointment = $this->createAppointment();

        $appointment->confirm();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_CONFIRMED,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->confirmed_at
        );

        $this->assertTrue(
            $appointment->confirmed_at->equalTo(now())
        );
    }

    public function test_scheduled_appointment_can_be_checked_in_directly(): void
    {
        $appointment = $this->createAppointment();

        $appointment->checkIn();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_CHECKED_IN,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->checked_in_at
        );
    }

    public function test_confirmed_appointment_can_be_checked_in(): void
    {
        $appointment = $this->createAppointment();

        $appointment->confirm();
        $appointment->checkIn();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_CHECKED_IN,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->confirmed_at
        );

        $this->assertNotNull(
            $appointment->checked_in_at
        );
    }

    public function test_checked_in_appointment_can_be_started(): void
    {
        $appointment = $this->createAppointment();

        $appointment->checkIn();
        $appointment->start();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_IN_PROGRESS,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->started_at
        );
    }

    public function test_scheduled_appointment_can_be_started_directly(): void
    {
        $appointment = $this->createAppointment();

        $appointment->start();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_IN_PROGRESS,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->started_at
        );
    }

    public function test_confirmed_appointment_can_be_started_directly(): void
    {
        $appointment = $this->createAppointment();

        $appointment->confirm();
        $appointment->start();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_IN_PROGRESS,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->started_at
        );
    }

    public function test_in_progress_appointment_can_be_completed(): void
    {
        $appointment = $this->createAppointment();

        $appointment->start();
        $appointment->complete();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_COMPLETED,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->completed_at
        );

        $this->assertTrue(
            $appointment->isTerminal()
        );
    }

    public function test_scheduled_appointment_can_be_cancelled(): void
    {
        $appointment = $this->createAppointment();

        $appointment->cancel(
            'Paciente solicitó cancelar'
        );

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_CANCELLED,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->cancelled_at
        );

        $this->assertSame(
            'Paciente solicitó cancelar',
            $appointment->cancellation_reason
        );

        $this->assertTrue(
            $appointment->isTerminal()
        );
    }

    public function test_confirmed_appointment_can_be_cancelled(): void
    {
        $appointment = $this->createAppointment();

        $appointment->confirm();
        $appointment->cancel(
            'Cancelación'
        );

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_CANCELLED,
            $appointment->status
        );
    }

    public function test_checked_in_appointment_can_be_cancelled(): void
    {
        $appointment = $this->createAppointment();

        $appointment->checkIn();
        $appointment->cancel(
            'No pudo continuar'
        );

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_CANCELLED,
            $appointment->status
        );
    }

    public function test_scheduled_appointment_cannot_be_marked_no_show_before_grace_period(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:44:00')
        );

        $appointment = $this->createAppointment();

        $this->assertFalse(
            $appointment->canMarkNoShow()
        );

        $this->expectException(
            LogicException::class
        );

        $appointment->markNoShow();
    }

    public function test_scheduled_appointment_can_be_marked_as_no_show_after_grace_period(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:45:00')
        );

        $appointment = $this->createAppointment();

        $this->assertTrue(
            $appointment->canMarkNoShow()
        );

        $appointment->markNoShow();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_NO_SHOW,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->no_show_at
        );

        $this->assertTrue(
            $appointment->no_show_at->equalTo(now())
        );

        $this->assertTrue(
            $appointment->isTerminal()
        );
    }

    public function test_confirmed_appointment_can_be_marked_as_no_show_after_grace_period(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:45:00')
        );

        $appointment = $this->createAppointment();

        $appointment->confirm();
        $appointment->markNoShow();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_NO_SHOW,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->confirmed_at
        );

        $this->assertNotNull(
            $appointment->no_show_at
        );
    }

    public function test_no_show_is_not_available_when_appointment_ends(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:30:00')
        );

        $appointment = $this->createAppointment();

        $this->assertFalse(
            $appointment->hasPassedNoShowGracePeriod()
        );

        $this->assertFalse(
            $appointment->canMarkNoShow()
        );
    }

    public function test_no_show_is_not_available_fourteen_minutes_after_appointment_ends(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:44:00')
        );

        $appointment = $this->createAppointment();

        $this->assertFalse(
            $appointment->hasPassedNoShowGracePeriod()
        );

        $this->assertFalse(
            $appointment->canMarkNoShow()
        );
    }

    public function test_no_show_becomes_available_exactly_fifteen_minutes_after_appointment_ends(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:45:00')
        );

        $appointment = $this->createAppointment();

        $this->assertTrue(
            $appointment->hasPassedNoShowGracePeriod()
        );

        $this->assertTrue(
            $appointment->canMarkNoShow()
        );
    }

    public function test_completed_appointment_cannot_be_changed_again(): void
    {
        $appointment = $this->createAppointment();

        $appointment->start();
        $appointment->complete();

        $this->expectException(
            LogicException::class
        );

        $appointment->checkIn();
    }

    public function test_cancelled_appointment_cannot_be_started(): void
    {
        $appointment = $this->createAppointment();

        $appointment->cancel();

        $this->expectException(
            LogicException::class
        );

        $appointment->start();
    }

    public function test_no_show_appointment_cannot_be_started(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:45:00')
        );

        $appointment = $this->createAppointment();

        $appointment->markNoShow();

        $this->expectException(
            LogicException::class
        );

        $appointment->start();
    }

    public function test_scheduled_appointment_can_be_rescheduled(): void
    {
        $appointment = $this->createAppointment();

        $newStart = Carbon::parse(
            '2026-08-25 11:00:00'
        );

        $newEnd = Carbon::parse(
            '2026-08-25 11:30:00'
        );

        $appointment->reschedule(
            $newStart,
            $newEnd
        );

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_SCHEDULED,
            $appointment->status
        );

        $this->assertTrue(
            $appointment->starts_at->equalTo(
                $newStart
            )
        );

        $this->assertTrue(
            $appointment->ends_at->equalTo(
                $newEnd
            )
        );
    }

    public function test_confirmed_appointment_returns_to_scheduled_when_rescheduled(): void
    {
        $appointment = $this->createAppointment();

        $appointment->confirm();

        $this->assertNotNull(
            $appointment->fresh()->confirmed_at
        );

        $appointment->reschedule(
            Carbon::parse(
                '2026-08-25 11:00:00'
            ),
            Carbon::parse(
                '2026-08-25 11:30:00'
            )
        );

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_SCHEDULED,
            $appointment->status
        );

        $this->assertNull(
            $appointment->confirmed_at
        );
    }

    public function test_in_progress_appointment_cannot_be_rescheduled(): void
    {
        $appointment = $this->createAppointment();

        $appointment->start();

        $this->expectException(
            LogicException::class
        );

        $appointment->reschedule(
            Carbon::parse(
                '2026-08-25 11:00:00'
            ),
            Carbon::parse(
                '2026-08-25 11:30:00'
            )
        );
    }

    public function test_can_reschedule_only_for_scheduled_or_confirmed_appointments(): void
    {
        $appointment = $this->createAppointment();

        $this->assertTrue(
            $appointment->canReschedule()
        );

        $appointment->confirm();

        $this->assertTrue(
            $appointment->canReschedule()
        );

        $appointment->checkIn();

        $this->assertFalse(
            $appointment->canReschedule()
        );
    }

    public function test_scheduled_appointment_exposes_correct_available_actions_before_no_show_grace_period(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        $appointment = $this->createAppointment();

        $this->assertTrue(
            $appointment->canConfirm()
        );

        $this->assertTrue(
            $appointment->canCheckIn()
        );

        $this->assertTrue(
            $appointment->canStart()
        );

        $this->assertTrue(
            $appointment->canReschedule()
        );

        $this->assertTrue(
            $appointment->canCancel()
        );

        /*
         * La cita todavía no ha terminado,
         * por lo que no debe ofrecer No-show.
         */
        $this->assertFalse(
            $appointment->canMarkNoShow()
        );

        $this->assertFalse(
            $appointment->canComplete()
        );

        $this->assertFalse(
            $appointment->isTerminal()
        );
    }

    public function test_scheduled_appointment_exposes_no_show_after_grace_period(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:45:00')
        );

        $appointment = $this->createAppointment();

        $this->assertTrue(
            $appointment->canMarkNoShow()
        );

        $this->assertFalse(
            $appointment->isTerminal()
        );
    }

    public function test_in_progress_appointment_only_exposes_complete_action(): void
    {
        $appointment = $this->createAppointment();

        $appointment->start();

        $this->assertFalse(
            $appointment->canConfirm()
        );

        $this->assertFalse(
            $appointment->canCheckIn()
        );

        $this->assertFalse(
            $appointment->canStart()
        );

        $this->assertFalse(
            $appointment->canReschedule()
        );

        $this->assertFalse(
            $appointment->canCancel()
        );

        $this->assertFalse(
            $appointment->canMarkNoShow()
        );

        $this->assertTrue(
            $appointment->canComplete()
        );

        $this->assertFalse(
            $appointment->isTerminal()
        );
    }

    public function test_terminal_appointment_has_no_available_actions(): void
    {
        $appointment = $this->createAppointment();

        $appointment->start();
        $appointment->complete();

        $this->assertFalse(
            $appointment->canConfirm()
        );

        $this->assertFalse(
            $appointment->canCheckIn()
        );

        $this->assertFalse(
            $appointment->canStart()
        );

        $this->assertFalse(
            $appointment->canReschedule()
        );

        $this->assertFalse(
            $appointment->canComplete()
        );

        $this->assertFalse(
            $appointment->canCancel()
        );

        $this->assertFalse(
            $appointment->canMarkNoShow()
        );

        $this->assertTrue(
            $appointment->isTerminal()
        );
    }

    private function createAppointment(): Appointment
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' =>
            'consultorio-' . str()->random(10),
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' =>
            str()->random(10) . '@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $specialty = Specialty::firstOrCreate(
            [
                'slug' => 'medicina-general',
            ],
            [
                'name' => 'Medicina General',
            ]
        );

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'specialty_id' => $specialty->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);

        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' =>
            $doctor->id,
            'starts_at' =>
            '2026-08-24 10:00:00',
            'ends_at' =>
            '2026-08-24 10:30:00',
            'status' =>
            Appointment::STATUS_SCHEDULED,
            'reason' =>
            'Consulta general',
        ]);
    }
}
