# DocTotal — TODO

## Progreso general

**45% completado**

`█████████░░░░░░░░░░░` 45%

> El porcentaje representa avance global del producto, no cobertura de tests.
> Se actualizará al cierre de cada DT.

Este documento representa el estado funcional actual de DocTotal y sirve como
mapa maestro para decidir los siguientes bloques de desarrollo (DT).

No sustituye al Roadmap.

- `ROADMAP.md` registra los bloques DT realizados, su objetivo y su cierre.
- `TODO.md` registra qué existe actualmente, qué está incompleto, qué falta y
  qué decisiones de producto siguen pendientes.

Este documento debe actualizarse al finalizar cada DT antes de seleccionar
el siguiente bloque de desarrollo.

---

# Objetivo del producto

DocTotal debe convertirse en una plataforma SaaS para médicos y consultorios
que cubra tres pilares principales.

## 1. Operación médica

Permitir administrar la operación diaria y clínica del consultorio:

- Pacientes.
- Expedientes clínicos.
- Agenda.
- Citas.
- Consultas.
- Diagnósticos.
- Recetas.
- Archivos clínicos.
- Estudios.
- Laboratorios.
- Imágenes médicas.
- Historial longitudinal del paciente.

## 2. Autoadministración SaaS

DocTotal debe administrar automáticamente el ciclo comercial y operativo de
cada tenant:

- Registro.
- Periodo de prueba.
- Suscripción.
- Mensualidades.
- Anualidades.
- Pagos.
- Renovaciones.
- Vencimientos.
- Pagos fallidos.
- Suspensiones.
- Reactivaciones.
- Cancelaciones.
- Eliminación programada.
- Referidos.
- Promociones.

El objetivo es minimizar al máximo la intervención manual del administrador
de DocTotal.

## 3. Experiencia de usuario

DocTotal debe sentirse como un producto médico profesional y terminado,
no como una aplicación construida directamente con componentes estándar
de Laravel/Livewire.

Debe ser:

- Visualmente agradable.
- Rápido.
- Claro.
- Consistente.
- Fácil de aprender.
- Cómodo durante toda la jornada.
- Optimizado para el flujo real de trabajo del médico.
- Visualmente identificable como DocTotal.

---

# Estados

Usar los siguientes estados:

- `[x]` Implementado.
- `[~]` Implementado parcialmente / requiere mejora.
- `[ ]` No implementado.
- `[!]` Requiere revisión o decisión de producto.

---

# 1. Arquitectura y multi-tenancy

Relacionado principalmente con DT-1, DT-2 y DT-3.

- [x] Estructura base multi-tenant.
- [x] Tenant como unidad principal de aislamiento.
- [x] Modelo `Tenant`.
- [x] Modelos Eloquent principales.
- [x] Relaciones base.
- [x] `TenantContext`.
- [x] `TenantScope`.
- [x] Trait `BelongsToTenant`.
- [x] Middleware `ResolveTenant`.
- [x] Resolución del tenant activo.
- [x] Protección contra acceso cruzado.
- [x] Aislamiento de pacientes.
- [x] Aislamiento de citas.
- [x] Aislamiento de consultas.
- [x] Aislamiento de recetas.
- [x] Cobertura automatizada de tenant isolation.
- [!] Mantener aislamiento como requisito obligatorio para todo módulo nuevo.

---

# 2. Autenticación y seguridad de cuenta

Relacionado principalmente con DT-5.

## Registro y autenticación

- [x] Registro de usuario.
- [x] Creación automática del tenant.
- [x] Asociación usuario → tenant.
- [x] Inicio de sesión.
- [x] Cierre de sesión.
- [x] Infraestructura Laravel Fortify.
- [x] Trial creado durante el registro.
- [x] Pantalla de registro.
- [x] Pantalla de login.

## Contraseña y recuperación

- [x] Backend para reset de contraseña mediante Fortify.
- [x] Backend para actualización de contraseña.
- [~] Recuperación de contraseña — infraestructura existente; revisar flujo/UI.
- [~] Cambio de contraseña — infraestructura existente; revisar UI desde
      configuración.

## Seguridad adicional

- [~] Two-factor authentication — infraestructura de base de datos existente.
- [~] Passkeys — infraestructura de base de datos existente.
- [ ] Verificar implementación completa de 2FA.
- [ ] Verificar implementación completa de passkeys.
- [ ] Verificación de correo.
- [ ] Administración visible de sesiones.
- [ ] Revocación de sesiones/dispositivos.
- [!] Auditar seguridad completa antes de producción.

---

# 3. Onboarding

Relacionado principalmente con DT-6.

- [x] Wizard de onboarding.
- [x] Paso 1 — Datos profesionales.
- [x] Paso 2 — Datos del consultorio.
- [x] Paso 3 — Horarios de atención.
- [x] Paso 4 — Confirmación.
- [x] Especialidad.
- [x] Cédula profesional.
- [x] Datos de contacto.
- [x] Dirección.
- [x] Código postal.
- [x] Servicio de código postal.
- [x] Autocompletado por código postal.
- [x] Horarios de atención.
- [x] Duración predeterminada de citas.
- [x] `onboarding_completed_at`.
- [x] Middleware `EnsureOnboardingIsComplete`.
- [x] Tests del wizard.
- [x] Tests del middleware.
- [~] Experiencia visual del onboarding.
- [ ] Mostrar claramente información del periodo de prueba.
- [ ] Preparar onboarding/registro para promociones y referidos.
- [!] Revisar qué información deberá ser obligatoria antes de producción.

---

# 4. Pacientes

Relacionado principalmente con DT-4 y DT-7.

