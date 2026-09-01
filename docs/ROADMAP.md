DocTotal --- ROADMAP

Este documento define los bloques principales de desarrollo de DocTotal y sirve como visión de alto nivel del producto.

DocTotal tiene tres objetivos principales:

Operación clínica

Pacientes.

Agenda.

Citas.

Consultas.

Diagnósticos.

Recetas.

Expediente clínico longitudinal.

Documentos y archivos clínicos.

Autoadministración SaaS

Trial.

Suscripciones.

Pagos.

Renovaciones.

Recuperación de pagos.

Suspensión.

Reactivación.

Cancelación.

Referidos.

Promociones.

Créditos.

Experiencia de producto

Convertir la interfaz funcional en una experiencia moderna, profesional, consistente y agradable para médicos y consultorios.

Reducir la apariencia genérica de Laravel/Livewire.

Construir y mantener una identidad visual propia de DocTotal.

Optimizar los flujos según el trabajo real del médico.

DT-1 --- Base multi-tenant database structure

Estado: Completado

Objetivo

Construir la base de datos inicial con soporte multi-tenant.

Incluye

Estructura inicial multi-tenant.

Tenant como entidad principal de aislamiento.

Relaciones base necesarias para el resto del sistema.

Commit principal

DT-1 Implement base multi-tenant database structure

DT-2 --- Core Eloquent models and relationships

Estado: Completado

Objetivo

Implementar los modelos Eloquent principales y sus relaciones.

Incluye

Modelos base del dominio.

Relaciones entre entidades.

Preparación para aislamiento por tenant.

Commit principal

DT-2 Implement core Eloquent models and relationships

DT-3 --- Tenant isolation and request resolution

Estado: Completado

Objetivo

Garantizar aislamiento entre tenants y resolver el tenant activo por request.

Incluye

TenantContext.

Resolución del tenant actual.

Protección contra acceso cruzado entre tenants.

Middleware de tenant.

Commit principal

DT-3 Implement tenant isolation and request resolution

DT-4 --- Patient clinical record foundation

Estado: Completado

Objetivo

Construir la base inicial del expediente clínico del paciente.

Incluye

Pacientes.

Contactos de emergencia.

Antecedentes médicos iniciales.

Base de consultas.

Fundamentos del expediente clínico.

Nota

DT-4 constituyó la foundation inicial. El expediente longitudinal fue desarrollado posteriormente en DT-14.

Commit principal

DT-4 Implement patient clinical record foundation

DT-5 --- Authentication, registration, dashboard and trial

Estado: Completado

Objetivo

Implementar autenticación, registro y flujo inicial del usuario.

Incluye

Registro.

Inicio de sesión.

Dashboard inicial.

Trial.

trial_started_at.

trial_ends_at.

Asociación del usuario con su tenant.

Commit principal

DT-5 Implement authentication registration dashboard and trial

DT-6 --- Onboarding wizard and postal code autocomplete

Estado: Completado

Objetivo

Construir el onboarding inicial del médico y consultorio.

Incluye

Wizard de onboarding.

Perfil del consultorio.

Perfil médico.

Especialidad.

Horarios de atención.

Autocompletado por código postal.

Commit principal

DT-6 feat: implement onboarding wizard with postal code autocomplete

DT-7 --- Gestión de pacientes

Estado: Completado

Objetivo

Construir el flujo principal de pacientes y expediente.

Incluye

Listado de pacientes.

Alta de pacientes.

Edición.

Detalle del paciente.

Antecedentes médicos.

Contactos de emergencia.

Integración con consultas.

Commit principal

DT-7 Implement gestión de pacientes

DT-8 --- Agenda, citas y ciclo operativo

Estado: Completado

Objetivo

Construir el sistema completo de agenda y citas.

Incluye

Creación de citas.

Creación rápida de pacientes desde una cita.

Disponibilidad basada en horarios del onboarding.

Excepciones de horario.

Bloqueos parciales y completos.

Horarios extraordinarios.

Prevención de solapamientos.

Eliminación de slots pasados.

Agenda por mes, semana y día.

Dashboard dinámico.

Estados de Appointment.

Confirmación.

Check-in.

Inicio de consulta.

Finalización automática de cita.

Cancelación.

No-show semiautomático.

Periodo de gracia de 15 minutos para no-show.

Reprogramación.

Edición de motivo y notas.

Integración Appointment → Consultation.

Continuar consulta cuando la cita queda en progreso.

Aislamiento multi-tenant.

Cobertura automatizada.

Baseline al cierre

366 tests verdes.

DT-9 --- Consultation workflow and clinical lifecycle

