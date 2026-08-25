# DocTotal Roadmap

Este documento resume los bloques de trabajo principales del proyecto DocTotal.

## DT-1 — Base multi-tenant database structure

Estado: Completado

Objetivo:
Construir la base de datos inicial con soporte multi-tenant.

Incluye:
- Estructura inicial multi-tenant.
- Tenant como entidad principal de aislamiento.
- Relaciones base necesarias para el resto del sistema.

Commit principal:
`DT-1 Implement base multi-tenant database structure`

---

## DT-2 — Core Eloquent models and relationships

Estado: Completado

Objetivo:
Implementar los modelos Eloquent principales y sus relaciones.

Incluye:
- Modelos base del dominio.
- Relaciones entre entidades.
- Preparación para aislamiento por tenant.

Commit principal:
`DT-2 Implement core Eloquent models and relationships`

---

## DT-3 — Tenant isolation and request resolution

Estado: Completado

Objetivo:
Garantizar aislamiento entre tenants y resolver el tenant activo por request.

Incluye:
- TenantContext.
- Resolución del tenant actual.
- Protección contra acceso cruzado entre tenants.
- Middleware de tenant.

Commit principal:
`DT-3 Implement tenant isolation and request resolution`

---

## DT-4 — Patient clinical record foundation

Estado: Completado

Objetivo:
Construir la base del expediente clínico del paciente.

Incluye:
- Pacientes.
- Base de consultas.
- Fundamentos del expediente clínico.

Commit principal:
`DT-4 Implement patient clinical record foundation`

---

## DT-5 — Authentication, registration, dashboard and trial

Estado: Completado

Objetivo:
Implementar autenticación, registro y flujo inicial del usuario.

Incluye:
- Registro.
- Inicio de sesión.
- Dashboard inicial.
- Trial.
- Asociación del usuario con su tenant.

Commit principal:
`DT-5 Implement authentication registration dashboard and trial`

---

## DT-6 — Onboarding wizard and postal code autocomplete

Estado: Completado

Objetivo:
Construir el onboarding inicial del médico y consultorio.

Incluye:
- Wizard de onboarding.
- Perfil del consultorio.
- Perfil médico.
- Especialidad.
- Horarios de atención.
- Autocompletado por código postal.

Commit principal:
`DT-6 feat: implement onboarding wizard with postal code autocomplete`

---

## DT-7 — Gestión de pacientes

Estado: Completado

Objetivo:
Construir el flujo principal de pacientes y expediente.

Incluye:
- Listado de pacientes.
- Alta de pacientes.
- Edición.
- Detalle del paciente.
- Expediente clínico.
- Integración con consultas.

Commit principal:
`DT-7 Implement gestión de pacientes`

---

## DT-8 — Agenda, citas y ciclo operativo

Estado: Completado

Objetivo:
Construir el sistema completo de agenda y citas.

Incluye:
- Creación de citas.
- Creación rápida de pacientes desde una cita.
- Disponibilidad basada en horarios del onboarding.
- Excepciones de horario.
- Bloqueos parciales y completos.
- Horarios extraordinarios.
- Prevención de solapamientos.
- Eliminación de slots pasados.
- Agenda por mes, semana y día.
- Dashboard dinámico.
- Estados de Appointment.
- Confirmación.
- Check-in.
- Inicio de consulta.
- Finalización automática de cita.
- Cancelación.
- No-show semiautomático.
- Periodo de gracia de 15 minutos para no-show.
- Reprogramación.
- Edición de motivo y notas.
- Integración Appointment → Consultation.
- Continuar consulta cuando la cita queda en progreso.
- Aislamiento multi-tenant.
- Cobertura completa con tests.

Estados de Appointment:

- scheduled
- confirmed
- checked_in
- in_progress
- completed
- cancelled
- no_show

Regla de no-show:

Una cita solo puede marcarse como no-show cuando:

`now() >= ends_at + 15 minutos`

El cambio a no-show nunca es automático. La acción debe ser confirmada por el usuario.

Commits principales:

- `DT-8 feat: complete appointment scheduling and dashboard`
- `DT-8 feat: finalize appointment workflow and lifecycle`
- `DT-8 feat: finalize appointment workflow and lifecycle`

Baseline al cerrar DT-8:

`366 tests verdes`

---

## DT-9 — Consultation workflow and clinical lifecycle

Estado: En desarrollo

Objetivo:
Convertir Consultation en una entidad clínica persistente desde el inicio de la atención.

Flujo objetivo:

Appointment
→ Iniciar consulta
→ Appointment = in_progress
→ Consultation = draft
→ Captura clínica
→ Guardar avances
→ Continuar posteriormente
→ Finalizar consulta
→ Consultation = completed
→ Appointment = completed

Alcance inicial:

- Estados de Consultation.
- `draft`.
- `completed`.
- Crear Consultation al iniciar una cita.
- Una sola Consultation por Appointment.
- Continuar una Consultation existente.
- Editar consulta mientras está en draft.
- Finalización explícita de Consultation.
- Completar Appointment al finalizar Consultation.
- Diagnósticos durante la consulta.
- Recetas asociadas.
- Historial clínico.
- Protección multi-tenant.
- Tests del ciclo clínico.

Fuera de alcance inicial:

- Autosave.
- WhatsApp.
- SMS.
- Recordatorios externos.
- Firma digital.
- Archivos clínicos.
- Laboratorios.
- Imágenes médicas.
- Plantillas por especialidad.