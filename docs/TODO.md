# DocTotal --- TODO

## Progreso general

**79% completado**

`ÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûêÔûæÔûæÔûæÔûæ` 79%

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
> Suite completa al cierre técnico de DT-22: 988 tests verdes / 0
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

-   `TODO.md` registra qu├® existe actualmente, qu├® est├í incompleto,
    qu├®

falta y

┬á qu├® decisiones de producto siguen pendientes.

Este documento debe actualizarse al finalizar cada DT antes de

seleccionar

el siguiente bloque de desarrollo.

# Objetivo del producto

DocTotal debe convertirse en una plataforma SaaS para m├®dicos y

consultorios

que cubra tres pilares principales.

## 1. Operaci├│n m├®dica

Permitir administrar la operaci├│n diaria y cl├¡nica del consultorio:

-   Pacientes.

-   Expedientes cl├¡nicos.

-   Agenda.

-   Citas.

-   Consultas.

-   Diagn├│sticos.

-   Recetas.

-   Archivos cl├¡nicos.

-   Estudios.

-   Laboratorios.

-   Im├ígenes m├®dicas.

-   Historial longitudinal del paciente.

## 2. Autoadministraci├│n SaaS

DocTotal debe administrar autom├íticamente el ciclo comercial y
operativo

de

cada tenant:

-   Registro.

-   Periodo de prueba.

-   Suscripci├│n.

-   Mensualidades.

-   Anualidades.

-   Pagos.

-   Renovaciones.

-   Vencimientos.

-   Pagos fallidos.

-   Suspensiones.

-   Reactivaciones.

-   Cancelaciones.

-   Eliminaci├│n programada.

-   Referidos.

-   Promociones.

El objetivo es minimizar al m├íximo la intervenci├│n manual del

administrador

de DocTotal.

## 3. Experiencia de usuario

DocTotal debe sentirse como un producto m├®dico profesional y terminado,

no como una aplicaci├│n construida directamente con componentes
est├índar

de Laravel/Livewire.

Debe ser:

-   Visualmente agradable.

-   R├ípido.

-   Claro.

-   Consistente.

-   F├ícil de aprender.

-   C├│modo durante toda la jornada.

-   Optimizado para el flujo real de trabajo del m├®dico.

-   Visualmente identificable como DocTotal.

# Estados

Usar los siguientes estados:

-   `[x]` Implementado.

-   `[~]` Implementado parcialmente / requiere mejora.

-   `[ ]` No implementado.

-   `[!]` Requiere revisi├│n o decisi├│n de producto.

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

-   [x] Resoluci├│n del tenant activo.

-   [x] Protecci├│n contra acceso cruzado.

-   [x] Aislamiento de pacientes.

-   [x] Aislamiento de citas.

-   [x] Aislamiento de consultas.

-   [x] Aislamiento de recetas.

-   [x] Aislamiento de archivos cl├¡nicos.

-   [x] Aislamiento de problemas cl├¡nicos.

-   [x] Cobertura automatizada de tenant isolation.

-   \[!\] Mantener aislamiento como requisito obligatorio para todo

m├│dulo nuevo.

# 2. Autenticaci├│n y seguridad de cuenta

Relacionado principalmente con DT-5.

## Registro y autenticaci├│n

-   [x] Registro de usuario.

-   [x] Creaci├│n autom├ítica del tenant.

-   [x] Asociaci├│n usuario ÔåÆ tenant.

-   [x] Inicio de sesi├│n.

-   [x] Cierre de sesi├│n.

-   [x] Infraestructura Laravel Fortify.

-   [x] Trial creado durante el registro.

-   [x] Pantalla de registro.

-   [x] Pantalla de login.

## Contrase├▒a y recuperaci├│n

-   [x] Backend para reset de contrase├▒a mediante Fortify.

-   [x] Backend para actualizaci├│n de contrase├▒a.

-   \[\~\] Recuperaci├│n de contrase├▒a --- infraestructura existente;

revisar flujo/UI.

-   \[\~\] Cambio de contrase├▒a --- infraestructura existente; revisar
    UI

desde

┬á ┬á ┬á configuraci├│n.

## Seguridad adicional

-   \[\~\] Two-factor authentication --- infraestructura de base de
    datos

existente.

-   \[\~\] Passkeys --- infraestructura de base de datos existente.

-   [ ] Verificar implementaci├│n completa de 2FA.

-   [ ] Verificar implementaci├│n completa de passkeys.

-   [ ] Verificaci├│n de correo.

-   [ ] Administraci├│n visible de sesiones.

-   [ ] Revocaci├│n de sesiones/dispositivos.

-   \[!\] Auditar seguridad completa antes de producci├│n.

# 3. Onboarding

Relacionado principalmente con DT-6.

-   [x] Wizard de onboarding.

-   [x] Paso 1 --- Datos profesionales.

-   [x] Paso 2 --- Datos del consultorio.

-   [x] Paso 3 --- Horarios de atenci├│n.

-   [x] Paso 4 --- Confirmaci├│n.

-   [x] Especialidad.

-   [x] C├®dula profesional.

-   [x] Datos de contacto.

-   [x] Direcci├│n.

-   [x] C├│digo postal.

-   [x] Servicio de c├│digo postal.

-   [x] Autocompletado por c├│digo postal.

-   [x] Horarios de atenci├│n.

-   [x] Duraci├│n predeterminada de citas.

-   [x] `onboarding_completed_at`.

-   [x] Middleware `EnsureOnboardingIsComplete`.

-   [x] Tests del wizard.

-   [x] Tests del middleware.

-   [x] Experiencia visual del onboarding.

-   [ ] Mostrar claramente informaci├│n del periodo de prueba.

-   [x] Registro preparado para promociones y referidos.

-   [x] Captura opcional de c├│digo de referido durante el alta inicial.

-   [x] Aplicaci├│n autom├ítica de c├│digo mediante enlace de referido.

-   \[!\] Revisar qu├® informaci├│n deber├í ser obligatoria antes de

producci├│n.

# 4. Pacientes

Relacionado principalmente con DT-4, DT-7 y DT-14.

Modelo Patient.

Listado de pacientes.

B├║squeda.

Alta.

Edici├│n.

Detalle.

Datos generales.

Fecha de nacimiento.

Edad.

Sexo.

Grupo sangu├¡neo.

Datos de contacto.

Contactos de emergencia.

Modelo PatientEmergencyContact.

Antecedentes m├®dicos.

Modelo PatientMedicalHistory.

Historial de consultas.

Acceso a consultas desde expediente.

Integraci├│n paciente ÔåÆ consulta.

Expediente cl├¡nico longitudinal.

Resumen cl├¡nico del paciente.

L├¡nea de tiempo cl├¡nica unificada.

Vista r├ípida de diagn├│sticos hist├│ricos relevantes.

Vista r├ípida de tratamientos hist├│ricos.

Medicamentos actuales existentes dentro de antecedentes m├®dicos.

Referencias navegables a consultas y recetas originales.

Tests de pacientes.

Tests de contactos de emergencia.

Tests de antecedentes m├®dicos.

Tests espec├¡ficos del expediente longitudinal.

Alertas cl├¡nicas relevantes.

Archivos asociados al paciente.

Estudios.

Laboratorios.

Im├ígenes m├®dicas.

\[!\] Evaluar detecci├│n de pacientes duplicados.

# 5. Expediente cl├¡nico

Relacionado con DT-4, DT-7, DT-9, DT-14, DT-15 y futuros DT.

DT-14 convirti├│ la base cl├¡nica existente en un expediente
longitudinal funcional sin duplicar las fuentes de verdad ya existentes.

DT-15 incorpor├│ la capa documental del expediente sobre storage
privado, manteniendo los metadatos cl├¡nicos separados de los bytes
almacenados.

Antecedentes existentes

Modelo dedicado de antecedentes m├®dicos.

Alergias.

Medicamentos actuales.

Enfermedades cr├│nicas.

Cirug├¡as.

Antecedentes familiares.

Antecedentes personales.

H├íbitos.

Notas adicionales.

Grupo sangu├¡neo.

Edici├│n de antecedentes.

Tests de antecedentes.

Expediente longitudinal implementado en DT-14

Resumen cl├¡nico dentro del expediente del paciente.

L├¡nea de tiempo cl├¡nica unificada.

Consultas finalizadas dentro de la historia cl├¡nica.

Consultas en borrador excluidas del historial oficial.

Diagn├│sticos mostrados en el contexto de su consulta.

Diagn├│sticos hist├│ricos consolidados.

Recetas asociadas mostradas dentro de la consulta correspondiente.

Recetas independientes mostradas como eventos propios.

Prevenci├│n de duplicar una receta vinculada como evento independiente.

Tratamientos hist├│ricos consolidados.

Consolidaci├│n de tratamientos por medicamento + dosis + frecuencia +
duraci├│n.

├Ültima fecha de prescripci├│n disponible por tratamiento consolidado.

Enlaces a la consulta y receta originales.

Orden cronol├│gico descendente.

Protecci├│n multi-tenant.

Proyecci├│n longitudinal construida sobre modelos cl├¡nicos existentes,
sin nueva tabla de eventos.

Expediente documental implementado en DT-15

Modelo ClinicalDocument.

Asociaci├│n documento ÔåÆ paciente.

Asociaci├│n opcional documento ÔåÆ consulta del mismo paciente.

Categor├¡as general, laboratory, imaging y other.

Metadatos separados del archivo f├¡sico.

Fecha cl├¡nica/documental opcional.

Notas y t├¡tulo del documento.

Storage privado mediante abstracci├│n de filesystem.

Carga segura de PDF, JPG, JPEG, PNG y WebP.

L├¡mite actual de 10 MB por archivo.

Visualizaci├│n inline protegida.

Miniatura protegida para im├ígenes.

Representaci├│n visual para PDF.

Descarga segura conservando el nombre original.

Eliminaci├│n controlada de registro y archivo f├¡sico.

Protecci├│n multi-tenant en consulta, visualizaci├│n, descarga y
eliminaci├│n.

Validaci├│n defensiva dentro de StoreClinicalDocument.

Validaci├│n de tenant para paciente y uploader.

Integraci├│n dentro del expediente del paciente.

Cobertura automatizada espec├¡fica.

Evoluci├│n pendiente

\[\~\] Mejorar estructura cl├¡nica de antecedentes.

Hospitalizaciones previas.

Problemas activos.

Alertas cl├¡nicas.

Dominios especializados para resultados estructurados de laboratorio.

