# DocTotal --- TODO

## Progreso general

**79% completado**

`████████████████░░░░` 79%

> El porcentaje representa avance global del producto, no cobertura de

tests.

> Baseline anterior normalizado en DT-18: 72%.
>
> Baseline al cierre de DT-19: 74%.
>
> Baseline al cierre de DT-20: 77%.
>
> Baseline ponderado vigente: 79% (último porcentaje formalmente
> calculado).
>
> Suite completa al cierre técnico de DT-29: 1110 tests verdes / 0
> failures.
>
> No se infiere un porcentaje nuevo sin aplicar nuevamente el criterio
> ponderado del producto.
>
> El porcentaje representa avance global ponderado del producto y no
> cobertura de tests.

Este documento representa el estado funcional actual de DocTotal y sirve

como

mapa maestro para decidir los siguientes bloques de desarrollo (DT).

No sustituye al Roadmap.

-   `ROADMAP.md` registra los bloques DT realizados, su objetivo y su

cierre.

-   `TODO.md` registra qué existe actualmente, qué está incompleto,
    qué

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

DocTotal debe administrar automáticamente el ciclo comercial y
operativo

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

no como una aplicación construida directamente con componentes
estándar

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

-   [x] Recuperación de contraseña mediante el flujo existente de Fortify.

-   [x] Cambio de contraseña desde Configuración → Seguridad.

-   [x] Contraseña actual obligatoria y confirmación de la nueva contraseña.

-   [x] Auditoría del cambio de contraseña sin persistir credenciales.

## Seguridad adicional

-   [x] Two-factor authentication (TOTP) con confirmación de configuración.

-   [x] QR y códigos de recuperación con exposición temporal protegida.

-   [x] Regeneración de códigos de recuperación y desactivación con reautenticación.

-   [x] Challenge 2FA durante login y soporte de código de recuperación.

-   [x] Verificación de correo con enlace firmado, reenvío y bloqueo de áreas protegidas.

-   [x] Configuración → Seguridad permanece accesible para usuarios no verificados y tenants suspendidos.

-   [x] Administración visible de sesiones y dispositivos.

-   [x] Revocación individual de sesiones y revocación de todas las demás sesiones.

-   [x] Invalidación de cookies persistentes mediante rotación de remember token al revocar sesiones.

-   [x] Auditoría de acciones sensibles sin contraseñas, OTP, códigos de recuperación, tokens ni IDs reales de sesión.

-   [x] Passkeys/WebAuthn evaluadas técnicamente en DT-28.

-   [!] Activación de passkeys diferida hasta definir el hostname productivo HTTPS canónico y sus WebAuthn relying party/origins.

-   [!] Mantener auditoría integral de seguridad de producción como requisito previo al lanzamiento.

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

DT-14 convirtió la base clínica existente en un expediente
longitudinal funcional sin duplicar las fuentes de verdad ya existentes.

DT-15 incorporó la capa documental del expediente sobre storage
privado, manteniendo los metadatos clínicos separados de los bytes
almacenados.

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

-   [x] Problemas clínicos activos visibles durante consulta --- DT-19.

-   [x] Problemas resueltos excluidos del contexto activo.

-   [x] Protección contra pérdida de cambios con `beforeunload`.

-   [x] Validación en español y foco automático al primer error.

-   [x] Finalización protegida y revalidada en backend.

-   [x] Plantillas clínicas reutilizables por tenant --- DT-26.

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

-   [x] Problemas clínicos activos estructurados mediante
    `PatientProblem`.

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

-   [x] Repetir una receta anterior mediante una nueva emisión independiente.

-   [x] Precargar medicamentos, presentación, dosis, frecuencia, duración e instrucciones para revisión antes de guardar.

-   [x] Conservar trazabilidad hacia la receta origen mediante `source_prescription_id`.

-   [x] Mantener inmutable la receta fuente y crear nuevos `PrescriptionItem`.

-   [x] Mantener la repetición dentro del mismo paciente y tenant.

-   [x] Crear la receta repetida como evento independiente, sin adjuntarla retroactivamente a la consulta histórica de origen.

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

-   [x] Tests específicos de almacenamiento, aislamiento,
    visualización, descarga y eliminación.

-   [ ] Límites totales por tenant.

-   [ ] Indicador de almacenamiento utilizado.

-   [ ] Proveedor externo definitivo.

-   [ ] URLs temporales firmadas si fueran necesarias.

-   [ ] Thumbnails derivados para PDF.

-   [ ] OCR/extracción.

-   [ ] DICOM/PACS.

-   [ ] Resultados de laboratorio estructurados.

