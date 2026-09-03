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

-   Preparaci├│n para aislamiento por tenant.

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

-   Resoluci├│n del tenant actual.

-   Protecci├│n contra acceso cruzado entre tenants.

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

Construir la base del expediente cl├¡nico del paciente.

Incluye:

-   Pacientes.

-   Contactos de emergencia.

-   Antecedentes m├®dicos.

-   Base de consultas.

-   Fundamentos del expediente cl├¡nico.

Commit principal:

`DT-4 Implement patient clinical record foundation`

------------------------------------------------------------------------

## DT-5 --- Authentication, registration, dashboard and trial

Estado: Completado

Objetivo:

Implementar autenticaci├│n, registro y flujo inicial del usuario.

Incluye:

-   Registro.

-   Inicio de sesi├│n.

-   Cierre de sesi├│n.

-   Dashboard inicial.

-   Trial.

-   Asociaci├│n del usuario con su tenant.

-   Infraestructura Laravel Fortify.

Commit principal:

`DT-5 Implement authentication registration dashboard and trial`

------------------------------------------------------------------------

## DT-6 --- Onboarding wizard and postal code autocomplete

Estado: Completado

Objetivo:

Construir el onboarding inicial del m├®dico y consultorio.

Incluye:

-   Wizard de onboarding.

-   Perfil del consultorio.

-   Perfil m├®dico.

-   Especialidad.

-   C├®dula profesional.

-   Horarios de atenci├│n.

-   Duraci├│n predeterminada de citas.

-   Autocompletado por c├│digo postal.

-   Middleware de onboarding.

-   Cobertura automatizada.

Commit principal:

`DT-6 feat: implement onboarding wizard with postal code autocomplete`

------------------------------------------------------------------------

## DT-7 --- Gesti├│n de pacientes

Estado: Completado

Objetivo:

Construir el flujo principal de pacientes y expediente.

Incluye:

-   Listado de pacientes.

-   B├║squeda.

-   Alta de pacientes.

-   Edici├│n.

-   Detalle del paciente.

-   Contactos de emergencia.

-   Antecedentes m├®dicos.

-   Expediente cl├¡nico.

-   Integraci├│n con consultas.

-   Protecci├│n multi-tenant.

Commit principal:

`DT-7 Implement gesti├│n de pacientes`

------------------------------------------------------------------------

## DT-8 --- Agenda, citas y ciclo operativo

Estado: Completado

Objetivo:

Construir el sistema completo de agenda y citas.

Incluye:

-   Creaci├│n de citas.

-   Creaci├│n r├ípida de pacientes desde una cita.

-   Disponibilidad basada en horarios del onboarding.

-   `AppointmentAvailabilityService`.

-   Excepciones de horario.

-   Bloqueos parciales y completos.

-   Horarios extraordinarios.

-   Prevenci├│n de solapamientos.

-   Eliminaci├│n de slots pasados.

-   Agenda por mes, semana y d├¡a.

-   Dashboard din├ímico.

-   Estados de Appointment.

-   Confirmaci├│n.

-   Check-in.

-   Inicio de consulta.

-   Finalizaci├│n autom├ítica de cita.

-   Cancelaci├│n.

-   No-show semiautom├ítico.

-   Periodo de gracia de 15 minutos para no-show.

-   Reprogramaci├│n.

-   Edici├│n de motivo y notas.

-   Integraci├│n Appointment ÔåÆ Consultation.

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

El cambio a no-show nunca es autom├ítico. La acci├│n debe ser confirmada
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

Convertir Consultation en una entidad cl├¡nica persistente desde el
inicio de la atenci├│n.

Flujo implementado:

Appointment

ÔåÆ Iniciar consulta

ÔåÆ Appointment = `in_progress`

ÔåÆ Consultation = `draft`

ÔåÆ Captura cl├¡nica

ÔåÆ Guardar avances

ÔåÆ Continuar posteriormente

ÔåÆ Finalizar consulta

ÔåÆ Consultation = `completed`

ÔåÆ Appointment = `completed`

Incluye:

-   Estados de Consultation.

-   `draft`.

-   `completed`.

-   Crear Consultation al iniciar una cita.

