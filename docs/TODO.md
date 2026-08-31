DocTotal --- TODO

Progreso general

70% completado

██████████████░░░░░░ 70%

El porcentaje representa avance global del producto, no cobertura de

tests.

Se actualizará al cierre de cada DT.

Este documento representa el estado funcional actual de DocTotal y sirve

como

mapa maestro para decidir los siguientes bloques de desarrollo (DT).

No sustituye al Roadmap.

ROADMAP.md registra los bloques DT realizados, su objetivo y su

cierre.

TODO.md registra qué existe actualmente, qué está incompleto, qué

falta y

  qué decisiones de producto siguen pendientes.

Este documento debe actualizarse al finalizar cada DT antes de

seleccionar

el siguiente bloque de desarrollo.

Objetivo del producto

DocTotal debe convertirse en una plataforma SaaS para médicos y

consultorios

que cubra tres pilares principales.

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

DocTotal debe administrar automáticamente el ciclo comercial y operativo

de

cada tenant:

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

El objetivo es minimizar al máximo la intervención manual del

administrador

de DocTotal.

3. Experiencia de usuario

DocTotal debe sentirse como un producto médico profesional y terminado,

no como una aplicación construida directamente con componentes estándar

de Laravel/Livewire.

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

Usar los siguientes estados:

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

[!] Mantener aislamiento como requisito obligatorio para todo

módulo nuevo.

2. Autenticación y seguridad de cuenta

Relacionado principalmente con DT-5.

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

[~] Recuperación de contraseña --- infraestructura existente;

revisar flujo/UI.

[~] Cambio de contraseña --- infraestructura existente; revisar
UI

desde

      configuración.

Seguridad adicional

[~] Two-factor authentication --- infraestructura de base de
datos

existente.

[~] Passkeys --- infraestructura de base de datos existente.

Verificar implementación completa de 2FA.

Verificar implementación completa de passkeys.

Verificación de correo.

Administración visible de sesiones.

Revocación de sesiones/dispositivos.

[!] Auditar seguridad completa antes de producción.

3. Onboarding

Relacionado principalmente con DT-6.

Wizard de onboarding.

Paso 1 --- Datos profesionales.

Paso 2 --- Datos del consultorio.

Paso 3 --- Horarios de atención.

Paso 4 --- Confirmación.

Especialidad.

Cédula profesional.

Datos de contacto.

Dirección.

Código postal.

Servicio de código postal.

Autocompletado por código postal.

Horarios de atención.

Duración predeterminada de citas.

onboarding_completed_at.

Middleware EnsureOnboardingIsComplete.

Tests del wizard.

Tests del middleware.

Experiencia visual del onboarding.

Mostrar claramente información del periodo de prueba.

Registro preparado para promociones y referidos.

Captura opcional de código de referido durante el alta inicial.

Aplicación automática de código mediante enlace de referido.

[!] Revisar qué información deberá ser obligatoria antes de

producción.

4. Pacientes

Relacionado principalmente con DT-4, DT-7 y DT-14.

Modelo Patient.

Listado de pacientes.

Búsqueda.

Alta.

Edición.

Detalle.

Datos generales.

Fecha de nacimiento.

Edad.

Sexo.

Grupo sanguíneo.

Datos de contacto.

Contactos de emergencia.

Modelo PatientEmergencyContact.

Antecedentes médicos.

Modelo PatientMedicalHistory.

Historial de consultas.

Acceso a consultas desde expediente.

Integración paciente → consulta.

Expediente clínico longitudinal.

Resumen clínico del paciente.

Línea de tiempo clínica unificada.

Vista rápida de diagnósticos históricos relevantes.

Vista rápida de tratamientos históricos.

Medicamentos actuales existentes dentro de antecedentes médicos.

Referencias navegables a consultas y recetas originales.

Tests de pacientes.

Tests de contactos de emergencia.

Tests de antecedentes médicos.

Tests específicos del expediente longitudinal.

Alertas clínicas relevantes.

Archivos asociados al paciente.

Estudios.

Laboratorios.

Imágenes médicas.

[!] Evaluar detección de pacientes duplicados.

5. Expediente clínico

Relacionado con DT-4, DT-7, DT-9, DT-14, DT-15 y futuros DT.

DT-14 convirtió la base clínica existente en un expediente longitudinal
funcional sin duplicar las fuentes de verdad ya existentes.

DT-15 incorporó la capa documental del expediente sobre storage privado,
manteniendo los metadatos clínicos separados de los bytes almacenados.

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

Edición de antecedentes.

Tests de antecedentes.

Expediente longitudinal implementado en DT-14

Resumen clínico dentro del expediente del paciente.

Línea de tiempo clínica unificada.

Consultas finalizadas dentro de la historia clínica.

Consultas en borrador excluidas del historial oficial.

Diagnósticos mostrados en el contexto de su consulta.

Diagnósticos históricos consolidados.

Recetas asociadas mostradas dentro de la consulta correspondiente.

Recetas independientes mostradas como eventos propios.

Prevención de duplicar una receta vinculada como evento independiente.

Tratamientos históricos consolidados.

Consolidación de tratamientos por medicamento + dosis + frecuencia +
duración.

Última fecha de prescripción disponible por tratamiento consolidado.

Enlaces a la consulta y receta originales.

Orden cronológico descendente.

Protección multi-tenant.

Proyección longitudinal construida sobre modelos clínicos existentes,
sin nueva tabla de eventos.

Expediente documental implementado en DT-15

Modelo ClinicalDocument.

Asociación documento → paciente.

Asociación opcional documento → consulta del mismo paciente.

Categorías general, laboratory, imaging y other.

Metadatos separados del archivo físico.

Fecha clínica/documental opcional.

Notas y título del documento.

Storage privado mediante abstracción de filesystem.

Carga segura de PDF, JPG, JPEG, PNG y WebP.

Límite actual de 10 MB por archivo.

Visualización inline protegida.

Miniatura protegida para imágenes.

Representación visual para PDF.

Descarga segura conservando el nombre original.

Eliminación controlada de registro y archivo físico.

Protección multi-tenant en consulta, visualización, descarga y
eliminación.

