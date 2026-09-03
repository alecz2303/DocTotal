# DocTotal --- Project Context

Documento de contexto técnico y funcional del proyecto.

Su propósito es mantener continuidad entre sesiones, chats y nuevos
bloques de desarrollo sin sustituir `TODO.md` ni `ROADMAP.md`.

------------------------------------------------------------------------

# Stack

Backend:

-   PHP 8.4.
-   Laravel 13.

Frontend:

-   Blade.
-   Livewire.
-   Componentes Livewire single-file estilo Volt.
-   Tailwind CSS.

Base de datos:

-   MySQL en desarrollo/producción.
-   SQLite in-memory en tests.

Testing:

-   PHPUnit.
-   Laravel Feature Tests.
-   Livewire component tests.

------------------------------------------------------------------------

# Convenciones del proyecto

## Livewire

Los componentes se crean con Artisan.

Ejemplo:

``` bash
php artisan make:livewire pages::appointments.reschedule
```

Las vistas principales del proyecto utilizan nombres con el carácter
`⚡`. En Windows, algunos comandos pueden mostrar ese carácter o texto
UTF-8 de forma incorrecta. No debe modificarse el código fuente
basándose únicamente en mojibake de la terminal.

## Multi-tenancy

El aislamiento por tenant es requisito obligatorio.

La foundation principal utiliza:

-   `TenantContext`.
-   `TenantScope`.
-   `App\Traits\BelongsToTenant`.

Los modelos que usan `BelongsToTenant` reciben automáticamente
`tenant_id` desde el contexto activo al crear registros y quedan
protegidos por el scope global del tenant.

Todo módulo nuevo debe revisar explícitamente su comportamiento
multi-tenant.

------------------------------------------------------------------------

# Baseline actual

Último DT integrado en `master`:

-   DT-23 --- Billing recovery must honor plan change for unpaid past-due subscription.
-   Commit:
    `bbd9ce6 DT-23 fix: honor recovery plan for past-due subscriptions`.

DT activo con cierre técnico completado:

-   DT-24 --- Patient self-service for appointment confirmation and cancellation.
-   Jira: DT-24.
-   Suite completa validada: `1002 tests verdes`, `0 failures`.
-   Pendiente: documentación final, commit, push, PR, merge a `master` y cierre Jira.

Suite completa validada al cierre técnico de DT-24:

`1002 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado vigente:

`79%`

## El 79% es el último porcentaje formalmente calculado. No se infiere un porcentaje nuevo sin aplicar nuevamente el criterio ponderado del producto.

# Commits recientes canónicos

-   `bbd9ce6 DT-23 fix: honor recovery plan for past-due subscriptions`
-   `c8fdd7f DT-22 feat: complete internal SaaS administration panel`
-   `43901d0 docs: define prioritized development queue`
-   `c3c70d9 DT-21 feat: implement audit trail and security hardening foundation`
-   `9192020 DT-20 feat: implement transactional communications and appointment reminders`
-   `1dd4ad7 DT-19 feat: implement structured active clinical problem list`
-   `b529ed5 DT-18 docs: normalize project baseline after DT-17`
-   `ff7aee4 DT-17 feat: implement advanced clinical consultation workspace`

# Foundation clínica actual

DocTotal cuenta actualmente con:

-   Pacientes y expediente clínico.
-   Contactos de emergencia.
-   Antecedentes médicos.
-   Agenda y ciclo completo de citas.
-   Consultas persistentes draft/completed.
-   Workspace clínico con autosave y protección de cambios.
-   Diagnósticos y catálogo diagnóstico.
-   Recetas y catálogo de medicamentos.
-   Expediente longitudinal.
-   Problemas clínicos activos/resueltos mediante `PatientProblem`.
-   Documentos clínicos privados.
-   Historial visual de actividad auditada del paciente.

`PatientMedicalHistory` continúa siendo la fuente explícita para:

-   alergias;
-   medicamentos actuales;
-   antecedentes;
-   enfermedades crónicas;
-   cirugías.

`PatientProblem` es la entidad longitudinal explícita para problemas
clínicos activos y resueltos.

No se infieren automáticamente medicamentos actuales desde recetas
históricas ni problemas activos desde diagnósticos históricos.

------------------------------------------------------------------------

# Foundation SaaS actual

DocTotal cuenta con:

-   Registro y autenticación.
-   Trial.
-   Onboarding.
-   Subscription lifecycle.
-   Billing con Stripe.
-   Pagos, renovaciones y recuperación.
-   Grace period y suspensión/reactivación.
-   Referidos.
-   Créditos promocionales.
-   Comunicaciones transaccionales.
-   Recordatorios de citas.
-   Scheduler para billing y comunicaciones.

Los cobros automáticos deben mantenerse bajo la feature flag
correspondiente hasta su activación controlada en el entorno objetivo.

DT-22 agregó la operación administrativa interna del SaaS:

-   rol `internal_admin` sin tenant asociado;
-   middleware y shell administrativo separados del producto clínico;
-   dashboard operacional global;
-   listado y detalle operativo de tenants;
-   indicadores de trials, suscripciones y estado efectivo del servicio;
-   monitoreo global de incidencias de billing;
-   monitoreo de comunicaciones;
-   acceso operativo controlado a auditoría;
-   lecturas cross-tenant encapsuladas en servicios internos;
-   bloqueo clínico mediante `service.access` cuando el tenant no tiene
    derecho vigente al servicio;
-   pantalla de servicio suspendido accesible sin debilitar el
    aislamiento.

El estado efectivo de acceso no debe inferirse únicamente desde
`Tenant.status`. Un tenant puede conservar fechas históricas de trial y
tener acceso por una suscripción vigente. La expiración del trial por sí
sola no muta automáticamente el estado persistido del tenant.

------------------------------------------------------------------------

# Comunicaciones --- DT-20

La capa de comunicaciones es independiente del proveedor.

Elementos principales:

-   `Communication`.
-   `CommunicationTransport`.
-   `CommunicationTransportManager`.
-   `CommunicationProcessor`.
-   `AppointmentReminderService`.
-   `AppointmentReminderValidator`.

Canales preparados:

-   email;
-   WhatsApp;
-   SMS.

Reglas importantes:

-   Sin transport configurado no se simula éxito.
-   Sin transport no se consume intento.
-   Máximo actual de 3 intentos.
-   Backoff actual: 5 y 15 minutos.
-   Los recordatorios obsoletos se cancelan y conservan.
-   Reprogramar una cita genera una nueva identidad de recordatorio.

------------------------------------------------------------------------

# Auditoría y hardening --- DT-21

## AuditEvent

`AuditEvent` es la entidad persistente de auditoría.

Registra:

-   tenant;
-   actor opcional;
-   acción;
-   recurso polimórfico;
-   descripción;
-   IP;
-   user agent;
-   metadata controlada;
-   timestamps.

Usa `BelongsToTenant`, por lo que las consultas normales quedan aisladas
por el tenant activo.

La protección append-only implementada impide update/delete mediante
eventos normales del modelo Eloquent.

Esta protección no equivale a inmutabilidad garantizada por la base de
datos: operaciones directas o query builder pueden omitir eventos del
modelo.

## AuditLogger

`AuditLogger::log()` persiste un evento y propaga errores.

`AuditLogger::safeLog()` implementa la política utilizada en las
integraciones de DT-21:

-   intenta persistir el evento;
-   si falla, registra el error técnico mediante Laravel logging;
-   devuelve `null`;
-   no cambia por sí mismo el resultado funcional de la operación
    principal.

Por ello, la auditoría actual debe describirse como **best-effort**.

No existe todavía outbox transaccional que garantice persistencia del
evento ante fallos de infraestructura.

## Metadata sensible

La sanitización es recursiva y redacta conservadoramente claves que
contengan fragmentos relacionados con:

-   password;
-   token;
-   authorization;
-   cookie;
-   secret;
-   api_key.

La metadata debe mantenerse mínima y no duplicar payload clínico
sensible.

## Flujos auditados en DT-21

Acciones iniciales:

-   `patient.updated`.
-   `consultation.completed`.
-   `appointment.rescheduled`.
-   `appointment.cancelled`.

Las cuatro integraciones utilizan `safeLog()`.

## Historial visual

El expediente del paciente incluye una tarjeta `Historial de actividad`.

Muestra:

-   etiqueta amigable;
-   actor;
-   descripción;
-   fecha/hora;
-   paginación de 5 eventos.

No muestra:

-   IP;
-   user agent;
-   metadata;
-   IDs internos;
-   tipo técnico del recurso.

La consulta visual actual filtra eventos cuyo recurso auditado es el
propio Patient. Por ello, eventos auditados sobre Consultation o
Appointment no se mezclan automáticamente en esta tarjeta.

------------------------------------------------------------------------

# Calidad DT-21

Cobertura específica agregada para:

-   creación y relaciones de `AuditEvent`;
-   aislamiento multi-tenant;
-   actor opcional;
-   metadata;
-   asociación polimórfica;
-   protección Eloquent contra update/delete;
-   sanitización de metadata;
-   variantes de claves sensibles;
-   `safeLog()` ante fallo;
-   auditoría de actualización de paciente;
-   auditoría de finalización de consulta;
-   auditoría de reprogramación;
-   auditoría de cancelación;
-   historial visual y paginación.

Regresión focalizada DT-21:

`58 tests verdes`

Regresión del historial visual/paginación:

`13 tests verdes`

AppointmentShow después del ajuste final a `safeLog()`:

`11 tests verdes`

Suite completa final:

`936 tests verdes`

`0 failures`

------------------------------------------------------------------------

# Alcance pendiente de seguridad

DT-21 no pretende cerrar toda la seguridad de producción.

Permanecen pendientes, entre otros:

-   revisión integral de autorización;
-   rate limiting;
-   administración/revocación de sesiones;
-   verificación completa de 2FA/passkeys;
-   backups y restauración;
-   logging estructurado y observabilidad;
-   política de retención;
-   política de eliminación;
-   inmutabilidad de auditoría a nivel de base de datos;
-   outbox durable para auditoría;
-   auditoría más amplia de billing y acciones administrativas.

------------------------------------------------------------------------

# Operación interna SaaS y acceso al servicio --- DT-22

DT-22 construyó una consola administrativa interna separada de la
experiencia clínica de los tenants.

Principios:

-   un operador interno válido utiliza rol `internal_admin` y
    `tenant_id = null`;
-   usuarios normales de tenant no pueden acceder a la consola interna;
-   las lecturas globales eliminan explícitamente `TenantScope`
    únicamente donde corresponde;
-   no se utilizan bypasses globales dispersos;
-   la consola evita exponer payload clínico, destinatarios, cuerpos de
    mensajes o secretos innecesarios.

Visibilidad operacional implementada:

-   tenants y usuarios;
-   trial y días restantes/vencidos;
-   suscripciones;
-   pagos e incidencias de billing;
-   grace period;
-   comunicaciones y errores;
-   eventos de auditoría;
-   indicadores generales de salud SaaS.

Acceso al servicio:

-   trial vigente: acceso permitido;
-   suscripción vigente: acceso permitido;
-   `past_due` dentro del grace period: acceso permitido;
-   grace period vencido: acceso denegado;
-   tenant suspendido o cancelado: acceso denegado;
-   sin trial vigente ni suscripción válida: acceso denegado.

`EnsureTenantHasServiceAccess` protege el área clínica. La pantalla de
servicio suspendido y la gestión de billing permanecen accesibles para
permitir recuperación.

Calidad final DT-22:

`988 tests verdes`

`0 failures`

El cierre funcional no introduce bypasses especiales para testing; los
fixtures clínicos históricos fueron actualizados para representar
explícitamente tenants con acceso vigente.

------------------------------------------------------------------------

# Billing recovery y cambio de plan --- DT-23

DT-23 corrigió la transición crítica de recuperación de una suscripción `past_due` cuando el tenant cambia de ciclo antes de pagar.

Reglas consolidadas:

-   una suscripción anual pagada y vigente que cambia a mensual conserva el anual hasta el fin del periodo y programa el cambio futuro;
-   una suscripción anual no pagada o `past_due` puede elegir mensual como plan de recuperación;
-   el checkout de recuperación utiliza el importe y moneda del ciclo elegido;
-   el cambio de ciclo se vuelve contractual únicamente después de un pago exitoso;
-   un pago fallido o checkout abandonado no reactiva falsamente la suscripción;
-   no se reutilizan PaymentIntents ni claves de idempotencia incompatibles con el nuevo importe/ciclo;
-   pagos históricos sin snapshot de ciclo conservan compatibilidad;
-   `recoverableSubscription()` permite recuperar una suscripción `past_due` aun después de vencer el grace period sin conceder acceso clínico.

Calidad final DT-23:

`995 tests verdes`

`0 failures`

Commit canónico:

`bbd9ce6 DT-23 fix: honor recovery plan for past-due subscriptions`

------------------------------------------------------------------------

# Interacción pública del paciente con citas --- DT-24

DT-24 agrega autoservicio público de citas sin requerir cuenta ni sesión del paciente.

Implementado:

-   enlace público seguro por cita mediante token aleatorio de alta entropía;
-   persistencia únicamente del hash del token;
-   resolución pública sin enumeración por UUID;
-   vista mínima de la cita sin expediente, motivo, notas ni información clínica sensible;
-   confirmación pública de citas `scheduled`;
-   cancelación pública de citas `scheduled` o `confirmed`;
-   confirmación idempotente;
-   bloqueo de acciones públicas para estados clínicos o terminales;
-   invalidación del enlace anterior al reprogramar la cita;
-   generación de un nuevo enlace en el siguiente recordatorio;
-   integración del enlace seguro con `AppointmentReminderService`;
-   auditoría de confirmación/cancelación pública sin persistir el token;
-   cobertura multi-tenant y funcionamiento sin sesión ni `TenantContext` previo.

Fuera de alcance de DT-24:

-   selección pública de nuevos horarios;
-   solicitud/ejecución pública de reprogramación;
-   portal completo del paciente.

Calidad final DT-24:

`1002 tests verdes`

`0 failures`

------------------------------------------------------------------------

# Regla de cierre

Antes de cerrar un DT:

1.  Ejecutar la suite completa.
2.  Confirmar cero fallos.
3.  Registrar el baseline real sin inventar assertions.
4.  Actualizar `PROJECT_CONTEXT.md`, `ROADMAP.md` y `TODO.md`.
5.  Revisar `git status` y el diff.
6.  Realizar commit final.
7.  Integrar en `master`.
8.  Registrar cierre técnico en Jira.
9.  Transicionar el ticket a `Listo`.

Los números DT deben ser asignados por Jira; no deben inventarse
manualmente.
