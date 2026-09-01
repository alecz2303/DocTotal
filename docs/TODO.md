DocTotal --- TODO

Progreso general

72% completado

██████████████░░░░░░ 72%

El porcentaje representa el avance global ponderado del producto, no la cobertura de tests ni el número de tareas individuales completadas.

Baseline recalculado al inicio de DT-18 después del cierre de DT-17.

El avance se pondera considerando los tres pilares del producto —operación clínica, autoadministración SaaS y experiencia/infraestructura— y debe recalcularse con el mismo criterio durante la normalización documental de cierre de cada DT.

Este documento representa el estado funcional actual de DocTotal y sirve como mapa maestro para decidir los siguientes bloques de desarrollo (DT).

No sustituye al Roadmap.

ROADMAP.md registra los bloques DT realizados, su objetivo y su cierre.

TODO.md registra qué existe actualmente, qué está incompleto, qué falta y qué decisiones de producto siguen pendientes.

A partir de DT-18, la normalización documental se realiza al inicio de cada DT para establecer el baseline real de entrada y nuevamente al cierre para registrar el baseline final.

Objetivo del producto

DocTotal debe convertirse en una plataforma SaaS para médicos y consultorios que cubra tres pilares principales.

1. Operación médica

Permitir administrar la operación diaria y clínica del consultorio:

Pacientes.

Expedientes clínicos.

Agenda.

Citas.

Consultas.

Diagnósticos.

Recetas.

Archivos clínicos.

Estudios.

Laboratorios.

Imágenes médicas.

Historial longitudinal del paciente.

2. Autoadministración SaaS

DocTotal debe administrar automáticamente el ciclo comercial y operativo de cada tenant:

Registro.

Periodo de prueba.

Suscripción.

Mensualidades.

Anualidades.

Pagos.

Renovaciones.

Vencimientos.

Pagos fallidos.

Suspensiones.

Reactivaciones.

Cancelaciones.

Eliminación programada.

Referidos.

Promociones.

Créditos.

El objetivo es minimizar al máximo la intervención manual del administrador de DocTotal.

3. Experiencia de usuario

DocTotal debe sentirse como un producto médico profesional y terminado, no como una aplicación construida directamente con componentes estándar de Laravel/Livewire.

Debe ser:

Visualmente agradable.

Rápido.

Claro.

Consistente.

Fácil de aprender.

Cómodo durante toda la jornada.

Optimizado para el flujo real de trabajo del médico.

Visualmente identificable como DocTotal.

Estados

[x] Implementado.

[~] Implementado parcialmente / requiere mejora.

[ ] No implementado.

[!] Requiere revisión o decisión de producto.

1. Arquitectura y multi-tenancy

Relacionado principalmente con DT-1, DT-2 y DT-3.

Estructura base multi-tenant.

Tenant como unidad principal de aislamiento.

Modelo Tenant.

Modelos Eloquent principales.

Relaciones base.

TenantContext.

TenantScope.

Trait BelongsToTenant.

Middleware ResolveTenant.

Resolución del tenant activo.

Protección contra acceso cruzado.

Aislamiento de pacientes.

Aislamiento de citas.

Aislamiento de consultas.

Aislamiento de recetas.

Cobertura automatizada de tenant isolation.

[!] Mantener aislamiento como requisito obligatorio para todo módulo nuevo.

2. Autenticación y seguridad de cuenta

Relacionado principalmente con DT-5 y DT-16.

Registro y autenticación

Registro de usuario.

Creación automática del tenant.

Asociación usuario → tenant.

Inicio de sesión.

Cierre de sesión.

Infraestructura Laravel Fortify.

Trial creado durante el registro.

Pantalla de registro.

Pantalla de login.

Contraseña y recuperación

Backend para reset de contraseña mediante Fortify.

Backend para actualización de contraseña.

Recuperación de contraseña con UI propia.

Restablecimiento de contraseña con UI propia.

Correo personalizado de restablecimiento de contraseña.

[~] Cambio de contraseña desde configuración.

Seguridad adicional

[~] Two-factor authentication: infraestructura de base de datos existente.

[~] Passkeys: infraestructura de base de datos existente.

Verificar implementación completa de 2FA.

Verificar implementación completa de passkeys.

Verificación de correo.

Administración visible de sesiones.

Revocación de sesiones/dispositivos.

[!] Auditar seguridad completa antes de producción.

3. Onboarding

Relacionado principalmente con DT-6 y DT-16.

Wizard de onboarding.

Datos profesionales.

Datos del consultorio.

Horarios de atención.

Confirmación.

Especialidad.

Cédula profesional.

Datos de contacto.

Dirección.

Código postal.

Servicio y autocompletado por código postal.

Duración predeterminada de citas.

onboarding_completed_at.

Middleware EnsureOnboardingIsComplete.

Tests del wizard y middleware.

Experiencia visual rediseñada.

Mostrar claramente información del periodo de prueba.

Registro preparado para promociones y referidos.

Captura opcional y aplicación automática de código de referido.

[!] Revisar qué información deberá ser obligatoria antes de producción.

4. Pacientes

Relacionado principalmente con DT-4, DT-7, DT-14, DT-15, DT-16 y DT-17.

Modelo Patient.

Listado, búsqueda, alta, edición y detalle.

Datos generales, nacimiento, edad, sexo, grupo sanguíneo y contacto.

Contactos de emergencia y PatientEmergencyContact.

Antecedentes médicos y PatientMedicalHistory.

Historial de consultas.

Integración paciente → consulta.

Expediente clínico longitudinal.

Resumen clínico.

Línea de tiempo clínica unificada.

Diagnósticos históricos relevantes.

Tratamientos históricos.

Medicamentos actuales dentro de antecedentes.

Referencias navegables a consultas y recetas.

Archivos clínicos asociados.

Tests específicos de pacientes y expediente.

Alertas clínicas inteligentes/relevantes.

Problemas clínicos activos.

[!] Evaluar detección de pacientes duplicados.

5. Expediente clínico

Relacionado con DT-4, DT-7, DT-9, DT-14, DT-15, DT-16 y DT-17.

Antecedentes existentes

Modelo dedicado de antecedentes médicos.

Alergias.

Medicamentos actuales.

Enfermedades crónicas.

Cirugías.

Antecedentes familiares.

Antecedentes personales.

Hábitos.

Notas adicionales.

Grupo sanguíneo.

Edición y tests.