Validación defensiva dentro de StoreClinicalDocument.

Validación de tenant para paciente y uploader.

Integración dentro del expediente del paciente.

Cobertura automatizada específica.

Evolución pendiente

[~] Mejorar estructura clínica de antecedentes.

Hospitalizaciones previas.

Problemas activos.

Alertas clínicas.

Dominios especializados para resultados estructurados de laboratorio.

Integración especializada para imágenes médicas/DICOM/PACS.

OCR o extracción estructurada de documentos.

[!] Definir límites totales de almacenamiento por tenant.

[!] Definir estrategia de respaldo y retención.

[!] Definir política de conservación documental.

6. Agenda

Relacionado principalmente con DT-8.

Modelo Appointment.

Vista mensual.

Vista semanal.

Vista diaria.

Navegación entre periodos.

Crear cita.

Crear paciente desde cita.

Buscar paciente.

Disponibilidad basada en horarios.

AppointmentAvailabilityService.

Excepciones de horario.

Bloqueos parciales.

Bloqueos completos.

Horarios extraordinarios.

Prevención de solapamientos.

Eliminación de slots pasados.

Filtrado por estado.

Búsqueda desde agenda.

Tests de disponibilidad.

Tests de creación.

Tests de edición.

Tests de reprogramación.

Tests de slots.

Tests de vistas de agenda.

[~] Experiencia visual del calendario.

Mejorar diferenciación visual de estados.

Mejorar densidad de información.

Optimizar operación rápida desde agenda.

[!] Evaluar acciones mediante popover/modal sin abandonar

calendario.

7. Ciclo de citas

Relacionado principalmente con DT-8.

Estados actuales

scheduled.

confirmed.

checked_in.

in_progress.

completed.

cancelled.

no_show.

Operaciones

Programar.

Confirmar.

Check-in.

Iniciar consulta.

Completar.

Cancelar.

Reprogramar.

Editar motivo.

Editar notas.

Continuar consulta en progreso.

Integración Appointment → Consultation.

No-show

Periodo de gracia de 15 minutos.

Regla temporal basada en ends_at + 15 minutos.

No-show nunca completamente automático.

Confirmación explícita por usuario.

Pendiente

Recordatorios de citas.

Confirmación externa por paciente.

WhatsApp.

SMS.

Correo.

[!] Definir estrategia de comunicación con pacientes.

8. Consultas

Relacionado principalmente con DT-9.

Modelo Consultation.

Consultation persistente.

Estado draft.

Estado completed.

Creación desde Appointment.

Una Consultation por Appointment.

Continuar Consultation existente.

Consulta sin cita.

Edición mientras está en draft.

Finalización explícita.

Finalización Consultation → Appointment completed.

Signos vitales.

Peso.

Estatura.

Presión arterial.

Frecuencia cardiaca.

Frecuencia respiratoria.

Temperatura.

Saturación O₂.

Motivo de consulta.

Nota SOAP.

Subjetivo.

Objetivo.

Evaluación / diagnóstico.

Plan.

Diagnósticos asociados.

Diagnóstico principal.

Recetas asociadas.

Historial de consultas.

Tests del modelo.

Tests del flujo.

Tests del lifecycle.

Tests Appointment → Consultation.

[~] Experiencia de captura durante consulta.

Autosave.

Indicador de cambios sin guardar.

Alertas clínicas visibles durante consulta.

Antecedentes relevantes visibles durante consulta.

Medicamentos actuales visibles durante consulta.

Historial reciente accesible sin abandonar consulta.

Plantillas clínicas.

Plantillas por especialidad.

[!] Diseñar consulta como workspace clínico y no solamente como

formulario.

9. Diagnósticos

Modelo ConsultationDiagnosis.

Modelo DiagnosisCatalog.

Catálogo diagnóstico.

Comando ImportDiagnosisCatalog.

Importación de catálogo.

Búsqueda/autocompletado.

Selección desde catálogo.

Diagnósticos asociados a Consultation.

Diagnóstico principal.

Código diagnóstico.

Descripción.

Tests del catálogo.

Tests de autocomplete.

Tests del flujo de diagnósticos.

Historial consolidado de diagnósticos por paciente.

Problemas clínicos activos.

Resolución/cierre de problemas.

[!] Definir modelo de problemas clínicos longitudinales.

10. Recetas y medicamentos

Recetas

Modelo Prescription.

Modelo PrescriptionItem.

Crear receta desde consulta.

Asociar receta a Consultation.

Medicamentos múltiples.

Medicamento.

Presentación.

Dosis.

Frecuencia.

Duración.

Indicaciones.

Indicaciones generales.

Ver receta.

Editar receta.

Anular receta.

Imprimir.

Descargar PDF.

Datos del médico.

Cédula profesional.

Tests de creación.

Tests de edición.

Tests de cancelación.

Tests del modelo.

Tests de PDF.

Catálogo de medicamentos

Modelo MedicationCatalog.

Catálogo de medicamentos.

Comando ImportMedicationCatalog.

Importación de catálogo.

Autocompletado.

Búsqueda por información del medicamento.

Tests de autocomplete.

Pendiente

Firma digital.

QR/verificación de receta.

Historial longitudinal de medicamentos/tratamientos por
paciente.

Repetir receta anterior.

[!] Revisar requisitos legales/documentales antes de producción.

11. Archivos clínicos

Relacionado principalmente con DT-15.

DT-15 implementó la foundation documental del expediente clínico. Los
archivos se almacenan de forma privada y los metadatos permanecen en
ClinicalDocument, sin guardar binarios en la base de datos.

Implementado

Modelo ClinicalDocument.

UUID para routing.

Upload.

Visualización inline protegida.

Descarga segura.

Eliminación controlada.

PDF.

Imágenes JPG/JPEG/PNG/WebP.

Categoría general.

Categoría laboratorio.

Categoría imagen.

Categoría otros.

Asociación con paciente.

Asociación opcional con consulta.

Validación de que la consulta pertenece al mismo paciente.

Metadatos.

Fecha del documento/estudio.

Título.

Notas.

Nombre original.

MIME type.