Integraci├│n especializada para im├ígenes m├®dicas/DICOM/PACS.

OCR o extracci├│n estructurada de documentos.

\[!\] Definir l├¡mites totales de almacenamiento por tenant.

\[!\] Definir estrategia de respaldo y retenci├│n.

\[!\] Definir pol├¡tica de conservaci├│n documental.

# 6. Agenda

Relacionado principalmente con DT-8.

-   [x] Modelo `Appointment`.

-   [x] Vista mensual.

-   [x] Vista semanal.

-   [x] Vista diaria.

-   [x] Navegaci├│n entre periodos.

-   [x] Crear cita.

-   [x] Crear paciente desde cita.

-   [x] Buscar paciente.

-   [x] Disponibilidad basada en horarios.

-   [x] `AppointmentAvailabilityService`.

-   [x] Excepciones de horario.

-   [x] Bloqueos parciales.

-   [x] Bloqueos completos.

-   [x] Horarios extraordinarios.

-   [x] Prevenci├│n de solapamientos.

-   [x] Eliminaci├│n de slots pasados.

-   [x] Filtrado por estado.

-   [x] B├║squeda desde agenda.

-   [x] Tests de disponibilidad.

-   [x] Tests de creaci├│n.

-   [x] Tests de edici├│n.

-   [x] Tests de reprogramaci├│n.

-   [x] Tests de slots.

-   [x] Tests de vistas de agenda.

-   \[\~\] Experiencia visual del calendario.

-   [ ] Mejorar diferenciaci├│n visual de estados.

-   [ ] Mejorar densidad de informaci├│n.

-   [ ] Optimizar operaci├│n r├ípida desde agenda.

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

-   [x] Integraci├│n Appointment ÔåÆ Consultation.

## No-show

-   [x] Periodo de gracia de 15 minutos.

-   [x] Regla temporal basada en `ends_at + 15 minutos`.

-   [x] No-show nunca completamente autom├ítico.

-   [x] Confirmaci├│n expl├¡cita por usuario.

## Pendiente

-   [ ] Recordatorios de citas.

-   [ ] Confirmaci├│n externa por paciente.

-   [ ] WhatsApp.

-   [ ] SMS.

-   [ ] Correo.

-   \[!\] Definir estrategia de comunicaci├│n con pacientes.

# 8. Consultas

Relacionado principalmente con DT-9.

-   [x] Modelo `Consultation`.

-   [x] Consultation persistente.

-   [x] Estado `draft`.

-   [x] Estado `completed`.

-   [x] Creaci├│n desde Appointment.

-   [x] Una Consultation por Appointment.

-   [x] Continuar Consultation existente.

-   [x] Consulta sin cita.

-   [x] Edici├│n mientras est├í en draft.

-   [x] Finalizaci├│n expl├¡cita.

-   [x] Finalizaci├│n Consultation ÔåÆ Appointment completed.

-   [x] Signos vitales.

-   [x] Peso.

-   [x] Estatura.

-   [x] Presi├│n arterial.

-   [x] Frecuencia cardiaca.

-   [x] Frecuencia respiratoria.

-   [x] Temperatura.

-   [x] Saturaci├│n OÔéé.

-   [x] Motivo de consulta.

-   [x] Nota SOAP.

-   [x] Subjetivo.

-   [x] Objetivo.

-   [x] Evaluaci├│n / diagn├│stico.

-   [x] Plan.

-   [x] Diagn├│sticos asociados.

-   [x] Diagn├│stico principal.

-   [x] Recetas asociadas.

-   [x] Historial de consultas.

-   [x] Tests del modelo.

-   [x] Tests del flujo.

-   [x] Tests del lifecycle.

-   [x] Tests Appointment ÔåÆ Consultation.

-   \[\~\] Experiencia de captura durante consulta.

-   [x] Autosave seguro de consultas draft.

-   [x] Indicador de cambios pendientes / guardando / guardado / error.

-   [ ] Alertas cl├¡nicas visibles durante consulta.

-   [x] Antecedentes relevantes visibles durante consulta.

-   [x] Medicamentos actuales visibles durante consulta.

-   [x] Historial reciente accesible sin abandonar consulta.

-   [x] Problemas cl├¡nicos activos visibles durante consulta --- DT-19.

-   [x] Problemas resueltos excluidos del contexto activo.

-   [x] Protecci├│n contra p├®rdida de cambios con `beforeunload`.

-   [x] Validaci├│n en espa├▒ol y foco autom├ítico al primer error.

-   [x] Finalizaci├│n protegida y revalidada en backend.

-   [ ] Plantillas cl├¡nicas.

-   [ ] Plantillas por especialidad.

-   \[!\] Dise├▒ar consulta como workspace cl├¡nico y no solamente como

formulario.

# 9. Diagn├│sticos

-   [x] Modelo `ConsultationDiagnosis`.

-   [x] Modelo `DiagnosisCatalog`.

-   [x] Cat├ílogo diagn├│stico.

-   [x] Comando `ImportDiagnosisCatalog`.

-   [x] Importaci├│n de cat├ílogo.

-   [x] B├║squeda/autocompletado.

-   [x] Selecci├│n desde cat├ílogo.

-   [x] Diagn├│sticos asociados a Consultation.

-   [x] Diagn├│stico principal.

-   [x] C├│digo diagn├│stico.

-   [x] Descripci├│n.

-   [x] Tests del cat├ílogo.

-   [x] Tests de autocomplete.

-   [x] Tests del flujo de diagn├│sticos.

-   [x] Historial consolidado de diagn├│sticos por paciente.

-   [x] Problemas cl├¡nicos activos estructurados mediante
    `PatientProblem`.

-   [x] Resoluci├│n y reapertura de problemas cl├¡nicos.

-   \[!\] Definir modelo de problemas cl├¡nicos longitudinales.

# 10. Recetas y medicamentos

## Recetas

-   [x] Modelo `Prescription`.

-   [x] Modelo `PrescriptionItem`.

-   [x] Crear receta desde consulta.

-   [x] Asociar receta a Consultation.

-   [x] Medicamentos m├║ltiples.

-   [x] Medicamento.

-   [x] Presentaci├│n.

-   [x] Dosis.

-   [x] Frecuencia.

-   [x] Duraci├│n.

-   [x] Indicaciones.

-   [x] Indicaciones generales.

-   [x] Ver receta.

-   [x] Editar receta.

-   [x] Anular receta.

-   [x] Imprimir.

-   [x] Descargar PDF.

-   [x] Datos del m├®dico.

-   [x] C├®dula profesional.

-   [x] Tests de creaci├│n.

-   [x] Tests de edici├│n.

-   [x] Tests de cancelaci├│n.

-   [x] Tests del modelo.

-   [x] Tests de PDF.

## Cat├ílogo de medicamentos

-   [x] Modelo `MedicationCatalog`.

-   [x] Cat├ílogo de medicamentos.

-   [x] Comando `ImportMedicationCatalog`.

-   [x] Importaci├│n de cat├ílogo.

-   [x] Autocompletado.

-   [x] B├║squeda por informaci├│n del medicamento.

-   [x] Tests de autocomplete.

## Pendiente

-   [ ] Firma digital.

-   [ ] QR/verificaci├│n de receta.

-   [x] Historial longitudinal de medicamentos/tratamientos por
    paciente.

-   [ ] Repetir receta anterior.

-   \[!\] Revisar requisitos legales/documentales antes de producci├│n.

# 11. Archivos cl├¡nicos

Relacionado principalmente con DT-15.

-   [x] Modelo `ClinicalDocument`.

-   [x] UUID para routing.

-   [x] Upload.

-   [x] Visualizaci├│n inline protegida.

-   [x] Descarga segura.

-   [x] Eliminaci├│n controlada.

-   [x] PDF.

-   [x] Im├ígenes JPG/JPEG/PNG/WebP.

-   [x] Categor├¡as general, laboratory, imaging y other.

-   [x] Asociaci├│n con paciente.

-   [x] Asociaci├│n opcional con consulta del mismo paciente.

-   [x] Metadatos cl├¡nicos/documentales.

-   [x] Fecha del estudio/documento.

-   [x] Descripci├│n.

-   [x] Vista previa y miniaturas protegidas para im├ígenes.

-   [x] Seguridad multi-tenant.

-   [x] L├¡mite actual de 10 MB por archivo.

-   [x] Storage privado configurable mediante `CLINICAL_DOCUMENTS_DISK`.

-   [x] Hardening de `StoreClinicalDocument`.

-   [x] Tests espec├¡ficos de almacenamiento, aislamiento,
    visualizaci├│n, descarga y eliminaci├│n.

-   [ ] L├¡mites totales por tenant.

-   [ ] Indicador de almacenamiento utilizado.

-   [ ] Proveedor externo definitivo.

-   [ ] URLs temporales firmadas si fueran necesarias.

-   [ ] Thumbnails derivados para PDF.

-   [ ] OCR/extracci├│n.

-   [ ] DICOM/PACS.

-   [ ] Resultados de laboratorio estructurados.

-   \[!\] Definir pol├¡tica de conservaci├│n, retenci├│n y respaldo.

# 12. Dashboard

Relacionado principalmente con DT-8.

-   [x] Dashboard funcional.

-   [x] Citas de hoy.

-   [x] Pacientes.

-   [x] Citas por atender.

-   [x] Pr├│xima cita.

-   [x] Agenda de hoy.

-   [x] Actividad del d├¡a.

-   [x] Consultas finalizadas.

-   [x] Consultas en progreso.

-   [x] Recetas.

-   [x] Pr├│ximos 7 d├¡as.

-   [x] Estado de agenda.

-   [x] Acciones r├ípidas.

-   [x] Tests del dashboard.

-   \[\~\] Jerarqu├¡a visual.

-   \[\~\] Utilidad cl├¡nica/operativa de algunos indicadores.

-   [ ] Alertas importantes.

-   [ ] Trial / estado de suscripci├│n.

-   [ ] Avisos de pago.

-   [ ] Acciones pendientes.

-   [ ] Pacientes esperando.

-   \[!\] Revisar qu├® informaci├│n necesita realmente el m├®dico al

comenzar el d├¡a.

# 13. Configuraci├│n

## Perfil profesional

-   [x] Nombre.

-   [x] Apellidos.

-   [x] Especialidad.

-   [x] C├®dula.

-   [x] Tel├®fono.

-   [x] WhatsApp.

-   [x] Biograf├¡a.

-   [x] Fotograf├¡a.

-   [x] Firma.

## Consultorio

