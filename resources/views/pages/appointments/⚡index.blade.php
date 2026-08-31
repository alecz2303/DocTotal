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

    {{-- PAGE HEADER --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5.5 w-5.5" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M8 3v4M16 3v4M3 10h18" stroke-linecap="round" />
                </svg>
            </div>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Agenda</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Consulta y administra las citas del consultorio.
                </p>
            </div>
        </div>

        <a
            href="{{ route('appointments.create') }}"
            class="dt-btn dt-btn-primary inline-flex gap-2 self-start lg:self-auto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4.5 w-4.5" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke-linecap="round" />
            </svg>
            Nueva cita
        </a>
    </div>

    {{-- TOOLBAR --}}
    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-4 xl:flex-row xl:items-end xl:justify-between">

            {{-- PERIOD --}}
            <div class="min-w-0">
                <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
                    Periodo
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="previousPeriod"
                        aria-label="Periodo anterior"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4.5 w-4.5">
                            <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <div class="flex min-h-10 min-w-52 items-center justify-center rounded-xl border border-slate-200 bg-slate-50/80 px-4 text-center sm:min-w-64">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ $periodTitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="nextPeriod"
                        aria-label="Periodo siguiente"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4.5 w-4.5">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        wire:click="today"
                        class="h-10 rounded-xl px-3 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">
                        Hoy
                    </button>
                </div>
            </div>

            {{-- VIEW MODE --}}
            <div>
                <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
                    Vista
                </p>

                <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                    @foreach ([
                    'month' => 'Mes',
                    'week' => 'Semana',
                    'day' => 'Día',
                    ] as $mode => $label)
                    <button
                        type="button"
                        wire:click="setViewMode('{{ $mode }}')"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition
                                   {{ $viewMode === $mode
                                        ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200'
                                        : 'text-slate-500 hover:text-slate-900'
                                   }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="grid gap-3 border-t border-slate-100 bg-slate-50/50 p-4 md:grid-cols-[minmax(0,2fr)_minmax(180px,1fr)_auto] md:items-end">
            <div>
                <label class="dt-label">Buscar paciente</label>
                <div class="relative">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        class="pointer-events-none absolute left-3 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-400">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                    </svg>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        autocomplete="off"
                        placeholder="Nombre, teléfono o correo..."
                        class="dt-input pl-10">
                </div>
            </div>

            <div>
                <label class="dt-label">Estado</label>
                <select wire:model.live="status" class="dt-select">
                    <option value="">Todos los estados</option>
                    <option value="scheduled">Programada</option>
                    <option value="confirmed">Confirmada</option>
                    <option value="checked_in">Paciente llegó</option>
                    <option value="in_progress">En atención</option>
                    <option value="completed">Completada</option>
                    <option value="cancelled">Cancelada</option>
                    <option value="no_show">No se presentó</option>
                </select>
            </div>

            <div>
                @if ($search || $status)
                <button type="button" wire:click="clearFilters" class="dt-btn dt-btn-secondary w-full md:w-auto">
                    Limpiar
                </button>
                @endif
            </div>
        </div>
    </section>

    {{-- MONTH VIEW --}}
    @if ($viewMode === 'month')
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="grid grid-cols-7 gap-px border-b border-slate-200 bg-white p-1.5">
            @foreach ([
            ['Lun', 'bg-blue-50 text-blue-700'],
            ['Mar', 'bg-cyan-50 text-cyan-700'],
            ['Mié', 'bg-emerald-50 text-emerald-700'],
            ['Jue', 'bg-amber-50 text-amber-700'],
            ['Vie', 'bg-sky-50 text-sky-700'],
            ['Sáb', 'bg-violet-50 text-violet-700'],
            ['Dom', 'bg-rose-50 text-rose-700'],
            ] as [$weekday, $weekdayClass])
            <div class="rounded-lg px-1 py-2 text-center text-[10px] font-bold uppercase tracking-wider sm:px-2 sm:text-xs {{ $weekdayClass }}">
                {{ $weekday }}
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach ($calendarDays as $day)
            @php
            $dayDate = $day['date'];
            $dayAppointments = $day['appointments'];
            $visibleAppointments = $dayAppointments->take(3);
            @endphp

            <button
                type="button"
                wire:click="selectDay('{{ $dayDate->format('Y-m-d') }}')"
                class="group relative min-h-20 border-b border-r border-slate-100 p-1.5 text-left transition hover:z-10 hover:bg-blue-50/40 sm:min-h-28 sm:p-2
                               {{ ! $day['isCurrentMonth'] ? 'bg-slate-50/60' : 'bg-white' }}
                               {{ $day['isSelected'] ? 'ring-2 ring-inset ring-blue-500/60' : '' }}">

                <div class="flex items-start justify-between gap-1">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold sm:text-xs
                                         {{ $day['isToday']
                                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-200'
                                            : ($day['isCurrentMonth'] ? 'text-slate-800' : 'text-slate-400') }}">
                        {{ $dayDate->day }}
                    </span>

                    @if ($day['isDayOff'])
                    <span class="hidden rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500 sm:inline-flex">
                        Libre
                    </span>
                    @elseif ($day['exceptions']->contains('type', 'available'))
                    <span class="hidden rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100 sm:inline-flex">
                        Extra
                    </span>
                    @endif
                </div>

                @if ($day['isDayOff'])
                <div class="mt-2 hidden rounded-lg bg-slate-50 px-2 py-1.5 sm:block">
                    <p class="truncate text-[10px] font-medium text-slate-400">
                        {{ $day['dayOffReason'] }}
                    </p>
                </div>
                @endif

                @if ($dayAppointments->isNotEmpty())
                <div class="mt-2 space-y-1">
                    @foreach ($visibleAppointments as $appointment)
                    @php
                    $appointmentClass = match ($appointment->status) {
                    'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    'cancelled' => 'border-rose-200 bg-rose-50 text-rose-700',
                    'checked_in' => 'border-amber-200 bg-amber-50 text-amber-700',
                    'in_progress' => 'border-orange-200 bg-orange-50 text-orange-700',
                    'confirmed' => 'border-violet-200 bg-violet-50 text-violet-700',
                    'no_show' => 'border-slate-200 bg-slate-100 text-slate-600',
                    default => 'border-blue-200 bg-blue-50 text-blue-700',
                    };
                    @endphp

                    <div class="truncate rounded-md border px-1.5 py-0.5 text-[9px] font-semibold sm:px-2 sm:text-[10px] {{ $appointmentClass }}">
                        <span class="tabular-nums">{{ $appointment->starts_at->format('H:i') }}</span>
                        <span class="hidden sm:inline"> · {{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }}</span>
                    </div>
                    @endforeach

                    @if ($dayAppointments->count() > 3)
                    <p class="px-1 text-[9px] font-bold text-blue-600 sm:text-[11px]">
                        + {{ $dayAppointments->count() - 3 }} más
                    </p>
                    @endif
                </div>
                @endif

                @foreach ($day['exceptions'] as $exception)
                @if ($exception->type === 'blocked' && $exception->start_time && $exception->end_time)
                <div class="mt-2 hidden truncate rounded-md bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-600 sm:block">
                    Bloqueo {{ Carbon::parse($exception->start_time)->format('H:i') }}
                </div>
                @break
                @endif
                @endforeach
            </button>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3 text-[11px] font-medium text-slate-600">
            @foreach ([
            ['bg-blue-500', 'Programada'],
            ['bg-violet-500', 'Confirmada'],
            ['bg-amber-500', 'Paciente llegó'],
            ['bg-orange-500', 'En atención'],
            ['bg-emerald-500', 'Completada'],
            ['bg-rose-500', 'Cancelada'],
            ['bg-slate-400', 'No se presentó'],
            ] as [$dotClass, $label])
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full {{ $dotClass }}"></span>
                {{ $label }}
            </span>
            @endforeach
        </div>
    </section>
    @endif

    {{-- WEEK VIEW --}}
    @if ($viewMode === 'week')
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <div class="grid min-w-[1050px] grid-cols-7 divide-x divide-slate-100">
                @foreach ($weekDays as $day)
                @php $dayDate = $day['date']; @endphp

                <div class="min-h-[520px] bg-white">
                    <button
                        type="button"
                        wire:click="selectDay('{{ $dayDate->format('Y-m-d') }}')"
                        class="w-full border-b border-slate-100 px-3 py-4 text-center transition hover:bg-blue-50/40">

                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            {{ ucfirst($dayDate->copy()->locale('es')->translatedFormat('D')) }}
                        </p>

                        <div class="mx-auto mt-2 flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold
                                            {{ $day['isToday'] ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'text-slate-900' }}">
                            {{ $dayDate->day }}
                        </div>

                        @if ($day['isDayOff'])
                        <p class="mt-2 truncate text-xs font-medium text-slate-400">{{ $day['dayOffReason'] }}</p>
                        @else
                        <p class="mt-2 text-xs font-medium text-blue-600">
                            {{ $day['appointments']->count() }} cita(s)
                        </p>
                        @endif
                    </button>

                    <div class="space-y-2 p-3">
                        @if ($day['isDayOff'] && $day['appointments']->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center">
                            <p class="text-xs text-slate-400">Sin atención</p>
                        </div>
                        @endif

                        @foreach ($day['appointments'] as $appointment)
                        @php
                        $statusClass = match ($appointment->status) {
                        'completed' => 'border-emerald-200 bg-emerald-50',
                        'cancelled' => 'border-rose-200 bg-rose-50',
                        'checked_in' => 'border-amber-200 bg-amber-50',
                        'in_progress' => 'border-orange-200 bg-orange-50',
                        'confirmed' => 'border-violet-200 bg-violet-50',
                        'no_show' => 'border-slate-200 bg-slate-50',
                        default => 'border-blue-200 bg-blue-50',
                        };
                        @endphp

                        <a
                            href="{{ route('appointments.show', ['uuid' => $appointment->uuid]) }}"
                            class="block rounded-xl border p-3 transition hover:-translate-y-0.5 hover:shadow-sm {{ $statusClass }}">
                            <p class="text-xs font-bold tabular-nums text-slate-900">
                                {{ $appointment->starts_at->format('H:i') }} – {{ $appointment->ends_at->format('H:i') }}
                            </p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }}
                            </p>
                            <p class="mt-1 truncate text-xs text-slate-500">
                                {{ $appointment->reason ?: 'Sin motivo' }}
                            </p>
                        </a>
                        @endforeach

                        @foreach ($day['exceptions'] as $exception)
                        @if ($exception->type === 'blocked' && $exception->start_time && $exception->end_time)
                        <div class="rounded-xl border border-rose-100 bg-rose-50 p-3">
                            <p class="text-xs font-bold text-rose-700">Bloqueado</p>
                            <p class="mt-1 text-xs text-rose-600">
                                {{ Carbon::parse($exception->start_time)->format('H:i') }} –
                                {{ Carbon::parse($exception->end_time)->format('H:i') }}
                            </p>
                            @if ($exception->reason)
                            <p class="mt-1 text-xs text-rose-500">{{ $exception->reason }}</p>
                            @endif
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- DAY VIEW --}}
    @if ($viewMode === 'day')
    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <rect x="3" y="5" width="18" height="16" rx="2" />
                        <path d="M8 3v4M16 3v4M3 10h18" />
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-slate-950">
                        {{ ucfirst($selectedDate->copy()->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }}
                    </h2>

                    @if ($dayData['isDayOff'])
                    <p class="mt-1 text-sm text-slate-500">{{ $dayData['dayOffReason'] }}</p>
                    @else
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($dayData['schedules'] as $schedule)
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                            {{ Carbon::parse($schedule->start_time)->format('H:i') }} –
                            {{ Carbon::parse($schedule->end_time)->format('H:i') }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            @if ($dayData['isDayOff'])
            <span class="inline-flex self-start rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                Día libre
            </span>
            @else
            <span class="inline-flex self-start items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Día de atención
            </span>
            @endif
        </div>

        @if ($dayData['exceptions']->isNotEmpty())
        <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
                Excepciones del día
            </p>

            <div class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach ($dayData['exceptions'] as $exception)
                @if ($exception->type === 'blocked')
                <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3">
                    <p class="text-sm font-semibold text-rose-700">
                        {{ $exception->start_time && $exception->end_time ? 'Horario bloqueado' : 'Día bloqueado' }}
                    </p>
                    @if ($exception->start_time && $exception->end_time)
                    <p class="mt-1 text-xs text-rose-600">
                        {{ Carbon::parse($exception->start_time)->format('H:i') }} –
                        {{ Carbon::parse($exception->end_time)->format('H:i') }}
                    </p>
                    @endif
                    @if ($exception->reason)
                    <p class="mt-1 text-xs text-rose-500">{{ $exception->reason }}</p>
                    @endif
                </div>
                @elseif ($exception->type === 'available')
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                    <p class="text-sm font-semibold text-emerald-700">Horario extraordinario</p>
                    @if ($exception->start_time && $exception->end_time)
                    <p class="mt-1 text-xs text-emerald-600">
                        {{ Carbon::parse($exception->start_time)->format('H:i') }} –
                        {{ Carbon::parse($exception->end_time)->format('H:i') }}
                    </p>
                    @endif
                    @if ($exception->reason)
                    <p class="mt-1 text-xs text-emerald-500">{{ $exception->reason }}</p>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif
    </section>

    {{-- APPOINTMENT LIST --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="hidden border-b border-slate-100 bg-slate-50/80 px-6 py-3 md:grid md:grid-cols-[100px_1.45fr_1fr_1fr_130px_190px] md:gap-4">
            @foreach (['Hora', 'Paciente', 'Motivo', 'Médico', 'Estado'] as $heading)
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $heading }}</div>
            @endforeach
            <div></div>
        </div>

        @forelse ($dayData['appointments'] as $appointment)
        @php
        $statusLabels = [
        'scheduled' => 'Programada',
        'confirmed' => 'Confirmada',
        'checked_in' => 'Paciente llegó',
        'in_progress' => 'En atención',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
        'no_show' => 'No se presentó',
        ];

        $statusClasses = [
        'scheduled' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'confirmed' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'checked_in' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'in_progress' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'no_show' => 'bg-slate-100 text-slate-700 ring-slate-300',
        ];

        $statusLabel = $statusLabels[$appointment->status] ?? ucfirst($appointment->status);
        $statusClass = $statusClasses[$appointment->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
        @endphp

        <div
            wire:key="appointment-{{ $appointment->uuid }}"
            class="border-b border-slate-100 px-5 py-5 last:border-0 md:grid md:grid-cols-[100px_1.45fr_1fr_1fr_130px_190px] md:items-center md:gap-4 md:px-6">

            <div>
                <p class="text-sm font-bold tabular-nums text-slate-950">{{ $appointment->starts_at->format('H:i') }}</p>
                <p class="text-xs tabular-nums text-slate-400">hasta {{ $appointment->ends_at->format('H:i') }}</p>
            </div>

            <div class="mt-3 min-w-0 md:mt-0">
                <p class="truncate font-semibold text-slate-900">
                    {{ $appointment->patient->first_name }}
                    {{ $appointment->patient->last_name }}
                    {{ $appointment->patient->second_last_name }}
                </p>
                @if ($appointment->patient->phone)
                <p class="mt-1 text-xs text-slate-500">{{ $appointment->patient->phone }}</p>
                @endif
            </div>

            <div class="mt-3 min-w-0 md:mt-0">
                <p class="truncate text-sm text-slate-600">{{ $appointment->reason ?: '—' }}</p>
            </div>

            <div class="mt-3 min-w-0 md:mt-0">
                <p class="truncate text-sm font-medium text-slate-700">
                    Dr. {{ $appointment->doctorProfile->first_name }} {{ $appointment->doctorProfile->last_name }}
                </p>
                @if ($appointment->doctorProfile->specialty)
                <p class="mt-1 truncate text-xs text-slate-500">{{ $appointment->doctorProfile->specialty->name }}</p>
                @endif
            </div>

            <div class="mt-3 md:mt-0">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 md:mt-0 md:justify-end">
                <a
                    href="{{ route('appointments.show', ['uuid' => $appointment->uuid]) }}"
                    class="dt-btn dt-btn-secondary px-3 py-1.5 text-xs">
                    Ver cita
                </a>
                <a
                    href="{{ route('patients.show', ['uuid' => $appointment->patient->uuid]) }}"
                    class="rounded-lg px-2 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 hover:text-blue-700">
                    Paciente
                </a>
            </div>
        </div>
        @empty
        <div class="px-6 py-16 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-6 w-6">
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M8 3v4M16 3v4M3 10h18" />
                </svg>
            </div>

            @if ($dayData['isDayOff'])
            <p class="mt-4 font-semibold text-slate-900">No hay atención este día.</p>
            <p class="mt-1 text-sm text-slate-500">{{ $dayData['dayOffReason'] }}</p>
            @else
            <p class="mt-4 font-semibold text-slate-900">No hay citas para este día.</p>
            <p class="mt-1 text-sm text-slate-500">
                Puedes programar una cita en alguno de los horarios disponibles.
            </p>
            <a href="{{ route('appointments.create') }}" class="dt-btn dt-btn-primary mt-5 inline-flex gap-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                </svg>
                Nueva cita
            </a>
            @endif
        </div>
        @endforelse
    </section>
    @endif

</div>