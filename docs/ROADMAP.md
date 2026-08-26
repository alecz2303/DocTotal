# DocTotal — ROADMAP

Este documento define los bloques principales de desarrollo de DocTotal y sirve como visión de alto nivel del producto.

DocTotal tiene tres objetivos principales:

1. **Operación clínica**
   - Pacientes.
   - Agenda.
   - Citas.
   - Consultas.
   - Diagnósticos.
   - Recetas.
   - Expediente clínico longitudinal.
   - Documentos y archivos clínicos.

2. **Autoadministración SaaS**
   - Trial.
   - Suscripciones.
   - Pagos.
   - Renovaciones.
   - Recuperación de pagos.
   - Suspensión.
   - Reactivación.
   - Cancelación.
   - Referidos.
   - Promociones.
   - Créditos.

3. **Experiencia de producto**
   - Convertir la interfaz funcional actual en una experiencia moderna, profesional, consistente y agradable para médicos y consultorios.
   - Reducir la apariencia genérica de Livewire.
   - Construir una identidad visual propia de DocTotal.

---

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

Construir la base inicial del expediente clínico del paciente.

Incluye:

- Pacientes.
- Contactos de emergencia.
- Antecedentes médicos iniciales.
- Base de consultas.
- Fundamentos del expediente clínico.

Nota:

DT-4 constituye únicamente la foundation inicial.

El expediente clínico longitudinal completo continúa pendiente en bloques posteriores.

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
- `trial_started_at`.
- `trial_ends_at`.
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
- Antecedentes médicos.
- Contactos de emergencia.
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

- `scheduled`
- `confirmed`
- `checked_in`
- `in_progress`
- `completed`
- `cancelled`
- `no_show`

Regla de no-show:

Una cita solamente puede marcarse como no-show cuando:

`now() >= ends_at + 15 minutos`

El cambio a no-show nunca es automático.

La acción debe ser confirmada por el usuario.

Baseline al cierre de DT-8:

`366 tests verdes`

---

## DT-9 — Consultation workflow and clinical lifecycle

Estado: Completado

Objetivo:

Convertir Consultation en una entidad clínica persistente desde el inicio de la atención.

Flujo:

Appointment

→ Iniciar consulta

→ Appointment = `in_progress`

→ Consultation = `draft`

→ Captura clínica

→ Guardar avances

→ Continuar posteriormente

→ Finalizar consulta

→ Consultation = `completed`

→ Appointment = `completed`

Incluye:

- Estado `draft`.
- Estado `completed`.
- Crear Consultation al iniciar una cita.
- Una sola Consultation por Appointment.
- Consultas sin cita.
- Continuar una Consultation existente.
- Editar consulta mientras está en draft.
- Finalización explícita de Consultation.
- Completar Appointment al finalizar Consultation.
- Diagnósticos durante la consulta.
- Diagnóstico principal.
- Catálogo de diagnósticos.
- Autocomplete de diagnósticos.
- Recetas asociadas.
- Catálogo de medicamentos.
- Autocomplete de medicamentos.
- Edición de recetas.
- Cancelación de recetas.
- PDF de receta.
- Historial de consultas.
- Protección multi-tenant.
- Tests del ciclo clínico.

Baseline al cierre de DT-9:

`393 tests verdes`

---

## DT-10 — Product inventory, roadmap and TODO

Estado: Completado

Objetivo:

Realizar un inventario general de DocTotal antes de continuar desarrollando funcionalidades aisladas.

DT-10 establece una visión completa del producto y permite decidir qué desarrollar posteriormente según prioridades reales.

Incluye:

- Inventario funcional.
- Inventario clínico.
- Inventario SaaS.
- Inventario visual.
- Identificación de funcionalidades existentes.
- Identificación de funcionalidades parciales.
- Identificación de funcionalidades inexistentes.
- Definición de los tres pilares principales de DocTotal.
- Creación de ROADMAP.
- Creación de TODO.
- Identificación del expediente clínico longitudinal como bloque futuro.
- Identificación de archivos clínicos como bloque independiente.
- Identificación del billing como bloque crítico.
- Definición inicial del sistema de referidos.
- Identificación del rediseño visual como bloque estratégico.

---

## DT-11 — Subscription lifecycle foundation

Estado: Completado

Objetivo:

Construir el dominio base de suscripciones necesario para que DocTotal pueda administrar posteriormente de forma automática el ciclo económico de cada tenant.

Incluye:

- Modelo `Subscription`.
- Relación Tenant → Subscriptions.
- Protección multi-tenant.
- UUID de Subscription.
- Soft deletes.

Ciclos de facturación:

- `monthly`
- `yearly`

Estados de Subscription:

