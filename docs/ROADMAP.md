# DocTotal — Roadmap

Jira es la fuente de verdad para identificadores y estados. Este documento registra la evolución canónica del producto.

## Baseline canónico al iniciar DT-45

- `master`: `108a5c79038c1986f5081a5aa9e8b34ed994f105`
- Último bloque integrado: **DT-44 — Add operational backup visibility to internal administration**
- DT-1 … DT-44: **Listo**
- DT-45: **En curso**
- Avance global ponderado formal vigente: **94%**
- No se recalcula porcentaje ni se inventan cifras de tests/assertions en DT-45.

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
| DT-45 | Observabilidad, error tracking seguro y respuesta operacional | En curso |

## Evolución por etapas

### Foundation y núcleo clínico/SaaS — DT-1 a DT-30

Se establecieron multi-tenancy, pacientes, expediente, agenda, consulta, billing, comunicaciones, seguridad, administración interna y autoservicio.

### Hardening DocTotal 1.0 — DT-31 a DT-41

Se automatizó CI y se endurecieron comunicaciones, billing, alertas clínicas, configuración/runtime de producción, flujo diario y documentación canónica.

### Evolución clínica y durabilidad — DT-42 a DT-44

DT-42 consolidó el centro de archivos clínicos. DT-43 estableció backup/restauración verificable y retención segura. DT-44 añadió visibilidad interna de esa estrategia sin ejecutar dumps, descargas o restores desde la aplicación.

### Observabilidad operativa — DT-45

DT-45 añade una estrategia explícita y verificable de observabilidad de producción:

- `config/observability.php`;
- `ObservabilityChecker` integrado a `ProductionReadinessChecker`;
- `ProductionExceptionReporter` conectado al pipeline de excepciones de Laravel;
- contexto mínimo: clase/código/fingerprint, método HTTP y nombre de ruta, con ubicación opcional;
- no incluye mensaje de excepción, body, query string, headers, cookies, email, nombres ni contenido clínico;
- alertamiento operativo debe declararse habilitado;
- runbook versionado `docs/OPERATIONS_INCIDENT_RESPONSE.md`;
- tests de readiness y privacidad del reporter.

La integración real con un proveedor externo puede realizarse mediante el canal de logging/infraestructura configurado; DocTotal permanece desacoplado del proveedor y no almacena credenciales de observabilidad en su dominio.

## Próximos candidatos después de DT-45

1. Queue/worker topology + failed-job monitoring del entorno objetivo.
2. Proveedor real de correo si es requisito de lanzamiento V1.0.
3. WhatsApp/SMS cuando el lanzamiento lo requiera.
4. Cierre formal de V1.0 / release readiness y reconciliación canónica final.

Los siguientes IDs DT deben obtenerse exclusivamente desde Jira al crear formalmente cada ticket.