-   [x] Modelo `PracticeProfile`.

-   [x] Nombre p├║blico.

-   [x] Raz├│n social.

-   [x] RFC.

-   [x] Logo.

-   [x] Tel├®fono.

-   [x] WhatsApp.

-   [x] Correo.

-   [x] Descripci├│n.

-   [x] Direcci├│n.

-   [x] Colonia.

-   [x] C├│digo postal.

-   [x] Ciudad.

-   [x] Estado.

-   [x] Pa├¡s.

## Documentos impresos

-   [x] Configuraci├│n de impresi├│n.

-   [x] Pie de p├ígina.

-   [x] Datos del m├®dico en receta.

-   [x] Datos del consultorio.

## Pendiente

-   [ ] Configuraci├│n de cuenta.

-   [ ] Seguridad.

-   [ ] Cambio de contrase├▒a desde UI.

-   [x] Suscripci├│n.

-   [x] Facturaci├│n.

-   [x] M├®todos de pago.

-   [x] Referidos.

-   [ ] Almacenamiento utilizado.

-   \[!\] Reorganizar configuraci├│n por secciones/pesta├▒as.

# 14. Trial

Existe infraestructura inicial de trial.

## Implementado

-   [x] Campo `status`.

-   [x] Estado inicial `trial`.

-   [x] `trial_started_at`.

-   [x] `trial_ends_at`.

-   [x] Duraci├│n configurable durante registro.

-   [x] Inicializaci├│n autom├ítica durante registro.

-   [x] `Tenant::isOnTrial()`.

-   [x] `Tenant::trialHasExpired()`.

-   [x] Tests de trial.

-   [x] Tests del trial durante registro.

## Pendiente

-   [x] Enforcement de acceso integrado mediante reglas centralizadas

del Tenant.

-   [x] Trial integrado con `Tenant::hasAccessToService()`.

-   [ ] Aviso de d├¡as restantes.

-   [ ] Avisos pr├│ximos al vencimiento.

-   [ ] Pantalla de trial vencido.

-   [x] Conversi├│n trial ÔåÆ suscripci├│n.

-   [x] Selecci├│n mensual/anual.

-   [ ] Bloqueo controlado al vencer.

-   \[!\] Definir qu├® puede hacer el tenant despu├®s del vencimiento.

-   \[!\] Definir si existir├í periodo de gracia.

# 15. Suscripciones

Relacionado principalmente con DT-11 y DT-12.

La infraestructura de suscripciones ya define el periodo de servicio,
ciclos

de facturaci├│n, estados comerciales, derecho de acceso del tenant e
integraci├│n

con billing real mediante Stripe.

## Modelo comercial

-   [x] Modelo `Subscription`.

-   [x] UUID.

-   [x] Soft deletes.

-   [x] Relaci├│n Tenant ÔåÆ subscriptions.

-   [x] Protecci├│n multi-tenant.

-   [x] Ciclo mensual.

-   [x] Ciclo anual.

-   [x] Estado `active`.

-   [x] Estado `past_due`.

-   [x] Estado `cancelled`.

-   [ ] Modelo `Plan` si posteriormente se requieren varios planes.

-   [x] Precio mensual definido: \$600 MXN.

-   [x] Precio anual definido: \$6,000 MXN.

-   [x] Moneda comercial almacenada por suscripci├│n.

## Periodo de servicio

-   [x] `starts_at`.

-   [x] `current_period_starts_at`.

-   [x] `current_period_ends_at`.

-   [x] `next_billing_at`.

-   [x] `cancel_at_period_end`.

-   [x] `cancelled_at`.

-   [x] Primer pago como billing anchor.

-   [x] Conservaci├│n de fecha y hora del primer pago.

-   [x] Conservaci├│n de minutos y segundos.

-   [x] Manejo de fin de mes sin overflow.

-   [x] Manejo de a├▒os bisiestos.

-   [x] Protecci├│n contra billing drift en renovaciones.

## Operaciones

-   [x] Alta de suscripci├│n.

-   [x] Activaci├│n del tenant al crear suscripci├│n.

-   [x] Renovaci├│n mensual.

-   [x] Renovaci├│n anual.

-   [x] Cancelaci├│n programada al final del periodo.

-   [x] Reanudaci├│n antes del vencimiento.

-   [x] Procesamiento al final exacto del periodo.

-   [x] Transici├│n `active ÔåÆ past_due`.

-   [x] Transici├│n `past_due ÔåÆ active`.

-   [x] `cancelled` como estado terminal actual.

-   [x] Prevenci├│n de segunda Subscription `active`.

-   [x] Prevenci├│n de segunda Subscription cuando existe `past_due`.

-   [x] Consulta de suscripci├│n vigente del tenant.

-   [x] Derecho de acceso centralizado.

-   [x] Acceso durante `past_due` mientras el tenant no est├®
    suspendido.

-   [x] Suspensi├│n del Tenant independiente del estado de Subscription.

-   [x] Conversi├│n trial ÔåÆ suscripci├│n desde UI.

-   [x] Cambio mensual Ôåö anual programado al siguiente periodo.

-   [x] Historial comercial visible para el usuario.

-   [ ] Upgrade/downgrade si posteriormente existen varios planes.

## Calidad

-   [x] Tests del modelo Subscription.

-   [x] Tests de activaci├│n.

-   [x] Tests de renovaci├│n.

-   [x] Tests de billing anchor.

-   [x] Tests de fin de mes.

-   [x] Tests de a├▒o bisiesto.

-   [x] Tests de cancelaci├│n programada.

-   [x] Tests de reanudaci├│n.

-   [x] Tests de transiciones.

-   [x] Tests de acceso del Tenant.

-   [x] Tests de expiraci├│n/procesamiento de periodo.

-   [x] Tests de cambio de plan.

## Decisiones resueltas / pendientes

-   [x] Proveedor de pagos definido: Stripe.

-   [x] Precio mensual definido: \$600 MXN.

-   [x] Precio anual definido: \$6,000 MXN.

-   [x] Descuento anual definido: equivalente a 2 meses sin costo frente
    al plan mensual.

-   [x] Regla base de cancelaci├│n definida: cancelaci├│n al final del
    periodo con posibilidad de conservar la suscripci├│n antes del
    vencimiento.

-   \[!\] Definir reglas de reembolso.

# 16. Pagos y facturaci├│n SaaS

Relacionado principalmente con DT-12.

DT-12 implement├│ la foundation operativa de pagos y recuperaci├│n SaaS
sobre

la Subscription construida en DT-11. Stripe qued├│ integrado y validado
en modo

de prueba. Los cobros autom├íticos permanecen protegidos por feature
flag hasta

su activaci├│n controlada en el entorno objetivo.

-   [x] Modelo `Payment`.

-   [x] Registro de pagos.

-   [x] Fecha de intento, pago y fallo.

-   [x] Importe.

-   [x] Moneda.

-   [x] M├®todo de pago Stripe.

-   [x] Estados `pending`, `succeeded`, `failed` y `canceled`.

-   [x] Referencia del proveedor.

-   [x] Pago exitoso.

-   [x] Pago pendiente.

-   [x] Pago fallido.

-   [x] Reintentos programados.

-   [x] Renovaci├│n autom├ítica implementada y protegida por feature
    flag.

-   [x] Vencimiento y recuperaci├│n `past_due`.

-   [x] Periodo de gracia de 7 d├¡as.

-   [x] Suspensi├│n autom├ítica por falta de pago al vencer el grace
    period.

-   [x] Reactivaci├│n despu├®s del pago.

-   [x] Recuperaci├│n autom├ítica mediante tarjeta guardada.

-   [x] Recuperaci├│n manual mediante PaymentIntent.

-   [x] Historial de pagos visible.

-   [x] M├®todos de pago guardados.

-   [x] Alta, actualizaci├│n y eliminaci├│n del m├®todo de pago Stripe.

-   [x] Idempotencia de intentos de renovaci├│n, recuperaci├│n y
    checkout manual.

-   [x] Integraci├│n de descuentos y cr├®ditos promocionales con
    billing.

-   [x] Reserva idempotente de cr├®ditos promocionales.

-   [x] Consumo de cr├®ditos ├║nicamente despu├®s de pago exitoso.

-   [x] Liberaci├│n de cr├®ditos despu├®s de pago fallido o checkout
    cancelado.

-   [x] Prevenci├│n de checkouts manuales pendientes duplicados.

-   [x] Cambio de plan con cancelaci├│n segura del checkout anterior.

-   [x] Limpieza autom├ítica de checkouts manuales abandonados.

-   [x] Reconciliaci├│n segura de checkouts cuyo PaymentIntent ya fue
    cobrado en Stripe.

-   [x] Scheduler para renovaciones, reintentos, cancelaciones, grace
    periods vencidos y limpieza de checkouts abandonados.

-   [x] Stripe test mode validado end-to-end.

-   [ ] Recibos/comprobantes.

-   [ ] Webhooks.

-   [ ] Idempotencia de webhooks.

-   [ ] Auditor├¡a formal de eventos de pago.

-   \[!\] Definir facturaci├│n fiscal de DocTotal.

-   [x] Proveedor de pagos definido: Stripe.

# 17. Ciclo de vida del tenant

Actualmente existe una parte importante de la automatizaci├│n comercial
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

ÔåÆ active

Problema de pago:

active

ÔåÆ past_due

ÔåÆ suspended

ÔåÆ active

Trial sin conversi├│n:

trial

ÔåÆ expired

Cancelaci├│n:

active

ÔåÆ cancelled

ÔåÆ deletion_pending

ÔåÆ deleted

## Estado actual

-   [x] Estados comerciales b├ísicos definidos entre Tenant y
    Subscription.

-   [x] Transiciones base de billing automatizadas.

-   [x] Reglas de acceso centralizadas mediante el Tenant.

-   [x] Suspensi├│n autom├ítica al vencer el periodo de gracia.

-   [x] Reactivaci├│n autom├ítica despu├®s de recuperar el pago.

-   [x] Cancelaci├│n programada al final del periodo.

-   [ ] Eliminaci├│n programada.

-   [ ] Recuperaci├│n antes de eliminaci├│n.

-   [x] Scheduler SaaS para billing.

-   \[\~\] Procesamiento programado implementado mediante comandos;
    queues/jobs dedicados quedan para necesidades futuras.

-   [ ] Auditor├¡a formal de transiciones.

-   \[!\] Definir pol├¡tica de conservaci├│n de expedientes despu├®s de
    cancelar.

