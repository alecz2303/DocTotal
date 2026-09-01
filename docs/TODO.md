# DocTotal --- TODO

## Progreso general

**74% completado**

`███████████████░░░░░` 74%

> El porcentaje representa avance global del producto, no cobertura de

tests.

> Baseline anterior normalizado en DT-18: 72%.
>
> Baseline actual al cierre técnico de DT-19: 74%.
>
> Suite completa actual: 854 tests verdes / 0 failures.
>
> El porcentaje representa avance global ponderado del producto y no cobertura de tests.

Este documento representa el estado funcional actual de DocTotal y sirve

como

mapa maestro para decidir los siguientes bloques de desarrollo (DT).

No sustituye al Roadmap.

-   `ROADMAP.md` registra los bloques DT realizados, su objetivo y su

cierre.

-   `TODO.md` registra qué existe actualmente, qué está incompleto, qué

falta y

  qué decisiones de producto siguen pendientes.

Este documento debe actualizarse al finalizar cada DT antes de

seleccionar

el siguiente bloque de desarrollo.

# Objetivo del producto

DocTotal debe convertirse en una plataforma SaaS para médicos y

consultorios

que cubra tres pilares principales.

## 1. Operación médica

Permitir administrar la operación diaria y clínica del consultorio:

-   Pacientes.

-   Expedientes clínicos.

-   Agenda.

-   Citas.

-   Consultas.

-   Diagnósticos.

-   Recetas.

-   Archivos clínicos.

-   Estudios.

-   Laboratorios.

-   Imágenes médicas.

-   Historial longitudinal del paciente.

## 2. Autoadministración SaaS

DocTotal debe administrar automáticamente el ciclo comercial y operativo

de

cada tenant:

-   Registro.

-   Periodo de prueba.

-   Suscripción.

-   Mensualidades.

-   Anualidades.

-   Pagos.

-   Renovaciones.

-   Vencimientos.

-   Pagos fallidos.

-   Suspensiones.

-   Reactivaciones.

-   Cancelaciones.

-   Eliminación programada.

-   Referidos.

-   Promociones.

El objetivo es minimizar al máximo la intervención manual del

administrador

de DocTotal.

## 3. Experiencia de usuario

DocTotal debe sentirse como un producto médico profesional y terminado,

no como una aplicación construida directamente con componentes estándar

de Laravel/Livewire.

Debe ser:

-   Visualmente agradable.

-   Rápido.

-   Claro.

-   Consistente.

-   Fácil de aprender.

-   Cómodo durante toda la jornada.

-   Optimizado para el flujo real de trabajo del médico.

-   Visualmente identificable como DocTotal.

# Estados

Usar los siguientes estados:

-   `[x]` Implementado.

-   `[~]` Implementado parcialmente / requiere mejora.

-   `[ ]` No implementado.

-   `[!]` Requiere revisión o decisión de producto.

# 1. Arquitectura y multi-tenancy

Relacionado principalmente con DT-1, DT-2 y DT-3.

-   [x] Estructura base multi-tenant.

-   [x] Tenant como unidad principal de aislamiento.

-   [x] Modelo `Tenant`.

-   [x] Modelos Eloquent principales.

-   [x] Relaciones base.

-   [x] `TenantContext`.

-   [x] `TenantScope`.

-   [x] Trait `BelongsToTenant`.

-   [x] Middleware `ResolveTenant`.

-   [x] Resolución del tenant activo.

-   [x] Protección contra acceso cruzado.

-   [x] Aislamiento de pacientes.

-   [x] Aislamiento de citas.

-   [x] Aislamiento de consultas.

-   [x] Aislamiento de recetas.

-   [x] Aislamiento de archivos clínicos.

-   [x] Aislamiento de problemas clínicos.

-   [x] Cobertura automatizada de tenant isolation.

-   \[!\] Mantener aislamiento como requisito obligatorio para todo

módulo nuevo.

# 2. Autenticación y seguridad de cuenta

Relacionado principalmente con DT-5.

## Registro y autenticación

-   [x] Registro de usuario.

-   [x] Creación automática del tenant.

-   [x] Asociación usuario → tenant.

-   [x] Inicio de sesión.

-   [x] Cierre de sesión.

-   [x] Infraestructura Laravel Fortify.

-   [x] Trial creado durante el registro.

-   [x] Pantalla de registro.

-   [x] Pantalla de login.

## Contraseña y recuperación

-   [x] Backend para reset de contraseña mediante Fortify.

-   [x] Backend para actualización de contraseña.

-   \[\~\] Recuperación de contraseña --- infraestructura existente;

revisar flujo/UI.

-   \[\~\] Cambio de contraseña --- infraestructura existente; revisar
    UI

desde

      configuración.

## Seguridad adicional

-   \[\~\] Two-factor authentication --- infraestructura de base de
    datos

existente.

-   \[\~\] Passkeys --- infraestructura de base de datos existente.

-   [ ] Verificar implementación completa de 2FA.

-   [ ] Verificar implementación completa de passkeys.

-   [ ] Verificación de correo.

-   [ ] Administración visible de sesiones.

-   [ ] Revocación de sesiones/dispositivos.

-   \[!\] Auditar seguridad completa antes de producción.

# 3. Onboarding

Relacionado principalmente con DT-6.

-   [x] Wizard de onboarding.

-   [x] Paso 1 --- Datos profesionales.

-   [x] Paso 2 --- Datos del consultorio.

-   [x] Paso 3 --- Horarios de atención.

-   [x] Paso 4 --- Confirmación.

-   [x] Especialidad.

-   [x] Cédula profesional.

-   [x] Datos de contacto.

-   [x] Dirección.

-   [x] Código postal.

-   [x] Servicio de código postal.

-   [x] Autocompletado por código postal.

-   [x] Horarios de atención.

-   [x] Duración predeterminada de citas.

-   [x] `onboarding_completed_at`.

-   [x] Middleware `EnsureOnboardingIsComplete`.

-   [x] Tests del wizard.

-   [x] Tests del middleware.

-   [x] Experiencia visual del onboarding.

-   [ ] Mostrar claramente información del periodo de prueba.

-   [x] Registro preparado para promociones y referidos.

-   [x] Captura opcional de código de referido durante el alta inicial.

-   [x] Aplicación automática de código mediante enlace de referido.

-   \[!\] Revisar qué información deberá ser obligatoria antes de

producción.

# 4. Pacientes

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

\[!\] Evaluar detección de pacientes duplicados.

# 5. Expediente clínico

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

\[\~\] Mejorar estructura clínica de antecedentes.

Hospitalizaciones previas.

Problemas activos.

Alertas clínicas.

Dominios especializados para resultados estructurados de laboratorio.

Integración especializada para imágenes médicas/DICOM/PACS.

OCR o extracción estructurada de documentos.

\[!\] Definir límites totales de almacenamiento por tenant.

\[!\] Definir estrategia de respaldo y retención.

\[!\] Definir política de conservación documental.

# 6. Agenda

Relacionado principalmente con DT-8.

-   [x] Modelo `Appointment`.

-   [x] Vista mensual.

-   [x] Vista semanal.

-   [x] Vista diaria.

-   [x] Navegación entre periodos.

-   [x] Crear cita.

-   [x] Crear paciente desde cita.

-   [x] Buscar paciente.

-   [x] Disponibilidad basada en horarios.

-   [x] `AppointmentAvailabilityService`.

-   [x] Excepciones de horario.

-   [x] Bloqueos parciales.

-   [x] Bloqueos completos.

-   [x] Horarios extraordinarios.

-   [x] Prevención de solapamientos.

-   [x] Eliminación de slots pasados.

-   [x] Filtrado por estado.

-   [x] Búsqueda desde agenda.

-   [x] Tests de disponibilidad.

-   [x] Tests de creación.

-   [x] Tests de edición.

-   [x] Tests de reprogramación.

-   [x] Tests de slots.

-   [x] Tests de vistas de agenda.

-   \[\~\] Experiencia visual del calendario.

