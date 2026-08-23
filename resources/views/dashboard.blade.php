<x-layouts.app>

    <div class="mx-auto max-w-7xl">

        {{-- HEADER --}}
        <div
            class="mb-8 flex flex-col gap-4
                   lg:flex-row lg:items-end lg:justify-between">

            <div>

                <h1
                    class="text-2xl font-bold
                           tracking-tight text-slate-900">
                    Bienvenido, {{ auth()->user()->name }}
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Aquí tienes el resumen de tu consultorio para hoy.
                </p>

            </div>

            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('appointments.index') }}"
                    class="inline-flex items-center
                           rounded-lg
                           border border-slate-300
                           bg-white px-4 py-2.5
                           text-sm font-semibold
                           text-slate-700
                           hover:bg-slate-50">
                    Ver agenda
                </a>

                <a
                    href="{{ route('appointments.create') }}"
                    class="inline-flex items-center
                           rounded-lg
                           bg-slate-900
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-slate-800">
                    + Nueva cita
                </a>

            </div>

        </div>


        {{-- INDICADORES PRINCIPALES --}}
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            {{-- CITAS HOY --}}
            <a
                href="{{ route('appointments.index') }}"
                class="group rounded-xl
                       border border-slate-200
                       bg-white p-5
                       shadow-sm
                       transition
                       hover:-translate-y-0.5
                       hover:shadow-md">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Citas de hoy
                        </p>

                        @if ($isDayOff)

                        <p
                            class="mt-2 text-xl
                                       font-bold text-slate-900">
                            Día libre
                        </p>

                        <p class="mt-2 text-xs text-slate-500">
                            {{ $dayOffReason }}
                        </p>

                        @else

                        <p
                            class="mt-2 text-3xl
                                       font-bold text-slate-900">
                            {{ $appointmentsTodayCount }}
                        </p>

                        <p class="mt-4 text-xs text-slate-500">
                            {{ $completedAppointmentsCount }}
                            atendidas
                        </p>

                        @endif

                    </div>

                    <div
                        class="rounded-lg
                               bg-blue-50
                               px-3 py-2
                               text-sm font-bold
                               text-blue-700">
                        Hoy
                    </div>

                </div>

            </a>


            {{-- PACIENTES --}}
            <a
                href="{{ route('patients.index') }}"
                class="group rounded-xl
                       border border-slate-200
                       bg-white p-5
                       shadow-sm transition
                       hover:-translate-y-0.5
                       hover:shadow-md">

                <p class="text-sm font-medium text-slate-500">
                    Pacientes
                </p>

                <p
                    class="mt-2 text-3xl
                           font-bold text-slate-900">
                    {{ $patientsCount }}
                </p>

                <p class="mt-4 text-xs text-slate-500">
                    Expedientes registrados
                </p>

            </a>


            {{-- POR ATENDER --}}
            <div
                class="rounded-xl
                       border border-slate-200
                       bg-white p-5
                       shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Por atender
                </p>

                @if ($isDayOff)

                <p
                    class="mt-2 text-xl
                               font-bold text-slate-900">
                    —
                </p>

                <p class="mt-4 text-xs text-slate-500">
                    Sin horario de atención hoy
                </p>

                @else

                <p
                    class="mt-2 text-3xl
                               font-bold text-slate-900">
                    {{ $pendingAppointmentsCount }}
                </p>

                <p class="mt-4 text-xs text-slate-500">
                    Programadas, confirmadas o en espera
                </p>

                @endif

            </div>


            {{-- PRÓXIMA CITA --}}
            <div
                class="rounded-xl
                       border border-slate-200
                       bg-white p-5
                       shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Próxima cita
                </p>

                @if ($nextAppointment)

                <p
                    class="mt-2 text-xl
                               font-bold text-slate-900">
                    {{ $nextAppointment->starts_at->format('H:i') }}
                </p>

                <p
                    class="mt-1 text-sm
                               font-semibold text-slate-700">
                    {{ ucfirst(
                            $nextAppointment
                                ->starts_at
                                ->locale('es')
                                ->translatedFormat('D d M Y')
                        ) }}
                </p>

                <p
                    class="mt-2 truncate
                               text-xs text-slate-500">
                    {{ $nextAppointment->patient->first_name }}
                    {{ $nextAppointment->patient->last_name }}
                </p>

                <a
                    href="{{ route(
                            'appointments.show',
                            [
                                'uuid' =>
                                    $nextAppointment->uuid
                            ]
                        ) }}"
                    class="mt-3 inline-flex
                               text-xs font-semibold
                               text-slate-700
                               hover:text-slate-950">
                    Ver cita →
                </a>

                @else

                <p
                    class="mt-3 text-lg
                               font-semibold
                               text-slate-900">
                    Sin citas
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    No hay citas próximas.
                </p>

                @endif

            </div>

        </div>


        {{-- SEGUNDA FILA --}}
        <div
            class="mt-8 grid gap-6
                   xl:grid-cols-[1.7fr_1fr]">

            {{-- AGENDA DE HOY --}}
            <section
                class="overflow-hidden
                       rounded-xl
                       border border-slate-200
                       bg-white shadow-sm">

                <div
                    class="flex items-center
                           justify-between
                           border-b border-slate-200
                           px-6 py-4">

                    <div>

                        <h2 class="font-semibold text-slate-900">
                            Agenda de hoy
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ ucfirst(
                                now()
                                    ->locale('es')
                                    ->translatedFormat(
                                        'l d \d\e F \d\e Y'
                                    )
                            ) }}
                        </p>

                    </div>

                    <a
                        href="{{ route('appointments.index') }}"
                        class="text-sm font-semibold
                               text-slate-600
                               hover:text-slate-900">
                        Ver completa
                    </a>

                </div>


                {{-- DÍA LIBRE / EXCEPCIÓN COMPLETA --}}
                @if ($isDayOff)

                <div class="px-6 py-14 text-center">

                    <div
                        class="mx-auto flex h-12 w-12
                                   items-center justify-center
                                   rounded-full
                                   bg-slate-100
                                   text-lg">
                        ☕
                    </div>

                    <p
                        class="mt-4 font-semibold
                                   text-slate-900">
                        Hoy no hay horario de atención
                    </p>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        {{ $dayOffReason }}
                    </p>

                    @if (
                    $todayException
                    && $todayException->isNotEmpty()
                    )

                    <div
                        class="mx-auto mt-5
                                       max-w-md
                                       rounded-lg
                                       border border-slate-200
                                       bg-slate-50
                                       px-4 py-3">

                        @foreach (
                        $todayException
                        as $exception
                        )

                        @if (
                        $exception->type === 'blocked'
                        && ! $exception->start_time
                        && ! $exception->end_time
                        )

                        <p
                            class="text-sm
                                                   font-medium
                                                   text-slate-700">
                            {{ $exception->reason
                                                ?: 'Día bloqueado' }}
                        </p>

                        @endif

                        @endforeach

                    </div>

                    @endif

                </div>

                @elseif ($appointmentsToday->isEmpty())

                <div class="px-6 py-16 text-center">

                    <p
                        class="font-medium
                                   text-slate-700">
                        No tienes citas programadas para hoy.
                    </p>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        Las citas aparecerán aquí.
                    </p>

                    @if (
                    $todaySchedule
                    && $todaySchedule->isNotEmpty()
                    )

                    <div
                        class="mt-4
                                       flex flex-wrap
                                       justify-center gap-2">

                        @foreach (
                        $todaySchedule
                        as $schedule
                        )

                        <span
                            class="rounded-full
                                               bg-slate-100
                                               px-3 py-1
                                               text-xs
                                               font-medium
                                               text-slate-600">
                            {{ \Illuminate\Support\Carbon::parse(
                                            $schedule->start_time
                                        )->format('H:i') }}

                            –

                            {{ \Illuminate\Support\Carbon::parse(
                                            $schedule->end_time
                                        )->format('H:i') }}
                        </span>

                        @endforeach

                    </div>

                    @endif

                    <a
                        href="{{ route('appointments.create') }}"
                        class="mt-5 inline-flex
                                   rounded-lg
                                   bg-slate-900
                                   px-4 py-2.5
                                   text-sm font-semibold
                                   text-white
                                   hover:bg-slate-800">
                        + Nueva cita
                    </a>

                </div>

                @else

                <div class="divide-y divide-slate-100">

                    @foreach (
                    $appointmentsToday
                    as $appointment
                    )

                    @php
                    $labels = [
                    'scheduled' => 'Programada',
                    'confirmed' => 'Confirmada',
                    'checked_in' => 'Paciente llegó',
                    'in_progress' => 'En atención',
                    'completed' => 'Completada',
                    'cancelled' => 'Cancelada',
                    'no_show' => 'No se presentó',
                    ];

                    $classes = [
                    'scheduled' =>
                    'bg-blue-50 text-blue-700',

                    'confirmed' =>
                    'bg-indigo-50 text-indigo-700',

                    'checked_in' =>
                    'bg-amber-50 text-amber-700',

                    'in_progress' =>
                    'bg-orange-50 text-orange-700',

                    'completed' =>
                    'bg-green-50 text-green-700',

                    'cancelled' =>
                    'bg-red-50 text-red-700',

                    'no_show' =>
                    'bg-slate-100 text-slate-700',
                    ];

                    $label =
                    $labels[$appointment->status]
                    ?? $appointment->status;

                    $class =
                    $classes[$appointment->status]
                    ?? 'bg-slate-100 text-slate-700';
                    @endphp

                    <a
                        href="{{ route(
                                    'appointments.show',
                                    [
                                        'uuid' =>
                                            $appointment->uuid
                                    ]
                                ) }}"
                        class="grid gap-4
                                       px-6 py-4
                                       transition
                                       hover:bg-slate-50
                                       sm:grid-cols-[90px_1fr_auto]
                                       sm:items-center">

                        <div>

                            <p
                                class="text-lg
                                               font-bold
                                               text-slate-900">
                                {{ $appointment->starts_at->format('H:i') }}
                            </p>

                            <p
                                class="text-xs
                                               text-slate-500">
                                {{ $appointment->ends_at->format('H:i') }}
                            </p>

                        </div>

                        <div>

                            <p
                                class="font-semibold
                                               text-slate-900">
                                {{ $appointment->patient->first_name }}
                                {{ $appointment->patient->last_name }}
                                {{ $appointment->patient->second_last_name }}
                            </p>

                            <p
                                class="mt-1 text-sm
                                               text-slate-500">
                                {{ $appointment->reason
                                            ?: 'Sin motivo registrado' }}
                            </p>

                        </div>

                        <span
                            class="inline-flex
                                           rounded-full
                                           px-2.5 py-1
                                           text-xs
                                           font-semibold
                                           {{ $class }}">
                            {{ $label }}
                        </span>

                    </a>

                    @endforeach

                </div>

                @endif


                {{-- BLOQUEOS PARCIALES / DISPONIBILIDAD EXTRA --}}
                @if (
                ! $isDayOff
                && $todayException
                && $todayException->isNotEmpty()
                )

                <div
                    class="border-t
                               border-slate-200
                               bg-slate-50
                               px-6 py-4">

                    <p
                        class="text-xs font-semibold
                                   uppercase tracking-wide
                                   text-slate-500">
                        Excepciones de hoy
                    </p>

                    <div class="mt-3 space-y-2">

                        @foreach (
                        $todayException
                        as $exception
                        )

                        @if (
                        $exception->type === 'blocked'
                        && $exception->start_time
                        && $exception->end_time
                        )

                        <div
                            class="flex flex-wrap
                                               items-center
                                               justify-between
                                               gap-2
                                               rounded-lg
                                               border border-red-100
                                               bg-red-50
                                               px-3 py-2">

                            <div>

                                <p
                                    class="text-sm
                                                       font-semibold
                                                       text-red-700">
                                    Horario bloqueado
                                </p>

                                <p
                                    class="text-xs
                                                       text-red-600">
                                    {{ \Illuminate\Support\Carbon::parse(
                                                    $exception->start_time
                                                )->format('H:i') }}

                                    –

                                    {{ \Illuminate\Support\Carbon::parse(
                                                    $exception->end_time
                                                )->format('H:i') }}
                                </p>

                            </div>

                            @if ($exception->reason)

                            <span
                                class="text-xs
                                                       text-red-600">
                                {{ $exception->reason }}
                            </span>

                            @endif

                        </div>

                        @elseif (
                        $exception->type === 'available'
                        && $exception->start_time
                        && $exception->end_time
                        )

                        <div
                            class="flex flex-wrap
                                               items-center
                                               justify-between
                                               gap-2
                                               rounded-lg
                                               border border-green-100
                                               bg-green-50
                                               px-3 py-2">

                            <div>

                                <p
                                    class="text-sm
                                                       font-semibold
                                                       text-green-700">
                                    Horario extraordinario
                                </p>

                                <p
                                    class="text-xs
                                                       text-green-600">
                                    {{ \Illuminate\Support\Carbon::parse(
                                                    $exception->start_time
                                                )->format('H:i') }}

                                    –

                                    {{ \Illuminate\Support\Carbon::parse(
                                                    $exception->end_time
                                                )->format('H:i') }}
                                </p>

                            </div>

                            @if ($exception->reason)

                            <span
                                class="text-xs
                                                       text-green-600">
                                {{ $exception->reason }}
                            </span>

                            @endif

                        </div>

                        @endif

                        @endforeach

                    </div>

                </div>

                @endif

            </section>


            {{-- ACTIVIDAD DE HOY --}}
            <section
                class="rounded-xl
                       border border-slate-200
                       bg-white shadow-sm">

                <div
                    class="border-b
                           border-slate-200
                           px-6 py-4">

                    <h2 class="font-semibold text-slate-900">
                        Actividad de hoy
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Resumen clínico del día.
                    </p>

                </div>

                <div class="space-y-5 p-6">

                    <a
                        href="{{ route('consultations.index') }}"
                        class="flex items-center
                               justify-between
                               rounded-lg
                               border border-slate-200
                               p-4
                               hover:bg-slate-50">

                        <div>

                            <p
                                class="text-sm
                                       font-medium
                                       text-slate-500">
                                Consultas
                            </p>

                            <p
                                class="mt-1 text-2xl
                                       font-bold
                                       text-slate-900">
                                {{ $consultationsTodayCount }}
                            </p>

                        </div>

                        <span class="text-slate-400">
                            →
                        </span>

                    </a>

                    <a
                        href="{{ route('prescriptions.index') }}"
                        class="flex items-center
                               justify-between
                               rounded-lg
                               border border-slate-200
                               p-4
                               hover:bg-slate-50">

                        <div>

                            <p
                                class="text-sm
                                       font-medium
                                       text-slate-500">
                                Recetas
                            </p>

                            <p
                                class="mt-1 text-2xl
                                       font-bold
                                       text-slate-900">
                                {{ $prescriptionsTodayCount }}
                            </p>

                        </div>

                        <span class="text-slate-400">
                            →
                        </span>

                    </a>

                </div>

            </section>

        </div>


        {{-- TERCERA FILA --}}
        <div
            class="mt-8 grid gap-6
                   xl:grid-cols-2">

            {{-- GRÁFICA 7 DÍAS --}}
            <section
                class="rounded-xl
                       border border-slate-200
                       bg-white p-6
                       shadow-sm">

                <h2 class="font-semibold text-slate-900">
                    Próximos 7 días
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Carga de citas programadas.
                </p>

                <div
                    class="mt-8 flex h-52
                           items-end gap-3">

                    @foreach (
                    $appointmentsNextDays
                    as $day
                    )

                    @php
                    $height =
                    $day['count'] > 0
                    ? max(
                    12,
                    round(
                    (
                    $day['count']
                    / $maxAppointmentsPerDay
                    ) * 100
                    )
                    )
                    : 4;
                    @endphp

                    <div
                        class="flex h-full
                                   flex-1 flex-col
                                   justify-end">

                        <div
                            class="mb-2 text-center
                                       text-xs
                                       font-semibold
                                       text-slate-700">
                            {{ $day['count'] }}
                        </div>

                        <div
                            class="flex flex-1
                                       items-end">

                            <div
                                class="w-full
                                           rounded-t-lg
                                           bg-slate-900
                                           transition-all
                                           hover:bg-slate-700"
                                style="height: {{ $height }}%"></div>

                        </div>

                        <div
                            class="mt-3 text-center">

                            <p
                                class="text-xs
                                           font-semibold
                                           text-slate-700">
                                {{ ucfirst(
                                        $day['date']
                                            ->locale('es')
                                            ->translatedFormat('D')
                                    ) }}
                            </p>

                            <p
                                class="text-xs
                                           text-slate-400">
                                {{ $day['date']->format('d/m') }}
                            </p>

                        </div>

                    </div>

                    @endforeach

                </div>

            </section>


            {{-- ESTADOS --}}
            <section
                class="rounded-xl
                       border border-slate-200
                       bg-white p-6
                       shadow-sm">

                <h2 class="font-semibold text-slate-900">
                    Estado de la agenda
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Distribución de las citas de hoy.
                </p>

                <div class="mt-6 space-y-4">

                    @php
                    $statusRows = [
                    [
                    'label' => 'Programadas',
                    'value' =>
                    $appointmentStatusCounts['scheduled'],
                    ],
                    [
                    'label' => 'Confirmadas',
                    'value' =>
                    $appointmentStatusCounts['confirmed'],
                    ],
                    [
                    'label' => 'Paciente llegó',
                    'value' =>
                    $appointmentStatusCounts['checked_in'],
                    ],
                    [
                    'label' => 'En atención',
                    'value' =>
                    $appointmentStatusCounts['in_progress'],
                    ],
                    [
                    'label' => 'Completadas',
                    'value' =>
                    $appointmentStatusCounts['completed'],
                    ],
                    [
                    'label' => 'Canceladas',
                    'value' =>
                    $appointmentStatusCounts['cancelled'],
                    ],
                    [
                    'label' => 'No se presentó',
                    'value' =>
                    $appointmentStatusCounts['no_show'],
                    ],
                    ];

                    $statusTotal = max(
                    1,
                    array_sum(
                    $appointmentStatusCounts
                    )
                    );
                    @endphp

                    @foreach ($statusRows as $row)

                    @php
                    $percentage = round(
                    (
                    $row['value']
                    / $statusTotal
                    ) * 100
                    );
                    @endphp

                    <div>

                        <div
                            class="mb-1 flex
                                       items-center
                                       justify-between">

                            <span
                                class="text-sm
                                           text-slate-600">
                                {{ $row['label'] }}
                            </span>

                            <span
                                class="text-sm
                                           font-semibold
                                           text-slate-900">
                                {{ $row['value'] }}
                            </span>

                        </div>

                        <div
                            class="h-2
                                       overflow-hidden
                                       rounded-full
                                       bg-slate-100">

                            <div
                                class="h-full
                                           rounded-full
                                           bg-slate-900"
                                style="width:
                                        {{ $percentage }}%"></div>

                        </div>

                    </div>

                    @endforeach

                </div>

            </section>

        </div>


        {{-- ACCIONES RÁPIDAS --}}
        <section
            class="mt-8
                   rounded-xl
                   border border-slate-200
                   bg-white p-6
                   shadow-sm">

            <h2 class="font-semibold text-slate-900">
                Acciones rápidas
            </h2>

            <div
                class="mt-4 grid gap-3
                       sm:grid-cols-2
                       lg:grid-cols-4">

                <a
                    href="{{ route('appointments.create') }}"
                    class="rounded-lg
                           border border-slate-200
                           p-4
                           text-sm font-semibold
                           text-slate-700
                           hover:bg-slate-50">
                    + Programar cita
                </a>

                <a
                    href="{{ route('patients.index') }}"
                    class="rounded-lg
                           border border-slate-200
                           p-4
                           text-sm font-semibold
                           text-slate-700
                           hover:bg-slate-50">
                    Pacientes
                </a>

                <a
                    href="{{ route('consultations.index') }}"
                    class="rounded-lg
                           border border-slate-200
                           p-4
                           text-sm font-semibold
                           text-slate-700
                           hover:bg-slate-50">
                    Consultas
                </a>

                <a
                    href="{{ route('prescriptions.index') }}"
                    class="rounded-lg
                           border border-slate-200
                           p-4
                           text-sm font-semibold
                           text-slate-700
                           hover:bg-slate-50">
                    Recetas
                </a>

            </div>

        </section>

    </div>

</x-layouts.app>