- [x] Modelo `Patient`.
- [x] Listado de pacientes.
- [x] Búsqueda.
- [x] Alta.
- [x] Edición.
- [x] Detalle.
- [x] Datos generales.
- [x] Fecha de nacimiento.
- [x] Edad.
- [x] Sexo.
- [x] Grupo sanguíneo.
- [x] Datos de contacto.
- [x] Contactos de emergencia.
- [x] Modelo `PatientEmergencyContact`.
- [x] Antecedentes médicos.
- [x] Modelo `PatientMedicalHistory`.
- [x] Historial de consultas.
- [x] Acceso a consultas desde expediente.
- [x] Integración paciente → consulta.
- [x] Tests de pacientes.
- [x] Tests de contactos de emergencia.
- [x] Tests de antecedentes médicos.
- [~] Expediente clínico longitudinal.
- [ ] Resumen clínico del paciente.
- [ ] Línea de tiempo clínica unificada.
- [ ] Vista rápida de diagnósticos relevantes.
- [ ] Vista rápida de medicamentos actuales.
- [ ] Alertas clínicas relevantes.
- [ ] Archivos asociados al paciente.
- [ ] Estudios.
- [ ] Laboratorios.
- [ ] Imágenes médicas.
- [!] Evaluar detección de pacientes duplicados.

---

# 5. Expediente clínico

Relacionado con DT-4, DT-7, DT-9 y futuros DT.

Actualmente existe una base funcional del expediente clínico.

No debe considerarse como un módulo inexistente.

## Antecedentes existentes

- [x] Modelo dedicado de antecedentes médicos.
- [x] Alergias.
- [x] Medicamentos actuales.
- [x] Enfermedades crónicas.
- [x] Cirugías.
- [x] Antecedentes familiares.
- [x] Antecedentes personales.
- [x] Hábitos.
- [x] Notas adicionales.
- [x] Grupo sanguíneo.
- [x] Edición de antecedentes.
- [x] Tests de antecedentes.

## Evolución pendiente

- [~] Mejorar estructura clínica de antecedentes.
- [ ] Hospitalizaciones previas.
- [ ] Resumen clínico.
- [ ] Problemas activos.
- [ ] Diagnósticos históricos consolidados.
- [ ] Medicamentos históricos consolidados.
- [ ] Línea de tiempo clínica.
- [ ] Alertas clínicas.
- [ ] Archivos clínicos.
- [ ] Estudios de laboratorio.
- [ ] Estudios de imagen.
- [ ] Otros documentos médicos.
- [ ] Asociación archivo → consulta.
- [ ] Asociación archivo → paciente.
- [ ] Descarga segura de archivos.
- [ ] Eliminación controlada.
- [!] Definir política de almacenamiento.
- [!] Definir límites de almacenamiento por tenant.
- [!] Definir estrategia de respaldo y retención.

---

# 6. Agenda

Relacionado principalmente con DT-8.

- [x] Modelo `Appointment`.
- [x] Vista mensual.
- [x] Vista semanal.
- [x] Vista diaria.
- [x] Navegación entre periodos.
- [x] Crear cita.
- [x] Crear paciente desde cita.
- [x] Buscar paciente.
- [x] Disponibilidad basada en horarios.
- [x] `AppointmentAvailabilityService`.
- [x] Excepciones de horario.
- [x] Bloqueos parciales.
- [x] Bloqueos completos.
- [x] Horarios extraordinarios.
- [x] Prevención de solapamientos.
- [x] Eliminación de slots pasados.
- [x] Filtrado por estado.
- [x] Búsqueda desde agenda.
- [x] Tests de disponibilidad.
- [x] Tests de creación.
- [x] Tests de edición.
- [x] Tests de reprogramación.
- [x] Tests de slots.
- [x] Tests de vistas de agenda.
- [~] Experiencia visual del calendario.
- [ ] Mejorar diferenciación visual de estados.
- [ ] Mejorar densidad de información.
- [ ] Optimizar operación rápida desde agenda.
- [!] Evaluar acciones mediante popover/modal sin abandonar calendario.

---

# 7. Ciclo de citas

Relacionado principalmente con DT-8.

## Estados actuales

- [x] `scheduled`.
- [x] `confirmed`.
- [x] `checked_in`.
- [x] `in_progress`.
- [x] `completed`.
- [x] `cancelled`.
- [x] `no_show`.

## Operaciones

- [x] Programar.
- [x] Confirmar.
- [x] Check-in.
- [x] Iniciar consulta.
- [x] Completar.
- [x] Cancelar.
- [x] Reprogramar.
- [x] Editar motivo.
- [x] Editar notas.
- [x] Continuar consulta en progreso.
- [x] Integración Appointment → Consultation.

## No-show

- [x] Periodo de gracia de 15 minutos.
- [x] Regla temporal basada en `ends_at + 15 minutos`.
- [x] No-show nunca completamente automático.
- [x] Confirmación explícita por usuario.

## Pendiente

- [ ] Recordatorios de citas.
- [ ] Confirmación externa por paciente.
- [ ] WhatsApp.
- [ ] SMS.
- [ ] Correo.
- [!] Definir estrategia de comunicación con pacientes.

---

# 8. Consultas

Relacionado principalmente con DT-9.