-   [ ] Mejorar diferenciación visual de estados.

-   [ ] Mejorar densidad de información.

-   [ ] Optimizar operación rápida desde agenda.

-   \[!\] Evaluar acciones mediante popover/modal sin abandonar

calendario.

# 7. Ciclo de citas

Relacionado principalmente con DT-8.

## Estados actuales

-   [x] `scheduled`.

-   [x] `confirmed`.

-   [x] `checked_in`.

-   [x] `in_progress`.

-   [x] `completed`.

-   [x] `cancelled`.

-   [x] `no_show`.

## Operaciones

-   [x] Programar.

-   [x] Confirmar.

-   [x] Check-in.

-   [x] Iniciar consulta.

-   [x] Completar.

-   [x] Cancelar.

-   [x] Reprogramar.

-   [x] Editar motivo.

-   [x] Editar notas.

-   [x] Continuar consulta en progreso.

-   [x] Integración Appointment → Consultation.

## No-show

-   [x] Periodo de gracia de 15 minutos.

-   [x] Regla temporal basada en `ends_at + 15 minutos`.

-   [x] No-show nunca completamente automático.

-   [x] Confirmación explícita por usuario.

## Pendiente

-   [ ] Recordatorios de citas.

-   [ ] Confirmación externa por paciente.

-   [ ] WhatsApp.

-   [ ] SMS.

-   [ ] Correo.

-   \[!\] Definir estrategia de comunicación con pacientes.

# 8. Consultas

Relacionado principalmente con DT-9.

-   [x] Modelo `Consultation`.

-   [x] Consultation persistente.

-   [x] Estado `draft`.

-   [x] Estado `completed`.

-   [x] Creación desde Appointment.

-   [x] Una Consultation por Appointment.

-   [x] Continuar Consultation existente.

-   [x] Consulta sin cita.

-   [x] Edición mientras está en draft.

-   [x] Finalización explícita.

-   [x] Finalización Consultation → Appointment completed.

-   [x] Signos vitales.

-   [x] Peso.

-   [x] Estatura.

-   [x] Presión arterial.

-   [x] Frecuencia cardiaca.

-   [x] Frecuencia respiratoria.

-   [x] Temperatura.

-   [x] Saturación O₂.

-   [x] Motivo de consulta.

-   [x] Nota SOAP.

-   [x] Subjetivo.

-   [x] Objetivo.

-   [x] Evaluación / diagnóstico.

-   [x] Plan.

-   [x] Diagnósticos asociados.

-   [x] Diagnóstico principal.

-   [x] Recetas asociadas.

-   [x] Historial de consultas.

-   [x] Tests del modelo.

-   [x] Tests del flujo.

-   [x] Tests del lifecycle.

-   [x] Tests Appointment → Consultation.

-   \[\~\] Experiencia de captura durante consulta.

-   [x] Autosave seguro de consultas draft.

-   [x] Indicador de cambios pendientes / guardando / guardado / error.

-   [ ] Alertas clínicas visibles durante consulta.

-   [x] Antecedentes relevantes visibles durante consulta.

-   [x] Medicamentos actuales visibles durante consulta.

-   [x] Historial reciente accesible sin abandonar consulta.

-   [x] Problemas clínicos activos visibles durante consulta — DT-19.

-   [x] Problemas resueltos excluidos del contexto activo.

-   [x] Protección contra pérdida de cambios con `beforeunload`.

-   [x] Validación en español y foco automático al primer error.

-   [x] Finalización protegida y revalidada en backend.

-   [ ] Plantillas clínicas.

-   [ ] Plantillas por especialidad.

-   \[!\] Diseñar consulta como workspace clínico y no solamente como

formulario.

# 9. Diagnósticos

-   [x] Modelo `ConsultationDiagnosis`.

-   [x] Modelo `DiagnosisCatalog`.

-   [x] Catálogo diagnóstico.

-   [x] Comando `ImportDiagnosisCatalog`.

-   [x] Importación de catálogo.

-   [x] Búsqueda/autocompletado.

-   [x] Selección desde catálogo.

-   [x] Diagnósticos asociados a Consultation.

-   [x] Diagnóstico principal.

-   [x] Código diagnóstico.

-   [x] Descripción.

-   [x] Tests del catálogo.

-   [x] Tests de autocomplete.

-   [x] Tests del flujo de diagnósticos.

-   [x] Historial consolidado de diagnósticos por paciente.

-   [x] Problemas clínicos activos estructurados mediante `PatientProblem`.

-   [x] Resolución y reapertura de problemas clínicos.

-   \[!\] Definir modelo de problemas clínicos longitudinales.

# 10. Recetas y medicamentos

## Recetas

-   [x] Modelo `Prescription`.

-   [x] Modelo `PrescriptionItem`.

-   [x] Crear receta desde consulta.

-   [x] Asociar receta a Consultation.

-   [x] Medicamentos múltiples.

-   [x] Medicamento.

-   [x] Presentación.

-   [x] Dosis.

-   [x] Frecuencia.

-   [x] Duración.

-   [x] Indicaciones.

-   [x] Indicaciones generales.

-   [x] Ver receta.

-   [x] Editar receta.

-   [x] Anular receta.

-   [x] Imprimir.

-   [x] Descargar PDF.

-   [x] Datos del médico.

-   [x] Cédula profesional.

-   [x] Tests de creación.

-   [x] Tests de edición.

-   [x] Tests de cancelación.

-   [x] Tests del modelo.

-   [x] Tests de PDF.

## Catálogo de medicamentos

-   [x] Modelo `MedicationCatalog`.

-   [x] Catálogo de medicamentos.

-   [x] Comando `ImportMedicationCatalog`.

-   [x] Importación de catálogo.

-   [x] Autocompletado.

-   [x] Búsqueda por información del medicamento.

-   [x] Tests de autocomplete.

## Pendiente

-   [ ] Firma digital.

-   [ ] QR/verificación de receta.

-   [x] Historial longitudinal de medicamentos/tratamientos por
    paciente.

-   [ ] Repetir receta anterior.

-   \[!\] Revisar requisitos legales/documentales antes de producción.

# 11. Archivos clínicos

Relacionado principalmente con DT-15.

-   [x] Modelo `ClinicalDocument`.

-   [x] UUID para routing.

-   [x] Upload.

-   [x] Visualización inline protegida.

-   [x] Descarga segura.

-   [x] Eliminación controlada.

-   [x] PDF.

-   [x] Imágenes JPG/JPEG/PNG/WebP.

-   [x] Categorías general, laboratory, imaging y other.

-   [x] Asociación con paciente.

-   [x] Asociación opcional con consulta del mismo paciente.

-   [x] Metadatos clínicos/documentales.

-   [x] Fecha del estudio/documento.

-   [x] Descripción.

-   [x] Vista previa y miniaturas protegidas para imágenes.

-   [x] Seguridad multi-tenant.

-   [x] Límite actual de 10 MB por archivo.

-   [x] Storage privado configurable mediante `CLINICAL_DOCUMENTS_DISK`.

-   [x] Hardening de `StoreClinicalDocument`.

-   [x] Tests específicos de almacenamiento, aislamiento, visualización,
    descarga y eliminación.

-   [ ] Límites totales por tenant.

-   [ ] Indicador de almacenamiento utilizado.

-   [ ] Proveedor externo definitivo.

-   [ ] URLs temporales firmadas si fueran necesarias.

-   [ ] Thumbnails derivados para PDF.

-   [ ] OCR/extracción.

-   [ ] DICOM/PACS.

-   [ ] Resultados de laboratorio estructurados.

-   [!] Definir política de conservación, retención y respaldo.

# 12. Dashboard

Relacionado principalmente con DT-8.

-   [x] Dashboard funcional.

-   [x] Citas de hoy.

-   [x] Pacientes.

-   [x] Citas por atender.

-   [x] Próxima cita.

-   [x] Agenda de hoy.

-   [x] Actividad del día.

-   [x] Consultas finalizadas.

-   [x] Consultas en progreso.

