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

-   DT-27 --- Structured laboratory results in clinical records.
-   Commit:
    `a1a66a1 DT-27 feat: add structured laboratory results`.

DT activo con cierre técnico completado:

-   DT-28 --- Complete account security and recovery flows.
-   Jira: DT-28.
-   Suite completa validada: `1068 tests verdes`, `0 failures`.
-   Pendiente: commit, push, PR, merge a `master` y cierre Jira.

Suite completa validada al cierre técnico de DT-28:

`1068 tests verdes`

`0 failures`

Assertions finales no registradas; no se infieren.

Avance global ponderado vigente:

`79%`

## El 79% es el último porcentaje formalmente calculado. No se infiere un porcentaje nuevo sin aplicar nuevamente el criterio ponderado del producto.

# Commits recientes canónicos

-   `a1a66a1 DT-27 feat: add structured laboratory results`
-   `4ceac23 DT-26 feat: add reusable clinical templates`
-   `240bcf6 DT-25 feat: add manual patient appointment link sharing`
-   `12d573b DT-24 feat: add patient appointment self-service`
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

# Compartición manual del enlace de gestión de cita --- DT-25

DT-25 completa la capa operativa manual sobre el autoservicio público de DT-24.

Implementado:

-   generación y regeneración manual del enlace seguro desde el detalle de la cita;
-   URL pública compacta mediante ruta `/a/{token}`;
-   token compacto URL-safe manteniendo alta entropía y persistencia únicamente del hash SHA-256;
-   compatibilidad con enlaces públicos anteriores de DT-24;
-   copia directa del enlace y del mensaje completo;
-   apertura de WhatsApp con destinatario y mensaje precargado, sin marcar envío confirmado;
-   apertura del cliente de correo con asunto y cuerpo precargados, sin marcar envío confirmado;
-   normalización de números mexicanos para WhatsApp;
-   mensaje humano con nombre del médico, consultorio/clínica, fecha en español y hora;
-   reutilización de la misma construcción de enlace/mensaje por recordatorios automáticos y flujo manual;
-   regenerar rota el token vigente e invalida cualquier enlace anterior;
-   pantalla pública amigable para enlaces inexistentes o invalidados, conservando respuesta HTTP 404;
-   sin persistir el token plano en auditoría ni metadata.

El flujo manual no requiere un transport configurado y no registra una comunicación como enviada por copiar o abrir un canal externo.

Calidad final DT-25:

`1005 tests verdes`

`0 failures`

------------------------------------------------------------------------

------------------------------------------------------------------------

# Plantillas clínicas --- DT-26

DT-26 agrega plantillas clínicas reutilizables para acelerar la captura de consultas sin crear dependencias mutables con el registro clínico.

Implementado:

-   administración de plantillas clínicas aisladas por tenant;
-   creación y edición con nombre, descripción, motivo de consulta y estructura SOAP;
-   activación y desactivación;
-   aplicación desde el workspace de consulta;
-   al aplicar una plantilla se copia su contenido al registro clínico como snapshot;
-   editar posteriormente la plantilla no modifica consultas ya capturadas;
-   sólo se ofrecen plantillas activas del tenant actual;
-   contador de usos;
-   eliminación permitida únicamente cuando la plantilla no ha sido utilizada;
-   plantillas utilizadas pueden desactivarse, pero no eliminarse;
-   auditoría de operaciones relevantes y aplicación;
-   confirmaciones de acciones mediante SweetAlert;
-   botones alineados con el sistema visual existente de DocTotal;
-   cobertura automatizada de aislamiento multi-tenant y comportamiento funcional.

Plantillas por especialidad permanecen como evolución posterior sobre esta foundation.

Calidad final DT-26:

`1011 tests verdes`

`0 failures`

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

------------------------------------------------------------------------

# Laboratorios estructurados --- DT-27

DT-27 agrega resultados de laboratorio estructurados dentro del expediente clínico sin interpretación clínica automática.

Incluye:

-   Estudios asociados al paciente y opcionalmente a una consulta.
-   Nombre del estudio, fecha, laboratorio/proveedor opcional y observaciones.
-   Múltiples parámetros por estudio con nombre, valor, unidad y rango de referencia opcional.
-   Valores numéricos o textuales.
-   Historial de laboratorios accesible desde el expediente.
-   Resumen visual de laboratorios dentro del expediente del paciente.
-   Alta, edición y eliminación de estudios.
-   Eliminación explícita de resultados asociados cuando un estudio con soft delete es eliminado.
-   Captura manual de parámetros.
-   Captura masiva mediante pegado de filas desde Excel/Google Sheets o texto separado por tabulador, `|` o `;`.
-   Revisión y edición de las filas convertidas antes de persistirlas.
-   Aislamiento multi-tenant mediante `TenantScope`, `TenantContext` y `BelongsToTenant`.
-   Auditoría de altas, cambios y eliminaciones.
-   Cobertura automatizada de autorización, tenant, asociación a consulta, edición, eliminación y captura masiva.

Decisión de producto:

Los documentos clínicos y los laboratorios estructurados cumplen funciones distintas. Un documento clínico conserva el archivo original; Laboratorios conserva los datos estructurados del estudio. Una futura evolución puede vincular ambos.

Fuera de alcance de DT-27:

-   OCR.
-   Extracción automática desde PDF o imagen.
-   Importaciones automáticas de proveedores.
-   HL7/FHIR.
-   Interpretación clínica automática o mediante IA.
-   Catálogo nacional de estudios.
-   Gráficas longitudinales avanzadas.

Calidad final DT-27:

`1021 tests verdes`

`0 failures`

Commit principal:

`a1a66a1 DT-27 feat: add structured laboratory results`.



------------------------------------------------------------------------

# Seguridad de cuenta y recuperación --- DT-28

DT-28 completa la capa visible de seguridad de cuenta sobre Laravel Fortify.

Implementado:

-   cambio de contraseña con contraseña actual, confirmación y reglas de Laravel;
-   auditoría `account.password.updated` sin credenciales;
-   2FA TOTP con QR, confirmación, recovery codes, regeneración y desactivación;
-   challenge 2FA en login y códigos de recuperación de un solo uso;
-   protección de secretos 2FA y exposición temporal autorizada de QR/recovery codes;
-   sesiones y dispositivos visibles sin exponer IDs reales de sesión;
-   revocación individual y de todas las demás sesiones con contraseña actual;
-   rotación del remember token al revocar sesiones;
-   verificación de correo con notificación personalizada, enlace firmado y reenvío;
-   middleware de correo verificado en onboarding, billing, consola interna y aplicación clínica;
-   Configuración → Seguridad accesible para usuarios no verificados y tenants suspendidos;
-   auditoría `account.email.verified` sin URL firmada ni hash de verificación;
-   compatibilidad de redirección para administradores internos;
-   cobertura automatizada de contraseña, 2FA, sesiones y verificación de correo.

Passkeys/WebAuthn:

La infraestructura actual de Laravel 13/Fortify ya contiene foundation de passkeys y persistencia. Su activación se difiere hasta definir el hostname productivo HTTPS canónico y configurar de forma definitiva el relying party y los allowed origins de WebAuthn. No se registrarán credenciales productivas contra dominios locales de desarrollo.

Calidad final DT-28:

`1068 tests verdes`

`0 failures`

Commit principal:

Pendiente hasta integrar DT-28 en `master`.
