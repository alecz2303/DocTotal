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

- `TenantScope`.

- Trait `BelongsToTenant`.

- Cobertura automatizada de aislamiento.

Commit principal:

`DT-3 Implement tenant isolation and request resolution`

---

## DT-4 — Patient clinical record foundation

Estado: Completado

Objetivo:

Construir la base del expediente clínico del paciente.

Incluye:

- Pacientes.

- Contactos de emergencia.

- Antecedentes médicos.

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

- Cierre de sesión.

- Dashboard inicial.

- Trial.

- Asociación del usuario con su tenant.

- Infraestructura Laravel Fortify.

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

- Cédula profesional.

- Horarios de atención.

- Duración predeterminada de citas.

- Autocompletado por código postal.

- Middleware de onboarding.

- Cobertura automatizada.

Commit principal:

`DT-6 feat: implement onboarding wizard with postal code autocomplete`

---

## DT-7 — Gestión de pacientes

Estado: Completado

Objetivo:

Construir el flujo principal de pacientes y expediente.

Incluye:

- Listado de pacientes.

- Búsqueda.

- Alta de pacientes.

- Edición.

- Detalle del paciente.

- Contactos de emergencia.

- Antecedentes médicos.

- Expediente clínico.

- Integración con consultas.

- Protección multi-tenant.

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

- `AppointmentAvailabilityService`.

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

Una cita solo puede marcarse como no-show cuando:

`now() >= ends_at + 15 minutos`

El cambio a no-show nunca es automático. La acción debe ser confirmada por el usuario.

Commits principales:

- `DT-8 feat: complete appointment scheduling and dashboard`

- `DT-8 feat: finalize appointment workflow and lifecycle`

Baseline al cerrar DT-8:

`366 tests verdes`

---

## DT-9 — Consultation workflow and clinical lifecycle

Estado: Completado

Objetivo:

Convertir Consultation en una entidad clínica persistente desde el inicio de la atención.

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

- Estados de Consultation.

- `draft`.

- `completed`.

- Crear Consultation al iniciar una cita.

- Una sola Consultation por Appointment.

- Continuar una Consultation existente.

- Consulta directa sin Appointment.

- Editar consulta mientras está en draft.

- Finalización explícita de Consultation.

- Completar Appointment al finalizar Consultation.

- Signos vitales.

- Motivo de consulta.

- Nota SOAP.

- Diagnósticos durante la consulta.

- Diagnóstico principal.

- Recetas asociadas.

- Historial clínico.

- Protección multi-tenant.

- Tests del modelo.

- Tests del flujo.

- Tests del lifecycle.

- Tests Appointment → Consultation.

Fuera de alcance en DT-9:

- Autosave.

- Workspace clínico avanzado.

- Alertas clínicas.

- WhatsApp.

- SMS.

- Recordatorios externos.

- Firma digital.

- Archivos clínicos.

- Laboratorios.

- Imágenes médicas.

- Plantillas por especialidad.

Baseline al cerrar DT-9:

`393 tests verdes`

---

## DT-10 — Product inventory and development roadmap

Estado: Completado

Objetivo:

Construir un inventario funcional real del producto y establecer los documentos maestros de seguimiento.

Incluye:

- Auditoría de módulos existentes.

- Inventario funcional.

- Inventario clínico.

- Inventario SaaS.

- Inventario visual.

- Identificación de deuda técnica.

- Identificación de decisiones pendientes.

- Creación de `TODO.md`.

- Creación de `ROADMAP.md`.

- Separación conceptual entre:

  - trabajo completado;

  - trabajo parcial;

  - trabajo pendiente;

  - decisiones de producto.

- Definición de próximos bloques DT.

Baseline al cerrar DT-10:

`393 tests verdes`

`1179 assertions`

`0 failures`

Commit principal:

`1e468fb DT-10 docs: establish DocTotal product TODO and development roadmap DT-10`

---

## DT-11 — Subscription lifecycle foundation

Estado: Completado

Objetivo:

Construir la base del ciclo de vida de las suscripciones de DocTotal.

Incluye:

- Modelo `Subscription`.