- [x] Modelo `Consultation`.
- [x] Consultation persistente.
- [x] Estado `draft`.
- [x] Estado `completed`.
- [x] Creación desde Appointment.
- [x] Una Consultation por Appointment.
- [x] Continuar Consultation existente.
- [x] Consulta sin cita.
- [x] Edición mientras está en draft.
- [x] Finalización explícita.
- [x] Finalización Consultation → Appointment completed.
- [x] Signos vitales.
- [x] Peso.
- [x] Estatura.
- [x] Presión arterial.
- [x] Frecuencia cardiaca.
- [x] Frecuencia respiratoria.
- [x] Temperatura.
- [x] Saturación O₂.
- [x] Motivo de consulta.
- [x] Nota SOAP.
- [x] Subjetivo.
- [x] Objetivo.
- [x] Evaluación / diagnóstico.
- [x] Plan.
- [x] Diagnósticos asociados.
- [x] Diagnóstico principal.
- [x] Recetas asociadas.
- [x] Historial de consultas.
- [x] Tests del modelo.
- [x] Tests del flujo.
- [x] Tests del lifecycle.
- [x] Tests Appointment → Consultation.
- [~] Experiencia de captura durante consulta.
- [ ] Autosave.
- [ ] Indicador de cambios sin guardar.
- [ ] Alertas clínicas visibles durante consulta.
- [ ] Antecedentes relevantes visibles durante consulta.
- [ ] Medicamentos actuales visibles durante consulta.
- [ ] Historial reciente accesible sin abandonar consulta.
- [ ] Plantillas clínicas.
- [ ] Plantillas por especialidad.
- [!] Diseñar consulta como workspace clínico y no solamente como formulario.

---

# 9. Diagnósticos

- [x] Modelo `ConsultationDiagnosis`.
- [x] Modelo `DiagnosisCatalog`.
- [x] Catálogo diagnóstico.
- [x] Comando `ImportDiagnosisCatalog`.
- [x] Importación de catálogo.
- [x] Búsqueda/autocompletado.
- [x] Selección desde catálogo.
- [x] Diagnósticos asociados a Consultation.
- [x] Diagnóstico principal.
- [x] Código diagnóstico.
- [x] Descripción.
- [x] Tests del catálogo.
- [x] Tests de autocomplete.
- [x] Tests del flujo de diagnósticos.
- [ ] Historial consolidado de diagnósticos por paciente.
- [ ] Problemas clínicos activos.
- [ ] Resolución/cierre de problemas.
- [!] Definir modelo de problemas clínicos longitudinales.

---

# 10. Recetas y medicamentos

## Recetas

- [x] Modelo `Prescription`.
- [x] Modelo `PrescriptionItem`.
- [x] Crear receta desde consulta.
- [x] Asociar receta a Consultation.
- [x] Medicamentos múltiples.
- [x] Medicamento.
- [x] Presentación.
- [x] Dosis.
- [x] Frecuencia.
- [x] Duración.
- [x] Indicaciones.
- [x] Indicaciones generales.
- [x] Ver receta.
- [x] Editar receta.
- [x] Anular receta.
- [x] Imprimir.
- [x] Descargar PDF.
- [x] Datos del médico.
- [x] Cédula profesional.
- [x] Tests de creación.
- [x] Tests de edición.
- [x] Tests de cancelación.
- [x] Tests del modelo.
- [x] Tests de PDF.

## Catálogo de medicamentos

- [x] Modelo `MedicationCatalog`.
- [x] Catálogo de medicamentos.
- [x] Comando `ImportMedicationCatalog`.
- [x] Importación de catálogo.
- [x] Autocompletado.
- [x] Búsqueda por información del medicamento.
- [x] Tests de autocomplete.

## Pendiente

- [ ] Firma digital.
- [ ] QR/verificación de receta.
- [ ] Historial longitudinal de medicamentos por paciente.
- [ ] Repetir receta anterior.
- [!] Revisar requisitos legales/documentales antes de producción.

---

# 11. Archivos clínicos

Actualmente aparece "Archivos" dentro de la navegación, pero no existe todavía
infraestructura de expediente documental.

No existen modelos ni migraciones específicas para archivos clínicos.

- [ ] Modelo de archivos.
- [ ] Upload.
- [ ] Descarga.
- [ ] Eliminación.
- [ ] Tipos de archivo.
- [ ] PDF.
- [ ] Imágenes.
- [ ] Resultados de laboratorio.
- [ ] Estudios.
- [ ] Radiografías.
- [ ] Fotografías clínicas.
- [ ] Otros documentos.
- [ ] Asociación con paciente.
- [ ] Asociación con consulta.
- [ ] Metadatos.
- [ ] Fecha del estudio.
- [ ] Descripción.
- [ ] Vista previa.
- [ ] Seguridad multi-tenant.
- [ ] Límites por archivo.
- [ ] Límites por tenant.
- [ ] Storage privado.
- [ ] URLs temporales/seguras.
- [!] Definir proveedor de almacenamiento.
- [!] Definir política de conservación.

---

# 12. Dashboard

Relacionado principalmente con DT-8.

- [x] Dashboard funcional.
- [x] Citas de hoy.
- [x] Pacientes.
- [x] Citas por atender.
- [x] Próxima cita.
- [x] Agenda de hoy.
- [x] Actividad del día.
- [x] Consultas finalizadas.
- [x] Consultas en progreso.
- [x] Recetas.
- [x] Próximos 7 días.
- [x] Estado de agenda.
- [x] Acciones rápidas.
- [x] Tests del dashboard.
- [~] Jerarquía visual.
- [~] Utilidad clínica/operativa de algunos indicadores.
- [ ] Alertas importantes.
- [ ] Trial / estado de suscripción.
- [ ] Avisos de pago.
- [ ] Acciones pendientes.
- [ ] Pacientes esperando.
- [!] Revisar qué información necesita realmente el médico al comenzar el día.

---

# 13. Configuración