-   [x] Recetas.

-   [x] Próximos 7 días.

-   [x] Estado de agenda.

-   [x] Acciones rápidas.

-   [x] Tests del dashboard.

-   \[\~\] Jerarquía visual.

-   \[\~\] Utilidad clínica/operativa de algunos indicadores.

-   [ ] Alertas importantes.

-   [ ] Trial / estado de suscripción.

-   [ ] Avisos de pago.

-   [ ] Acciones pendientes.

-   [ ] Pacientes esperando.

-   \[!\] Revisar qué información necesita realmente el médico al

comenzar el día.

# 13. Configuración

## Perfil profesional

-   [x] Nombre.

-   [x] Apellidos.

-   [x] Especialidad.

-   [x] Cédula.

-   [x] Teléfono.

-   [x] WhatsApp.

-   [x] Biografía.

-   [x] Fotografía.

-   [x] Firma.

## Consultorio

-   [x] Modelo `PracticeProfile`.

-   [x] Nombre público.

-   [x] Razón social.

-   [x] RFC.

-   [x] Logo.

-   [x] Teléfono.

-   [x] WhatsApp.

-   [x] Correo.

-   [x] Descripción.

-   [x] Dirección.

-   [x] Colonia.

-   [x] Código postal.

-   [x] Ciudad.

-   [x] Estado.

-   [x] País.

## Documentos impresos

-   [x] Configuración de impresión.

-   [x] Pie de página.

-   [x] Datos del médico en receta.

-   [x] Datos del consultorio.

## Pendiente

-   [ ] Configuración de cuenta.

-   [ ] Seguridad.

-   [ ] Cambio de contraseña desde UI.

-   [x] Suscripción.

-   [x] Facturación.

-   [x] Métodos de pago.

-   [x] Referidos.

-   [ ] Almacenamiento utilizado.

-   \[!\] Reorganizar configuración por secciones/pestañas.

# 14. Trial

Existe infraestructura inicial de trial.

## Implementado

-   [x] Campo `status`.

-   [x] Estado inicial `trial`.

-   [x] `trial_started_at`.

-   [x] `trial_ends_at`.

-   [x] Duración configurable durante registro.

-   [x] Inicialización automática durante registro.

-   [x] `Tenant::isOnTrial()`.

-   [x] `Tenant::trialHasExpired()`.

-   [x] Tests de trial.

-   [x] Tests del trial durante registro.

## Pendiente

-   [x] Enforcement de acceso integrado mediante reglas centralizadas

del Tenant.

-   [x] Trial integrado con `Tenant::hasAccessToService()`.

-   [ ] Aviso de días restantes.

-   [ ] Avisos próximos al vencimiento.

-   [ ] Pantalla de trial vencido.

-   [x] Conversión trial → suscripción.

-   [x] Selección mensual/anual.

-   [ ] Bloqueo controlado al vencer.

-   \[!\] Definir qué puede hacer el tenant después del vencimiento.

-   \[!\] Definir si existirá periodo de gracia.

# 15. Suscripciones

Relacionado principalmente con DT-11 y DT-12.

La infraestructura de suscripciones ya define el periodo de servicio,
ciclos

de facturación, estados comerciales, derecho de acceso del tenant e
integración

con billing real mediante Stripe.

## Modelo comercial

-   [x] Modelo `Subscription`.

-   [x] UUID.

-   [x] Soft deletes.

-   [x] Relación Tenant → subscriptions.

-   [x] Protección multi-tenant.

-   [x] Ciclo mensual.

-   [x] Ciclo anual.

-   [x] Estado `active`.

-   [x] Estado `past_due`.

-   [x] Estado `cancelled`.

-   [ ] Modelo `Plan` si posteriormente se requieren varios planes.

-   [x] Precio mensual definido: \$600 MXN.

-   [x] Precio anual definido: \$6,000 MXN.

-   [x] Moneda comercial almacenada por suscripción.

## Periodo de servicio

-   [x] `starts_at`.

-   [x] `current_period_starts_at`.

-   [x] `current_period_ends_at`.

-   [x] `next_billing_at`.

-   [x] `cancel_at_period_end`.

-   [x] `cancelled_at`.

-   [x] Primer pago como billing anchor.

-   [x] Conservación de fecha y hora del primer pago.

-   [x] Conservación de minutos y segundos.

-   [x] Manejo de fin de mes sin overflow.

-   [x] Manejo de años bisiestos.

-   [x] Protección contra billing drift en renovaciones.

## Operaciones

-   [x] Alta de suscripción.

-   [x] Activación del tenant al crear suscripción.

-   [x] Renovación mensual.

-   [x] Renovación anual.

-   [x] Cancelación programada al final del periodo.

-   [x] Reanudación antes del vencimiento.

-   [x] Procesamiento al final exacto del periodo.

-   [x] Transición `active → past_due`.

-   [x] Transición `past_due → active`.

-   [x] `cancelled` como estado terminal actual.

-   [x] Prevención de segunda Subscription `active`.

-   [x] Prevención de segunda Subscription cuando existe `past_due`.

-   [x] Consulta de suscripción vigente del tenant.

-   [x] Derecho de acceso centralizado.

-   [x] Acceso durante `past_due` mientras el tenant no esté suspendido.

-   [x] Suspensión del Tenant independiente del estado de Subscription.

-   [x] Conversión trial → suscripción desde UI.

-   [x] Cambio mensual ↔ anual programado al siguiente periodo.

-   [x] Historial comercial visible para el usuario.

-   [ ] Upgrade/downgrade si posteriormente existen varios planes.

## Calidad

-   [x] Tests del modelo Subscription.

-   [x] Tests de activación.

-   [x] Tests de renovación.

-   [x] Tests de billing anchor.

-   [x] Tests de fin de mes.

-   [x] Tests de año bisiesto.

-   [x] Tests de cancelación programada.

-   [x] Tests de reanudación.

-   [x] Tests de transiciones.

-   [x] Tests de acceso del Tenant.

-   [x] Tests de expiración/procesamiento de periodo.

-   [x] Tests de cambio de plan.

## Decisiones resueltas / pendientes

-   [x] Proveedor de pagos definido: Stripe.

-   [x] Precio mensual definido: \$600 MXN.

-   [x] Precio anual definido: \$6,000 MXN.

-   [x] Descuento anual definido: equivalente a 2 meses sin costo frente
    al plan mensual.

-   [x] Regla base de cancelación definida: cancelación al final del
    periodo con posibilidad de conservar la suscripción antes del
    vencimiento.

-   \[!\] Definir reglas de reembolso.

# 16. Pagos y facturación SaaS

Relacionado principalmente con DT-12.

DT-12 implementó la foundation operativa de pagos y recuperación SaaS
sobre

la Subscription construida en DT-11. Stripe quedó integrado y validado
en modo

de prueba. Los cobros automáticos permanecen protegidos por feature flag
hasta

su activación controlada en el entorno objetivo.

-   [x] Modelo `Payment`.

-   [x] Registro de pagos.

-   [x] Fecha de intento, pago y fallo.

-   [x] Importe.

-   [x] Moneda.

-   [x] Método de pago Stripe.

-   [x] Estados `pending`, `succeeded`, `failed` y `canceled`.

-   [x] Referencia del proveedor.

-   [x] Pago exitoso.

-   [x] Pago pendiente.

-   [x] Pago fallido.

-   [x] Reintentos programados.

-   [x] Renovación automática implementada y protegida por feature flag.

-   [x] Vencimiento y recuperación `past_due`.

-   [x] Periodo de gracia de 7 días.

-   [x] Suspensión automática por falta de pago al vencer el grace
    period.

-   [x] Reactivación después del pago.

-   [x] Recuperación automática mediante tarjeta guardada.

-   [x] Recuperación manual mediante PaymentIntent.

-   [x] Historial de pagos visible.

-   [x] Métodos de pago guardados.

-   [x] Alta, actualización y eliminación del método de pago Stripe.

-   [x] Idempotencia de intentos de renovación, recuperación y checkout
    manual.

