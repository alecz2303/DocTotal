# DocTotal V1.0 — Release Readiness

## Estado

**Codebase V1.0: release-ready.**

Este estado significa que el alcance funcional y técnico versionado definido para V1.0 está cerrado y que no se identifican bloqueos técnicos críticos pendientes dentro del repositorio.

No significa que una instalación concreta esté automáticamente lista para tráfico productivo. La preparación final de cada entorno depende de configuración, credenciales e infraestructura no versionadas y debe verificarse antes del lanzamiento.

## Baseline técnico

- Último bloque cerrado antes de este cierre: DT-47.
- `master` de partida para DT-48: `eba60d9881f510da40127462154de4858502333d`.
- Jira es la fuente de verdad para IDs y estados.
- GitHub Actions es la validación técnica canónica.
- La referencia/tag V1.0 debe crearse únicamente después de aprobar e integrar DT-48.

## Alcance V1.0 cerrado en código

1. Núcleo clínico y expediente longitudinal.
2. Agenda, citas y autoservicio.
3. Consulta clínica, diagnósticos, recetas, problemas, plantillas y alertas.
4. Documentos clínicos y laboratorios estructurados.
5. Multi-tenancy, seguridad, auditoría y controles de cuenta.
6. SaaS, billing, Stripe, ciclo comercial y recuperación de pagos.
7. Comunicaciones transaccionales con foundation real de email.
8. Administración interna, hardening de producción, backups, observabilidad y operación de scheduler/queues.

## Auditoría de deuda técnica crítica

- No se encontraron marcadores `FIXME` en el repositorio.
- No se identificaron marcadores `TODO` técnicos críticos pendientes; las coincidencias relevantes corresponden al documento canónico `docs/TODO.md` o a palabras normales en contenido/idioma.
- Los pendientes conocidos están clasificados explícitamente como operación de despliegue, decisión de producto/legal o evolución futura.

## Gates de despliegue productivo

Antes de habilitar tráfico real en una instalación concreta debe verificarse, como mínimo:

- HTTPS y hostname canónico definitivos.
- Variables productivas y `APP_KEY` correctas.
- MySQL, cache y locks operativos. Para conexiones MySQL, DocTotal fuerza `InnoDB` y no depende del motor predeterminado del proveedor de hosting.
- Stripe real, webhook secret y endpoint configurado.
- Proveedor de email real, remitente, SPF, DKIM y DMARC validados.
- `DOCTOTAL_EMAIL_COMMUNICATIONS_ENABLED=true` cuando el canal email sea requerido.
- Backup de base de datos y archivos privados configurado y verificable.
- Runbook de restore disponible y prueba operativa realizada según política de despliegue.
- Laravel Scheduler ejecutándose cada minuto.
- Topología de colas en modo `scheduler_only` mientras no existan jobs `ShouldQueue` reales.
- Observabilidad y alertamiento configurados.
- `php artisan doctotal:check-production-readiness --probe` con resultado exitoso en el entorno objetivo.

Estos gates no deben satisfacerse introduciendo secretos en Git.

## Compatibilidad de producción posterior a V1.0.0

DT-49 documenta el ajuste de compatibilidad descubierto durante el primer despliegue productivo: algunos proveedores mantienen MyISAM como motor predeterminado del servidor. DocTotal debe crear sus tablas MySQL con InnoDB de forma explícita para preservar `utf8mb4`, transacciones, claves foráneas y los índices definidos por el esquema sin depender de defaults del hosting. Este ajuste corresponde al patch candidate V1.0.1.

## WhatsApp y SMS

WhatsApp y SMS no son bloqueos de V1.0 mientras no formen parte explícita del alcance comercial del lanzamiento. La arquitectura permanece preparada para transports adicionales, pero su selección/configuración debe abrirse como trabajo versionado independiente si se decide incluirlos.

## Pendientes no bloqueantes de V1.0

- Política legal definitiva de retención/eliminación documental.
- Cuotas totales de almacenamiento por tenant.
- Política comercial definitiva de reembolsos y efecto en promociones/comisiones.
- Proveedores futuros de WhatsApp/SMS y almacenamiento externo.
- Passkeys, firma/QR, CFDI, OCR/IA, DICOM/PACS, HL7/FHIR, SIEM completo y multi-plan, todos diferidos deliberadamente salvo cambio de alcance.

## Recalculo formal de avance

El porcentaje histórico de 94% medía el avance del plan previo al cierre, incluyendo trabajo todavía pendiente de hardening y release readiness.

Para DT-48 se redefine explícitamente la base de cálculo al **alcance comprometido de DocTotal V1.0**. Los ocho dominios V1.0 listados en este documento están implementados en el codebase y los elementos restantes están clasificados fuera del alcance V1.0 o como gates de despliegue no versionables.

Por ello, al integrar DT-48, el avance formal del **alcance V1.0 versionado** queda en **100%**.

Este 100% no representa cobertura de tests, ausencia absoluta de bugs, ni que cualquier servidor esté listo sin configuración. Representa cierre del alcance de producto/código acordado para V1.0.

## Criterio de release

DocTotal V1.0 puede etiquetarse como release después de:

1. DT-48 aprobado e integrado en `master`.
2. CI verde sobre el SHA consolidado del ticket y CI verde del PR.
3. Baseline final de `master` verificado después del merge.
4. Creación de la referencia/tag de V1.0 sobre ese baseline final, no antes.

La habilitación de producción sigue requiriendo completar y validar los gates de despliegue anteriores en el entorno objetivo.
