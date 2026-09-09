# DocTotal — TODO

## Progreso general

**100% del alcance V1.0 versionado**

`████████████████████` 100%

> Este porcentaje corresponde exclusivamente al alcance comprometido de DocTotal V1.0. No representa cobertura de tests, ausencia absoluta de bugs ni preparación automática de cualquier entorno productivo.

## Estado canónico en DT-48

- DT-1 a DT-47 están cerrados en Jira (`Listo`).
- DT-48 está `En curso` y formaliza el cierre/release readiness de V1.0.
- `master` post-DT-47: `eba60d9881f510da40127462154de4858502333d`.
- GitHub Actions es la validación técnica canónica; no se inventan baselines de tests/assertions.
- `docs/RELEASE_V1_0_READINESS.md` documenta el criterio de cierre y los gates de despliegue.

## Leyenda

- `[x]` Implementado para V1.0.
- `[~]` Implementado parcialmente / evolución futura no bloqueante.
- `[ ]` Trabajo realmente pendiente.
- `[!]` Decisión de producto/operación todavía abierta y no bloqueante salvo cambio de alcance.
- `[D]` Diferido deliberadamente fuera de V1.0.

# 1. Producto clínico

- [x] Pacientes, contactos de emergencia, antecedentes y expediente longitudinal.
- [x] Agenda y ciclo completo de citas, incluido autoservicio público y reprogramación.
- [x] Consulta persistente con autosave, diagnósticos, recetas, problemas activos, plantillas y alertas contextuales.
- [x] Documentos clínicos privados y centro global tenant-scoped de archivos — DT-15/DT-42.
- [x] Laboratorios estructurados y documento fuente opcional — DT-27/DT-42.
- [~] Mejorar antecedentes cuando exista necesidad clínica concreta.
- [D] OCR, DICOM/PACS, HL7/FHIR e interpretación clínica automática/IA.
- [!] Definir cuotas totales de almacenamiento por tenant.
- [!] Definir política legal definitiva de conservación y retención documental.
- [D] Firma digital y QR/verificación externa de receta hasta cerrar requisitos legales.

# 2. Seguridad, privacidad y auditoría

- [x] Aislamiento multi-tenant como requisito transversal.
- [x] Auditoría persistente y sanitización de metadata sensible — DT-21.
- [x] Cambio de contraseña, 2FA TOTP, recovery codes, verificación de correo y sesiones/dispositivos — DT-28.
- [D] Passkeys hasta fijar hostname HTTPS canónico y relying party/origins productivos.
- [x] Foundation verificable de backup/restauración — DT-43.
- [x] Visibilidad operativa interna de backups — DT-44.
- [~] Foundation de retención segura sin borrado automático; política legal definitiva pendiente.
- [~] Ampliar auditoría a más mutaciones según riesgo.
- [D] Inmutabilidad DB y outbox transaccional para auditoría.

# 3. SaaS, billing y ciclo comercial

- [x] Trial, derecho de acceso y estado comercial visible.
- [x] Subscription lifecycle mensual/anual, Stripe, pagos, renovación, recuperación, grace y suspensión/reactivación.
- [x] Webhooks Stripe autenticados/idempotentes y comprobante operativo — DT-33.
- [x] Referidos, créditos promocionales, códigos comerciales y comisiones — DT-13/DT-39.
- [x] CI automatizado para ramas DT y PR hacia `master` — DT-31.
- [!] Definir política comercial definitiva de reembolso y su efecto en promociones/comisiones.
- [D] CFDI hasta definir alcance legal/proveedor.
- [D] Multi-plan mientras exista una sola oferta comercial.

# 4. Comunicaciones

- [x] Foundation multi-tenant de comunicaciones y recordatorios — DT-20/DT-32.
- [x] Preferencias, elegibilidad, claim transaccional, processing y redacción de errores.
- [x] Enlaces de gestión de cita integrados.
- [x] Foundation productiva de email desacoplada de proveedor, con transport Laravel Mail, readiness y runbook — DT-47.
- [~] Configurar credenciales/proveedor real y validar SPF/DKIM/DMARC en la infraestructura de lanzamiento.
- [D] WhatsApp para V1.0 mientras no forme parte explícita del alcance comercial del lanzamiento.
- [D] SMS para V1.0 mientras no forme parte explícita del alcance comercial del lanzamiento.
- [D] Campañas de marketing/envíos masivos.

# 5. Operación interna y producción

- [x] Consola administrativa interna SaaS — DT-22.
- [x] Hardening de configuración/runtime y `doctotal:check-production-readiness --probe` — DT-35.
- [x] Host canónico, cookies seguras, logging y probes DB/cache/queue — DT-35.
- [x] Estrategia de backup/restauración + runbook — DT-43.
- [x] Pantalla interna de Backups de solo lectura — DT-44.
- [x] Foundation de observabilidad y error tracking con contexto técnico mínimo y sin payload clínico — DT-45.
- [x] Readiness bloquea observabilidad incompleta: reporting, canal válido, alertamiento y runbook — DT-45.
- [x] Runbook versionado de respuesta operacional a incidentes — DT-45.
- [x] Topología canónica `scheduler_only` basada en auditoría real del código — DT-46.
- [x] Workers permanecen deshabilitados hasta existir un job `ShouldQueue` real — DT-46.
- [x] Checker y comando `doctotal:check-queue-operations` para configuración y failed jobs — DT-46.
- [x] Monitoreo agregado de pending/failed jobs sin exponer payloads o excepciones — DT-46.
- [x] Runbook de activación futura de workers, diagnóstico y retry seguro — DT-46.
- [x] Production Readiness valida mailer real, remitente válido, transport de email, SMTP remoto y runbook — DT-47.
- [x] Runbook de email productivo con verificación DNS, prueba de entrega, diagnóstico y rollback — DT-47.
- [x] Release-readiness V1.0 y clasificación de gates de despliegue — DT-48.
- [D] Impersonación, herramientas destructivas masivas y SIEM completo.

# 6. Decisiones abiertas no bloqueantes de V1.0

- [!] Reembolsos y efecto en créditos/comisiones.
- [!] Retención/eliminación legal definitiva.
- [!] Cuotas de almacenamiento.
- [!] Proveedores futuros de comunicación y almacenamiento externo.
- [!] Requisitos legales de recetas/firma/verificación/CFDI.
- [!] Activación de passkeys ligada al origen HTTPS definitivo.

# 7. Gates de despliegue productivo

No son trabajo pendiente del codebase V1.0, pero deben validarse en el entorno objetivo antes de tráfico real:

- HTTPS/hostname canónico y variables productivas.
- MySQL, cache y locks operativos.
- Stripe real y webhook configurado.
- Email real, remitente y SPF/DKIM/DMARC validados.
- Backup/restauración operativos.
- Scheduler cada minuto.
- Observabilidad y alertamiento configurados.
- `php artisan doctotal:check-production-readiness --probe` exitoso.

Ver detalle en `docs/RELEASE_V1_0_READINESS.md`.

# 8. Trabajo posterior a V1.0

No queda trabajo de código pendiente que bloquee el cierre del alcance V1.0 identificado en DT-48.

Los nuevos desarrollos deben abrir tickets nuevos en Jira únicamente cuando exista una decisión de producto, necesidad clínica, requisito legal o cambio de alcance concreto.

La activación futura de workers deberá abrir un nuevo cambio versionado cuando exista el primer job asíncrono real.

Los siguientes IDs DT deben obtenerse exclusivamente desde Jira.