-   [x] Comportamiento de acceso en estado `past_due`: acceso mientras
    el tenant no est├® suspendido.

-   [x] Comportamiento de acceso en estado `suspended`: sin acceso al
    servicio.

# 18. Referidos y promociones

Relacionado principalmente con DT-13.

DT-13 implement├│ el programa de referidos y la foundation de cr├®ditos

promocionales integrada con el lifecycle de billing.

## C├│digo de referido

-   [x] C├│digo ├║nico por tenant.

-   [x] Generaci├│n autom├ítica.

-   [x] C├│digo permanente.

-   [x] ├ìndice UNIQUE en base de datos.

-   [x] Backfill de c├│digos para tenants existentes.

-   [x] Pantalla para consultar c├│digo.

-   [x] Enlace de referido.

-   [x] Acci├│n para copiar/compartir referencia.

-   [x] Validaci├│n del c├│digo.

-   [x] Identificaci├│n del tenant referente.

## Uso durante registro

-   [x] Un tenant nuevo puede utilizar como m├íximo un c├│digo de
    referido.

-   [x] Captura del c├│digo durante la inscripci├│n inicial.

-   [x] Aplicaci├│n autom├ítica mediante par├ímetro `ref`.

-   [x] Validaci├│n de c├│digos ingresados manualmente.

-   [x] Asociaci├│n permanente entre referidor y referido.

-   [x] Referencia inicialmente en estado `pending`.

-   [x] Un tenant no puede referirse a s├¡ mismo.

-   [x] Prevenci├│n de atribuciones duplicadas.

-   [x] El registro por s├¡ solo no genera recompensa.

-   [x] La referencia califica ├║nicamente con el primer pago exitoso.

## L├¡mite promocional mensual

-   [x] M├íximo de 5 recompensas para el referidor por mes calendario.

-   [x] M├íximo mensual actual de \$250 MXN.

-   [x] El periodo se determina por la fecha del primer pago exitoso del
    referido.

-   [x] Conteo de referencias calificadas durante el periodo.

-   [x] Reinicio l├│gico al comenzar un nuevo mes.

-   [x] Registro de referencias que generaron recompensa.

-   [x] Registro de referencias que alcanzaron el l├¡mite mensual.

-   [x] El sexto referido y posteriores no generan cr├®dito adicional
    para el referidor.

-   [x] El l├¡mite del referidor no elimina el beneficio propio del
    referido.

## Beneficio del referido

-   [x] Descuento ├║nico de \$50 MXN.

-   [x] Aplicaci├│n sobre el primer pago elegible.

-   [x] Plan mensual: \$600 MXN ÔåÆ \$550 MXN.

-   [x] Plan anual: \$6,000 MXN ÔåÆ \$5,950 MXN.

-   [x] Beneficio independiente del l├¡mite mensual del referidor.

-   [x] Prevenci├│n de doble descuento.

## Cr├®dito del referidor

-   [x] Cr├®dito de \$50 MXN por referido calificado.

-   [x] Modelo `PromotionalCredit`.

-   [x] Estados `available`, `reserved` y `consumed`.

-   [x] Cr├®dito sin caducidad.

-   [x] Aplicaci├│n autom├ítica al siguiente pago elegible.

-   [x] Compatible con pago manual.

-   [x] Compatible con renovaci├│n autom├ítica.

-   [x] Reserva idempotente antes del intento de cobro.

-   [x] Consumo ├║nicamente despu├®s de pago exitoso.

-   [x] Liberaci├│n despu├®s de pago fallido.

-   [x] Liberaci├│n despu├®s de checkout cancelado.

-   [x] Reutilizaci├│n del cr├®dito despu├®s de liberarlo.

-   [x] Protecci├│n para evitar importes de cobro inv├ílidos.

-   [x] Trazabilidad entre cr├®dito, referencia y pago.

## Checkout y promociones

-   [x] Desglose de importe bruto, descuento de referido y cr├®dito
    promocional.

-   [x] Prevenci├│n de m├║ltiples checkouts manuales pendientes para el
    mismo tenant.

-   [x] Reutilizaci├│n idempotente del checkout pendiente.

-   [x] Cambio mensual Ôåö anual cancelando primero el checkout
    anterior.

-   [x] Cancelaci├│n del PaymentIntent de Stripe al abandonar un
    checkout.

-   [x] Estado `canceled` para pagos abandonados.

-   [x] Limpieza autom├ítica de checkouts expirados.

-   [x] Expiraci├│n configurable de checkout manual.

-   [x] Reconciliaci├│n de PaymentIntent `succeeded` cuando el Payment
    local contin├║a `pending`.

-   [x] Scheduler horario para limpieza de checkouts abandonados.

## Calidad

-   [x] Tests de modelos y relaciones.

-   [x] Tests de generaci├│n y unicidad de c├│digos.

-   [x] Tests de atribuci├│n.

-   [x] Tests de auto-referido y atribuci├│n duplicada.

-   [x] Tests de calificaci├│n por primer pago exitoso.

-   [x] Tests de descuento mensual y anual.

-   [x] Tests de recompensa del referidor.

-   [x] Tests del l├¡mite mensual.

-   [x] Tests de idempotencia.

-   [x] Tests de reserva, consumo y liberaci├│n de cr├®ditos.

-   [x] Tests de integraci├│n con billing.

-   [x] Tests de checkout abandonado.

-   [x] Tests de reconciliaci├│n con Stripe.

-   [x] Tests del comando de limpieza.

-   [x] Suite completa sin regresiones.

## Pendiente futuro

-   [ ] Auditor├¡a administrativa/comercial avanzada de promociones.

-   [ ] Herramientas administrativas para consultar y gestionar
    referidos.

-   \[!\] Definir comportamiento comercial ante reembolsos futuros.

# 19. Comunicaciones

Relacionado principalmente con DT-20.

DT-20 construy├│ la foundation transaccional multi-tenant de
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

-   [x] Conteo de intentos, ├║ltimo error y motivo de cancelaci├│n.

-   [x] `idempotency_key` ├║nico por tenant.

-   [x] Historial persistente y auditable.

## Transportes y procesamiento

-   [x] Contrato `CommunicationTransport`.

-   [x] `CommunicationTransportManager`.

-   [x] Canales preparados: email, WhatsApp y SMS.

-   [x] Configuraci├│n independiente por canal.

-   [x] Ausencia segura de proveedor configurado.

-   [x] Sin transport no se simula un env├¡o exitoso.

-   [x] Sin transport no se consume intento.

-   [x] `CommunicationProcessor`.

-   [x] M├íximo actual de 3 intentos.

-   [x] Backoff de 5 y 15 minutos.

-   [x] `communications:process-due`.

-   [x] Procesamiento aislado por tenant.

-   [x] Scheduler de procesamiento.

## Recordatorios de citas

-   [x] `AppointmentReminderService`.

-   [x] Recordatorios para citas futuras `scheduled` y `confirmed`.

-   [x] Programaci├│n ideal 24 horas antes.

-   [x] Si la ventana ideal ya pas├│, queda elegible para env├¡o
    inmediato.

-   [x] Idempotencia por appointment UUID + canal + timestamp de cita.

-   [x] Una reprogramaci├│n genera una nueva identidad de recordatorio.

-   [x] Citas canceladas no generan recordatorio nuevo.

-   [x] Falta de contacto requerido omite la generaci├│n.

-   [x] `communications:generate-appointment-reminders`.

-   [x] Canal y ventana futura configurables.

-   [x] Scheduler horario de generaci├│n.

## Protecci├│n contra recordatorios obsoletos

-   [x] `AppointmentReminderValidator`.

-   [x] Validaci├│n antes del procesamiento.

-   [x] Cancelaci├│n de recordatorios de citas que dejaron de ser
    elegibles.

-   [x] Cancelaci├│n del recordatorio anterior despu├®s de reprogramar.

-   [x] Comparaci├│n contra `appointment_starts_at` persistido en
    metadata.

-   [x] Cancelaci├│n sin consumir intento.

-   [x] Conservaci├│n del motivo para auditor├¡a.

## Integraci├│n visual

-   [x] Historial de comunicaciones dentro del detalle de cita.

-   [x] Estado, canal, destinatario y tipo.

-   [x] Fechas de creaci├│n, programaci├│n y env├¡o.

-   [x] Intentos y pr├│ximo intento.

-   [x] Fecha y motivo de cancelaci├│n.

-   [x] ├Ültimo error.

-   [x] Empty state.

## Pendiente futuro

-   [ ] Proveedor real de correo.

-   [ ] Proveedor real de WhatsApp.

-   [ ] Proveedor real de SMS.

-   [ ] Confirmaci├│n externa por paciente.

-   [ ] Avisos autom├íticos de cancelaci├│n y reprogramaci├│n.

-   [ ] Comunicaciones de trial y billing.

-   [ ] Preferencias/consentimiento por canal.

-   \[!\] Evaluar hardening adicional de concurrencia para despliegues
    distribuidos.

## Decisiones

La arquitectura no se acopla a un proveedor espec├¡fico.

La ausencia de proveedor nunca debe marcar una comunicaci├│n como
enviada.

Los recordatorios obsoletos se cancelan y conservan como historial.

Las comunicaciones transaccionales se mantienen separadas de campa├▒as
de marketing o env├¡os masivos.

# 20. Dise├▒o y experiencia visual

Esta ├írea debe tratarse como una l├¡nea formal de desarrollo y no como

una

serie de retoques aislados.

La interfaz actual es funcional y consistente, pero todav├¡a transmite

claramente la estructura visual de una aplicaci├│n Laravel/Livewire.

El objetivo no es solamente "hacerla bonita".

El objetivo es crear una identidad propia de DocTotal y optimizar cada

pantalla para el trabajo real del m├®dico.

## DocTotal Design System

-   [x] Identidad visual definitiva.

-   [x] Paleta de colores.

-   \[\~\] Tipograf├¡a.

-   [x] Escala tipogr├ífica.

-   [x] Sistema de espaciado.

-   [x] Sistema de tama├▒os.

-   [x] Iconograf├¡a.

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

-   [x] Estados de ├®xito.

-   [x] Navegaci├│n.

-   [x] Sidebar.

-   [x] Header.

-   [ ] Breadcrumbs.

-   [x] Responsive.

-   \[\~\] Accesibilidad b├ísica.

## Auditor├¡a visual actual

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

-   [x] Configuraci├│n revisada.

-   [x] Login revisado.

-   [x] Registro revisado.

-   [x] Onboarding completo revisado.