- Ciclos mensual y anual.

- Estado de la suscripción.

- Periodo actual de servicio.

- Billing anchor.

- Conversión trial → suscripción.

- Cancelación.

- Cancelación programada.

- Reanudación.

- Reactivación.

- Derecho de acceso basado en el estado de suscripción.

- Integración con Tenant.

- Protección multi-tenant.

- Cobertura automatizada.

Decisiones:

DocTotal debe ser capaz de administrar automáticamente el derecho de acceso al producto según el estado comercial del tenant.

Baseline al cerrar DT-11:

`445 tests verdes`

`0 failures`

Commit principal:

`6b35252 DT-11 feat: implement subscription lifecycle foundation`

---

## DT-12 — Payments, billing recovery and automatic account lifecycle

Estado: Completado

Objetivo:

Implementar pagos, recuperación de cobros y automatización del ciclo comercial de la cuenta.

Incluye:

- Integración con Stripe.

- `BillingCustomer`.

- `Payment`.

- `PaymentMethod`.

- SetupIntent.

- PaymentIntent.

- Métodos de pago guardados.

- Renovaciones.

- Renovaciones mensuales.

- Renovaciones anuales.

- Reintentos de pago.

- Recuperación de pagos fallidos.

- Grace period.

- Estados `past_due`.

- Suspensión automática.

- Reactivación después de pago recuperado.

- Cancelación programada.

- Reanudación de suscripción.

- Idempotencia.

- Integración de pagos con Subscription.

- Integración de pagos con Tenant.

- Cobertura automatizada extensa.

Fuera de alcance de DT-12:

- Facturación fiscal.

- Webhooks Stripe completos.

- Recibos/comprobantes finales.

- Herramientas administrativas avanzadas.

Baseline al cerrar DT-12:

`696 tests verdes`

`0 failures`

Commit principal:

`9fe1106 DT-12 feat: finalize billing lifecycle hardening and documentation`

---

## DT-13 — Referral program and promotional credits

Estado: Completado

Objetivo:

Implementar un programa de referidos y créditos promocionales integrado con el ciclo de facturación.

Incluye:

- Modelo `Referral`.

- Código de referido.

- Enlace de referido.

- Captura opcional de código de referido.

- Prevención de auto-referidos.

- Prevención de referidos duplicados.

- Calificación por primer pago exitoso.

- Descuento para el referido.

- Crédito para el referidor.

- Límites mensuales.

- Modelo `PromotionalCredit`.

- Reserva de créditos.

- Consumo de créditos.

- Liberación de créditos.

- Idempotencia.

- Integración con pagos.

- Integración con renovaciones.

- Integración con recuperación de pagos.

- Protección multi-tenant.

- Cobertura automatizada.

Decisiones:

El crédito promocional se administra como una entidad explícita y auditable.

Los créditos no deben consumirse dos veces.

Las operaciones deben mantenerse idempotentes.

Baseline al cerrar DT-13:

`797 tests verdes`

`2244 assertions`

`0 failures`

Commit principal:

`f4d7322 DT-13 feat: complete referral program and promotional credits`

---

## DT-14 — Expediente clínico longitudinal

Estado: Completado

Objetivo:

Transformar el expediente del paciente en una vista clínica longitudinal útil.

Incluye:

- Resumen clínico.

- Línea de tiempo clínica.

- Consultas finalizadas.

- Diagnósticos históricos.

- Diagnóstico principal.

- Tratamientos históricos.

- Recetas históricas.

- Navegación hacia consultas originales.

- Navegación hacia recetas originales.

- Consolidación de información clínica relevante.

- Protección multi-tenant.

- Tests del expediente longitudinal.

Decisiones:

Los diagnósticos históricos se obtienen desde consultas completadas.

Los tratamientos históricos se obtienen desde recetas asociadas al paciente.

El expediente longitudinal no debe reinterpretar automáticamente los datos clínicos.

Baseline al cerrar DT-14:

`814 tests verdes`

`2339 assertions`

`0 failures`

Commit principal:

`3df050f DT-14 feat: implement longitudinal clinical record`

