# DocTotal — TODO

Progreso general aproximado:

`█████████░░░░░░░░░░░ 45%`

El porcentaje representa el avance funcional aproximado del producto completo.

No representa únicamente la cantidad de DTs terminados.

Baseline actual:

`445 tests verdes`

Último bloque completado:

`DT-11 — Subscription lifecycle foundation`

Próximo bloque recomendado:

`DT-12 — Payments, billing recovery and automatic account lifecycle`

---

## Objetivos principales de DocTotal

DocTotal se construye alrededor de tres objetivos principales.

### 1. Operación clínica

Permitir que médicos y consultorios administren:

- Pacientes.
- Agenda.
- Citas.
- Consultas.
- Diagnósticos.
- Recetas.
- Expediente clínico.
- Documentos.
- Estudios.
- Seguimiento clínico.

### 2. Autoadministración SaaS

DocTotal debe poder administrar automáticamente:

- Periodos de prueba.
- Suscripciones.
- Pagos mensuales.
- Pagos anuales.
- Renovaciones.
- Pagos fallidos.
- Reintentos.
- Periodos de gracia.
- Suspensiones.
- Reactivaciones.
- Cancelaciones.
- Referidos.
- Promociones.
- Créditos.

El objetivo es reducir al mínimo la administración manual necesaria para operar DocTotal como SaaS.

### 3. Experiencia de producto

DocTotal debe ser visualmente agradable para médicos y consultorios.

La aplicación funcional actual deberá evolucionar hacia una interfaz:

- Moderna.
- Limpia.
- Profesional.
- Consistente.
- Fácil de utilizar.
- Visualmente propia de DocTotal.

Debe reducirse la apariencia genérica de Livewire.

---

## Base técnica

- [x] Arquitectura multi-tenant.
- [x] Tenant como unidad principal de aislamiento.
- [x] TenantContext.
- [x] TenantScope.
- [x] Resolución de tenant por request.
- [x] Middleware de tenant.
- [x] Protección contra acceso cross-tenant.
- [x] UUIDs.
- [x] Soft deletes donde corresponde.
- [x] Autenticación.
- [x] Registro.
- [x] Onboarding.
- [x] Dashboard base.
- [x] Suite automatizada de tests.
- [ ] Auditoría global de permisos y autorización.
- [ ] Logging estructurado.
- [ ] Auditoría de acciones críticas.
- [ ] Estrategia de backups.
- [ ] Estrategia de almacenamiento privado.
- [ ] Monitoreo de producción.

---

## Pacientes

- [x] Alta de pacientes.
- [x] Listado.
- [x] Edición.
- [x] Detalle.
- [x] Contacto de emergencia.
- [x] Antecedentes médicos iniciales.
- [x] Integración con consultas.
- [x] Protección multi-tenant.
- [ ] Completar expediente longitudinal.
- [ ] Resumen clínico.
- [ ] Línea de tiempo clínica.
- [ ] Archivos clínicos.
- [ ] Documentos.
- [ ] Laboratorios.
- [ ] Radiografías.
- [ ] Imágenes médicas.

---

## Agenda y citas

- [x] Agenda.
- [x] Creación de citas.
- [x] Creación rápida de pacientes.
- [x] Horarios de atención.
- [x] Excepciones de horario.
- [x] Bloqueos parciales.
- [x] Bloqueos completos.
- [x] Horarios extraordinarios.
- [x] Prevención de solapamientos.
- [x] Eliminación de slots pasados.
- [x] Vista mensual.
- [x] Vista semanal.
- [x] Vista diaria.
- [x] Dashboard dinámico.
- [x] Confirmación.
- [x] Check-in.
- [x] Inicio de consulta.
- [x] Cancelación.
- [x] No-show.
- [x] Periodo de gracia de 15 minutos.
- [x] Reprogramación.
- [x] Edición de motivo.
- [x] Edición de notas.
- [x] Appointment → Consultation.
- [x] Continuar consulta en progreso.
- [x] Finalización coordinada.
- [x] Lifecycle completo.
- [x] Protección multi-tenant.

---

## Consultas

