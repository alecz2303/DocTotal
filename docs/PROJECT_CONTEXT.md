# DocTotal — Project Context

Documento de contexto técnico y funcional del proyecto.

Su propósito es mantener continuidad entre sesiones, chats y nuevos bloques de desarrollo.

---

# Stack

Backend:
- PHP
- Laravel 13

Frontend:
- Blade
- Livewire
- Volt-style single-file Livewire components
- Tailwind CSS

Base de datos:
- MySQL en desarrollo/producción
- SQLite in-memory en tests

Testing:
- PHPUnit
- Laravel Feature Tests
- Livewire component tests

---

# Convenciones del proyecto

## Livewire

Los componentes se crean con Artisan.

Ejemplo:

```bash
php artisan make:livewire pages::appointments.reschedule