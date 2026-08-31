<?php

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Services\PostalCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
    #[Layout('layouts::app')]
    #[Title('Configuración | DocTotal')]
    class extends Component
    {
        use WithFileUploads;

        public DoctorProfile $doctor;
        public PracticeProfile $practice;

        // Médico
        public string $first_name = '';
        public string $last_name = '';
        public string $second_last_name = '';
        public ?int $specialty_id = null;
        public string $professional_license = '';
        public string $doctor_phone = '';
        public string $doctor_whatsapp = '';
        public string $bio = '';

        public $photo;
        public $signature;

        // Consultorio
        public string $public_name = '';
        public string $legal_name = '';
        public string $tax_id = '';
        public string $description = '';

        public $logo;

        public string $practice_phone = '';
        public string $practice_whatsapp = '';
        public string $practice_email = '';

        // Dirección
        public string $address_line_1 = '';
        public string $address_line_2 = '';
        public string $neighborhood = '';
        public string $city = '';
        public string $state = '';
        public string $postal_code = '';
        public string $country = 'MX';

        public array $neighborhoodOptions = [];
        public bool $postalCodeLoading = false;
        public ?string $postalCodeError = null;

        // Horarios
        public int $appointment_duration = 30;

        public array $days = [
            1 => [
                'label' => 'Lunes',
                'enabled' => false,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            2 => [
                'label' => 'Martes',
                'enabled' => false,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            3 => [
                'label' => 'Miércoles',
                'enabled' => false,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            4 => [
                'label' => 'Jueves',
                'enabled' => false,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            5 => [
                'label' => 'Viernes',
                'enabled' => false,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            6 => [
                'label' => 'Sábado',
                'enabled' => false,
                'start_time' => '09:00',
                'end_time' => '14:00',
            ],
            0 => [
                'label' => 'Domingo',
                'enabled' => false,
                'start_time' => '09:00',
                'end_time' => '14:00',
            ],
        ];

        // Impresión
        public string $print_footer = '';

        public function mount(): void
        {
            $this->doctor = DoctorProfile::query()
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $this->practice = PracticeProfile::query()
                ->firstOrFail();

            // Médico
            $this->first_name = $this->doctor->first_name;
            $this->last_name = $this->doctor->last_name;
            $this->second_last_name = $this->doctor->second_last_name ?? '';
            $this->specialty_id = $this->doctor->specialty_id;
            $this->professional_license = $this->doctor->professional_license ?? '';
            $this->doctor_phone = $this->doctor->phone ?? '';
            $this->doctor_whatsapp = $this->doctor->whatsapp ?? '';
            $this->bio = $this->doctor->bio ?? '';

            // Consultorio
            $this->public_name = $this->practice->public_name;
            $this->legal_name = $this->practice->legal_name ?? '';
            $this->tax_id = $this->practice->tax_id ?? '';
            $this->description = $this->practice->description ?? '';

            $this->practice_phone = $this->practice->phone ?? '';
            $this->practice_whatsapp = $this->practice->whatsapp ?? '';
            $this->practice_email = $this->practice->email ?? '';

            // Dirección
            $this->address_line_1 = $this->practice->address_line_1 ?? '';
            $this->address_line_2 = $this->practice->address_line_2 ?? '';
            $this->neighborhood = $this->practice->neighborhood ?? '';
            $this->city = $this->practice->city ?? '';
            $this->state = $this->practice->state ?? '';
            $this->postal_code = $this->practice->postal_code ?? '';
            $this->country = $this->practice->country ?? 'MX';

            // Horarios existentes
            $schedules = Schedule::query()
                ->where('doctor_profile_id', $this->doctor->id)
                ->orderBy('day_of_week')
                ->get();

            if ($schedules->isNotEmpty()) {
                $firstSchedule = $schedules->first();

                $this->appointment_duration =
                    $firstSchedule->appointment_duration ?: 30;

                foreach ($schedules as $schedule) {
                    $dayOfWeek = $schedule->day_of_week;

                    if (! array_key_exists($dayOfWeek, $this->days)) {
                        continue;
                    }

                    $this->days[$dayOfWeek]['enabled'] = (bool) $schedule->active;
                    $this->days[$dayOfWeek]['start_time'] =
                        substr((string) $schedule->start_time, 0, 5);
                    $this->days[$dayOfWeek]['end_time'] =
                        substr((string) $schedule->end_time, 0, 5);
                }
            }

            // Impresión
            $this->print_footer = $this->practice->print_footer ?? '';
        }

        public function specialties()
        {
            return Specialty::query()
                ->where('active', true)
                ->orderBy('name')
                ->get();
        }

        public function updatedPostalCode(string $value): void
        {
            $this->postalCodeError = null;
            $this->neighborhoodOptions = [];

            if (! preg_match('/^\d{5}$/', $value)) {
                return;
            }

            $this->postalCodeLoading = true;

            try {
                $result = app(PostalCodeService::class)->lookup($value);

                $this->state = $result['state'] ?? '';

                $this->city = $result['city']
                    ?? $result['municipality']
                    ?? '';

                $this->neighborhoodOptions = $result['neighborhoods'] ?? [];

                if (count($this->neighborhoodOptions) === 1) {
                    $this->neighborhood = $this->neighborhoodOptions[0];
                } elseif (
                    $this->neighborhood !== ''
                    && ! in_array(
                        $this->neighborhood,
                        $this->neighborhoodOptions,
                        true
                    )
                ) {
                    $this->neighborhood = '';
                }
            } catch (\RuntimeException $exception) {
                $this->postalCodeError = $exception->getMessage();
            } finally {
                $this->postalCodeLoading = false;
            }
        }

        public function save(): void
        {
            $validated = $this->validate([
                // Médico
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'second_last_name' => ['nullable', 'string', 'max:100'],
                'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
                'professional_license' => ['nullable', 'string', 'max:100'],
                'doctor_phone' => ['nullable', 'string', 'max:30'],
                'doctor_whatsapp' => ['nullable', 'string', 'max:30'],
                'bio' => ['nullable', 'string', 'max:5000'],

                'photo' => ['nullable', 'image', 'max:3072'],
                'signature' => ['nullable', 'image', 'max:3072'],

                // Consultorio
                'public_name' => ['required', 'string', 'max:150'],
                'legal_name' => ['nullable', 'string', 'max:190'],
                'tax_id' => ['nullable', 'string', 'max:30'],
                'description' => ['nullable', 'string', 'max:5000'],
                'logo' => ['nullable', 'image', 'max:3072'],

                'practice_phone' => ['nullable', 'string', 'max:30'],
                'practice_whatsapp' => ['nullable', 'string', 'max:30'],
                'practice_email' => ['nullable', 'email', 'max:190'],

                // Dirección
                'address_line_1' => ['nullable', 'string', 'max:255'],
                'address_line_2' => ['nullable', 'string', 'max:255'],
                'neighborhood' => ['nullable', 'string', 'max:150'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'regex:/^\d{5}$/'],
                'country' => ['required', 'string', 'size:2'],

                // Horarios
                'appointment_duration' => [
                    'required',
                    'integer',
                    'in:15,20,30,45,60,90',
                ],
                'days.*.enabled' => ['boolean'],
                'days.*.start_time' => [
                    'required_if:days.*.enabled,true',
                ],
                'days.*.end_time' => [
                    'required_if:days.*.enabled,true',
                ],

                // Impresión
                'print_footer' => ['nullable', 'string', 'max:255'],
            ]);

            foreach ($this->days as $day) {
                if (
                    $day['enabled']
                    && $day['start_time'] >= $day['end_time']
                ) {
                    $this->addError(
                        'days',
                        'La hora de salida debe ser posterior a la hora de entrada.'
                    );

                    return;
                }
            }

            DB::transaction(function () use ($validated): void {
                $doctorData = [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'second_last_name' => $validated['second_last_name'] ?: null,
                    'specialty_id' => $validated['specialty_id'],
                    'professional_license' =>
                    $validated['professional_license'] ?: null,
                    'phone' => $validated['doctor_phone'] ?: null,
                    'whatsapp' => $validated['doctor_whatsapp'] ?: null,
                    'bio' => $validated['bio'] ?: null,
                ];

                if ($this->photo) {
                    if ($this->doctor->photo_path) {
                        Storage::disk('public')->delete(
                            $this->doctor->photo_path
                        );
                    }

                    $doctorData['photo_path'] = $this->photo->store(
                        'doctor-photos',
                        'public'
                    );
                }

                if ($this->signature) {
                    if ($this->doctor->signature_path) {
                        Storage::disk('public')->delete(
                            $this->doctor->signature_path
                        );
                    }

                    $doctorData['signature_path'] = $this->signature->store(
                        'doctor-signatures',
                        'public'
                    );
                }

                $this->doctor->update($doctorData);

                $practiceData = [
                    'public_name' => $validated['public_name'],
                    'legal_name' => $validated['legal_name'] ?: null,
                    'tax_id' => $validated['tax_id'] ?: null,
                    'description' => $validated['description'] ?: null,

                    'phone' => $validated['practice_phone'] ?: null,
                    'whatsapp' => $validated['practice_whatsapp'] ?: null,
                    'email' => $validated['practice_email'] ?: null,

                    'address_line_1' => $validated['address_line_1'] ?: null,
                    'address_line_2' => $validated['address_line_2'] ?: null,
                    'neighborhood' => $validated['neighborhood'] ?: null,
                    'city' => $validated['city'] ?: null,
                    'state' => $validated['state'] ?: null,
                    'postal_code' => $validated['postal_code'] ?: null,
                    'country' => strtoupper($validated['country']),

                    'print_footer' => $validated['print_footer'] ?: null,
                ];

                if ($this->logo) {
                    if ($this->practice->logo_path) {
                        Storage::disk('public')->delete(
                            $this->practice->logo_path
                        );
                    }

                    $practiceData['logo_path'] = $this->logo->store(
                        'practice-logos',
                        'public'
                    );
                }

                $this->practice->update($practiceData);

                Schedule::query()
                    ->where('doctor_profile_id', $this->doctor->id)
                    ->delete();

                foreach ($this->days as $dayOfWeek => $day) {
                    if (! $day['enabled']) {
                        continue;
                    }

                    Schedule::create([
                        'doctor_profile_id' => $this->doctor->id,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $day['start_time'],
                        'end_time' => $day['end_time'],
                        'appointment_duration' => $validated['appointment_duration'],
                        'buffer_before' => 0,
                        'buffer_after' => 0,
                        'active' => true,
                    ]);
                }
            });

            $this->doctor->refresh();
            $this->practice->refresh();

            $this->reset([
                'photo',
                'signature',
                'logo',
            ]);

            $this->dispatch(
                'swal',
                title: 'Configuración guardada',
                text: 'Tu perfil, consultorio, dirección y horarios se actualizaron correctamente.',
                icon: 'success'
            );
        }
    };
?>

<div class="dt-page mx-auto max-w-6xl">

    {{-- Header --}}
    <div class="relative mb-7 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950 px-6 py-7 text-white shadow-lg sm:px-8">
        <div class="absolute -right-16 -top-20 h-60 w-60 rounded-full bg-blue-500/10 blur-2xl"></div>
        <div class="absolute -bottom-24 right-24 h-52 w-52 rounded-full bg-violet-500/10 blur-2xl"></div>

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold text-blue-100 backdrop-blur">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z" />
                        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.37a1.7 1.7 0 0 0-1 .63 1.7 1.7 0 0 0-.37 1.08V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.23 15a1.7 1.7 0 0 0-.63-1 1.7 1.7 0 0 0-1.08-.37H2.4v-4h.09A1.7 1.7 0 0 0 4 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8.4 4.23a1.7 1.7 0 0 0 1-.63A1.7 1.7 0 0 0 9.77 2.5V2.4h4v.09A1.7 1.7 0 0 0 14.8 4a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.17 8.4a1.7 1.7 0 0 0 .63 1 1.7 1.7 0 0 0 1.08.37H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z" />
                    </svg>
                    Configuración de DocTotal
                </div>

                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Perfil y consultorio
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                    Actualiza tus datos profesionales, consultorio, dirección,
                    horarios de atención y configuración de documentos.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs text-slate-300">Duración de cita</p>
                    <p class="mt-1 font-semibold">{{ $appointment_duration }} min</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs text-slate-300">Días activos</p>
                    <p class="mt-1 font-semibold">
                        {{ collect($days)->where('enabled', true)->count() }}
                    </p>
                </div>
                <div class="col-span-2 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur sm:col-span-1">
                    <p class="text-xs text-slate-300">Consultorio</p>
                    <p class="mt-1 max-w-40 truncate font-semibold">
                        {{ $public_name ?: 'Sin nombre' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings navigation --}}
    <div class="mb-7 border-b border-slate-200">
        <nav class="-mb-px flex gap-7 overflow-x-auto">
            <a
                href="{{ route('settings.profile') }}"
                class="whitespace-nowrap border-b-2 border-blue-600 px-1 pb-3 text-sm font-semibold text-blue-700">
                Perfil y consultorio
            </a>

            <a
                href="{{ route('settings.billing') }}"
                class="whitespace-nowrap border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-900">
                Facturación
            </a>
        </nav>
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- Perfil profesional --}}
        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21a8 8 0 0 1 16 0" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Perfil profesional</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Información que identifica al médico y puede aparecer en recetas y documentos clínicos.
                    </p>
                </div>
            </div>

            <div class="dt-card-body">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="dt-label">Nombre *</label>
                        <input wire:model="first_name" type="text" class="dt-input">
                        @error('first_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="dt-label">Apellido paterno *</label>
                        <input wire:model="last_name" type="text" class="dt-input">
                        @error('last_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="dt-label">Apellido materno</label>
                        <input wire:model="second_last_name" type="text" class="dt-input">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="dt-label">Especialidad</label>
                        <select wire:model="specialty_id" class="dt-select">
                            <option value="">Sin especialidad</option>
                            @foreach ($this->specialties() as $specialty)
                            <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                            @endforeach
                        </select>
                        @error('specialty_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="dt-label">Cédula profesional</label>
                        <input wire:model="professional_license" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">Teléfono</label>
                        <input wire:model="doctor_phone" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">WhatsApp</label>
                        <input wire:model="doctor_whatsapp" type="text" class="dt-input">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="dt-label">Biografía profesional</label>
                        <textarea wire:model="bio" rows="4" class="dt-textarea" placeholder="Experiencia, enfoque profesional, formación o información que desees mostrar."></textarea>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 border-t border-slate-100 pt-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm ring-1 ring-slate-200">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="3" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <path d="m21 15-5-5L5 21" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <label class="dt-label">Fotografía profesional</label>
                                <input wire:model="photo" type="file" accept="image/*" class="block w-full text-sm text-slate-600">
                                @if ($doctor->photo_path)
                                <p class="mt-2 text-xs font-medium text-emerald-600">Fotografía guardada actualmente.</p>
                                @endif
                                @error('photo') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm ring-1 ring-slate-200">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19c4-4 5-10 9-14 1.5-1.5 4-.5 4 1.5 0 4-6 7-8 8.5 3-1 7-2 11-1" />
                                    <path d="M5 19h14" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <label class="dt-label">Firma</label>
                                <input wire:model="signature" type="file" accept="image/*" class="block w-full text-sm text-slate-600">
                                @if ($doctor->signature_path)
                                <p class="mt-2 text-xs font-medium text-emerald-600">Firma guardada actualmente.</p>
                                @endif
                                @error('signature') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Consultorio --}}
        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 21h18" />
                        <path d="M5 21V7l7-4 7 4v14" />
                        <path d="M9 10h2v2H9zM13 10h2v2h-2zM9 15h2v2H9zM13 15h2v2h-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Consultorio</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Datos comerciales y de contacto del lugar donde atiendes.
                    </p>
                </div>
            </div>

            <div class="dt-card-body">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="dt-label">Nombre público *</label>
                        <input wire:model="public_name" type="text" class="dt-input">
                        @error('public_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="dt-label">Razón social</label>
                        <input wire:model="legal_name" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">RFC</label>
                        <input wire:model="tax_id" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">Correo</label>
                        <input wire:model="practice_email" type="email" class="dt-input">
                        @error('practice_email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="dt-label">Teléfono</label>
                        <input wire:model="practice_phone" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">WhatsApp</label>
                        <input wire:model="practice_whatsapp" type="text" class="dt-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="dt-label">Descripción</label>
                        <textarea wire:model="description" rows="3" class="dt-textarea"></textarea>
                    </div>

                    <div class="sm:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="dt-label">Logo del consultorio</label>
                        <input wire:model="logo" type="file" accept="image/*" class="block w-full text-sm text-slate-600">
                        @if ($practice->logo_path)
                        <p class="mt-2 text-xs font-medium text-emerald-600">Logo guardado actualmente.</p>
                        @endif
                        @error('logo') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Dirección --}}
        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
                        <circle cx="12" cy="10" r="2.5" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Dirección</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Escribe el código postal para completar automáticamente ciudad, estado y colonias disponibles.
                    </p>
                </div>
            </div>

            <div class="dt-card-body">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="dt-label">Código postal</label>
                        <div class="relative">
                            <input
                                wire:model.live.debounce.500ms="postal_code"
                                type="text"
                                inputmode="numeric"
                                maxlength="5"
                                placeholder="Ej. 29025"
                                class="dt-input pr-24">

                            <div
                                wire:loading
                                wire:target="postal_code"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-blue-600">
                                Buscando...
                            </div>
                        </div>

                        @error('postal_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror

                        @if ($postalCodeError)
                        <p class="mt-2 text-sm text-amber-600">
                            {{ $postalCodeError }} Puedes capturar la dirección manualmente.
                        </p>
                        @endif
                    </div>

                    <div>
                        <label class="dt-label">Colonia</label>

                        @if (count($neighborhoodOptions) > 0)
                        <select wire:model="neighborhood" class="dt-select">
                            <option value="">Selecciona una colonia</option>
                            @foreach ($neighborhoodOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @else
                        <input wire:model="neighborhood" type="text" class="dt-input" placeholder="Colonia">
                        @endif
                    </div>

                    <div>
                        <label class="dt-label">Ciudad / Municipio</label>
                        <input wire:model="city" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">Estado</label>
                        <input wire:model="state" type="text" class="dt-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="dt-label">Calle y número</label>
                        <input wire:model="address_line_1" type="text" class="dt-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="dt-label">Interior / complemento / referencia</label>
                        <input wire:model="address_line_2" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">País</label>
                        <input wire:model="country" type="text" maxlength="2" class="dt-input uppercase">
                    </div>
                </div>
            </div>
        </section>

        {{-- Horarios --}}
        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="16" rx="2" />
                            <path d="M16 3v4M8 3v4M3 10h18" />
                            <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="font-semibold text-slate-950">Horarios y agenda</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Define los días en que atiendes, las horas disponibles y la duración de cada cita.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-52">
                    <label class="dt-label">Duración de cada cita</label>
                    <select wire:model="appointment_duration" class="dt-select">
                        <option value="15">15 minutos</option>
                        <option value="20">20 minutos</option>
                        <option value="30">30 minutos</option>
                        <option value="45">45 minutos</option>
                        <option value="60">60 minutos</option>
                        <option value="90">90 minutos</option>
                    </select>
                    @error('appointment_duration') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="dt-card-body">
                <div class="space-y-3">
                    @foreach ($days as $dayNumber => $day)
                    <div
                        wire:key="settings-day-{{ $dayNumber }}"
                        class="rounded-2xl border p-4 transition
                                {{ $day['enabled']
                                    ? 'border-blue-200 bg-blue-50/40'
                                    : 'border-slate-200 bg-white' }}">

                        <div class="grid gap-4 md:grid-cols-[180px_1fr_1fr] md:items-center">
                            <label class="flex cursor-pointer items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="days.{{ $dayNumber }}.enabled"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                                <div>
                                    <p class="font-semibold {{ $day['enabled'] ? 'text-slate-950' : 'text-slate-500' }}">
                                        {{ $day['label'] }}
                                    </p>
                                    <p class="mt-0.5 text-xs {{ $day['enabled'] ? 'text-blue-600' : 'text-slate-400' }}">
                                        {{ $day['enabled'] ? 'Disponible' : 'Sin atención' }}
                                    </p>
                                </div>
                            </label>

                            <div>
                                <label class="dt-label">Hora de inicio</label>
                                <input
                                    type="time"
                                    wire:model="days.{{ $dayNumber }}.start_time"
                                    @disabled(! $day['enabled'])
                                    class="dt-input disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            </div>

                            <div>
                                <label class="dt-label">Hora de salida</label>
                                <input
                                    type="time"
                                    wire:model="days.{{ $dayNumber }}.end_time"
                                    @disabled(! $day['enabled'])
                                    class="dt-input disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @error('days')
                <div class="mt-4 dt-alert dt-alert-danger">
                    {{ $message }}
                </div>
                @enderror

                <div class="mt-5 flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50 p-4">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 11v5M12 8h.01" />
                    </svg>
                    <p class="text-sm leading-6 text-sky-700">
                        Los cambios de horario afectarán la disponibilidad futura de la agenda.
                        Las citas ya registradas no se eliminan al modificar estos horarios.
                    </p>
                </div>
            </div>
        </section>

        {{-- Documentos impresos --}}
        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9V2h12v7" />
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                        <rect x="6" y="14" width="12" height="8" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Documentos impresos</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Personaliza información que puede aparecer en recetas y otros documentos.
                    </p>
                </div>
            </div>

            <div class="dt-card-body">
                <label class="dt-label">Pie de página</label>
                <input
                    wire:model="print_footer"
                    type="text"
                    placeholder="Ej. Citas: 961 000 0000"
                    class="dt-input">

                <p class="mt-2 text-xs text-slate-500">
                    Este texto puede utilizarse como información de contacto al pie de documentos impresos.
                </p>
            </div>
        </section>

        {{-- Sticky actions --}}
        <div class="sticky bottom-4 z-20">
            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-900">¿Terminaste tus cambios?</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Guarda para actualizar tu perfil, consultorio y disponibilidad.
                    </p>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="dt-btn dt-btn-primary min-w-44">

                    <span wire:loading.remove wire:target="save">
                        Guardar configuración
                    </span>

                    <span wire:loading wire:target="save">
                        Guardando...
                    </span>
                </button>
            </div>
        </div>

    </form>
</div>

@script
<script>
    $wire.on('swal', (event) => {
        Swal.fire({
            title: event.title,
            text: event.text,
            icon: event.icon,
            confirmButtonText: 'Aceptar'
        });
    });
</script>
@endscript