-   Una sola Consultation por Appointment.

-   Continuar una Consultation existente.

-   Consulta directa sin Appointment.

-   Editar consulta mientras est├í en draft.

-   Finalizaci├│n expl├¡cita de Consultation.

-   Completar Appointment al finalizar Consultation.

-   Signos vitales.

-   Motivo de consulta.

-   Nota SOAP.

-   Diagn├│sticos durante la consulta.

-   Diagn├│stico principal.

-   Recetas asociadas.

-   Historial cl├¡nico.

-   Protecci├│n multi-tenant.

-   Tests del modelo.

-   Tests del flujo.

-   Tests del lifecycle.

-   Tests Appointment ÔåÆ Consultation.

Fuera de alcance en DT-9:

-   Autosave.

-   Workspace cl├¡nico avanzado.

-   Alertas cl├¡nicas.

-   WhatsApp.

-   SMS.

-   Recordatorios externos.

-   Firma digital.

-   Archivos cl├¡nicos.

-   Laboratorios.

-   Im├ígenes m├®dicas.

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

-   Auditor├¡a de m├│dulos existentes.

-   Inventario funcional.

-   Inventario cl├¡nico.

-   Inventario SaaS.

-   Inventario visual.

-   Identificaci├│n de deuda t├®cnica.

-   Identificaci├│n de decisiones pendientes.

-   Creaci├│n de `TODO.md`.

-   Creaci├│n de `ROADMAP.md`.

-   Separaci├│n conceptual entre:

    -   trabajo completado;

    -   trabajo parcial;

    -   trabajo pendiente;

    -   decisiones de producto.

-   Definici├│n de pr├│ximos bloques DT.

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

-   Estado de la suscripci├│n.

-   Periodo actual de servicio.

-   Billing anchor.

-   Conversi├│n trial ÔåÆ suscripci├│n.

-   Cancelaci├│n.

-   Cancelaci├│n programada.

-   Reanudaci├│n.

-   Reactivaci├│n.

-   Derecho de acceso basado en el estado de suscripci├│n.

-   Integraci├│n con Tenant.

-   Protecci├│n multi-tenant.

-   Cobertura automatizada.

Decisiones:

DocTotal debe ser capaz de administrar autom├íticamente el derecho de
acceso al producto seg├║n el estado comercial del tenant.

Baseline al cerrar DT-11:

`445 tests verdes`

`0 failures`

Commit principal:

`6b35252 DT-11 feat: implement subscription lifecycle foundation`

------------------------------------------------------------------------

## DT-12 --- Payments, billing recovery and automatic account lifecycle

Estado: Completado

Objetivo:

Implementar pagos, recuperaci├│n de cobros y automatizaci├│n del ciclo
comercial de la cuenta.

Incluye:

-   Integraci├│n con Stripe.

-   `BillingCustomer`.

-   `Payment`.

-   `PaymentMethod`.

-   SetupIntent.

-   PaymentIntent.

-   M├®todos de pago guardados.

-   Renovaciones.

-   Renovaciones mensuales.

-   Renovaciones anuales.

-   Reintentos de pago.

-   Recuperaci├│n de pagos fallidos.

-   Grace period.

-   Estados `past_due`.

-   Suspensi├│n autom├ítica.

-   Reactivaci├│n despu├®s de pago recuperado.

-   Cancelaci├│n programada.

-   Reanudaci├│n de suscripci├│n.

-   Idempotencia.

-   Integraci├│n de pagos con Subscription.

-   Integraci├│n de pagos con Tenant.

-   Cobertura automatizada extensa.

Fuera de alcance de DT-12:

-   Facturaci├│n fiscal.

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

Implementar un programa de referidos y cr├®ditos promocionales integrado
con el ciclo de facturaci├│n.

Incluye:

-   Modelo `Referral`.

-   C├│digo de referido.

-   Enlace de referido.

-   Captura opcional de c├│digo de referido.

-   Prevenci├│n de auto-referidos.

-   Prevenci├│n de referidos duplicados.

-   Calificaci├│n por primer pago exitoso.

-   Descuento para el referido.

-   Cr├®dito para el referidor.

-   L├¡mites mensuales.