Tamaño en bytes.

Usuario que realizó la carga.

Vista previa mediante miniatura protegida para imágenes.

Tarjeta visual para PDF.

Seguridad multi-tenant.

Protección contra paciente de otro tenant.

Protección contra uploader de otro tenant.

Storage privado.

Límite de 10 MB por archivo.

Tipos permitidos validados también dentro de la Action.

Limpieza del archivo si falla la persistencia en base de datos.

Limpieza del registro cuando el archivo físico ya no existe.

Integración con el expediente del paciente.

Tests de almacenamiento, aislamiento, visualización, descarga y
eliminación.

Decisiones de arquitectura

Los binarios no se almacenan en base de datos.

El disco utilizado se registra por documento para permitir migraciones
futuras de storage.

El filesystem se consume mediante Storage, sin exponer rutas físicas.

No se generan URLs públicas permanentes para documentos clínicos.

ResolveTenant se ejecuta antes de SubstituteBindings para que el route
model binding respete TenantScope.

Pendiente futuro

Límites totales de almacenamiento por tenant.

Indicador de almacenamiento utilizado.

Migración/selección operativa de proveedor externo cuando sea necesaria.

URLs temporales firmadas si un flujo futuro las requiere.

Thumbnails derivados para PDF.

OCR/extracción.

DICOM/PACS.

Resultados de laboratorio estructurados.

[!] Definir política de conservación y retención.

[!] Definir estrategia de respaldo de documentos clínicos.

12. Dashboard

Relacionado principalmente con DT-8.

Dashboard funcional.

Citas de hoy.

Pacientes.

Citas por atender.

Próxima cita.

Agenda de hoy.

Actividad del día.

Consultas finalizadas.

Consultas en progreso.

Recetas.

Próximos 7 días.

Estado de agenda.

Acciones rápidas.

Tests del dashboard.

[~] Jerarquía visual.

[~] Utilidad clínica/operativa de algunos indicadores.

Alertas importantes.

Trial / estado de suscripción.

Avisos de pago.

Acciones pendientes.

Pacientes esperando.

[!] Revisar qué información necesita realmente el médico al

comenzar el día.

13. Configuración

Perfil profesional

Nombre.

Apellidos.

Especialidad.

Cédula.

Teléfono.

WhatsApp.

Biografía.

Fotografía.

Firma.

Consultorio

Modelo PracticeProfile.

Nombre público.

Razón social.

RFC.

Logo.

Teléfono.

WhatsApp.

Correo.

Descripción.

Dirección.

Colonia.

Código postal.

Ciudad.

Estado.

País.

Documentos impresos

Configuración de impresión.

Pie de página.

Datos del médico en receta.

Datos del consultorio.

Pendiente

Configuración de cuenta.

Seguridad.

Cambio de contraseña desde UI.

Suscripción.

Facturación.

Métodos de pago.

Referidos.

Almacenamiento utilizado.

[!] Reorganizar configuración por secciones/pestañas.

14. Trial

Existe infraestructura inicial de trial.

Implementado

Campo status.

Estado inicial trial.

trial_started_at.

trial_ends_at.

Duración configurable durante registro.

Inicialización automática durante registro.

Tenant::isOnTrial().

Tenant::trialHasExpired().

Tests de trial.

Tests del trial durante registro.

Pendiente

Enforcement de acceso integrado mediante reglas centralizadas

del Tenant.

Trial integrado con Tenant::hasAccessToService().

Aviso de días restantes.

Avisos próximos al vencimiento.

Pantalla de trial vencido.

Conversión trial → suscripción.

Selección mensual/anual.

Bloqueo controlado al vencer.

[!] Definir qué puede hacer el tenant después del vencimiento.

[!] Definir si existirá periodo de gracia.

15. Suscripciones

Relacionado principalmente con DT-11 y DT-12.

La infraestructura de suscripciones ya define el periodo de servicio,
ciclos

de facturación, estados comerciales, derecho de acceso del tenant e
integración

con billing real mediante Stripe.

Modelo comercial

Modelo Subscription.

UUID.

Soft deletes.

Relación Tenant → subscriptions.

Protección multi-tenant.

Ciclo mensual.

Ciclo anual.

Estado active.

Estado past_due.

Estado cancelled.

Modelo Plan si posteriormente se requieren varios planes.

Precio mensual definido: $600 MXN.

Precio anual definido: $6,000 MXN.

Moneda comercial almacenada por suscripción.

Periodo de servicio

starts_at.

current_period_starts_at.

current_period_ends_at.

next_billing_at.

cancel_at_period_end.

cancelled_at.

Primer pago como billing anchor.

Conservación de fecha y hora del primer pago.

Conservación de minutos y segundos.

Manejo de fin de mes sin overflow.

Manejo de años bisiestos.

Protección contra billing drift en renovaciones.

Operaciones

Alta de suscripción.

Activación del tenant al crear suscripción.

Renovación mensual.

Renovación anual.

Cancelación programada al final del periodo.

Reanudación antes del vencimiento.

Procesamiento al final exacto del periodo.

Transición active → past_due.

Transición past_due → active.

cancelled como estado terminal actual.

Prevención de segunda Subscription active.

Prevención de segunda Subscription cuando existe past_due.

Consulta de suscripción vigente del tenant.

Derecho de acceso centralizado.

Acceso durante past_due mientras el tenant no esté suspendido.

Suspensión del Tenant independiente del estado de Subscription.

Conversión trial → suscripción desde UI.

Cambio mensual ↔ anual programado al siguiente periodo.

Historial comercial visible para el usuario.

Upgrade/downgrade si posteriormente existen varios planes.

Calidad

Tests del modelo Subscription.

Tests de activación.

Tests de renovación.

Tests de billing anchor.

Tests de fin de mes.

Tests de año bisiesto.

Tests de cancelación programada.

Tests de reanudación.

Tests de transiciones.

Tests de acceso del Tenant.

Tests de expiración/procesamiento de periodo.

Tests de cambio de plan.

Decisiones resueltas / pendientes

Proveedor de pagos definido: Stripe.

Precio mensual definido: $600 MXN.