## Problemas detectados

-   [x] Existe consistencia visual b├ísica.

-   [x] La interfaz es limpia.

-   [x] La interfaz es funcional.

-   \[!\] Exceso de cards visualmente similares.

-   \[!\] Jerarqu├¡a visual limitada.

-   \[!\] Escasa diferenciaci├│n entre m├│dulos.

-   \[!\] Demasiada dependencia del patr├│n visual de formulario.

-   [x] Sidebar redise├▒ado con identidad propia de DocTotal.

-   [x] Identidad visual propia de DocTotal incorporada.

-   \[!\] El espacio disponible no siempre se aprovecha correctamente.

-   \[!\] Acciones cl├¡nicas importantes podr├¡an destacar mejor.

-   \[!\] El producto todav├¡a transmite sensaci├│n de aplicaci├│n en

desarrollo.

-   \[!\] Consulta cl├¡nica parece formulario y no workspace m├®dico.

-   \[!\] Expediente todav├¡a no comunica suficientemente la historia
    del

paciente.

# 21. Redise├▒o por m├│dulo

No iniciar redise├▒os aislados antes de definir las bases del Design

System.

## Login

-   [x] Redise├▒ar login.

-   [x] Incorporar identidad DocTotal.

-   [x] Mejorar percepci├│n de confianza.

-   [x] Mejorar estados de error.

## Registro

-   [x] Redise├▒ar registro.

-   [ ] Mejorar presentaci├│n del trial.

-   [x] Incorporar c├│digo de referido.

-   [x] Mejorar explicaci├│n de creaci├│n del consultorio.

## Onboarding

-   [x] Redise├▒ar wizard.

-   [x] Mejorar indicador de progreso.

-   [x] Reducir sensaci├│n de formulario administrativo.

-   [x] Mejorar selecci├│n de horarios.

-   [x] Mejorar pantalla final.

## Dashboard

-   [ ] Redise├▒ar jerarqu├¡a.

-   [ ] Priorizar operaci├│n del d├¡a.

-   [ ] Mejorar agenda del d├¡a.

-   [ ] Mejorar indicadores.

-   [ ] Incorporar alertas.

-   [ ] Incorporar estado de cuenta/trial cuando corresponda.

## Pacientes

-   [x] Mejorar listado.

-   [x] Mejorar b├║squeda.

-   [x] Mejorar acciones r├ípidas.

-   [x] Mejorar lectura de informaci├│n importante.

## Expediente

-   [x] Redise├▒ar como expediente cl├¡nico longitudinal.

-   [x] Resumen del paciente.

-   [x] Alertas.

-   [x] Antecedentes.

-   [x] Timeline.

-   [x] Consultas.

-   [x] Diagn├│sticos.

-   [x] Medicamentos.

-   [x] Recetas.

-   [x] Archivos.

## Agenda

-   [ ] Mejorar calendario.

-   [x] Mejorar representaci├│n de citas.

-   [x] Diferenciar estados.

-   [x] Mejorar acciones r├ípidas.

-   [ ] Optimizar vista diaria.

-   [ ] Optimizar vista semanal.

-   [x] Mejorar navegaci├│n temporal.

## Consulta

-   [ ] Convertir en workspace cl├¡nico.

-   [ ] Mejor distribuci├│n de informaci├│n.

-   [ ] Reducir navegaci├│n innecesaria.

-   [ ] Mostrar contexto cl├¡nico del paciente.

-   [ ] Mostrar alergias relevantes.

-   [ ] Mostrar medicamentos actuales.

-   [ ] Mostrar diagn├│sticos importantes.

-   [ ] Mejorar captura SOAP.

-   [ ] Mejorar diagn├│sticos.

-   [ ] Mejorar recetas.

-   [ ] Preparar autosave.

## Recetas

-   [x] Mejorar captura.

-   [x] Mejorar b├║squeda de medicamentos.

-   [x] Mejorar visualizaci├│n.

-   [x] Mejorar documento final.

-   [ ] Facilitar repetir tratamientos.

## Configuraci├│n

-   [ ] Dividir por secciones.

-   [x] Perfil.

-   [x] Consultorio.

-   [x] Agenda.

-   [x] Documentos.

-   [ ] Cuenta.

-   [ ] Seguridad.

-   [x] Suscripci├│n y pagos.

-   [x] Referidos.

# 22. Auditor├¡a, privacidad y seguridad

DT-21 incorpor├│ la foundation formal de auditor├¡a sensible de
DocTotal. El alcance actual es deliberadamente limitado y extensible.

## Foundation implementada en DT-21

-   [x] Modelo `AuditEvent`.

-   [x] Persistencia multi-tenant mediante `BelongsToTenant`.

-   [x] Actor opcional mediante `user_id`.

-   [x] Asociaci├│n polim├│rfica con el recurso auditado.

-   [x] Acci├│n, descripci├│n, timestamps, IP y user agent.

-   [x] Metadata controlada.

-   [x] Sanitizaci├│n recursiva de metadata sensible.

-   [x] Redacci├│n de variantes de password, token, authorization,
    cookie, secret y api_key.

-   [x] `AuditLogger`.

-   [x] `safeLog()` best-effort para no romper la operaci├│n principal
    cuando falla la persistencia de auditor├¡a.

-   [x] Registro t├®cnico de fallos de auditor├¡a mediante logging de
    Laravel.

-   [x] Protecci├│n append-only a nivel Eloquent para update/delete
    normales.

-   [x] Aislamiento de eventos entre tenants.

-   [x] Tests de integridad, aislamiento, actor, recurso, metadata y
    fallos.

## Flujos sensibles auditados actualmente

-   [x] Actualizaci├│n de datos generales del paciente.

-   [x] Finalizaci├│n de consulta.

-   [x] Reprogramaci├│n de cita.

-   [x] Cancelaci├│n de cita.

-   \[\~\] Historial de cambios cl├¡nicos: foundation disponible; no se
    auditan todav├¡a todas las mutaciones cl├¡nicas.

-   \[\~\] Historial de cambios de citas: reprogramaci├│n y cancelaci├│n
    auditadas; otras transiciones pueden incorporarse posteriormente.

-   [ ] Historial de cambios de recetas.

-   [ ] Historial de suscripci├│n.

-   [ ] Historial de pagos.

-   [ ] Eventos administrativos internos.

## Integraci├│n visual

-   [x] Historial de actividad dentro del expediente del paciente.

-   [x] Actor, descripci├│n y fecha/hora.

-   [x] Paginaci├│n de 5 eventos.

-   [x] Empty state.

-   [x] Detalles t├®cnicos y metadata no expuestos en la tarjeta visual.

-   \[!\] La tarjeta del paciente muestra eventos cuyo recurso auditado
    es Patient; no mezcla autom├íticamente eventos de Consultation o
    Appointment.

## Seguridad y privacidad pendiente

-   [x] Protecci├│n base de archivos cl├¡nicos.

-   \[\~\] Protecci├│n de informaci├│n sensible en metadata de
    auditor├¡a.

-   [ ] Revisi├│n integral de autorizaci├│n.

-   [ ] Revisi├│n integral de validaciones.

-   [ ] Rate limiting.

-   [ ] Pol├¡tica de contrase├▒as.

-   [ ] Auditor├¡a de 2FA/passkeys.

-   [ ] Administraci├│n y revocaci├│n de sesiones/dispositivos.

-   [ ] Backups.

-   [ ] Restauraci├│n.

-   [ ] Logging estructurado de producci├│n.

-   [ ] Monitoreo de errores.

-   [ ] Pol├¡tica de retenci├│n.

-   [ ] Pol├¡tica de eliminaci├│n.

-   [ ] Inmutabilidad de auditor├¡a garantizada a nivel de base de
    datos.

-   [ ] Outbox/transacci├│n durable para garantizar persistencia de
    auditor├¡a ante fallos de infraestructura.

-   \[!\] Revisi├│n integral antes de manejar informaci├│n real de
    pacientes.

Decisiones:

La auditor├¡a actual es best-effort. Una falla al persistir un
`AuditEvent` no debe cambiar el resultado funcional de la operaci├│n
principal.

La protecci├│n append-only de DT-21 existe a nivel del modelo Eloquent y
no debe documentarse como garant├¡a de inmutabilidad de base de datos.

La metadata de auditor├¡a debe mantenerse m├¡nima y evitar secretos,
tokens, contrase├▒as y payload cl├¡nico innecesario.

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

# 24. Infraestructura y operaci├│n t├®cnica

Existe infraestructura base de Laravel para cache/jobs y DT-12
incorpor├│

scheduler operativo para billing. Todav├¡a falta definir la operaci├│n
completa

de producci├│n.

-   [x] Tabla de jobs.

-   [x] Tabla de cache.

-   [ ] Configurar queue de producci├│n.

-   \[\~\] Procesos SaaS de billing implementados mediante comandos
    programados.

-   [x] Scheduler para procesos SaaS de billing.

-   [x] Procesamiento de renovaciones.

-   [x] Procesamiento de reintentos de pago.

-   [x] Procesamiento de cancelaciones programadas.

-   [x] Procesamiento de suspensiones por grace period vencido.

-   [x] Limpieza autom├ítica de checkouts manuales abandonados.

-   [x] Reconciliaci├│n segura de checkouts manuales ya cobrados en
    Stripe.

-   [ ] Procesamiento de eliminaciones.

-   [x] Procesamiento programado de comunicaciones transaccionales.

-   [x] Generaci├│n programada de recordatorios de citas.

-   \[\~\] Env├¡o real pendiente de transports/proveedores.

-   [ ] Monitoreo de queues.

-   [ ] Manejo de failed jobs.

-   [ ] Logging estructurado.

-   [ ] Error tracking.

-   [ ] Backups autom├íticos.

-   [ ] Monitoreo de aplicaci├│n.

-   [ ] Health checks.

-   \[!\] Definir infraestructura de producci├│n.

# 25. Calidad y tests

La aplicaci├│n cuenta con una suite automatizada considerable.

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

Normalizaci├│n documental integrada en master.

Baseline heredado: 840 tests verdes / 0 failures.

Cierre DT-19:

854 tests verdes.

0 failures.

Cierre t├®cnico DT-20:

910 tests verdes.

0 failures.

Assertions finales de DT-20 no registradas; no se infieren.

Cierre t├®cnico DT-21:

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

-   [x] Tests de autenticaci├│n.

-   [x] Tests de registro.

-   [x] Tests de trial.

-   [x] Tests de multi-tenancy.