-   \[!\] Definir política de conservación, retención y respaldo.

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

-   [x] Acceso durante `past_due` mientras el tenant no esté
    suspendido.

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

de prueba. Los cobros automáticos permanecen protegidos por feature
flag hasta

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

-   [x] Renovación automática implementada y protegida por feature
    flag.

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

-   [x] Idempotencia de intentos de renovación, recuperación y
    checkout manual.

-   [x] Integración de descuentos y créditos promocionales con
    billing.

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

-   [x] Cambio mensual ↔ anual cancelando primero el checkout
    anterior.

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

Relacionado principalmente con DT-20.

DT-20 construyó la foundation transaccional multi-tenant de
comunicaciones de DocTotal y el primer flujo operativo de recordatorios
de citas.

## Foundation implementada

-   [x] Modelo `Communication`.

-   [x] Persistencia multi-tenant mediante `BelongsToTenant`.

-   [x] Relaciones con Patient y Appointment.

-   [x] Snapshot del destinatario.

-   [x] Tipo, canal, asunto opcional, cuerpo y metadata.

-   [x] Estados `pending`, `sent`, `failed` y `cancelled`.

-   [x] `scheduled_for`, `sent_at`, `failed_at`, `next_attempt_at` y
    `cancelled_at`.

-   [x] Conteo de intentos, último error y motivo de cancelación.

-   [x] `idempotency_key` único por tenant.

-   [x] Historial persistente y auditable.

## Transportes y procesamiento

-   [x] Contrato `CommunicationTransport`.

-   [x] `CommunicationTransportManager`.

-   [x] Canales preparados: email, WhatsApp y SMS.

-   [x] Configuración independiente por canal.

-   [x] Ausencia segura de proveedor configurado.

-   [x] Sin transport no se simula un envío exitoso.

-   [x] Sin transport no se consume intento.

-   [x] `CommunicationProcessor`.

-   [x] Máximo actual de 3 intentos.

-   [x] Backoff de 5 y 15 minutos.

-   [x] `communications:process-due`.

-   [x] Procesamiento aislado por tenant.

-   [x] Scheduler de procesamiento.

## Recordatorios de citas

-   [x] `AppointmentReminderService`.

-   [x] Recordatorios para citas futuras `scheduled` y `confirmed`.

-   [x] Programación ideal 24 horas antes.

-   [x] Si la ventana ideal ya pasó, queda elegible para envío
    inmediato.

-   [x] Idempotencia por appointment UUID + canal + timestamp de cita.

-   [x] Una reprogramación genera una nueva identidad de recordatorio.

-   [x] Citas canceladas no generan recordatorio nuevo.

-   [x] Falta de contacto requerido omite la generación.

-   [x] `communications:generate-appointment-reminders`.

-   [x] Canal y ventana futura configurables.

-   [x] Scheduler horario de generación.

## Protección contra recordatorios obsoletos

-   [x] `AppointmentReminderValidator`.

-   [x] Validación antes del procesamiento.

-   [x] Cancelación de recordatorios de citas que dejaron de ser
    elegibles.

-   [x] Cancelación del recordatorio anterior después de reprogramar.

-   [x] Comparación contra `appointment_starts_at` persistido en
    metadata.

-   [x] Cancelación sin consumir intento.

-   [x] Conservación del motivo para auditoría.

## Integración visual

-   [x] Historial de comunicaciones dentro del detalle de cita.

-   [x] Estado, canal, destinatario y tipo.

-   [x] Fechas de creación, programación y envío.

-   [x] Intentos y próximo intento.

-   [x] Fecha y motivo de cancelación.

-   [x] Último error.

-   [x] Empty state.

## Pendiente futuro

-   [ ] Proveedor real de correo.

-   [ ] Proveedor real de WhatsApp.

-   [ ] Proveedor real de SMS.

-   [ ] Confirmación externa por paciente.

-   [ ] Avisos automáticos de cancelación y reprogramación.

-   [ ] Comunicaciones de trial y billing.

-   [ ] Preferencias/consentimiento por canal.

-   \[!\] Evaluar hardening adicional de concurrencia para despliegues
    distribuidos.

## Decisiones

La arquitectura no se acopla a un proveedor específico.

La ausencia de proveedor nunca debe marcar una comunicación como
enviada.

Los recordatorios obsoletos se cancelan y conservan como historial.

Las comunicaciones transaccionales se mantienen separadas de campañas
de marketing o envíos masivos.

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

-   \[!\] Expediente todavía no comunica suficientemente la historia
    del

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

-   [x] Facilitar repetir tratamientos/recetas desde el historial del paciente.

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

