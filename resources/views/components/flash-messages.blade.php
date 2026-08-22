@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: '¡Listo!',
            text: @json(session('success')),
            confirmButtonText: 'Aceptar',
        });
    });
</script>
@endif

@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: 'Ocurrió un problema',
            text: @json(session('error')),
            confirmButtonText: 'Aceptar',
        });
    });
</script>
@endif

@if (session('warning'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: @json(session('warning')),
            confirmButtonText: 'Aceptar',
        });
    });
</script>
@endif

@if (session('info'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'info',
            title: 'Información',
            text: @json(session('info')),
            confirmButtonText: 'Aceptar',
        });
    });
</script>
@endif