-   [x] Tests de resoluci├│n de tenant.

-   [x] Tests de modelos base.

-   [x] Tests de onboarding.

-   [x] Tests de c├│digo postal.

-   [x] Tests de pacientes.

-   [x] Tests de antecedentes.

-   [x] Tests de contactos de emergencia.

-   [x] Tests de agenda.

-   [x] Tests de appointments.

-   [x] Tests de disponibilidad.

-   [x] Tests de slots.

-   [x] Tests de estados.

-   [x] Tests de reprogramaci├│n.

-   [x] Tests Appointment ÔåÆ Consultation.

-   [x] Tests de Consultation.

-   [x] Tests de lifecycle cl├¡nico.

-   [x] Tests de diagn├│sticos.

-   [x] Tests del cat├ílogo diagn├│stico.

-   [x] Tests de autocomplete diagn├│stico.

-   [x] Tests de recetas.

-   [x] Tests de medicamentos.

-   [x] Tests de autocomplete de medicamentos.

-   [x] Tests de PDF.

-   [x] Tests del dashboard.

-   [x] Tests de suscripciones.

-   [x] Tests de pagos.

-   [x] Tests de recuperaci├│n de pagos.

-   [x] Tests de cancelaci├│n y reanudaci├│n.

-   [x] Tests del ciclo de vida comercial y acceso del tenant.

-   [x] Tests de comandos programados de billing.

-   [x] Tests de referidos.

-   [x] Tests de promociones.

-   [x] Tests de cr├®ditos promocionales.

-   [x] Tests de checkout manual y abandono.

-   [x] Tests de reconciliaci├│n de pagos manuales.

Tests de l├¡nea de tiempo cl├¡nica longitudinal.

Tests de diagn├│sticos hist├│ricos consolidados.

Tests de tratamientos hist├│ricos consolidados.

Tests de integraci├│n del expediente longitudinal en la vista del
paciente.

## Pendiente futuro

-   [x] Baseline actualizado al cierre de DT-16.

-   [x] Tests de archivos.

-   [x] Tests de almacenamiento.

-   [x] Tests del workspace cl├¡nico y autosave.

-   [x] Tests de problemas cl├¡nicos longitudinales.

-   [x] Tests Livewire de CRUD, resoluci├│n, reapertura y soft delete.

-   [x] Tests de problemas activos dentro del contexto de consulta.

-   [ ] Tests de webhooks.

-   [x] Tests de comunicaciones transaccionales y recordatorios.

-   [x] Tests de idempotencia, transports, reintentos y recordatorios
    obsoletos.

-   [x] Tests de foundation de auditor├¡a, aislamiento, integridad y
    redacci├│n.

-   [x] Tests de fiabilidad best-effort de auditor├¡a.

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

-   [x] Base cl├¡nica.

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

-   [x] Perfil m├®dico.

-   [x] Consultorio.

-   [x] Horarios.

-   [x] C├│digo postal.

## DT-7 --- Gesti├│n de pacientes

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

-   [x] Appointment ÔåÆ Consultation.

Baseline al cierre:

366 tests verdes.

## DT-9 --- Consultation workflow and clinical lifecycle

Estado: Completado.

-   [x] Consultation persistente.

-   [x] Draft.

-   [x] Completed.

-   [x] Lifecycle.

-   [x] SOAP.

-   [x] Diagn├│sticos.

-   [x] Cat├ílogo diagn├│stico.

-   [x] Recetas.

-   [x] Cat├ílogo de medicamentos.

-   [x] PDF.

## DT-10 --- Product inventory and development roadmap

Estado: Completado.

Objetivo:

Crear un mapa maestro del producto que permita conocer qu├® existe, qu├®

est├í

incompleto, qu├® falta y qu├® debe desarrollarse despu├®s.

Incluye:

-   [x] Inventario funcional.

-   [x] Inventario cl├¡nico.

-   [x] Inventario de pacientes.

-   [x] Inventario de agenda.

-   [x] Inventario de consultas.

-   [x] Inventario de diagn├│sticos.

-   [x] Inventario de medicamentos.

-   [x] Inventario de recetas.

-   [x] Inventario de archivos pendientes.

-   [x] Inventario SaaS.

-   [x] Inventario de trial.

-   [x] Inventario de billing.

-   [x] Inventario de lifecycle del tenant.

-   [x] Definici├│n inicial de referidos/promociones.

-   [x] Inventario visual.

-   [x] Auditor├¡a de pantallas existentes.

-   [x] Separaci├│n de los tres pilares del producto.

-   [x] Validaci├│n final contra `app/`.

-   [x] Validaci├│n final contra migraciones.

-   [x] Validaci├│n final contra tests.

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

-   [x] Relaci├│n Tenant ÔåÆ subscriptions.

-   [x] Protecci├│n multi-tenant.

-   [x] Ciclos mensual y anual.

-   [x] Estados `active`, `past_due` y `cancelled`.

-   [x] Billing anchor basado en fecha y hora del primer pago.

-   [x] Renovaci├│n mensual y anual sin billing drift.

-   [x] Manejo de fin de mes.

-   [x] Manejo de a├▒os bisiestos.

-   [x] Cancelaci├│n programada al final del periodo.

-   [x] Procesamiento de expiraci├│n del periodo.

-   [x] Transiciones de estado.

-   [x] Reglas centralizadas de acceso del tenant.

-   [x] Acceso durante recuperaci├│n `past_due`.

-   [x] Suspensi├│n del Tenant separada del estado de Subscription.

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

con m├¡nima intervenci├│n manual.

Incluye:

-   [x] Stripe como proveedor de pagos.

-   [x] Precios mensual (\$600 MXN) y anual (\$6,000 MXN).

-   [x] Modelo `Payment` y lifecycle `pending` / `succeeded` / `failed`.

-   [x] Stripe Customer y m├®todos de pago guardados.

-   [x] SetupIntent para alta/actualizaci├│n de tarjeta.

-   [x] Checkout manual para conversi├│n a suscripci├│n.

-   [x] Ciclos mensual y anual.

-   [x] Cambio mensual Ôåö anual diferido al siguiente periodo.

-   [x] Renovaciones autom├íticas.

-   [x] Recuperaci├│n `past_due`.

-   [x] Reintentos programados con idempotencia por episodio de
    recuperaci├│n.

-   [x] Grace period de 7 d├¡as.

-   [x] Suspensi├│n autom├ítica al vencer el grace period.

-   [x] Reactivaci├│n despu├®s del pago.

-   [x] Recuperaci├│n manual con tarjeta guardada o tarjeta alternativa.

-   [x] Cancelaci├│n programada al final del periodo.

-   [x] Reanudaci├│n antes del vencimiento.

-   [x] Protecci├│n contra renovaci├│n/cobro cuando existe cancelaci├│n
    programada.

-   [x] Historial de pagos y estado de suscripci├│n en UI.

-   [x] Scheduler para renovaciones, retries, cancelaciones y grace
    periods vencidos.

-   [x] Feature flag `BILLING_AUTOMATIC_CHARGING_ENABLED`.

-   [x] Hardening de estados l├¡mite, multi-tenancy e idempotencia.

-   [x] Cobertura automatizada de billing y lifecycle.

Pendiente fuera del alcance de DT-12:

-   [ ] Webhooks Stripe.

-   [ ] Recibos/comprobantes.

-   [ ] Facturaci├│n fiscal.

-   [ ] Auditor├¡a formal de eventos de pago.

-   [ ] Activaci├│n operativa de cobros autom├íticos en el entorno
    objetivo.

Nota operativa:

Mantener `BILLING_AUTOMATIC_CHARGING_ENABLED=false` hasta realizar una

activaci├│n controlada de cobros autom├íticos en el entorno
correspondiente.

Baseline al cierre:

696 tests verdes.

0 failures.

## DT-13 --- Referral program and promotional credits

Estado: Completado.

Objetivo:

Implementar el programa de referidos de DocTotal y los cr├®ditos
promocionales,

integr├índolos de forma segura con el lifecycle comercial y de billing.

Incluye:

-   [x] C├│digo ├║nico y permanente por tenant.

-   [x] Enlace de referido.

-   [x] Captura y validaci├│n durante registro.

-   [x] Prevenci├│n de auto-referidos y atribuciones duplicadas.

-   [x] Modelo `Referral`.

-   [x] Calificaci├│n mediante primer pago exitoso.

-   [x] Descuento ├║nico de \$50 MXN para el referido.

-   [x] Cr├®dito de \$50 MXN para el referidor.

-   [x] M├íximo de 5 recompensas / \$250 MXN por mes calendario.

-   [x] Modelo `PromotionalCredit`.

-   [x] Estados `available`, `reserved` y `consumed`.

-   [x] Reserva, consumo y liberaci├│n idempotentes.

-   [x] Integraci├│n con pagos manuales.

-   [x] Integraci├│n con renovaciones autom├íticas.

-   [x] Integraci├│n con recuperaci├│n de pagos.

-   [x] Desglose promocional visible en Billing.

-   [x] Prevenci├│n de checkouts manuales pendientes duplicados.

-   [x] Cambio de plan con cancelaci├│n segura del checkout anterior.

-   [x] Limpieza autom├ítica de checkouts abandonados.

-   [x] Reconciliaci├│n segura cuando Stripe ya reporta un PaymentIntent
    como `succeeded`.

-   [x] Scheduler horario para mantenimiento de checkouts.

-   [x] Cobertura automatizada completa.

Baseline al cierre:

797 tests verdes.

2244 assertions.

0 failures.

## DT-14 --- Expediente cl├¡nico longitudinal

Estado: Completado.

Objetivo:

Convertir la informaci├│n cl├¡nica existente del paciente en una
historia longitudinal coherente, reutilizando las entidades cl├¡nicas ya
existentes y evitando estructuras duplicadas.

Incluye:

Auditor├¡a previa de modelos, migraciones, vistas y tests cl├¡nicos
existentes.

Evoluci├│n de patients.show como expediente longitudinal principal.

Resumen cl├¡nico basado en PatientMedicalHistory.

L├¡nea de tiempo cl├¡nica unificada.

Solo consultas completed dentro del historial oficial.

Consultas draft mantenidas fuera de la historia cl├¡nica final.

Diagn├│sticos en contexto de consulta.

Diagn├│sticos hist├│ricos consolidados.

Prescripciones ligadas a consulta dentro del evento de consulta.

Prescripciones independientes como eventos propios.

Prevenci├│n de duplicados de recetas vinculadas.