Expediente longitudinal — DT-14

Resumen clínico.

Línea de tiempo clínica unificada.

Consultas completed en historia oficial.

Consultas draft excluidas de la historia oficial.

Diagnósticos dentro del contexto de consulta.

Diagnósticos históricos consolidados.

Recetas asociadas dentro de su consulta.

Recetas independientes como eventos propios.

Prevención de duplicados.

Tratamientos históricos consolidados.

Consolidación por medicamento + dosis + frecuencia + duración.

Última fecha de prescripción.

Enlaces a consulta y receta originales.

Orden cronológico descendente.

Protección multi-tenant.

Proyección sobre modelos existentes, sin nueva tabla de eventos.

Expediente documental — DT-15

Modelo ClinicalDocument.

Asociación con paciente y opcionalmente consulta del mismo paciente.

Categorías general, laboratory, imaging y other.

Metadata separada del archivo físico.

Storage privado.

PDF, JPG, JPEG, PNG y WebP.

Límite actual de 10 MB.

Visualización inline y miniaturas protegidas.

Descarga y eliminación seguras.

Protección multi-tenant.

Integración dentro del expediente.

Cobertura automatizada específica.

Evolución pendiente

[~] Mejorar estructura clínica de antecedentes.

Hospitalizaciones previas.

Problemas activos.

Alertas clínicas inteligentes.

Resultados estructurados de laboratorio.

Integración DICOM/PACS.

OCR/extracción estructurada.

[!] Definir límites totales de almacenamiento por tenant.

[!] Definir estrategia de respaldo, retención y conservación documental.

6. Agenda

Relacionado principalmente con DT-8 y DT-16.

Modelo Appointment.

Vistas mensual, semanal y diaria.

Navegación temporal.

Crear cita y paciente desde cita.

Buscar paciente.

Disponibilidad basada en horarios.

AppointmentAvailabilityService.

Excepciones, bloqueos y horarios extraordinarios.

Prevención de solapamientos y slots pasados.

Filtrado y búsqueda.

Cobertura automatizada.

Rediseño visual base.

[~] Mejorar diferenciación visual de estados.

[~] Mejorar densidad de información.

[~] Optimizar operación rápida.

[!] Evaluar acciones mediante popover/modal sin abandonar calendario.

7. Ciclo de citas

Relacionado principalmente con DT-8.

Estados scheduled, confirmed, checked_in, in_progress, completed, cancelled, no_show.

Programar, confirmar, check-in, iniciar consulta, completar, cancelar y reprogramar.

Editar motivo y notas.

Continuar consulta en progreso.

Integración Appointment → Consultation.

Periodo de gracia de 15 minutos para no-show.

Confirmación explícita de no-show.

Recordatorios de citas.

Confirmación externa por paciente.

WhatsApp.

SMS.

Correo.

[!] Definir estrategia de comunicación con pacientes.

8. Consultas

Relacionado principalmente con DT-9, DT-16 y DT-17.

Lifecycle clínico

Modelo Consultation.

Estados draft y completed.

Creación desde Appointment.

Una Consultation por Appointment.

Continuar Consultation existente.

Consulta sin cita.

Edición mientras está en draft.

Finalización explícita.

Finalización Consultation → Appointment completed.

Signos vitales.

Motivo de consulta.

Nota SOAP.

Diagnósticos y diagnóstico principal.

Recetas asociadas.

Historial de consultas.

Cobertura automatizada del lifecycle.

Workspace clínico — DT-17

Consulta organizada como workspace clínico.

Distribución responsive con contexto clínico y consulta actual.

Resumen rápido del paciente durante la atención.

Edad, sexo y grupo sanguíneo visibles.

Alergias visibles durante la consulta.

Medicamentos actuales reportados en antecedentes.

Enfermedades crónicas y cirugías.

Antecedentes clínicos relevantes.

Historial reciente sin abandonar la consulta.

Últimas consultas finalizadas.

Diagnósticos recientes en contexto.

Contexto construido desde las fuentes clínicas existentes.

PatientMedicalHistory permanece como fuente de alergias, medicamentos actuales y antecedentes.

Solo consultas completed forman el historial reciente mostrado.

Autosave y protección de captura

Autosave de consultas draft.

Guardado automático después de cambios clínicos.

Persistencia sin necesidad de abandonar el campo.

Estados visibles: cambios pendientes, guardando, guardado y error.

Protección frente a recarga/cierre con cambios pendientes.

Cambios inválidos permanecen pendientes.

Validación en español.

Nombres clínicos amigables para campos.

Resaltado visual de campos y signos vitales inválidos.

Scroll y foco automático al primer error.

Protección de finalización con cambios pendientes, guardado en curso o errores.

Revalidación backend antes de completar.

Una consulta inválida no puede cambiar a completed.

Appointment solo se completa tras finalizar correctamente Consultation.

Los rangos actuales son límites técnicos de validación de captura; no constituyen un sistema de alertas o decisión clínica.

Pendiente futuro

Alertas clínicas inteligentes/relevantes.

Plantillas clínicas.

Plantillas por especialidad.

[!] Definir alcance de futuras alertas clínicas sin confundirlas con validaciones técnicas.

9. Diagnósticos

ConsultationDiagnosis.

DiagnosisCatalog.

Catálogo, importación, búsqueda y autocomplete.

Diagnósticos asociados a Consultation.

Diagnóstico principal.

Código y descripción.

Tests del catálogo y flujo.

Historial consolidado por paciente.

Problemas clínicos activos.

Resolución/cierre de problemas.

[!] Definir modelo de problemas clínicos longitudinales.

10. Recetas y medicamentos

Prescription y PrescriptionItem.

Crear receta desde consulta.

Asociación con Consultation.

Medicamentos múltiples.

Presentación, dosis, frecuencia, duración e indicaciones.

Ver, editar, anular, imprimir y descargar PDF.

Datos del médico y cédula.

Cobertura automatizada.

MedicationCatalog.

Importación, autocomplete y búsqueda.

Historial longitudinal de tratamientos por paciente.

Firma digital.

QR/verificación de receta.

Repetir receta anterior.

[!] Revisar requisitos legales/documentales antes de producción.

11. Archivos clínicos

Relacionado principalmente con DT-15.

ClinicalDocument.

UUID para routing.

Upload, visualización, descarga y eliminación.

PDF e imágenes JPG/JPEG/PNG/WebP.