## Perfil profesional

- [x] Nombre.
- [x] Apellidos.
- [x] Especialidad.
- [x] Cédula.
- [x] Teléfono.
- [x] WhatsApp.
- [x] Biografía.
- [x] Fotografía.
- [x] Firma.

## Consultorio

- [x] Modelo `PracticeProfile`.
- [x] Nombre público.
- [x] Razón social.
- [x] RFC.
- [x] Logo.
- [x] Teléfono.
- [x] WhatsApp.
- [x] Correo.
- [x] Descripción.
- [x] Dirección.
- [x] Colonia.
- [x] Código postal.
- [x] Ciudad.
- [x] Estado.
- [x] País.

## Documentos impresos

- [x] Configuración de impresión.
- [x] Pie de página.
- [x] Datos del médico en receta.
- [x] Datos del consultorio.

## Pendiente

- [ ] Configuración de cuenta.
- [ ] Seguridad.
- [ ] Cambio de contraseña desde UI.
- [ ] Suscripción.
- [ ] Facturación.
- [ ] Métodos de pago.
- [ ] Referidos.
- [ ] Almacenamiento utilizado.
- [!] Reorganizar configuración por secciones/pestañas.

---

# 14. Trial

Existe infraestructura inicial de trial.

## Implementado

- [x] Campo `status`.
- [x] Estado inicial `trial`.
- [x] `trial_started_at`.
- [x] `trial_ends_at`.
- [x] Duración configurable durante registro.
- [x] Inicialización automática durante registro.
- [x] `Tenant::isOnTrial()`.
- [x] `Tenant::trialHasExpired()`.
- [x] Tests de trial.
- [x] Tests del trial durante registro.

## Pendiente

- [ ] Enforcement completo cuando el trial vence.
- [ ] Middleware/política de acceso para trial vencido.
- [ ] Aviso de días restantes.
- [ ] Avisos próximos al vencimiento.
- [ ] Pantalla de trial vencido.
- [ ] Conversión trial → suscripción.
- [ ] Selección mensual/anual.
- [ ] Bloqueo controlado al vencer.
- [!] Definir qué puede hacer el tenant después del vencimiento.
- [!] Definir si existirá periodo de gracia.

---

# 15. Suscripciones

Actualmente NO existe infraestructura de suscripciones.

No existen modelos/migraciones `Subscription`, `Plan`, `Billing` ni
equivalentes.

## Modelo comercial

- [ ] Modelo `Subscription`.
- [ ] Plan.
- [ ] Ciclo mensual.
- [ ] Ciclo anual.
- [ ] Precio mensual.
- [ ] Precio anual.
- [ ] Moneda.
- [ ] Estado de suscripción.

## Periodo de servicio

- [ ] `starts_at`.
- [ ] `current_period_starts_at`.
- [ ] `current_period_ends_at`.
- [ ] `next_billing_at`.
- [ ] Fecha de vencimiento.
- [ ] Fecha de cancelación.

## Operaciones

- [ ] Alta de suscripción.
- [ ] Conversión desde trial.
- [ ] Renovación.
- [ ] Cancelación.
- [ ] Reactivación.
- [ ] Historial de suscripción.
- [ ] Upgrade/downgrade si posteriormente existen varios planes.

## Decisiones pendientes

- [!] Definir proveedor de pagos.
- [!] Definir precio mensual.
- [!] Definir precio anual.
- [!] Definir descuento anual.
- [!] Definir reglas de cancelación.
- [!] Definir reglas de reembolso.

---

# 16. Pagos y facturación SaaS

Actualmente NO existe infraestructura de pagos.

- [ ] Modelo `Payment`.
- [ ] Registro de pagos.
- [ ] Fecha de pago.
- [ ] Importe.
- [ ] Moneda.
- [ ] Método de pago.
- [ ] Estado.
- [ ] Referencia del proveedor.
- [ ] Pago exitoso.
- [ ] Pago pendiente.
- [ ] Pago fallido.
- [ ] Reintentos.
- [ ] Renovación automática.
- [ ] Vencimiento.
- [ ] Periodo de gracia.
- [ ] Suspensión por falta de pago.
- [ ] Reactivación después del pago.
- [ ] Historial de pagos.
- [ ] Recibos/comprobantes.
- [ ] Webhooks.
- [ ] Idempotencia de webhooks.
- [ ] Auditoría de eventos de pago.
- [ ] Métodos de pago.
- [ ] Actualización de método de pago.
- [!] Definir facturación fiscal de DocTotal.
- [!] Definir proveedor de pagos.

---

# 17. Ciclo de vida del tenant

Actualmente existe parte de la infraestructura base.

## Campos existentes

- [x] `status`.
- [x] `suspended_at`.
- [x] `deletion_due_at`.
- [x] `trial_started_at`.
- [x] `trial_ends_at`.
- [x] Soft deletes.

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

## Pendiente

- [ ] Definir estados oficiales.
- [ ] Implementar transiciones.
- [ ] Reglas de acceso por estado.
- [ ] Suspensión automática.
- [ ] Reactivación automática.
- [ ] Cancelación.
- [ ] Eliminación programada.
- [ ] Recuperación antes de eliminación.
- [ ] Scheduler.
- [ ] Jobs.
- [ ] Auditoría de transiciones.
- [!] Definir política de conservación de expedientes después de cancelar.
- [!] Definir comportamiento de acceso en estado `past_due`.
- [!] Definir comportamiento de acceso en estado `suspended`.

---

# 18. Referidos y promociones

Actualmente NO existe infraestructura de referidos.

Cada tenant deberá recibir un código único que pueda compartir.