Precio anual definido: $6,000 MXN.

Descuento anual definido: equivalente a 2 meses sin costo frente
al plan mensual.

Regla base de cancelación definida: cancelación al final del
periodo con posibilidad de conservar la suscripción antes del
vencimiento.

[!] Definir reglas de reembolso.

16. Pagos y facturación SaaS

Relacionado principalmente con DT-12.

DT-12 implementó la foundation operativa de pagos y recuperación SaaS
sobre

la Subscription construida en DT-11. Stripe quedó integrado y validado
en modo

de prueba. Los cobros automáticos permanecen protegidos por feature flag
hasta

su activación controlada en el entorno objetivo.

Modelo Payment.

Registro de pagos.

Fecha de intento, pago y fallo.

Importe.

Moneda.

Método de pago Stripe.

Estados pending, succeeded, failed y canceled.

Referencia del proveedor.

Pago exitoso.

Pago pendiente.

Pago fallido.

Reintentos programados.

Renovación automática implementada y protegida por feature flag.

Vencimiento y recuperación past_due.

Periodo de gracia de 7 días.

Suspensión automática por falta de pago al vencer el grace
period.

Reactivación después del pago.

Recuperación automática mediante tarjeta guardada.

Recuperación manual mediante PaymentIntent.

Historial de pagos visible.

Métodos de pago guardados.

Alta, actualización y eliminación del método de pago Stripe.

Idempotencia de intentos de renovación, recuperación y checkout
manual.

Integración de descuentos y créditos promocionales con billing.

Reserva idempotente de créditos promocionales.

Consumo de créditos únicamente después de pago exitoso.

Liberación de créditos después de pago fallido o checkout
cancelado.

Prevención de checkouts manuales pendientes duplicados.

Cambio de plan con cancelación segura del checkout anterior.

Limpieza automática de checkouts manuales abandonados.

Reconciliación segura de checkouts cuyo PaymentIntent ya fue
cobrado en Stripe.

Scheduler para renovaciones, reintentos, cancelaciones, grace
periods vencidos y limpieza de checkouts abandonados.

Stripe test mode validado end-to-end.

Recibos/comprobantes.

Webhooks.

Idempotencia de webhooks.

Auditoría formal de eventos de pago.

[!] Definir facturación fiscal de DocTotal.

Proveedor de pagos definido: Stripe.

17. Ciclo de vida del tenant

Actualmente existe una parte importante de la automatización comercial
base.

Campos existentes

status.

suspended_at.

deletion_due_at.

trial_started_at.

trial_ends_at.

Soft deletes.

Ciclo objetivo preliminar

Trial exitoso:

trial

→ active

Problema de pago:

active

→ past_due

→ suspended

→ active

Trial sin conversión:

trial

→ expired

Cancelación:

active

→ cancelled

→ deletion_pending

→ deleted

Estado actual

Estados comerciales básicos definidos entre Tenant y
Subscription.

Transiciones base de billing automatizadas.

Reglas de acceso centralizadas mediante el Tenant.

Suspensión automática al vencer el periodo de gracia.

Reactivación automática después de recuperar el pago.

Cancelación programada al final del periodo.

Eliminación programada.

Recuperación antes de eliminación.

Scheduler SaaS para billing.

[~] Procesamiento programado implementado mediante comandos;
queues/jobs dedicados quedan para necesidades futuras.

Auditoría formal de transiciones.

[!] Definir política de conservación de expedientes después de
cancelar.

Comportamiento de acceso en estado past_due: acceso mientras
el tenant no esté suspendido.

Comportamiento de acceso en estado suspended: sin acceso al
servicio.

18. Referidos y promociones

Relacionado principalmente con DT-13.

DT-13 implementó el programa de referidos y la foundation de créditos

promocionales integrada con el lifecycle de billing.

Código de referido

Código único por tenant.

Generación automática.

Código permanente.

Índice UNIQUE en base de datos.

Backfill de códigos para tenants existentes.

Pantalla para consultar código.

Enlace de referido.

Acción para copiar/compartir referencia.

Validación del código.

Identificación del tenant referente.

Uso durante registro

Un tenant nuevo puede utilizar como máximo un código de
referido.

Captura del código durante la inscripción inicial.

Aplicación automática mediante parámetro ref.

Validación de códigos ingresados manualmente.

Asociación permanente entre referidor y referido.

Referencia inicialmente en estado pending.

Un tenant no puede referirse a sí mismo.

Prevención de atribuciones duplicadas.

El registro por sí solo no genera recompensa.

La referencia califica únicamente con el primer pago exitoso.

Límite promocional mensual

Máximo de 5 recompensas para el referidor por mes calendario.

Máximo mensual actual de $250 MXN.

El periodo se determina por la fecha del primer pago exitoso del
referido.

Conteo de referencias calificadas durante el periodo.

Reinicio lógico al comenzar un nuevo mes.

Registro de referencias que generaron recompensa.

Registro de referencias que alcanzaron el límite mensual.

El sexto referido y posteriores no generan crédito adicional
para el referidor.

El límite del referidor no elimina el beneficio propio del
referido.

Beneficio del referido

Descuento único de $50 MXN.

Aplicación sobre el primer pago elegible.

Plan mensual: $600 MXN → $550 MXN.

Plan anual: $6,000 MXN → $5,950 MXN.

Beneficio independiente del límite mensual del referidor.

Prevención de doble descuento.

Crédito del referidor

Crédito de $50 MXN por referido calificado.

Modelo PromotionalCredit.

Estados available, reserved y consumed.

Crédito sin caducidad.

Aplicación automática al siguiente pago elegible.

Compatible con pago manual.

Compatible con renovación automática.

Reserva idempotente antes del intento de cobro.

Consumo únicamente después de pago exitoso.

Liberación después de pago fallido.

Liberación después de checkout cancelado.

Reutilización del crédito después de liberarlo.

Protección para evitar importes de cobro inválidos.

Trazabilidad entre crédito, referencia y pago.

Checkout y promociones

Desglose de importe bruto, descuento de referido y crédito
promocional.

Prevención de múltiples checkouts manuales pendientes para el
mismo tenant.

