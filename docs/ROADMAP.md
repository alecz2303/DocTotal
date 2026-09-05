# DocTotal Roadmap

Este documento resume los bloques de trabajo principales del proyecto
DocTotal.

## DT-1 --- Base multi-tenant database structure

Estado: Completado

Objetivo:

Construir la base de datos inicial con soporte multi-tenant.

Incluye:

-   Estructura inicial multi-tenant.

-   Tenant como entidad principal de aislamiento.

-   Relaciones base necesarias para el resto del sistema.

Commit principal:

`DT-1 Implement base multi-tenant database structure`

------------------------------------------------------------------------

## DT-2 --- Core Eloquent models and relationships

Estado: Completado

Objetivo:

Implementar los modelos Eloquent principales y sus relaciones.

Incluye:

-   Modelos base del dominio.

-   Relaciones entre entidades.

-   Preparación para aislamiento por tenant.

Commit principal:

`DT-2 Implement core Eloquent models and relationships`

------------------------------------------------------------------------

## DT-3 --- Tenant isolation and request resolution

Estado: Completado

Objetivo:

Garantizar aislamiento entre tenants y resolver el tenant activo por
request.

Incluye:

-   TenantContext.

-   Resolución del tenant actual.

-   Protección contra acceso cruzado entre tenants.

-   Middleware de tenant.

-   `TenantScope`.

-   Trait `BelongsToTenant`.

-   Cobertura automatizada de aislamiento.

Commit principal:

`DT-3 Implement tenant isolation and request resolution`

------------------------------------------------------------------------

## DT-4 --- Patient clinical record foundation

Estado: Completado

Objetivo:

Construir la base del expediente clínico del paciente.

Incluye:

-   Pacientes.

-   Contactos de emergencia.

-   Antecedentes médicos.

-   Base de consultas.

-   Fundamentos del expediente clínico.

Commit principal:

`DT-4 Implement patient clinical record foundation`

------------------------------------------------------------------------

## DT-5 --- Authentication, registration, dashboard and trial

Estado: Completado

Objetivo:

Implementar autenticación, registro y flujo inicial del usuario.

Incluye:

-   Registro.

-   Inicio de sesión.

-   Cierre de sesión.

-   Dashboard inicial.

-   Trial.

-   Asociación del usuario con su tenant.

-   Infraestructura Laravel Fortify.

Commit principal:

`DT-5 Implement authentication registration dashboard and trial`

------------------------------------------------------------------------

## DT-6 --- Onboarding wizard and postal code autocomplete

Estado: Completado

Objetivo:

Construir el onboarding inicial del médico y consultorio.

Incluye:

-   Wizard de onboarding.

-   Perfil del consultorio.

-   Perfil médico.

-   Especialidad.

-   Cédula profesional.

-   Horarios de atención.

-   Duración predeterminada de citas.

-   Autocompletado por código postal.

-   Middleware de onboarding.

-   Cobertura automatizada.

Commit principal:

`DT-6 feat: implement onboarding wizard with postal code autocomplete`

------------------------------------------------------------------------

## DT-7 --- Gestión de pacientes

Estado: Completado

Objetivo:

Construir el flujo principal de pacientes y expediente.

Incluye:

-   Listado de pacientes.

-   Búsqueda.

-   Alta de pacientes.

-   Edición.

-   Detalle del paciente.

-   Contactos de emergencia.

-   Antecedentes médicos.

-   Expediente clínico.

-   Integración con consultas.

-   Protección multi-tenant.

Commit principal:

`DT-7 Implement gestión de pacientes`

------------------------------------------------------------------------

## DT-8 --- Agenda, citas y ciclo operativo

Estado: Completado

Objetivo:

Construir el sistema completo de agenda y citas.

Incluye:

-   Creación de citas.

-   Creación rápida de pacientes desde una cita.

-   Disponibilidad basada en horarios del onboarding.

-   `AppointmentAvailabilityService`.

-   Excepciones de horario.

-   Bloqueos parciales y completos.

-   Horarios extraordinarios.

-   Prevención de solapamientos.

-   Eliminación de slots pasados.

-   Agenda por mes, semana y día.

-   Dashboard dinámico.

-   Estados de Appointment.

-   Confirmación.

-   Check-in.

-   Inicio de consulta.

-   Finalización automática de cita.

-   Cancelación.

-   No-show semiautomático.

-   Periodo de gracia de 15 minutos para no-show.

-   Reprogramación.

-   Edición de motivo y notas.

-   Integración Appointment → Consultation.

-   Continuar consulta cuando la cita queda en progreso.

-   Aislamiento multi-tenant.

-   Cobertura completa con tests.

Estados de Appointment:

-   `scheduled`

-   `confirmed`

-   `checked_in`

-   `in_progress`

-   `completed`

-   `cancelled`

-   `no_show`

Regla de no-show:

Una cita solo puede marcarse como no-show cuando:

`now() >= ends_at + 15 minutos`

El cambio a no-show nunca es automático. La acción debe ser confirmada
por el usuario.

Commits principales:

-   `DT-8 feat: complete appointment scheduling and dashboard`

-   `DT-8 feat: finalize appointment workflow and lifecycle`

Baseline al cerrar DT-8:

`366 tests verdes`

------------------------------------------------------------------------

## DT-9 --- Consultation workflow and clinical lifecycle

Estado: Completado

Objetivo:

Convertir Consultation en una entidad clínica persistente desde el
inicio de la atención.

Flujo implementado:

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

-   Estados de Consultation.

-   `draft`.

-   `completed`.

-   Crear Consultation al iniciar una cita.

-   Una sola Consultation por Appointment.

-   Continuar una Consultation existente.

-   Consulta directa sin Appointment.

-   Editar consulta mientras está en draft.

-   Finalización explícita de Consultation.

-   Completar Appointment al finalizar Consultation.

-   Signos vitales.

-   Motivo de consulta.

-   Nota SOAP.

-   Diagnósticos durante la consulta.

-   Diagnóstico principal.

-   Recetas asociadas.

-   Historial clínico.

-   Protección multi-tenant.

-   Tests del modelo.

-   Tests del flujo.

-   Tests del lifecycle.

-   Tests Appointment → Consultation.

Fuera de alcance en DT-9:

-   Autosave.

-   Workspace clínico avanzado.

-   Alertas clínicas.

-   WhatsApp.

-   SMS.

-   Recordatorios externos.

-   Firma digital.

-   Archivos clínicos.

-   Laboratorios.

-   Imágenes médicas.

-   Plantillas por especialidad.

Baseline al cerrar DT-9:

`393 tests verdes`

------------------------------------------------------------------------

## DT-10 --- Product inventory and development roadmap

Estado: Completado

Objetivo:

Construir un inventario funcional real del producto y establecer los
documentos maestros de seguimiento.

Incluye:

-   Auditoría de módulos existentes.

-   Inventario funcional.

-   Inventario clínico.

-   Inventario SaaS.

-   Inventario visual.

-   Identificación de deuda técnica.