## Código de referido

- [ ] Código único por tenant.
- [ ] Generación automática.
- [ ] Código permanente.
- [ ] Índice UNIQUE en base de datos.
- [ ] Pantalla para consultar código.
- [ ] Acción para copiar código.
- [ ] Validación del código.
- [ ] Identificación del tenant referente.

## Uso durante registro

Reglas definidas:

- [ ] Un tenant nuevo puede utilizar como máximo UN código de referido.
- [ ] El código solamente puede utilizarse durante su inscripción inicial.
- [ ] La oportunidad existe únicamente durante el alta inicial.
- [ ] Si termina el registro sin utilizar código, pierde definitivamente
      la posibilidad de aplicar uno.
- [ ] No puede agregar un código posteriormente.
- [ ] No puede cambiar el código utilizado.
- [ ] No puede eliminarlo para utilizar otro.
- [ ] No puede utilizar nuevamente otro código.
- [ ] Un tenant no puede referirse a sí mismo.
- [ ] El uso debe quedar registrado de forma permanente.

## Límite promocional mensual

El número máximo debe ser configurable.

Ejemplo inicial:

Primeros 5 referidos válidos del mes → generan promoción.

Referidos posteriores → no generan promoción durante ese periodo.

Pendiente:

- [ ] Máximo configurable de usos premiados por código y por mes.
- [ ] Periodo mensual.
- [ ] Contador de usos.
- [ ] Reinicio lógico por nuevo periodo.
- [ ] Registrar qué usos obtuvieron promoción.
- [ ] Evitar race conditions al consumir el último cupo.
- [ ] Definir comportamiento de referidos posteriores al límite.

## Beneficio

- [ ] Definir monto promocional.
- [ ] Registrar beneficio del tenant nuevo.
- [ ] Registrar beneficio del tenant referente.
- [ ] Aplicar beneficio a suscripción.
- [ ] Saldo promocional.
- [ ] Historial de promociones.
- [ ] Evitar doble beneficio.
- [ ] Auditoría de promociones.

## Decisiones pendientes

- [!] Definir monto de promoción.
- [!] Definir número inicial de referidos premiables por mes.
- [!] Definir si el beneficio del referente requiere primer pago del referido.
- [!] Definir vencimiento del saldo promocional.
- [!] Definir si aplica a mensualidad.
- [!] Definir si aplica a anualidad.
- [!] Definir comportamiento si el referido cancela o solicita reembolso.

---

# 19. Comunicaciones

Actualmente no existe infraestructura completa de comunicaciones externas.

- [ ] Infraestructura de notificaciones.
- [ ] Correo transaccional.
- [ ] WhatsApp.
- [ ] SMS.

## Citas

- [ ] Confirmación de cita.
- [ ] Recordatorio de cita.
- [ ] Cancelación.
- [ ] Reprogramación.
- [ ] Confirmación por paciente.

## SaaS

- [ ] Bienvenida.
- [ ] Trial próximo a vencer.
- [ ] Trial vencido.
- [ ] Pago próximo.
- [ ] Pago exitoso.
- [ ] Pago fallido.
- [ ] Reintento de pago.
- [ ] Cuenta suspendida.
- [ ] Cuenta reactivada.
- [ ] Cancelación de suscripción.

## Decisiones

- [!] Definir proveedor de correo.
- [!] Definir proveedor de WhatsApp.
- [!] Definir proveedor de SMS.

---

# 20. Diseño y experiencia visual

Esta área debe tratarse como una línea formal de desarrollo y no como una
serie de retoques aislados.

La interfaz actual es funcional y consistente, pero todavía transmite
claramente la estructura visual de una aplicación Laravel/Livewire.

El objetivo no es solamente "hacerla bonita".

El objetivo es crear una identidad propia de DocTotal y optimizar cada
pantalla para el trabajo real del médico.

## DocTotal Design System

- [ ] Identidad visual definitiva.
- [ ] Paleta de colores.
- [ ] Tipografía.
- [ ] Escala tipográfica.
- [ ] Sistema de espaciado.
- [ ] Sistema de tamaños.
- [ ] Iconografía.
- [ ] Botones.
- [ ] Inputs.
- [ ] Selects.
- [ ] Textareas.
- [ ] Checkboxes.
- [ ] Radio buttons.
- [ ] Cards.
- [ ] Modales.
- [ ] Dropdowns.
- [ ] Tooltips.
- [ ] Badges.
- [ ] Alertas.
- [ ] Toasts.
- [ ] Tablas.
- [ ] Empty states.
- [ ] Loading states.
- [ ] Skeletons.
- [ ] Estados de error.
- [ ] Estados de éxito.
- [ ] Navegación.
- [ ] Sidebar.
- [ ] Header.
- [ ] Breadcrumbs.
- [ ] Responsive.
- [ ] Accesibilidad básica.

## Auditoría visual actual

- [x] Inventario visual realizado.
- [x] Dashboard revisado.
- [x] Pacientes revisado.
- [x] Expediente revisado.
- [x] Agenda revisada.
- [x] Detalle de cita revisado.
- [x] Consulta en progreso revisada.
- [x] Consulta finalizada revisada.
- [x] Crear receta revisado.
- [x] Receta final revisada.
- [x] Configuración revisada.
- [x] Login revisado.
- [x] Registro revisado.
- [x] Onboarding completo revisado.

## Problemas detectados

