# DocTotal — Project Context

Documento de continuidad técnica y funcional. `TODO.md` describe estado/pending; `ROADMAP.md` registra bloques DT; Jira es fuente de verdad para IDs/estados.

## Stack y workflow

- PHP 8.4 / Laravel 13 / Blade + Livewire/Volt / Tailwind CSS.
- MySQL en desarrollo/producción; SQLite in-memory en tests.
- Repo: `alecz2303/DocTotal`; rama principal: `master`.
- Baseline al iniciar DT-45: `108a5c79038c1986f5081a5aa9e8b34ed994f105` (post-DT-44).
- DT-1 a DT-44: `Listo`; DT-45: `En curso`.
- Avance global ponderado formal: `94%`; no se recalcula en DT-45.
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
- `ProductionReadinessChecker` incorpora sus fallos, por lo que producción no se considera ready con observabilidad incompleta;
- `ProductionExceptionReporter` se registra en `bootstrap/app.php` mediante el pipeline de reportes de excepciones;
- el reporter solo actúa en `production` y cuando observabilidad está habilitada;
- contexto permitido: clase de excepción, código entero, fingerprint SHA-256, método HTTP, nombre de ruta y opcionalmente basename/line de origen;
- no registra mensaje de excepción, stack trace completo, body, query string, headers, cookies, URL completa, email, nombres de pacientes ni contenido clínico;
- `docs/OPERATIONS_INCIDENT_RESPONSE.md` define severidad, detección, diagnóstico, mitigación, escalamiento, recuperación y verificación posterior;
- un incidente no se cierra solo porque la app responda: debe comprobarse integridad, aislamiento multi-tenant y ausencia de pérdida/corrupción cuando aplique.

La salida puede conectarse a infraestructura/proveedor externo mediante el canal de logging configurado. DocTotal no necesita acoplar el dominio clínico a un SDK concreto ni almacenar credenciales del proveedor en modelos de aplicación.

## Comunicaciones

La arquitectura continúa independiente de proveedor (`CommunicationTransport`, manager, processor, reminders y preferencias). Sin transport configurado no se simula éxito. Email/WhatsApp/SMS reales siguen siendo decisiones de despliegue/producto.

## Baseline de calidad

No se documenta un conteo nuevo de tests/assertions hasta que GitHub Actions valide el SHA consolidado. No se inventan cifras. Avance formal vigente: `94%`.

## Pendientes reales después de DT-45

- queue/worker topology y failed-job monitoring del entorno objetivo;
- proveedor real de correo si es requisito de lanzamiento V1.0;
- WhatsApp/SMS si se incluyen en lanzamiento;
- decisiones de cuotas/proveedor de almacenamiento;
- política legal definitiva de retención/eliminación;
- cierre formal de V1.0 / release readiness.

## Diferidos deliberadamente

Passkeys hasta fijar origen productivo; DICOM/PACS; OCR/IA clínica; HL7/FHIR; firma/QR de recetas; CFDI; SIEM completo; impersonación/herramientas destructivas; multi-plan mientras no sea necesario.
