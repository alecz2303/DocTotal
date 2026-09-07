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
- `master` canónico post-DT-42: `cc5a1231527cdc5f4bfb232faae358a83b03a998`
- Commit post-DT-42: `DT-42 feat: add global clinical files center`
- Jira project key: `DT`
- Jira es la fuente de verdad para IDs DT; nunca se inventa el siguiente número.
- GitHub Actions es la validación técnica canónica.
- Antes de PR, cada rama DT debe quedar en exactamente un commit consolidado y CI verde sobre ese SHA.
- Después del PR se solicita reviewer `aruedaboldr`, se verifica CI, Jira pasa a `En revisión` y se espera aprobación humana.
- No se hace merge sin aprobación explícita.

## Estado documental actual

DT-42 quedó integrado y cerrado. DT-43 trabaja la foundation de durabilidad operativa para backup, restauración y retención segura.

Estado canónico al iniciar DT-43:

- DT-1 a DT-42: `Listo` en Jira.
- DT-43: `En curso` en Jira.
- Baseline `master`: `cc5a1231527cdc5f4bfb232faae358a83b03a998`.
- Avance global ponderado formal: `94%`.
- No se recalcula el porcentaje en DT-43.
- No se inventan cifras de tests/assertions. La autoridad técnica es GitHub Actions sobre el SHA validado.

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
- centro global tenant-scoped de archivos clínicos con búsqueda, filtros, acceso al expediente y acciones privadas de ver/descargar — DT-42;
- plantillas clínicas por tenant;
- laboratorios estructurados con captura masiva revisable;
- vínculo opcional entre laboratorio estructurado y documento fuente de laboratorio del mismo paciente/tenant — DT-42;
- alertas clínicas contextuales deterministas y trazables.

`ClinicalDocument` continúa siendo la única entidad de almacenamiento documental clínico. DT-42 no introduce un repositorio paralelo: la vista global `Archivos` reutiliza documentos, autorización, almacenamiento privado y rutas de visualización/descarga existentes. `LaboratoryStudy.clinical_document_id` es nullable y el documento fuente se desacopla con `nullOnDelete` si el archivo es eliminado.

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
- Los vínculos de laboratorio a documento fuente se validan contra el paciente actual y mediante queries tenant-scoped; no se acepta selección cross-patient/cross-tenant desde el flujo clínico.

## Producción y operaciones

DT-35 añadió:

- validación explícita de configuración crítica;
- fail-fast del runtime HTTP;
- Host canónico;
- cookies de sesión seguras por defecto en producción;
- validación de logging;
- probes operativos de DB, cache locks y backend de colas;
- comando `php artisan doctotal:check-production-readiness --probe`.

DT-43 añade una foundation separada de durabilidad de datos:

- configuración `config/data_durability.php`;
- cobertura obligatoria de backup de base de datos y archivos clínicos privados;
- proveedor/mecanismo operativo de backup declarado explícitamente;
- frecuencia máxima y número mínimo de copias declarados;
- runbook versionado `docs/OPERATIONS_DATA_DURABILITY.md`;
- comando `php artisan doctotal:check-data-durability`;
- integración del checker de durabilidad dentro de `ProductionReadinessChecker`;
- restauración considerada válida sólo después de verificación operativa;
- retención segura con modos `manual`/`policy` sin activar borrado automático;
- `DOCTOTAL_RETENTION_AUTOMATIC_DELETION_ENABLED=true` se considera configuración insegura y bloquea readiness.

DocTotal no ejecuta dumps genéricos desde la aplicación ni almacena credenciales de backup. La ejecución real corresponde a infraestructura/proveedor; la aplicación valida que exista una estrategia declarada, completa y compatible con una restauración verificable.

La política legal definitiva de conservación clínica sigue abierta. DT-43 no implementa eliminación destructiva de expedientes ni decide plazos regulatorios.

Permanecen como brechas reales de producción:

- monitoreo/error tracking y procedimiento de respuesta operacional;
- queue/worker topology y monitoreo de failed jobs del entorno objetivo;
- cuotas/operación de almacenamiento;
- proveedores reales de comunicaciones cuando el lanzamiento los requiera.

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

No se documenta en DT-43 un nuevo conteo formal de tests/assertions.

Referencias históricas sólo se conservan en los commits/documentos de los DT donde fueron explícitamente registradas. Para el estado actual, la autoridad técnica es GitHub Actions sobre el SHA que se valida.

Avance global ponderado formal vigente:

`94%`

No se modifica sin una recalculación ponderada formal.

## Pendientes reales vs diferidos

### Pendientes reales

- observabilidad operativa del entorno productivo;
- queue/worker topology y failed-job monitoring del entorno objetivo;
- proveedores reales de comunicaciones cuando se requieran;
- decisiones de cuotas/proveedor de almacenamiento;
- política legal definitiva de retención/eliminación clínica.

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
- retención legal definitiva;
- cuotas de almacenamiento;
- proveedores definitivos de correo/WhatsApp/SMS/storage externo;
- requisitos legales de documentos/recetas.

## Siguientes candidatos de desarrollo

Después de DT-43, los candidatos recomendados son:

1. **Production monitoring/error tracking + operational response procedure.**
2. **Queue/worker topology + failed-job monitoring.**
3. Proveedores reales de comunicaciones cuando el lanzamiento los requiera.

Los IDs se obtienen exclusivamente desde Jira al crear formalmente los tickets.