-   Identificación de decisiones pendientes.

-   Creación de `TODO.md`.

-   Creación de `ROADMAP.md`.

-   Separación conceptual entre:

    -   trabajo completado;

    -   trabajo parcial;

    -   trabajo pendiente;

    -   decisiones de producto.

-   Definición de próximos bloques DT.

Baseline al cerrar DT-10:

`393 tests verdes`

`1179 assertions`

`0 failures`

Commit principal:

`1e468fb DT-10 docs: establish DocTotal product TODO and development roadmap DT-10`

------------------------------------------------------------------------

## DT-11 --- Subscription lifecycle foundation

Estado: Completado

Objetivo:

Construir la base del ciclo de vida de las suscripciones de DocTotal.

Incluye:

-   Modelo `Subscription`.

-   Ciclos mensual y anual.

-   Estado de la suscripción.

-   Periodo actual de servicio.

-   Billing anchor.

-   Conversión trial → suscripción.

-   Cancelación.

-   Cancelación programada.

-   Reanudación.

-   Reactivación.

-   Derecho de acceso basado en el estado de suscripción.

-   Integración con Tenant.

-   Protección multi-tenant.

-   Cobertura automatizada.

Decisiones:

DocTotal debe ser capaz de administrar automáticamente el derecho de
acceso al producto según el estado comercial del tenant.

Baseline al cerrar DT-11:

`445 tests verdes`

`0 failures`

Commit principal:

`6b35252 DT-11 feat: implement subscription lifecycle foundation`

------------------------------------------------------------------------

## DT-12 --- Payments, billing recovery and automatic account lifecycle

Estado: Completado

Objetivo:

Implementar pagos, recuperación de cobros y automatización del ciclo
comercial de la cuenta.

Incluye:

-   Integración con Stripe.

-   `BillingCustomer`.

-   `Payment`.

-   `PaymentMethod`.

-   SetupIntent.

-   PaymentIntent.

-   Métodos de pago guardados.

-   Renovaciones.

-   Renovaciones mensuales.

-   Renovaciones anuales.

-   Reintentos de pago.

-   Recuperación de pagos fallidos.

-   Grace period.

-   Estados `past_due`.

-   Suspensión automática.

-   Reactivación después de pago recuperado.

-   Cancelación programada.

-   Reanudación de suscripción.

-   Idempotencia.

-   Integración de pagos con Subscription.

-   Integración de pagos con Tenant.

-   Cobertura automatizada extensa.

Fuera de alcance de DT-12:

-   Facturación fiscal.

-   Webhooks Stripe completos.

-   Recibos/comprobantes finales.

-   Herramientas administrativas avanzadas.

Baseline al cerrar DT-12:

`696 tests verdes`

`0 failures`

Commit principal:

`9fe1106 DT-12 feat: finalize billing lifecycle hardening and documentation`

------------------------------------------------------------------------

## DT-13 --- Referral program and promotional credits

Estado: Completado

Objetivo:

Implementar un programa de referidos y créditos promocionales integrado
con el ciclo de facturación.

Incluye:

-   Modelo `Referral`.

-   Código de referido.

-   Enlace de referido.

-   Captura opcional de código de referido.

-   Prevención de auto-referidos.

-   Prevención de referidos duplicados.

-   Calificación por primer pago exitoso.

-   Descuento para el referido.

-   Crédito para el referidor.

-   Límites mensuales.

-   Modelo `PromotionalCredit`.

-   Reserva de créditos.

-   Consumo de créditos.

-   Liberación de créditos.

-   Idempotencia.

-   Integración con pagos.

-   Integración con renovaciones.

-   Integración con recuperación de pagos.

-   Protección multi-tenant.

-   Cobertura automatizada.

Decisiones:

El crédito promocional se administra como una entidad explícita y
auditable.

Los créditos no deben consumirse dos veces.

Las operaciones deben mantenerse idempotentes.

Baseline al cerrar DT-13:

`797 tests verdes`

`2244 assertions`

`0 failures`

Commit principal:

`f4d7322 DT-13 feat: complete referral program and promotional credits`

------------------------------------------------------------------------

## DT-14 --- Expediente clínico longitudinal

Estado: Completado

Objetivo:

Transformar el expediente del paciente en una vista clínica
longitudinal útil.

Incluye:

-   Resumen clínico.

-   Línea de tiempo clínica.

-   Consultas finalizadas.

-   Diagnósticos históricos.

-   Diagnóstico principal.

-   Tratamientos históricos.

-   Recetas históricas.

-   Navegación hacia consultas originales.

-   Navegación hacia recetas originales.

-   Consolidación de información clínica relevante.

-   Protección multi-tenant.

-   Tests del expediente longitudinal.

Decisiones:

Los diagnósticos históricos se obtienen desde consultas completadas.

Los tratamientos históricos se obtienen desde recetas asociadas al
paciente.

El expediente longitudinal no debe reinterpretar automáticamente los
datos clínicos.

Baseline al cerrar DT-14:

`814 tests verdes`

`2339 assertions`

`0 failures`

Commit principal:

`3df050f DT-14 feat: implement longitudinal clinical record`

------------------------------------------------------------------------

## DT-15 --- Clinical files and medical documents

Estado: Completado

Objetivo:

Implementar infraestructura segura para archivos y documentos clínicos.

Incluye:

-   Modelo `ClinicalDocument`.

-   UUID para routing.

-   Storage privado.

-   Upload de documentos.

-   PDF.

-   JPG.

-   JPEG.

-   PNG.

-   WebP.

-   Categorías documentales.

-   `general`.

-   `laboratory`.

-   `imaging`.

-   `other`.

-   Asociación con Patient.

-   Asociación opcional con Consultation.

-   Metadatos clínicos.

-   Fecha del estudio/documento.

-   Descripción.

-   Visualización inline segura.

-   Descarga segura.

-   Miniaturas protegidas para imágenes.

-   Eliminación controlada.

-   Límite actual de 10 MB por archivo.

-   Disco configurable con `CLINICAL_DOCUMENTS_DISK`.

-   Hardening de `StoreClinicalDocument`.

-   Protección multi-tenant.

-   Cobertura automatizada de almacenamiento, visualización, descarga y
    eliminación.

Fuera de alcance:

-   OCR.

-   DICOM/PACS.

-   Resultados de laboratorio estructurados.

-   Cuotas totales de almacenamiento por tenant.

-   Storage externo definitivo.

-   Políticas completas de retención y respaldo.

Baseline al cerrar DT-15:

`837 tests verdes`

`2395 assertions`

`0 failures`

Commit principal:

`07f73ec DT-15 feat: implement clinical files and medical documents`

------------------------------------------------------------------------

## DT-16 --- Visual redesign / DocTotal UI

Estado: Completado

Objetivo:

Definir e implementar una identidad visual consistente para DocTotal y
rediseñar sus principales áreas funcionales.

Dirección visual:

-   Producto médico moderno.

-   Estética tecnológica.

-   Sidebar azul marino / índigo oscuro.