- [~] Existe consistencia visual básica.
- [~] La interfaz es limpia.
- [~] La interfaz es funcional.
- [!] Exceso de cards visualmente similares.
- [!] Jerarquía visual limitada.
- [!] Escasa diferenciación entre módulos.
- [!] Demasiada dependencia del patrón visual de formulario.
- [!] Sidebar demasiado básico.
- [!] Poca identidad propia de DocTotal.
- [!] El espacio disponible no siempre se aprovecha correctamente.
- [!] Acciones clínicas importantes podrían destacar mejor.
- [!] El producto todavía transmite sensación de aplicación en desarrollo.
- [!] Consulta clínica parece formulario y no workspace médico.
- [!] Expediente todavía no comunica suficientemente la historia del paciente.

---

# 21. Rediseño por módulo

No iniciar rediseños aislados antes de definir las bases del Design System.

## Login

- [ ] Rediseñar login.
- [ ] Incorporar identidad DocTotal.
- [ ] Mejorar percepción de confianza.
- [ ] Mejorar estados de error.

## Registro

- [ ] Rediseñar registro.
- [ ] Mejorar presentación del trial.
- [ ] Incorporar código de referido.
- [ ] Mejorar explicación de creación del consultorio.

## Onboarding

- [ ] Rediseñar wizard.
- [ ] Mejorar indicador de progreso.
- [ ] Reducir sensación de formulario administrativo.
- [ ] Mejorar selección de horarios.
- [ ] Mejorar pantalla final.

## Dashboard

- [ ] Rediseñar jerarquía.
- [ ] Priorizar operación del día.
- [ ] Mejorar agenda del día.
- [ ] Mejorar indicadores.
- [ ] Incorporar alertas.
- [ ] Incorporar estado de cuenta/trial cuando corresponda.

## Pacientes

- [ ] Mejorar listado.
- [ ] Mejorar búsqueda.
- [ ] Mejorar acciones rápidas.
- [ ] Mejorar lectura de información importante.

## Expediente

- [ ] Rediseñar como expediente clínico longitudinal.
- [ ] Resumen del paciente.
- [ ] Alertas.
- [ ] Antecedentes.
- [ ] Timeline.
- [ ] Consultas.
- [ ] Diagnósticos.
- [ ] Medicamentos.
- [ ] Recetas.
- [ ] Archivos.

## Agenda

- [ ] Mejorar calendario.
- [ ] Mejorar representación de citas.
- [ ] Diferenciar estados.
- [ ] Mejorar acciones rápidas.
- [ ] Optimizar vista diaria.
- [ ] Optimizar vista semanal.
- [ ] Mejorar navegación temporal.

## Consulta

- [ ] Convertir en workspace clínico.
- [ ] Mejor distribución de información.
- [ ] Reducir navegación innecesaria.
- [ ] Mostrar contexto clínico del paciente.
- [ ] Mostrar alergias relevantes.
- [ ] Mostrar medicamentos actuales.
- [ ] Mostrar diagnósticos importantes.
- [ ] Mejorar captura SOAP.
- [ ] Mejorar diagnósticos.
- [ ] Mejorar recetas.
- [ ] Preparar autosave.

## Recetas

- [ ] Mejorar captura.
- [ ] Mejorar búsqueda de medicamentos.
- [ ] Mejorar visualización.
- [ ] Mejorar documento final.
- [ ] Facilitar repetir tratamientos.

## Configuración

- [ ] Dividir por secciones.
- [ ] Perfil.
- [ ] Consultorio.
- [ ] Agenda.
- [ ] Documentos.
- [ ] Cuenta.
- [ ] Seguridad.
- [ ] Suscripción y pagos.
- [ ] Referidos.

---

# 22. Auditoría, privacidad y seguridad

- [ ] Auditoría de acciones sensibles.
- [ ] Historial de cambios clínicos.
- [ ] Historial de cambios de citas.
- [ ] Historial de cambios de recetas.
- [ ] Historial de suscripción.
- [ ] Historial de pagos.
- [ ] Eventos administrativos.
- [ ] Protección de archivos.
- [ ] Revisión de autorización.
- [ ] Revisión de validaciones.
- [ ] Rate limiting.
- [ ] Política de contraseñas.
- [ ] Auditoría de 2FA/passkeys.
- [ ] Backups.
- [ ] Restauración.
- [ ] Logs de producción.
- [ ] Monitoreo de errores.
- [ ] Protección de información sensible.
- [ ] Política de retención.
- [ ] Política de eliminación.
- [!] Revisión integral antes de manejar información real de pacientes.

---

# 23. Operación interna de DocTotal

DocTotal también necesita herramientas para administrarse como negocio.

Actualmente no existe panel administrativo interno.

## Tenants

- [ ] Panel administrativo.
- [ ] Listado de tenants.
- [ ] Buscar tenant.
- [ ] Ver tenant.
- [ ] Estado.
- [ ] Fecha de alta.
- [ ] Trial.
- [ ] Suscripción.
- [ ] Último pago.
- [ ] Próximo pago.
- [ ] Estado de almacenamiento.

## Operación SaaS

- [ ] Trials activos.
- [ ] Trials próximos a vencer.
- [ ] Trials vencidos.
- [ ] Suscripciones activas.
- [ ] Suscripciones vencidas.
- [ ] Suscripciones canceladas.
- [ ] Tenants suspendidos.
- [ ] Pagos.
- [ ] Pagos fallidos.
- [ ] Referidos.
- [ ] Promociones.

## Métricas

- [ ] Total de tenants.
- [ ] Tenants activos.
- [ ] Altas.
- [ ] Cancelaciones.
- [ ] Conversión trial → pago.
- [ ] MRR.
- [ ] ARR.
- [ ] Churn.
- [ ] Uso de almacenamiento.

## Soporte