Categorías general, laboratorio, imagen y otros.

Asociación con paciente y opcionalmente consulta.

Metadata clínica/documental.

Storage privado.

Límite de 10 MB.

Protección multi-tenant.

Integración con expediente.

Tests de almacenamiento, aislamiento, visualización, descarga y eliminación.

Límites totales de almacenamiento por tenant.

Indicador de almacenamiento utilizado.

Proveedor externo definitivo.

URLs temporales firmadas si fueran necesarias.

Thumbnails derivados para PDF.

OCR/extracción.

DICOM/PACS.

Resultados de laboratorio estructurados.

[!] Definir política de conservación, retención y respaldo.

12. Dashboard

Relacionado principalmente con DT-8 y DT-16.

Dashboard funcional.

Citas de hoy, pacientes, citas por atender y próxima cita.

Agenda y actividad del día.

Consultas finalizadas y en progreso.

Recetas y próximos 7 días.

Estado de agenda y acciones rápidas.

Tests.

Revisión visual dentro de DT-16.

[~] Utilidad clínica/operativa de algunos indicadores.

Alertas importantes.

Trial / estado de suscripción.

Avisos de pago.

Acciones pendientes.

Pacientes esperando.

[!] Revisar qué información necesita realmente el médico al comenzar el día.

13. Configuración

Perfil profesional.

Consultorio / PracticeProfile.

Configuración de impresión.

Suscripción.

Facturación.

Métodos de pago.

Referidos.

Rediseño visual base en DT-16.

Configuración de cuenta completa.

Seguridad.

[~] Cambio de contraseña desde UI.

Almacenamiento utilizado.

[!] Reorganizar configuración por secciones/pestañas cuando el alcance funcional lo requiera.

14. Trial

Estado inicial trial.

trial_started_at y trial_ends_at.

Duración configurable e inicialización automática.

Tenant::isOnTrial() y Tenant::trialHasExpired().

Tests.

Enforcement mediante reglas centralizadas del Tenant.

Integración con Tenant::hasAccessToService().

Conversión trial → suscripción.

Selección mensual/anual.

Aviso de días restantes.

Avisos próximos al vencimiento.

Pantalla de trial vencido.

Bloqueo controlado al vencer.

[!] Definir comportamiento después del vencimiento.

[!] Definir si existirá periodo de gracia específico de trial.

15. Suscripciones

Relacionado principalmente con DT-11 y DT-12.

Modelo Subscription, UUID, soft deletes y multi-tenancy.

Ciclos mensual y anual.

Estados active, past_due, cancelled.

Precio mensual $600 MXN.

Precio anual $6,000 MXN.

Periodos, billing anchor y next_billing_at.

Renovaciones sin billing drift.

Fin de mes y años bisiestos.

Cancelación programada y reanudación.

Transiciones comerciales.

Prevención de múltiples suscripciones abiertas.

Derecho de acceso centralizado.

Conversión trial → suscripción.

Cambio mensual ↔ anual.

Historial comercial.

Cobertura automatizada.

Modelo Plan si posteriormente existen varios planes.

Upgrade/downgrade si posteriormente existen varios planes.

[!] Definir reglas de reembolso.

16. Pagos y facturación SaaS

Relacionado principalmente con DT-12 y DT-13.

Modelo Payment.

Estados pending, succeeded, failed, canceled.

Stripe como proveedor.

Métodos de pago.

SetupIntent y PaymentIntent.

Checkout manual.

Renovación automática protegida por feature flag.

Recuperación past_due.

Grace period de 7 días.

Reintentos.

Suspensión y reactivación.

Historial de pagos.

Idempotencia de renovaciones, recuperación y checkout.

Integración de promociones y créditos.

Limpieza/reconciliación de checkouts.

Scheduler.

Stripe test mode validado end-to-end.

Recibos/comprobantes.

Webhooks.

Idempotencia de webhooks.

Auditoría formal de eventos de pago.

[!] Definir facturación fiscal.

17. Ciclo de vida del tenant

Estados comerciales básicos entre Tenant y Subscription.

Reglas de acceso centralizadas.

Suspensión automática al vencer grace period.

Reactivación después de pago.

Cancelación programada.

Scheduler SaaS para billing.

[~] Procesamiento programado mediante comandos; queues/jobs dedicados quedan para necesidades futuras.

Eliminación programada.

Recuperación antes de eliminación.

Auditoría formal de transiciones.

[!] Definir política de conservación de expedientes después de cancelar.

18. Referidos y promociones

Relacionado principalmente con DT-13.

Código único y permanente por tenant.

Enlace y captura durante registro.

Prevención de auto-referidos y duplicados.

Modelo Referral.

Calificación mediante primer pago exitoso.

Descuento único de $50 MXN al referido.

Crédito de $50 MXN al referidor.

Máximo 5 recompensas / $250 MXN por mes.

PromotionalCredit.

Estados available, reserved, consumed.

Reserva, consumo y liberación idempotentes.

Integración con pagos, renovaciones y recuperación.

Checkout promocional y limpieza.

Cobertura automatizada.

Auditoría administrativa/comercial avanzada.

Herramientas administrativas de referidos.

[!] Definir comportamiento ante reembolsos futuros.

19. Comunicaciones

Actualmente no existe infraestructura completa de comunicaciones externas.

Infraestructura de notificaciones.

Correo transaccional.

WhatsApp.

SMS.

Confirmación y recordatorio de cita.

Cancelación y reprogramación.

Confirmación por paciente.

Bienvenida.

Avisos de trial.

Avisos de pago y reintento.

Cuenta suspendida/reactivada.

Cancelación de suscripción.

[!] Definir proveedor de correo.

[!] Definir proveedor de WhatsApp.

[!] Definir proveedor de SMS.

20. Diseño y experiencia visual

Relacionado principalmente con DT-16 y DT-17.

DT-16 estableció la foundation visual y la identidad propia de DocTotal.
DT-17 llevó esa foundation al workspace clínico avanzado.

Implementado

Foundation visual y design tokens.

Identidad, branding, logo y favicons.

Paleta y tipografía base.

Sistema de componentes visuales.

Sidebar, header y navegación responsive.

Pacientes y expediente.

Agenda y ciclo visual de citas.

Consultas y recetas.

Onboarding.

Configuración y Billing.

Login, registro y recuperación de contraseña.

Landing pública.

Flash messages.

Responsive global.