- [x] Consultation persistente.
- [x] Estado `draft`.
- [x] Estado `completed`.
- [x] Consulta desde Appointment.
- [x] Consulta sin cita.
- [x] Una Consultation por Appointment.
- [x] Continuar consulta.
- [x] Edición mientras está en draft.
- [x] Captura clínica.
- [x] Signos vitales.
- [x] Información subjetiva.
- [x] Información objetiva.
- [x] Assessment.
- [x] Plan.
- [x] Diagnósticos.
- [x] Diagnóstico principal.
- [x] Catálogo de diagnósticos.
- [x] Autocomplete de diagnósticos.
- [x] Finalización explícita.
- [x] Finalización coordinada con Appointment.
- [x] Historial de consultas.
- [x] Protección multi-tenant.
- [ ] Autosave.
- [ ] Plantillas clínicas.
- [ ] Plantillas por especialidad.
- [ ] Firma clínica/digital.

---

## Recetas

- [x] Prescription.
- [x] PrescriptionItem.
- [x] Relación con Consultation.
- [x] Relación con Patient.
- [x] Relación con DoctorProfile.
- [x] Catálogo de medicamentos.
- [x] Autocomplete.
- [x] Creación.
- [x] Edición.
- [x] Cancelación.
- [x] PDF.
- [x] Protección multi-tenant.
- [ ] Plantillas de receta.
- [ ] Historial mejorado.
- [ ] Firma.
- [ ] Envío electrónico.
- [ ] Compartir receta con paciente.

---

## Expediente clínico longitudinal

Existe una foundation mediante `PatientMedicalHistory`.

Antes de crear nuevas estructuras se deberá auditar qué información ya existe y reutilizarla.

- [x] Base `PatientMedicalHistory`.
- [x] Información clínica inicial.
- [ ] Auditar campos actuales contra expediente objetivo.
- [ ] Antecedentes personales patológicos.
- [ ] Antecedentes personales no patológicos.
- [ ] Antecedentes heredofamiliares.
- [ ] Antecedentes quirúrgicos.
- [ ] Hospitalizaciones previas.
- [ ] Alergias estructuradas.
- [ ] Medicamentos habituales.
- [ ] Enfermedades y condiciones crónicas.
- [ ] Grupo sanguíneo.
- [ ] Notas clínicas relevantes.
- [ ] Resumen clínico del paciente.
- [ ] Línea de tiempo clínica.
- [ ] Integrar consultas en timeline.
- [ ] Integrar diagnósticos en timeline.
- [ ] Integrar recetas en timeline.
- [ ] Integrar documentos en timeline.

---

## Archivos clínicos

Actualmente DocTotal no cuenta con almacenamiento de archivos clínicos.

- [ ] Diseñar modelo de archivo clínico.
- [ ] Storage privado.
- [ ] Upload seguro.
- [ ] PDFs.
- [ ] Laboratorios.
- [ ] Radiografías.
- [ ] Imágenes médicas.
- [ ] Documentos externos.
- [ ] Tipo de documento.
- [ ] Fecha del estudio/documento.
- [ ] Descripción.
- [ ] Metadata.
- [ ] Asociación con Patient.
- [ ] Asociación opcional con Consultation.
- [ ] Descarga autorizada.
- [ ] Eliminación controlada.
- [ ] Límites de almacenamiento por tenant.
- [ ] Protección multi-tenant.
- [ ] Tests de aislamiento.
- [ ] Tests de autorización.

---

## Trial

- [x] `trial_started_at`.
- [x] `trial_ends_at`.
- [x] Trial creado durante registro.
- [x] `isOnTrial()`.
- [x] `trialHasExpired()`.
- [x] Trial integrado con reglas de acceso.
- [ ] Avisos próximos a expiración.
- [ ] Conversión trial → suscripción desde UI.
- [ ] Emails de expiración.
- [ ] Pantalla de trial expirado.

---

## Suscripciones — DT-11

