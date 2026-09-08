# DocTotal — TODO

## Progreso general

**94% completado**

`███████████████████░` 94%

> Este porcentaje es el último avance global ponderado formalmente establecido. No se recalcula en DT-47. No representa cobertura de tests.

## Estado canónico al iniciar DT-47

- DT-1 a DT-46 están cerrados en Jira (`Listo`).
- DT-47 está `En curso`.
- `master` post-DT-46: `8c3ffb80f6c1d87050d2ea3025217576e71fb53e`.
- GitHub Actions es la validación técnica canónica; no se inventan baselines de tests/assertions.

## Leyenda

- `[x]` Implementado.
- `[~]` Implementado parcialmente / evolución futura.
- `[ ]` Trabajo realmente pendiente.
- `[!]` Decisión de producto/operación todavía no cerrada.
- `[D]` Diferido deliberadamente.

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
- [ ] Seleccionar/configurar proveedor real de WhatsApp si el lanzamiento lo requiere.
- [ ] Seleccionar/configurar proveedor real de SMS si el lanzamiento lo requiere.
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
- [D] Impersonación, herramientas destructivas masivas y SIEM completo.

# 6. Decisiones de producto todavía abiertas

- [!] Reembolsos y efecto en créditos/comisiones.
- [!] Retención/eliminación legal definitiva.
- [!] Cuotas de almacenamiento.
- [!] Proveedores definitivos de comunicación y almacenamiento externo.
- [!] Requisitos legales de recetas/firma/verificación/CFDI.
- [!] Activación de passkeys ligada al origen HTTPS definitivo.

# 7. Trabajo realmente pendiente priorizado

DT-47 prepara email real para V1.0 sin versionar secretos ni acoplar DocTotal a un proveedor. La activación productiva final exige credenciales reales y validación del dominio en la infraestructura objetivo.

**Siguientes candidatos recomendados (sin asignar ID DT):**

1. WhatsApp/SMS solo si forman parte del alcance de lanzamiento V1.0.
2. **Cierre formal de V1.0 / release readiness y reconciliación canónica final.**
3. Decisiones operativas/legal-comerciales pendientes que sean bloqueo real de lanzamiento.

La activación futura de workers deberá abrir un nuevo cambio versionado cuando exista el primer job asíncrono real.

Los siguientes IDs DT deben obtenerse exclusivamente desde Jira cuando se decida crear cada ticket.
