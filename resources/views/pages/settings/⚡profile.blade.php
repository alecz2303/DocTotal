<?php

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Specialty;
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

        public string $address_line_1 = '';
        public string $address_line_2 = '';
        public string $neighborhood = '';
        public string $city = '';
        public string $state = '';
        public string $postal_code = '';
        public string $country = 'MX';

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

            $this->address_line_1 = $this->practice->address_line_1 ?? '';
            $this->address_line_2 = $this->practice->address_line_2 ?? '';
            $this->neighborhood = $this->practice->neighborhood ?? '';
            $this->city = $this->practice->city ?? '';
            $this->state = $this->practice->state ?? '';
            $this->postal_code = $this->practice->postal_code ?? '';
            $this->country = $this->practice->country ?? 'MX';

            $this->print_footer = $this->practice->print_footer ?? '';
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

                'photo' => [
                    'nullable',
                    'image',
                    'max:3072',
                ],

                'signature' => [
                    'nullable',
                    'image',
                    'max:3072',
                ],

                // Consultorio
                'public_name' => ['required', 'string', 'max:150'],
                'legal_name' => ['nullable', 'string', 'max:190'],
                'tax_id' => ['nullable', 'string', 'max:30'],
                'description' => ['nullable', 'string', 'max:5000'],

                'logo' => [
                    'nullable',
                    'image',
                    'max:3072',
                ],

                'practice_phone' => ['nullable', 'string', 'max:30'],
                'practice_whatsapp' => ['nullable', 'string', 'max:30'],
                'practice_email' => ['nullable', 'email', 'max:190'],

                'address_line_1' => ['nullable', 'string', 'max:255'],
                'address_line_2' => ['nullable', 'string', 'max:255'],
                'neighborhood' => ['nullable', 'string', 'max:150'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:15'],
                'country' => ['required', 'string', 'size:2'],

                'print_footer' => ['nullable', 'string', 'max:255'],
            ]);

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
                text: 'Los datos del perfil profesional y consultorio se actualizaron correctamente.',
                icon: 'success'
            );
        }

        public function specialties()
        {
            return Specialty::query()
                ->orderBy('name')
                ->get();
        }
    };
?>

<div class="mx-auto max-w-5xl">

    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            Configuración
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Administra tu perfil profesional y los datos de tu consultorio.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- PERFIL PROFESIONAL --}}
        <section
            class="rounded-xl border border-slate-200
                   bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Perfil profesional
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Estos datos aparecerán en recetas y documentos clínicos.
                </p>
            </div>

            <div class="grid gap-5 p-6 sm:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nombre *
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
                        Apellido paterno *
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

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Apellido materno
                    </label>

                    <input
                        wire:model="second_last_name"
                        type="text"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Especialidad
                    </label>

                    <select
                        wire:model="specialty_id"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                        <option value="">
                            Sin especialidad
                        </option>

                        @foreach ($this->specialties() as $specialty)
                        <option value="{{ $specialty->id }}">
                            {{ $specialty->name }}
                        </option>
                        @endforeach
                    </select>
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

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">
                        Biografía profesional
                    </label>

                    <textarea
                        wire:model="bio"
                        rows="4"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Fotografía
                    </label>

                    <input
                        wire:model="photo"
                        type="file"
                        accept="image/*"
                        class="block w-full text-sm">

                    @if ($doctor->photo_path)
                    <p class="mt-2 text-xs text-slate-500">
                        Ya existe una fotografía guardada.
                    </p>
                    @endif

                    @error('photo')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Firma
                    </label>

                    <input
                        wire:model="signature"
                        type="file"
                        accept="image/*"
                        class="block w-full text-sm">

                    @if ($doctor->signature_path)
                    <p class="mt-2 text-xs text-slate-500">
                        Ya existe una firma guardada.
                    </p>
                    @endif

                    @error('signature')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

            </div>
        </section>

        {{-- CONSULTORIO --}}
        <section
            class="rounded-xl border border-slate-200
                   bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Consultorio
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Información pública y de impresión del consultorio.
                </p>
            </div>

            <div class="grid gap-5 p-6 sm:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nombre público *
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
                        Razón social
                    </label>

                    <input
                        wire:model="legal_name"
                        type="text"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        RFC
                    </label>

                    <input
                        wire:model="tax_id"
                        type="text"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Logo
                    </label>

                    <input
                        wire:model="logo"
                        type="file"
                        accept="image/*"
                        class="block w-full text-sm">

                    @if ($practice->logo_path)
                    <p class="mt-2 text-xs text-slate-500">
                        Ya existe un logo guardado.
                    </p>
                    @endif

                    @error('logo')
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

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">
                        Correo
                    </label>

                    <input
                        wire:model="practice_email"
                        type="email"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">
                        Descripción
                    </label>

                    <textarea
                        wire:model="description"
                        rows="3"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2"></textarea>
                </div>

            </div>
        </section>

        {{-- DIRECCIÓN --}}
        <section
            class="rounded-xl border border-slate-200
                   bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Dirección
                </h2>
            </div>

            <div class="grid gap-5 p-6 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">
                        Calle y número
                    </label>

                    <input
                        wire:model="address_line_1"
                        type="text"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">
                        Interior / referencia
                    </label>

                    <input
                        wire:model="address_line_2"
                        type="text"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Colonia
                    </label>

                    <input
                        wire:model="neighborhood"
                        type="text"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Código postal
                    </label>

                    <input
                        wire:model="postal_code"
                        type="text"
                        maxlength="5"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Ciudad
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

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        País
                    </label>

                    <input
                        wire:model="country"
                        type="text"
                        maxlength="2"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

            </div>
        </section>

        {{-- IMPRESIÓN --}}
        <section
            class="rounded-xl border border-slate-200
                   bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Documentos impresos
                </h2>
            </div>

            <div class="p-6">

                <label class="mb-1 block text-sm font-medium">
                    Pie de página
                </label>

                <input
                    wire:model="print_footer"
                    type="text"
                    placeholder="Ej. Citas: 961 000 0000"
                    class="w-full rounded-lg border
                           border-slate-300 px-3 py-2">

                <p class="mt-2 text-xs text-slate-500">
                    Aparecerá en recetas y otros documentos impresos.
                </p>

            </div>
        </section>

        <div class="flex justify-end">

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg bg-slate-900 px-5 py-2.5
                       text-sm font-semibold text-white
                       hover:bg-slate-800 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">
                    Guardar configuración
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </button>

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