- [x] Modelo Subscription.
- [x] UUID.
- [x] Soft deletes.
- [x] Tenant → subscriptions.
- [x] Protección multi-tenant.
- [x] Billing cycle monthly.
- [x] Billing cycle yearly.
- [x] Status active.
- [x] Status past_due.
- [x] Status cancelled.
- [x] `starts_at`.
- [x] `current_period_starts_at`.
- [x] `current_period_ends_at`.
- [x] `next_billing_at`.
- [x] `cancel_at_period_end`.
- [x] `cancelled_at`.
- [x] Activación.
- [x] Primer pago como billing anchor.
- [x] Conservación de fecha y hora.
- [x] Conservación de minutos y segundos.
- [x] Renovación mensual.
- [x] Renovación anual.
- [x] Protección contra billing drift.
- [x] Manejo de fin de mes.
- [x] Manejo de años bisiestos.
- [x] Cancelación programada.
- [x] Procesamiento al final exacto del periodo.
- [x] `active → past_due`.
- [x] `past_due → active`.
- [x] Cancelled como estado terminal actual.
- [x] Prevención de segunda Subscription active.
- [x] Prevención de segunda Subscription cuando existe past_due.
- [x] Validación de inicio del periodo.
- [x] Validación de fin exacto del periodo.
- [x] Acceso con past_due durante recuperación.
- [x] Suspensión del Tenant independiente de past_due.
- [x] Separación Subscription/Tenant/access.
- [x] Cobertura automatizada.

Baseline al cierre:

`445 tests verdes`

---

## Billing y pagos — DT-12

### Diseño de pagos

- [ ] Elegir proveedor/pasarela de pagos.
- [ ] Definir estrategia de integración.
- [ ] Definir modelo Payment.
- [ ] Definir si PaymentAttempt será entidad independiente.
- [ ] Definir identificador externo del pago.
- [ ] Definir identificador externo de customer.
- [ ] Definir identificador externo de subscription si aplica.
- [ ] Registrar monto.
- [ ] Registrar moneda.
- [ ] Registrar fecha/hora.
- [ ] Registrar estado.
- [ ] Registrar método de pago.

### Cobros

- [ ] Cobro mensual.
- [ ] Cobro anual.
- [ ] Cobro usando `next_billing_at`.
- [ ] Pago exitoso → registrar Payment.
- [ ] Pago exitoso → renovar Subscription.
- [ ] Mantener billing anchor.
- [ ] Evitar doble renovación.
- [ ] Evitar doble cobro.

### Webhooks

- [ ] Endpoint de webhook.
- [ ] Validación de firma.
- [ ] Idempotencia.
- [ ] Registro de eventos.
- [ ] Protección contra eventos duplicados.
- [ ] Manejo de eventos fuera de orden.
- [ ] Tests de webhooks.

### Pago fallido

- [ ] Pago fallido → Subscription past_due.
- [ ] Registrar intento fallido.
- [ ] Definir duración del grace period.
- [ ] Definir número de reintentos.
- [ ] Definir calendario de reintentos.
- [ ] Mantener acceso durante grace period.
- [ ] Recuperación de pago.
- [ ] `past_due → active`.
- [ ] Agotar recuperación → Tenant suspended.
- [ ] Registrar `suspended_at`.

### Reactivación

- [ ] Pago recuperado → Subscription active.
- [ ] Tenant suspended → active.
- [ ] Limpiar `suspended_at`.
- [ ] Restaurar acceso.
- [ ] Evitar creación innecesaria de otra Subscription.

### Automatización

- [ ] Scheduler.
- [ ] Jobs.
- [ ] Commands administrativos.
- [ ] Procesamiento de renovaciones.
- [ ] Procesamiento de pagos fallidos.
- [ ] Procesamiento de reintentos.
- [ ] Procesamiento de suspensiones.
- [ ] Procesamiento de cancelaciones pendientes.
- [ ] Auditoría de billing.

### UI de billing

- [ ] Estado actual de suscripción.
- [ ] Plan mensual/anual.
- [ ] Próxima fecha y hora de cobro.
- [ ] Método de pago.
- [ ] Actualizar método de pago.
- [ ] Historial de pagos.
- [ ] Cancelar suscripción.
- [ ] Aviso de cancelación programada.
- [ ] Reactivar cancelación programada si aplica.
- [ ] Mostrar past_due.
- [ ] Mostrar grace period.
- [ ] Mostrar suspensión.

### Calidad de billing