Estado: Completado

Objetivo

Convertir Consultation en una entidad clínica persistente desde el inicio de la atención.

Flujo

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

Incluye

Estado draft.

Estado completed.

Una sola Consultation por Appointment.

Consultas sin cita.

Continuación y edición de drafts.

Finalización explícita.

Diagnósticos y diagnóstico principal.

Catálogo y autocomplete de diagnósticos.

Recetas asociadas.

Catálogo y autocomplete de medicamentos.

Edición y cancelación de recetas.

PDF de receta.

Historial de consultas.

Protección multi-tenant.

Tests del ciclo clínico.

Baseline al cierre

393 tests verdes.

DT-10 --- Product inventory, roadmap and TODO

Estado: Completado

Objetivo

Realizar un inventario general de DocTotal antes de continuar desarrollando funcionalidades aisladas.

Incluye

Inventario funcional.

Inventario clínico.

Inventario SaaS.

Inventario visual.

Identificación de funcionalidades existentes, parciales e inexistentes.

Definición de los tres pilares principales.

Creación de ROADMAP.

Creación de TODO.

Identificación de los grandes bloques futuros.

Baseline al cierre

393 tests verdes.
1179 assertions.
0 failures.

DT-11 --- Subscription lifecycle foundation

Estado: Completado

Objetivo

Construir el dominio base de suscripciones necesario para administrar el ciclo económico de cada tenant.

Incluye

Modelo Subscription.

UUID y soft deletes.

Protección multi-tenant.

Ciclos monthly/yearly.

Estados active, past_due y cancelled.

Billing anchor.

Renovaciones sin billing drift.

Manejo de fin de mes y años bisiestos.

Cancelación programada.

Reanudación.

Transiciones comerciales.

Separación Subscription/Tenant.

Derecho de acceso centralizado.

Cobertura automatizada.

Baseline al cierre

445 tests verdes.
0 failures.

DT-12 --- Payments, billing recovery and automatic account lifecycle

Estado: Completado

Objetivo

Conectar Subscription con Stripe y convertirla en un sistema de billing SaaS capaz de cobrar, renovar, recuperar pagos fallidos, suspender y reactivar tenants con mínima intervención manual.

Incluye

Modelo Payment.

Stripe.

Stripe Customer.

SetupIntent.

PaymentIntent.

Métodos de pago guardados.

Checkout manual.

Plan mensual de $600 MXN.

Plan anual de $6,000 MXN.

Cambio mensual ↔ anual.

Renovaciones automáticas.

Feature flag BILLING_AUTOMATIC_CHARGING_ENABLED.

Recuperación past_due.

Grace period de 7 días.

Reintentos.

Idempotencia.

Suspensión y reactivación.

Cancelación programada y reanudación.

Historial de pagos.

Scheduler.

Hardening de estados límite y multi-tenancy.

Fuera de alcance

Webhooks Stripe.

Recibos/comprobantes.

Facturación fiscal.

Auditoría formal de billing.

Notificaciones comerciales.

Baseline al cierre

696 tests verdes.
0 failures.

DT-13 --- Referral program and promotional credits

Estado: Completado

Objetivo

Implementar el programa de referidos y la foundation de créditos promocionales integrada con billing.

Incluye

Código único y permanente por tenant.

Enlace de referido.

Captura durante registro.

Prevención de auto-referidos y duplicados.

Modelo Referral.

Calificación por primer pago exitoso.

Descuento de $50 MXN al referido.

Crédito de $50 MXN al referidor.

Máximo de 5 recompensas / $250 MXN al mes.

PromotionalCredit.

Estados available, reserved y consumed.

Reserva, consumo y liberación idempotentes.

Integración con pagos, renovaciones y recuperación.

Checkout promocional.

Limpieza y reconciliación.

Scheduler.

Protección multi-tenant.

Cobertura automatizada.

Baseline al cierre

797 tests verdes.
2244 assertions.
0 failures.

Commit principal

f4d7322 DT-13 feat: complete referral program and promotional credits

DT-14 --- Expediente clínico longitudinal

Estado: Completado

Objetivo

Convertir la información clínica existente del paciente en un expediente longitudinal coherente, reutilizando las entidades clínicas existentes y evitando nuevas fuentes de verdad.

Incluye

Auditoría clínica previa.

Evolución de patients.show.

Resumen clínico basado en PatientMedicalHistory.

Línea de tiempo clínica unificada.

Solo consultas completed en historial oficial.

Drafts excluidos.

Diagnósticos dentro de su consulta.

Diagnósticos históricos consolidados.

Recetas vinculadas dentro del evento de consulta.