- `active`
- `past_due`
- `cancelled`

Información temporal:

- `starts_at`
- `current_period_starts_at`
- `current_period_ends_at`
- `next_billing_at`
- `cancel_at_period_end`
- `cancelled_at`

### Activación

La suscripción comienza exactamente en el instante del primer pago.

Ejemplo:

`2026-08-26 16:37:22`

La fecha y hora del primer pago se convierten en el billing anchor original.

`starts_at` permanece como referencia original de la suscripción.

### Renovación

Se implementó renovación para:

- Suscripciones mensuales.
- Suscripciones anuales.

La renovación conserva:

- Día original cuando existe.
- Hora.
- Minuto.
- Segundo.

Se evita billing drift.

Ejemplo mensual:

`31 enero → 28 febrero → 31 marzo → 30 abril → 31 mayo`

El paso por un mes corto no modifica permanentemente el día original de facturación.

### Fin de mes

Las renovaciones utilizan el último día válido del mes cuando el día original no existe.

### Años bisiestos

Las suscripciones anuales iniciadas el 29 de febrero utilizan el último día válido de febrero en años no bisiestos y pueden recuperar el 29 cuando vuelva a existir.

### Cancelación programada

Se soporta:

`cancel_at_period_end = true`

Esto significa que el tenant conserva acceso hasta:

`current_period_ends_at`

La suscripción no se cancela antes del final exacto del periodo.

Al finalizar:

- `status = cancelled`
- `cancel_at_period_end = false`
- `cancelled_at = now()`
- `next_billing_at = null`

### Estados comerciales

Transiciones implementadas:

`active → past_due`

`past_due → active`

`active → cancelled`

`cancelled` se considera estado terminal para el lifecycle actual.

### Protección contra múltiples suscripciones

Un tenant no puede recibir una nueva suscripción mientras exista una suscripción abierta en estado:

- `active`
- `past_due`

Una suscripción `past_due` debe recuperarse/reactivarse.

No debe crearse otra Subscription para sustituirla.

### Subscription vs Tenant

Se separaron explícitamente dos conceptos.

Subscription representa el estado comercial:

- `active`
- `past_due`
- `cancelled`

Tenant representa el estado operativo de la cuenta:

- `trial`
- `active`
- `suspended`
- `cancelled`

Por tanto:

`past_due != suspended`

Una falla de cobro no implica suspensión inmediata.

### Acceso al servicio

Reglas actuales:

Trial vigente:

`acceso permitido`

Subscription active con periodo vigente:

`acceso permitido`

Subscription past_due con periodo vigente y Tenant activo:

`acceso permitido`

Subscription past_due con Tenant suspended:

`acceso denegado`

Subscription cancelled:

`acceso denegado`

Periodo de suscripción terminado:

`acceso denegado`

Tenant cancelled:

`acceso denegado`

### Vigencia temporal

Una Subscription solamente es vigente cuando:

`current_period_starts_at <= now()`

y:

`current_period_ends_at > now()`

Una suscripción que comienza en el futuro todavía no es vigente.

Una suscripción cuyo `current_period_ends_at` es exactamente igual a `now()` ya no es vigente.

### Fuera de alcance de DT-11

Se dejan expresamente para DT-12:

- Pagos reales.
- Payment.
- Intentos de cobro.
- Proveedor de pagos.
- Webhooks.
- Idempotencia.
- Grace period.
- Reintentos.
- Suspensión automática.
- Reactivación automática por pago.
- Scheduler.
- Jobs de billing.
- Notificaciones comerciales.

Baseline al cierre de DT-11:

`445 tests verdes`

Commit sugerido:

`feat: implement subscription lifecycle foundation DT-11`

---

## DT-12 — Payments, billing recovery and automatic account lifecycle

Estado: Pendiente

Objetivo:

Conectar la foundation de suscripciones de DT-11 con pagos reales y permitir que DocTotal administre automáticamente el ciclo económico de cada tenant.

Alcance previsto:

- Entidad Payment.
- Registro de pagos.
- Intentos de cobro.
- Integración con proveedor de pagos.
- Identificadores externos.
- Webhooks.
- Idempotencia.
- Cobro mensual.
- Cobro anual.
- Renovación después de pago exitoso.
- Manejo de pagos fallidos.
- Cambio `active → past_due`.
- Periodo de gracia.
- Política de reintentos.
- Suspensión automática.
- Cambio de Tenant a `suspended`.
- Recuperación de pagos.
- Reactivación automática.
- Scheduler.
- Jobs.
- Commands administrativos.
- Auditoría de eventos de billing.
- Protección contra cobros duplicados.

Flujo objetivo:

`next_billing_at`

→ Intento de cobro

