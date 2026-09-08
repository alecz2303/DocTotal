# DocTotal — Roadmap

Jira es la fuente de verdad para identificadores y estados. Este documento registra la evolución canónica del producto.

## Baseline canónico al iniciar DT-47

- `master`: `8c3ffb80f6c1d87050d2ea3025217576e71fb53e`
- Último bloque integrado: **DT-46 — Define production queue/worker topology and failed-job monitoring**
- DT-1 … DT-46: **Listo**
- DT-47: **En curso**
- Avance global ponderado formal vigente: **94%**
- No se recalcula porcentaje ni se inventan cifras de tests/assertions en DT-47.

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
| DT-46 | Topología queue/worker y monitoreo seguro de failed jobs | Listo |
| DT-47 | Foundation productiva de email, readiness y runbook operacional | En curso |

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

La auditoría de DT-46 confirmó que el código actual no contiene jobs `ShouldQueue`. Billing y comunicaciones periódicas se ejecutan mediante Laravel Scheduler, por lo que la topología canónica V1.0 es `scheduler_only`.

DT-46 añadió:

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

### Entrega de email productivo — DT-47

DT-47 prepara el correo real necesario para verificación de cuenta, recuperación y comunicaciones transaccionales sin acoplar el dominio a un proveedor único.

DT-47 añade:

- `LaravelMailCommunicationTransport` para el canal email;
- activación explícita mediante `DOCTOTAL_EMAIL_COMMUNICATIONS_ENABLED`;
- `EmailDeliveryChecker` integrado a Production Readiness;
- validación de mailer real, remitente válido, SMTP remoto cuando aplica y runbook versionado;
- `docs/OPERATIONS_EMAIL_DELIVERY.md` con configuración, SPF/DKIM/DMARC, prueba controlada, diagnóstico y rollback;
- tests de estados productivos seguros/inseguros;
- continuidad de flujos existentes de verificación y recuperación a través de Laravel Mail.

Las credenciales reales, DNS y proveedor concreto permanecen como responsabilidad de infraestructura/despliegue y nunca se versionan.

## Próximos candidatos después de DT-47

1. WhatsApp/SMS solo si forman parte del lanzamiento V1.0.
2. Cierre formal de V1.0 / release readiness y reconciliación canónica final.
3. Decisiones legales/operativas pendientes que bloqueen realmente el lanzamiento.

Los siguientes IDs DT deben obtenerse exclusivamente desde Jira al crear formalmente cada ticket.