DT-21 incorporó la foundation formal de auditoría sensible de
DocTotal. El alcance actual es deliberadamente limitado y extensible.

## Foundation implementada en DT-21

-   [x] Modelo `AuditEvent`.

-   [x] Persistencia multi-tenant mediante `BelongsToTenant`.

-   [x] Actor opcional mediante `user_id`.

-   [x] Asociación polimórfica con el recurso auditado.

-   [x] Acción, descripción, timestamps, IP y user agent.

-   [x] Metadata controlada.

-   [x] Sanitización recursiva de metadata sensible.

-   [x] Redacción de variantes de password, token, authorization,
    cookie, secret y api_key.

-   [x] `AuditLogger`.

-   [x] `safeLog()` best-effort para no romper la operación principal
    cuando falla la persistencia de auditoría.

-   [x] Registro técnico de fallos de auditoría mediante logging de
    Laravel.

-   [x] Protección append-only a nivel Eloquent para update/delete
    normales.

-   [x] Aislamiento de eventos entre tenants.

-   [x] Tests de integridad, aislamiento, actor, recurso, metadata y
    fallos.

## Flujos sensibles auditados actualmente

-   [x] Actualización de datos generales del paciente.

-   [x] Finalización de consulta.

-   [x] Reprogramación de cita.

-   [x] Cancelación de cita.

-   \[\~\] Historial de cambios clínicos: foundation disponible; no se
    auditan todavía todas las mutaciones clínicas.

-   \[\~\] Historial de cambios de citas: reprogramación y cancelación
    auditadas; otras transiciones pueden incorporarse posteriormente.

-   [ ] Historial de cambios de recetas.

-   [ ] Historial de suscripción.

-   [ ] Historial de pagos.

-   [ ] Eventos administrativos internos.

## Integración visual

-   [x] Historial de actividad dentro del expediente del paciente.

-   [x] Actor, descripción y fecha/hora.

-   [x] Paginación de 5 eventos.

-   [x] Empty state.

-   [x] Detalles técnicos y metadata no expuestos en la tarjeta visual.

-   \[!\] La tarjeta del paciente muestra eventos cuyo recurso auditado
    es Patient; no mezcla automáticamente eventos de Consultation o
    Appointment.

## Seguridad y privacidad pendiente

-   [x] Protección base de archivos clínicos.

-   \[\~\] Protección de información sensible en metadata de
    auditoría.

-   [ ] Revisión integral de autorización.

-   [ ] Revisión integral de validaciones.

-   [ ] Rate limiting.

-   [ ] Política de contraseñas.

-   [ ] Auditoría de 2FA/passkeys.

-   [ ] Administración y revocación de sesiones/dispositivos.

-   [ ] Backups.

-   [ ] Restauración.

-   [ ] Logging estructurado de producción.

-   [ ] Monitoreo de errores.

-   [ ] Política de retención.

-   [ ] Política de eliminación.

-   [ ] Inmutabilidad de auditoría garantizada a nivel de base de
    datos.

-   [ ] Outbox/transacción durable para garantizar persistencia de
    auditoría ante fallos de infraestructura.

-   \[!\] Revisión integral antes de manejar información real de
    pacientes.

Decisiones:

La auditoría actual es best-effort. Una falla al persistir un
`AuditEvent` no debe cambiar el resultado funcional de la operación
principal.

La protección append-only de DT-21 existe a nivel del modelo Eloquent y
no debe documentarse como garantía de inmutabilidad de base de datos.

La metadata de auditoría debe mantenerse mínima y evitar secretos,
tokens, contraseñas y payload clínico innecesario.

# 23. Operación interna de DocTotal

Relacionado con DT-22.

DT-22 completó la foundation de operación administrativa interna de
DocTotal como una experiencia separada del producto clínico de los
tenants.

La consola permite operar el SaaS globalmente sin convertir las
consultas cross-tenant en bypasses dispersos del aislamiento.

## Acceso interno

-   [x] Rol `internal_admin`.
-   [x] Administrador interno válido con `tenant_id = null`.
-   [x] Middleware exclusivo `internal.admin`.
-   [x] Usuarios normales de tenant bloqueados de la consola interna.
-   [x] Consola fuera del flujo de onboarding clínico.
-   [x] Layout visual interno independiente.
-   [x] Redirección post-login hacia `/internal`.
-   [x] Usuarios de tenant conservan `/dashboard`.
-   [x] Comando `doctotal:make-internal-admin`.

## Tenants