Reutilización idempotente del checkout pendiente.

Cambio mensual ↔ anual cancelando primero el checkout anterior.

Cancelación del PaymentIntent de Stripe al abandonar un
checkout.

Estado canceled para pagos abandonados.

Limpieza automática de checkouts expirados.

Expiración configurable de checkout manual.

Reconciliación de PaymentIntent succeeded cuando el Payment
local continúa pending.

Scheduler horario para limpieza de checkouts abandonados.

Calidad

Tests de modelos y relaciones.

Tests de generación y unicidad de códigos.

Tests de atribución.

Tests de auto-referido y atribución duplicada.

Tests de calificación por primer pago exitoso.

Tests de descuento mensual y anual.

Tests de recompensa del referidor.

Tests del límite mensual.

Tests de idempotencia.

Tests de reserva, consumo y liberación de créditos.

Tests de integración con billing.

Tests de checkout abandonado.

Tests de reconciliación con Stripe.

Tests del comando de limpieza.

Suite completa sin regresiones.

Pendiente futuro

Auditoría administrativa/comercial avanzada de promociones.

Herramientas administrativas para consultar y gestionar
referidos.

[!] Definir comportamiento comercial ante reembolsos futuros.

19. Comunicaciones

Actualmente no existe infraestructura completa de comunicaciones

externas.

Infraestructura de notificaciones.

Correo transaccional.

WhatsApp.

SMS.

Citas

Confirmación de cita.

Recordatorio de cita.

Cancelación.

Reprogramación.

Confirmación por paciente.

SaaS

Bienvenida.

Trial próximo a vencer.

Trial vencido.

Pago próximo.

Pago exitoso.

Pago fallido.

Reintento de pago.

Cuenta suspendida.

Cuenta reactivada.

Cancelación de suscripción.

Decisiones

[!] Definir proveedor de correo.

[!] Definir proveedor de WhatsApp.

[!] Definir proveedor de SMS.

20. Diseño y experiencia visual

Esta área debe tratarse como una línea formal de desarrollo y no como

una

serie de retoques aislados.

La interfaz actual es funcional y consistente, pero todavía transmite

claramente la estructura visual de una aplicación Laravel/Livewire.

El objetivo no es solamente "hacerla bonita".

El objetivo es crear una identidad propia de DocTotal y optimizar cada

pantalla para el trabajo real del médico.

DocTotal Design System

Identidad visual definitiva.

Paleta de colores.

[~] Tipografía.

Escala tipográfica.

Sistema de espaciado.

Sistema de tamaños.

Iconografía.

Botones.

Inputs.

Selects.

Textareas.

Checkboxes.

Radio buttons.

Cards.

Modales.

Dropdowns.

Tooltips.

Badges.

Alertas.

Toasts.

Tablas.

Empty states.

Loading states.

Skeletons.

Estados de error.

Estados de éxito.

Navegación.

Sidebar.

Header.

Breadcrumbs.

Responsive.

[~] Accesibilidad básica.

Auditoría visual actual

Inventario visual realizado.

Dashboard revisado.

Pacientes revisado.

Expediente revisado.

Agenda revisada.

Detalle de cita revisado.

Consulta en progreso revisada.

Consulta finalizada revisada.

Crear receta revisado.

Receta final revisada.

Configuración revisada.

Login revisado.

Registro revisado.

Onboarding completo revisado.

Problemas detectados

Existe consistencia visual básica.

La interfaz es limpia.

La interfaz es funcional.

[!] Exceso de cards visualmente similares.

[!] Jerarquía visual limitada.

[!] Escasa diferenciación entre módulos.

[!] Demasiada dependencia del patrón visual de formulario.

Sidebar rediseñado con identidad propia de DocTotal.

Identidad visual propia de DocTotal incorporada.

[!] El espacio disponible no siempre se aprovecha correctamente.

[!] Acciones clínicas importantes podrían destacar mejor.

[!] El producto todavía transmite sensación de aplicación en

desarrollo.

[!] Consulta clínica parece formulario y no workspace médico.

[!] Expediente todavía no comunica suficientemente la historia del

paciente.

21. Rediseño por módulo

No iniciar rediseños aislados antes de definir las bases del Design

System.

Login

Rediseñar login.

Incorporar identidad DocTotal.

Mejorar percepción de confianza.

Mejorar estados de error.

Registro

Rediseñar registro.

Mejorar presentación del trial.

Incorporar código de referido.

Mejorar explicación de creación del consultorio.

Onboarding

Rediseñar wizard.

Mejorar indicador de progreso.

Reducir sensación de formulario administrativo.

Mejorar selección de horarios.

Mejorar pantalla final.

Dashboard

Rediseñar jerarquía.

Priorizar operación del día.

Mejorar agenda del día.

Mejorar indicadores.

Incorporar alertas.

Incorporar estado de cuenta/trial cuando corresponda.

Pacientes

Mejorar listado.

Mejorar búsqueda.

Mejorar acciones rápidas.

Mejorar lectura de información importante.

Expediente

Rediseñar como expediente clínico longitudinal.

Resumen del paciente.

Alertas.

Antecedentes.

Timeline.

Consultas.

Diagnósticos.

Medicamentos.

Recetas.

Archivos.

Agenda

Mejorar calendario.

Mejorar representación de citas.

Diferenciar estados.

Mejorar acciones rápidas.

Optimizar vista diaria.

Optimizar vista semanal.

Mejorar navegación temporal.

Consulta

Convertir en workspace clínico.

Mejor distribución de información.

Reducir navegación innecesaria.

Mostrar contexto clínico del paciente.

Mostrar alergias relevantes.

Mostrar medicamentos actuales.

Mostrar diagnósticos importantes.

Mejorar captura SOAP.

Mejorar diagnósticos.

Mejorar recetas.

Preparar autosave.

Recetas

Mejorar captura.

Mejorar búsqueda de medicamentos.

Mejorar visualización.

Mejorar documento final.

Facilitar repetir tratamientos.

Configuración

Dividir por secciones.

Perfil.

Consultorio.

Agenda.

Documentos.

Cuenta.

