<?php

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Services\PostalCodeService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::onboarding')]
    #[Title('Configurar consultorio | DocTotal')]
    class extends Component
    {
        public int $step = 1;

        // Paso 1
        public string $first_name = '';
        public string $last_name = '';
        public ?int $specialty_id = null;
        public string $professional_license = '';
        public string $doctor_phone = '';
        public string $doctor_whatsapp = '';

        // Paso 2
        public string $public_name = '';
        public string $practice_phone = '';
        public string $practice_whatsapp = '';
        public string $address_line_1 = '';
        public string $address_line_2 = '';
        public string $neighborhood = '';
        public string $city = '';
        public string $state = '';
        public string $postal_code = '';

        public array $neighborhoodOptions = [];
        public bool $postalCodeLoading = false;
        public ?string $postalCodeError = null;

        // Paso 3
        public int $appointment_duration = 30;

        public array $days = [
            1 => [
                'label' => 'Lunes',
                'enabled' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            2 => [
                'label' => 'Martes',
                'enabled' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            3 => [
                'label' => 'Miércoles',
                'enabled' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            4 => [
                'label' => 'Jueves',
                'enabled' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
            5 => [
                'label' => 'Viernes',
                'enabled' => true,
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

        public function mount(): void
        {
            $user = auth()->user();
            $tenant = $user->tenant;

            if ($tenant->hasCompletedOnboarding()) {
                $this->redirectRoute('dashboard');

                return;
            }

            $doctor = DoctorProfile::query()
                ->where('user_id', $user->id)
                ->firstOrFail();

            $practice = PracticeProfile::query()
                ->where('tenant_id', $tenant->id)
                ->firstOrFail();

            $this->first_name = $doctor->first_name;
            $this->last_name = $doctor->last_name;
            $this->specialty_id = $doctor->specialty_id;
            $this->professional_license = $doctor->professional_license ?? '';
            $this->doctor_phone = $doctor->phone ?? '';
            $this->doctor_whatsapp = $doctor->whatsapp ?? '';

            $this->public_name = $practice->public_name;
            $this->practice_phone = $practice->phone ?? '';
            $this->practice_whatsapp = $practice->whatsapp ?? '';
            $this->address_line_1 = $practice->address_line_1 ?? '';
            $this->address_line_2 = $practice->address_line_2 ?? '';
            $this->neighborhood = $practice->neighborhood ?? '';
            $this->city = $practice->city ?? '';
            $this->state = $practice->state ?? '';
            $this->postal_code = $practice->postal_code ?? '';
        }

        public function specialties()
        {
            return Specialty::query()
                ->where('active', true)
                ->orderBy('name')
                ->get();
        }

        public function nextStep(): void
        {
            $this->validateCurrentStep();

            if ($this->step < 4) {
                $this->step++;
            }
        }

        public function previousStep(): void
        {
            if ($this->step > 1) {
                $this->step--;
            }
        }

        private function validateCurrentStep(): void
        {
            if ($this->step === 1) {
                $this->validate([
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'specialty_id' => ['required', 'exists:specialties,id'],
                    'professional_license' => ['nullable', 'string', 'max:100'],
                    'doctor_phone' => ['nullable', 'string', 'max:30'],
                    'doctor_whatsapp' => ['nullable', 'string', 'max:30'],
                ]);

                return;
            }

            if ($this->step === 2) {
                $this->validate([
                    'public_name' => ['required', 'string', 'max:150'],
                    'practice_phone' => ['nullable', 'string', 'max:30'],
                    'practice_whatsapp' => ['nullable', 'string', 'max:30'],
                    'address_line_1' => ['nullable', 'string', 'max:255'],
                    'address_line_2' => ['nullable', 'string', 'max:255'],
                    'neighborhood' => ['nullable', 'string', 'max:150'],
                    'city' => ['nullable', 'string', 'max:100'],
                    'state' => ['nullable', 'string', 'max:100'],
                    'postal_code' => [
                        'nullable',
                        'regex:/^\d{5}$/',
                    ],
                ]);

                return;
            }

            if ($this->step === 3) {
                $this->validate([
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
            }
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
            } catch (\RuntimeException $e) {
                $this->postalCodeError = $e->getMessage();
            } finally {
                $this->postalCodeLoading = false;
            }
        }

        public function finish(): void
        {
            $this->step = 3;
            $this->validateCurrentStep();

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }

            $user = auth()->user();
            $tenant = $user->tenant;

            DB::transaction(function () use ($user, $tenant): void {

                $doctor = DoctorProfile::query()
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $doctor->update([
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'specialty_id' => $this->specialty_id,
                    'professional_license' =>
                    $this->professional_license ?: null,
                    'phone' => $this->doctor_phone ?: null,
                    'whatsapp' => $this->doctor_whatsapp ?: null,
                ]);

                $practice = PracticeProfile::query()
                    ->where('tenant_id', $tenant->id)
                    ->firstOrFail();

                $practice->update([
                    'public_name' => $this->public_name,
                    'phone' => $this->practice_phone ?: null,
                    'whatsapp' => $this->practice_whatsapp ?: null,
                    'address_line_1' => $this->address_line_1 ?: null,
                    'address_line_2' => $this->address_line_2 ?: null,
                    'neighborhood' => $this->neighborhood ?: null,
                    'city' => $this->city ?: null,
                    'state' => $this->state ?: null,
                    'postal_code' => $this->postal_code ?: null,
                ]);

                Schedule::query()
                    ->where('doctor_profile_id', $doctor->id)
                    ->delete();

                foreach ($this->days as $dayOfWeek => $day) {
                    if (! $day['enabled']) {
                        continue;
                    }

                    Schedule::create([
                        'doctor_profile_id' => $doctor->id,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $day['start_time'],
                        'end_time' => $day['end_time'],
                        'appointment_duration' =>
                        $this->appointment_duration,
                        'buffer_before' => 0,
                        'buffer_after' => 0,
                        'active' => true,
                    ]);
                }

                $tenant->update([
                    'onboarding_completed_at' => now(),
                ]);
            });

            session()->flash(
                'success',
                '¡Tu consultorio está listo! Bienvenido a DocTotal.'
            );

            $this->redirectRoute('dashboard');
        }
    };
?>


<div class="min-h-screen bg-[#f6f8fc]">
    <div class="mx-auto flex min-h-screen max-w-7xl flex-col lg:flex-row">

        {{-- Panel lateral de marca --}}
        <aside class="relative overflow-hidden bg-slate-950 px-6 py-8 text-white lg:w-[320px] lg:px-8 lg:py-10">
            <div class="absolute -left-20 top-16 h-56 w-56 rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="absolute -bottom-20 right-0 h-64 w-64 rounded-full bg-violet-600/20 blur-3xl"></div>

            <div class="relative flex h-full flex-col">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-600/20">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-bold tracking-tight">DocTotal</p>
                            <p class="text-xs text-slate-400">Gestión médica inteligente</p>
                        </div>
                    </div>

                    <div class="mt-14">
                        <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-blue-200">
                            Configuración inicial
                        </span>

                        <h1 class="mt-5 max-w-sm text-3xl font-bold leading-tight tracking-tight">
                            Configura tu consultorio en pocos minutos.
                        </h1>

                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-400">
                            Completa tus datos profesionales, la información del consultorio y tus horarios de atención.
                        </p>
                    </div>
                </div>

                <div class="relative mt-10 lg:mt-auto">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">Tus datos quedan guardados</p>
                                <p class="mt-1 text-xs leading-5 text-slate-400">
                                    Podrás actualizar esta información más adelante desde Configuración.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Contenido principal --}}
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
            <div class="mx-auto max-w-4xl">

                {{-- Encabezado + progreso --}}
                <div class="mb-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-blue-600">Paso {{ $step }} de 4</p>
                            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">
                                @switch($step)
                                @case(1) Tu perfil profesional @break
                                @case(2) Tu consultorio @break
                                @case(3) Tu disponibilidad @break
                                @default Revisa y finaliza
                                @endswitch
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                @switch($step)
                                @case(1) Comencemos con tus datos como profesional de la salud. @break
                                @case(2) Ahora agrega los datos principales de tu consultorio. @break
                                @case(3) Define los días y horarios en que atiendes. @break
                                @default Confirma que todo esté correcto antes de comenzar.
                                @endswitch
                            </p>
                        </div>

                        <span class="text-sm font-medium text-slate-400">{{ $step * 25 }}%</span>
                    </div>

                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-blue-600 to-indigo-500 transition-all duration-300"
                            style="width: {{ $step * 25 }}%">
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-4 gap-2 text-center text-[11px] font-medium text-slate-400">
                        <span class="{{ $step >= 1 ? 'text-blue-600' : '' }}">Perfil</span>
                        <span class="{{ $step >= 2 ? 'text-blue-600' : '' }}">Consultorio</span>
                        <span class="{{ $step >= 3 ? 'text-blue-600' : '' }}">Horarios</span>
                        <span class="{{ $step >= 4 ? 'text-blue-600' : '' }}">Listo</span>
                    </div>
                </div>

                <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-[0_20px_55px_-32px_rgba(15,23,42,0.28)]">
                    <div class="p-5 sm:p-7 lg:p-8">

                        {{-- PASO 1 --}}
                        @if ($step === 1)
                        <div>
                            <div class="mb-6 flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19a6 6 0 0 0-12 0m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6 1h6m-3-3v6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-950">Datos profesionales</h3>
                                    <p class="mt-1 text-sm text-slate-500">Información básica del médico.</p>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="dt-label">Nombre</label>
                                    <input wire:model="first_name" type="text" class="dt-input">
                                    @error('first_name')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="dt-label">Apellido</label>
                                    <input wire:model="last_name" type="text" class="dt-input">
                                    @error('last_name')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="dt-label">Especialidad</label>
                                    <select wire:model="specialty_id" class="dt-select">
                                        <option value="">Selecciona una especialidad</option>
                                        @foreach ($this->specialties() as $specialty)
                                        <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('specialty_id')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
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
                            </div>
                        </div>
                        @endif

                        {{-- PASO 2 --}}
                        @if ($step === 2)
                        <div>
                            <div class="mb-6 flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 10h2m2 0h2M9 14h2m2 0h2" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-950">Datos del consultorio</h3>
                                    <p class="mt-1 text-sm text-slate-500">Esta información podrá mostrarse posteriormente en tu minisitio.</p>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="dt-label">Nombre público</label>
                                    <input wire:model="public_name" type="text" class="dt-input">
                                    @error('public_name')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="dt-label">Teléfono</label>
                                    <input wire:model="practice_phone" type="text" class="dt-input">
                                </div>

                                <div>
                                    <label class="dt-label">WhatsApp</label>
                                    <input wire:model="practice_whatsapp" type="text" class="dt-input">
                                </div>

                                <div>
                                    <label for="postal_code" class="dt-label">Código postal</label>
                                    <div class="relative">
                                        <input
                                            id="postal_code"
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

                                    @error('postal_code')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror

                                    @if ($postalCodeError)
                                    <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                        {{ $postalCodeError }} Puedes capturar la dirección manualmente.
                                    </div>
                                    @endif
                                </div>

                                <div>
                                    <label for="neighborhood" class="dt-label">Colonia</label>

                                    @if (count($neighborhoodOptions) > 0)
                                    <select id="neighborhood" wire:model="neighborhood" class="dt-select">
                                        <option value="">Selecciona una colonia</option>
                                        @foreach ($neighborhoodOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @else
                                    <input
                                        id="neighborhood"
                                        wire:model="neighborhood"
                                        type="text"
                                        placeholder="Colonia"
                                        class="dt-input">
                                    @endif

                                    @error('neighborhood')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
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
                                    <label class="dt-label">Dirección</label>
                                    <input wire:model="address_line_1" type="text" placeholder="Calle y número" class="dt-input">
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="dt-label">Interior / complemento</label>
                                    <input wire:model="address_line_2" type="text" placeholder="Interior, piso, edificio, etc." class="dt-input">
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- PASO 3 --}}
                        @if ($step === 3)
                        <div>
                            <div class="mb-6 flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v3m8-3v3M3 9h18M5 5h14a2 2 0 0 1 2 2v12H3V7a2 2 0 0 1 2-2Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-950">Horarios de atención</h3>
                                    <p class="mt-1 text-sm text-slate-500">Selecciona los días y horarios en que atiendes.</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach ($days as $dayNumber => $day)
                                <div
                                    wire:key="day-{{ $dayNumber }}"
                                    class="grid items-center gap-3 rounded-2xl border p-4 transition
                                                {{ $day['enabled']
                                                    ? 'border-blue-200 bg-blue-50/40'
                                                    : 'border-slate-200 bg-slate-50/50' }}
                                                sm:grid-cols-[180px_1fr_1fr]">

                                    <label class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            wire:model="days.{{ $dayNumber }}.enabled"
                                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                                        <span class="font-semibold {{ $day['enabled'] ? 'text-slate-900' : 'text-slate-500' }}">
                                            {{ $day['label'] }}
                                        </span>
                                    </label>

                                    <input
                                        type="time"
                                        wire:model="days.{{ $dayNumber }}.start_time"
                                        @disabled(! $day['enabled'])
                                        class="dt-input disabled:bg-slate-100 disabled:text-slate-400">

                                    <input
                                        type="time"
                                        wire:model="days.{{ $dayNumber }}.end_time"
                                        @disabled(! $day['enabled'])
                                        class="dt-input disabled:bg-slate-100 disabled:text-slate-400">
                                </div>
                                @endforeach
                            </div>

                            @error('days')
                            <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="dt-label">Duración de cada cita</label>
                                <select wire:model="appointment_duration" class="dt-select max-w-xs">
                                    <option value="15">15 minutos</option>
                                    <option value="20">20 minutos</option>
                                    <option value="30">30 minutos</option>
                                    <option value="45">45 minutos</option>
                                    <option value="60">60 minutos</option>
                                    <option value="90">90 minutos</option>
                                </select>
                            </div>
                        </div>
                        @endif

                        {{-- PASO 4 --}}
                        @if ($step === 4)
                        <div>
                            <div class="mb-7 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-2xl font-bold tracking-tight text-slate-950">Todo listo</h3>
                                <p class="mt-1 text-sm text-slate-500">Revisa tu información antes de finalizar.</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Médico</p>
                                    <p class="mt-2 font-semibold text-slate-900">{{ $first_name }} {{ $last_name }}</p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Consultorio</p>
                                    <p class="mt-2 font-semibold text-slate-900">{{ $public_name }}</p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:col-span-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dirección</p>
                                    <p class="mt-2 font-medium leading-6 text-slate-800">
                                        @if ($address_line_1) {{ $address_line_1 }} @endif
                                        @if ($neighborhood) · {{ $neighborhood }} @endif
                                        @if ($city) · {{ $city }} @endif
                                        @if ($state) · {{ $state }} @endif
                                        @if ($postal_code) · C.P. {{ $postal_code }} @endif
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 sm:col-span-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Duración de cita</p>
                                    <p class="mt-2 text-lg font-bold text-blue-950">{{ $appointment_duration }} minutos</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Navegación --}}
                    <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50/60 px-5 py-4 sm:px-7 lg:px-8">
                        <div>
                            @if ($step > 1)
                            <button
                                type="button"
                                wire:click="previousStep"
                                class="dt-btn dt-btn-secondary">
                                Anterior
                            </button>
                            @endif
                        </div>

                        <div>
                            @if ($step < 4)
                                <button
                                type="button"
                                wire:click="nextStep"
                                wire:loading.attr="disabled"
                                class="dt-btn dt-btn-primary disabled:opacity-50">
                                <span wire:loading.remove wire:target="nextStep">Continuar</span>
                                <span wire:loading wire:target="nextStep">Validando...</span>
                                </button>
                                @else
                                <button
                                    type="button"
                                    wire:click="finish"
                                    wire:loading.attr="disabled"
                                    class="dt-btn dt-btn-primary disabled:opacity-50">
                                    <span wire:loading.remove wire:target="finish">Finalizar configuración</span>
                                    <span wire:loading wire:target="finish">Guardando...</span>
                                </button>
                                @endif
                        </div>
                    </div>
                </section>

                <p class="mt-5 text-center text-xs text-slate-400">
                    DocTotal · Configuración inicial segura
                </p>
            </div>
        </main>
    </div>
</div>