-   Modelo `PromotionalCredit`.

-   Reserva de cr├®ditos.

-   Consumo de cr├®ditos.

-   Liberaci├│n de cr├®ditos.

-   Idempotencia.

-   Integraci├│n con pagos.

-   Integraci├│n con renovaciones.

-   Integraci├│n con recuperaci├│n de pagos.

-   Protecci├│n multi-tenant.

-   Cobertura automatizada.

Decisiones:

El cr├®dito promocional se administra como una entidad expl├¡cita y
auditable.

Los cr├®ditos no deben consumirse dos veces.

Las operaciones deben mantenerse idempotentes.

Baseline al cerrar DT-13:

`797 tests verdes`

`2244 assertions`

`0 failures`

Commit principal:

`f4d7322 DT-13 feat: complete referral program and promotional credits`

------------------------------------------------------------------------

## DT-14 --- Expediente cl├¡nico longitudinal

Estado: Completado

Objetivo:

Transformar el expediente del paciente en una vista cl├¡nica
longitudinal ├║til.

Incluye:

-   Resumen cl├¡nico.

-   L├¡nea de tiempo cl├¡nica.

-   Consultas finalizadas.

-   Diagn├│sticos hist├│ricos.

-   Diagn├│stico principal.

-   Tratamientos hist├│ricos.

-   Recetas hist├│ricas.

-   Navegaci├│n hacia consultas originales.

-   Navegaci├│n hacia recetas originales.

-   Consolidaci├│n de informaci├│n cl├¡nica relevante.

-   Protecci├│n multi-tenant.

-   Tests del expediente longitudinal.

Decisiones:

Los diagn├│sticos hist├│ricos se obtienen desde consultas completadas.

Los tratamientos hist├│ricos se obtienen desde recetas asociadas al
paciente.

El expediente longitudinal no debe reinterpretar autom├íticamente los
datos cl├¡nicos.

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

Implementar infraestructura segura para archivos y documentos cl├¡nicos.

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

-   Categor├¡as documentales.

-   `general`.

-   `laboratory`.

-   `imaging`.

-   `other`.

-   Asociaci├│n con Patient.

-   Asociaci├│n opcional con Consultation.

-   Metadatos cl├¡nicos.

-   Fecha del estudio/documento.

-   Descripci├│n.

-   Visualizaci├│n inline segura.

-   Descarga segura.

-   Miniaturas protegidas para im├ígenes.

-   Eliminaci├│n controlada.

-   L├¡mite actual de 10 MB por archivo.

-   Disco configurable con `CLINICAL_DOCUMENTS_DISK`.

-   Hardening de `StoreClinicalDocument`.

-   Protecci├│n multi-tenant.

-   Cobertura automatizada de almacenamiento, visualizaci├│n, descarga y
    eliminaci├│n.

Fuera de alcance:

-   OCR.

-   DICOM/PACS.

-   Resultados de laboratorio estructurados.

-   Cuotas totales de almacenamiento por tenant.

-   Storage externo definitivo.

-   Pol├¡ticas completas de retenci├│n y respaldo.

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
redise├▒ar sus principales ├íreas funcionales.

Direcci├│n visual:

-   Producto m├®dico moderno.

-   Est├®tica tecnol├│gica.

-   Sidebar azul marino / ├¡ndigo oscuro.

-   Azul el├®ctrico como color principal.

-   Acentos violeta, cyan y verde.

-   Fondos claros azul/blanco.

-   Tarjetas blancas.

-   Bordes suaves.

-   Radios generosos.

-   Sombras discretas.

-   Iconograf├¡a lineal.

-   Badges elegantes.

-   Focus luminoso azul.

-   Responsive.

-   Componentes reutilizables.

Incluye:

-   Foundation visual.

-   Shell global.

-   Sidebar.

-   Header.

-   Navegaci├│n responsive.

-   Dashboard.

-   Pacientes.

-   Expediente cl├¡nico.

-   Agenda.

-   Consultas.

-   Recetas.

-   Onboarding.

-   Configuraci├│n.

-   Billing.

-   Autenticaci├│n.

-   Estados vac├¡os.

-   Estados de carga.