- [ ] Herramientas administrativas.
- [ ] Auditoría de acciones administrativas.
- [ ] Soporte de cuentas.
- [!] El panel administrativo nunca debe permitir romper accidentalmente
      el aislamiento entre tenants.

---

# 24. Infraestructura y operación técnica

Existe infraestructura base de Laravel para cache/jobs, pero todavía falta
definir la operación real de producción.

- [x] Tabla de jobs.
- [x] Tabla de cache.
- [ ] Configurar queue de producción.
- [ ] Jobs para tareas SaaS.
- [ ] Scheduler.
- [ ] Procesamiento de renovaciones.
- [ ] Procesamiento de suspensiones.
- [ ] Procesamiento de eliminaciones.
- [ ] Procesamiento de notificaciones.
- [ ] Monitoreo de queues.
- [ ] Manejo de failed jobs.
- [ ] Logging estructurado.
- [ ] Error tracking.
- [ ] Backups automáticos.
- [ ] Monitoreo de aplicación.
- [ ] Health checks.
- [!] Definir infraestructura de producción.

---

# 25. Calidad y tests

La aplicación cuenta con una suite automatizada considerable.

## Baseline actual

Cierre DT-10:

393 tests verdes.

1179 assertions.

0 failures.

## Cobertura existente

- [x] Tests de autenticación.
- [x] Tests de registro.
- [x] Tests de trial.
- [x] Tests de multi-tenancy.
- [x] Tests de resolución de tenant.
- [x] Tests de modelos base.
- [x] Tests de onboarding.
- [x] Tests de código postal.
- [x] Tests de pacientes.
- [x] Tests de antecedentes.
- [x] Tests de contactos de emergencia.
- [x] Tests de agenda.
- [x] Tests de appointments.
- [x] Tests de disponibilidad.
- [x] Tests de slots.
- [x] Tests de estados.
- [x] Tests de reprogramación.
- [x] Tests Appointment → Consultation.
- [x] Tests de Consultation.
- [x] Tests de lifecycle clínico.
- [x] Tests de diagnósticos.
- [x] Tests del catálogo diagnóstico.
- [x] Tests de autocomplete diagnóstico.
- [x] Tests de recetas.
- [x] Tests de medicamentos.
- [x] Tests de autocomplete de medicamentos.
- [x] Tests de PDF.
- [x] Tests del dashboard.

## Pendiente futuro

- [ ] Mantener baseline actualizado después de cada DT.
- [ ] Tests de archivos.
- [ ] Tests de almacenamiento.
- [ ] Tests de suscripciones.
- [ ] Tests de pagos.
- [ ] Tests de referidos.
- [ ] Tests de promociones.
- [ ] Tests del ciclo de vida del tenant.
- [ ] Tests de comandos/jobs programados.
- [ ] Tests de webhooks.
- [ ] Tests de notificaciones.
- [ ] Tests de seguridad adicionales.

---

# 26. Estado de los DT

## DT-1 — Base multi-tenant database structure

Estado: Completado.

- [x] Base multi-tenant.
- [x] Tenant.
- [x] Relaciones iniciales.

## DT-2 — Core Eloquent models and relationships

Estado: Completado.

- [x] Modelos principales.
- [x] Relaciones.

## DT-3 — Tenant isolation and request resolution

Estado: Completado.

- [x] TenantContext.
- [x] TenantScope.
- [x] Middleware.
- [x] Aislamiento.

## DT-4 — Patient clinical record foundation

Estado: Completado.

- [x] Pacientes.
- [x] Base clínica.
- [x] Consultas iniciales.

## DT-5 — Authentication, registration, dashboard and trial

Estado: Completado.

- [x] Registro.
- [x] Login.
- [x] Dashboard.
- [x] Trial.

## DT-6 — Onboarding wizard and postal code autocomplete

Estado: Completado.

- [x] Onboarding.
- [x] Perfil médico.
- [x] Consultorio.
- [x] Horarios.
- [x] Código postal.

## DT-7 — Gestión de pacientes

Estado: Completado.

- [x] Pacientes.
- [x] Expediente.
- [x] Antecedentes.
- [x] Historial.

## DT-8 — Agenda, citas y ciclo operativo

Estado: Completado.

- [x] Agenda.
- [x] Citas.
- [x] Availability.
- [x] Estados.
- [x] Appointment → Consultation.

Baseline al cierre:

366 tests verdes.

## DT-9 — Consultation workflow and clinical lifecycle

Estado: Completado.

- [x] Consultation persistente.
- [x] Draft.
- [x] Completed.
- [x] Lifecycle.
- [x] SOAP.
- [x] Diagnósticos.
- [x] Catálogo diagnóstico.
- [x] Recetas.
- [x] Catálogo de medicamentos.
- [x] PDF.

## DT-10 — Product inventory and development roadmap

Estado: Completado.

Objetivo:

Crear un mapa maestro del producto que permita conocer qué existe, qué está
incompleto, qué falta y qué debe desarrollarse después.

Incluye:

- [x] Inventario funcional.
- [x] Inventario clínico.
- [x] Inventario de pacientes.
- [x] Inventario de agenda.
- [x] Inventario de consultas.
- [x] Inventario de diagnósticos.
- [x] Inventario de medicamentos.
- [x] Inventario de recetas.
- [x] Inventario de archivos pendientes.
- [x] Inventario SaaS.
- [x] Inventario de trial.
- [x] Inventario de billing.
- [x] Inventario de lifecycle del tenant.
- [x] Definición inicial de referidos/promociones.
- [x] Inventario visual.
- [x] Auditoría de pantallas existentes.
- [x] Separación de los tres pilares del producto.
- [x] Validación final contra `app/`.
- [x] Validación final contra migraciones.
- [x] Validación final contra tests.
- [x] Baseline actualizado.