Tratamientos hist├│ricos consolidados por esquema cl├¡nico.

Referencias a las entidades originales.

Protecci├│n multi-tenant.

Cobertura automatizada espec├¡fica.

Integraci├│n sin regresiones con pacientes, consultas y recetas.

Decisiones de dise├▒o:

No crear una tabla adicional de timeline/eventos en DT-14.

Construir la historia longitudinal como proyecci├│n de lectura sobre las
fuentes cl├¡nicas existentes.

Mantener medicamentos actuales reportados en antecedentes separados de
los tratamientos hist├│ricos prescritos.

Mantener cada ocurrencia original visible en la l├¡nea de tiempo aunque
los res├║menes consoliden informaci├│n.

Baseline al cierre:

814 tests verdes.

2339 assertions.

0 failures.

DT-15 --- Clinical files and medical documents

Estado: Completado.

Objetivo:

Incorporar archivos y documentos cl├¡nicos al expediente del paciente
sobre una base segura, multi-tenant y extensible, sin duplicar
responsabilidades del expediente longitudinal construido en DT-14.

Incluye:

Auditor├¡a de uploads, filesystem, storage y rutas existentes.

Modelo ClinicalDocument.

Migraci├│n y relaciones con Patient y Consultation.

Asociaci├│n obligatoria con paciente.

Asociaci├│n opcional con consulta del mismo paciente.

Categor├¡as general, laboratorio, imagen y otros.

Storage privado configurable mediante CLINICAL_DOCUMENTS_DISK.

Metadata separada del archivo f├¡sico.

Upload seguro.

L├¡mite de 10 MB por archivo.

PDF, JPG, JPEG, PNG y WebP.

Visualizaci├│n inline protegida.

Miniaturas protegidas para im├ígenes.

Descarga segura.

Eliminaci├│n controlada.

Integraci├│n en el expediente del paciente.

Protecci├│n multi-tenant.

Hardening de StoreClinicalDocument.

Correcci├│n de prioridad de middleware para resolver tenant antes del
route model binding.

Cobertura automatizada espec├¡fica.

Validaci├│n manual de upload, miniatura, visualizaci├│n, descarga y
eliminaci├│n.

Fuera de alcance / pendiente posterior:

L├¡mites totales de almacenamiento por tenant.

OCR/extracci├│n.

DICOM/PACS.

Interpretaci├│n o estructura avanzada de laboratorios.

Thumbnails derivados de PDF.

Pol├¡tica definitiva de retenci├│n y respaldo.

Baseline al cierre:

837 tests verdes.

2395 assertions.

0 failures.

## DT-16 --- Visual redesign / DocTotal UI

Estado: Completado.

Objetivo:

Transformar la interfaz funcional de DocTotal en una experiencia visual
propia, moderna, profesional, consistente y responsive, preservando los
workflows funcionales y la l├│gica de negocio existente.

Incluye:

-   [x] Foundation visual y design tokens.

-   [x] Identidad y branding de DocTotal.

-   [x] Shell principal, sidebar, header y navegaci├│n responsive.

-   [x] Redise├▒o visual de pacientes y expediente longitudinal.

-   [x] Redise├▒o visual de citas, consultas y recetas.

-   [x] Redise├▒o visual de onboarding y configuraci├│n.

-   [x] Redise├▒o de login y registro.

-   [x] Recuperaci├│n y restablecimiento de contrase├▒a con UI propia.

-   [x] Correo personalizado de restablecimiento de contrase├▒a.

-   [x] Landing p├║blica de DocTotal.

-   [x] Branding, logo y favicons.

-   [x] Revisi├│n de receta imprimible y PDF.

-   [x] Flash messages integrados visualmente.

-   [x] Auditor├¡a visual final.

-   [x] Suite completa sin regresiones.

Baseline al cierre:

837 tests verdes.

0 failures.

## DT-17 --- Clinical workspace / Consulta m├®dica avanzada

Estado: Completado.

Objetivo:

Transformar la consulta m├®dica en un workspace cl├¡nico avanzado,
manteniendo visible el contexto longitudinal del paciente y protegiendo
la captura cl├¡nica durante toda la atenci├│n.

Incluye:

-   [x] Workspace cl├¡nico responsive.

-   [x] Panel persistente de contexto cl├¡nico.

-   [x] Alergias, medicamentos actuales, enfermedades cr├│nicas,
    cirug├¡as y antecedentes relevantes.

-   [x] Consultas finalizadas recientes y diagn├│sticos en contexto.

-   [x] Autosave de consultas draft.

-   [x] Estados de guardado visibles.

-   [x] Protecci├│n `beforeunload` frente a cambios pendientes.

-   [x] Validaci├│n en espa├▒ol.

-   [x] Resaltado de campos inv├ílidos.

-   [x] Scroll y foco al primer error.

-   [x] Protecci├│n de finalizaci├│n cuando existen cambios pendientes,
    guardado en curso o errores.

-   [x] Revalidaci├│n backend antes de completar.

-   [x] Appointment completado ├║nicamente despu├®s de finalizar
    correctamente Consultation.

-   [x] Cobertura automatizada espec├¡fica.

Decisiones:

`PatientMedicalHistory` contin├║a siendo la fuente expl├¡cita de
alergias, medicamentos actuales y antecedentes.

Las prescripciones hist├│ricas no se interpretan autom├íticamente como
medicaci├│n actual.

Los rangos de signos vitales implementados son l├¡mites t├®cnicos de
validaci├│n y no constituyen decisi├│n cl├¡nica.

Baseline al cierre:

840 tests verdes.

0 failures.

Commit:

`ff7aee4 DT-17 feat: implement advanced clinical consultation workspace`

## DT-18 --- Documentation baseline normalization

Estado: Completado.

Objetivo:

Normalizar `TODO.md` y `ROADMAP.md` contra el estado real del producto
despu├®s del cierre funcional de DT-17.

Incluye:

-   [x] Revisi├│n del baseline documental.

-   [x] Sincronizaci├│n de avances funcionales previos.

-   [x] Recalculo ponderado del avance global.

-   [x] Baseline establecido en 72%.

-   [x] Integraci├│n en `master`.

Baseline heredado:

840 tests verdes.

0 failures.

Avance global ponderado:

72%.

## DT-19 --- Structured active clinical problem list

Estado: Completado.

Objetivo:

Implementar una lista estructurada y longitudinal de problemas cl├¡nicos
por paciente como evoluci├│n natural del expediente y del workspace
cl├¡nico.

Incluye:

-   [x] Modelo `PatientProblem`.

-   [x] Persistencia multi-tenant.

-   [x] Soft deletes.

-   [x] Estados `active` y `resolved`.

-   [x] C├│digo opcional.

-   [x] Descripci├│n.

-   [x] Fecha de inicio.

-   [x] Fecha de resoluci├│n.

-   [x] Notas.

-   [x] Relaci├│n Patient ÔåÆ problems.

-   [x] CRUD dentro del expediente.

-   [x] Resolver y reabrir problemas.

-   [x] Autocomplete opcional desde `DiagnosisCatalog`.

-   [x] Captura manual preservada.

-   [x] Problemas activos visibles durante consulta.

-   [x] Problemas resueltos conservados en el expediente longitudinal.

-   [x] Problemas resueltos excluidos del contexto activo de consulta.

-   [x] Protecci├│n contra manipulaci├│n desde otro paciente.

-   [x] Aislamiento multi-tenant.

-   [x] Cobertura automatizada de modelo, flujo Livewire, expediente y
    contexto de consulta.

Decisiones:

`PatientProblem` es una entidad longitudinal expl├¡cita.

No se infiere autom├íticamente desde `ConsultationDiagnosis`.

`DiagnosisCatalog` se reutiliza ├║nicamente como ayuda de captura.

`PatientMedicalHistory` contin├║a siendo fuente de alergias,
medicamentos actuales y antecedentes.

No se agreg├│ UUID porque `PatientProblem` no tiene routing p├║blico
independiente.

Fuera de alcance:

-   [ ] Alertas cl├¡nicas autom├íticas.

-   [ ] Inferencia autom├ítica desde diagn├│sticos hist├│ricos.

-   [ ] Interacciones farmacol├│gicas.

-   [ ] OCR.

-   [ ] Laboratorios estructurados.

-   [ ] DICOM/PACS.

-   [ ] Comunicaciones externas.

Calidad:

13 tests verdes en `PatientProblemTest` + `PatientProblemFlowTest`.

57 tests verdes en Patients.

76 tests verdes en Consultations.

147 tests verdes en la regresi├│n combinada de DT-19.

Suite completa:

854 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre t├®cnico:

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
recordatorios de citas sin acoplar DocTotal a un proveedor espec├¡fico.

Incluye:

-   [x] Modelo `Communication`.

-   [x] Relaciones con Patient y Appointment.

-   [x] Estados `pending`, `sent`, `failed` y `cancelled`.

-   [x] Snapshot de destinatario y contenido.

-   [x] Idempotencia por tenant.

-   [x] `CommunicationTransport`.

-   [x] `CommunicationTransportManager`.

-   [x] Configuraci├│n independiente por email, WhatsApp y SMS.

-   [x] `CommunicationProcessor`.

-   [x] Reintentos con backoff y m├íximo de 3 intentos.

-   [x] Manejo seguro cuando no existe transport configurado.

-   [x] `AppointmentReminderService`.

-   [x] Generaci├│n idempotente de recordatorios.

-   [x] Comandos de generaci├│n y procesamiento.

-   [x] Scheduler.

-   [x] `AppointmentReminderValidator`.

-   [x] Cancelaci├│n auditable de recordatorios obsoletos.

-   [x] Protecci├│n ante cancelaci├│n, finalizaci├│n, no-show y
    reprogramaci├│n.

-   [x] Historial visual de comunicaciones en el detalle de la cita.

-   [x] Protecci├│n multi-tenant.

-   [x] Cobertura automatizada.

Validaci├│n:

56 tests verdes en la regresi├│n espec├¡fica de DT-20.

148 tests verdes en la regresi├│n de appointments despu├®s de la
integraci├│n visual.

Suite completa:

910 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre t├®cnico:

77%.

Fuera de alcance:

-   [ ] Campa├▒as de marketing y env├¡os masivos.

-   [ ] Proveedores reales obligatorios en este DT.

-   [ ] Confirmaci├│n externa por paciente.

-   [ ] Alertas cl├¡nicas e inferencia m├®dica autom├ítica.

Cierre definitivo:

-   [x] Commit final DT-20.

-   [x] Merge a `master`.