-   Feedback visual.

-   Consistencia general.

Decisiones:

La foundation visual global queda aprobada.

No deben hacerse cambios globales casuales al shell, sidebar o header
sin una raz├│n de producto.

Baseline al cerrar DT-16:

`837 tests verdes`

`0 failures`

Commit principal:

`4e6d77d DT-16 feat: complete DocTotal visual redesign`

------------------------------------------------------------------------

## DT-17 --- Clinical workspace / Consulta m├®dica avanzada

Estado: Completado

Objetivo:

Transformar la captura de consulta m├®dica en un workspace cl├¡nico
avanzado con contexto persistente y protecci├│n de datos durante la
atenci├│n.

Incluye:

-   Workspace cl├¡nico responsive.

-   Layout amplio para uso cl├¡nico.

-   Panel lateral persistente.

-   Alergias.

-   Medicamentos actuales.

-   Enfermedades cr├│nicas.

-   Cirug├¡as.

-   Antecedentes relevantes.

-   Consultas recientes completadas.

-   Diagn├│sticos recientes.

-   Signos vitales.

-   Motivo de consulta.

-   SOAP.

-   Diagn├│sticos.

-   Autosave.

-   Estado de guardado visible.

-   Estado `guardando`.

-   Estado `guardado`.

-   Estado de error.

-   Protecci├│n contra p├®rdida de cambios.

-   `beforeunload`.

-   Validaciones en espa├▒ol.

-   Nombres amigables de atributos.

-   Resaltado visual de campos inv├ílidos.

-   Scroll autom├ítico al primer error.

-   Focus autom├ítico al primer campo inv├ílido.

-   Protecci├│n al finalizar cuando existen cambios pendientes.

-   Protecci├│n al finalizar cuando hay guardado en curso.

-   Protecci├│n al finalizar cuando existe error de guardado.

-   Revalidaci├│n backend antes de completar.

-   Finalizaci├│n Consultation ÔåÆ Appointment ├║nicamente despu├®s de
    validaci├│n correcta.

-   Continuidad de drafts.

-   Consulta directa sin cita.

-   Consulta desde Appointment.

-   Diagn├│stico principal.

-   Integraci├│n con recetas.

-   Cobertura automatizada.

Decisiones cl├¡nicas y de producto:

`PatientMedicalHistory` contin├║a siendo la fuente expl├¡cita para:

-   alergias;

-   medicamentos actuales;

-   antecedentes;

-   enfermedades cr├│nicas;

-   cirug├¡as.

Las recetas hist├│ricas no se interpretan autom├íticamente como
medicamentos actuales.

Los l├¡mites de validaci├│n de signos vitales son l├¡mites t├®cnicos de
captura y no constituyen rangos cl├¡nicos normales, alertas m├®dicas ni
decisi├│n cl├¡nica.

El workspace mantiene el historial reciente visible sin abandonar la
consulta.

Baseline al cerrar DT-17:

`840 tests verdes`

`0 failures`

Tests espec├¡ficos de Consultations:

`76 tests verdes`

ConsultationFlowTest:

`18 tests verdes`

Commit principal:

`ff7aee4 DT-17 feat: implement advanced clinical consultation workspace`

------------------------------------------------------------------------

## DT-18 --- Documentation baseline normalization

Estado: Completado

Objetivo:

Normalizar la documentaci├│n maestra del proyecto despu├®s del cierre
funcional de DT-17.

Incluye:

-   Revisi├│n de `TODO.md`.

-   Revisi├│n de `ROADMAP.md`.

-   Sincronizaci├│n contra el estado real del producto.

-   Correcci├│n de avances acumulados.

-   Revisi├│n de los bloques funcionales ya completados.

-   Revisi├│n de pendientes reales.

-   Revisi├│n del baseline de tests.

-   Recalculo ponderado del avance global.

Decisiones:

DT-18 se utiliz├│ como bloque documental.

No se forz├│ funcionalidad adicional ├║nicamente para justificar el
ticket.

La actualizaci├│n de documentaci├│n se mantiene como parte ligera del
inicio/cierre de cada DT.

Baseline heredado:

`840 tests verdes`

`0 failures`

