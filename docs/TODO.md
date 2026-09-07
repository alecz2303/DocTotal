# DocTotal — TODO

## Progreso general

**94% completado**

`███████████████████░` 94%

> Este porcentaje es el último avance global ponderado formalmente establecido. No se recalcula en DT-43. No representa cobertura de tests.

## Estado canónico post-DT-42

La documentación fue reconciliada en DT-41 y actualizada después del cierre de DT-42.

- DT-1 a DT-42 están cerrados en Jira (`Listo`).
- `master` post-DT-42: `cc5a1231527cdc5f4bfb232faae358a83b03a998`.
- Commit post-DT-42: `DT-42 feat: add global clinical files center`.
- GitHub Actions es la validación técnica canónica. Los números históricos de tests/assertions se conservan únicamente cuando fueron registrados explícitamente; no se inventan baselines nuevos.

## Leyenda

- `[x]` Implementado.
- `[~]` Implementado parcialmente / evolución futura.
- `[ ]` Trabajo realmente pendiente.
- `[!]` Decisión de producto/operación todavía no cerrada.
- `[D]` Diferido deliberadamente.

# 1. Producto clínico

## Pacientes y expediente

- [x] Pacientes, contactos de emergencia y antecedentes médicos.
- [x] Expediente longitudinal con consultas, diagnósticos, recetas y tratamientos históricos.
- [x] Problemas clínicos activos/resueltos (`PatientProblem`) — DT-19.
- [x] Documentos clínicos privados — DT-15.
- [x] Centro global tenant-scoped de archivos clínicos con búsqueda/filtros y navegación al expediente — DT-42.
- [x] Plantillas clínicas reutilizables — DT-26.
- [x] Laboratorios estructurados y captura masiva revisable — DT-27.
- [x] Vinculación opcional de laboratorio estructurado con su documento fuente del mismo paciente/tenant — DT-42.
- [x] Alertas clínicas contextuales deterministas y trazables — DT-34.
- [~] Mejorar estructura de antecedentes cuando exista una necesidad clínica concreta (p. ej. hospitalizaciones).
- [D] OCR/extracción de documentos.
- [D] DICOM/PACS.
- [D] HL7/FHIR e integraciones con proveedores de laboratorio.
- [D] Interpretación clínica automática/IA.
- [!] Definir cuotas totales de almacenamiento por tenant.
- [!] Definir política legal definitiva de conservación y retención documental.

## Agenda y citas

- [x] Agenda mensual, semanal y diaria.
- [x] Disponibilidad, excepciones, bloqueos, horarios extraordinarios y prevención de solapamientos.
- [x] Ciclo completo de citas: programar, confirmar, check-in, consulta, completar, cancelar, no-show y reprogramar.
- [x] Autoservicio público para confirmar/cancelar — DT-24.
- [x] Compartición manual del enlace seguro — DT-25.
- [x] Reprogramación pública con slots reales y revalidación server-side — DT-30.
- [x] Flujo diario y jerarquía operativa de dashboard/agenda — DT-36.
- [~] Refinamientos visuales de densidad/calendario sólo si se justifican por uso real; no son brecha funcional de v1.0.

## Consulta

- [x] Consultation persistente `draft/completed`.
- [x] Workspace clínico con contexto persistente, autosave y protección contra pérdida de cambios — DT-17.
- [x] Diagnósticos, catálogo, recetas y PDF.
- [x] Problemas activos visibles durante consulta — DT-19.
- [x] Plantillas reutilizables — DT-26.
- [x] Alertas clínicas contextuales visibles durante consulta — DT-34.
- [x] Repetición de recetas conservando historial fuente — DT-29.
- [D] Firma digital de receta.
- [D] QR/verificación externa de receta.
- [!] Resolver requisitos legales/documentales antes de activar funciones que dependan de ellos.

# 2. Seguridad, privacidad y auditoría

- [x] Aislamiento multi-tenant como requisito transversal — DT-1 a DT-3 y cobertura posterior.
- [x] Auditoría persistente multi-tenant y sanitización de metadata sensible — DT-21.
- [x] Cambio de contraseña desde UI — DT-28.
- [x] 2FA TOTP, códigos de recuperación y challenge de login — DT-28.
- [x] Verificación de correo — DT-28.
- [x] Sesiones/dispositivos y revocación individual/masiva — DT-28.
- [x] Passkeys/WebAuthn evaluadas técnicamente — DT-28.
- [D] Activar passkeys hasta definir hostname HTTPS canónico y relying party/origins productivos.
- [~] Ampliar cobertura de auditoría a más mutaciones clínicas/comerciales según riesgo.
- [x] Foundation verificable de backup/restauración para base de datos y archivos clínicos privados — DT-43.
- [~] Foundation operativa de retención segura sin borrado automático; la política legal definitiva sigue pendiente — DT-43.
- [D] Inmutabilidad de auditoría garantizada a nivel de base de datos.
- [D] Outbox transaccional para auditoría durable.
- [!] Mantener revisión de autorización, rate limiting, observabilidad y controles de producción como requisito continuo, no como supuesto resuelto por un único DT.