-   [x] Panel administrativo interno.
-   [x] Listado global de tenants.
-   [x] Ver tenant.
-   [x] Estado.
-   [x] Fecha de alta.
-   [x] Trial.
-   [x] Inicio y fin de trial.
-   [x] Duración del trial.
-   [x] Días restantes o vencidos.
-   [x] Cantidad de usuarios.
-   [x] Estado de onboarding.
-   [x] Suscripción actual.
-   [x] Últimos pagos.
-   [x] Estado efectivo del servicio.
-   [ ] Estado de almacenamiento.

## Operación SaaS

-   [x] Trials activos.
-   [x] Trials vencidos.
-   [x] Suscripciones activas.
-   [x] Suscripciones `past_due`.
-   [x] Tenants suspendidos visibles por estado.
-   [x] Pagos dentro del detalle operativo.
-   [x] Pagos fallidos globales.
-   [x] Grace period vigente.
-   [x] Grace period vencido.
-   [x] Estado global de comunicaciones.
-   [x] Comunicaciones fallidas.
-   [x] Acceso operativo a auditoría.
-   [x] Indicadores generales de salud SaaS.
-   [x] Referidos existentes en el dominio SaaS.
-   [ ] Promociones dentro de la consola interna.

## Frontera cross-tenant

-   [x] `InternalSaasOverviewService` centraliza lecturas globales.
-   [x] Eliminación explícita únicamente de `TenantScope` cuando
    corresponde.
-   [x] Sin desactivación indiscriminada de global scopes.
-   [x] Cobertura automatizada de overview, listado y detalle.
-   [x] Cobertura de aislamiento de la consola interna.
-   \[!\] Todo nuevo acceso global debe seguir siendo explícito,
    encapsulado y testeado.

## Acceso efectivo al servicio

-   [x] Trial vigente permite acceso.
-   [x] Suscripción vigente permite acceso.
-   [x] `past_due` dentro del grace period permite acceso.
-   [x] Grace period vencido bloquea acceso clínico.
-   [x] Tenant suspendido bloquea acceso aun con suscripción.
-   [x] Tenant cancelado bloquea acceso.
-   [x] Sin trial vigente ni suscripción válida se bloquea acceso.
-   [x] `EnsureTenantHasServiceAccess`.
-   [x] Pantalla de servicio suspendido.
-   [x] Billing permanece accesible para permitir recuperación.
-   [x] La expiración del trial no muta automáticamente `Tenant.status`.

## Límites deliberados

-   [ ] Impersonación.
-   [ ] Edición arbitraria de información clínica.
-   [ ] SIEM completo.
-   [ ] Herramientas destructivas masivas.
-   [ ] Analítica financiera definitiva.
-   [ ] Auditoría de futuras acciones administrativas sensibles de
    escritura.
-   \[!\] El panel administrativo nunca debe permitir romper
    accidentalmente el aislamiento entre tenants.

# 24. Infraestructura y operación técnica

Existe infraestructura base de Laravel para cache/jobs y DT-12
incorporó

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

-   [x] Procesamiento programado de comunicaciones transaccionales.

-   [x] Generación programada de recordatorios de citas.

-   \[\~\] Envío real pendiente de transports/proveedores.

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

Cierre DT-19:

854 tests verdes.

0 failures.

Cierre técnico DT-20:

910 tests verdes.

0 failures.

Assertions finales de DT-20 no registradas; no se infieren.

Cierre técnico DT-21:

936 tests verdes.

0 failures.

Assertions finales de DT-21 no registradas; no se infieren.

Estado actual durante DT-22:

952 tests verdes.

0 failures.

El baseline de 952 corresponde a la suite completa ejecutada durante el
desarrollo de DT-22. El porcentaje global permanece en 79% hasta
realizar el cierre técnico y recalcularlo.

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

-   [x] Tests de comunicaciones transaccionales y recordatorios.

-   [x] Tests de idempotencia, transports, reintentos y recordatorios
    obsoletos.

-   [x] Tests de foundation de auditoría, aislamiento, integridad y
    redacción.

-   [x] Tests de fiabilidad best-effort de auditoría.

-   [x] Tests de acceso a la consola administrativa interna.

-   [x] Tests de aislamiento entre `internal_admin` y usuarios de
    tenant.

-   [x] Tests de overview SaaS cross-tenant encapsulado.

-   [x] Tests de listado y detalle operativo de tenants.

-   [x] Tests del comando de creación de administrador interno.

-   [x] Tests de redirección Fortify para administrador interno y
    usuarios de tenant.

-   \[\~\] Tests de seguridad adicionales fuera del alcance inicial de
    DT-21.

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

Convertir la información clínica existente del paciente en una
historia longitudinal coherente, reutilizando las entidades clínicas ya
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