Seguridad.

Suscripción y pagos.

Referidos.

22. Auditoría, privacidad y seguridad

Auditoría de acciones sensibles.

Historial de cambios clínicos.

Historial de cambios de citas.

Historial de cambios de recetas.

Historial de suscripción.

Historial de pagos.

Eventos administrativos.

Protección base de archivos clínicos.

Revisión de autorización.

Revisión de validaciones.

Rate limiting.

Política de contraseñas.

Auditoría de 2FA/passkeys.

Backups.

Restauración.

Logs de producción.

Monitoreo de errores.

Protección de información sensible.

Política de retención.

Política de eliminación.

[!] Revisión integral antes de manejar información real de

pacientes.

23. Operación interna de DocTotal

DocTotal también necesita herramientas para administrarse como negocio.

Actualmente no existe panel administrativo interno.

Tenants

Panel administrativo.

Listado de tenants.

Buscar tenant.

Ver tenant.

Estado.

Fecha de alta.

Trial.

Suscripción.

Último pago.

Próximo pago.

Estado de almacenamiento.

Operación SaaS

Trials activos.

Trials próximos a vencer.

Trials vencidos.

Suscripciones activas.

Suscripciones vencidas.

Suscripciones canceladas.

Tenants suspendidos.

Pagos.

Pagos fallidos.

Referidos.

Promociones.

Métricas

Total de tenants.

Tenants activos.

Altas.

Cancelaciones.

Conversión trial → pago.

MRR.

ARR.

Churn.

Uso de almacenamiento.

Soporte

Herramientas administrativas.

Auditoría de acciones administrativas.

Soporte de cuentas.

[!] El panel administrativo nunca debe permitir romper

accidentalmente

      el aislamiento entre tenants.

24. Infraestructura y operación técnica

Existe infraestructura base de Laravel para cache/jobs y DT-12 incorporó

scheduler operativo para billing. Todavía falta definir la operación
completa

de producción.

Tabla de jobs.

Tabla de cache.

Configurar queue de producción.

[~] Procesos SaaS de billing implementados mediante comandos
programados.

Scheduler para procesos SaaS de billing.

Procesamiento de renovaciones.

Procesamiento de reintentos de pago.

Procesamiento de cancelaciones programadas.

Procesamiento de suspensiones por grace period vencido.

Limpieza automática de checkouts manuales abandonados.

Reconciliación segura de checkouts manuales ya cobrados en
Stripe.

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

Baseline actual

Cierre DT-10:

393 tests verdes.

1179 assertions.

0 failures.

Cierre DT-11:

445 tests verdes.

0 failures.

Cierre DT-12:

696 tests verdes.

0 failures.

Cierre DT-13:

797 tests verdes.

2244 assertions.

0 failures.

Cierre DT-14:

814 tests verdes.

2339 assertions.

0 failures.

Cierre DT-15:

837 tests verdes.

2395 assertions.

0 failures.

Cierre DT-16:

837 tests verdes.

0 failures.

Cobertura existente

Tests de autenticación.

Tests de registro.

Tests de trial.

Tests de multi-tenancy.

Tests de resolución de tenant.

Tests de modelos base.

Tests de onboarding.

Tests de código postal.

Tests de pacientes.

Tests de antecedentes.

Tests de contactos de emergencia.

Tests de agenda.

Tests de appointments.

Tests de disponibilidad.

Tests de slots.

Tests de estados.

Tests de reprogramación.

Tests Appointment → Consultation.

Tests de Consultation.

Tests de lifecycle clínico.

Tests de diagnósticos.

Tests del catálogo diagnóstico.

Tests de autocomplete diagnóstico.

Tests de recetas.

Tests de medicamentos.

Tests de autocomplete de medicamentos.

Tests de PDF.

Tests del dashboard.

Tests de suscripciones.

Tests de pagos.

Tests de recuperación de pagos.

Tests de cancelación y reanudación.

Tests del ciclo de vida comercial y acceso del tenant.

Tests de comandos programados de billing.

Tests de referidos.

Tests de promociones.

Tests de créditos promocionales.

Tests de checkout manual y abandono.

Tests de reconciliación de pagos manuales.

Tests de línea de tiempo clínica longitudinal.

Tests de diagnósticos históricos consolidados.

Tests de tratamientos históricos consolidados.

Tests de integración del expediente longitudinal en la vista del
paciente.

Pendiente futuro

Baseline actualizado al cierre de DT-16.

Tests de archivos.

Tests de almacenamiento.

Tests de webhooks.

Tests de notificaciones.

Tests de seguridad adicionales.

26. Estado de los DT

DT-1 --- Base multi-tenant database structure

Estado: Completado.

Base multi-tenant.

Tenant.

Relaciones iniciales.

DT-2 --- Core Eloquent models and relationships

Estado: Completado.

Modelos principales.

Relaciones.

DT-3 --- Tenant isolation and request resolution

Estado: Completado.

TenantContext.

TenantScope.

Middleware.

Aislamiento.

DT-4 --- Patient clinical record foundation

Estado: Completado.

Pacientes.

Base clínica.

Consultas iniciales.

DT-5 --- Authentication, registration, dashboard and trial

Estado: Completado.

Registro.

Login.

Dashboard.

Trial.

DT-6 --- Onboarding wizard and postal code autocomplete

Estado: Completado.

Onboarding.

Perfil médico.

Consultorio.

Horarios.

Código postal.

DT-7 --- Gestión de pacientes

Estado: Completado.

Pacientes.

Expediente.

Antecedentes.

Historial.

DT-8 --- Agenda, citas y ciclo operativo

Estado: Completado.

Agenda.

Citas.

Availability.

Estados.

Appointment → Consultation.

Baseline al cierre:

366 tests verdes.

DT-9 --- Consultation workflow and clinical lifecycle

Estado: Completado.

Consultation persistente.

Draft.

Completed.

Lifecycle.

SOAP.

Diagnósticos.

Catálogo diagnóstico.

Recetas.

Catálogo de medicamentos.

PDF.

DT-10 --- Product inventory and development roadmap

Estado: Completado.

Objetivo:

Crear un mapa maestro del producto que permita conocer qué existe, qué

está

incompleto, qué falta y qué debe desarrollarse después.

Incluye:

Inventario funcional.

Inventario clínico.

Inventario de pacientes.

Inventario de agenda.

Inventario de consultas.

Inventario de diagnósticos.

Inventario de medicamentos.

Inventario de recetas.

Inventario de archivos pendientes.

Inventario SaaS.

Inventario de trial.

Inventario de billing.

Inventario de lifecycle del tenant.

Definición inicial de referidos/promociones.

Inventario visual.

Auditoría de pantallas existentes.

Separación de los tres pilares del producto.

Validación final contra app/.

Validación final contra migraciones.

Validación final contra tests.

Baseline actualizado.

Baseline al cierre:

393 tests verdes.

1179 assertions.

0 failures.

DT-11 --- Subscription lifecycle foundation

Estado: Completado.

Objetivo:

Construir la foundation de suscripciones de DocTotal y definir el ciclo

comercial base antes de integrar pagos reales.

Incluye:

Modelo Subscription.

UUID y soft deletes.

Relación Tenant → subscriptions.

Protección multi-tenant.

Ciclos mensual y anual.

Estados active, past_due y cancelled.

Billing anchor basado en fecha y hora del primer pago.

Renovación mensual y anual sin billing drift.

Manejo de fin de mes.

Manejo de años bisiestos.

Cancelación programada al final del periodo.

Procesamiento de expiración del periodo.

Transiciones de estado.

Reglas centralizadas de acceso del tenant.

Acceso durante recuperación past_due.

Suspensión del Tenant separada del estado de Subscription.

Cobertura automatizada.

Baseline al cierre:

445 tests verdes.

0 failures.

DT-12 --- Payments, billing recovery and automatic account

lifecycle

Estado: Completado.

Objetivo:

Convertir la foundation de Subscription de DT-11 en un sistema de
billing SaaS

capaz de cobrar, renovar, recuperar pagos fallidos, suspender y
reactivar tenants

con mínima intervención manual.

Incluye:

Stripe como proveedor de pagos.

Precios mensual ($600 MXN) y anual ($6,000 MXN).

Modelo Payment y lifecycle pending / succeeded / failed.

Stripe Customer y métodos de pago guardados.

SetupIntent para alta/actualización de tarjeta.

Checkout manual para conversión a suscripción.

Ciclos mensual y anual.

Cambio mensual ↔ anual diferido al siguiente periodo.

Renovaciones automáticas.

Recuperación past_due.

Reintentos programados con idempotencia por episodio de
recuperación.

Grace period de 7 días.

Suspensión automática al vencer el grace period.

Reactivación después del pago.

Recuperación manual con tarjeta guardada o tarjeta alternativa.

Cancelación programada al final del periodo.

Reanudación antes del vencimiento.

Protección contra renovación/cobro cuando existe cancelación
programada.

Historial de pagos y estado de suscripción en UI.

Scheduler para renovaciones, retries, cancelaciones y grace
periods vencidos.

Feature flag BILLING_AUTOMATIC_CHARGING_ENABLED.

Hardening de estados límite, multi-tenancy e idempotencia.

Cobertura automatizada de billing y lifecycle.

Pendiente fuera del alcance de DT-12:

Webhooks Stripe.

Recibos/comprobantes.

Facturación fiscal.

Auditoría formal de eventos de pago.

Activación operativa de cobros automáticos en el entorno
objetivo.

Nota operativa:

Mantener BILLING_AUTOMATIC_CHARGING_ENABLED=false hasta realizar una

activación controlada de cobros automáticos en el entorno
correspondiente.

Baseline al cierre:

696 tests verdes.

0 failures.

DT-13 --- Referral program and promotional credits

Estado: Completado.

Objetivo:

Implementar el programa de referidos de DocTotal y los créditos
promocionales,

integrándolos de forma segura con el lifecycle comercial y de billing.

Incluye:

Código único y permanente por tenant.

Enlace de referido.

Captura y validación durante registro.

Prevención de auto-referidos y atribuciones duplicadas.

Modelo Referral.

Calificación mediante primer pago exitoso.

Descuento único de $50 MXN para el referido.

Crédito de $50 MXN para el referidor.

Máximo de 5 recompensas / $250 MXN por mes calendario.

Modelo PromotionalCredit.

Estados available, reserved y consumed.

Reserva, consumo y liberación idempotentes.

Integración con pagos manuales.

Integración con renovaciones automáticas.

Integración con recuperación de pagos.

Desglose promocional visible en Billing.

Prevención de checkouts manuales pendientes duplicados.

Cambio de plan con cancelación segura del checkout anterior.

Limpieza automática de checkouts abandonados.

Reconciliación segura cuando Stripe ya reporta un PaymentIntent
como succeeded.

Scheduler horario para mantenimiento de checkouts.

Cobertura automatizada completa.

Baseline al cierre:

797 tests verdes.

2244 assertions.

0 failures.

DT-14 --- Expediente clínico longitudinal

Estado: Completado.

Objetivo:

Convertir la información clínica existente del paciente en una historia
longitudinal coherente, reutilizando las entidades clínicas ya
existentes y evitando estructuras duplicadas.

Incluye:

Auditoría previa de modelos, migraciones, vistas y tests clínicos
existentes.

Evolución de patients.show como expediente longitudinal principal.

Resumen clínico basado en PatientMedicalHistory.

Línea de tiempo clínica unificada.

Solo consultas completed dentro del historial oficial.

Consultas draft mantenidas fuera de la historia clínica final.

Diagnósticos en contexto de consulta.

Diagnósticos históricos consolidados.

Prescripciones ligadas a consulta dentro del evento de consulta.

Prescripciones independientes como eventos propios.

Prevención de duplicados de recetas vinculadas.

Tratamientos históricos consolidados por esquema clínico.

Referencias a las entidades originales.

Protección multi-tenant.

Cobertura automatizada específica.

Integración sin regresiones con pacientes, consultas y recetas.

Decisiones de diseño:

No crear una tabla adicional de timeline/eventos en DT-14.