Workspace clínico avanzado.

Barra flotante de acciones de consulta.

Estados visuales de autosave y validación.

Evolución visual pendiente

[~] Accesibilidad básica; falta auditoría avanzada.

[~] Mejorar diferenciación visual de algunos estados de agenda.

[~] Revisar densidad y utilidad de algunos indicadores del dashboard.

Alertas clínicas con tratamiento visual específico cuando exista su dominio funcional.

[!] Mantener consistencia con el Design System en todo módulo nuevo.

21. Rediseño por módulo

Completado o cubierto por DT-16/DT-17

Login.

Registro.

Onboarding.

Dashboard: revisión visual base.

Pacientes.

Expediente longitudinal.

Agenda.

Consulta como workspace clínico.

Contexto clínico durante consulta.

Alergias y medicamentos actuales visibles.

Historial reciente y diagnósticos en contexto.

Captura SOAP reorganizada.

Autosave.

Recetas.

Configuración visual base.

Pendiente/evolución

Alertas clínicas inteligentes.

Facilitar repetición de tratamientos.

[~] Configuración puede evolucionar hacia más secciones según crezca el producto.

[~] Dashboard requiere definición final de información prioritaria.

[~] Agenda puede optimizar acciones rápidas y densidad.

22. Auditoría, privacidad y seguridad

Auditoría de acciones sensibles.

Historial de cambios clínicos.

Historial de cambios de citas.

Historial de cambios de recetas.

Historial de suscripción y pagos.

Eventos administrativos.

Protección base de archivos clínicos.

Revisión integral de autorización y validaciones.

Rate limiting.

Política de contraseñas.

Auditoría de 2FA/passkeys.

Backups y restauración.

Logs y monitoreo de producción.

Protección integral de información sensible.

Políticas de retención y eliminación.

[!] Revisión integral antes de manejar información real de pacientes.

23. Operación interna de DocTotal

Actualmente no existe panel administrativo interno.

Panel administrativo.

Listado, búsqueda y detalle de tenants.

Estado, alta, trial, suscripción, pagos y almacenamiento.

Métricas de trials y suscripciones.

Tenants suspendidos.

Pagos fallidos.

Referidos y promociones.

MRR, ARR, churn y conversión trial → pago.

Uso de almacenamiento.

Herramientas de soporte.

Auditoría administrativa.

[!] El panel administrativo nunca debe romper accidentalmente el aislamiento entre tenants.

24. Infraestructura y operación técnica

Infraestructura base Laravel para cache/jobs.

Scheduler operativo para billing.

Renovaciones, reintentos, cancelaciones y grace periods programados.

Limpieza y reconciliación de checkouts.

Queue de producción.

Procesamiento de eliminaciones.

Procesamiento de notificaciones.

Monitoreo de queues.

Manejo de failed jobs.

Logging estructurado.

Error tracking.

Backups automáticos.

Monitoreo de aplicación.

Health checks.

[!] Definir infraestructura de producción.

25. Calidad y tests

La aplicación cuenta con una suite automatizada considerable.

Baselines

DT-10: 393 tests verdes / 1179 assertions / 0 failures.

DT-11: 445 tests verdes / 0 failures.

DT-12: 696 tests verdes / 0 failures.

DT-13: 797 tests verdes / 2244 assertions / 0 failures.

DT-14: 814 tests verdes / 2339 assertions / 0 failures.

DT-15: 837 tests verdes / 2395 assertions / 0 failures.

DT-16: 837 tests verdes / 0 failures.

DT-17: 840 tests verdes / 0 failures.

No se registró un número final de assertions para DT-16 ni DT-17; no debe inferirse.

Cobertura existente

Autenticación, registro y trial.

Multi-tenancy.

Onboarding y código postal.

Pacientes, antecedentes y contactos.

Agenda, appointments, disponibilidad, slots y estados.

Appointment → Consultation.

Consultation y lifecycle clínico.

Diagnósticos y catálogos.

Recetas, medicamentos y PDF.

Dashboard.

Suscripciones y billing.

Recuperación de pagos.

Referidos, promociones y créditos.

Checkout y reconciliación.

Expediente longitudinal.

Archivos clínicos y storage.

Workspace clínico DT-17.

Protección backend frente a finalización inválida.

Webhooks.

Notificaciones.

Seguridad adicional.

26. Estado de los DT

DT-1 — Base multi-tenant database structure.

DT-2 — Core Eloquent models and relationships.

DT-3 — Tenant isolation and request resolution.

DT-4 — Patient clinical record foundation.

DT-5 — Authentication, registration, dashboard and trial.

DT-6 — Onboarding wizard and postal code autocomplete.

DT-7 — Gestión de pacientes.

DT-8 — Agenda, citas y ciclo operativo. Baseline: 366 tests.

DT-9 — Consultation workflow and clinical lifecycle. Baseline: 393 tests.

DT-10 — Product inventory and development roadmap. Baseline: 393 / 1179 / 0.

DT-11 — Subscription lifecycle foundation. Baseline: 445 / 0.

DT-12 — Payments, billing recovery and automatic account lifecycle. Baseline: 696 / 0.

DT-13 — Referral program and promotional credits. Baseline: 797 / 2244 / 0.

DT-14 — Expediente clínico longitudinal. Baseline: 814 / 2339 / 0.

DT-15 — Clinical files and medical documents. Baseline: 837 / 2395 / 0.

DT-16 — Visual redesign / DocTotal UI. Baseline: 837 / 0.

DT-17 — Clinical workspace / Consulta médica avanzada. Baseline: 840 / 0.

[~] DT-18 — Documentation baseline normalization and next DocTotal development block.

DT-17 — Clinical workspace / Consulta médica avanzada

Objetivo cumplido:

Transformar la consulta en un workspace clínico avanzado que mantenga visible el contexto relevante del paciente y proteja la captura mediante autosave seguro.

Incluye:

Workspace clínico responsive.

Resumen rápido del paciente.

Alergias, medicamentos actuales y antecedentes relevantes.

Consultas y diagnósticos recientes.

Reorganización de motivo, signos vitales, SOAP y diagnósticos.

Autosave de draft.

Estados de guardado.

Validación en español.

Resaltado y navegación al primer error.

Protección frente a pérdida de cambios.

Barra flotante de acciones.

Protección de finalización.

Validación backend contra consultas inválidas.

Preservación de lifecycle y multi-tenancy.