---

## DT-15 — Clinical files and medical documents

Estado: Completado

Objetivo:

Implementar infraestructura segura para archivos y documentos clínicos.

Incluye:

- Modelo `ClinicalDocument`.

- UUID para routing.

- Storage privado.

- Upload de documentos.

- PDF.

- JPG.

- JPEG.

- PNG.

- WebP.

- Categorías documentales.

- `general`.

- `laboratory`.

- `imaging`.

- `other`.

- Asociación con Patient.

- Asociación opcional con Consultation.

- Metadatos clínicos.

- Fecha del estudio/documento.

- Descripción.

- Visualización inline segura.

- Descarga segura.

- Miniaturas protegidas para imágenes.

- Eliminación controlada.

- Límite actual de 10 MB por archivo.

- Disco configurable con `CLINICAL_DOCUMENTS_DISK`.

- Hardening de `StoreClinicalDocument`.

- Protección multi-tenant.

- Cobertura automatizada de almacenamiento, visualización, descarga y eliminación.

Fuera de alcance:

- OCR.

- DICOM/PACS.

- Resultados de laboratorio estructurados.

- Cuotas totales de almacenamiento por tenant.

- Storage externo definitivo.

- Políticas completas de retención y respaldo.

Baseline al cerrar DT-15:

`837 tests verdes`

`2395 assertions`

`0 failures`

Commit principal:

`07f73ec DT-15 feat: implement clinical files and medical documents`

---

## DT-16 — Visual redesign / DocTotal UI

Estado: Completado

Objetivo:

Definir e implementar una identidad visual consistente para DocTotal y rediseñar sus principales áreas funcionales.

Dirección visual:

- Producto médico moderno.

- Estética tecnológica.

- Sidebar azul marino / índigo oscuro.

- Azul eléctrico como color principal.

- Acentos violeta, cyan y verde.

- Fondos claros azul/blanco.

- Tarjetas blancas.

- Bordes suaves.

- Radios generosos.

- Sombras discretas.

- Iconografía lineal.

- Badges elegantes.

- Focus luminoso azul.

- Responsive.

- Componentes reutilizables.

Incluye:

- Foundation visual.

- Shell global.

- Sidebar.

- Header.

- Navegación responsive.

- Dashboard.

- Pacientes.

- Expediente clínico.

- Agenda.

- Consultas.

- Recetas.

- Onboarding.

- Configuración.

- Billing.

- Autenticación.

- Estados vacíos.

- Estados de carga.

- Feedback visual.

- Consistencia general.

Decisiones:

La foundation visual global queda aprobada.

No deben hacerse cambios globales casuales al shell, sidebar o header sin una razón de producto.

Baseline al cerrar DT-16:

`837 tests verdes`

`0 failures`

Commit principal:

`4e6d77d DT-16 feat: complete DocTotal visual redesign`

---

## DT-17 — Clinical workspace / Consulta médica avanzada

Estado: Completado

Objetivo:

Transformar la captura de consulta médica en un workspace clínico avanzado con contexto persistente y protección de datos durante la atención.

Incluye:

- Workspace clínico responsive.

- Layout amplio para uso clínico.

- Panel lateral persistente.

- Alergias.

- Medicamentos actuales.

- Enfermedades crónicas.

- Cirugías.

- Antecedentes relevantes.

- Consultas recientes completadas.

- Diagnósticos recientes.

- Signos vitales.

- Motivo de consulta.

- SOAP.

- Diagnósticos.

- Autosave.

- Estado de guardado visible.

- Estado `guardando`.

- Estado `guardado`.

- Estado de error.

- Protección contra pérdida de cambios.

- `beforeunload`.

- Validaciones en español.

- Nombres amigables de atributos.

- Resaltado visual de campos inválidos.

- Scroll automático al primer error.

- Focus automático al primer campo inválido.

- Protección al finalizar cuando existen cambios pendientes.

- Protección al finalizar cuando hay guardado en curso.

- Protección al finalizar cuando existe error de guardado.

- Revalidación backend antes de completar.

- Finalización Consultation → Appointment únicamente después de validación correcta.