-   Azul eléctrico como color principal.

-   Acentos violeta, cyan y verde.

-   Fondos claros azul/blanco.

-   Tarjetas blancas.

-   Bordes suaves.

-   Radios generosos.

-   Sombras discretas.

-   Iconografía lineal.

-   Badges elegantes.

-   Focus luminoso azul.

-   Responsive.

-   Componentes reutilizables.

Incluye:

-   Foundation visual.

-   Shell global.

-   Sidebar.

-   Header.

-   Navegación responsive.

-   Dashboard.

-   Pacientes.

-   Expediente clínico.

-   Agenda.

-   Consultas.

-   Recetas.

-   Onboarding.

-   Configuración.

-   Billing.

-   Autenticación.

-   Estados vacíos.

-   Estados de carga.

-   Feedback visual.

-   Consistencia general.

Decisiones:

La foundation visual global queda aprobada.

No deben hacerse cambios globales casuales al shell, sidebar o header
sin una razón de producto.

Baseline al cerrar DT-16:

`837 tests verdes`

`0 failures`

Commit principal:

`4e6d77d DT-16 feat: complete DocTotal visual redesign`

------------------------------------------------------------------------

## DT-17 --- Clinical workspace / Consulta médica avanzada

Estado: Completado

Objetivo:

Transformar la captura de consulta médica en un workspace clínico
avanzado con contexto persistente y protección de datos durante la
atención.

Incluye:

-   Workspace clínico responsive.

-   Layout amplio para uso clínico.

-   Panel lateral persistente.

-   Alergias.

-   Medicamentos actuales.

-   Enfermedades crónicas.

-   Cirugías.

-   Antecedentes relevantes.

-   Consultas recientes completadas.

-   Diagnósticos recientes.

-   Signos vitales.

-   Motivo de consulta.

-   SOAP.

-   Diagnósticos.

-   Autosave.

-   Estado de guardado visible.

-   Estado `guardando`.

-   Estado `guardado`.

-   Estado de error.

-   Protección contra pérdida de cambios.

-   `beforeunload`.

-   Validaciones en español.

-   Nombres amigables de atributos.

-   Resaltado visual de campos inválidos.

-   Scroll automático al primer error.

-   Focus automático al primer campo inválido.

-   Protección al finalizar cuando existen cambios pendientes.

-   Protección al finalizar cuando hay guardado en curso.

-   Protección al finalizar cuando existe error de guardado.

-   Revalidación backend antes de completar.

-   Finalización Consultation → Appointment únicamente después de
    validación correcta.

-   Continuidad de drafts.

-   Consulta directa sin cita.

-   Consulta desde Appointment.

-   Diagnóstico principal.

-   Integración con recetas.

-   Cobertura automatizada.

Decisiones clínicas y de producto:

`PatientMedicalHistory` continúa siendo la fuente explícita para:

-   alergias;

-   medicamentos actuales;

-   antecedentes;

-   enfermedades crónicas;

-   cirugías.

Las recetas históricas no se interpretan automáticamente como
medicamentos actuales.

Los límites de validación de signos vitales son límites técnicos de
captura y no constituyen rangos clínicos normales, alertas médicas ni
decisión clínica.

El workspace mantiene el historial reciente visible sin abandonar la
consulta.

Baseline al cerrar DT-17:

`840 tests verdes`

`0 failures`

Tests específicos de Consultations:

`76 tests verdes`

ConsultationFlowTest:

`18 tests verdes`

Commit principal:

`ff7aee4 DT-17 feat: implement advanced clinical consultation workspace`

------------------------------------------------------------------------

## DT-18 --- Documentation baseline normalization

Estado: Completado

Objetivo:

Normalizar la documentación maestra del proyecto después del cierre
funcional de DT-17.

Incluye:

-   Revisión de `TODO.md`.

-   Revisión de `ROADMAP.md`.

-   Sincronización contra el estado real del producto.

-   Corrección de avances acumulados.

-   Revisión de los bloques funcionales ya completados.

-   Revisión de pendientes reales.

-   Revisión del baseline de tests.

-   Recalculo ponderado del avance global.

Decisiones:

DT-18 se utilizó como bloque documental.

No se forzó funcionalidad adicional únicamente para justificar el
ticket.

La actualización de documentación se mantiene como parte ligera del
inicio/cierre de cada DT.

Baseline heredado:

`840 tests verdes`

`0 failures`

Avance global ponderado al cerrar DT-18:

`72%`

Integración:

DT-18 fue integrado en `master`.

------------------------------------------------------------------------

## DT-19 --- Structured active clinical problem list

Estado: Completado

Objetivo:

Implementar una lista estructurada y longitudinal de problemas clínicos
por paciente como evolución natural del expediente y del workspace
clínico.

Problema que resuelve:

Antes de DT-19, DocTotal tenía:

-   antecedentes médicos;

-   consultas;

-   diagnósticos;

-   recetas;

-   documentos clínicos;

-   historial longitudinal.

Sin embargo, no existía una entidad explícita para distinguir
longitudinalmente problemas clínicos:

-   activos;

-   resueltos.

DT-19 introduce esa estructura sin inferir automáticamente decisiones
médicas desde el historial.

Incluye:

-   Modelo `PatientProblem`.

-   Persistencia multi-tenant.

-   Trait `BelongsToTenant`.

-   Soft deletes.

-   Relación con Patient.

-   Estados:

    -   `active`;

    -   `resolved`.

-   Código opcional.

-   Descripción.

-   Fecha de inicio.

-   Fecha de resolución.

-   Notas.

-   Índices de base de datos.

-   Relación `Patient → problems`.

-   CRUD dentro del expediente.

-   Crear problema.

-   Editar problema.

-   Resolver problema.

-   Reabrir problema.

-   Eliminar con soft delete.

-   Orden de problemas activos antes de resueltos.

-   Historial de problemas resueltos.

-   Protección por paciente.

-   Protección multi-tenant.

Autocomplete:

-   Reutilización de `DiagnosisCatalog`.

-   Búsqueda por código.

-   Búsqueda por descripción.

-   Resultados ordenados por relevancia.

-   Selección desde catálogo.

-   Autollenado de código.

-   Autollenado de descripción.

-   Captura manual preservada.

-   No dependencia obligatoria del catálogo.

Integración con expediente:

-   Sección visual `Problemas clínicos`.

-   Problemas activos.

-   Problemas resueltos.

-   Código.

-   Descripción.

-   Fecha de inicio.

-   Fecha de resolución.

-   Notas en problemas activos.

-   Acciones de edición.

-   Acción para marcar como resuelto.

-   Acción para reabrir.

-   Acción para eliminar.

Integración con consulta:

-   Eager load de problemas activos.

-   Problemas clínicos activos visibles en el panel de contexto.

-   Código visible.

-   Descripción visible.

-   Fecha de inicio visible.

-   Notas visibles.

-   Problemas resueltos excluidos del contexto activo.

-   La consulta no abandona el workspace para consultar esta
    información.

Decisiones de arquitectura:

`PatientProblem` es una entidad longitudinal explícita.

No se infiere automáticamente desde `ConsultationDiagnosis`.

`DiagnosisCatalog` se utiliza únicamente como ayuda de captura.

`PatientMedicalHistory` continúa siendo fuente explícita de:

-   alergias;

-   medicamentos actuales;

-   antecedentes;

-   enfermedades crónicas;

-   cirugías.

No se agregó UUID a `PatientProblem` porque actualmente no tiene
routing público independiente.

Podrá evaluarse posteriormente si el diseño de rutas lo requiere.

Fuera de alcance de DT-19:

-   Alertas clínicas automáticas.

-   Reglas médicas automáticas.

-   Inferencia automática desde diagnósticos históricos.

-   Interacciones farmacológicas.

-   OCR.

-   Resultados estructurados de laboratorio.

-   DICOM/PACS.

-   WhatsApp.

-   SMS.

-   Email transaccional.

Cobertura automatizada:

`PatientProblemTest`:

-   pertenencia a Patient;

-   pertenencia al tenant actual;

-   relación Patient → problems;

-   resolución;

-   reapertura;

-   aislamiento entre tenants;

-   ausencia de tenant context.

`PatientProblemFlowTest`:

-   crear;

-   validar descripción obligatoria;

-   editar;

-   resolver;

-   reabrir;

-   soft delete;

-   impedir manipulación desde otro paciente.

Expediente:

-   visualización de problemas activos;

-   visualización de problemas resueltos.

Consulta:

-   visualización de problemas activos;

-   exclusión de problemas resueltos del contexto activo.

Resultados de regresión:

`13 tests verdes` en PatientProblemTest + PatientProblemFlowTest.

`57 tests verdes` en Patients.

`76 tests verdes` en Consultations.

`147 tests verdes` en la regresión combinada relacionada con DT-19.

Suite completa al cierre técnico:

`854 tests verdes`

`0 failures`

No se registró un número final de assertions.

No debe inferirse ni inventarse.

Avance global ponderado al cierre técnico de DT-19:

`74%`

Cierre definitivo:

-   Documentación actualizada.

-   Commit final realizado.

-   Merge a `master`.

-   Comentario técnico registrado en Jira.

-   DT-19 transicionado a `Listo`.

Commit principal:

`1dd4ad7 DT-19 feat: implement structured active clinical problem list`

------------------------------------------------------------------------

## DT-20 --- Transactional communications and appointment reminders foundation

Estado: Completado

Objetivo:

Construir una foundation multi-tenant, persistente, auditable y
extensible para comunicaciones transaccionales, implementando como
primer caso real los recordatorios de citas.

Incluye:

-   Modelo `Communication`.
-   Relaciones con Patient y Appointment.
-   Estados `pending`, `sent`, `failed` y `cancelled`.
-   Snapshot de destinatario, contenido y metadata.
-   Idempotencia por tenant.
-   Contrato `CommunicationTransport`.
-   `CommunicationTransportManager`.
-   Preparación para email, WhatsApp y SMS.
-   Ausencia segura de proveedor configurado.
-   `CommunicationProcessor`.
-   Máximo de 3 intentos.
-   Backoff de 5 y 15 minutos.
-   `communications:process-due`.
-   `AppointmentReminderService`.
-   Generación idempotente por cita, canal y horario.
-   `communications:generate-appointment-reminders`.
-   Scheduler.
-   `AppointmentReminderValidator`.
-   Cancelación auditable de recordatorios obsoletos.
-   Protección ante reprogramación y cambios de estado.
-   Historial visual de comunicaciones dentro de la cita.
-   Protección multi-tenant.

Decisiones:

DocTotal no utiliza un transport nulo que simule éxito.

Si no existe transport configurado, la comunicación permanece pendiente
y no consume intento.

Una reprogramación crea una nueva identidad de recordatorio y el
anterior se conserva como cancelado para auditoría.

La capa de comunicaciones permanece independiente de proveedores
concretos.

Fuera de alcance:

-   Campañas de marketing.
-   Envíos masivos.
-   Proveedores reales obligatorios.
-   Confirmación externa por paciente.
-   Alertas clínicas.
-   Inferencia médica automática.

Validación:

`56 tests verdes` en la regresión específica de DT-20.

`148 tests verdes` en appointments después de la integración visual.

Suite completa al cierre:

`910 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre:

`77%`

Cierre definitivo:

-   Documentación actualizada.
-   Commit final realizado.
-   Merge a `master`.
-   Comentario técnico registrado en Jira.
-   DT-20 transicionado a `Listo`.

Commit principal:

`9192020 DT-20 feat: implement transactional communications and appointment reminders`

------------------------------------------------------------------------

## DT-21 --- Audit trail and security hardening foundation

Estado: Completado

Objetivo:

Construir una foundation reutilizable de auditoría y hardening para
registrar acciones sensibles de forma persistente, multi-tenant y
trazable, sin acoplar la lógica clínica u operativa a una
implementación específica de logging.

Incluye:

-   Modelo `AuditEvent`.
-   Persistencia multi-tenant mediante `BelongsToTenant`.
-   Asociación opcional con usuario/actor.
-   Asociación polimórfica con el recurso auditado.
-   Acción, descripción, IP, user agent y metadata controlada.
-   Índices por tenant, actor, acción y recurso.
-   `AuditLogger`.
-   Sanitización recursiva de metadata sensible.
-   Redacción conservadora de claves relacionadas con password, token,
    authorization, cookie, secret y api_key.
-   `safeLog()` para auditoría best-effort.
-   Registro técnico del fallo de auditoría sin romper la operación
    principal.
-   Protección append-only a nivel de modelo Eloquent para impedir
    update/delete normales sobre eventos existentes.
-   Auditoría inicial de `patient.updated`.
-   Auditoría inicial de `consultation.completed`.
-   Auditoría inicial de `appointment.rescheduled`.
-   Auditoría inicial de `appointment.cancelled`.
-   Metadata mínima para evitar duplicar payload clínico sensible.
-   Historial visual de actividad en el expediente del paciente.
-   Paginación de 5 eventos por página en el historial visual.
-   Actor, descripción y fecha/hora visibles.
-   Detalles técnicos como IP, user agent, IDs internos y metadata no
    expuestos en la tarjeta visual.
-   Protección multi-tenant.
-   Cobertura automatizada de modelo, logger, aislamiento, integridad,
    redacción, fiabilidad e integraciones auditadas.

Decisiones de arquitectura y seguridad:

La auditoría de DT-21 es best-effort: una falla al persistir el evento
no debe cambiar el resultado funcional de la acción principal.

`safeLog()` registra el error técnico en el log de Laravel y devuelve
`null` cuando la persistencia del evento falla.

La protección append-only implementada es a nivel Eloquent. No debe
interpretarse como inmutabilidad garantizada por la base de datos,
porque operaciones directas/query builder pueden omitir eventos del
modelo.

La metadata de auditoría debe contener contexto mínimo y controlado.
No se guardan contraseñas, tokens, secretos ni payload clínico
innecesario.

El historial visual del paciente consulta únicamente eventos cuyo
recurso auditado es el propio Patient. Los eventos auditados sobre
Consultation o Appointment conservan su trazabilidad persistente, pero
no se mezclan automáticamente en esa tarjeta.

Fuera de alcance de DT-21:

-   SIEM completo.
-   Auditoría exhaustiva de todas las lecturas.
-   Backups y restauración integral.
-   2FA obligatorio.
-   Passkeys.
-   Gestión avanzada de dispositivos.
-   Política legal definitiva de retención.
-   Auditoría exhaustiva de billing.
-   Garantía de inmutabilidad a nivel de base de datos.
-   Outbox transaccional para garantizar persistencia de auditoría ante
    fallos.
-   Alertas clínicas o inferencia médica.

Validación:

Regresión focalizada de DT-21:

`58 tests verdes`

Regresión del historial clínico visual con paginación:

`13 tests verdes`

Regresión de cancelación de cita después del ajuste final a
`safeLog()`:

`11 tests verdes`

Suite completa final:

`936 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre técnico:

`79%`

Cierre definitivo:

-   Commit final realizado.
-   Merge a `master`.
-   Cierre Jira realizado.

Commit principal:

`c3c70d9 DT-21 feat: implement audit trail and security hardening foundation`

------------------------------------------------------------------------

## DT-22 --- Internal SaaS administration panel foundation

Estado: Completado e integrado en `master`.

Objetivo:

Construir una consola administrativa interna separada de la experiencia
clínica de los tenants, con visibilidad operativa global de DocTotal y
una frontera explícita para lecturas cross-tenant.

Implementado:

-   Rol `internal_admin` para operadores internos sin tenant asociado.
-   Middleware `internal.admin`.
-   Shell administrativo independiente del producto clínico.
-   Dashboard operacional SaaS.
-   Indicadores globales de tenants, usuarios, trials, suscripciones y
    pagos.
-   Listado global y detalle operativo de tenants.
-   Presentación de inicio, fin, duración y días restantes/vencidos del
    trial.
-   Estado efectivo del servicio.
-   Visibilidad de suscripciones activas y `past_due`.
-   Incidencias globales de billing y pagos fallidos.
-   Distinción de grace period vigente y vencido.
-   Monitoreo global de comunicaciones y errores.
-   Acceso operativo controlado a eventos de auditoría de DT-21.
-   `InternalSaasOverviewService` como frontera explícita para lecturas
    globales.
-   Eliminación exclusiva de `TenantScope` cuando corresponde.
-   Comando `doctotal:make-internal-admin`.
-   Redirección post-login de operadores internos hacia `/internal`.
-   Conservación del flujo normal `/dashboard` para usuarios de tenant.
-   Middleware `service.access` para proteger funcionalidad clínica.
-   Pantalla de servicio suspendido.
-   Billing accesible aun cuando el servicio clínico está bloqueado.
-   Cobertura automatizada del panel interno, aislamiento y reglas de
    acceso.
-   Ajuste de fixtures históricos para representar tenants clínicos con
    acceso vigente.

Decisiones de arquitectura:

La consola interna no utiliza el shell clínico ni depende de
`TenantContext`.

Un operador interno válido debe tener rol `internal_admin` y
`tenant_id = null`.

Las lecturas globales sobre modelos tenant-scoped deben estar
encapsuladas y testeadas; no se permiten bypasses globales dispersos.

La expiración del trial no debe mutar automáticamente `Tenant.status`.
El acceso efectivo depende de trial, suscripción, grace period y
suspensión/cancelación explícita.

Las futuras acciones administrativas sensibles deberán integrarse con la
foundation de auditoría de DT-21.

Fuera de alcance:

-   Impersonación.
-   Edición arbitraria de información clínica.
-   SIEM completo.
-   Herramientas destructivas masivas.
-   Analítica financiera definitiva.
-   Cambios profundos al motor de billing únicamente para alimentar el
    panel.
-   Exposición de secretos o payload clínico innecesario.

Validación final:

Suite completa:

