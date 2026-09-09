# DocTotal — Roadmap

Jira es la fuente de verdad para identificadores y estados. Este documento registra la evolución canónica del producto.

## Baseline canónico en DT-48

- `master` post-DT-47: `eba60d9881f510da40127462154de4858502333d`
- DT-1 … DT-47: **Listo**
- DT-48: **En curso**
- Alcance V1.0 versionado: **100%** al integrar DT-48, según criterio documentado en `docs/RELEASE_V1_0_READINESS.md`.
- GitHub Actions es la validación técnica canónica; el 100% no representa cobertura de tests.

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
| DT-47 | Foundation productiva de email, readiness y runbook operacional | Listo |
| DT-48 | Cierre formal V1.0, release readiness y reconciliación canónica final | En curso |

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

DT-46 añadió checker, monitoreo agregado y runbook operativo. La introducción del primer job asíncrono real deberá ser un cambio versionado que revise idempotencia, tenant context y privacidad antes de cambiar a modo `workers`.

### Entrega de email productivo — DT-47

DT-47 preparó el correo real necesario para verificación de cuenta, recuperación y comunicaciones transaccionales sin acoplar el dominio a un proveedor único.

Añadió `LaravelMailCommunicationTransport`, activación explícita, `EmailDeliveryChecker`, integración con Production Readiness y `docs/OPERATIONS_EMAIL_DELIVERY.md`. Las credenciales reales, DNS y proveedor concreto permanecen como responsabilidad de infraestructura/despliegue y nunca se versionan.

### Cierre formal de V1.0 — DT-48

DT-48 separa explícitamente tres conceptos:

1. **Codebase V1.0 release-ready**: alcance funcional/técnico comprometido cerrado en Git.
2. **Gates de despliegue**: configuración real de HTTPS, Stripe, email/DNS, backups, scheduler, observabilidad y probes en el entorno objetivo.
3. **Evolución futura**: decisiones legales/comerciales y funcionalidades deliberadamente fuera de V1.0.

La auditoría de cierre no encontró marcadores `FIXME` y no identificó marcadores `TODO` técnicos críticos ocultos. Los pendientes conocidos quedan clasificados de forma explícita en `docs/TODO.md` y `docs/RELEASE_V1_0_READINESS.md`.

El avance formal se recalcula al **100% del alcance V1.0 versionado** al integrar DT-48. Este porcentaje no implica que cualquier servidor esté listo para producción sin completar sus gates operativos.

## Baseline de release V1.0

El tag/referencia V1.0 debe crearse únicamente después de:

- aprobar DT-48;
- integrar su PR mediante el workflow canónico;
- verificar el `master` resultante;
- confirmar CI verde sobre el cierre.

Por tanto, este documento no fija por adelantado el SHA final de V1.0. Ese SHA será el `master` verificado después del merge de DT-48.

## Después de V1.0

Los siguientes bloques solo deben abrirse cuando exista una necesidad concreta. Candidatos actuales:

1. WhatsApp/SMS si pasan a formar parte explícita del alcance comercial.
2. Política legal definitiva de retención/eliminación.
3. Cuotas y estrategia externa de almacenamiento.
4. Política de reembolsos y efecto en promociones/comisiones.
5. Funcionalidades diferidas como passkeys, firma/QR, CFDI, OCR/IA, DICOM/PACS, HL7/FHIR, SIEM o multi-plan cuando cambie el alcance.

Los siguientes IDs DT deben obtenerse exclusivamente desde Jira al crear formalmente cada ticket.