- [ ] Tests de Payment.
- [ ] Tests de cobro.
- [ ] Tests de renovación.
- [ ] Tests de pago fallido.
- [ ] Tests de grace period.
- [ ] Tests de suspensión.
- [ ] Tests de recuperación.
- [ ] Tests de idempotencia.
- [ ] Tests de eventos duplicados.
- [ ] Tests de concurrencia.

---

## Referidos y promociones — DT-13

### Código

- [ ] Código único por tenant.
- [ ] Código fácil de compartir.
- [ ] Garantizar unicidad.
- [ ] Generación automática.

### Uso

- [ ] Captura exclusivamente durante primera inscripción.
- [ ] Un único código máximo por tenant nuevo.
- [ ] El código solamente puede utilizarse una vez por tenant nuevo.
- [ ] No permitir agregar código posteriormente.
- [ ] Si no se utiliza durante registro, la oportunidad se pierde permanentemente.
- [ ] Registrar tenant referidor.
- [ ] Registrar tenant referido.
- [ ] Registrar fecha/hora del uso.

### Límites

- [ ] Límite configurable de usos/recompensas válidos por mes.
- [ ] Regla inicial propuesta: primeros 5 usos válidos por mes.
- [ ] Reinicio mensual del límite.
- [ ] No premiar usos que excedan el límite.
- [ ] Mantener trazabilidad aunque el uso no genere beneficio.

### Crédito promocional

- [ ] Definir monto/regla del crédito.
- [ ] Ledger de créditos.
- [ ] Crédito pendiente.
- [ ] Crédito aplicado.
- [ ] Crédito expirado si posteriormente se requiere.
- [ ] Integración con billing.
- [ ] Aplicación automática al siguiente cobro.

### Antifraude

- [ ] Evitar autorreferidos.
- [ ] Evitar reutilización.
- [ ] Evitar múltiples códigos por tenant.
- [ ] Evitar doble recompensa.
- [ ] Auditoría de promociones.
- [ ] Tests.

### UI

- [ ] Mostrar código propio.
- [ ] Botón copiar.
- [ ] Compartir código.
- [ ] Mostrar referidos.
- [ ] Mostrar usos válidos del mes.
- [ ] Mostrar créditos obtenidos.
- [ ] Mostrar créditos aplicados.

---

## Autoadministración SaaS

- [x] Trial foundation.
- [x] Subscription foundation.
- [x] Ciclo mensual.
- [x] Ciclo anual.
- [x] Estados comerciales básicos.
- [x] Concepto de suspensión del Tenant.
- [x] Derecho de acceso centralizado.
- [x] Cancelación al final del periodo a nivel de dominio.
- [ ] Pagos reales.
- [ ] Cobros automáticos.
- [ ] Reintentos.
- [ ] Grace period.
- [ ] Suspensión automática.
- [ ] Reactivación automática.
- [ ] Cancelación desde UI.
- [ ] Cambio mensual ↔ anual.
- [ ] Administración del método de pago.
- [ ] Emails transaccionales.
- [ ] Recordatorios.
- [ ] Panel de cuenta.
- [ ] Eliminación programada de cuenta.
- [ ] Política de retención.
- [ ] Créditos/promociones.
- [ ] Referidos.
- [ ] Herramientas internas de soporte/admin.

---

## Diseño y UX — DT-16

### Design system

- [ ] Definir identidad visual de DocTotal.
- [ ] Tipografía.
- [ ] Escala de espacios.
- [ ] Paleta.
- [ ] Bordes.
- [ ] Sombras.
- [ ] Estados interactivos.
- [ ] Iconografía.

### Componentes

- [ ] Botones.
- [ ] Inputs.
- [ ] Textareas.
- [ ] Selects.
- [ ] Autocomplete.
- [ ] Checkboxes.
- [ ] Radios.
- [ ] Cards.
- [ ] Tablas.
- [ ] Badges.
- [ ] Tabs.
- [ ] Modales.
- [ ] Dropdowns.
- [ ] Alerts.
- [ ] Toasts.
- [ ] Empty states.
- [ ] Loading states.
- [ ] Skeletons.
- [ ] Confirmaciones.

### Pantallas

