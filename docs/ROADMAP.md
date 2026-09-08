# DocTotal — Roadmap

Jira es la fuente de verdad para identificadores y estados. Este documento registra la evolución canónica del producto.

## Baseline canónico al iniciar DT-46

- `master`: `084aec1564c256d43a01663974a71b1298e47c80`
- Último bloque integrado: **DT-45 — Add production observability, error tracking and operational response**
- DT-1 … DT-45: **Listo**
- DT-46: **En curso**
- Avance global ponderado formal vigente: **94%**
- No se recalcula porcentaje ni se inventan cifras de tests/assertions en DT-46.

## Historial DT

| Rango / DT | Objetivo resumido | Estado Jira |
|---|---|---|
| DT-1 — DT-10 | Foundation SaaS multi-tenant, pacientes, auth, onboarding, agenda y consulta | Listo |
| DT-11 — DT-21 | Billing, referidos, expediente longitudinal, documentos, UI, comunicaciones y auditoría | Listo |
| DT-22 — DT-30 | Administración interna, autoservicio, plantillas, laboratorios, seguridad y reprogramación | Listo |
| DT-31 — DT-41 | CI y hardening DocTotal 1.0, billing, alertas, runtime, flujo diario y reconciliación | Listo |
| DT-42 | Centro global de archivos clínicos y documento fuente de laboratorios | Listo |
| DT-43 | Foundation de backup, restauración y retención operativa | Listo |
| DT-44 | Visibilidad operativa de backups en administración interna | Listo |
| DT-45 | Observabilidad, error tracking seguro y respuesta operacional | Listo |
| DT-46 | Topología queue/worker y monitoreo seguro de failed jobs | En curso |

## Evolución por etapas

### Foundation y núcleo clínico/SaaS — DT-1 a DT-30

Se establecieron multi-tenancy, pacientes, expediente, agenda, consulta, billing, comunicaciones, seguridad, administración interna y autoservicio.

### Hardening DocTotal 1.0 — DT-31 a DT-41

Se automatizó CI y se endurecieron comunicaciones, billing, alertas clínicas, configuración/runtime de producción, flujo diario y documentación canónica.

### Evolución clínica y durabilidad — DT-42 a DT-44

DT-42 consolidó el centro de archivos clínicos. DT-43 estableció backup/restauración verificable y retención segura. DT-44 añadió visibilidad interna de esa estrategia sin ejecutar dumps, descargas o restores desde la aplicación.

### Observabilidad operativa — DT-45

DT-45 añadió configuración explícita de observabilidad, checker integrado a Production Readiness, reporter de excepciones con contexto técnico mínimo y runbook de respuesta a incidentes, manteniendo privacidad clínica y desacoplamiento de proveedor.

### Scheduler, workers y failed jobs — DT-46

La auditoría de DT-46 confirma que el código actual no contiene jobs `ShouldQueue`. Billing y comunicaciones periódicas se ejecutan mediante Laravel Scheduler, por lo que la topología canónica V1.0 es `scheduler_only`.

DT-46 añade:

- `config/queue_operations.php` con modo operativo explícito;
- workers deshabilitados mientras no exista trabajo asíncrono real;
- parámetros versionados para activación futura de workers;
- `QueueOperationsChecker` integrado a `ProductionReadinessChecker`;
- validación de modo, worker, tries, timeout y relación `retry_after > timeout` cuando aplica;
- monitoreo de pending/failed jobs mediante conteos agregados, sin payloads;
- `doctotal:check-queue-operations` con exit code utilizable por infraestructura de alertamiento;
- runbook `docs/OPERATIONS_QUEUE_WORKERS.md` para scheduler, activación futura, diagnóstico y retry seguro;
- tests de readiness, privacidad y umbral de failed jobs.

La introducción del primer job asíncrono real deberá ser un cambio versionado que revise idempotencia, tenant context y privacidad antes de cambiar a modo `workers`.

## Próximos candidatos después de DT-46

1. Proveedor real de correo si es requisito de lanzamiento V1.0.
2. WhatsApp/SMS cuando el lanzamiento lo requiera.
3. Cierre formal de V1.0 / release readiness y reconciliación canónica final.

Los siguientes IDs DT deben obtenerse exclusivamente desde Jira al crear formalmente cada ticket.
