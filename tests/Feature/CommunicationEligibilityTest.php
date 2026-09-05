<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Communications\AppointmentReminderService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_opt_out_of_each_transactional_channel(): void
    {
        foreach ([
            Communication::CHANNEL_EMAIL => 'allow_email_communications',
            Communication::CHANNEL_WHATSAPP => 'allow_whatsapp_communications',
            Communication::CHANNEL_SMS => 'allow_sms_communications',
        ] as $channel => $preference) {
            [$patient, $appointment] = $this->context(uniqid($channel));

            $patient->update([$preference => false]);

            $this->assertNull(
                app(AppointmentReminderService::class)->create($appointment, $channel)
            );
        }

        $this->assertSame(0, Communication::query()->count());
    }

    public function test_channel_preferences_do_not_disable_other_channels(): void
    {
        [$patient, $appointment] = $this->context('independent');

        $patient->update(['allow_whatsapp_communications' => false]);

        $communication = app(AppointmentReminderService::class)->create(
            $appointment,
            Communication::CHANNEL_EMAIL
        );

        $this->assertNotNull($communication);
        $this->assertSame(Communication::CHANNEL_EMAIL, $communication->channel);
    }

    private function context(string $suffix): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.strtolower(preg_replace('/[^a-z0-9]+/i', '-', $suffix)),
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'email' => $suffix.'@example.com',
            'phone' => '9611111111',
            'whatsapp' => '9612222222',
        ]);

        $user = User::factory()->create();
        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Prueba',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        return [$patient, $appointment];
    }
}