-   [x] Integración de descuentos y créditos promocionales con billing.

-   [x] Reserva idempotente de créditos promocionales.

-   [x] Consumo de créditos únicamente después de pago exitoso.

-   [x] Liberación de créditos después de pago fallido o checkout
    cancelado.

-   [x] Prevención de checkouts manuales pendientes duplicados.

-   [x] Cambio de plan con cancelación segura del checkout anterior.

-   [x] Limpieza automática de checkouts manuales abandonados.

-   [x] Reconciliación segura de checkouts cuyo PaymentIntent ya fue
    cobrado en Stripe.

-   [x] Scheduler para renovaciones, reintentos, cancelaciones, grace
    periods vencidos y limpieza de checkouts abandonados.

-   [x] Stripe test mode validado end-to-end.

-   [ ] Recibos/comprobantes.

-   [ ] Webhooks.

-   [ ] Idempotencia de webhooks.

-   [ ] Auditoría formal de eventos de pago.

-   \[!\] Definir facturación fiscal de DocTotal.

-   [x] Proveedor de pagos definido: Stripe.

# 17. Ciclo de vida del tenant

Actualmente existe una parte importante de la automatización comercial
base.

## Campos existentes

-   [x] `status`.

-   [x] `suspended_at`.

-   [x] `deletion_due_at`.

-   [x] `trial_started_at`.

-   [x] `trial_ends_at`.

-   [x] Soft deletes.

## Ciclo objetivo preliminar

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

## Estado actual

-   [x] Estados comerciales básicos definidos entre Tenant y
    Subscription.

-   [x] Transiciones base de billing automatizadas.

-   [x] Reglas de acceso centralizadas mediante el Tenant.

-   [x] Suspensión automática al vencer el periodo de gracia.

-   [x] Reactivación automática después de recuperar el pago.

-   [x] Cancelación programada al final del periodo.

-   [ ] Eliminación programada.

-   [ ] Recuperación antes de eliminación.

-   [x] Scheduler SaaS para billing.

-   \[\~\] Procesamiento programado implementado mediante comandos;
    queues/jobs dedicados quedan para necesidades futuras.

-   [ ] Auditoría formal de transiciones.

-   \[!\] Definir política de conservación de expedientes después de
    cancelar.

-   [x] Comportamiento de acceso en estado `past_due`: acceso mientras
    el tenant no esté suspendido.

-   [x] Comportamiento de acceso en estado `suspended`: sin acceso al
    servicio.

# 18. Referidos y promociones

Relacionado principalmente con DT-13.

DT-13 implementó el programa de referidos y la foundation de créditos

promocionales integrada con el lifecycle de billing.

## Código de referido

-   [x] Código único por tenant.

-   [x] Generación automática.

-   [x] Código permanente.

-   [x] Índice UNIQUE en base de datos.

-   [x] Backfill de códigos para tenants existentes.

-   [x] Pantalla para consultar código.

-   [x] Enlace de referido.

-   [x] Acción para copiar/compartir referencia.

-   [x] Validación del código.

-   [x] Identificación del tenant referente.

## Uso durante registro

-   [x] Un tenant nuevo puede utilizar como máximo un código de
    referido.

-   [x] Captura del código durante la inscripción inicial.

-   [x] Aplicación automática mediante parámetro `ref`.

-   [x] Validación de códigos ingresados manualmente.

-   [x] Asociación permanente entre referidor y referido.

-   [x] Referencia inicialmente en estado `pending`.

-   [x] Un tenant no puede referirse a sí mismo.

-   [x] Prevención de atribuciones duplicadas.

-   [x] El registro por sí solo no genera recompensa.

-   [x] La referencia califica únicamente con el primer pago exitoso.

## Límite promocional mensual

-   [x] Máximo de 5 recompensas para el referidor por mes calendario.

-   [x] Máximo mensual actual de \$250 MXN.

-   [x] El periodo se determina por la fecha del primer pago exitoso del
    referido.

-   [x] Conteo de referencias calificadas durante el periodo.

-   [x] Reinicio lógico al comenzar un nuevo mes.

-   [x] Registro de referencias que generaron recompensa.

-   [x] Registro de referencias que alcanzaron el límite mensual.

-   [x] El sexto referido y posteriores no generan crédito adicional
    para el referidor.

-   [x] El límite del referidor no elimina el beneficio propio del
    referido.

## Beneficio del referido

-   [x] Descuento único de \$50 MXN.

-   [x] Aplicación sobre el primer pago elegible.

-   [x] Plan mensual: \$600 MXN → \$550 MXN.

-   [x] Plan anual: \$6,000 MXN → \$5,950 MXN.

-   [x] Beneficio independiente del límite mensual del referidor.

-   [x] Prevención de doble descuento.

## Crédito del referidor

-   [x] Crédito de \$50 MXN por referido calificado.

-   [x] Modelo `PromotionalCredit`.

-   [x] Estados `available`, `reserved` y `consumed`.

-   [x] Crédito sin caducidad.

-   [x] Aplicación automática al siguiente pago elegible.

-   [x] Compatible con pago manual.

-   [x] Compatible con renovación automática.

-   [x] Reserva idempotente antes del intento de cobro.

-   [x] Consumo únicamente después de pago exitoso.

-   [x] Liberación después de pago fallido.

-   [x] Liberación después de checkout cancelado.

-   [x] Reutilización del crédito después de liberarlo.

-   [x] Protección para evitar importes de cobro inválidos.

-   [x] Trazabilidad entre crédito, referencia y pago.

## Checkout y promociones

-   [x] Desglose de importe bruto, descuento de referido y crédito
    promocional.

-   [x] Prevención de múltiples checkouts manuales pendientes para el
    mismo tenant.

-   [x] Reutilización idempotente del checkout pendiente.

-   [x] Cambio mensual ↔ anual cancelando primero el checkout anterior.

-   [x] Cancelación del PaymentIntent de Stripe al abandonar un
    checkout.

-   [x] Estado `canceled` para pagos abandonados.

-   [x] Limpieza automática de checkouts expirados.

-   [x] Expiración configurable de checkout manual.

-   [x] Reconciliación de PaymentIntent `succeeded` cuando el Payment
    local continúa `pending`.

-   [x] Scheduler horario para limpieza de checkouts abandonados.

## Calidad

-   [x] Tests de modelos y relaciones.

-   [x] Tests de generación y unicidad de códigos.

-   [x] Tests de atribución.

-   [x] Tests de auto-referido y atribución duplicada.

-   [x] Tests de calificación por primer pago exitoso.

-   [x] Tests de descuento mensual y anual.

-   [x] Tests de recompensa del referidor.

-   [x] Tests del límite mensual.

-   [x] Tests de idempotencia.

-   [x] Tests de reserva, consumo y liberación de créditos.

-   [x] Tests de integración con billing.

-   [x] Tests de checkout abandonado.

-   [x] Tests de reconciliación con Stripe.

-   [x] Tests del comando de limpieza.

-   [x] Suite completa sin regresiones.

## Pendiente futuro

-   [ ] Auditoría administrativa/comercial avanzada de promociones.

-   [ ] Herramientas administrativas para consultar y gestionar
    referidos.

-   \[!\] Definir comportamiento comercial ante reembolsos futuros.

# 19. Comunicaciones

Actualmente no existe infraestructura completa de comunicaciones

externas.

-   [ ] Infraestructura de notificaciones.

-   [ ] Correo transaccional.

-   [ ] WhatsApp.

-   [ ] SMS.

## Citas

-   [ ] Confirmación de cita.

-   [ ] Recordatorio de cita.

-   [ ] Cancelación.

-   [ ] Reprogramación.

-   [ ] Confirmación por paciente.

## SaaS

-   [ ] Bienvenida.

-   [ ] Trial próximo a vencer.

-   [ ] Trial vencido.

-   [ ] Pago próximo.

-   [ ] Pago exitoso.

-   [ ] Pago fallido.

-   [ ] Reintento de pago.

-   [ ] Cuenta suspendida.