- Continuidad de drafts.

- Consulta directa sin cita.

- Consulta desde Appointment.

- Diagnóstico principal.

- Integración con recetas.

- Cobertura automatizada.

Decisiones clínicas y de producto:

`PatientMedicalHistory` continúa siendo la fuente explícita para:

- alergias;

- medicamentos actuales;

- antecedentes;

- enfermedades crónicas;

- cirugías.

Las recetas históricas no se interpretan automáticamente como medicamentos actuales.

Los límites de validación de signos vitales son límites técnicos de captura y no constituyen rangos clínicos normales, alertas médicas ni decisión clínica.

El workspace mantiene el historial reciente visible sin abandonar la consulta.

Baseline al cerrar DT-17:

`840 tests verdes`

`0 failures`

Tests específicos de Consultations:

`76 tests verdes`

ConsultationFlowTest:

`18 tests verdes`

Commit principal:

`ff7aee4 DT-17 feat: implement advanced clinical consultation workspace`

---

## DT-18 — Documentation baseline normalization

Estado: Completado

Objetivo:

Normalizar la documentación maestra del proyecto después del cierre funcional de DT-17.

Incluye:

- Revisión de `TODO.md`.

- Revisión de `ROADMAP.md`.

- Sincronización contra el estado real del producto.

- Corrección de avances acumulados.

- Revisión de los bloques funcionales ya completados.

- Revisión de pendientes reales.

- Revisión del baseline de tests.

- Recalculo ponderado del avance global.

Decisiones:

DT-18 se utilizó como bloque documental.

No se forzó funcionalidad adicional únicamente para justificar el ticket.

La actualización de documentación se mantiene como parte ligera del inicio/cierre de cada DT.

Baseline heredado:

`840 tests verdes`

`0 failures`

Avance global ponderado al cerrar DT-18:

`72%`

Integración:

DT-18 fue integrado en `master`.

---

## DT-19 — Structured active clinical problem list

Estado: Completado

Objetivo:

Implementar una lista estructurada y longitudinal de problemas clínicos por paciente como evolución natural del expediente y del workspace clínico.

Problema que resuelve:

Antes de DT-19, DocTotal tenía:

- antecedentes médicos;

- consultas;

- diagnósticos;

- recetas;

- documentos clínicos;

- historial longitudinal.

Sin embargo, no existía una entidad explícita para distinguir longitudinalmente problemas clínicos:

- activos;

- resueltos.

DT-19 introduce esa estructura sin inferir automáticamente decisiones médicas desde el historial.

Incluye:

- Modelo `PatientProblem`.

- Persistencia multi-tenant.

- Trait `BelongsToTenant`.

- Soft deletes.

- Relación con Patient.

- Estados:

  - `active`;

  - `resolved`.

- Código opcional.

- Descripción.

- Fecha de inicio.

- Fecha de resolución.

- Notas.

- Índices de base de datos.

- Relación `Patient → problems`.

- CRUD dentro del expediente.

- Crear problema.

- Editar problema.

- Resolver problema.

- Reabrir problema.

- Eliminar con soft delete.

- Orden de problemas activos antes de resueltos.

- Historial de problemas resueltos.

- Protección por paciente.

- Protección multi-tenant.

Autocomplete:

- Reutilización de `DiagnosisCatalog`.

- Búsqueda por código.

- Búsqueda por descripción.

- Resultados ordenados por relevancia.

- Selección desde catálogo.

- Autollenado de código.

- Autollenado de descripción.

- Captura manual preservada.

- No dependencia obligatoria del catálogo.

Integración con expediente:

- Sección visual `Problemas clínicos`.

- Problemas activos.

- Problemas resueltos.

- Código.

- Descripción.

- Fecha de inicio.

- Fecha de resolución.

- Notas en problemas activos.

- Acciones de edición.

- Acción para marcar como resuelto.

- Acción para reabrir.

- Acción para eliminar.

Integración con consulta:

- Eager load de problemas activos.

- Problemas clínicos activos visibles en el panel de contexto.

- Código visible.

- Descripción visible.

