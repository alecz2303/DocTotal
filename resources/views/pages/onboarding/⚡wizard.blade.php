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

<div class="min-h-screen bg-slate-50 px-4 py-10">

    <div class="mx-auto max-w-4xl">

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                Configura tu consultorio
            </h1>

            <p class="mt-2 text-slate-500">
                Te tomará solo unos minutos.
            </p>
        </div>

        {{-- Progreso --}}
        <div class="mb-8 grid grid-cols-4 gap-2">

            @foreach ([1, 2, 3, 4] as $number)

            <div>
                <div
                    class="h-2 rounded-full
                            {{ $step >= $number
                                ? 'bg-slate-900'
                                : 'bg-slate-200' }}"></div>

                <p class="mt-2 text-center text-xs text-slate-500">
                    Paso {{ $number }}
                </p>
            </div>

            @endforeach

        </div>

        <div
            class="rounded-2xl border border-slate-200
                   bg-white p-6 shadow-sm sm:p-8">

            {{-- PASO 1 --}}
            @if ($step === 1)

            <div>

                <h2 class="text-xl font-semibold">
                    Datos profesionales
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Información básica del médico.
                </p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Nombre
                        </label>

                        <input
                            wire:model="first_name"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">

                        @error('first_name')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Apellido
                        </label>

                        <input
                            wire:model="last_name"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">

                        @error('last_name')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">

                        <label class="mb-1 block text-sm font-medium">
                            Especialidad
                        </label>

                        <select
                            wire:model="specialty_id"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                            <option value="">
                                Selecciona una especialidad
                            </option>

                            @foreach ($this->specialties() as $specialty)
                            <option value="{{ $specialty->id }}">
                                {{ $specialty->name }}
                            </option>
                            @endforeach
                        </select>

                        @error('specialty_id')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Cédula profesional
                        </label>

                        <input
                            wire:model="professional_license"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Teléfono
                        </label>

                        <input
                            wire:model="doctor_phone"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            WhatsApp
                        </label>

                        <input
                            wire:model="doctor_whatsapp"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                </div>

            </div>

            @endif

            {{-- PASO 2 --}}
            @if ($step === 2)

            <div>

                <h2 class="text-xl font-semibold">
                    Datos del consultorio
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Esta información podrá mostrarse posteriormente
                    en tu minisitio.
                </p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">
                            Nombre público
                        </label>

                        <input
                            wire:model="public_name"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">

                        @error('public_name')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Teléfono
                        </label>

                        <input
                            wire:model="practice_phone"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            WhatsApp
                        </label>

                        <input
                            wire:model="practice_whatsapp"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                    {{-- CP primero para autocompletar --}}
                    <div>
                        <label
                            for="postal_code"
                            class="mb-1 block text-sm font-medium">
                            Código postal
                        </label>

                        <div class="relative">

                            <input
                                id="postal_code"
                                wire:model.live.debounce.500ms="postal_code"
                                type="text"
                                inputmode="numeric"
                                maxlength="5"
                                placeholder="Ej. 29025"
                                class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">

                            <div
                                wire:loading
                                wire:target="postal_code"
                                class="absolute right-3 top-2.5
                                           text-xs text-slate-400">
                                Buscando...
                            </div>

                        </div>

                        @error('postal_code')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                        @if ($postalCodeError)
                        <p class="mt-1 text-sm text-amber-600">
                            {{ $postalCodeError }}
                            Puedes capturar la dirección manualmente.
                        </p>
                        @endif
                    </div>

                    {{-- Colonia dinámica --}}
                    <div>
                        <label
                            for="neighborhood"
                            class="mb-1 block text-sm font-medium">
                            Colonia
                        </label>

                        @if (count($neighborhoodOptions) > 0)

                        <select
                            id="neighborhood"
                            wire:model="neighborhood"
                            class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">
                            <option value="">
                                Selecciona una colonia
                            </option>

                            @foreach ($neighborhoodOptions as $option)
                            <option value="{{ $option }}">
                                {{ $option }}
                            </option>
                            @endforeach
                        </select>

                        @else

                        <input
                            id="neighborhood"
                            wire:model="neighborhood"
                            type="text"
                            placeholder="Colonia"
                            class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">

                        @endif

                        @error('neighborhood')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Ciudad / Municipio
                        </label>

                        <input
                            wire:model="city"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Estado
                        </label>

                        <input
                            wire:model="state"
                            type="text"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">
                            Dirección
                        </label>

                        <input
                            wire:model="address_line_1"
                            type="text"
                            placeholder="Calle y número"
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">
                            Interior / complemento
                        </label>

                        <input
                            wire:model="address_line_2"
                            type="text"
                            placeholder="Interior, piso, edificio, etc."
                            class="w-full rounded-lg border
                                       border-slate-300 px-3 py-2">
                    </div>

                </div>

            </div>

            @endif

            {{-- PASO 3 --}}
            @if ($step === 3)

            <div>

                <h2 class="text-xl font-semibold">
                    Horarios de atención
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Selecciona los días y horarios en que atiendes.
                </p>

                <div class="mt-6 space-y-3">

                    @foreach ($days as $dayNumber => $day)

                    <div
                        wire:key="day-{{ $dayNumber }}"
                        class="grid items-center gap-3 rounded-xl
                                       border border-slate-200 p-4
                                       sm:grid-cols-[160px_1fr_1fr]">

                        <label class="flex items-center gap-3">

                            <input
                                type="checkbox"
                                wire:model="days.{{ $dayNumber }}.enabled">

                            <span class="font-medium">
                                {{ $day['label'] }}
                            </span>

                        </label>

                        <input
                            type="time"
                            wire:model="days.{{ $dayNumber }}.start_time"
                            @disabled(! $day['enabled'])
                            class="rounded-lg border border-slate-300
                                           px-3 py-2 disabled:bg-slate-100">

                        <input
                            type="time"
                            wire:model="days.{{ $dayNumber }}.end_time"
                            @disabled(! $day['enabled'])
                            class="rounded-lg border border-slate-300
                                           px-3 py-2 disabled:bg-slate-100">

                    </div>

                    @endforeach

                </div>

                @error('days')
                <p class="mt-3 text-sm text-red-600">
                    {{ $message }}
                </p>
                @enderror

                <div class="mt-6">

                    <label class="mb-1 block text-sm font-medium">
                        Duración de cada cita
                    </label>

                    <select
                        wire:model="appointment_duration"
                        class="w-full max-w-xs rounded-lg border
                                   border-slate-300 px-3 py-2">
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

                <h2 class="text-xl font-semibold">
                    Todo listo
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Revisa tu información antes de finalizar.
                </p>

                <div class="mt-6 space-y-4">

                    <div class="rounded-xl bg-slate-50 p-4">

                        <p class="text-sm text-slate-500">
                            Médico
                        </p>

                        <p class="mt-1 font-medium">
                            {{ $first_name }} {{ $last_name }}
                        </p>

                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">

                        <p class="text-sm text-slate-500">
                            Consultorio
                        </p>

                        <p class="mt-1 font-medium">
                            {{ $public_name }}
                        </p>

                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">

                        <p class="text-sm text-slate-500">
                            Dirección
                        </p>

                        <p class="mt-1 font-medium">
                            @if ($address_line_1)
                            {{ $address_line_1 }}
                            @endif

                            @if ($neighborhood)
                            · {{ $neighborhood }}
                            @endif

                            @if ($city)
                            · {{ $city }}
                            @endif

                            @if ($state)
                            · {{ $state }}
                            @endif

                            @if ($postal_code)
                            · C.P. {{ $postal_code }}
                            @endif
                        </p>

                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">

                        <p class="text-sm text-slate-500">
                            Duración de cita
                        </p>

                        <p class="mt-1 font-medium">
                            {{ $appointment_duration }} minutos
                        </p>

                    </div>

                </div>

            </div>

            @endif

            {{-- Navegación --}}
            <div
                class="mt-8 flex items-center justify-between
                       border-t border-slate-200 pt-6">

                <div>
                    @if ($step > 1)

                    <button
                        type="button"
                        wire:click="previousStep"
                        class="rounded-lg border border-slate-300
                                   px-4 py-2 text-sm font-medium">
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
                        class="rounded-lg bg-slate-900 px-5 py-2.5
                                   text-sm font-semibold text-white
                                   disabled:opacity-50">
                        Continuar
                        </button>

                        @else

                        <button
                            type="button"
                            wire:click="finish"
                            wire:loading.attr="disabled"
                            class="rounded-lg bg-slate-900 px-5 py-2.5
                                   text-sm font-semibold text-white
                                   disabled:opacity-50">

                            <span
                                wire:loading.remove
                                wire:target="finish">
                                Finalizar configuración
                            </span>

                            <span
                                wire:loading
                                wire:target="finish">
                                Guardando...
                            </span>

                        </button>

                        @endif

                </div>

            </div>

        </div>

    </div>

</div>