-   [ ] Cuenta reactivada.

-   [ ] Cancelación de suscripción.

## Decisiones

-   \[!\] Definir proveedor de correo.

-   \[!\] Definir proveedor de WhatsApp.

-   \[!\] Definir proveedor de SMS.

# 20. Diseño y experiencia visual

Esta área debe tratarse como una línea formal de desarrollo y no como

una

serie de retoques aislados.

La interfaz actual es funcional y consistente, pero todavía transmite

claramente la estructura visual de una aplicación Laravel/Livewire.

El objetivo no es solamente "hacerla bonita".

El objetivo es crear una identidad propia de DocTotal y optimizar cada

pantalla para el trabajo real del médico.

## DocTotal Design System

-   [x] Identidad visual definitiva.

-   [x] Paleta de colores.

-   \[\~\] Tipografía.

-   [x] Escala tipográfica.

-   [x] Sistema de espaciado.

-   [x] Sistema de tamaños.

-   [x] Iconografía.

-   [x] Botones.

-   [x] Inputs.

-   [x] Selects.

-   [x] Textareas.

-   [ ] Checkboxes.

-   [ ] Radio buttons.

-   [x] Cards.

-   [x] Modales.

-   [ ] Dropdowns.

-   [ ] Tooltips.

-   [x] Badges.

-   [x] Alertas.

-   [x] Toasts.

-   [x] Tablas.

-   [x] Empty states.

-   [x] Loading states.

-   [ ] Skeletons.

-   [x] Estados de error.

-   [x] Estados de éxito.

-   [x] Navegación.

-   [x] Sidebar.

-   [x] Header.

-   [ ] Breadcrumbs.

-   [x] Responsive.

-   \[\~\] Accesibilidad básica.

## Auditoría visual actual

-   [x] Inventario visual realizado.

-   [x] Dashboard revisado.

-   [x] Pacientes revisado.

-   [x] Expediente revisado.

-   [x] Agenda revisada.

-   [x] Detalle de cita revisado.

-   [x] Consulta en progreso revisada.

-   [x] Consulta finalizada revisada.

-   [x] Crear receta revisado.

-   [x] Receta final revisada.

-   [x] Configuración revisada.

-   [x] Login revisado.

-   [x] Registro revisado.

-   [x] Onboarding completo revisado.

## Problemas detectados

-   [x] Existe consistencia visual básica.

-   [x] La interfaz es limpia.

-   [x] La interfaz es funcional.

-   \[!\] Exceso de cards visualmente similares.

-   \[!\] Jerarquía visual limitada.

-   \[!\] Escasa diferenciación entre módulos.

-   \[!\] Demasiada dependencia del patrón visual de formulario.

-   [x] Sidebar rediseñado con identidad propia de DocTotal.

-   [x] Identidad visual propia de DocTotal incorporada.

-   \[!\] El espacio disponible no siempre se aprovecha correctamente.

-   \[!\] Acciones clínicas importantes podrían destacar mejor.

-   \[!\] El producto todavía transmite sensación de aplicación en

desarrollo.

-   \[!\] Consulta clínica parece formulario y no workspace médico.

-   \[!\] Expediente todavía no comunica suficientemente la historia del

paciente.

# 21. Rediseño por módulo

No iniciar rediseños aislados antes de definir las bases del Design

System.

## Login

-   [x] Rediseñar login.

-   [x] Incorporar identidad DocTotal.

-   [x] Mejorar percepción de confianza.

-   [x] Mejorar estados de error.

## Registro

-   [x] Rediseñar registro.

-   [ ] Mejorar presentación del trial.

-   [x] Incorporar código de referido.

-   [x] Mejorar explicación de creación del consultorio.

## Onboarding

-   [x] Rediseñar wizard.

-   [x] Mejorar indicador de progreso.

-   [x] Reducir sensación de formulario administrativo.

-   [x] Mejorar selección de horarios.

-   [x] Mejorar pantalla final.

## Dashboard

-   [ ] Rediseñar jerarquía.

-   [ ] Priorizar operación del día.

-   [ ] Mejorar agenda del día.

-   [ ] Mejorar indicadores.

-   [ ] Incorporar alertas.

-   [ ] Incorporar estado de cuenta/trial cuando corresponda.

## Pacientes

-   [x] Mejorar listado.

-   [x] Mejorar búsqueda.

-   [x] Mejorar acciones rápidas.

-   [x] Mejorar lectura de información importante.

## Expediente

-   [x] Rediseñar como expediente clínico longitudinal.

-   [x] Resumen del paciente.

-   [x] Alertas.

-   [x] Antecedentes.

-   [x] Timeline.

-   [x] Consultas.

-   [x] Diagnósticos.

-   [x] Medicamentos.

-   [x] Recetas.

-   [x] Archivos.

## Agenda

-   [ ] Mejorar calendario.

-   [x] Mejorar representación de citas.

-   [x] Diferenciar estados.

-   [x] Mejorar acciones rápidas.

-   [ ] Optimizar vista diaria.

-   [ ] Optimizar vista semanal.

-   [x] Mejorar navegación temporal.

## Consulta

-   [ ] Convertir en workspace clínico.

-   [ ] Mejor distribución de información.

-   [ ] Reducir navegación innecesaria.

-   [ ] Mostrar contexto clínico del paciente.

-   [ ] Mostrar alergias relevantes.

-   [ ] Mostrar medicamentos actuales.

-   [ ] Mostrar diagnósticos importantes.

-   [ ] Mejorar captura SOAP.

-   [ ] Mejorar diagnósticos.

-   [ ] Mejorar recetas.

-   [ ] Preparar autosave.

## Recetas

-   [x] Mejorar captura.

-   [x] Mejorar búsqueda de medicamentos.

-   [x] Mejorar visualización.

-   [x] Mejorar documento final.

-   [ ] Facilitar repetir tratamientos.

## Configuración

-   [ ] Dividir por secciones.

-   [x] Perfil.

-   [x] Consultorio.

-   [x] Agenda.

-   [x] Documentos.

-   [ ] Cuenta.

-   [ ] Seguridad.

-   [x] Suscripción y pagos.

-   [x] Referidos.

# 22. Auditoría, privacidad y seguridad

-   [ ] Auditoría de acciones sensibles.

-   [ ] Historial de cambios clínicos.

-   [ ] Historial de cambios de citas.

-   [ ] Historial de cambios de recetas.

-   [ ] Historial de suscripción.

-   [ ] Historial de pagos.

-   [ ] Eventos administrativos.

-   [x] Protección base de archivos clínicos.

-   [ ] Revisión de autorización.

-   [ ] Revisión de validaciones.

-   [ ] Rate limiting.

-   [ ] Política de contraseñas.

-   [ ] Auditoría de 2FA/passkeys.

-   [ ] Backups.

-   [ ] Restauración.

-   [ ] Logs de producción.

-   [ ] Monitoreo de errores.

-   [ ] Protección de información sensible.

-   [ ] Política de retención.

-   [ ] Política de eliminación.

-   \[!\] Revisión integral antes de manejar información real de

pacientes.

# 23. Operación interna de DocTotal

DocTotal también necesita herramientas para administrarse como negocio.

Actualmente no existe panel administrativo interno.

## Tenants

-   [ ] Panel administrativo.

-   [ ] Listado de tenants.

-   [ ] Buscar tenant.

-   [ ] Ver tenant.

-   [ ] Estado.

-   [ ] Fecha de alta.

-   [ ] Trial.

-   [x] Suscripción.

-   [ ] Último pago.

-   [ ] Próximo pago.

-   [ ] Estado de almacenamiento.

## Operación SaaS

-   [ ] Trials activos.

-   [ ] Trials próximos a vencer.

-   [ ] Trials vencidos.

-   [ ] Suscripciones activas.

-   [ ] Suscripciones vencidas.

-   [ ] Suscripciones canceladas.

-   [ ] Tenants suspendidos.

-   [ ] Pagos.

-   [ ] Pagos fallidos.