Construir la historia longitudinal como proyección de lectura sobre las
fuentes clínicas existentes.

Mantener medicamentos actuales reportados en antecedentes separados de
los tratamientos históricos prescritos.

Mantener cada ocurrencia original visible en la línea de tiempo aunque
los resúmenes consoliden información.

Baseline al cierre:

814 tests verdes.

2339 assertions.

0 failures.

DT-15 --- Clinical files and medical documents

Estado: Completado.

Objetivo:

Incorporar archivos y documentos clínicos al expediente del paciente
sobre una base segura, multi-tenant y extensible, sin duplicar
responsabilidades del expediente longitudinal construido en DT-14.

Incluye:

Auditoría de uploads, filesystem, storage y rutas existentes.

Modelo ClinicalDocument.

Migración y relaciones con Patient y Consultation.

Asociación obligatoria con paciente.

Asociación opcional con consulta del mismo paciente.

Categorías general, laboratorio, imagen y otros.

Storage privado configurable mediante CLINICAL_DOCUMENTS_DISK.

Metadata separada del archivo físico.

Upload seguro.

Límite de 10 MB por archivo.

PDF, JPG, JPEG, PNG y WebP.

Visualización inline protegida.

Miniaturas protegidas para imágenes.

Descarga segura.

Eliminación controlada.

Integración en el expediente del paciente.

Protección multi-tenant.

Hardening de StoreClinicalDocument.

Corrección de prioridad de middleware para resolver tenant antes del
route model binding.

Cobertura automatizada específica.

Validación manual de upload, miniatura, visualización, descarga y
eliminación.

Fuera de alcance / pendiente posterior:

Límites totales de almacenamiento por tenant.

OCR/extracción.

DICOM/PACS.

Interpretación o estructura avanzada de laboratorios.

Thumbnails derivados de PDF.

Política definitiva de retención y respaldo.

Baseline al cierre:

837 tests verdes.

2395 assertions.

0 failures.

DT-16 --- Visual redesign / DocTotal UI

Estado: Completado.

Objetivo:

Transformar la interfaz funcional de DocTotal en una experiencia visual
propia, moderna, profesional, consistente y responsive, preservando los
workflows funcionales y la lógica de negocio existente.

Incluye:

Foundation visual y design tokens.

Identidad y branding de DocTotal.

Shell principal, sidebar, header y navegación responsive.

Rediseño visual de pacientes y expediente longitudinal.

Rediseño visual de citas, consultas y recetas.

Rediseño visual de onboarding y configuración.

Rediseño de login y registro.

Recuperación y restablecimiento de contraseña con UI propia.

Correo personalizado de restablecimiento de contraseña.

Landing pública de DocTotal.

Branding, logo y favicons.

Revisión de receta imprimible y PDF.

Flash messages integrados visualmente.

Auditoría visual final.

Suite completa sin regresiones.

Baseline al cierre:

837 tests verdes.

0 failures.

27. Candidatos para siguientes DT

Los números definitivos deben asignarse al momento de seleccionar el
siguiente

bloque.

DT-16 quedó completado.

El siguiente bloque debe seleccionarse después del cierre documental y
del commit final de DT-16.

Candidato A --- Clinical workspace

Objetivo:

Transformar el expediente y la consulta en una experiencia clínica
realmente

optimizada.

Construir sobre la foundation longitudinal ya terminada en DT-14:

Patient

→ Alerts

→ Consultation workspace

→ Contexto clínico visible durante consulta

→ Autosave

→ Diagnósticos

→ Prescripciones

→ Files

28. Deuda y decisiones de producto

Estas decisiones no necesariamente requieren un DT inmediato, pero deben

resolverse antes de los módulos que dependan de ellas.

Comercial

Precio mensual: $600 MXN.

Precio anual: $6,000 MXN.

Descuento anual: 2 meses equivalentes sin costo frente al
mensual.

[!] Duración definitiva del trial.

Periodo de gracia de billing: 7 días.

Política base de cancelación: al final del periodo; reversible
antes del vencimiento.

[!] Política de reembolso.

Referidos

Beneficio para tenant nuevo: $50 MXN de descuento en el primer
pago.

Beneficio para referente: $50 MXN de crédito.

Número de promociones mensuales: máximo 5 recompensas.

Crédito máximo generado por mes: $250 MXN.

Requisito de primer pago exitoso.

Crédito promocional sin caducidad.

Aplicación a mensualidad.

Aplicación a anualidad.

[!] Definir comportamiento ante reembolsos futuros.

Infraestructura

Proveedor de pagos: Stripe.

[~] Storage privado local implementado; proveedor externo
pendiente de decisión operativa.

[!] Proveedor de correo.

[!] Proveedor de WhatsApp.

[!] Proveedor de SMS.

Clínica

[!] Estructura de problemas activos.

[!] Política de modificación de información clínica finalizada.

[~] Política técnica base de archivos implementada en DT-15;
retención y cuotas pendientes.

[!] Retención de expedientes.

[!] Requisitos legales de recetas/documentos.

UX

Identidad visual definitiva.

Dirección visual del Design System.

[!] Estructura definitiva del workspace clínico.

29. Regla para decidir el siguiente DT

Al terminar cada DT:

Ejecutar suite completa de tests.

Confirmar que todos los tests estén verdes.

Registrar número de tests.

Registrar número de assertions.

Actualizar ROADMAP.md.

Actualizar TODO.md.

Marcar funcionalidades terminadas.

Registrar nuevas necesidades descubiertas.

Revisar pendientes críticos.

Revisar dependencias entre módulos.

Elegir el siguiente DT.

Definir objetivo.

Definir alcance.

Definir explícitamente qué queda fuera.

Crear branch.

Comenzar implementación.

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

31. Principio de desarrollo

No desarrollar una función solamente porque "hace falta".

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

DocTotal debe crecer como un producto integrado, no como una colección

de

pantallas y funciones independientes.

32. Visión de producto

El objetivo final no es construir solamente un sistema de expedientes

médicos.

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

Todo esto debe presentarse mediante una experiencia visual propia,

profesional, agradable y diseñada específicamente para el trabajo

médico.