Si el pago es exitoso:

→ registrar Payment

→ renovar Subscription

→ mantener `active`

Si el pago falla:

→ registrar intento

→ Subscription = `past_due`

→ iniciar periodo de gracia

→ realizar reintentos

Si se recupera:

→ Subscription = `active`

→ Tenant = `active`

Si se agota la recuperación:

→ Tenant = `suspended`

---

## DT-13 — Referral and promotional credit system

Estado: Pendiente

Objetivo:

Implementar adquisición orgánica mediante códigos únicos de referido por tenant.

Reglas definidas:

- Cada tenant tendrá un código único.
- El tenant podrá compartir su código.
- El código solamente podrá utilizarse durante la primera inscripción de un nuevo tenant.
- Cada nuevo tenant podrá utilizar un único código.
- Un tenant nuevo no podrá utilizar más de un código.
- Si el usuario no utiliza un código durante su inscripción inicial, pierde la oportunidad permanentemente.
- El código no podrá agregarse posteriormente.
- Existirá un límite configurable de beneficios válidos por código durante cada mes.
- La regla inicial prevista es permitir únicamente los primeros 5 usos/recompensas válidos por mes.
- El límite deberá poder modificarse posteriormente sin cambiar el dominio.
- La recompensa será crédito/promoción dentro de DocTotal.
- Debe existir trazabilidad del tenant referidor y tenant referido.
- Deben prevenirse autorreferidos.
- Debe prevenirse reutilización.
- Debe prevenirse doble beneficio.
- Los créditos deberán integrarse con el sistema de billing.

---

## DT-14 — Expediente clínico longitudinal

Estado: Pendiente

Objetivo:

Convertir la información clínica existente del paciente en un expediente clínico longitudinal completo.

Importante:

DocTotal ya cuenta con una base mediante `PatientMedicalHistory`.

Este bloque debe auditar y ampliar la información existente.

No debe crear estructuras duplicadas innecesariamente.

Incluye por revisar y completar:

- Antecedentes personales patológicos.
- Antecedentes personales no patológicos.
- Antecedentes heredofamiliares.
- Antecedentes quirúrgicos.
- Hospitalizaciones previas.
- Alergias.
- Medicamentos habituales.
- Enfermedades y condiciones crónicas.
- Grupo sanguíneo.
- Notas clínicas relevantes.
- Resumen clínico.
- Línea de tiempo clínica.

La línea de tiempo deberá poder combinar posteriormente:

- Consultas.
- Diagnósticos.
- Recetas.
- Documentos.
- Estudios.
- Otros eventos clínicos relevantes.

---

## DT-15 — Clinical files and medical documents

Estado: Pendiente

Objetivo:

Permitir que el expediente clínico almacene documentación clínica real.

Actualmente DocTotal no cuenta con almacenamiento de archivos clínicos, radiografías, estudios o documentos asociados al paciente.

Incluye:

- Archivos adjuntos.
- PDFs.
- Resultados de laboratorio.
- Radiografías.
- Imágenes médicas.
- Documentos externos.
- Clasificación por tipo.
- Fecha del documento o estudio.
- Descripción.
- Asociación con Patient.
- Asociación opcional con Consultation.
- Descarga segura.
- Eliminación controlada.
- Protección multi-tenant.
- Storage privado.
- Límites de almacenamiento por tenant.

---

## DT-16 — Visual redesign / DocTotal UI

Estado: Pendiente

Objetivo:

Transformar la interfaz funcional actual de DocTotal en un producto visualmente profesional, moderno, consistente y agradable para médicos.

La aplicación no debe conservar una apariencia genérica de Livewire.

Incluye:

- Identidad visual.
- Design system.
- Tipografía.
- Escala de espaciado.
- Paleta.
- Botones.
- Inputs.
- Selects.
- Autocomplete.
- Cards.
- Tablas.
- Badges.
- Modales.
- Alerts.
- Toasts.
- Empty states.
- Loading states.
- Skeletons.
- Navegación.
- Dashboard.
- Agenda.
- Pacientes.
- Expediente.
- Consulta.
- Recetas.
- Onboarding.
- Billing.
- Login.
- Registro.
- Responsive.
- Accesibilidad.
- Consistencia visual global.

El rediseño deberá realizarse sin romper los workflows funcionales ya cubiertos por tests.

---

## Estado actual

Bloques completados:

- DT-1
- DT-2
- DT-3
- DT-4
- DT-5
- DT-6
- DT-7
- DT-8
- DT-9
- DT-10
- DT-11

Baseline actual:

`445 tests verdes`

Próximo bloque recomendado:

`DT-12 — Payments, billing recovery and automatic account lifecycle`