Recetas independientes como eventos propios.

Prevención de duplicados.

Tratamientos históricos consolidados.

Referencias a entidades originales.

Protección multi-tenant.

Cobertura automatizada.

Decisiones de arquitectura

No crear tabla adicional de eventos.

Construir la línea de tiempo como proyección sobre las entidades existentes.

PatientMedicalHistory continúa como fuente de antecedentes.

Medicamentos actuales permanecen separados de tratamientos históricos prescritos.

Baseline al cierre

814 tests verdes.
2339 assertions.
0 failures.

Commit principal

3df050f DT-14 feat: implement longitudinal clinical record

DT-15 --- Clinical files and medical documents

Estado: Completado

Objetivo

Incorporar documentos clínicos al expediente sobre una base segura, multi-tenant y extensible.

Incluye

Modelo ClinicalDocument.

UUID.

Asociación con Patient.

Asociación opcional con Consultation del mismo paciente.

Categorías general, laboratory, imaging y other.

Metadata separada del archivo.

Storage privado.

CLINICAL_DOCUMENTS_DISK.

PDF, JPG, JPEG, PNG y WebP.

Límite de 10 MB.

Visualización inline.

Miniaturas protegidas.

Descarga segura.

Eliminación controlada.

Integración con expediente.

Protección multi-tenant.

Hardening de StoreClinicalDocument.

ResolveTenant antes de SubstituteBindings.

Cobertura automatizada y validación manual.

Fuera de alcance

Cuotas totales de almacenamiento.

Proveedor externo definitivo.

Retención y respaldo definitivos.

OCR.

DICOM/PACS.

Laboratorios estructurados.

Firma digital.

Thumbnails derivados de PDF.

Baseline al cierre

837 tests verdes.
2395 assertions.
0 failures.

Commit principal

07f73ec DT-15 feat: implement clinical files and medical documents

DT-16 --- Visual redesign / DocTotal UI

Estado: Completado

Objetivo

Transformar la interfaz funcional de DocTotal en una experiencia visual propia, moderna, profesional, consistente y responsive, preservando workflows y lógica de negocio.

Incluye

Foundation visual.

Design tokens.

Identidad y branding.

Logo y favicons.

Shell principal.

Sidebar y header.

Navegación responsive.

Pacientes.

Expediente longitudinal.

Agenda y citas.

Consultas.

Recetas.

Onboarding.

Configuración.

Billing.

Login y registro.

Recuperación/restablecimiento de contraseña.

Correo personalizado de reset.

Landing pública.

Flash messages.

Revisión de receta imprimible y PDF.

Auditoría visual final.

Suite completa sin regresiones.

Baseline al cierre

837 tests verdes.
0 failures.

Commit principal

68fa046 DT-16 feat: complete DocTotal visual redesign

DT-17 --- Clinical workspace / Consulta médica avanzada

Estado: Completado

Objetivo

Evolucionar la consulta desde una experiencia basada principalmente en formulario hacia un workspace clínico avanzado que mantenga visible el contexto relevante del paciente y proteja la captura mediante autosave seguro.

Incluye

Contexto clínico

Workspace responsive.

Resumen rápido del paciente.

Edad.

Sexo.

Grupo sanguíneo.

Alergias.

Medicamentos actuales.

Enfermedades crónicas.

Cirugías.

Antecedentes relevantes.

Consultas recientes finalizadas.

Diagnósticos recientes dentro de su contexto.

Fuentes de verdad

PatientMedicalHistory continúa como fuente de alergias, medicamentos actuales y antecedentes.

Consultation y ConsultationDiagnosis alimentan el historial reciente.

Solo consultas completed se muestran como historia clínica final.

No se creó un nuevo modelo para duplicar información clínica.

Captura clínica

Reorganización de motivo de consulta.

Signos vitales.

SOAP.

Diagnósticos.

Panel contextual persistente.

Barra flotante de acciones.

Autosave

Autosave de Consultation draft.

Guardado automático de campos clínicos.

Estados de cambios pendientes, guardando, guardado y error.

Protección beforeunload frente a cambios pendientes.

Validación

Mensajes de validación en español.

Nombres clínicos amigables.

Resaltado visual de campos inválidos.

Resaltado de signos vitales inválidos.

Scroll automático al primer error.

Foco automático en el primer campo inválido.

Los rangos implementados son límites técnicos de captura y no constituyen alertas clínicas ni un sistema de decisión médica.

Finalización segura

Protección frente a finalización con cambios pendientes.

Protección mientras existe guardado en curso.

Protección cuando existe error de guardado.

Persistencia y validación backend antes de completar.