-   [x] Comentario t├®cnico de cierre en Jira.

-   [x] Transici├│n de DT-20 a `Listo`.

Commit principal:

`9192020 DT-20 feat: implement transactional communications and appointment reminders`

## DT-21 --- Audit trail and security hardening foundation

Estado: Completado.

Objetivo:

Construir una foundation reusable de auditor├¡a y hardening para
acciones sensibles, con aislamiento multi-tenant, trazabilidad y
metadata controlada, sin acoplar los modelos cl├¡nicos a una
implementaci├│n espec├¡fica de logging.

Incluye:

-   [x] Modelo `AuditEvent`.

-   [x] Migraci├│n e ├¡ndices de auditor├¡a.

-   [x] `BelongsToTenant` y aislamiento por tenant.

-   [x] Actor opcional.

-   [x] Recurso polim├│rfico auditable.

-   [x] `AuditLogger`.

-   [x] Sanitizaci├│n recursiva de metadata sensible.

-   [x] `safeLog()` best-effort.

-   [x] Logging t├®cnico cuando la auditor├¡a falla.

-   [x] Protecci├│n append-only a nivel Eloquent.

-   [x] Auditor├¡a de `patient.updated`.

-   [x] Auditor├¡a de `consultation.completed`.

-   [x] Auditor├¡a de `appointment.rescheduled`.

-   [x] Auditor├¡a de `appointment.cancelled`.

-   [x] Historial visual de actividad en expediente de paciente.

-   [x] Paginaci├│n de 5 eventos.

-   [x] Cobertura automatizada.

Decisiones:

La operaci├│n principal no debe fallar ├║nicamente porque no pudo
persistirse el evento de auditor├¡a.

La protecci├│n append-only actual es de modelo Eloquent, no una
garant├¡a de inmutabilidad a nivel de base de datos.

La metadata debe ser m├¡nima y no duplicar contenido cl├¡nico sensible.

Validaci├│n:

58 tests verdes en la regresi├│n focalizada de DT-21.

13 tests verdes en la regresi├│n del historial visual/paginaci├│n.

11 tests verdes en AppointmentShow despu├®s del ajuste final a
`safeLog()`.

Suite completa final:

936 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

Avance global ponderado al cierre t├®cnico:

79%.

Fuera de alcance:

-   [ ] SIEM completo.

-   [ ] Auditor├¡a exhaustiva de lecturas.

-   [ ] Backups/restauraci├│n integral.

-   [ ] 2FA obligatorio.

-   [ ] Passkeys.

-   [ ] Gesti├│n avanzada de dispositivos.

-   [ ] Retenci├│n legal definitiva.

-   [ ] Inmutabilidad garantizada por base de datos.

-   [ ] Outbox transaccional de auditor├¡a.

Cierre definitivo:

-   [x] Commit final DT-21.

-   [x] Merge a `master`.

-   [x] Comentario t├®cnico de cierre en Jira.

-   [x] Transici├│n de DT-21 a `Listo`.

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

# 28. Deuda y decisiones de producto

Estas decisiones no necesariamente requieren un DT inmediato, pero deben

resolverse antes de los m├│dulos que dependan de ellas.

## Comercial

-   [x] Precio mensual: \$600 MXN.

-   [x] Precio anual: \$6,000 MXN.

-   [x] Descuento anual: 2 meses equivalentes sin costo frente al
    mensual.

-   \[!\] Duraci├│n definitiva del trial.

-   [x] Periodo de gracia de billing: 7 d├¡as.

-   [x] Pol├¡tica base de cancelaci├│n: al final del periodo; reversible
    antes del vencimiento.

-   \[!\] Pol├¡tica de reembolso.

## Referidos

-   [x] Beneficio para tenant nuevo: \$50 MXN de descuento en el primer
    pago.

-   [x] Beneficio para referente: \$50 MXN de cr├®dito.

-   [x] N├║mero de promociones mensuales: m├íximo 5 recompensas.

-   [x] Cr├®dito m├íximo generado por mes: \$250 MXN.

-   [x] Requisito de primer pago exitoso.

-   [x] Cr├®dito promocional sin caducidad.

-   [x] Aplicaci├│n a mensualidad.

-   [x] Aplicaci├│n a anualidad.

-   \[!\] Definir comportamiento ante reembolsos futuros.

## Infraestructura

-   [x] Proveedor de pagos: Stripe.

-   \[\~\] Storage privado local implementado; proveedor externo
    pendiente de decisi├│n operativa.

-   \[!\] Proveedor de correo.

-   \[!\] Proveedor de WhatsApp.

-   \[!\] Proveedor de SMS.

## Cl├¡nica

-   \[!\] Estructura de problemas activos.

-   \[!\] Pol├¡tica de modificaci├│n de informaci├│n cl├¡nica
    finalizada.

-   \[\~\] Pol├¡tica t├®cnica base de archivos implementada en DT-15;
    retenci├│n y cuotas pendientes.

-   \[!\] Retenci├│n de expedientes.

-   \[!\] Requisitos legales de recetas/documentos.

## UX

-   [x] Identidad visual definitiva.

-   [x] Direcci├│n visual del Design System.

-   \[!\] Estructura definitiva del workspace cl├¡nico.

# 29. Regla para decidir el siguiente DT

Al terminar cada DT:

1.  Ejecutar suite completa de tests.

2.  Confirmar que todos los tests est├®n verdes.

3.  Registrar n├║mero de tests.

4.  Registrar n├║mero de assertions.

5.  Actualizar `ROADMAP.md`.

6.  Actualizar `TODO.md`.

7.  Marcar funcionalidades terminadas.

8.  Registrar nuevas necesidades descubiertas.

9.  Revisar pendientes cr├¡ticos.

10. Revisar dependencias entre m├│dulos.

11. Elegir el siguiente DT.

12. Definir objetivo.

13. Definir alcance.

14. Definir expl├¡citamente qu├® queda fuera.

15. Crear branch.

16. Comenzar implementaci├│n.

# 30. Regla de cierre de un DT

Un DT no debe considerarse terminado ├║nicamente porque "funciona".

Antes de cerrarlo debe comprobarse:

-   [x] Objetivo cumplido.

-   [x] Alcance implementado.

-   [x] Multi-tenancy protegido.

-   [x] Validaciones implementadas.

-   [x] Estados l├¡mite contemplados.

-   [x] Tests agregados.

-   [x] Suite completa verde.

-   [x] UI funcional.

-   [x] Integraci├│n con m├│dulos existentes comprobada.

-   [x] TODO actualizado.

-   [x] ROADMAP actualizado.

-   [x] Baseline registrado.

-   [ ] Commit final realizado.

# 31. Principio de desarrollo

No desarrollar una funci├│n solamente porque "hace falta".

Antes de comenzar un nuevo DT debemos responder:

1.  ┬┐Qu├® problema resuelve?

2.  ┬┐Para qui├®n lo resuelve?

3.  ┬┐Qu├® m├│dulos existentes afecta?

4.  ┬┐Qu├® informaci├│n necesita?

5.  ┬┐Qu├® informaci├│n genera?

6.  ┬┐C├│mo afecta al aislamiento multi-tenant?

7.  ┬┐C├│mo afecta al expediente cl├¡nico?

8.  ┬┐C├│mo afecta al ciclo SaaS?

9.  ┬┐C├│mo debe verse y sentirse para el usuario?

10. ┬┐C├│mo vamos a probarlo?

DocTotal debe crecer como un producto integrado, no como una colecci├│n

de

pantallas y funciones independientes.

# Baseline actual al cierre técnico de DT-22

Avance global ponderado vigente:

79%.

Este es el último porcentaje formalmente calculado. No se infiere un
porcentaje nuevo sin aplicar nuevamente el criterio ponderado del
producto.

Suite completa final:

988 tests verdes.

0 failures.

Assertions finales no registradas; no se infieren.

DT-21 está completado e integrado.

DT-22 tiene cierre técnico completado. Pendiente: commit documental
final, push, PR, merge y cierre Jira.

# 32. Visi├│n de producto

El objetivo final no es construir solamente un sistema de expedientes

m├®dicos.

DocTotal debe permitir que un m├®dico pueda:

Registrarse

ÔåÆ configurar su consultorio

ÔåÆ comenzar su periodo de prueba

ÔåÆ administrar pacientes

ÔåÆ organizar su agenda

ÔåÆ atender citas

ÔåÆ documentar consultas

ÔåÆ consultar el historial cl├¡nico

ÔåÆ diagnosticar

ÔåÆ recetar

ÔåÆ almacenar estudios y documentos

ÔåÆ continuar el seguimiento del paciente

mientras DocTotal autom├íticamente:

controla el trial

ÔåÆ convierte a suscripci├│n

ÔåÆ cobra

ÔåÆ renueva

ÔåÆ detecta pagos fallidos

ÔåÆ aplica periodos de gracia

ÔåÆ suspende cuando corresponda

ÔåÆ reactiva despu├®s del pago

ÔåÆ administra promociones

ÔåÆ registra referidos

ÔåÆ controla l├¡mites

ÔåÆ conserva la informaci├│n

ÔåÆ mantiene aislados los tenants.

Todo esto debe presentarse mediante una experiencia visual propia,

profesional, agradable y dise├▒ada espec├¡ficamente para el trabajo

m├®dico.

------------------------------------------------------------------------

## Cola priorizada de próximos bloques

> Orden acordado después de DT-21. Al cerrar cada bloque se continuará
> con el siguiente pendiente, salvo que se decida explícitamente cambiar
> la prioridad.

1.  🏥 **Panel administrativo interno SaaS --- DT-22 CIERRE TÉCNICO
    COMPLETADO** Consola interna implementada y validada con suite
    completa verde.

2.  📱 **Interacción del paciente con citas --- SIGUIENTE PRIORIDAD**
    Confirmación, cancelación y solicitud de reprogramación mediante
    enlaces seguros, integrados con la infraestructura de
    comunicaciones.

3.  📋 **Plantillas clínicas** Plantillas reutilizables para consultas y
    base para plantillas por especialidad.

4.  🧪 **Laboratorios estructurados** Estudios, resultados estructurados
    e historial longitudinal, sin interpretación clínica automática.

5.  🔐 **Seguridad de cuenta** 2FA, passkeys, verificación de correo,
    sesiones activas y revocación de sesiones/dispositivos.

6.  💊 **Repetición de tratamientos/recetas** Crear nuevas recetas a
    partir de tratamientos anteriores conservando inmutable el
    historial.