- [ ] Login.
- [ ] Registro.
- [ ] Onboarding.
- [ ] Dashboard.
- [ ] Agenda.
- [ ] Crear cita.
- [ ] Detalle de cita.
- [ ] Pacientes.
- [ ] Alta de paciente.
- [ ] Detalle del paciente.
- [ ] Expediente clínico.
- [ ] Consulta.
- [ ] Diagnósticos.
- [ ] Recetas.
- [ ] PDF/impresión donde corresponda.
- [ ] Configuración.
- [ ] Suscripción.
- [ ] Billing.
- [ ] Referidos.

### Calidad visual

- [ ] Responsive.
- [ ] Mobile.
- [ ] Tablet.
- [ ] Desktop.
- [ ] Accesibilidad.
- [ ] Navegación consistente.
- [ ] Feedback inmediato.
- [ ] Estados de error.
- [ ] Estados vacíos.
- [ ] Consistencia global.
- [ ] Reducir/eliminar apariencia genérica de Livewire.

---

## Comunicaciones

- [ ] Emails transaccionales.
- [ ] Confirmación de registro.
- [ ] Avisos de trial.
- [ ] Avisos próximos al vencimiento del trial.
- [ ] Confirmaciones de cita.
- [ ] Recordatorios de cita.
- [ ] Avisos de cancelación.
- [ ] Avisos de pago.
- [ ] Avisos de pago fallido.
- [ ] Avisos de reintento.
- [ ] Avisos de suspensión.
- [ ] Avisos de reactivación.
- [ ] WhatsApp.
- [ ] SMS.

---

## Seguridad y cumplimiento

- [x] Aislamiento multi-tenant base.
- [x] Autenticación.
- [x] Protección cross-tenant probada.
- [ ] Auditoría de autorización por recurso.
- [ ] Roles y permisos según crecimiento del producto.
- [ ] Audit log clínico.
- [ ] Audit log comercial.
- [ ] Protección de archivos clínicos.
- [ ] Política de sesiones.
- [ ] Rate limiting donde corresponda.
- [ ] Protección de endpoints de billing.
- [ ] Validación de webhooks.
- [ ] Política de retención.
- [ ] Exportación de datos.
- [ ] Eliminación controlada.
- [ ] Revisión legal/regulatoria antes de producción.

---

## Calidad

- [x] Suite automatizada.
- [x] Tests de tenancy.
- [x] Tests de pacientes.
- [x] Tests de agenda.
- [x] Tests de citas.
- [x] Tests de consultas.
- [x] Tests de diagnósticos.
- [x] Tests de recetas.
- [x] Tests de trial.
- [x] Tests de Subscription.
- [x] Tests de activación.
- [x] Tests de renovación.
- [x] Tests de billing anchor.
- [x] Tests de fin de mes.
- [x] Tests de año bisiesto.
- [x] Tests de cancelación programada.
- [x] Tests de transiciones de Subscription.
- [x] Tests de acceso del Tenant.
- [x] 445 tests verdes al cierre de DT-11.
- [ ] CI automático.
- [ ] Tests de pagos.
- [ ] Tests de webhooks.
- [ ] Tests de idempotencia.
- [ ] Tests de concurrencia.
- [ ] Tests de referidos.
- [ ] Tests de archivos clínicos.
- [ ] Tests end-to-end de workflows críticos.

---

## Estado actual

Completado:

- [x] DT-1
- [x] DT-2
- [x] DT-3
- [x] DT-4
- [x] DT-5
- [x] DT-6
- [x] DT-7
- [x] DT-8
- [x] DT-9
- [x] DT-10
- [x] DT-11

Pendiente:

- [ ] DT-12 — Payments, billing recovery and automatic account lifecycle
- [ ] DT-13 — Referral and promotional credit system
- [ ] DT-14 — Expediente clínico longitudinal
- [ ] DT-15 — Clinical files and medical documents
- [ ] DT-16 — Visual redesign / DocTotal UI

---

## Siguiente decisión

Continuar con:

`DT-12 — Payments, billing recovery and automatic account lifecycle`

Objetivo inmediato:

Convertir la foundation de Subscription construida en DT-11 en un sistema de billing capaz de operar DocTotal automáticamente.

Baseline de inicio de DT-12:

`445 tests verdes`