-   [x] Alergias, medicamentos actuales, enfermedades crónicas,
    cirugías y antecedentes relevantes.

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

`PatientMedicalHistory` continúa siendo la fuente explícita de
alergias, medicamentos actuales y antecedentes.

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

Estado: Completado.

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

`PatientMedicalHistory` continúa siendo fuente de alergias,
medicamentos actuales y antecedentes.

No se agregó UUID porque `PatientProblem` no tiene routing público
independiente.

Fuera de alcance:

-   [ ] Alertas clínicas automáticas.

-   [ ] Inferencia automática desde diagnósticos históricos.

-   [ ] Interacciones farmacológicas.

-   [ ] OCR.

-   [x] Laboratorios estructurados --- DT-27.

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

Cierre definitivo:

-   [x] Commit final DT-19.

-   [x] Merge a `master`.

-   [x] Cierre Jira DT-19.

Commit principal:

`1dd4ad7 DT-19 feat: implement structured active clinical problem list`

## DT-20 --- Transactional communications and appointment reminders foundation

Estado: Completado.

Objetivo:

Construir una foundation multi-tenant, persistente y extensible para
comunicaciones transaccionales y habilitar el primer flujo real de
recordatorios de citas sin acoplar DocTotal a un proveedor específico.

Incluye:

-   [x] Modelo `Communication`.

-   [x] Relaciones con Patient y Appointment.

-   [x] Estados `pending`, `sent`, `failed` y `cancelled`.

-   [x] Snapshot de destinatario y contenido.

-   [x] Idempotencia por tenant.

-   [x] `CommunicationTransport`.

-   [x] `CommunicationTransportManager`.

-   [x] Configuración independiente por email, WhatsApp y SMS.

-   [x] `CommunicationProcessor`.

-   [x] Reintentos con backoff y máximo de 3 intentos.

-   [x] Manejo seguro cuando no existe transport configurado.

-   [x] `AppointmentReminderService`.

-   [x] Generación idempotente de recordatorios.

-   [x] Comandos de generación y procesamiento.

-   [x] Scheduler.

-   [x] `AppointmentReminderValidator`.

-   [x] Cancelación auditable de recordatorios obsoletos.

-   [x] Protección ante cancelación, finalización, no-show y
    reprogramación.

-   [x] Historial visual de comunicaciones en el detalle de la cita.

-   [x] Protección multi-tenant.

-   [x] Cobertura automatizada.

Validación:

56 tests verdes en la regresión específica de DT-20.

148 tests verdes en la regresión de appointments después de la
integración visual.

Suite completa:

910 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre técnico:

77%.

Fuera de alcance:

-   [ ] Campañas de marketing y envíos masivos.

-   [ ] Proveedores reales obligatorios en este DT.

-   [ ] Confirmación externa por paciente.

-   [ ] Alertas clínicas e inferencia médica automática.

Cierre definitivo:

-   [x] Commit final DT-20.

-   [x] Merge a `master`.

-   [x] Comentario técnico de cierre en Jira.

-   [x] Transición de DT-20 a `Listo`.

Commit principal:

`9192020 DT-20 feat: implement transactional communications and appointment reminders`

## DT-21 --- Audit trail and security hardening foundation

Estado: Completado.

Objetivo:

Construir una foundation reusable de auditoría y hardening para
acciones sensibles, con aislamiento multi-tenant, trazabilidad y
metadata controlada, sin acoplar los modelos clínicos a una
implementación específica de logging.

Incluye:

-   [x] Modelo `AuditEvent`.

-   [x] Migración e índices de auditoría.

-   [x] `BelongsToTenant` y aislamiento por tenant.

-   [x] Actor opcional.

-   [x] Recurso polimórfico auditable.

-   [x] `AuditLogger`.

-   [x] Sanitización recursiva de metadata sensible.

-   [x] `safeLog()` best-effort.

-   [x] Logging técnico cuando la auditoría falla.

-   [x] Protección append-only a nivel Eloquent.

-   [x] Auditoría de `patient.updated`.

-   [x] Auditoría de `consultation.completed`.

-   [x] Auditoría de `appointment.rescheduled`.

-   [x] Auditoría de `appointment.cancelled`.

-   [x] Historial visual de actividad en expediente de paciente.

-   [x] Paginación de 5 eventos.

-   [x] Cobertura automatizada.

Decisiones:

La operación principal no debe fallar únicamente porque no pudo
persistirse el evento de auditoría.

La protección append-only actual es de modelo Eloquent, no una
garantía de inmutabilidad a nivel de base de datos.

La metadata debe ser mínima y no duplicar contenido clínico sensible.

