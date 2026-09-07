# DocTotal — Project Context

Documento de continuidad técnica y funcional. `TODO.md` describe el estado/pending real; `ROADMAP.md` registra los bloques DT; Jira es la fuente de verdad para IDs y estados.

## Stack

- PHP 8.4
- Laravel 13
- Blade + Livewire/Volt
- Tailwind CSS
- MySQL en desarrollo/producción
- SQLite in-memory en tests
- PHPUnit / Laravel Feature Tests / Livewire tests

## Repositorio y workflow

- Repo: `alecz2303/DocTotal`
- Rama principal: `master`
- `master` canónico post-DT-40: `1a837fac891cf4c4f2a210b50233e8f066d2ec04`
- Commit post-DT-40: `DT-40 feat: add SweetAlert feedback to internal trial settings`
- Jira project key: `DT`
- Jira es la fuente de verdad para IDs DT; nunca se inventa el siguiente número.
- GitHub Actions es la validación técnica canónica.
- Antes de PR, cada rama DT debe quedar en exactamente un commit consolidado y CI verde sobre ese SHA.
- Después del PR se solicita reviewer `aruedaboldr`, se verifica CI, Jira pasa a `En revisión` y se espera aprobación humana.
- No se hace merge sin aprobación explícita.

## Estado documental actual

DT-41 reconcilia los documentos canónicos después del hardening de DocTotal 1.0.

Estado verificado al iniciar DT-41:

- DT-1 a DT-40: `Listo` en Jira.
- DT-41: trabajo documental.
- Avance global ponderado formal: `94%`.
- No se recalcula el porcentaje en DT-41.
- No se inventan cifras de tests/assertions. Los baselines históricos sólo son válidos cuando fueron registrados explícitamente.

## Multi-tenancy

El aislamiento por tenant es un requisito obligatorio e innegociable.

Foundation:

- `TenantContext`
- `TenantScope`
- `App\Traits\BelongsToTenant`
- middleware de resolución del tenant
- cobertura de aislamiento en módulos clínicos/SaaS

Las lecturas globales de administración interna deben permanecer explícitas, encapsuladas y testeadas. No se permiten bypasses cross-tenant dispersos.

## Foundation clínica integrada

DocTotal incluye actualmente:

- pacientes, contactos de emergencia y antecedentes;
- expediente clínico longitudinal;
- agenda y ciclo completo de citas;
- autoservicio público de confirmación/cancelación/reprogramación de citas;
- enlaces manuales seguros de gestión de cita;
- consultas persistentes `draft/completed`;
- workspace clínico con autosave y protección de cambios;
- diagnósticos y catálogo diagnóstico;
- recetas, catálogo de medicamentos y repetición trazable de receta;
- problemas clínicos activos/resueltos (`PatientProblem`);
- documentos clínicos privados;
- plantillas clínicas por tenant;
- laboratorios estructurados con captura masiva revisable;
- alertas clínicas contextuales deterministas y trazables.

Fuentes clínicas explícitas:

- `PatientMedicalHistory`: alergias, medicamentos actuales, antecedentes, enfermedades crónicas y cirugías.
- `PatientProblem`: problemas clínicos longitudinales activos/resueltos.

No se infieren automáticamente medicamentos actuales desde recetas históricas ni problemas activos desde diagnósticos históricos. Las alertas contextuales no realizan diagnóstico automático ni recomendaciones terapéuticas.

## Foundation SaaS integrada

- Registro, autenticación y onboarding.
- Trial y derecho de acceso centralizado.
- Subscription lifecycle mensual/anual.
- Stripe, pagos, métodos de pago, renovación, recuperación, grace, suspensión y reactivación.
- Webhooks Stripe autenticados/idempotentes y sincronización de estados — DT-33.
- Comprobante operativo de pago — DT-33.
- Referidos y créditos promocionales — DT-13.
- Códigos promocionales y comisiones de vendedores — DT-39.
- Comunicaciones transaccionales/recordatorios — DT-20/DT-32.
- Administración interna SaaS — DT-22.
- Estado de trial/suscripción/pago visible en experiencia médica — DT-37.
- Duración de trial configurable — DT-38.
- Feedback SweetAlert de ajustes de trial — DT-40.

