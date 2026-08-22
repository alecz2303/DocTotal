<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Iniciar sesión | DocTotal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-xl shadow p-8">

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold">
                DocTotal
            </h1>

            <p class="text-gray-500 mt-2">
                Ingresa a tu consultorio
            </p>
        </div>

        @if ($errors->any())
        <div class="mb-4 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="/login">
            @csrf

            <div class="mb-4">
                <label
                    for="email"
                    class="block text-sm font-medium mb-1">
                    Correo electrónico
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full rounded-lg border-gray-300">
            </div>

            <div class="mb-4">
                <label
                    for="password"
                    class="block text-sm font-medium mb-1">
                    Contraseña
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="w-full rounded-lg border-gray-300">
            </div>

            <label class="flex items-center gap-2 mb-6 text-sm">
                <input
                    type="checkbox"
                    name="remember">

                Recordarme
            </label>

            <button
                type="submit"
                class="w-full rounded-lg bg-gray-900 px-4 py-3 text-white font-medium">
                Iniciar sesión
            </button>
        </form>

    </div>

</body>

</html>