`988 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Commit funcional más reciente:

`228c9b2 DT-22 feat: complete operational dashboard and enforce tenant service access`

El porcentaje global ponderado vigente permanece en `79%`, último valor
formalmente calculado. No se infiere un porcentaje nuevo sin aplicar
nuevamente el criterio ponderado del producto.

------------------------------------------------------------------------

## DT-23 --- Billing recovery must honor plan change for unpaid past-due subscription

Estado: Completado e integrado en `master`.

Objetivo:

Corregir la recuperación de suscripciones `past_due` para que un cambio de plan realizado antes del pago sea respetado por el checkout y se vuelva contractual únicamente después de un pago exitoso.

Incluye:

-   Distinción entre cambio programado de una suscripción pagada y plan elegido para recuperación de una obligación no pagada.
-   Resolución del importe/ciclo correcto para checkout de recuperación.
-   Prevención de reutilización de PaymentIntent o idempotency key incompatible.
-   Aplicación atómica del nuevo ciclo después del pago exitoso.
-   Conservación de créditos promocionales y referidos.
-   Compatibilidad con pagos históricos sin snapshot contractual completo.
-   `recoverableSubscription()` separado del derecho efectivo de acceso al servicio.
-   Reactivación correcta después del pago, sin reactivación falsa ante fallo o abandono.

Validación final:

`995 tests verdes`

`0 failures`

Commit principal:

`bbd9ce6 DT-23 fix: honor recovery plan for past-due subscriptions`

------------------------------------------------------------------------

## DT-24 --- Patient self-service for appointment confirmation and cancellation

Estado: Completado e integrado en `master`.

Objetivo:

Permitir que el paciente gestione de forma segura una cita desde un enlace público sin cuenta ni sesión en DocTotal.

Incluye:

-   Token público aleatorio de alta entropía almacenado únicamente como hash.
-   Lookup público sin enumeración por UUID y con bypass explícito únicamente de `TenantScope`.
-   Vista pública con información mínima no clínica.
-   Confirmación pública `scheduled` → `confirmed`.
-   Confirmación idempotente.
-   Cancelación pública desde `scheduled` o `confirmed`.
-   Bloqueo de acciones públicas en estados clínicos/terminales.
-   Invalidación del token previo cuando una cita se reprograma.
-   Nuevo enlace en el recordatorio posterior a una reprogramación.
-   Integración con la infraestructura de recordatorios de DT-20.
-   Auditoría de acciones públicas sin persistir el token.
-   Cobertura de seguridad, multi-tenancy y acceso sin autenticación.

Fuera de alcance:

-   Reprogramación pública con selección de nuevos horarios.
-   Portal completo del paciente.

Validación final:

`1002 tests verdes`

`0 failures`

Commit principal:

`12d573b DT-24 feat: add patient appointment self-service`

------------------------------------------------------------------------

## DT-25 --- Manual sharing of patient appointment management link

Estado: Completado e integrado en `master`.

Objetivo:

Permitir que el médico o personal autorizado genere y comparta manualmente el enlace público seguro de una cita sin depender de un proveedor de mensajería.

Incluye:

-   Generación/regeneración manual desde el detalle de la cita.
-   URL compacta `/a/{token}` con token URL-safe de alta entropía.
-   Persistencia únicamente del hash SHA-256 del token.
-   Compatibilidad con enlaces públicos anteriores de DT-24.
-   Copiar enlace y copiar mensaje completo.
-   Abrir WhatsApp con número y mensaje precargados.
-   Normalización de números mexicanos para WhatsApp.
-   Abrir correo con destinatario, asunto y cuerpo precargados.
-   Mensaje humano con médico, clínica/consultorio, fecha en español y hora.
-   Reutilización de la lógica común de enlace/mensaje por el recordatorio automático.
-   Regeneración con invalidación inmediata del enlace previo.
-   Vista pública amigable para enlaces inexistentes/invalidados conservando HTTP 404.
-   Ninguna acción manual se marca como enviada sin confirmación de un transport integrado.

Validación final:

`1005 tests verdes`

`0 failures`

Commit principal:

`240bcf6 DT-25 feat: add manual patient appointment link sharing`.

------------------------------------------------------------------------

## DT-26 --- Clinical templates for medical records

Estado: Completado e integrado en `master`.

Objetivo:

Agregar plantillas clínicas reutilizables para agilizar la captura de consultas conservando inmutable el contenido ya aplicado al expediente.

Incluye:

-   Administración de plantillas clínicas por tenant.
-   Nombre, descripción, motivo de consulta y contenido SOAP estructurado.
-   Crear, editar, activar y desactivar plantillas.
-   Aplicar una plantilla desde el workspace de consulta.
-   Copia del contenido como snapshot: la consulta no mantiene dependencia mutable con la plantilla.
-   Contador de usos.
-   Eliminación únicamente de plantillas nunca utilizadas.
-   Aislamiento multi-tenant mediante la foundation existente.
-   Auditoría de operaciones relevantes y aplicación.
-   Confirmaciones con SweetAlert y botones integrados al sistema visual de DocTotal.
-   Cobertura automatizada de aislamiento y flujo funcional.

Validación final:

`1011 tests verdes`

`0 failures`

Commit principal:

`4ceac23 DT-26 feat: add reusable clinical templates`.

------------------------------------------------------------------------

------------------------------------------------------------------------

## DT-27 --- Structured laboratory results in clinical records

Estado: Completado e integrado en `master`.

Objetivo:

Agregar captura estructurada de resultados de laboratorio dentro del expediente clínico, con historial por paciente y asociación opcional a consulta.

Incluye:

-   Estudios de laboratorio por paciente.
-   Asociación opcional a una consulta del mismo paciente.
-   Fecha, laboratorio/proveedor y observaciones.
-   Resultados estructurados con parámetro, valor, unidad y rango de referencia.
-   Soporte de valores numéricos y textuales.
-   Historial de laboratorios dentro del expediente.
-   Tarjeta/resumen de Laboratorios integrada al expediente.
-   Captura manual de parámetros.
-   Pegado masivo desde Excel/Google Sheets o texto tabulado, `|` o `;`.
-   Conversión a filas editables antes del guardado.
-   Aislamiento multi-tenant.
-   Auditoría de creación, actualización y eliminación.
-   Eliminación segura de resultados asociados.
-   Cobertura automatizada del flujo funcional, aislamiento y captura masiva.

Decisiones:

-   El registro estructurado de laboratorio es independiente del documento clínico original.
-   Un futuro bloque podrá vincular el estudio estructurado con su documento fuente.
-   La captura automática desde PDF requiere revisión humana antes de persistir datos clínicos.

Fuera de alcance:

-   OCR y extracción automática desde PDF/imagen.
-   HL7/FHIR e importaciones de proveedores.
-   Interpretación clínica automática o IA.
-   Catálogos nacionales.
-   Gráficas longitudinales avanzadas.

Validación final:

`1021 tests verdes`

`0 failures`

Commit principal:

`a1a66a1 DT-27 feat: add structured laboratory results`.


## DT-28 --- Complete account security and recovery flows

Estado: Completado e integrado en `master`.

Objetivo:

Completar los flujos de seguridad de cuenta y recuperación sobre Laravel Fortify sin debilitar el acceso de recuperación para tenants suspendidos.

Incluye:

-   Cambio de contraseña desde Configuración → Seguridad con contraseña actual y confirmación.
-   Auditoría `account.password.updated` sin credenciales.
-   2FA TOTP con QR, confirmación, recovery codes, regeneración y desactivación.
-   Challenge 2FA durante login y soporte de códigos de recuperación.
-   Reautenticación para acciones sensibles de 2FA.
-   Administración visible de sesiones/dispositivos.
-   Revocación de una sesión específica o de todas las demás sesiones.
-   Fingerprints opacos en lugar de IDs reales de sesión.
-   Rotación del remember token al revocar sesiones para invalidar cookies persistentes antiguas.
-   Verificación de correo mediante enlaces firmados y reenvío.
-   Middleware `verified` en áreas protegidas de la aplicación.
-   Configuración → Seguridad y aviso de verificación disponibles para recuperación incluso con tenant suspendido.
-   Redirección correcta del administrador interno después de verificar correo o completar 2FA.
-   Auditoría de verificación de correo sin almacenar URL firmada ni hash de verificación.

Passkeys/WebAuthn:

-   Evaluadas contra la infraestructura real de Laravel 13/Fortify.
-   La foundation de backend y base de datos está disponible.
-   Activación diferida hasta fijar el hostname productivo HTTPS canónico y los valores definitivos de WebAuthn relying party/origins.
-   No se introduce una implementación temporal ligada a dominios locales.

Calidad final DT-28:

`1068 tests verdes`

`0 failures`

Commit principal:

`762b3d3 DT-28 feat: complete account security and recovery flows`.


------------------------------------------------------------------------

## DT-29 --- Repeat previous treatments and prescriptions

Estado: Completado e integrado en `master`.

Objetivo:

Permitir crear una nueva receta a partir de una receta anterior del mismo
paciente sin modificar la emisión clínica fuente.

Incluye:

-   Acción de dominio `RepeatPrescription`.
-   Relación opcional `source_prescription_id` hacia la receta origen.
-   Formulario de repetición con datos precargados y editables antes de guardar.
-   Nueva receta con UUID, fecha, estado e ítems propios.
-   Creación de nuevos `PrescriptionItem` mediante allowlist explícita.
-   Copia de medicamento, presentación, dosis, frecuencia, duración,
    instrucciones e instrucciones generales.
-   Asociación obligatoria al mismo paciente.
-   Médico de la nueva receta tomado del usuario actual, no de la receta fuente.
-   Nueva receta independiente con `consultation_id = null`.
-   Repetición desde el detalle de receta y desde el historial longitudinal.
-   Acceso desde recetas asociadas a consulta, recetas independientes y
    tratamientos consolidados.
-   Las recetas canceladas permanecen visibles en historial pero no pueden repetirse.
-   Revalidación de receta fuente, paciente y acceso al momento de guardar.
-   Aislamiento multi-tenant y rechazo de endpoints cross-tenant.
-   Auditoría `prescription.repeated` con referencia al origen sin incluir
    el contenido del tratamiento en metadata.
-   Independencia entre fuente y copia ante edición, cancelación o eliminación.
-   Impresión y PDF de la copia basados en la nueva emisión.
-   Repetición encadenada con referencia al origen inmediato.
-   La repetición no modifica `PatientMedicalHistory.current_medications_text`.
-   Cobertura automatizada en `PrescriptionRepeatTest` y
    `PrescriptionRepeatHistoryTest`.

Validación al cierre técnico:

-   Bloque 2 de repetición/historial: `16 tests verdes`.
-   Regresión de recetas: `112 tests verdes`.
-   Suite completa: `1110 tests verdes`.
-   `0 failures`.

Base de desarrollo:

`762b3d3 DT-28 feat: complete account security and recovery flows`

Commit principal:

`86420d1 DT-29 feat: add prescription repeat workflow`


------------------------------------------------------------------------

## DT-30 --- Patient self-service appointment rescheduling with available slots

Estado: Completado e integrado en `master`.

Objetivo:

Permitir que el paciente reprograme una cita existente desde su enlace
público de gestión, seleccionando únicamente horarios realmente
disponibles y sin requerir cuenta ni sesión.

Incluye:

-   Acción pública `Reprogramar mi cita` para citas `scheduled` y `confirmed`.
-   Selección de fecha y slots mediante `AppointmentAvailabilityService`.
-   Conservación de paciente, tenant, médico y duración.
-   Actualización de la misma entidad `Appointment`.
-   Revalidación server-side de disponibilidad antes de guardar.
-   Rechazo de horarios pasados, fuera de agenda, bloqueados u ocupados.
-   Bloqueo transaccional de la cita y del perfil médico para endurecer concurrencia.
-   Protección frente a manipulación de paciente, médico o tenant.
-   Bloqueo de estados clínicos y terminales.
-   Una cita confirmada vuelve a `scheduled` y limpia `confirmed_at`.
-   Rotación del token público e invalidación del enlace anterior.
-   Recordatorio anterior obsoleto y nueva identidad posible para el horario reprogramado.
-   Auditoría `appointment.public_rescheduled` con metadata mínima y sin token.
-   Vista pública sin datos clínicos sensibles ni identificadores internos.

Validación final reportada:

-   `PublicAppointmentRescheduleTest`: `9 tests verdes`, `43 assertions`.
-   `AppointmentReminder*`: `24 tests verdes`, `68 assertions`.
-   `PublicAppointmentSelfServiceTest`: `7 tests verdes`, `22 assertions`.
-   Suite completa: `1119 tests verdes`.
-   `0 failures`.
-   `git diff --check` limpio.

Base canónica:

`86420d1 DT-29 feat: add prescription repeat workflow`

Commit principal:

`bf99782 DT-30 feat: add public appointment rescheduling`

Fuera de alcance:

-   Portal completo del paciente.
-   Creación pública de citas desde cero.
-   Cambio público de médico.
-   Pagos del paciente.
-   Proveedor externo obligatorio de WhatsApp/email/SMS.
-   Telemedicina.

------------------------------------------------------------------------

## DT-31 --- Automate DocTotal CI validation with GitHub Actions

Estado: Completado e integrado en `master`.

Commit principal:

`2ddfb92 DT-31 feat: automate CI validation with GitHub Actions`

Objetivo:

Automatizar la validación técnica de ramas `DT-*` y pull requests hacia
`master`, reduciendo la dependencia de ejecución manual local y haciendo
visible el resultado de calidad directamente en GitHub.

Incluye:

-   GitHub Actions en `push` a ramas `DT-*`.
-   GitHub Actions en `pull_request` hacia `master`.
-   Ejecución manual opcional mediante `workflow_dispatch`.
-   PHP 8.4.
-   Node.js 22.
-   Composer instalado de forma reproducible desde `composer.lock`.
-   Frontend instalado mediante `npm ci`.
-   Entorno Laravel aislado de producción.
-   SQLite in-memory para la suite.
-   Mail, cache, session y queue no productivos.
-   Billing/provider configurado con valores de test, sin credenciales reales.
-   Build de assets frontend.
-   Validación de sintaxis PHP en `app`, `routes`, `database` y `tests`.
-   Suite completa mediante `php artisan test`.
-   Cancelación de ejecuciones obsoletas por concurrencia.
-   Permisos de repositorio de solo lectura durante CI.
-   Sin deployment automático.
-   Sin merge automático.
-   Aprobación humana obligatoria antes de integrar a `master`.

Validación real:

-   Trigger por `push` a `DT-31`: correcto.
-   Trigger por PR hacia `master`: correcto.
-   Job `PHP 8.4 · Laravel test suite`: success.
-   Suite validada: `1119 tests verdes`.
-   `3484 assertions`.
-   `0 failures`.

PR:

`PR #33 — DT-31 feat: automate CI validation with GitHub Actions`