Baseline al cierre:

840 tests verdes.

0 failures.

Commit principal:

0fb847f DT-17 feat: implement advanced clinical consultation workspace

27. Candidatos para siguientes DT

DT-17 completó el candidato anterior de Clinical workspace.

DT-18 comienza con normalización documental y debe seleccionar su bloque funcional a partir del estado real del producto.

Candidato A — Comunicaciones y recordatorios

Confirmación de citas.

Recordatorios.

Correo transaccional.

WhatsApp/SMS según proveedor.

Eventos SaaS de trial, billing, suspensión y reactivación.

Candidato B — Seguridad, privacidad y auditoría

Auditoría de acciones sensibles.

Historial de cambios clínicos.

Sesiones y dispositivos.

2FA/passkeys.

Logs, backups y políticas de retención.

Candidato C — Problemas clínicos activos y alertas

Modelo longitudinal de problemas activos.

Resolución/cierre.

Alertas clínicas.

Integración con expediente y workspace.

Candidato D — Operación interna SaaS

Panel administrativo.

Tenants.

Trials.

Suscripciones y pagos.

Referidos/promociones.

Métricas SaaS.

Candidato E — Trial y lifecycle pendiente

Avisos de vencimiento.

Pantalla de trial vencido.

Bloqueo controlado.

Eliminación programada y recuperación previa.

Candidato F — Storage y documentos avanzados

Cuotas por tenant.

Indicador de almacenamiento.

Retención/respaldo.

Proveedor externo.

OCR/PDF thumbnails según prioridad.

28. Deuda y decisiones de producto

Comercial

Precio mensual: $600 MXN.

Precio anual: $6,000 MXN.

Descuento anual: equivalente a 2 meses.

[!] Duración definitiva del trial.

Grace period de billing: 7 días.

Cancelación al final del periodo, reversible antes del vencimiento.

[!] Política de reembolso.

Referidos

$50 MXN de descuento al tenant nuevo.

$50 MXN de crédito al referente.

Máximo 5 recompensas / $250 MXN al mes.

Primer pago exitoso como requisito.

Crédito sin caducidad.

Aplicación a mensualidad y anualidad.

[!] Comportamiento ante reembolsos futuros.

Infraestructura

Proveedor de pagos: Stripe.

[~] Storage privado local implementado; proveedor externo pendiente.

[!] Proveedor de correo.

[!] Proveedor de WhatsApp.

[!] Proveedor de SMS.

Clínica

[!] Estructura de problemas activos.

[!] Política de modificación de información clínica finalizada.

[~] Política técnica base de archivos implementada; retención y cuotas pendientes.

[!] Retención de expedientes.

[!] Requisitos legales de recetas/documentos.

[!] Alcance y reglas de alertas clínicas inteligentes.

[!] Plantillas clínicas y por especialidad.

UX

Identidad visual y dirección del Design System.

Workspace clínico base.

[~] Auditoría avanzada de accesibilidad pendiente.

[~] Evolución continua de agenda/dashboard conforme se definan necesidades operativas.

29. Regla para decidir el siguiente DT

Al iniciar un DT:

Confirmar branch y ticket Jira asignado.

Normalizar TODO.md y ROADMAP.md contra el baseline real de entrada.

Auditar el bloque funcional antes de modificarlo.

Definir problema, objetivo, alcance y fuera de alcance.

Implementar con protección multi-tenant y tests.

Al cerrar un DT:

Ejecutar suite completa.

Confirmar tests verdes.

Registrar tests y assertions disponibles.

Actualizar ROADMAP.md.

Actualizar TODO.md.

Registrar nuevas necesidades.

Confirmar objetivo y alcance.

Realizar commit/merge.

Cerrar Jira.

30. Regla de cierre de un DT

Un DT no debe considerarse terminado únicamente porque "funciona".

Antes de cerrarlo debe comprobarse:

Objetivo cumplido.

Alcance implementado.

Multi-tenancy protegido.

Validaciones implementadas.

Estados límite contemplados.

Tests agregados.

Suite completa verde.

UI funcional.

Integración con módulos existentes comprobada.

TODO actualizado.

ROADMAP actualizado.

Baseline registrado.

Commit final realizado.

Merge realizado.

Jira cerrado.

31. Principio de desarrollo

Antes de comenzar un nuevo DT debemos responder:

¿Qué problema resuelve?

¿Para quién lo resuelve?

¿Qué módulos existentes afecta?

¿Qué información necesita?

¿Qué información genera?

¿Cómo afecta al aislamiento multi-tenant?

¿Cómo afecta al expediente clínico?

¿Cómo afecta al ciclo SaaS?

¿Cómo debe verse y sentirse para el usuario?

¿Cómo vamos a probarlo?

DocTotal debe crecer como un producto integrado, no como una colección de pantallas y funciones independientes.

32. Visión de producto

El objetivo final no es construir solamente un sistema de expedientes médicos.

DocTotal debe permitir que un médico pueda:

Registrarse
→ configurar su consultorio
→ comenzar su periodo de prueba
→ administrar pacientes
→ organizar su agenda
→ atender citas
→ documentar consultas
→ consultar el historial clínico
→ diagnosticar
→ recetar
→ almacenar estudios y documentos
→ continuar el seguimiento del paciente

mientras DocTotal automáticamente:

controla el trial
→ convierte a suscripción
→ cobra
→ renueva
→ detecta pagos fallidos
→ aplica periodos de gracia
→ suspende cuando corresponda
→ reactiva después del pago
→ administra promociones
→ registra referidos
→ controla límites
→ conserva la información
→ mantiene aislados los tenants.

Todo esto debe presentarse mediante una experiencia visual propia, profesional, agradable y diseñada específicamente para el trabajo médico.as clínicas.

Plantillas por especialidad.

[!] Definir alcance de futuras alertas clínicas sin confundirlas con validaciones técnicas.

9. Diagnósticos

ConsultationDiagnosis.

DiagnosisCatalog.

Catálogo, importación, búsqueda y autocomplete.

Diagnósticos asociados a Consultation.

Diagnóstico principal.

Código y descripción.

Tests del catálogo y flujo.

Historial consolidado por paciente.

Problemas clínicos activos.

Resolución/cierre de problemas.

[!] Definir modelo de problemas clínicos longitudinales.

10. Recetas y medicamentos

Prescription y PrescriptionItem.

Crear receta desde consulta.

Asociación con Consultation.