# 3. SaaS, billing y ciclo comercial

## Trial y estado comercial

- [x] Trial creado durante registro.
- [x] Derecho de acceso centralizado.
- [x] Estado de trial/suscripción/pago visible en onboarding y experiencia del médico — DT-37.
- [x] Duración del trial configurable desde administración interna — DT-38.
- [x] Feedback SweetAlert en ajustes internos de trial — DT-40.
- [x] Pantalla de servicio suspendido y recuperación por billing.
- [!] Definir política comercial definitiva de reembolso.

## Suscripciones y pagos

- [x] Subscription lifecycle mensual/anual — DT-11.
- [x] Stripe, Payment, métodos de pago, renovación, recuperación, grace y suspensión/reactivación — DT-12.
- [x] Recuperación respetando cambio de plan `past_due` — DT-23.
- [x] Webhooks Stripe autenticados e idempotentes, sincronización de éxito/fallo/cancelación y recuperación coherente — DT-33.
- [x] Comprobante operativo de pago — DT-33.
- [x] CI automatizado para ramas DT y PR hacia `master` — DT-31.
- [D] Facturación fiscal/CFDI hasta definir alcance legal y proveedor.
- [D] Modelo `Plan`/upgrade/downgrade multi-plan mientras exista una sola oferta comercial.

## Referidos, promociones y vendedores

- [x] Programa de referidos y créditos promocionales — DT-13.
- [x] Códigos promocionales administrables, atribución de vendedores y ledger de comisiones — DT-39.
- [x] Registro evita acumulación incompatible entre referido médico y código comercial — DT-39.
- [~] Herramientas administrativas adicionales sólo según necesidad operativa real.
- [!] Definir tratamiento comercial de promociones/comisiones ante reembolsos futuros.

# 4. Comunicaciones

- [x] Foundation multi-tenant de comunicaciones y recordatorios — DT-20.
- [x] Preferencias por canal, elegibilidad, claim transaccional, estado `processing`, redacción de errores y transport fake — DT-32.
- [x] Enlaces de gestión de cita integrados en flujo público/manual — DT-24/DT-25/DT-30.
- [ ] Seleccionar/configurar proveedor real de correo para producción si el lanzamiento lo requiere.
- [ ] Seleccionar/configurar proveedor real de WhatsApp si el lanzamiento lo requiere.
- [ ] Seleccionar/configurar proveedor real de SMS si el lanzamiento lo requiere.
- [D] Campañas de marketing/envíos masivos: fuera del núcleo transaccional actual.

# 5. Operación interna y producción

- [x] Consola administrativa interna SaaS — DT-22.
- [x] Visibilidad global de tenants, trials, suscripciones, billing, comunicaciones y auditoría.
- [x] Hardening de configuración/runtime de producción y comando `doctotal:check-production-readiness --probe` — DT-35.
- [x] Host canónico, cookies seguras, logging activo y probes de DB/cache locks/queue backend — DT-35.
- [x] Estrategia operativa declarativa y verificable de backup/restauración con cobertura de BD + archivos privados, frecuencia, copias mínimas y runbook — DT-43.
- [~] Foundation de retención operacional segura; no existe borrado clínico automático y la política legal definitiva sigue abierta — DT-43.
- [ ] Monitoreo/error tracking de producción y procedimiento de respuesta operacional.
- [ ] Definir queue/worker topology del entorno objetivo y monitoreo de failed jobs cuando se despliegue con procesamiento asíncrono real.
- [D] Impersonación y herramientas destructivas masivas.
- [D] SIEM completo.

# 6. Decisiones de producto todavía abiertas

- [!] Reembolsos y su efecto en créditos/comisiones.
- [!] Retención/eliminación legal definitiva de información clínica y comercial.
- [!] Cuotas de almacenamiento por tenant.
- [!] Proveedores definitivos de comunicación y almacenamiento externo.
- [!] Requisitos legales de recetas, firma/verificación y facturación fiscal.
- [!] Activación de passkeys ligada al origen HTTPS productivo definitivo.

# 7. Trabajo realmente pendiente priorizado

DT-43 cubre la foundation técnica/operativa de durabilidad sin inventar plazos legales ni activar borrados destructivos. Las brechas siguientes son principalmente de operación productiva.

**Siguientes candidatos recomendados (sin asignar ID DT):**

1. **Production monitoring/error tracking + operational response procedure.**
2. **Queue/worker topology + failed-job monitoring para procesamiento asíncrono real.**
3. Proveedores reales de comunicaciones si el lanzamiento los requiere.

Los siguientes IDs DT deben obtenerse exclusivamente desde Jira cuando se decida crear cada ticket.