Flujo operativo resultante:

`Kai actualiza DT-* → GitHub Actions valida → Kai corrige si es necesario → CI verde → Alecz realiza validación manual/visual cuando corresponda → PR/revisión → aprobación humana explícita → merge`

Fuera de alcance:

-   Deployment a producción.
-   Merge automático.
-   Sustituir la validación manual/visual de producto.
-   Credenciales reales de WhatsApp, email, SMS, pagos u otros proveedores.
-   Servicios pagados nuevos.

------------------------------------------------------------------------

## DT-32 --- Harden transactional communications and appointment reminders

Estado: Completado

Objetivo:

Endurecer la foundation de DT-20 para comunicaciones transaccionales y recordatorios de citas sin acoplar el dominio a proveedores reales.

Incluye:

-   Preferencias explícitas por paciente y canal para email, WhatsApp y SMS.
-   `PatientCommunicationEligibilityService`.
-   Estado `processing` y `processing_started_at`.
-   Claim transaccional antes de contactar al transport para evitar procesamiento concurrente duplicado.
-   Reintentos existentes preservados con máximo de 3 intentos y backoff.
-   Redacción de secretos/tokens en errores persistidos.
-   `FakeCommunicationTransport` determinista y sin I/O externo.
-   Idempotencia existente de recordatorios por appointment UUID + canal + timestamp preservada.
-   Aislamiento multi-tenant preservado.
-   Sin credenciales reales, deployment ni merge automático.

Validación técnica:

-   GitHub Actions en push DT-32: success.
-   Suite completa: `1123 tests verdes`.
-   `3499 assertions`.
-   `0 failures`.
-   PR #34 abierto hacia `master`.

Fuera de alcance:

-   Campañas de marketing o mensajería masiva.
-   Alertas clínicas inteligentes.
-   Activación obligatoria de proveedores reales.
-   Contratación de servicios externos.
-   Deployment o merge automático.