Medicamentos múltiples.

Presentación, dosis, frecuencia, duración e indicaciones.

Ver, editar, anular, imprimir y descargar PDF.

Datos del médico y cédula.

Cobertura automatizada.

MedicationCatalog.

Importación, autocomplete y búsqueda.

Historial longitudinal de tratamientos por paciente.

Firma digital.

QR/verificación de receta.

Repetir receta anterior.

[!] Revisar requisitos legales/documentales antes de producción.

11. Archivos clínicos

Relacionado principalmente con DT-15.

ClinicalDocument.

UUID para routing.

Upload, visualización, descarga y eliminación.

PDF e imágenes JPG/JPEG/PNG/WebP.

Categorías general, laboratorio, imagen y otros.

Asociación con paciente y opcionalmente consulta.

Metadata clínica/documental.

Storage privado.

Límite de 10 MB.

Protección multi-tenant.

Integración con expediente.

Tests de almacenamiento, aislamiento, visualización, descarga y eliminación.

Límites totales de almacenamiento por tenant.

Indicador de almacenamiento utilizado.

Proveedor externo definitivo.

URLs temporales firmadas si fueran necesarias.

Thumbnails derivados para PDF.

OCR/extracción.

DICOM/PACS.

Resultados de laboratorio estructurados.

[!] Definir política de conservación, retención y respaldo.

12. Dashboard

Relacionado principalmente con DT-8 y DT-16.

Dashboard funcional.

Citas de hoy, pacientes, citas por atender y próxima cita.

Agenda y actividad del día.

Consultas finalizadas y en progreso.

Recetas y próximos 7 días.

Estado de agenda y acciones rápidas.

Tests.

Revisión visual dentro de DT-16.

[~] Utilidad clínica/operativa de algunos indicadores.

Alertas importantes.

Trial / estado de suscripción.

Avisos de pago.

Acciones pendientes.

Pacientes esperando.

[!] Revisar qué información necesita realmente el médico al comenzar el día.

13. Configuración

Perfil profesional.

Consultorio / PracticeProfile.

Configuración de impresión.

Suscripción.

Facturación.

Métodos de pago.

Referidos.

Rediseño visual base en DT-16.

Configuración de cuenta completa.

Seguridad.

[~] Cambio de contraseña desde UI.

Almacenamiento utilizado.

[!] Reorganizar configuración por secciones/pestañas cuando el alcance funcional lo requiera.

14. Trial

Estado inicial trial.

trial_started_at y trial_ends_at.

Duración configurable e inicialización automática.

Tenant::isOnTrial() y Tenant::trialHasExpired().

Tests.

Enforcement mediante reglas centralizadas del Tenant.

Integración con Tenant::hasAccessToService().

Conversión trial → suscripción.

Selección mensual/anual.

Aviso de días restantes.

Avisos próximos al vencimiento.

Pantalla de trial vencido.

Bloqueo controlado al vencer.

[!] Definir comportamiento después del vencimiento.

[!] Definir si existirá periodo de gracia específico de trial.

15. Suscripciones

Relacionado principalmente con DT-11 y DT-12.

Modelo Subscription, UUID, soft deletes y multi-tenancy.

Ciclos mensual y anual.

Estados active, past_due, cancelled.

Precio mensual $600 MXN.

Precio anual $6,000 MXN.

Periodos, billing anchor y next_billing_at.

Renovaciones sin billing drift.

Fin de mes y años bisiestos.

Cancelación programada y reanudación.

Transiciones comerciales.

Prevención de múltiples suscripciones abiertas.

Derecho de acceso centralizado.

Conversión trial → suscripción.

Cambio mensual ↔ anual.

Historial comercial.

Cobertura automatizada.

Modelo Plan si posteriormente existen varios planes.

Upgrade/downgrade si posteriormente existen varios planes.

[!] Definir reglas de reembolso.

16. Pagos y facturación SaaS

Relacionado principalmente con DT-12 y DT-13.

Modelo Payment.

Estados pending, succeeded, failed, canceled.

Stripe como proveedor.

Métodos de pago.

SetupIntent y PaymentIntent.

Checkout manual.

Renovación automática protegida por feature flag.

Recuperación past_due.

Grace period de 7 días.

Reintentos.

Suspensión y reactivación.

Historial de pagos.

Idempotencia de renovaciones, recuperación y checkout.

Integración de promociones y créditos.

Limpieza/reconciliación de checkouts.

Scheduler.

Stripe test mode validado end-to-end.

Recibos/comprobantes.

Webhooks.

Idempotencia de webhooks.

Auditoría formal de eventos de pago.

[!] Definir facturación fiscal.

17. Ciclo de vida del tenant

Estados comerciales básicos entre Tenant y Subscription.

Reglas de acceso centralizadas.

Suspensión automática al vencer grace period.

Reactivación después de pago.

Cancelación programada.

Scheduler SaaS para billing.

[~] Procesamiento programado mediante comandos; queues/jobs dedicados quedan para necesidades futuras.

Eliminación programada.

Recuperación antes de eliminación.

Auditoría formal de transiciones.

[!] Definir política de conservación de expedientes después de cancelar.

18. Referidos y promociones

Relacionado principalmente con DT-13.

Código único y permanente por tenant.

Enlace y captura durante registro.

Prevención de auto-referidos y duplicados.

Modelo Referral.

Calificación mediante primer pago exitoso.

Descuento único de $50 MXN al referido.

Crédito de $50 MXN al referidor.

Máximo 5 recompensas / $250 MXN por mes.

PromotionalCredit.

Estados available, reserved, consumed.

Reserva, consumo y liberación idempotentes.

Integración con pagos, renovaciones y recuperación.

Checkout promocional y limpieza.

Cobertura automatizada.

Auditoría administrativa/comercial avanzada.

Herramientas administrativas de referidos.

[!] Definir comportamiento ante reembolsos futuros.

19. Comunicaciones

Actualmente no existe infraestructura completa de comunicaciones externas.

Infraestructura de notificaciones.

Correo transaccional.

WhatsApp.

SMS.

Confirmación y recordatorio de cita.

Cancelación y reprogramación.

Confirmación por paciente.

Bienvenida.

Avisos de trial.

Avisos de pago y reintento.

Cuenta suspendida/reactivada.

Cancelación de suscripción.

[!] Definir proveedor de correo.

[!] Definir proveedor de WhatsApp.