-   [x] Referidos.

-   [ ] Promociones.

## Métricas

-   [ ] Total de tenants.

-   [ ] Tenants activos.

-   [ ] Altas.

-   [ ] Cancelaciones.

-   [ ] Conversión trial → pago.

-   [ ] MRR.

-   [ ] ARR.

-   [ ] Churn.

-   [ ] Uso de almacenamiento.

## Soporte

-   [ ] Herramientas administrativas.

-   [ ] Auditoría de acciones administrativas.

-   [ ] Soporte de cuentas.

-   \[!\] El panel administrativo nunca debe permitir romper

accidentalmente

      el aislamiento entre tenants.

# 24. Infraestructura y operación técnica

Existe infraestructura base de Laravel para cache/jobs y DT-12 incorporó

scheduler operativo para billing. Todavía falta definir la operación
completa

de producción.

-   [x] Tabla de jobs.

-   [x] Tabla de cache.

-   [ ] Configurar queue de producción.

-   \[\~\] Procesos SaaS de billing implementados mediante comandos
    programados.

-   [x] Scheduler para procesos SaaS de billing.

-   [x] Procesamiento de renovaciones.

-   [x] Procesamiento de reintentos de pago.

-   [x] Procesamiento de cancelaciones programadas.

-   [x] Procesamiento de suspensiones por grace period vencido.

-   [x] Limpieza automática de checkouts manuales abandonados.

-   [x] Reconciliación segura de checkouts manuales ya cobrados en
    Stripe.

-   [ ] Procesamiento de eliminaciones.

-   [ ] Procesamiento de notificaciones.

-   [ ] Monitoreo de queues.

-   [ ] Manejo de failed jobs.

-   [ ] Logging estructurado.

-   [ ] Error tracking.

-   [ ] Backups automáticos.

-   [ ] Monitoreo de aplicación.

-   [ ] Health checks.

-   \[!\] Definir infraestructura de producción.

# 25. Calidad y tests

La aplicación cuenta con una suite automatizada considerable.

## Baseline actual

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

Cierre DT-17:

840 tests verdes.

0 failures.

DT-18:

Normalización documental integrada en master.

Baseline heredado: 840 tests verdes / 0 failures.

Cierre técnico DT-19:

854 tests verdes.

0 failures.

## Cobertura existente

-   [x] Tests de autenticación.

-   [x] Tests de registro.

-   [x] Tests de trial.

-   [x] Tests de multi-tenancy.

-   [x] Tests de resolución de tenant.

-   [x] Tests de modelos base.

-   [x] Tests de onboarding.

-   [x] Tests de código postal.

-   [x] Tests de pacientes.

-   [x] Tests de antecedentes.

-   [x] Tests de contactos de emergencia.

-   [x] Tests de agenda.

-   [x] Tests de appointments.

-   [x] Tests de disponibilidad.

-   [x] Tests de slots.

-   [x] Tests de estados.

-   [x] Tests de reprogramación.

-   [x] Tests Appointment → Consultation.

-   [x] Tests de Consultation.

-   [x] Tests de lifecycle clínico.

-   [x] Tests de diagnósticos.

-   [x] Tests del catálogo diagnóstico.

-   [x] Tests de autocomplete diagnóstico.

-   [x] Tests de recetas.

-   [x] Tests de medicamentos.

-   [x] Tests de autocomplete de medicamentos.

-   [x] Tests de PDF.

-   [x] Tests del dashboard.

-   [x] Tests de suscripciones.

-   [x] Tests de pagos.

-   [x] Tests de recuperación de pagos.

-   [x] Tests de cancelación y reanudación.

-   [x] Tests del ciclo de vida comercial y acceso del tenant.

-   [x] Tests de comandos programados de billing.

-   [x] Tests de referidos.

-   [x] Tests de promociones.

-   [x] Tests de créditos promocionales.

-   [x] Tests de checkout manual y abandono.

-   [x] Tests de reconciliación de pagos manuales.

Tests de línea de tiempo clínica longitudinal.

Tests de diagnósticos históricos consolidados.

Tests de tratamientos históricos consolidados.

Tests de integración del expediente longitudinal en la vista del
paciente.

## Pendiente futuro

-   [x] Baseline actualizado al cierre de DT-16.

-   [x] Tests de archivos.

-   [x] Tests de almacenamiento.

-   [x] Tests del workspace clínico y autosave.

-   [x] Tests de problemas clínicos longitudinales.

-   [x] Tests Livewire de CRUD, resolución, reapertura y soft delete.

-   [x] Tests de problemas activos dentro del contexto de consulta.

-   [ ] Tests de webhooks.

-   [ ] Tests de notificaciones.

-   [ ] Tests de seguridad adicionales.

# 26. Estado de los DT

## DT-1 --- Base multi-tenant database structure

Estado: Completado.

-   [x] Base multi-tenant.

-   [x] Tenant.

-   [x] Relaciones iniciales.

## DT-2 --- Core Eloquent models and relationships

Estado: Completado.

-   [x] Modelos principales.

-   [x] Relaciones.

## DT-3 --- Tenant isolation and request resolution

Estado: Completado.

-   [x] TenantContext.

-   [x] TenantScope.

-   [x] Middleware.

-   [x] Aislamiento.

## DT-4 --- Patient clinical record foundation

Estado: Completado.

-   [x] Pacientes.

-   [x] Base clínica.

-   [x] Consultas iniciales.

## DT-5 --- Authentication, registration, dashboard and trial

Estado: Completado.

-   [x] Registro.

-   [x] Login.

-   [x] Dashboard.

-   [x] Trial.

## DT-6 --- Onboarding wizard and postal code autocomplete

Estado: Completado.

-   [x] Onboarding.

-   [x] Perfil médico.

-   [x] Consultorio.

-   [x] Horarios.

-   [x] Código postal.

## DT-7 --- Gestión de pacientes

Estado: Completado.

-   [x] Pacientes.

-   [x] Expediente.

-   [x] Antecedentes.

-   [x] Historial.

## DT-8 --- Agenda, citas y ciclo operativo

Estado: Completado.

-   [x] Agenda.

-   [x] Citas.

-   [x] Availability.

-   [x] Estados.

-   [x] Appointment → Consultation.

Baseline al cierre:

366 tests verdes.

## DT-9 --- Consultation workflow and clinical lifecycle

Estado: Completado.

-   [x] Consultation persistente.

-   [x] Draft.

-   [x] Completed.

-   [x] Lifecycle.

-   [x] SOAP.

-   [x] Diagnósticos.

-   [x] Catálogo diagnóstico.

-   [x] Recetas.

-   [x] Catálogo de medicamentos.

-   [x] PDF.

## DT-10 --- Product inventory and development roadmap

Estado: Completado.

Objetivo:

Crear un mapa maestro del producto que permita conocer qué existe, qué

está

incompleto, qué falta y qué debe desarrollarse después.

Incluye:

-   [x] Inventario funcional.

-   [x] Inventario clínico.

-   [x] Inventario de pacientes.

-   [x] Inventario de agenda.

-   [x] Inventario de consultas.

-   [x] Inventario de diagnósticos.

-   [x] Inventario de medicamentos.

-   [x] Inventario de recetas.

-   [x] Inventario de archivos pendientes.

-   [x] Inventario SaaS.

-   [x] Inventario de trial.

-   [x] Inventario de billing.

-   [x] Inventario de lifecycle del tenant.

-   [x] Definición inicial de referidos/promociones.

-   [x] Inventario visual.

-   [x] Auditoría de pantallas existentes.

-   [x] Separación de los tres pilares del producto.

-   [x] Validación final contra `app/`.

-   [x] Validación final contra migraciones.

-   [x] Validación final contra tests.

-   [x] Baseline actualizado.

Baseline al cierre:

393 tests verdes.

1179 assertions.

0 failures.

## DT-11 --- Subscription lifecycle foundation

Estado: Completado.

Objetivo:

Construir la foundation de suscripciones de DocTotal y definir el ciclo

comercial base antes de integrar pagos reales.

