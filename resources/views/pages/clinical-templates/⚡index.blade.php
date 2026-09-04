<?php

use App\Models\ClinicalTemplate;
use App\Services\AuditLogger;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Plantillas clínicas | DocTotal')]
    class extends Component
    {
        public bool $showForm = false;
        public ?int $editingId = null;

        public string $name = '';
        public string $description = '';
        public string $reason = '';
        public string $subjective = '';
        public string $objective = '';
        public string $assessment = '';
        public string $plan = '';
        public bool $active = true;

        #[Computed]
        public function templates()
        {
            return ClinicalTemplate::query()
                ->orderByDesc('active')
                ->orderBy('name')
                ->get();
        }

        public function createTemplate(): void
        {
            $this->resetForm();
            $this->showForm = true;
        }

        public function editTemplate(int $templateId): void
        {
            $template = ClinicalTemplate::query()->findOrFail($templateId);
            $content = $template->content ?? [];

            $this->editingId = $template->id;
            $this->name = $template->name;
            $this->description = $template->description ?? '';
            $this->reason = $content['reason'] ?? '';
            $this->subjective = $content['subjective'] ?? '';
            $this->objective = $content['objective'] ?? '';
            $this->assessment = $content['assessment'] ?? '';
            $this->plan = $content['plan'] ?? '';
            $this->active = $template->active;
            $this->resetValidation();
            $this->showForm = true;
        }

        public function cancelForm(): void
        {
            $this->resetForm();
        }

        public function saveTemplate(): void
        {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:2000'],
                'reason' => ['nullable', 'string', 'max:500'],
                'subjective' => ['nullable', 'string', 'max:10000'],
                'objective' => ['nullable', 'string', 'max:10000'],
                'assessment' => ['nullable', 'string', 'max:10000'],
                'plan' => ['nullable', 'string', 'max:10000'],
                'active' => ['boolean'],
            ]);

            $content = [
                'reason' => $this->nullableString($validated['reason']),
                'subjective' => $this->nullableString($validated['subjective']),
                'objective' => $this->nullableString($validated['objective']),
                'assessment' => $this->nullableString($validated['assessment']),
                'plan' => $this->nullableString($validated['plan']),
            ];

            if ($this->editingId) {
                $template = ClinicalTemplate::query()->findOrFail($this->editingId);
                $template->update([
                    'name' => trim($validated['name']),
                    'description' => $this->nullableString($validated['description']),
                    'content' => $content,
                    'active' => $validated['active'],
                ]);

                app(AuditLogger::class)->safeLog(
                    action: 'clinical_template.updated',
                    auditable: $template,
                    description: 'Plantilla clínica actualizada.',
                    metadata: ['template_name' => $template->name],
                );
                $message = 'Plantilla clínica actualizada correctamente.';
            } else {
                $template = ClinicalTemplate::create([
                    'name' => trim($validated['name']),
                    'description' => $this->nullableString($validated['description']),
                    'content' => $content,
                    'active' => $validated['active'],
                ]);

                app(AuditLogger::class)->safeLog(
                    action: 'clinical_template.created',
                    auditable: $template,
                    description: 'Plantilla clínica creada.',
                    metadata: ['template_name' => $template->name],
                );
                $message = 'Plantilla clínica creada correctamente.';
            }

            unset($this->templates);
            $this->resetForm();
            session()->flash('success', $message);
        }

        public function toggleTemplate(int $templateId): void
        {
            $template = ClinicalTemplate::query()->findOrFail($templateId);
            $template->update(['active' => ! $template->active]);

            app(AuditLogger::class)->safeLog(
                action: $template->active ? 'clinical_template.activated' : 'clinical_template.deactivated',
                auditable: $template,
                description: $template->active
                    ? 'Plantilla clínica activada.'
                    : 'Plantilla clínica desactivada.',
                metadata: ['template_name' => $template->name],
            );

            unset($this->templates);
        }

        public function deleteTemplate(int $templateId): void
        {
            $template = ClinicalTemplate::query()->findOrFail($templateId);

            if (! $template->canDelete()) {
                $this->addError(
                    'deleteTemplate',
                    'Esta plantilla ya fue utilizada y no puede eliminarse. Puedes desactivarla.'
                );
                return;
            }

            app(AuditLogger::class)->safeLog(
                action: 'clinical_template.deleted',
                auditable: $template,
                description: 'Plantilla clínica eliminada.',
                metadata: ['template_name' => $template->name],
            );

            $template->delete();
            unset($this->templates);
            session()->flash('success', 'Plantilla clínica eliminada correctamente.');
        }

        private function resetForm(): void
        {
            $this->reset([
                'showForm', 'editingId', 'name', 'description', 'reason',
                'subjective', 'objective', 'assessment', 'plan',
            ]);
            $this->active = true;
            $this->resetValidation();
        }

        private function nullableString(?string $value): ?string
        {
            $value = trim((string) $value);
            return $value === '' ? null : $value;
        }
    };
