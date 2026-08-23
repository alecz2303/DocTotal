<?php

use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Cita | DocTotal')]
    class extends Component
    {
        public Appointment $appointment;

        public function mount(string $uuid): void
        {
            $this->appointment = Appointment::query()
                ->with([
                    'patient',
                    'doctorProfile',
                ])
                ->where('uuid', $uuid)
                ->firstOrFail();
        }
    };
?>

<div class="mx-auto max-w-5xl">

    <a
        href="{{ route('appointments.index') }}"
        class="text-sm font-medium text-slate-500 hover:text-slate-900">
        ← Volver a agenda
    </a>

    <div
        class="mt-4 rounded-xl border
               border-slate-200 bg-white p-6 shadow-sm">

        <h1 class="text-2xl font-bold text-slate-900">
            Cita programada
        </h1>

        <p class="mt-3 text-slate-700">
            {{ $appointment->patient->first_name }}
            {{ $appointment->patient->last_name }}
            {{ $appointment->patient->second_last_name }}
        </p>

        <p class="mt-2 text-sm text-slate-500">
            {{ $appointment->starts_at->format('d/m/Y H:i') }}
            —
            {{ $appointment->ends_at->format('H:i') }}
        </p>

        @if ($appointment->reason)
        <p class="mt-4 text-sm text-slate-700">
            {{ $appointment->reason }}
        </p>
        @endif

    </div>

</div>