Validación:

58 tests verdes en la regresión focalizada de DT-21.

13 tests verdes en la regresión del historial visual/paginación.

11 tests verdes en AppointmentShow después del ajuste final a
`safeLog()`.

Suite completa final:

936 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre técnico:

79%.

Fuera de alcance:

-   [ ] SIEM completo.

-   [ ] Auditoría exhaustiva de lecturas.

-   [ ] Backups/restauración integral.

-   [ ] 2FA obligatorio.

-   [ ] Passkeys.

-   [ ] Gestión avanzada de dispositivos.

-   [ ] Retención legal definitiva.

-   [ ] Inmutabilidad garantizada por base de datos.

-   [ ] Outbox transaccional de auditoría.

Cierre definitivo:

-   [x] Commit final DT-21.

-   [x] Merge a `master`.

-   [x] Comentario técnico de cierre en Jira.

-   [x] Transición de DT-21 a `Listo`.

Commit principal:

`c3c70d9 DT-21 feat: implement audit trail and security hardening foundation`

# 27. Estado del siguiente bloque priorizado

La cola priorizada definida después de DT-21 ya seleccionó formalmente
el siguiente bloque.

## DT-22 --- Internal SaaS administration panel foundation

Estado: Cierre técnico completado; pendiente commit documental final,
push, PR, merge y cierre Jira.

Objetivo:

Construir una consola interna para operar DocTotal como SaaS con
visibilidad global controlada sobre tenants, usuarios, trials,
suscripciones, pagos, incidencias, comunicaciones, auditoría y salud
general del servicio.

Implementado:

-   [x] Rol y autorización `internal_admin`.
-   [x] Shell administrativo separado del producto clínico.
-   [x] Dashboard SaaS operacional.
-   [x] Métricas e indicadores globales.
-   [x] Listado y detalle operativo de tenants.
-   [x] Presentación detallada del trial.
-   [x] Estado efectivo del servicio.
-   [x] Incidencias globales de billing.
-   [x] Pagos fallidos.
-   [x] Suscripciones `past_due`.
-   [x] Grace period vigente/vencido.
-   [x] Estado global de comunicaciones.
-   [x] Comunicaciones fallidas.
-   [x] Acceso operativo a auditoría.
-   [x] Lecturas cross-tenant encapsuladas.
-   [x] Creación formal de administrador interno.
-   [x] Redirección Fortify correcta.
-   [x] Protección clínica mediante `service.access`.
-   [x] Pantalla de servicio suspendido.
-   [x] Billing accesible para recuperación.
-   [x] Cobertura automatizada y estabilización de fixtures históricos.

Validación final:

`988 tests verdes`

`0 failures`

Commit funcional más reciente:

`228c9b2 DT-22 feat: complete operational dashboard and enforce tenant service access`

Fuera de alcance:

-   [ ] Impersonación.
-   [ ] Edición arbitraria de contenido clínico.
-   [ ] SIEM completo.
-   [ ] Herramientas destructivas masivas.
-   [ ] Analítica financiera definitiva.
-   [ ] Cambios profundos al motor de billing únicamente para alimentar
    la consola.

Después del cierre definitivo de DT-22, la siguiente prioridad funcional
es **Interacción del paciente con citas**.

# 26A. Billing recovery y cambio de plan --- DT-23

Relacionado con DT-23.

-   [x] Diferenciar cambio de plan futuro de una suscripción pagada frente a cambio de plan para recuperación `past_due`.
-   [x] Resolver el ciclo e importe de recuperación desde `pending_billing_cycle` cuando corresponde.
-   [x] Mantener inmutable el histórico de pagos exitosos.
-   [x] No reutilizar PaymentIntent/idempotency key con importe o ciclo incompatible.
-   [x] Aplicar el nuevo ciclo únicamente después de pago exitoso.
-   [x] Mantener compatibilidad con pagos históricos sin snapshot contractual completo.
-   [x] Mantener créditos promocionales/referidos durante recuperación.
-   [x] Permitir recuperar una suscripción después de vencer grace sin conceder acceso clínico previo al pago.
-   [x] Restaurar acceso después de recuperación exitosa.

Validación final:

`995 tests verdes`

`0 failures`

# 27A. Interacción pública del paciente con citas --- DT-24

Relacionado con DT-24.

