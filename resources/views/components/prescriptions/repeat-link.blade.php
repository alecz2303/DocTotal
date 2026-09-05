@props(['prescription', 'patientId', 'context', 'label' => 'Repetir receta'])

@if ($prescription->status === 'active'
    && ! $prescription->trashed()
    && (int) $prescription->patient_id === (int) $patientId
    && (int) $prescription->tenant_id === (int) app(\App\Support\TenantContext::class)->id()
    && auth()->user()?->doctorProfile)
<a href="{{ route('prescriptions.repeat', ['uuid' => $prescription->uuid]) }}"
    data-repeat-prescription="{{ $prescription->uuid }}"
    data-repeat-context="{{ $context }}"
    title="Abrir una copia editable de la receta completa, sin modificar el historial original"
    {{ $attributes->class(['inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-700']) }}>
    {{ $label }}
</a>
@endif
