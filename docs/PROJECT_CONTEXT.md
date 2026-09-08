# DocTotal — Project Context

Documento de continuidad técnica y funcional. `TODO.md` describe estado/pending; `ROADMAP.md` registra bloques DT; Jira es fuente de verdad para IDs/estados.

## Stack y workflow

- PHP 8.4 / Laravel 13 / Blade + Livewire/Volt / Tailwind CSS.
- MySQL en desarrollo/producción; SQLite in-memory en tests.
- Repo: `alecz2303/DocTotal`; rama principal: `master`.
- Baseline al iniciar DT-46: `084aec1564c256d43a01663974a71b1298e47c80` (post-DT-45).
- DT-1 a DT-45: `Listo`; DT-46: `En curso`.
- Avance global ponderado formal: `94%`; no se recalcula en DT-46.
- GitHub Actions es la validación técnica canónica.
- Antes de PR: rama en exactamente un commit, CI verde sobre ese SHA; luego PR, reviewer `aruedaboldr`, CI PR, Jira `En revisión` y espera de aprobación humana.

## Requisitos transversales

El aislamiento multi-tenant es obligatorio. Las lecturas globales de administración interna deben ser explícitas, encapsuladas y testeadas. No se permiten bypasses cross-tenant dispersos.

Credenciales, tokens, OTP, recovery codes, IDs reales de sesión, payload clínico y demás datos sensibles no deben incorporarse a auditoría u observabilidad salvo necesidad explícita y controlada.

## Foundation clínica integrada

DocTotal incluye pacientes y expediente longitudinal, agenda y ciclo completo de citas, autoservicio público, consultas persistentes con autosave, diagnósticos, recetas, problemas activos, plantillas, alertas contextuales, documentos clínicos privados, centro global de archivos y laboratorios estructurados con documento fuente opcional.

`ClinicalDocument` continúa siendo la entidad canónica de almacenamiento documental. No se infieren automáticamente medicamentos actuales desde recetas históricas ni problemas activos desde diagnósticos históricos.

## Foundation SaaS integrada

Incluye registro/auth/onboarding, trial y acceso centralizado, subscription lifecycle, Stripe y recuperación de pagos, webhooks autenticados/idempotentes, comprobantes, referidos/créditos, códigos promocionales/comisiones, comunicaciones transaccionales y administración interna.

## Seguridad, durabilidad y producción

DT-21 introdujo auditoría y sanitización. DT-28 añadió cambio de contraseña, 2FA, recovery codes, verificación de correo y sesiones/dispositivos.

DT-35 añadió production readiness, fail-fast HTTP, host canónico, cookies seguras, logging y probes DB/cache/queue.

DT-43 añadió `config/data_durability.php`, backup obligatorio de BD + archivos privados, mecanismo declarado, frecuencia/copias, runbook de restauración, checker y retención segura sin borrado automático.

DT-44 añadió la pantalla interna de Backups de solo lectura, reutilizando `DataDurabilityChecker`, sin dumps, restores, descargas, credenciales ni contenido clínico.

## Observabilidad — DT-45

DT-45 cierra la brecha de monitoreo/error tracking productivo con una foundation desacoplada del proveedor:

- `config/observability.php` declara enabled, canal, alertamiento, runbook y política de ubicación de excepción;
- `ObservabilityChecker` exige observabilidad habilitada, canal válido, alertamiento declarado y runbook existente;
- `ProductionReadinessChecker` incorpora sus fallos;
- `ProductionExceptionReporter` se registra en `bootstrap/app.php`;
- contexto permitido: clase/código/fingerprint, método HTTP, nombre de ruta y opcionalmente basename/line;
- no registra mensaje de excepción, stack trace completo, body, query string, headers, cookies, URL completa, email, nombres de pacientes ni contenido clínico;
- `docs/OPERATIONS_INCIDENT_RESPONSE.md` define severidad, detección, diagnóstico, mitigación, escalamiento, recuperación y verificación posterior.

## Scheduler, queues y workers — DT-46

La auditoría real del código durante DT-46 no encontró jobs que implementen `ShouldQueue`. Los procesos recurrentes actuales de billing y comunicaciones se ejecutan desde `routes/console.php` mediante Laravel Scheduler.

La topología canónica de V1.0 queda declarada como `scheduler_only`:

- el servidor debe ejecutar `php artisan schedule:run` cada minuto;
- no debe mantenerse un `queue:work` activo mientras no exista trabajo asíncrono real;
- `config/queue_operations.php` declara modo, parámetros futuros de worker, monitoreo de failed jobs y runbook;
- `QueueOperationsChecker` forma parte de `ProductionReadinessChecker`;
- el modo `workers` exige worker habilitado, cola explícita, tries/timeout válidos y `retry_after > timeout` cuando el driver lo soporta;
- `QueueOperationsMonitor` solo expone modo y conteos agregados de pending/failed jobs; no lee ni retorna payloads o excepciones;
- `doctotal:check-queue-operations` puede utilizarse desde infraestructura para detectar configuración insegura o alcanzar el umbral de failed jobs;
- `docs/OPERATIONS_QUEUE_WORKERS.md` documenta activación futura, supervisión, diagnóstico y retry seguro.

El primer job `ShouldQueue` futuro debe revisar explícitamente idempotencia, tenant context, payload mínimo y efectos externos antes de cambiar la topología a `workers`.

## Comunicaciones

La arquitectura continúa independiente de proveedor (`CommunicationTransport`, manager, processor, reminders y preferencias). Sin transport configurado no se simula éxito. Email/WhatsApp/SMS reales siguen siendo decisiones de despliegue/producto.

## Baseline de calidad

No se documenta un conteo nuevo de tests/assertions hasta que GitHub Actions valide el SHA consolidado. No se inventan cifras. Avance formal vigente: `94%`.

## Pendientes reales después de DT-46

- proveedor real de correo si es requisito de lanzamiento V1.0;
- WhatsApp/SMS si se incluyen en lanzamiento;
- decisiones de cuotas/proveedor de almacenamiento;
- política legal definitiva de retención/eliminación;
- cierre formal de V1.0 / release readiness.

## Diferidos deliberadamente

Passkeys hasta fijar origen productivo; DICOM/PACS; OCR/IA clínica; HL7/FHIR; firma/QR de recetas; CFDI; SIEM completo; impersonación/herramientas destructivas; multi-plan mientras no sea necesario.