-   [x] Enlace público seguro de gestión por cita.
-   [x] Token aleatorio de alta entropía.
-   [x] Persistir únicamente hash del token.
-   [x] Evitar enumeración por UUID/ID.
-   [x] Acceso sin login ni sesión del paciente.
-   [x] Vista pública mínima sin información clínica sensible.
-   [x] Confirmación pública de cita `scheduled`.
-   [x] Confirmación idempotente.
-   [x] Cancelación pública de cita `scheduled` o `confirmed`.
-   [x] Bloquear acciones públicas en `checked_in`, `in_progress`, `completed`, `cancelled` y `no_show`.
-   [x] Invalidar el enlace anterior al reprogramar.
-   [x] Generar un enlace nuevo en el recordatorio posterior a la reprogramación.
-   [x] Integrar enlace seguro con la infraestructura de recordatorios DT-20.
-   [x] Auditar confirmación/cancelación pública sin persistir el token.
-   [x] Cobertura automatizada de acceso público, estados y aislamiento.
-   [ ] Solicitud/reprogramación pública con selección de nuevos horarios.
-   [ ] Portal completo del paciente.

Validación final:

`1002 tests verdes`

`0 failures`


# 27B. Compartición manual del enlace de gestión de cita --- DT-25

Relacionado con DT-25.

-   [x] Generar manualmente el enlace seguro desde el detalle de la cita.
-   [x] Regenerar el enlace e invalidar inmediatamente el anterior.
-   [x] Usar URL pública compacta `/a/{token}`.
-   [x] Mantener token URL-safe de alta entropía y persistir sólo SHA-256.
-   [x] Mantener compatibilidad con enlaces anteriores de DT-24.
-   [x] Copiar enlace público.
-   [x] Copiar mensaje completo.
-   [x] Abrir WhatsApp con destinatario y mensaje precargados.
-   [x] Normalizar números mexicanos para WhatsApp.
-   [x] Abrir correo con destinatario, asunto y cuerpo precargados.
-   [x] Incluir médico, clínica/consultorio, fecha humana y hora en el mensaje.
-   [x] Reutilizar construcción de enlace/mensaje en recordatorios automáticos y flujo manual.
-   [x] No marcar como enviada una comunicación por copiar o abrir WhatsApp/correo.
-   [x] Mostrar una vista pública amigable para enlaces inválidos o invalidados conservando HTTP 404.
-   [x] Mantener token plano fuera de auditoría y metadata persistida.

Validación final:

`1005 tests verdes`

`0 failures`

# 27C. Plantillas clínicas reutilizables --- DT-26

Relacionado con DT-26.

-   [x] Administración de plantillas clínicas por tenant.
-   [x] Crear y editar nombre, descripción, motivo de consulta y estructura SOAP.
-   [x] Activar y desactivar plantillas.
-   [x] Aplicar plantillas desde el workspace de consulta.
-   [x] Copiar el contenido como snapshot sin dependencia mutable con la plantilla original.
-   [x] Mantener consultas previas sin cambios cuando la plantilla se edita después.
-   [x] Mostrar únicamente plantillas activas del tenant actual al aplicar.
-   [x] Registrar contador de usos.
-   [x] Permitir eliminar sólo plantillas nunca utilizadas.
-   [x] Mantener plantillas utilizadas mediante desactivación en lugar de eliminación.
-   [x] Auditar operaciones relevantes y aplicación.
-   [x] Usar SweetAlert para confirmaciones de aplicación/eliminación.
-   [x] Integrar botones con el sistema visual existente de DocTotal.
-   [x] Cobertura automatizada de aislamiento multi-tenant y flujo funcional.
-   [ ] Plantillas por especialidad.

Validación final:

`1011 tests verdes`

`0 failures`


# 27D. Laboratorios estructurados --- DT-27

Relacionado con DT-27.

-   [x] Crear estudios de laboratorio asociados al paciente.
-   [x] Permitir asociación opcional a una consulta del mismo paciente.
-   [x] Registrar nombre, fecha, laboratorio/proveedor y observaciones.
-   [x] Registrar múltiples parámetros por estudio.
-   [x] Registrar parámetro, valor, unidad y rango de referencia.
-   [x] Soportar valores numéricos y textuales.
-   [x] Mostrar historial de laboratorios dentro del expediente.
-   [x] Integrar acceso/resumen visual de Laboratorios en el expediente.
-   [x] Crear, editar y eliminar estudios.
-   [x] Eliminar de forma segura los resultados asociados al eliminar un estudio.
-   [x] Mantener captura manual de parámetros.
-   [x] Permitir captura masiva mediante pegado desde Excel/Google Sheets.
-   [x] Aceptar filas tabuladas y texto separado por `|` o `;`.
-   [x] Convertir la captura masiva en filas revisables antes de guardar.
-   [x] Mantener aislamiento multi-tenant.
-   [x] Auditar altas, cambios y eliminaciones.
-   [x] Cubrir autorización, tenant, asociación, edición, eliminación y captura masiva con tests.
-   [ ] Vincular opcionalmente el estudio estructurado con su documento clínico fuente.
-   [ ] Importar resultados desde PDF/imagen con revisión humana.
-   [ ] Integraciones HL7/FHIR o importaciones de proveedores.
-   [ ] Gráficas longitudinales avanzadas.
-   [ ] Interpretación clínica automática/IA.