- Fecha de inicio visible.

- Notas visibles.

- Problemas resueltos excluidos del contexto activo.

- La consulta no abandona el workspace para consultar esta información.

Decisiones de arquitectura:

`PatientProblem` es una entidad longitudinal explícita.

No se infiere automáticamente desde `ConsultationDiagnosis`.

`DiagnosisCatalog` se utiliza únicamente como ayuda de captura.

`PatientMedicalHistory` continúa siendo fuente explícita de:

- alergias;

- medicamentos actuales;

- antecedentes;

- enfermedades crónicas;

- cirugías.

No se agregó UUID a `PatientProblem` porque actualmente no tiene routing público independiente.

Podrá evaluarse posteriormente si el diseño de rutas lo requiere.

Fuera de alcance de DT-19:

- Alertas clínicas automáticas.

- Reglas médicas automáticas.

- Inferencia automática desde diagnósticos históricos.

- Interacciones farmacológicas.

- OCR.

- Resultados estructurados de laboratorio.

- DICOM/PACS.

- WhatsApp.

- SMS.

- Email transaccional.

Cobertura automatizada:

`PatientProblemTest`:

- pertenencia a Patient;

- pertenencia al tenant actual;

- relación Patient → problems;

- resolución;

- reapertura;

- aislamiento entre tenants;

- ausencia de tenant context.

`PatientProblemFlowTest`:

- crear;

- validar descripción obligatoria;

- editar;

- resolver;

- reabrir;

- soft delete;

- impedir manipulación desde otro paciente.

Expediente:

- visualización de problemas activos;

- visualización de problemas resueltos.

Consulta:

- visualización de problemas activos;

- exclusión de problemas resueltos del contexto activo.

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

- Documentación actualizada.

- Commit final realizado.

- Merge a `master`.

- Comentario técnico registrado en Jira.

- DT-19 transicionado a `Listo`.

Commit principal:

`d6f2678 DT-19 feat: implement structured active clinical problem list`

---

## DT-20 — Transactional communications and appointment reminders foundation

Estado: Cierre técnico completado

Pendiente:

- Commit final.

- Merge a `master`.

- Cierre Jira.

Objetivo:

Construir una foundation multi-tenant, persistente, auditable y extensible
para comunicaciones transaccionales, implementando como primer caso real
los recordatorios de citas.

Incluye:

- Modelo `Communication`.

- Relaciones con Patient y Appointment.

- Estados `pending`, `sent`, `failed` y `cancelled`.

- Snapshot de destinatario, contenido y metadata.

- Idempotencia por tenant.

- Contrato `CommunicationTransport`.

- `CommunicationTransportManager`.

- Preparación para email, WhatsApp y SMS.

- Ausencia segura de proveedor configurado.

- `CommunicationProcessor`.

- Máximo de 3 intentos.

- Backoff de 5 y 15 minutos.

- `communications:process-due`.

- `AppointmentReminderService`.

- Generación idempotente por cita, canal y horario.

- `communications:generate-appointment-reminders`.

- Scheduler.

- `AppointmentReminderValidator`.

- Cancelación auditable de recordatorios obsoletos.

- Protección ante reprogramación y cambios de estado.

- Historial visual de comunicaciones dentro de la cita.

- Protección multi-tenant.

Decisiones:

DocTotal no utiliza un transport nulo que simule éxito.

Si no existe transport configurado, la comunicación permanece pendiente y
no consume intento.

Una reprogramación crea una nueva identidad de recordatorio y el anterior
se conserva como cancelado para auditoría.

La capa de comunicaciones permanece independiente de proveedores concretos.

Fuera de alcance:

- Campañas de marketing.

- Envíos masivos.

- Proveedores reales obligatorios.

- Confirmación externa por paciente.

- Alertas clínicas.

- Inferencia médica automática.

Validación:

`56 tests verdes` en la regresión específica de DT-20.

`148 tests verdes` en appointments después de la integración visual.

Suite completa al cierre técnico:

`910 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre técnico:

`77%`

Cierre pendiente:

- Actualización documental final.

- Commit final DT-20.

- Merge a `master`.

- Comentario técnico de cierre en Jira.

- Transición de DT-20 a `Listo`.

---

## Próximos candidatos

Los siguientes bloques no están todavía comprometidos como DT definitivo.

Deben evaluarse al iniciar el siguiente bloque de desarrollo.

### Candidato A — Alertas clínicas inteligentes

Objetivo:

Construir alertas clínicas contextuales sobre la base estructurada de `PatientProblem`.

Base disponible:

Patient

→ PatientMedicalHistory

→ PatientProblem

→ Consultation

→ ConsultationDiagnosis

→ Prescription

→ ClinicalDocument

Pendiente:

- Definir tipos de alertas.

- Definir reglas explícitas.

- Definir prioridad.

- Definir presentación visual.

- Definir si algunas alertas requieren confirmación.

- Evitar inferencia médica automática no validada.

- Evitar convertir validaciones técnicas en decisión clínica.

Este bloque requiere definición clínica y de producto antes de implementación.

### Candidato B — Comunicaciones y recordatorios

Objetivo:

Implementar comunicación transaccional y recordatorios operativos.

Incluye potencialmente:

- Email.

- WhatsApp.

- SMS.

- Confirmación de citas.

- Recordatorios de citas.

- Cancelaciones.

- Reprogramaciones.

- Avisos de trial.

- Avisos de pago.

- Recuperación de pagos.

- Suspensión.

- Reactivación.

Decisiones pendientes:

- Proveedores.

- Consentimiento.

- Trazabilidad.

- Reintentos.

- Costos por canal.

### Candidato C — Seguridad, privacidad y auditoría

Objetivo:

Preparar DocTotal para una operación SaaS clínica más robusta.

Incluye potencialmente:

- Auditoría de acciones sensibles.

- Historial formal de cambios clínicos.

- Logs operativos.

- Backups.

- Restauración.

- Políticas de retención.

- Seguridad de sesiones.

- Revocación de dispositivos.

- 2FA.

- Passkeys.

- Verificación de correo.

- Hardening previo a producción.

### Candidato D — Operación interna SaaS

Objetivo:

Construir herramientas internas para operar DocTotal como servicio.

Incluye potencialmente:

- Panel administrativo.

- Gestión de tenants.

- Métricas.

- Soporte.

- Auditoría comercial.

- Observabilidad.

- Gestión de incidencias.

- Herramientas de billing.

### Candidato E — Trial y lifecycle avanzado

Objetivo:

Completar la experiencia comercial alrededor del periodo de prueba y cierre de cuenta.

Incluye potencialmente:

- Avisos de trial.

- Pantalla de expiración.

- Comunicación previa al vencimiento.

- Eliminación programada.

- Recuperación antes de eliminación.

- Retención después de cancelación.

- Auditoría de transiciones.

### Candidato F — Storage y documentos avanzados

Objetivo:

Evolucionar la infraestructura documental clínica.

Incluye potencialmente:

- Cuotas por tenant.

- Indicadores de almacenamiento.

- Storage externo.

- Backups.

- Retención.

- OCR.

- Laboratorios estructurados.

- DICOM/PACS.

---

## Estado global actual

DT completados:

- DT-1.

- DT-2.

- DT-3.

- DT-4.

- DT-5.

- DT-6.

- DT-7.

- DT-8.

- DT-9.

- DT-10.

- DT-11.

- DT-12.

- DT-13.

- DT-14.

- DT-15.

- DT-16.

- DT-17.

- DT-18.

- DT-19.

DT con cierre técnico completado:

- DT-20.

Baseline funcional actual:

`910 tests verdes`

`0 failures`

Avance global ponderado:

`77%`

DocTotal cuenta actualmente con una base clínica, operativa, SaaS y visual considerablemente más madura que al inicio del roadmap.

La selección del siguiente DT debe seguir priorizando:

- valor clínico;

- valor operativo;

- riesgo SaaS;

- seguridad;

- multi-tenancy;

- cobertura automatizada;

- dependencias técnicas;

- experiencia del usuario;

- preparación para producción.