[!] Definir proveedor de SMS.

20. Diseño y experiencia visual

Relacionado principalmente con DT-16 y DT-17.

DT-16 estableció la foundation visual y la identidad propia de DocTotal.
DT-17 llevó esa foundation al workspace clínico avanzado.

Implementado

Foundation visual y design tokens.

Identidad, branding, logo y favicons.

Paleta y tipografía base.

Sistema de componentes visuales.

Sidebar, header y navegación responsive.

Pacientes y expediente.

Agenda y ciclo visual de citas.

Consultas y recetas.

Onboarding.

Configuración y Billing.

Login, registro y recuperación de contraseña.

Landing pública.

Flash messages.

Responsive global.

Workspace clínico avanzado.

Barra flotante de acciones de consulta.

Estados visuales de autosave y validación.

Evolución visual pendiente

[~] Accesibilidad básica; falta auditoría avanzada.

[~] Mejorar diferenciación visual de algunos estados de agenda.

[~] Revisar densidad y utilidad de algunos indicadores del dashboard.

Alertas clínicas con tratamiento visual específico cuando exista su dominio funcional.

[!] Mantener consistencia con el Design System en todo módulo nuevo.

21. Rediseño por módulo

Completado o cubierto por DT-16/DT-17

Login.

Registro.

Onboarding.

Dashboard: revisión visual base.

Pacientes.

Expediente longitudinal.

Agenda.

Consulta como workspace clínico.

Contexto clínico durante consulta.

Alergias y medicamentos actuales visibles.

Historial reciente y diagnósticos en contexto.

Captura SOAP reorganizada.

Autosave.

Recetas.

Configuración visual base.

Pendiente/evolución

Alertas clínicas inteligentes.

Facilitar repetición de tratamientos.

[~] Configuración puede evolucionar hacia más secciones según crezca el producto.

[~] Dashboard requiere definición final de información prioritaria.

[~] Agenda puede optimizar acciones rápidas y densidad.

22. Auditoría, privacidad y seguridad

Auditoría de acciones sensibles.

Historial de cambios clínicos.

Historial de cambios de citas.

Historial de cambios de recetas.

Historial de suscripción y pagos.

Eventos administrativos.

Protección base de archivos clínicos.

Revisión integral de autorización y validaciones.

Rate limiting.

Política de contraseñas.

Auditoría de 2FA/passkeys.

Backups y restauración.

Logs y monitoreo de producción.

Protección integral de información sensible.

Políticas de retención y eliminación.

[!] Revisión integral antes de manejar información real de pacientes.

23. Operación interna de DocTotal

Actualmente no existe panel administrativo interno.

Panel administrativo.

Listado, búsqueda y detalle de tenants.

Estado, alta, trial, suscripción, pagos y almacenamiento.

Métricas de trials y suscripciones.

Tenants suspendidos.

Pagos fallidos.

Referidos y promociones.

MRR, ARR, churn y conversión trial → pago.

Uso de almacenamiento.

Herramientas de soporte.

Auditoría administrativa.

[!] El panel administrativo nunca debe romper accidentalmente el aislamiento entre tenants.

24. Infraestructura y operación técnica

Infraestructura base Laravel para cache/jobs.

Scheduler operativo para billing.

Renovaciones, reintentos, cancelaciones y grace periods programados.

Limpieza y reconciliación de checkouts.

Queue de producción.

Procesamiento de eliminaciones.

Procesamiento de notificaciones.

Monitoreo de queues.

Manejo de failed jobs.

Logging estructurado.

Error tracking.

Backups automáticos.

Monitoreo de aplicación.

Health checks.

[!] Definir infraestructura de producción.

25. Calidad y tests

La aplicación cuenta con una suite automatizada considerable.

Baselines

DT-10: 393 tests verdes / 1179 assertions / 0 failures.

DT-11: 445 tests verdes / 0 failures.

DT-12: 696 tests verdes / 0 failures.

DT-13: 797 tests verdes / 2244 assertions / 0 failures.

DT-14: 814 tests verdes / 2339 assertions / 0 failures.

DT-15: 837 tests verdes / 2395 assertions / 0 failures.

DT-16: 837 tests verdes / 0 failures.

DT-17: 840 tests verdes / 0 failures.

No se registró un número final de assertions para DT-16 ni DT-17; no debe inferirse.

Cobertura existente

Autenticación, registro y trial.

Multi-tenancy.

Onboarding y código postal.

Pacientes, antecedentes y contactos.

Agenda, appointments, disponibilidad, slots y estados.

Appointment → Consultation.

Consultation y lifecycle clínico.

Diagnósticos y catálogos.

Recetas, medicamentos y PDF.

Dashboard.

Suscripciones y billing.

Recuperación de pagos.

Referidos, promociones y créditos.

Checkout y reconciliación.

Expediente longitudinal.

Archivos clínicos y storage.

Workspace clínico DT-17.

Protección backend frente a finalización inválida.

Webhooks.

Notificaciones.

Seguridad adicional.

26. Estado de los DT

DT-1 — Base multi-tenant database structure.

DT-2 — Core Eloquent models and relationships.

DT-3 — Tenant isolation and request resolution.

DT-4 — Patient clinical record foundation.

DT-5 — Authentication, registration, dashboard and trial.

DT-6 — Onboarding wizard and postal code autocomplete.

DT-7 — Gestión de pacientes.

DT-8 — Agenda, citas y ciclo operativo. Baseline: 366 tests.

DT-9 — Consultation workflow and clinical lifecycle. Baseline: 393 tests.

DT-10 — Product inventory and development roadmap. Baseline: 393 / 1179 / 0.

DT-11 — Subscription lifecycle foundation. Baseline: 445 / 0.

DT-12 — Payments, billing recovery and automatic account lifecycle. Baseline: 696 / 0.

DT-13 — Referral program and promotional credits. Baseline: 797 / 2244 / 0.

DT-14 — Expediente clínico longitudinal. Baseline: 814 / 2339 / 0.

DT-15 — Clinical files and medical documents. Baseline: 837 / 2395 / 0.

DT-16 — Visual redesign / DocTotal UI. Baseline: 837 / 0.

DT-17 — Clinical workspace / Consulta médica avanzada. Baseline: 840 / 0.

[~] DT-18 — Documentation baseline normalization and next DocTotal development block.

DT-17 — Clinical workspace / Consulta médica avanzada

Objetivo cumplido:

Transformar la consulta en un workspace clínico avanzado que mantenga visible el contexto relevante del paciente y proteja la captura mediante autosave seguro.

Incluye:

Workspace clínico responsive.

Resumen rápido del paciente.

Alergias, medicamentos actuales y antecedentes relevantes.

Consultas y diagnósticos recientes.

Reorganización de motivo, signos vitales, SOAP y diagnósticos.

Autosave de draft.

Estados de guardado.

Validación en español.

Resaltado y navegación al primer error.

Protección frente a pérdida de cambios.

Barra flotante de acciones.

Protección de finalización.

Validación backend contra consultas inválidas.

Preservación de lifecycle y multi-tenancy.

Baseline al cierre:

840 tests verdes.

0 failures.

Commit principal:

0fb847f DT-17 feat: implement advanced clinical consultation workspace

27. Candidatos para siguientes DT

DT-17 completó el candidato anterior de Clinical workspace.

DT-18 comienza con normalización documental y debe seleccionar su bloque funcional a partir del estado real del producto.

Candidato A — Comunicaciones y recordatorios

Confirmación de citas.

Recordatorios.

Correo transaccional.

WhatsApp/SMS según proveedor.

Eventos SaaS de trial, billing, suspensión y reactivación.

Candidato B — Seguridad, privacidad y auditoría

Auditoría de acciones sensibles.

Historial de cambios clínicos.

Sesiones y dispositivos.

2FA/passkeys.

Logs, backups y políticas de retención.

Candidato C — Problemas clínicos activos y alertas

Modelo longitudinal de problemas activos.

Resolución/cierre.

Alertas clínicas.

Integración con expediente y workspace.

Candidato D — Operación interna SaaS

Panel administrativo.

Tenants.

Trials.

Suscripciones y pagos.

Referidos/promociones.

Métricas SaaS.

Candidato E — Trial y lifecycle pendiente

Avisos de vencimiento.

Pantalla de trial vencido.

Bloqueo controlado.

Eliminación programada y recuperación previa.

Candidato F — Storage y documentos avanzados

Cuotas por tenant.

Indicador de almacenamiento.

Retención/respaldo.

Proveedor externo.

OCR/PDF thumbnails según prioridad.

28. Deuda y decisiones de producto

Comercial

Precio mensual: $600 MXN.

Precio anual: $6,000 MXN.

Descuento anual: equivalente a 2 meses.

[!] Duración definitiva del trial.

Grace period de billing: 7 días.

Cancelación al final del periodo, reversible antes del vencimiento.

[!] Política de reembolso.

Referidos

$50 MXN de descuento al tenant nuevo.

$50 MXN de crédito al referente.

Máximo 5 recompensas / $250 MXN al mes.

Primer pago exitoso como requisito.

Crédito sin caducidad.

Aplicación a mensualidad y anualidad.

[!] Comportamiento ante reembolsos futuros.

Infraestructura

Proveedor de pagos: Stripe.

[~] Storage privado local implementado; proveedor externo pendiente.

[!] Proveedor de correo.

[!] Proveedor de WhatsApp.

[!] Proveedor de SMS.

Clínica

[!] Estructura de problemas activos.

[!] Política de modificación de información clínica finalizada.

[~] Política técnica base de archivos implementada; retención y cuotas pendientes.

[!] Retención de expedientes.

[!] Requisitos legales de recetas/documentos.

[!] Alcance y reglas de alertas clínicas inteligentes.

[!] Plantillas clínicas y por especialidad.

UX

Identidad visual y dirección del Design System.

Workspace clínico base.

[~] Auditoría avanzada de accesibilidad pendiente.

[~] Evolución continua de agenda/dashboard conforme se definan necesidades operativas.

29. Regla para decidir el siguiente DT

Al iniciar un DT:

Confirmar branch y ticket Jira asignado.

Normalizar TODO.md y ROADMAP.md contra el baseline real de entrada.

Auditar el bloque funcional antes de modificarlo.

Definir problema, objetivo, alcance y fuera de alcance.

Implementar con protección multi-tenant y tests.

Al cerrar un DT:

Ejecutar suite completa.

Confirmar tests verdes.

Registrar tests y assertions disponibles.

Actualizar ROADMAP.md.

Actualizar TODO.md.

Registrar nuevas necesidades.

Confirmar objetivo y alcance.

Realizar commit/merge.

Cerrar Jira.

30. Regla de cierre de un DT

Un DT no debe considerarse terminado únicamente porque "funciona".

Antes de cerrarlo debe comprobarse:

Objetivo cumplido.

Alcance implementado.

Multi-tenancy protegido.

Validaciones implementadas.

Estados límite contemplados.

Tests agregados.

Suite completa verde.

UI funcional.

Integración con módulos existentes comprobada.

TODO actualizado.

ROADMAP actualizado.

Baseline registrado.

Commit final realizado.

Merge realizado.

Jira cerrado.

31. Principio de desarrollo

Antes de comenzar un nuevo DT debemos responder:

¿Qué problema resuelve?

¿Para quién lo resuelve?

¿Qué módulos existentes afecta?

¿Qué información necesita?

¿Qué información genera?

¿Cómo afecta al aislamiento multi-tenant?

¿Cómo afecta al expediente clínico?

¿Cómo afecta al ciclo SaaS?

¿Cómo debe verse y sentirse para el usuario?

¿Cómo vamos a probarlo?

DocTotal debe crecer como un producto integrado, no como una colección de pantallas y funciones independientes.

32. Visión de producto

El objetivo final no es construir solamente un sistema de expedientes médicos.

DocTotal debe permitir que un médico pueda:

Registrarse
→ configurar su consultorio
→ comenzar su periodo de prueba
→ administrar pacientes
→ organizar su agenda
→ atender citas
→ documentar consultas
→ consultar el historial clínico
→ diagnosticar
→ recetar
→ almacenar estudios y documentos
→ continuar el seguimiento del paciente

mientras DocTotal automáticamente:

controla el trial
→ convierte a suscripción
→ cobra
→ renueva
→ detecta pagos fallidos
→ aplica periodos de gracia
→ suspende cuando corresponda
→ reactiva después del pago
→ administra promociones
→ registra referidos
→ controla límites
→ conserva la información
→ mantiene aislados los tenants.

Todo esto debe presentarse mediante una experiencia visual propia, profesional, agradable y diseñada específicamente para el trabajo médico.