Validación final:

`1021 tests verdes`

`0 failures`


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

-   \[!\] Política de modificación de información clínica
    finalizada.

-   \[\~\] Política técnica base de archivos implementada en DT-15;
    retención y cuotas pendientes.

-   \[!\] Retención de expedientes.

-   \[!\] Requisitos legales de recetas/documentos.

## UX

-   [x] Identidad visual definitiva.

-   [x] Dirección visual del Design System.

-   \[!\] Estructura definitiva del workspace clínico.

# 28. Seguridad de cuenta --- DT-28

Relacionado con DT-28.

Implementado:

-   Configuración → Seguridad con cambio de contraseña protegido por contraseña actual.
-   2FA TOTP con QR, confirmación, códigos de recuperación, regeneración, desactivación y challenge de login.
-   Administración de sesiones/dispositivos con revocación individual y masiva, sin exponer IDs reales.
-   Verificación de correo con enlaces firmados y reenvío.
-   Protección de onboarding, billing, consola interna y aplicación clínica para cuentas no verificadas.
-   Acceso a Seguridad conservado para cuentas no verificadas y tenants suspendidos.
-   Auditoría de eventos sensibles sin credenciales, secretos, OTP, recovery codes ni URLs firmadas.
-   Evaluación de passkeys/WebAuthn sobre la infraestructura real de Laravel/Fortify.

Decisión de passkeys:

-   Laravel/Fortify y la base de datos ya contienen foundation para passkeys.
-   La activación se difiere hasta definir el hostname productivo HTTPS canónico y configurar de forma definitiva relying party/origins.
-   No se activa una ceremonia WebAuthn contra dominios locales que no serán el origen productivo.

Calidad final DT-28:

`1068 tests verdes`

`0 failures`

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

# Baseline actual al cierre técnico de DT-29

Avance global ponderado vigente:

79%.

Este es el último porcentaje formalmente calculado. No se infiere un
porcentaje nuevo sin aplicar nuevamente el criterio ponderado del
producto.

Suite completa final:

1110 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

DT-21, DT-22, DT-23, DT-24, DT-25, DT-26, DT-27 y DT-28 están completados e integrados.

DT-29 tiene cierre técnico completado y está pendiente de commit, push,
PR, merge y cierre Jira.

Validación específica reportada para el cierre de DT-29:

-   Bloque 2 de repetición/historial: 16 tests verdes.
-   Regresión de recetas: 112 tests verdes.
-   Suite completa: 1110 tests verdes / 0 failures.

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

------------------------------------------------------------------------

## Cola priorizada de próximos bloques

> Orden acordado después de DT-21. Al cerrar cada bloque se continuará
> con el siguiente pendiente, salvo que se decida explícitamente cambiar
> la prioridad.

1.  🏥 **Panel administrativo interno SaaS --- DT-22 COMPLETADO** Consola
    interna implementada e integrada.

2.  📱 **Interacción del paciente con citas --- DT-24 COMPLETADO**
    Confirmación y cancelación mediante enlaces públicos seguros.

3.  🔗 **Compartición manual del enlace de cita --- DT-25 COMPLETADO**
    Generación/regeneración manual, URL compacta, copia, WhatsApp, correo
    y experiencia amigable para enlaces inválidos.

4.  📋 **Plantillas clínicas --- DT-26 COMPLETADO**
    Plantillas reutilizables por tenant, aplicación como snapshot y base
    para futuras plantillas por especialidad.

5.  🧪 **Laboratorios estructurados --- DT-27 COMPLETADO** Estudios, resultados estructurados,
    historial y captura masiva revisable, sin interpretación clínica automática.

6.  🔐 **Seguridad de cuenta --- DT-28 COMPLETADO** Cambio de contraseña, 2FA,
    verificación de correo, sesiones/dispositivos y revocación. Passkeys evaluadas y diferidas hasta fijar el origen WebAuthn productivo.

7.  💊 **Repetición de tratamientos/recetas --- DT-29 CIERRE TÉCNICO COMPLETADO**
    Crear nuevas recetas a partir de recetas anteriores, conservando
    inmutable el historial fuente, con trazabilidad al origen,
    edición previa y protección multi-tenant.
