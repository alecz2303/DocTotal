<x-layouts.app>

    <div class="mx-auto max-w-7xl">

        <div class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Bienvenido, {{ auth()->user()->name }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Aquí tienes un resumen de tu consultorio.
            </p>
        </div>

        {{-- Indicadores --}}
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Citas de hoy
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    0
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Pacientes
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ \App\Models\Patient::count() }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Consultas pendientes
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    0
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Próxima cita
                </p>

                <p class="mt-2 text-lg font-semibold text-slate-900">
                    Sin citas
                </p>
            </div>

        </div>

        {{-- Agenda --}}
        <div class="mt-8 rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Próximas citas
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Tu agenda para el día de hoy.
                </p>
            </div>

            <div class="px-6 py-12 text-center">

                <p class="font-medium text-slate-700">
                    No tienes citas programadas.
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Las próximas citas aparecerán aquí.
                </p>

            </div>

        </div>

    </div>

</x-layouts.app>