Incluye:

-   [x] Modelo `Subscription`.

-   [x] UUID y soft deletes.

-   [x] Relación Tenant → subscriptions.

-   [x] Protección multi-tenant.

-   [x] Ciclos mensual y anual.

-   [x] Estados `active`, `past_due` y `cancelled`.

-   [x] Billing anchor basado en fecha y hora del primer pago.

-   [x] Renovación mensual y anual sin billing drift.

-   [x] Manejo de fin de mes.

-   [x] Manejo de años bisiestos.

-   [x] Cancelación programada al final del periodo.

-   [x] Procesamiento de expiración del periodo.

-   [x] Transiciones de estado.

-   [x] Reglas centralizadas de acceso del tenant.

-   [x] Acceso durante recuperación `past_due`.

-   [x] Suspensión del Tenant separada del estado de Subscription.

-   [x] Cobertura automatizada.

Baseline al cierre:

445 tests verdes.

0 failures.

## DT-12 --- Payments, billing recovery and automatic account

lifecycle

Estado: Completado.

Objetivo:

Convertir la foundation de Subscription de DT-11 en un sistema de
billing SaaS

capaz de cobrar, renovar, recuperar pagos fallidos, suspender y
reactivar tenants

con mínima intervención manual.

Incluye:

-   [x] Stripe como proveedor de pagos.

-   [x] Precios mensual (\$600 MXN) y anual (\$6,000 MXN).

-   [x] Modelo `Payment` y lifecycle `pending` / `succeeded` / `failed`.

-   [x] Stripe Customer y métodos de pago guardados.

-   [x] SetupIntent para alta/actualización de tarjeta.

-   [x] Checkout manual para conversión a suscripción.

-   [x] Ciclos mensual y anual.

-   [x] Cambio mensual ↔ anual diferido al siguiente periodo.

-   [x] Renovaciones automáticas.

-   [x] Recuperación `past_due`.

-   [x] Reintentos programados con idempotencia por episodio de
    recuperación.

-   [x] Grace period de 7 días.

-   [x] Suspensión automática al vencer el grace period.

-   [x] Reactivación después del pago.

-   [x] Recuperación manual con tarjeta guardada o tarjeta alternativa.

-   [x] Cancelación programada al final del periodo.

-   [x] Reanudación antes del vencimiento.

-   [x] Protección contra renovación/cobro cuando existe cancelación
    programada.

-   [x] Historial de pagos y estado de suscripción en UI.

-   [x] Scheduler para renovaciones, retries, cancelaciones y grace
    periods vencidos.

-   [x] Feature flag `BILLING_AUTOMATIC_CHARGING_ENABLED`.

-   [x] Hardening de estados límite, multi-tenancy e idempotencia.

-   [x] Cobertura automatizada de billing y lifecycle.

Pendiente fuera del alcance de DT-12:

-   [ ] Webhooks Stripe.

-   [ ] Recibos/comprobantes.

-   [ ] Facturación fiscal.

-   [ ] Auditoría formal de eventos de pago.

-   [ ] Activación operativa de cobros automáticos en el entorno
    objetivo.

Nota operativa:

Mantener `BILLING_AUTOMATIC_CHARGING_ENABLED=false` hasta realizar una

activación controlada de cobros automáticos en el entorno
correspondiente.

Baseline al cierre:

696 tests verdes.

0 failures.

## DT-13 --- Referral program and promotional credits

Estado: Completado.

Objetivo:

Implementar el programa de referidos de DocTotal y los créditos
promocionales,

integrándolos de forma segura con el lifecycle comercial y de billing.

Incluye:

-   [x] Código único y permanente por tenant.

-   [x] Enlace de referido.

-   [x] Captura y validación durante registro.

-   [x] Prevención de auto-referidos y atribuciones duplicadas.

-   [x] Modelo `Referral`.

-   [x] Calificación mediante primer pago exitoso.

-   [x] Descuento único de \$50 MXN para el referido.

-   [x] Crédito de \$50 MXN para el referidor.

-   [x] Máximo de 5 recompensas / \$250 MXN por mes calendario.

-   [x] Modelo `PromotionalCredit`.

-   [x] Estados `available`, `reserved` y `consumed`.

-   [x] Reserva, consumo y liberación idempotentes.

-   [x] Integración con pagos manuales.

-   [x] Integración con renovaciones automáticas.

-   [x] Integración con recuperación de pagos.

-   [x] Desglose promocional visible en Billing.

-   [x] Prevención de checkouts manuales pendientes duplicados.

-   [x] Cambio de plan con cancelación segura del checkout anterior.

-   [x] Limpieza automática de checkouts abandonados.

-   [x] Reconciliación segura cuando Stripe ya reporta un PaymentIntent
    como `succeeded`.

-   [x] Scheduler horario para mantenimiento de checkouts.

-   [x] Cobertura automatizada completa.

Baseline al cierre:

797 tests verdes.

2244 assertions.

0 failures.

## DT-14 --- Expediente clínico longitudinal

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

## DT-16 --- Visual redesign / DocTotal UI

Estado: Completado.

Objetivo:

Transformar la interfaz funcional de DocTotal en una experiencia visual
propia, moderna, profesional, consistente y responsive, preservando los
workflows funcionales y la lógica de negocio existente.

Incluye:

-   [x] Foundation visual y design tokens.

-   [x] Identidad y branding de DocTotal.

-   [x] Shell principal, sidebar, header y navegación responsive.

-   [x] Rediseño visual de pacientes y expediente longitudinal.

-   [x] Rediseño visual de citas, consultas y recetas.

-   [x] Rediseño visual de onboarding y configuración.

-   [x] Rediseño de login y registro.

-   [x] Recuperación y restablecimiento de contraseña con UI propia.

-   [x] Correo personalizado de restablecimiento de contraseña.

-   [x] Landing pública de DocTotal.

-   [x] Branding, logo y favicons.

-   [x] Revisión de receta imprimible y PDF.

-   [x] Flash messages integrados visualmente.

-   [x] Auditoría visual final.

-   [x] Suite completa sin regresiones.

Baseline al cierre:

837 tests verdes.

0 failures.

## DT-17 --- Clinical workspace / Consulta médica avanzada

Estado: Completado.

Objetivo:

Transformar la consulta médica en un workspace clínico avanzado,
manteniendo visible el contexto longitudinal del paciente y protegiendo
la captura clínica durante toda la atención.

Incluye:

-   [x] Workspace clínico responsive.

-   [x] Panel persistente de contexto clínico.

-   [x] Alergias, medicamentos actuales, enfermedades crónicas, cirugías
    y antecedentes relevantes.

-   [x] Consultas finalizadas recientes y diagnósticos en contexto.

-   [x] Autosave de consultas draft.

-   [x] Estados de guardado visibles.

-   [x] Protección `beforeunload` frente a cambios pendientes.

-   [x] Validación en español.

-   [x] Resaltado de campos inválidos.

-   [x] Scroll y foco al primer error.

-   [x] Protección de finalización cuando existen cambios pendientes,
    guardado en curso o errores.

-   [x] Revalidación backend antes de completar.

-   [x] Appointment completado únicamente después de finalizar
    correctamente Consultation.

-   [x] Cobertura automatizada específica.

Decisiones:

`PatientMedicalHistory` continúa siendo la fuente explícita de alergias,
medicamentos actuales y antecedentes.

Las prescripciones históricas no se interpretan automáticamente como
medicación actual.

Los rangos de signos vitales implementados son límites técnicos de
validación y no constituyen decisión clínica.

Baseline al cierre:

840 tests verdes.

0 failures.

Commit:

`ff7aee4 DT-17 feat: implement advanced clinical consultation workspace`

## DT-18 --- Documentation baseline normalization

Estado: Completado.

Objetivo:

Normalizar `TODO.md` y `ROADMAP.md` contra el estado real del producto
después del cierre funcional de DT-17.

Incluye:

-   [x] Revisión del baseline documental.

-   [x] Sincronización de avances funcionales previos.