El estado efectivo de acceso no debe inferirse únicamente de `Tenant.status`; depende de trial, suscripción, grace period y suspensión/cancelación según las reglas existentes del dominio.

## Seguridad y auditoría

- `AuditEvent` + `AuditLogger` y sanitización de metadata sensible — DT-21.
- Auditoría actual es best-effort; no equivale a inmutabilidad DB.
- Cambio de contraseña, 2FA TOTP, recovery codes, verificación de correo y sesiones/dispositivos — DT-28.
- Passkeys/WebAuthn fueron evaluadas, pero su activación se difiere hasta fijar hostname HTTPS canónico y relying party/origins productivos.
- Credenciales, OTP, recovery codes, tokens y IDs reales de sesión no deben persistirse en auditoría.

## Producción y operaciones

DT-35 añadió:

- validación explícita de configuración crítica;
- fail-fast del runtime HTTP;
- Host canónico;
- cookies de sesión seguras por defecto en producción;
- validación de logging;
- probes operativos de DB, cache locks y backend de colas;
- comando `php artisan doctotal:check-production-readiness --probe`.

Ese hardening no debe confundirse con una estrategia de durabilidad de datos. Permanecen como brechas reales:

- backup verificable;
- restauración probada;
- política de retención/eliminación;
- cuotas/operación de almacenamiento;
- monitoreo/error tracking y procedimientos operativos del entorno objetivo.

## Comunicaciones

La arquitectura permanece independiente de proveedor.

- `Communication`
- `CommunicationTransport`
- `CommunicationTransportManager`
- `CommunicationProcessor`
- `AppointmentReminderService`
- `AppointmentReminderValidator`
- preferencias y elegibilidad por canal;
- claim transaccional + estado `processing`;
- redacción de secretos/tokens en errores persistidos.

Sin transport configurado no se simula éxito. La selección de proveedores reales de email/WhatsApp/SMS es una decisión de despliegue/producto todavía pendiente.

## Estado comercial y experiencia médica

DT-36 consolidó la prioridad operativa diaria en dashboard/agenda.

DT-37 hizo visible, sin duplicar reglas de dominio, el estado de trial, suscripción, pagos fallidos/pending y riesgo de suspensión dentro de onboarding/dashboard.

DT-38 trasladó la duración del trial a configuración interna.

DT-39 añadió un canal comercial separado del referral médico: sellers/promoters, códigos promocionales, atribución inmutable y ledger de comisiones con snapshots económicos ligados a pagos exitosos.

DT-40 añadió feedback SweetAlert al ajuste interno de trial.

## Baseline de calidad

No existe en DT-41 un nuevo conteo formal de tests/assertions que deba documentarse.

Referencias históricas sólo se conservan en los commits/documentos de los DT donde fueron explícitamente registradas. Para el estado actual, la autoridad técnica es GitHub Actions sobre el SHA que se valida.

Avance global ponderado formal vigente:

`94%`

No se modifica sin una recalculación ponderada formal.

## Pendientes reales vs diferidos

### Pendientes reales

- estrategia de backup y restauración;
- retención/eliminación operacional de datos clínicos/documentales;
- observabilidad operativa del entorno productivo;
- proveedores reales de comunicaciones cuando se requieran;
- decisiones de cuotas/proveedor de almacenamiento.

### Diferidos deliberadamente

- passkeys hasta fijar origen productivo;
- DICOM/PACS;
- OCR/IA clínica;
- HL7/FHIR;
- firma/QR de recetas hasta cerrar requisitos legales;
- facturación fiscal/CFDI hasta definir alcance;
- SIEM, impersonación y herramientas destructivas masivas;
- multi-plan si el producto comercial lo requiere.

### Decisiones de producto/operación

- política de reembolsos;
- efecto de reembolsos sobre promociones/comisiones;
- retención legal/operativa;
- cuotas de almacenamiento;
- proveedores definitivos de correo/WhatsApp/SMS/storage externo;
- requisitos legales de documentos/recetas.

## Siguiente candidato de desarrollo

Después de reconciliar el estado real post-DT-40, el siguiente candidato recomendado es:

**Production data durability: backup, restore and retention foundation.**

No se asigna un ID aquí. Si se aprueba ese bloque, el identificador debe crearse/obtenerse directamente en Jira.