Avance global ponderado al cerrar DT-18:

`72%`

Integraci├│n:

DT-18 fue integrado en `master`.

------------------------------------------------------------------------

## DT-19 --- Structured active clinical problem list

Estado: Completado

Objetivo:

Implementar una lista estructurada y longitudinal de problemas cl├¡nicos
por paciente como evoluci├│n natural del expediente y del workspace
cl├¡nico.

Problema que resuelve:

Antes de DT-19, DocTotal ten├¡a:

-   antecedentes m├®dicos;

-   consultas;

-   diagn├│sticos;

-   recetas;

-   documentos cl├¡nicos;

-   historial longitudinal.

Sin embargo, no exist├¡a una entidad expl├¡cita para distinguir
longitudinalmente problemas cl├¡nicos:

-   activos;

-   resueltos.

DT-19 introduce esa estructura sin inferir autom├íticamente decisiones
m├®dicas desde el historial.

Incluye:

-   Modelo `PatientProblem`.

-   Persistencia multi-tenant.

-   Trait `BelongsToTenant`.

-   Soft deletes.

-   Relaci├│n con Patient.

-   Estados:

    -   `active`;

    -   `resolved`.

-   C├│digo opcional.

-   Descripci├│n.

-   Fecha de inicio.

-   Fecha de resoluci├│n.

-   Notas.

-   ├ìndices de base de datos.

-   Relaci├│n `Patient ÔåÆ problems`.

-   CRUD dentro del expediente.

-   Crear problema.

-   Editar problema.

-   Resolver problema.

-   Reabrir problema.

-   Eliminar con soft delete.

-   Orden de problemas activos antes de resueltos.

-   Historial de problemas resueltos.

-   Protecci├│n por paciente.

-   Protecci├│n multi-tenant.

Autocomplete:

-   Reutilizaci├│n de `DiagnosisCatalog`.

-   B├║squeda por c├│digo.

-   B├║squeda por descripci├│n.

-   Resultados ordenados por relevancia.

-   Selecci├│n desde cat├ílogo.

-   Autollenado de c├│digo.

-   Autollenado de descripci├│n.

-   Captura manual preservada.

-   No dependencia obligatoria del cat├ílogo.

Integraci├│n con expediente:

-   Secci├│n visual `Problemas cl├¡nicos`.

-   Problemas activos.

-   Problemas resueltos.

-   C├│digo.

-   Descripci├│n.

-   Fecha de inicio.

-   Fecha de resoluci├│n.

-   Notas en problemas activos.

-   Acciones de edici├│n.

-   Acci├│n para marcar como resuelto.

-   Acci├│n para reabrir.

-   Acci├│n para eliminar.

Integraci├│n con consulta:

-   Eager load de problemas activos.

-   Problemas cl├¡nicos activos visibles en el panel de contexto.

-   C├│digo visible.

-   Descripci├│n visible.

-   Fecha de inicio visible.

-   Notas visibles.

-   Problemas resueltos excluidos del contexto activo.

-   La consulta no abandona el workspace para consultar esta
    informaci├│n.

Decisiones de arquitectura:

`PatientProblem` es una entidad longitudinal expl├¡cita.

No se infiere autom├íticamente desde `ConsultationDiagnosis`.

`DiagnosisCatalog` se utiliza ├║nicamente como ayuda de captura.

`PatientMedicalHistory` contin├║a siendo fuente expl├¡cita de:

-   alergias;

-   medicamentos actuales;

-   antecedentes;

-   enfermedades cr├│nicas;

-   cirug├¡as.

No se agreg├│ UUID a `PatientProblem` porque actualmente no tiene
routing p├║blico independiente.

Podr├í evaluarse posteriormente si el dise├▒o de rutas lo requiere.

Fuera de alcance de DT-19:

-   Alertas cl├¡nicas autom├íticas.

-   Reglas m├®dicas autom├íticas.

-   Inferencia autom├ítica desde diagn├│sticos hist├│ricos.

-   Interacciones farmacol├│gicas.

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

-   relaci├│n Patient ÔåÆ problems;

-   resoluci├│n;

-   reapertura;

-   aislamiento entre tenants;