------------------------------------------------------------------------
## Próximos candidatos

Los siguientes bloques no están todavía comprometidos como DT
definitivo.

Deben evaluarse al iniciar el siguiente bloque de desarrollo.

### Candidato A --- Alertas clínicas inteligentes

Objetivo:

Construir alertas clínicas contextuales sobre la base estructurada de
`PatientProblem`.

Base disponible:

Patient

→ PatientMedicalHistory

→ PatientProblem

→ Consultation

→ ConsultationDiagnosis

→ Prescription

→ ClinicalDocument

Pendiente:

-   Definir tipos de alertas.

-   Definir reglas explícitas.

-   Definir prioridad.

-   Definir presentación visual.

-   Definir si algunas alertas requieren confirmación.

-   Evitar inferencia médica automática no validada.

-   Evitar convertir validaciones técnicas en decisión clínica.

Este bloque requiere definición clínica y de producto antes de
implementación.

### Candidato B --- Comunicaciones y recordatorios

Estado:

Foundation realizada en DT-20 y endurecida en DT-32 con preferencias por canal,
elegibilidad centralizada, protección de procesamiento concurrente y redacción
de errores. Permanecen evoluciones futuras como proveedores reales y nuevos
tipos de comunicación.

Objetivo:

Implementar comunicación transaccional y recordatorios operativos.

Incluye potencialmente:

-   Email.

-   WhatsApp.

-   SMS.

-   Confirmación de citas.

-   Recordatorios de citas.

-   Cancelaciones.

-   Reprogramaciones.

-   Avisos de trial.

-   Avisos de pago.

-   Recuperación de pagos.

-   Suspensión.

-   Reactivación.

Decisiones pendientes:

-   Proveedores.

-   Consentimiento.

-   Trazabilidad.

-   Reintentos.

-   Costos por canal.

### Candidato C --- Seguridad, privacidad y auditoría

Estado:

Foundation de auditoría realizada en DT-21. Permanecen hardening
avanzado, backups, sesiones, 2FA/passkeys, retención y observabilidad.

Objetivo:

Preparar DocTotal para una operación SaaS clínica más robusta.

Incluye potencialmente:

-   Auditoría de acciones sensibles.

-   Historial formal de cambios clínicos.

-   Logs operativos.

-   Backups.

-   Restauración.

-   Políticas de retención.

-   Seguridad de sesiones.

-   Revocación de dispositivos.

-   2FA.

-   Passkeys.

-   Verificación de correo.

-   Hardening previo a producción.

### Candidato D --- Operación interna SaaS

Objetivo:

Construir herramientas internas para operar DocTotal como servicio.

Incluye potencialmente:

-   Panel administrativo.

-   Gestión de tenants.

-   Métricas.

-   Soporte.

-   Auditoría comercial.

-   Observabilidad.

-   Gestión de incidencias.

-   Herramientas de billing.

### Candidato E --- Trial y lifecycle avanzado

Objetivo:

Completar la experiencia comercial alrededor del periodo de prueba y
cierre de cuenta.

Incluye potencialmente:

-   Avisos de trial.

-   Pantalla de expiración.

-   Comunicación previa al vencimiento.

-   Eliminación programada.

-   Recuperación antes de eliminación.

-   Retención después de cancelación.

-   Auditoría de transiciones.

### Candidato F --- Storage y documentos avanzados

Objetivo:

Evolucionar la infraestructura documental clínica.

Incluye potencialmente:

-   Cuotas por tenant.

-   Indicadores de almacenamiento.

-   Storage externo.

-   Backups.

-   Retención.

-   OCR.

-   Laboratorios estructurados.

-   DICOM/PACS.

------------------------------------------------------------------------

## Estado global actual

DT completados e integrados en `master`:

`DT-1 → DT-29`

DT-30 tiene cierre técnico validado e integración preparada mediante PR #32.

DT-31 permanece `Por hacer` en Jira.

Baseline funcional validado en DT-30:

`1119 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado vigente:

`79%`

El 79% es el último porcentaje formalmente calculado y no se sustituye por una estimación.

DT-25 completa la compartición manual del enlace seguro de gestión de citas mediante copiar, WhatsApp y correo, sin depender de transports configurados.

DT-26 completa la foundation de plantillas clínicas reutilizables por tenant y su aplicación como snapshot dentro del workspace de consulta. Las plantillas por especialidad permanecen como evolución posterior.

DT-27 completa la foundation de laboratorios estructurados por paciente, con parámetros editables, historial, asociación opcional a consulta y captura masiva revisable. OCR/importación desde PDF permanece como evolución posterior.

DT-28 completa la seguridad de cuenta priorizada después de DT-27 y está integrado en `master`.

DT-29 completa la repetición de tratamientos/recetas mediante nuevas
emisiones independientes, editables antes del guardado y trazables hacia
su receta origen, sin modificar el historial clínico fuente.

DocTotal cuenta actualmente con una base clínica, operativa, SaaS, visual, de comunicaciones, auditoría, operación administrativa interna y autoservicio básico de citas considerablemente más madura que al inicio del roadmap.

La selección y ejecución del siguiente DT debe seguir priorizando:

-   valor clínico;
-   valor operativo;
-   riesgo SaaS;
-   seguridad;
-   multi-tenancy;
-   cobertura automatizada;
-   dependencias técnicas;
-   experiencia del usuario;
-   preparación para producción.

------------------------------------------------------------------------

## DT-33 --- Close DocTotal 1.0 billing production readiness

Estado: Completado

Objetivo:

Cerrar las brechas críticas de facturación necesarias para operación comercial de DocTotal 1.0 sin reconstruir el lifecycle existente.

Incluye:

- Webhook Stripe autenticado mediante firma.
- Registro persistente e idempotente de eventos del proveedor.
- Sincronización de `payment_intent.succeeded`, `payment_intent.payment_failed` y `payment_intent.canceled`.
- Recuperación segura de eventos previamente fallidos.
- Validación de identidad DocTotal, tenant, PaymentIntent, importe, moneda, customer y contexto de suscripción.
- Sincronización de pagos manuales, recuperación manual y cobros automáticos.
- Preservación de los flujos de recovery, retries, grace period, suspensión y reactivación ya existentes.
- Auditoría controlada de eventos procesados sin almacenar secretos ni payload completo.
- Comprobante operativo de pago para pagos exitosos, explícitamente no fiscal.
- Protección multi-tenant del comprobante.
- Cobertura automatizada para éxito, fallo, cancelación, idempotencia, tenant isolation y recuperación automática.
- Validación completa mediante GitHub Actions CI #37.

Fuera de alcance:

- CFDI/facturación fiscal mexicana.
- Rediseño completo de billing.
- Cambios de precios o planes.
- Deployment automático.
- Merge automático.

Resultado:

El bloque de billing queda preparado para la etapa final de producción de DocTotal 1.0, manteniendo Stripe detrás de las abstracciones existentes y sin credenciales reales en repositorio o CI.