?>

<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">Configuración clínica</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Plantillas clínicas</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                Guarda estructuras SOAP reutilizables para agilizar la captura de tus consultas.
            </p>
        </div>

        <button wire:click="createTemplate" type="button" class="dt-btn dt-btn-primary">
            Nueva plantilla
        </button>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @error('deleteTemplate')
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
            {{ $message }}
        </div>
    @enderror

    @if ($showForm)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-950">
                    {{ $editingId ? 'Editar plantilla' : 'Nueva plantilla' }}
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    El contenido se copiará a la consulta cuando la plantilla sea aplicada.
                </p>
            </div>

            <form wire:submit="saveTemplate" class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dt-label">Nombre *</label>
                        <input wire:model="name" type="text" class="dt-input" placeholder="Ej. Consulta de seguimiento">
                        @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="dt-label">Descripción</label>
                        <input wire:model="description" type="text" class="dt-input" placeholder="Uso recomendado de la plantilla">
                        @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="dt-label">Motivo de consulta sugerido</label>
                    <input wire:model="reason" type="text" class="dt-input" placeholder="Opcional">
                    @error('reason') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['subjective', 'S · Subjetivo'],
                        ['objective', 'O · Objetivo'],
                        ['assessment', 'A · Evaluación / diagnóstico'],
                        ['plan', 'P · Plan'],
                    ] as [$model, $label])
                        <div>
                            <label class="dt-label">{{ $label }}</label>
                            <textarea wire:model="{{ $model }}" rows="5" class="dt-textarea"></textarea>
                            @error($model) <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <input wire:model="active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Plantilla activa</span>
                        <span class="block text-xs text-slate-500">Las plantillas inactivas no aparecen al capturar una consulta.</span>
                    </span>
                </label>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
                    <button wire:click="cancelForm" type="button" class="dt-btn dt-btn-secondary">Cancelar</button>
                    <button type="submit" class="dt-btn dt-btn-primary">Guardar plantilla</button>
                </div>
            </form>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-slate-950">Tus plantillas</h2>
            <p class="mt-1 text-xs text-slate-500">Cada plantilla pertenece exclusivamente a este consultorio.</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($this->templates as $template)
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-slate-900">{{ $template->name }}</h3>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-bold',
                                'bg-emerald-50 text-emerald-700' => $template->active,
                                'bg-slate-100 text-slate-500' => ! $template->active,
                            ])>{{ $template->active ? 'Activa' : 'Inactiva' }}</span>
                        </div>
                        @if ($template->description)
                            <p class="mt-1 text-sm text-slate-500">{{ $template->description }}</p>
                        @endif
                        <p class="mt-2 text-xs text-slate-400">
                            Usada {{ $template->usage_count }} {{ $template->usage_count === 1 ? 'vez' : 'veces' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button wire:click="editTemplate({{ $template->id }})" type="button" class="dt-btn dt-btn-secondary">Editar</button>
                        <button wire:click="toggleTemplate({{ $template->id }})" type="button" class="dt-btn dt-btn-secondary">
                            {{ $template->active ? 'Desactivar' : 'Activar' }}
                        </button>
                        @if ($template->canDelete())
                            <button
                                type="button"
                                x-data
                                x-on:click="
                                    Swal.fire({
                                        title: '¿Eliminar plantilla clínica?',
                                        text: 'Esta acción no se puede deshacer.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Sí, eliminar',
                                        cancelButtonText: 'Cancelar',
                                        reverseButtons: true,
                                        focusCancel: true
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            $wire.deleteTemplate({{ $template->id }})
                                        }
                                    })
                                "
                                class="dt-btn inline-flex items-center justify-center border border-rose-200 bg-white text-rose-700 hover:bg-rose-50">
                                Eliminar
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <p class="font-semibold text-slate-700">Aún no tienes plantillas clínicas.</p>
                    <p class="mt-1 text-sm text-slate-500">Crea la primera para reutilizarla en tus consultas.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