-   ausencia de tenant context.

`PatientProblemFlowTest`:

-   crear;

-   validar descripci├│n obligatoria;

-   editar;

-   resolver;

-   reabrir;

-   soft delete;

-   impedir manipulaci├│n desde otro paciente.

Expediente:

-   visualizaci├│n de problemas activos;

-   visualizaci├│n de problemas resueltos.

Consulta:

-   visualizaci├│n de problemas activos;

-   exclusi├│n de problemas resueltos del contexto activo.

Resultados de regresi├│n:

`13 tests verdes` en PatientProblemTest + PatientProblemFlowTest.

`57 tests verdes` en Patients.

`76 tests verdes` en Consultations.

`147 tests verdes` en la regresi├│n combinada relacionada con DT-19.

Suite completa al cierre t├®cnico:

`854 tests verdes`

`0 failures`

No se registr├│ un n├║mero final de assertions.

No debe inferirse ni inventarse.

Avance global ponderado al cierre t├®cnico de DT-19:

`74%`

Cierre definitivo:

-   Documentaci├│n actualizada.

-   Commit final realizado.

-   Merge a `master`.

-   Comentario t├®cnico registrado en Jira.

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
-   Preparaci├│n para email, WhatsApp y SMS.
-   Ausencia segura de proveedor configurado.
-   `CommunicationProcessor`.
-   M├íximo de 3 intentos.
-   Backoff de 5 y 15 minutos.
-   `communications:process-due`.
-   `AppointmentReminderService`.
-   Generaci├│n idempotente por cita, canal y horario.
-   `communications:generate-appointment-reminders`.
-   Scheduler.
-   `AppointmentReminderValidator`.
-   Cancelaci├│n auditable de recordatorios obsoletos.
-   Protecci├│n ante reprogramaci├│n y cambios de estado.
-   Historial visual de comunicaciones dentro de la cita.
-   Protecci├│n multi-tenant.

Decisiones:

DocTotal no utiliza un transport nulo que simule ├®xito.

Si no existe transport configurado, la comunicaci├│n permanece pendiente
y no consume intento.

Una reprogramaci├│n crea una nueva identidad de recordatorio y el
anterior se conserva como cancelado para auditor├¡a.

La capa de comunicaciones permanece independiente de proveedores
concretos.

Fuera de alcance:

-   Campa├▒as de marketing.
-   Env├¡os masivos.
-   Proveedores reales obligatorios.
-   Confirmaci├│n externa por paciente.
-   Alertas cl├¡nicas.
-   Inferencia m├®dica autom├ítica.

Validaci├│n:

`56 tests verdes` en la regresi├│n espec├¡fica de DT-20.

`148 tests verdes` en appointments despu├®s de la integraci├│n visual.

Suite completa al cierre:

`910 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre:

`77%`

Cierre definitivo:

-   Documentaci├│n actualizada.
-   Commit final realizado.
-   Merge a `master`.
-   Comentario t├®cnico registrado en Jira.
-   DT-20 transicionado a `Listo`.

Commit principal:

`9192020 DT-20 feat: implement transactional communications and appointment reminders`

------------------------------------------------------------------------

## DT-21 --- Audit trail and security hardening foundation

Estado: Completado

Objetivo:

Construir una foundation reutilizable de auditor├¡a y hardening para
registrar acciones sensibles de forma persistente, multi-tenant y
trazable, sin acoplar la l├│gica cl├¡nica u operativa a una
implementaci├│n espec├¡fica de logging.

Incluye:

-   Modelo `AuditEvent`.
-   Persistencia multi-tenant mediante `BelongsToTenant`.
-   Asociaci├│n opcional con usuario/actor.
-   Asociaci├│n polim├│rfica con el recurso auditado.
-   Acci├│n, descripci├│n, IP, user agent y metadata controlada.
-   ├ìndices por tenant, actor, acci├│n y recurso.
-   `AuditLogger`.
-   Sanitizaci├│n recursiva de metadata sensible.
-   Redacci├│n conservadora de claves relacionadas con password, token,
    authorization, cookie, secret y api_key.
-   `safeLog()` para auditor├¡a best-effort.
-   Registro t├®cnico del fallo de auditor├¡a sin romper la operaci├│n
    principal.
-   Protecci├│n append-only a nivel de modelo Eloquent para impedir
    update/delete normales sobre eventos existentes.
-   Auditor├¡a inicial de `patient.updated`.
-   Auditor├¡a inicial de `consultation.completed`.
-   Auditor├¡a inicial de `appointment.rescheduled`.
-   Auditor├¡a inicial de `appointment.cancelled`.
-   Metadata m├¡nima para evitar duplicar payload cl├¡nico sensible.
-   Historial visual de actividad en el expediente del paciente.
-   Paginaci├│n de 5 eventos por p├ígina en el historial visual.
-   Actor, descripci├│n y fecha/hora visibles.
-   Detalles t├®cnicos como IP, user agent, IDs internos y metadata no
    expuestos en la tarjeta visual.
-   Protecci├│n multi-tenant.
-   Cobertura automatizada de modelo, logger, aislamiento, integridad,
    redacci├│n, fiabilidad e integraciones auditadas.

Decisiones de arquitectura y seguridad:

La auditor├¡a de DT-21 es best-effort: una falla al persistir el evento
no debe cambiar el resultado funcional de la acci├│n principal.

`safeLog()` registra el error t├®cnico en el log de Laravel y devuelve
`null` cuando la persistencia del evento falla.

La protecci├│n append-only implementada es a nivel Eloquent. No debe
interpretarse como inmutabilidad garantizada por la base de datos,
porque operaciones directas/query builder pueden omitir eventos del
modelo.

La metadata de auditor├¡a debe contener contexto m├¡nimo y controlado.
No se guardan contrase├▒as, tokens, secretos ni payload cl├¡nico
innecesario.

El historial visual del paciente consulta ├║nicamente eventos cuyo
recurso auditado es el propio Patient. Los eventos auditados sobre
Consultation o Appointment conservan su trazabilidad persistente, pero
no se mezclan autom├íticamente en esa tarjeta.

Fuera de alcance de DT-21:

-   SIEM completo.
-   Auditor├¡a exhaustiva de todas las lecturas.
-   Backups y restauraci├│n integral.
-   2FA obligatorio.
-   Passkeys.
-   Gesti├│n avanzada de dispositivos.
-   Pol├¡tica legal definitiva de retenci├│n.
-   Auditor├¡a exhaustiva de billing.
-   Garant├¡a de inmutabilidad a nivel de base de datos.
-   Outbox transaccional para garantizar persistencia de auditor├¡a ante
    fallos.
-   Alertas cl├¡nicas o inferencia m├®dica.

Validaci├│n:

Regresi├│n focalizada de DT-21:

`58 tests verdes`

Regresi├│n del historial cl├¡nico visual con paginaci├│n:

`13 tests verdes`

Regresi├│n de cancelaci├│n de cita despu├®s del ajuste final a
`safeLog()`:

`11 tests verdes`

Suite completa final:

`936 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre t├®cnico:

`79%`

Cierre definitivo:

-   Commit final realizado.
-   Merge a `master`.
-   Cierre Jira realizado.

Commit principal:

`c3c70d9 DT-21 feat: implement audit trail and security hardening foundation`

------------------------------------------------------------------------

## DT-22 --- Internal SaaS administration panel foundation

Estado: Cierre técnico completado; pendiente commit documental final,
push, PR, merge y cierre Jira.

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

## Pr├│ximos candidatos

Los siguientes bloques no est├ín todav├¡a comprometidos como DT
definitivo.

Deben evaluarse al iniciar el siguiente bloque de desarrollo.

### Candidato A --- Alertas cl├¡nicas inteligentes

Objetivo:

Construir alertas cl├¡nicas contextuales sobre la base estructurada de
`PatientProblem`.

Base disponible:

Patient

ÔåÆ PatientMedicalHistory

ÔåÆ PatientProblem

ÔåÆ Consultation

ÔåÆ ConsultationDiagnosis

ÔåÆ Prescription

ÔåÆ ClinicalDocument

Pendiente:

-   Definir tipos de alertas.

-   Definir reglas expl├¡citas.