-   [x] Recalculo ponderado del avance global.

-   [x] Baseline establecido en 72%.

-   [x] Integración en `master`.

Baseline heredado:

840 tests verdes.

0 failures.

Avance global ponderado:

72%.

## DT-19 --- Structured active clinical problem list

Estado: Cierre técnico completado; pendiente commit, merge y cierre Jira.

Objetivo:

Implementar una lista estructurada y longitudinal de problemas clínicos
por paciente como evolución natural del expediente y del workspace
clínico.

Incluye:

-   [x] Modelo `PatientProblem`.

-   [x] Persistencia multi-tenant.

-   [x] Soft deletes.

-   [x] Estados `active` y `resolved`.

-   [x] Código opcional.

-   [x] Descripción.

-   [x] Fecha de inicio.

-   [x] Fecha de resolución.

-   [x] Notas.

-   [x] Relación Patient → problems.

-   [x] CRUD dentro del expediente.

-   [x] Resolver y reabrir problemas.

-   [x] Autocomplete opcional desde `DiagnosisCatalog`.

-   [x] Captura manual preservada.

-   [x] Problemas activos visibles durante consulta.

-   [x] Problemas resueltos conservados en el expediente longitudinal.

-   [x] Problemas resueltos excluidos del contexto activo de consulta.

-   [x] Protección contra manipulación desde otro paciente.

-   [x] Aislamiento multi-tenant.

-   [x] Cobertura automatizada de modelo, flujo Livewire, expediente y
    contexto de consulta.

Decisiones:

`PatientProblem` es una entidad longitudinal explícita.

No se infiere automáticamente desde `ConsultationDiagnosis`.

`DiagnosisCatalog` se reutiliza únicamente como ayuda de captura.

`PatientMedicalHistory` continúa siendo fuente de alergias, medicamentos
actuales y antecedentes.

No se agregó UUID porque `PatientProblem` no tiene routing público
independiente.

Fuera de alcance:

-   [ ] Alertas clínicas automáticas.

-   [ ] Inferencia automática desde diagnósticos históricos.

-   [ ] Interacciones farmacológicas.

-   [ ] OCR.

-   [ ] Laboratorios estructurados.

-   [ ] DICOM/PACS.

-   [ ] Comunicaciones externas.

Calidad:

13 tests verdes en `PatientProblemTest` + `PatientProblemFlowTest`.

57 tests verdes en Patients.

76 tests verdes en Consultations.

147 tests verdes en la regresión combinada de DT-19.

Suite completa:

854 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre técnico:

74%.

Pendiente para cierre definitivo:

-   [ ] Commit final DT-19.

-   [ ] Merge a `master`.

-   [ ] Cierre Jira DT-19.


# 27. Candidatos para siguientes DT

Los números definitivos deben asignarse al momento de seleccionar el
siguiente

bloque.

DT-16 quedó completado.

El siguiente bloque debe seleccionarse después del cierre documental y
del commit final de DT-16.

## Candidato A --- Alertas clínicas inteligentes

Objetivo:

Construir alertas clínicas contextuales sobre la foundation longitudinal
existente y sobre `PatientProblem`, sin inferir automáticamente decisiones
médicas.

Base disponible:

Patient

→ PatientMedicalHistory

→ PatientProblem

→ Consultation workspace

→ Diagnósticos históricos

→ Tratamientos históricos

→ Documentos clínicos

Pendiente:

-   [ ] Definir tipos de alertas.

-   [ ] Definir reglas explícitas y auditables.

-   [ ] Definir presentación visual durante consulta.

-   [ ] Evitar convertir validaciones técnicas en decisión clínica.

-   [!] Requiere definición clínica y de producto antes de implementación.

# 28. Deuda y decisiones de producto

Estas decisiones no necesariamente requieren un DT inmediato, pero deben

resolverse antes de los módulos que dependan de ellas.

## Comercial

-   [x] Precio mensual: \$600 MXN.

-   [x] Precio anual: \$6,000 MXN.

-   [x] Descuento anual: 2 meses equivalentes sin costo frente al
    mensual.

-   \[!\] Duración definitiva del trial.

-   [x] Periodo de gracia de billing: 7 días.

-   [x] Política base de cancelación: al final del periodo; reversible
    antes del vencimiento.

-   \[!\] Política de reembolso.

## Referidos

-   [x] Beneficio para tenant nuevo: \$50 MXN de descuento en el primer
    pago.

-   [x] Beneficio para referente: \$50 MXN de crédito.

-   [x] Número de promociones mensuales: máximo 5 recompensas.

-   [x] Crédito máximo generado por mes: \$250 MXN.

-   [x] Requisito de primer pago exitoso.

-   [x] Crédito promocional sin caducidad.

-   [x] Aplicación a mensualidad.

-   [x] Aplicación a anualidad.

-   \[!\] Definir comportamiento ante reembolsos futuros.

## Infraestructura

-   [x] Proveedor de pagos: Stripe.

-   \[\~\] Storage privado local implementado; proveedor externo
    pendiente de decisión operativa.

-   \[!\] Proveedor de correo.

-   \[!\] Proveedor de WhatsApp.

-   \[!\] Proveedor de SMS.

## Clínica

-   \[!\] Estructura de problemas activos.

-   \[!\] Política de modificación de información clínica finalizada.

-   \[\~\] Política técnica base de archivos implementada en DT-15;
    retención y cuotas pendientes.

-   \[!\] Retención de expedientes.

-   \[!\] Requisitos legales de recetas/documentos.

## UX

-   [x] Identidad visual definitiva.

-   [x] Dirección visual del Design System.

-   \[!\] Estructura definitiva del workspace clínico.

# 29. Regla para decidir el siguiente DT

Al terminar cada DT:

1.  Ejecutar suite completa de tests.

2.  Confirmar que todos los tests estén verdes.

3.  Registrar número de tests.

4.  Registrar número de assertions.

5.  Actualizar `ROADMAP.md`.

6.  Actualizar `TODO.md`.

7.  Marcar funcionalidades terminadas.

8.  Registrar nuevas necesidades descubiertas.

9.  Revisar pendientes críticos.

10. Revisar dependencias entre módulos.

11. Elegir el siguiente DT.

12. Definir objetivo.

13. Definir alcance.

14. Definir explícitamente qué queda fuera.

15. Crear branch.

16. Comenzar implementación.

# 30. Regla de cierre de un DT

Un DT no debe considerarse terminado únicamente porque "funciona".

Antes de cerrarlo debe comprobarse:

-   [x] Objetivo cumplido.

-   [x] Alcance implementado.

-   [x] Multi-tenancy protegido.

-   [x] Validaciones implementadas.

-   [x] Estados límite contemplados.

-   [x] Tests agregados.

-   [x] Suite completa verde.

-   [x] UI funcional.

-   [x] Integración con módulos existentes comprobada.

-   [x] TODO actualizado.

-   [x] ROADMAP actualizado.

-   [x] Baseline registrado.

-   [ ] Commit final realizado.

# 31. Principio de desarrollo

No desarrollar una función solamente porque "hace falta".

Antes de comenzar un nuevo DT debemos responder:

1.  ¿Qué problema resuelve?

2.  ¿Para quién lo resuelve?

3.  ¿Qué módulos existentes afecta?

4.  ¿Qué información necesita?

5.  ¿Qué información genera?

6.  ¿Cómo afecta al aislamiento multi-tenant?

7.  ¿Cómo afecta al expediente clínico?

8.  ¿Cómo afecta al ciclo SaaS?

9.  ¿Cómo debe verse y sentirse para el usuario?

10. ¿Cómo vamos a probarlo?

DocTotal debe crecer como un producto integrado, no como una colección

de

pantallas y funciones independientes.

# Baseline actual después de DT-19

Avance global ponderado:

74%.

Suite completa:

854 tests verdes.

0 failures.

DT-19 se encuentra técnicamente terminado y pendiente únicamente de su
commit final, merge a `master` y cierre en Jira.

# 32. Visión de producto

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
