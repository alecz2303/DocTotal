<?php

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Schedule;
use App\Models\ScheduleException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Agenda | DocTotal')]
    class extends Component
    {
        public string $viewMode = 'month';

        public string $date = '';

        public string $status = '';

        public string $search = '';

        public function mount(): void
        {
            $this->date = now()->format('Y-m-d');
        }

        public function setViewMode(string $mode): void
        {
            if (! in_array($mode, [
                'month',
                'week',
                'day',
            ], true)) {
                return;
            }

            $this->viewMode = $mode;
        }

        public function selectDay(string $date): void
        {
            $this->date = Carbon::parse($date)
                ->format('Y-m-d');

            $this->viewMode = 'day';
        }

        public function previousPeriod(): void
        {
            $date = Carbon::parse($this->date);

            $this->date = match ($this->viewMode) {
                'month' => $date
                    ->subMonthNoOverflow()
                    ->format('Y-m-d'),

                'week' => $date
                    ->subWeek()
                    ->format('Y-m-d'),

                default => $date
                    ->subDay()
                    ->format('Y-m-d'),
            };
        }

        public function nextPeriod(): void
        {
            $date = Carbon::parse($this->date);

            $this->date = match ($this->viewMode) {
                'month' => $date
                    ->addMonthNoOverflow()
                    ->format('Y-m-d'),

                'week' => $date
                    ->addWeek()
                    ->format('Y-m-d'),

                default => $date
                    ->addDay()
                    ->format('Y-m-d'),
            };
        }

        public function today(): void
        {
            $this->date = now()->format('Y-m-d');
        }

        public function clearFilters(): void
        {
            $this->search = '';
            $this->status = '';
        }

        public function with(): array
        {
            $selectedDate = Carbon::parse(
                $this->date
            );

            $doctor = DoctorProfile::query()
                ->where('user_id', auth()->id())
                ->first();

            /*
        |--------------------------------------------------------------------------
        | Rango visible
        |--------------------------------------------------------------------------
        */

            if ($this->viewMode === 'month') {
                $rangeStart = $selectedDate
                    ->copy()
                    ->startOfMonth()
                    ->startOfWeek(Carbon::MONDAY);

                $rangeEnd = $selectedDate
                    ->copy()
                    ->endOfMonth()
                    ->endOfWeek(Carbon::SUNDAY);
            } elseif ($this->viewMode === 'week') {
                $rangeStart = $selectedDate
                    ->copy()
                    ->startOfWeek(Carbon::MONDAY);

                $rangeEnd = $selectedDate
                    ->copy()
                    ->endOfWeek(Carbon::SUNDAY);
            } else {
                $rangeStart = $selectedDate
                    ->copy()
                    ->startOfDay();

                $rangeEnd = $selectedDate
                    ->copy()
                    ->endOfDay();
            }

            /*
        |--------------------------------------------------------------------------
        | Citas del rango
        |--------------------------------------------------------------------------
        */

            $search = trim($this->search);

            $appointments = Appointment::query()
                ->with([
                    'patient',
                    'doctorProfile.specialty',
                ])
                ->whereBetween(
                    'starts_at',
                    [
                        $rangeStart->copy()->startOfDay(),
                        $rangeEnd->copy()->endOfDay(),
                    ]
                )
                ->when(
                    $this->status !== '',
                    fn($query) => $query->where(
                        'status',
                        $this->status
                    )
                )
                ->when(
                    $search !== '',
                    function ($query) use ($search) {
                        $query->whereHas(
                            'patient',
                            function ($patientQuery) use ($search) {
                                $patientQuery->where(
                                    function ($q) use ($search) {
                                        $q
                                            ->where(
                                                'first_name',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'last_name',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'second_last_name',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'phone',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
                                                '%' . $search . '%'
                                            );
                                    }
                                );
                            }
                        );
                    }
                )
                ->orderBy('starts_at')
                ->get();

            $appointmentsByDate = $appointments
                ->groupBy(
                    fn(Appointment $appointment) =>
                    $appointment
                        ->starts_at
                        ->format('Y-m-d')
                );

            /*
        |--------------------------------------------------------------------------
        | Horarios semanales
        |--------------------------------------------------------------------------
        */

            $schedulesByDay = collect();

            if ($doctor) {
                $schedulesByDay = Schedule::query()
                    ->where(
                        'doctor_profile_id',
                        $doctor->id
                    )
                    ->where('active', true)
                    ->orderBy('start_time')
                    ->get()
                    ->groupBy('day_of_week');
            }

            /*
        |--------------------------------------------------------------------------
        | Excepciones dentro del rango
        |--------------------------------------------------------------------------
        */

            $exceptionsByDate = collect();

            if ($doctor) {
                $exceptionsByDate =
                    ScheduleException::query()
                    ->where(
                        'doctor_profile_id',
                        $doctor->id
                    )
                    ->whereDate(
                        'date',
                        '>=',
                        $rangeStart->toDateString()
                    )
                    ->whereDate(
                        'date',
                        '<=',
                        $rangeEnd->toDateString()
                    )
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->get()
                    ->groupBy(
                        fn(ScheduleException $exception) =>
                        $exception
                            ->date
                            ->format('Y-m-d')
                    );
            }

            /*
        |--------------------------------------------------------------------------
        | Construcción de cada día
        |--------------------------------------------------------------------------
        */

            $buildDay = function (
                Carbon $day
            ) use (
                $selectedDate,
                $appointmentsByDate,
                $schedulesByDay,
                $exceptionsByDate
            ): array {
                $dateKey = $day->format('Y-m-d');

                $dayAppointments =
                    $appointmentsByDate->get(
                        $dateKey,
                        collect()
                    );

                $daySchedules =
                    $schedulesByDay->get(
                        $day->dayOfWeek,
                        collect()
                    );

                $dayExceptions =
                    $exceptionsByDate->get(
                        $dateKey,
                        collect()
                    );

                $fullDayBlock =
                    $dayExceptions->first(
                        function ($exception) {
                            return $exception->type === 'blocked'
                                && ! $exception->start_time
                                && ! $exception->end_time;
                        }
                    );

                $extraordinaryAvailability =
                    $dayExceptions->contains(
                        function ($exception) {
                            return $exception->type === 'available'
                                && $exception->start_time
                                && $exception->end_time;
                        }
                    );

                $regularDayOff =
                    $daySchedules->isEmpty()
                    && ! $extraordinaryAvailability;

                $isDayOff =
                    (bool) $fullDayBlock
                    || $regularDayOff;

                $dayOffReason = null;

                if ($fullDayBlock) {
                    $dayOffReason =
                        $fullDayBlock->reason
                        ?: 'Día bloqueado';
                } elseif ($regularDayOff) {
                    $dayOffReason = 'Día libre';
                }

                return [
                    'date' => $day->copy(),

                    'appointments' =>
                    $dayAppointments,

                    'schedules' =>
                    $daySchedules,

                    'exceptions' =>
                    $dayExceptions,

                    'isDayOff' =>
                    $isDayOff,

                    'dayOffReason' =>
                    $dayOffReason,

                    'isToday' =>
                    $day->isToday(),

                    'isSelected' =>
                    $day->isSameDay(
                        $selectedDate
                    ),
                ];
            };

            /*
        |--------------------------------------------------------------------------
        | Días del calendario mensual
        |--------------------------------------------------------------------------
        */

            $calendarDays = collect();

            if ($this->viewMode === 'month') {
                $cursor = $rangeStart->copy();

                while (
                    $cursor->lessThanOrEqualTo(
                        $rangeEnd
                    )
                ) {
                    $day = $buildDay(
                        $cursor->copy()
                    );

                    $day['isCurrentMonth'] =
                        $cursor->month
                        === $selectedDate->month;

                    $calendarDays->push($day);

                    $cursor->addDay();
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Días de la semana
        |--------------------------------------------------------------------------
        */

            $weekDays = collect();

            $weekStart = $selectedDate
                ->copy()
                ->startOfWeek(Carbon::MONDAY);

            if ($this->viewMode === 'week') {
                for ($i = 0; $i < 7; $i++) {
                    $weekDays->push(
                        $buildDay(
                            $weekStart
                                ->copy()
                                ->addDays($i)
                        )
                    );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Vista diaria
        |--------------------------------------------------------------------------
        */

            $dayData = $buildDay(
                $selectedDate->copy()
            );

            /*
        |--------------------------------------------------------------------------
        | Títulos
        |--------------------------------------------------------------------------
        */

            $periodTitle = match ($this->viewMode) {
                'month' => ucfirst(
                    $selectedDate
                        ->copy()
                        ->locale('es')
                        ->translatedFormat(
                            'F \d\e Y'
                        )
                ),

                'week' => sprintf(
                    '%s - %s',
                    $weekStart
                        ->copy()
                        ->locale('es')
                        ->translatedFormat(
                            'd M'
                        ),
                    $weekStart
                        ->copy()
                        ->addDays(6)
                        ->locale('es')
                        ->translatedFormat(
                            'd M Y'
                        )
                ),

                default => ucfirst(
                    $selectedDate
                        ->copy()
                        ->locale('es')
                        ->translatedFormat(
                            'l d \d\e F \d\e Y'
                        )
                ),
            };

            return [
                'selectedDate' =>
                $selectedDate,

                'periodTitle' =>
                $periodTitle,

                'appointments' =>
                $appointments,

                'calendarDays' =>
                $calendarDays,

                'weekDays' =>
                $weekDays,

                'dayData' =>
                $dayData,
            ];
        }
    };
?>

<div class="mx-auto max-w-7xl">

    {{-- HEADER --}}
    <div
        class="mb-8 flex flex-col gap-4
               lg:flex-row lg:items-end
               lg:justify-between">

        <div>

            <h1
                class="text-2xl font-bold
                       tracking-tight
                       text-slate-900">
                Agenda
            </h1>

            <p
                class="mt-1 text-sm
                       text-slate-500">
                Consulta y administra las citas del consultorio.
            </p>

        </div>

        <div class="flex flex-wrap gap-2">

            <a
                href="{{ route(
                    'appointments.create'
                ) }}"
                class="inline-flex
                       items-center
                       justify-center
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


    {{-- CONTROLES PRINCIPALES --}}
    <div
        class="mb-6 rounded-xl
               border border-slate-200
               bg-white p-5
               shadow-sm">

        <div
            class="flex flex-col gap-5
                   xl:flex-row
                   xl:items-end
                   xl:justify-between">

            {{-- NAVEGACIÓN --}}
            <div>

                <p
                    class="mb-2 text-xs
                           font-semibold uppercase
                           tracking-wide
                           text-slate-500">
                    Periodo
                </p>

                <div
                    class="flex flex-wrap
                           items-center gap-2">

                    <button
                        type="button"
                        wire:click="previousPeriod"
                        class="rounded-lg
                               border border-slate-300
                               px-3 py-2
                               text-sm font-semibold
                               text-slate-700
                               hover:bg-slate-50">
                        ←
                    </button>

                    <div
                        class="min-w-56
                               rounded-lg
                               border border-slate-200
                               bg-slate-50
                               px-4 py-2
                               text-center">
                        <p
                            class="text-sm
                                   font-semibold
                                   text-slate-900">
                            {{ $periodTitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="nextPeriod"
                        class="rounded-lg
                               border border-slate-300
                               px-3 py-2
                               text-sm font-semibold
                               text-slate-700
                               hover:bg-slate-50">
                        →
                    </button>

                    <button
                        type="button"
                        wire:click="today"
                        class="rounded-lg
                               px-3 py-2
                               text-sm font-semibold
                               text-slate-500
                               hover:text-slate-900">
                        Hoy
                    </button>

                </div>

            </div>


            {{-- SELECTOR VISTA --}}
            <div>

                <p
                    class="mb-2 text-xs
                           font-semibold uppercase
                           tracking-wide
                           text-slate-500">
                    Vista
                </p>

                <div
                    class="inline-flex
                           rounded-lg
                           border border-slate-300
                           bg-white p-1">

                    <button
                        type="button"
                        wire:click="setViewMode('month')"
                        class="rounded-md
                               px-4 py-1.5
                               text-sm font-semibold
                               transition
                               {{ $viewMode === 'month'
                                    ? 'bg-slate-900 text-white'
                                    : 'text-slate-600 hover:bg-slate-50'
                               }}">
                        Mes
                    </button>

                    <button
                        type="button"
                        wire:click="setViewMode('week')"
                        class="rounded-md
                               px-4 py-1.5
                               text-sm font-semibold
                               transition
                               {{ $viewMode === 'week'
                                    ? 'bg-slate-900 text-white'
                                    : 'text-slate-600 hover:bg-slate-50'
                               }}">
                        Semana
                    </button>

                    <button
                        type="button"
                        wire:click="setViewMode('day')"
                        class="rounded-md
                               px-4 py-1.5
                               text-sm font-semibold
                               transition
                               {{ $viewMode === 'day'
                                    ? 'bg-slate-900 text-white'
                                    : 'text-slate-600 hover:bg-slate-50'
                               }}">
                        Día
                    </button>

                </div>

            </div>

        </div>


        {{-- FILTROS --}}
        <div
            class="mt-5 grid gap-4
                   border-t border-slate-200
                   pt-5
                   md:grid-cols-[2fr_1fr_auto]">

            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium">
                    Buscar paciente
                </label>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    autocomplete="off"
                    placeholder="Nombre, teléfono o correo..."
                    class="w-full rounded-lg
                           border border-slate-300
                           px-3 py-2">

            </div>

            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium">
                    Estado
                </label>

                <select
                    wire:model.live="status"
                    class="w-full rounded-lg
                           border border-slate-300
                           px-3 py-2">

                    <option value="">
                        Todos
                    </option>

                    <option value="scheduled">
                        Programada
                    </option>

                    <option value="confirmed">
                        Confirmada
                    </option>

                    <option value="checked_in">
                        Paciente llegó
                    </option>

                    <option value="in_progress">
                        En atención
                    </option>

                    <option value="completed">
                        Completada
                    </option>

                    <option value="cancelled">
                        Cancelada
                    </option>

                    <option value="no_show">
                        No se presentó
                    </option>

                </select>

            </div>

            <div class="flex items-end">

                @if ($search || $status)

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="rounded-lg
                               border border-slate-300
                               px-4 py-2
                               text-sm font-semibold
                               text-slate-600
                               hover:bg-slate-50">
                    Limpiar
                </button>

                @endif

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- VISTA MES --}}
    {{-- ========================================================= --}}

    @if ($viewMode === 'month')

    <section
        class="overflow-hidden
                   rounded-xl
                   border border-slate-200
                   bg-white
                   shadow-sm">

        {{-- DÍAS DE SEMANA --}}
        <div
            class="grid grid-cols-7
                       border-b
                       border-slate-200
                       bg-slate-50">

            @foreach ([
            'Lun',
            'Mar',
            'Mié',
            'Jue',
            'Vie',
            'Sáb',
            'Dom',
            ] as $weekday)

            <div
                class="px-2 py-3
                               text-center
                               text-xs font-semibold
                               uppercase tracking-wide
                               text-slate-500">
                {{ $weekday }}
            </div>

            @endforeach

        </div>


        {{-- CALENDARIO --}}
        <div
            class="grid grid-cols-7">

            @foreach (
            $calendarDays
            as $day
            )

            @php
            $dayDate =
            $day['date'];

            $dayAppointments =
            $day['appointments'];

            $visibleAppointments =
            $dayAppointments
            ->take(3);
            @endphp

            <button
                type="button"
                wire:click="selectDay(
                            '{{ $dayDate->format('Y-m-d') }}'
                        )"
                class="relative
                               min-h-32
                               border-b
                               border-r
                               border-slate-100
                               p-2
                               text-left
                               transition
                               hover:bg-slate-50
                               sm:min-h-40
                               sm:p-3
                               {{ ! $day['isCurrentMonth']
                                    ? 'bg-slate-50/50'
                                    : 'bg-white'
                               }}">

                {{-- DÍA --}}
                <div
                    class="flex
                                   items-start
                                   justify-between
                                   gap-2">

                    <span
                        class="inline-flex
                                       h-7 w-7
                                       items-center
                                       justify-center
                                       rounded-full
                                       text-sm
                                       font-semibold
                                       {{ $day['isToday']
                                            ? 'bg-slate-900 text-white'
                                            : (
                                                $day['isCurrentMonth']
                                                    ? 'text-slate-900'
                                                    : 'text-slate-400'
                                            )
                                       }}">
                        {{ $dayDate->day }}
                    </span>

                    @if ($day['isDayOff'])

                    <span
                        class="hidden
                                           rounded-full
                                           bg-slate-100
                                           px-2 py-0.5
                                           text-[10px]
                                           font-semibold
                                           text-slate-500
                                           sm:inline-flex">
                        Libre
                    </span>

                    @elseif (
                    $day['exceptions']
                    ->contains(
                    'type',
                    'available'
                    )
                    )

                    <span
                        class="hidden
                                           rounded-full
                                           bg-green-50
                                           px-2 py-0.5
                                           text-[10px]
                                           font-semibold
                                           text-green-700
                                           sm:inline-flex">
                        Extra
                    </span>

                    @endif

                </div>


                {{-- DÍA LIBRE --}}
                @if ($day['isDayOff'])

                <div
                    class="mt-3
                                       rounded-md
                                       bg-slate-100
                                       px-2 py-1.5">

                    <p
                        class="truncate
                                           text-[11px]
                                           font-medium
                                           text-slate-500">
                        {{ $day['dayOffReason'] }}
                    </p>

                </div>

                @endif


                {{-- CITAS --}}
                @if (
                $dayAppointments->isNotEmpty()
                )

                <div class="mt-2 space-y-1">

                    @foreach (
                    $visibleAppointments
                    as $appointment
                    )

                    @php
                    $appointmentClass =
                    match (
                    $appointment->status
                    ) {
                    'completed' =>
                    'bg-green-50 text-green-700',

                    'cancelled' =>
                    'bg-red-50 text-red-700',

                    'checked_in' =>
                    'bg-amber-50 text-amber-700',

                    'in_progress' =>
                    'bg-orange-50 text-orange-700',

                    'confirmed' =>
                    'bg-indigo-50 text-indigo-700',

                    'no_show' =>
                    'bg-slate-100 text-slate-600',

                    default =>
                    'bg-blue-50 text-blue-700',
                    };
                    @endphp

                    <div
                        class="truncate
                                               rounded-md
                                               px-2 py-1
                                               text-[11px]
                                               font-medium
                                               {{ $appointmentClass }}">
                        {{ $appointment
                                            ->starts_at
                                            ->format('H:i') }}

                        ·

                        {{ $appointment
                                            ->patient
                                            ->first_name }}

                        {{ $appointment
                                            ->patient
                                            ->last_name }}
                    </div>

                    @endforeach

                    @if (
                    $dayAppointments->count()
                    > 3
                    )

                    <p
                        class="px-1
                                               text-[11px]
                                               font-semibold
                                               text-slate-500">
                        +
                        {{
                                            $dayAppointments
                                                ->count()
                                            - 3
                                        }}
                        más
                    </p>

                    @endif

                </div>

                @endif


                {{-- EXCEPCIÓN --}}
                @foreach (
                $day['exceptions']
                as $exception
                )

                @if (
                $exception->type
                === 'blocked'
                && $exception->start_time
                && $exception->end_time
                )

                <div
                    class="mt-2 truncate
                                           rounded-md
                                           bg-red-50
                                           px-2 py-1
                                           text-[10px]
                                           font-medium
                                           text-red-600">
                    Bloqueo
                    {{ Carbon::parse(
                                        $exception->start_time
                                    )->format('H:i') }}
                </div>

                @break

                @endif

                @endforeach

            </button>

            @endforeach

        </div>

    </section>

    @endif


    {{-- ========================================================= --}}
    {{-- VISTA SEMANA --}}
    {{-- ========================================================= --}}

    @if ($viewMode === 'week')

    <div
        class="overflow-x-auto
                   rounded-xl
                   border border-slate-200
                   bg-white
                   shadow-sm">

        <div
            class="grid
                       min-w-[1050px]
                       grid-cols-7
                       divide-x
                       divide-slate-200">

            @foreach (
            $weekDays
            as $day
            )

            @php
            $dayDate =
            $day['date'];
            @endphp

            <div
                class="min-h-[560px]">

                {{-- CABECERA DÍA --}}
                <button
                    type="button"
                    wire:click="selectDay(
                                '{{ $dayDate->format('Y-m-d') }}'
                            )"
                    class="w-full
                                   border-b
                                   border-slate-200
                                   px-3 py-4
                                   text-center
                                   hover:bg-slate-50">

                    <p
                        class="text-xs
                                       font-semibold
                                       uppercase
                                       tracking-wide
                                       text-slate-500">
                        {{ ucfirst(
                                    $dayDate
                                        ->copy()
                                        ->locale('es')
                                        ->translatedFormat('D')
                                ) }}
                    </p>

                    <div
                        class="mx-auto mt-2
                                       flex h-9 w-9
                                       items-center
                                       justify-center
                                       rounded-full
                                       text-sm
                                       font-bold
                                       {{ $day['isToday']
                                            ? 'bg-slate-900 text-white'
                                            : 'text-slate-900'
                                       }}">
                        {{ $dayDate->day }}
                    </div>

                    @if ($day['isDayOff'])

                    <p
                        class="mt-2
                                           text-xs
                                           font-medium
                                           text-slate-400">
                        {{ $day['dayOffReason'] }}
                    </p>

                    @else

                    <p
                        class="mt-2
                                           text-xs
                                           text-slate-400">
                        {{
                                        $day['appointments']
                                            ->count()
                                    }}
                        cita(s)
                    </p>

                    @endif

                </button>


                {{-- CONTENIDO --}}
                <div class="space-y-2 p-3">

                    @if (
                    $day['isDayOff']
                    && $day['appointments']->isEmpty()
                    )

                    <div
                        class="rounded-lg
                                           bg-slate-50
                                           p-3
                                           text-center">

                        <p
                            class="text-xs
                                               text-slate-400">
                            Sin atención
                        </p>

                    </div>

                    @endif


                    @foreach (
                    $day['appointments']
                    as $appointment
                    )

                    @php
                    $statusClass =
                    match (
                    $appointment->status
                    ) {
                    'completed' =>
                    'border-green-200 bg-green-50',

                    'cancelled' =>
                    'border-red-200 bg-red-50',

                    'checked_in' =>
                    'border-amber-200 bg-amber-50',

                    'in_progress' =>
                    'border-orange-200 bg-orange-50',

                    'confirmed' =>
                    'border-indigo-200 bg-indigo-50',

                    default =>
                    'border-blue-200 bg-blue-50',
                    };
                    @endphp

                    <a
                        href="{{ route(
                                        'appointments.show',
                                        [
                                            'uuid' =>
                                                $appointment->uuid
                                        ]
                                    ) }}"
                        class="block
                                           rounded-lg
                                           border
                                           p-3
                                           transition
                                           hover:shadow-sm
                                           {{ $statusClass }}">

                        <p
                            class="text-xs
                                               font-bold
                                               text-slate-900">
                            {{ $appointment
                                            ->starts_at
                                            ->format('H:i') }}

                            –

                            {{ $appointment
                                            ->ends_at
                                            ->format('H:i') }}
                        </p>

                        <p
                            class="mt-1
                                               text-sm
                                               font-semibold
                                               text-slate-800">
                            {{ $appointment
                                            ->patient
                                            ->first_name }}

                            {{ $appointment
                                            ->patient
                                            ->last_name }}
                        </p>

                        <p
                            class="mt-1 truncate
                                               text-xs
                                               text-slate-500">
                            {{ $appointment->reason
                                            ?: 'Sin motivo' }}
                        </p>

                    </a>

                    @endforeach


                    {{-- EXCEPCIONES --}}
                    @foreach (
                    $day['exceptions']
                    as $exception
                    )

                    @if (
                    $exception->type
                    === 'blocked'
                    && $exception->start_time
                    && $exception->end_time
                    )

                    <div
                        class="rounded-lg
                                               border
                                               border-red-100
                                               bg-red-50
                                               p-3">

                        <p
                            class="text-xs
                                                   font-semibold
                                                   text-red-700">
                            Bloqueado
                        </p>

                        <p
                            class="mt-1
                                                   text-xs
                                                   text-red-600">
                            {{ Carbon::parse(
                                                $exception->start_time
                                            )->format('H:i') }}

                            –

                            {{ Carbon::parse(
                                                $exception->end_time
                                            )->format('H:i') }}
                        </p>

                        @if ($exception->reason)

                        <p
                            class="mt-1
                                                       text-xs
                                                       text-red-500">
                            {{ $exception->reason }}
                        </p>

                        @endif

                    </div>

                    @endif

                    @endforeach

                </div>

            </div>

            @endforeach

        </div>

    </div>

    @endif


    {{-- ========================================================= --}}
    {{-- VISTA DÍA --}}
    {{-- ========================================================= --}}

    @if ($viewMode === 'day')

    {{-- INFORMACIÓN DEL DÍA --}}
    <section
        class="mb-6
                   rounded-xl
                   border border-slate-200
                   bg-white
                   shadow-sm">

        <div
            class="flex flex-col
                       gap-4
                       border-b
                       border-slate-200
                       px-6 py-4
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

            <div>

                <h2
                    class="font-semibold
                               text-slate-900">
                    {{ ucfirst(
                            $selectedDate
                                ->copy()
                                ->locale('es')
                                ->translatedFormat(
                                    'l d \d\e F \d\e Y'
                                )
                        ) }}
                </h2>

                @if ($dayData['isDayOff'])

                <p
                    class="mt-1
                                   text-sm
                                   text-slate-500">
                    {{ $dayData['dayOffReason'] }}
                </p>

                @else

                <div
                    class="mt-2
                                   flex flex-wrap gap-2">

                    @foreach (
                    $dayData['schedules']
                    as $schedule
                    )

                    <span
                        class="rounded-full
                                           bg-slate-100
                                           px-3 py-1
                                           text-xs
                                           font-medium
                                           text-slate-600">
                        {{ Carbon::parse(
                                        $schedule->start_time
                                    )->format('H:i') }}

                        –

                        {{ Carbon::parse(
                                        $schedule->end_time
                                    )->format('H:i') }}
                    </span>

                    @endforeach

                </div>

                @endif

            </div>

            @if ($dayData['isDayOff'])

            <span
                class="inline-flex
                               rounded-full
                               bg-slate-100
                               px-3 py-1
                               text-xs
                               font-semibold
                               text-slate-700">
                Día libre
            </span>

            @else

            <span
                class="inline-flex
                               rounded-full
                               bg-green-50
                               px-3 py-1
                               text-xs
                               font-semibold
                               text-green-700
                               ring-1
                               ring-inset
                               ring-green-200">
                Día de atención
            </span>

            @endif

        </div>


        {{-- EXCEPCIONES --}}
        @if (
        $dayData['exceptions']
        ->isNotEmpty()
        )

        <div
            class="bg-slate-50
                           px-6 py-4">

            <p
                class="text-xs
                               font-semibold
                               uppercase
                               tracking-wide
                               text-slate-500">
                Excepciones del día
            </p>

            <div
                class="mt-3
                               grid gap-2
                               md:grid-cols-2">

                @foreach (
                $dayData['exceptions']
                as $exception
                )

                @if (
                $exception->type
                === 'blocked'
                )

                <div
                    class="rounded-lg
                                           border
                                           border-red-100
                                           bg-red-50
                                           px-4 py-3">

                    <p
                        class="text-sm
                                               font-semibold
                                               text-red-700">
                        @if (
                        $exception->start_time
                        && $exception->end_time
                        )
                        Horario bloqueado
                        @else
                        Día bloqueado
                        @endif
                    </p>

                    @if (
                    $exception->start_time
                    && $exception->end_time
                    )

                    <p
                        class="mt-1
                                                   text-xs
                                                   text-red-600">
                        {{ Carbon::parse(
                                                $exception->start_time
                                            )->format('H:i') }}

                        –

                        {{ Carbon::parse(
                                                $exception->end_time
                                            )->format('H:i') }}
                    </p>

                    @endif

                    @if ($exception->reason)

                    <p
                        class="mt-1
                                                   text-xs
                                                   text-red-500">
                        {{ $exception->reason }}
                    </p>

                    @endif

                </div>

                @elseif (
                $exception->type
                === 'available'
                )

                <div
                    class="rounded-lg
                                           border
                                           border-green-100
                                           bg-green-50
                                           px-4 py-3">

                    <p
                        class="text-sm
                                               font-semibold
                                               text-green-700">
                        Horario extraordinario
                    </p>

                    @if (
                    $exception->start_time
                    && $exception->end_time
                    )

                    <p
                        class="mt-1
                                                   text-xs
                                                   text-green-600">
                        {{ Carbon::parse(
                                                $exception->start_time
                                            )->format('H:i') }}

                        –

                        {{ Carbon::parse(
                                                $exception->end_time
                                            )->format('H:i') }}
                    </p>

                    @endif

                    @if ($exception->reason)

                    <p
                        class="mt-1
                                                   text-xs
                                                   text-green-500">
                        {{ $exception->reason }}
                    </p>

                    @endif

                </div>

                @endif

                @endforeach

            </div>

        </div>

        @endif

    </section>


    {{-- LISTA DE CITAS --}}
    <section
        class="overflow-hidden
                   rounded-xl
                   border border-slate-200
                   bg-white
                   shadow-sm">

        <div
            class="hidden
                       border-b
                       border-slate-200
                       bg-slate-50
                       px-6 py-3
                       md:grid
                       md:grid-cols-[110px_1.5fr_1fr_1fr_130px_220px]
                       md:gap-4">

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Hora
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Paciente
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Motivo
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Médico
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Estado
            </div>

            <div></div>

        </div>


        @forelse (
        $dayData['appointments']
        as $appointment
        )

        @php
        $statusLabels = [
        'scheduled' =>
        'Programada',

        'confirmed' =>
        'Confirmada',

        'checked_in' =>
        'Paciente llegó',

        'in_progress' =>
        'En atención',

        'completed' =>
        'Completada',

        'cancelled' =>
        'Cancelada',

        'no_show' =>
        'No se presentó',
        ];

        $statusClasses = [
        'scheduled' =>
        'bg-blue-50 text-blue-700 ring-blue-200',

        'confirmed' =>
        'bg-indigo-50 text-indigo-700 ring-indigo-200',

        'checked_in' =>
        'bg-amber-50 text-amber-700 ring-amber-200',

        'in_progress' =>
        'bg-orange-50 text-orange-700 ring-orange-200',

        'completed' =>
        'bg-green-50 text-green-700 ring-green-200',

        'cancelled' =>
        'bg-red-50 text-red-700 ring-red-200',

        'no_show' =>
        'bg-slate-100 text-slate-700 ring-slate-300',
        ];

        $statusLabel =
        $statusLabels[
        $appointment->status
        ]
        ?? ucfirst(
        $appointment->status
        );

        $statusClass =
        $statusClasses[
        $appointment->status
        ]
        ?? 'bg-slate-50 text-slate-700 ring-slate-200';
        @endphp

        <div
            wire:key="appointment-{{ $appointment->uuid }}"
            class="border-b
                           border-slate-100
                           px-6 py-5
                           last:border-0
                           md:grid
                           md:grid-cols-[110px_1.5fr_1fr_1fr_130px_220px]
                           md:items-center
                           md:gap-4">

            <div>

                <p
                    class="text-sm
                                   font-bold
                                   text-slate-900">
                    {{ $appointment
                                ->starts_at
                                ->format('H:i') }}
                </p>

                <p
                    class="text-xs
                                   text-slate-500">
                    hasta

                    {{ $appointment
                                ->ends_at
                                ->format('H:i') }}
                </p>

            </div>


            <div class="mt-3 md:mt-0">

                <p
                    class="font-semibold
                                   text-slate-900">
                    {{ $appointment->patient->first_name }}
                    {{ $appointment->patient->last_name }}
                    {{ $appointment->patient->second_last_name }}
                </p>

                @if (
                $appointment
                ->patient
                ->phone
                )

                <p
                    class="mt-1
                                       text-xs
                                       text-slate-500">
                    {{ $appointment
                                    ->patient
                                    ->phone }}
                </p>

                @endif

            </div>


            <div class="mt-3 md:mt-0">

                <p
                    class="text-sm
                                   text-slate-600">
                    {{ $appointment->reason
                                ?: '—' }}
                </p>

            </div>


            <div class="mt-3 md:mt-0">

                <p
                    class="text-sm
                                   text-slate-700">
                    Dr.
                    {{ $appointment->doctorProfile->first_name }}
                    {{ $appointment->doctorProfile->last_name }}
                </p>

                @if (
                $appointment
                ->doctorProfile
                ->specialty
                )

                <p
                    class="mt-1
                                       text-xs
                                       text-slate-500">
                    {{ $appointment
                                    ->doctorProfile
                                    ->specialty
                                    ->name }}
                </p>

                @endif

            </div>


            <div class="mt-3 md:mt-0">

                <span
                    class="inline-flex
                                   rounded-full
                                   px-2.5 py-1
                                   text-xs
                                   font-semibold
                                   ring-1
                                   ring-inset
                                   {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

            </div>


            <div
                class="mt-4
                               flex flex-wrap
                               items-center
                               justify-end
                               gap-2
                               md:mt-0">

                <a
                    href="{{ route(
                                'appointments.show',
                                [
                                    'uuid' =>
                                        $appointment->uuid
                                ]
                            ) }}"
                    class="inline-flex
                                   rounded-lg
                                   border border-slate-300
                                   px-3 py-1.5
                                   text-xs
                                   font-semibold
                                   text-slate-700
                                   hover:bg-slate-50">
                    Ver cita
                </a>

                <a
                    href="{{ route(
                                'patients.show',
                                [
                                    'uuid' =>
                                        $appointment
                                            ->patient
                                            ->uuid
                                ]
                            ) }}"
                    class="inline-flex
                                   px-2 py-1.5
                                   text-xs
                                   font-semibold
                                   text-slate-500
                                   hover:text-slate-900">
                    Paciente
                </a>

            </div>

        </div>

        @empty

        <div
            class="px-6 py-16
                           text-center">

            @if ($dayData['isDayOff'])

            <div
                class="mx-auto
                                   flex h-12 w-12
                                   items-center
                                   justify-center
                                   rounded-full
                                   bg-slate-100
                                   text-lg">
                ☕
            </div>

            <p
                class="mt-4
                                   font-semibold
                                   text-slate-900">
                No hay atención este día.
            </p>

            <p
                class="mt-1
                                   text-sm
                                   text-slate-500">
                {{ $dayData['dayOffReason'] }}
            </p>

            @else

            <p
                class="font-medium
                                   text-slate-700">
                No hay citas para este día.
            </p>

            <p
                class="mt-1
                                   text-sm
                                   text-slate-500">
                Puedes programar una cita en alguno de los horarios disponibles.
            </p>

            <a
                href="{{ route(
                                'appointments.create'
                            ) }}"
                class="mt-4
                                   inline-flex
                                   rounded-lg
                                   bg-slate-900
                                   px-4 py-2.5
                                   text-sm
                                   font-semibold
                                   text-white
                                   hover:bg-slate-800">
                + Nueva cita
            </a>

            @endif

        </div>

        @endforelse

    </section>

    @endif

</div>