-   Definir prioridad.

-   Definir presentaci├│n visual.

-   Definir si algunas alertas requieren confirmaci├│n.

-   Evitar inferencia m├®dica autom├ítica no validada.

-   Evitar convertir validaciones t├®cnicas en decisi├│n cl├¡nica.

Este bloque requiere definici├│n cl├¡nica y de producto antes de
implementaci├│n.

### Candidato B --- Comunicaciones y recordatorios

Estado:

Foundation realizada en DT-20. Permanecen evoluciones futuras como
proveedores reales, consentimiento y nuevos tipos de comunicaci├│n.

Objetivo:

Implementar comunicaci├│n transaccional y recordatorios operativos.

Incluye potencialmente:

-   Email.

-   WhatsApp.

-   SMS.

-   Confirmaci├│n de citas.

-   Recordatorios de citas.

-   Cancelaciones.

-   Reprogramaciones.

-   Avisos de trial.

-   Avisos de pago.

-   Recuperaci├│n de pagos.

-   Suspensi├│n.

-   Reactivaci├│n.

Decisiones pendientes:

-   Proveedores.

-   Consentimiento.

-   Trazabilidad.

-   Reintentos.

-   Costos por canal.

### Candidato C --- Seguridad, privacidad y auditor├¡a

Estado:

Foundation de auditor├¡a realizada en DT-21. Permanecen hardening
avanzado, backups, sesiones, 2FA/passkeys, retenci├│n y observabilidad.

Objetivo:

Preparar DocTotal para una operaci├│n SaaS cl├¡nica m├ís robusta.

Incluye potencialmente:

-   Auditor├¡a de acciones sensibles.

-   Historial formal de cambios cl├¡nicos.

-   Logs operativos.

-   Backups.

-   Restauraci├│n.

-   Pol├¡ticas de retenci├│n.

-   Seguridad de sesiones.

-   Revocaci├│n de dispositivos.

-   2FA.

-   Passkeys.

-   Verificaci├│n de correo.

-   Hardening previo a producci├│n.

### Candidato D --- Operaci├│n interna SaaS

Objetivo:

Construir herramientas internas para operar DocTotal como servicio.

Incluye potencialmente:

-   Panel administrativo.

-   Gesti├│n de tenants.

-   M├®tricas.

-   Soporte.

-   Auditor├¡a comercial.

-   Observabilidad.

-   Gesti├│n de incidencias.

-   Herramientas de billing.

### Candidato E --- Trial y lifecycle avanzado

Objetivo:

Completar la experiencia comercial alrededor del periodo de prueba y
cierre de cuenta.

Incluye potencialmente:

-   Avisos de trial.

-   Pantalla de expiraci├│n.

-   Comunicaci├│n previa al vencimiento.

-   Eliminaci├│n programada.

-   Recuperaci├│n antes de eliminaci├│n.

-   Retenci├│n despu├®s de cancelaci├│n.

-   Auditor├¡a de transiciones.

### Candidato F --- Storage y documentos avanzados

Objetivo:

Evolucionar la infraestructura documental cl├¡nica.

Incluye potencialmente:

-   Cuotas por tenant.

-   Indicadores de almacenamiento.

-   Storage externo.

-   Backups.

-   Retenci├│n.

-   OCR.

-   Laboratorios estructurados.

-   DICOM/PACS.

------------------------------------------------------------------------

## Estado global actual

DT completados:

`DT-1 → DT-22`

DT-22 tiene cierre técnico completado y se encuentra pendiente
únicamente de su cierre documental/Git/Jira.

Baseline funcional actual:

`988 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado vigente:

`79%`

El 79% es el último porcentaje formalmente calculado y no se sustituye
por una estimación.

Siguiente prioridad funcional después del cierre definitivo de DT-22:

**Interacción del paciente con citas**

Objetivo general:

Permitir confirmación, cancelación y solicitud de reprogramación
mediante enlaces seguros, integrados con la infraestructura de
comunicaciones existente.

DocTotal cuenta actualmente con una base clínica, operativa, SaaS,
visual, de comunicaciones, auditoría y operación administrativa interna
considerablemente más madura que al inicio del roadmap.

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