Baseline al cierre:

393 tests verdes.

1179 assertions.

0 failures.

---

# 27. Candidatos para siguientes DT

Los números definitivos deben asignarse al momento de seleccionar el siguiente
bloque.

## Candidato A — Archivos y expediente documental

Objetivo:

Completar la parte documental del expediente.

Construir:

Patient
→ Clinical Files
→ Studies
→ Labs
→ Images
→ Consultation attachments

Dependencias importantes:

- Storage.
- Seguridad.
- Multi-tenancy.
- Límites.
- Retención.

---

## Candidato B — SaaS billing foundation

Objetivo:

Construir la infraestructura que permita a DocTotal administrar
automáticamente a sus clientes.

Construir:

Tenant
→ Subscription
→ Billing period
→ Payments
→ Renewal
→ Expiration
→ Suspension
→ Reactivation

Este bloque desbloquea:

- Cobros.
- Mensualidades.
- Anualidades.
- Trial → pago.
- Suspensiones automáticas.
- Promociones.
- Referidos.

---

## Candidato C — Referral system

Objetivo:

Implementar el sistema de crecimiento por códigos de referido.

Construir:

Tenant
→ Referral code
→ Registration usage
→ Monthly quota
→ Reward
→ Subscription credit

Dependencia:

Debe coordinarse estrechamente con billing para poder aplicar correctamente
beneficios económicos.

---

## Candidato D — DocTotal Design System

Objetivo:

Crear el lenguaje visual propio de DocTotal.

Construir:

Design tokens
→ Components
→ Navigation
→ Forms
→ Cards
→ Tables
→ States
→ Clinical patterns

Después migrar progresivamente las pantallas existentes.

---

## Candidato E — Clinical workspace

Objetivo:

Transformar el expediente y la consulta en una experiencia clínica realmente
optimizada.

Construir:

Patient
→ Clinical summary
→ Alerts
→ Consultation workspace
→ Timeline
→ Diagnoses
→ Prescriptions
→ Files

---

# 28. Deuda y decisiones de producto

Estas decisiones no necesariamente requieren un DT inmediato, pero deben
resolverse antes de los módulos que dependan de ellas.

## Comercial

- [!] Precio mensual.
- [!] Precio anual.
- [!] Descuento anual.
- [!] Duración definitiva del trial.
- [!] Periodo de gracia.
- [!] Política de cancelación.
- [!] Política de reembolso.

## Referidos

- [!] Beneficio para tenant nuevo.
- [!] Beneficio para referente.
- [!] Número de promociones mensuales.
- [!] Requisito de primer pago.
- [!] Vencimiento de saldo promocional.

## Infraestructura

- [!] Proveedor de pagos.
- [!] Proveedor de almacenamiento.
- [!] Proveedor de correo.
- [!] Proveedor de WhatsApp.
- [!] Proveedor de SMS.

## Clínica

- [!] Estructura de problemas activos.
- [!] Política de modificación de información clínica finalizada.
- [!] Política de archivos.
- [!] Retención de expedientes.
- [!] Requisitos legales de recetas/documentos.

## UX

- [!] Identidad visual definitiva.
- [!] Dirección visual del Design System.
- [!] Estructura definitiva del workspace clínico.

---

# 29. Regla para decidir el siguiente DT

Al terminar cada DT:

1. Ejecutar suite completa de tests.
2. Confirmar que todos los tests estén verdes.
3. Registrar número de tests.
4. Registrar número de assertions.
5. Actualizar `ROADMAP.md`.
6. Actualizar `TODO.md`.
7. Marcar funcionalidades terminadas.
8. Registrar nuevas necesidades descubiertas.
9. Revisar pendientes críticos.
10. Revisar dependencias entre módulos.
11. Elegir el siguiente DT.
12. Definir objetivo.
13. Definir alcance.
14. Definir explícitamente qué queda fuera.
15. Crear branch.
16. Comenzar implementación.

---

# 30. Regla de cierre de un DT

Un DT no debe considerarse terminado únicamente porque "funciona".

Antes de cerrarlo debe comprobarse:

- [ ] Objetivo cumplido.
- [ ] Alcance implementado.
- [ ] Multi-tenancy protegido.
- [ ] Validaciones implementadas.
- [ ] Estados límite contemplados.
- [ ] Tests agregados.
- [ ] Suite completa verde.
- [ ] UI funcional.
- [ ] Integración con módulos existentes comprobada.
- [ ] TODO actualizado.
- [ ] ROADMAP actualizado.
- [ ] Baseline registrado.
- [ ] Commit final realizado.

---

# 31. Principio de desarrollo

No desarrollar una función solamente porque "hace falta".

Antes de comenzar un nuevo DT debemos responder:

1. ¿Qué problema resuelve?
2. ¿Para quién lo resuelve?
3. ¿Qué módulos existentes afecta?
4. ¿Qué información necesita?
5. ¿Qué información genera?
6. ¿Cómo afecta al aislamiento multi-tenant?
7. ¿Cómo afecta al expediente clínico?
8. ¿Cómo afecta al ciclo SaaS?
9. ¿Cómo debe verse y sentirse para el usuario?
10. ¿Cómo vamos a probarlo?

DocTotal debe crecer como un producto integrado, no como una colección de
pantallas y funciones independientes.

---

# 32. Visión de producto

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

Todo esto debe presentarse mediante una experiencia visual propia,
profesional, agradable y diseñada específicamente para el trabajo médico.