Una Consultation inválida no puede finalizar.

Appointment solamente se completa después de finalizar correctamente su Consultation.

Compatibilidad

Lifecycle draft/completed preservado.

Appointment ↔ Consultation preservado.

Consultas directas sin cita preservadas.

Continuidad de drafts preservada.

Diagnósticos preservados.

Recetas posteriores a finalización preservadas.

Aislamiento multi-tenant preservado.

Fuera de alcance

Alertas clínicas inteligentes.

Problemas clínicos activos.

Plantillas clínicas.

Plantillas por especialidad.

Motor de decisión clínica.

Repetición avanzada de tratamientos.

Calidad

Tests específicos de consultas:

76 tests verdes.

ConsultationFlowTest:

18 tests verdes.

Suite completa al cierre de DT-17:

840 tests verdes.
0 failures.

No se registró un número final de assertions para DT-17.

Commit principal

0fb847f DT-17 feat: implement advanced clinical consultation workspace

DT-18 --- Documentation baseline normalization and next DocTotal development block

Estado: En curso

Objetivo

Iniciar el siguiente bloque de desarrollo desde documentación sincronizada con el estado real de DocTotal después de DT-17.

DT-18 comienza normalizando TODO y ROADMAP antes de seleccionar e implementar su siguiente bloque funcional.

Baseline de entrada

Estado funcional heredado de DT-17:

Clinical workspace implementado.

Contexto clínico persistente durante consulta.

Autosave seguro.

Validación y protección de finalización.

DT-17 integrado en master.

840 tests verdes.

0 failures.

Avance global ponderado al inicio de DT-18: 72%.

Este baseline corresponde al cierre validado de DT-17. No implica que se haya registrado una nueva ejecución completa post-merge en master.

Fase 1 — Normalización documental

Actualizar TODO al estado post-DT-17.

Actualizar ROADMAP al estado post-DT-17.

Registrar commits reales de DT-14 a DT-17.

Registrar baseline de 840 tests.

Retirar Clinical workspace de los bloques futuros.

Identificar los pendientes reales del producto.

Establecer la nueva regla documental de inicio/cierre de cada DT.

Fase 2 — Auditoría y selección funcional

Después de normalizar la documentación:

Revisar pendientes.

Revisar dependencias.

Identificar el problema de mayor prioridad.

Definir objetivo.

Definir alcance.

Definir fuera de alcance.

Verificar impacto multi-tenant, clínico y SaaS.

El bloque funcional de DT-18 permanece pendiente de selección hasta completar esta auditoría.

No debe asignarse funcionalidad arbitrariamente antes de finalizar la normalización inicial.

Fase 3 — Desarrollo

Una vez seleccionado el bloque:

Implementación.

Tests específicos.

Integración.

Validación de regresiones.

Fase 4 — Cierre

Suite completa.

Registrar baseline final.

Recalcular avance global con el mismo criterio ponderado.

Normalizar nuevamente TODO.

Normalizar nuevamente ROADMAP.

Commit final.

Merge.

Cierre de Jira.

Estado actual

Bloques completados

DT-1
DT-2
DT-3
DT-4
DT-5
DT-6
DT-7
DT-8
DT-9
DT-10
DT-11
DT-12
DT-13
DT-14
DT-15
DT-16
DT-17

Bloque en curso

DT-18 — Documentation baseline normalization and next DocTotal development block

Baseline actual de entrada

840 tests verdes.
0 failures.
72% de avance global ponderado.

Siguiente bloque funcional

Pendiente de selección dentro de DT-18 después de la normalización documental y auditoría de prioridades.

El Clinical workspace ya no es un bloque futuro: fue implementado en DT-17.

Regla documental a partir de DT-18

El ciclo documental y de desarrollo queda establecido como:

Inicio del DT
→ normalización de TODO y ROADMAP contra el estado real heredado
→ auditoría
→ definición del bloque
→ desarrollo
→ tests
→ normalización documental final
→ commit/merge
→ cierre Jira.

Esto evita que TODO y ROADMAP permanezcan un DT atrás respecto al estado real del producto.

Principio para seleccionar próximos bloques

La selección del siguiente bloque debe considerar conjuntamente:

Valor para el médico.

Valor para la operación del consultorio.

Riesgo clínico.

Riesgo SaaS/comercial.

Seguridad y privacidad.

Dependencias técnicas.

Multi-tenancy.

Madurez de la infraestructura.

Cobertura automatizada.

Experiencia de usuario.

Preparación para producción.

DocTotal debe continuar evolucionando como un producto integrado y no como una colección de funcionalidades aisladas.