# DocTotal — Roadmap

Este documento registra el historial canónico de bloques DT y su estado real. Jira es la fuente de verdad para identificadores y estados; este archivo no sustituye Jira.

## Baseline canónico post-DT-41

- `master`: `2954f13374981211a8f1c7f81b1919c47a817246`
- Último bloque integrado: **DT-41 — Reconcile canonical product documentation after DocTotal 1.0 hardening**
- Estado Jira DT-1 … DT-41: **Listo**
- Avance global ponderado formal vigente: **94%**
- No se recalcula porcentaje ni se inventan cifras de tests/assertions en DT-42.

## Historial DT

| DT | Objetivo resumido | Estado Jira |
|---|---|---|
| DT-1 | Estructura SaaS multi-tenant base | Listo |
| DT-2 | Modelos y relaciones Eloquent del núcleo | Listo |
| DT-3 | Aislamiento automático y resolución de tenant | Listo |
| DT-4 | Pacientes y expediente clínico base | Listo |
| DT-5 | Autenticación, registro, dashboard y trial | Listo |
| DT-6 | Onboarding del médico/consultorio | Listo |
| DT-7 | Gestión de pacientes | Listo |
| DT-8 | Agenda y gestión de citas | Listo |
| DT-9 | Flujo de consulta y ciclo clínico | Listo |
| DT-10 | Product TODO y roadmap evolutivo | Listo |
| DT-11 | Suscripciones y ciclo de facturación SaaS | Listo |
| DT-12 | Pagos, recuperación y lifecycle automático de cuenta | Listo |
| DT-13 | Referidos y créditos promocionales | Listo |
| DT-14 | Expediente clínico longitudinal | Listo |
| DT-15 | Archivos y documentos clínicos | Listo |
| DT-16 | Rediseño visual / DocTotal UI | Listo |
| DT-17 | Workspace clínico avanzado | Listo |
| DT-18 | Normalización documental | Listo |
| DT-19 | Lista estructurada de problemas clínicos activos | Listo |
| DT-20 | Comunicaciones transaccionales y recordatorios | Listo |
| DT-21 | Auditoría y security hardening foundation | Listo |
| DT-22 | Panel administrativo interno SaaS | Listo |
| DT-23 | Recuperación billing respetando cambio de plan | Listo |
| DT-24 | Autoservicio del paciente para confirmar/cancelar cita | Listo |
| DT-25 | Compartición manual del enlace de gestión de cita | Listo |
| DT-26 | Plantillas clínicas reutilizables | Listo |
| DT-27 | Laboratorios estructurados | Listo |
| DT-28 | Seguridad y recuperación de cuenta | Listo |
| DT-29 | Repetición de recetas conservando historial | Listo |
| DT-30 | Reprogramación pública con slots disponibles | Listo |
| DT-31 | CI automatizado con GitHub Actions | Listo |
| DT-32 | Hardening de comunicaciones y recordatorios | Listo |
| DT-33 | Cierre de billing production readiness v1.0 | Listo |
| DT-34 | Alertas clínicas contextuales | Listo |
| DT-35 | Hardening de configuración/runtime de producción | Listo |
| DT-36 | Pulido del flujo clínico diario y dashboard | Listo |
| DT-37 | Estado de trial/suscripción/pago en experiencia médica | Listo |
| DT-38 | Duración de trial configurable desde administración interna | Listo |
| DT-39 | Códigos promocionales y comisiones de vendedores | Listo |
| DT-40 | SweetAlert en ajustes internos de trial | Listo |
| DT-41 | Reconciliación documental post-hardening 1.0 | Listo |
| DT-42 | Centro global de archivos clínicos y documento fuente de laboratorios | En curso |

## Evolución por etapas

### Foundation (DT-1 — DT-10)

Se establecieron multi-tenancy, modelos, aislamiento, pacientes, autenticación, onboarding, agenda, consulta y el primer inventario canónico del producto.

### SaaS y clínica longitudinal (DT-11 — DT-21)

Se construyeron suscripciones/billing, referidos, expediente longitudinal, documentos clínicos, UI propia, workspace de consulta, problemas clínicos, comunicaciones transaccionales y auditoría.

### Operación de producto y autoservicio (DT-22 — DT-30)

Se incorporaron administración interna, correcciones de recuperación de billing, autoservicio público de citas, enlaces manuales, plantillas, laboratorios, seguridad de cuenta, repetición de recetas y reprogramación pública.

### Hardening DocTotal 1.0 (DT-31 — DT-41)

- DT-31 automatizó la validación técnica mediante GitHub Actions.
- DT-32 endureció comunicaciones transaccionales.
- DT-33 cerró las brechas críticas de billing v1.0, incluidos webhooks y comprobante operativo.
- DT-34 incorporó alertas clínicas contextuales deterministas.
- DT-35 endureció configuración/runtime y readiness de producción.
- DT-36 priorizó el flujo clínico diario y dashboard.
- DT-37 hizo visible el estado comercial dentro de la experiencia del médico.
- DT-38 hizo configurable la duración del trial.
- DT-39 añadió adquisición promocional y tracking de comisiones.
- DT-40 añadió feedback SweetAlert a ajustes internos de trial.
- DT-41 reconcilió la documentación canónica con el estado real de GitHub y Jira.

### Evolución clínica post-hardening

DT-42 convierte `Archivos` en un centro clínico global tenant-scoped que reutiliza `ClinicalDocument`, y permite vincular opcionalmente un laboratorio estructurado con un documento fuente del mismo paciente/tenant sin crear un segundo sistema de almacenamiento.

## Próximo candidato después de DT-42

Sin asignar todavía un ID Jira, el siguiente bloque recomendado continúa siendo:

**Production data durability: backup, restore and retention foundation.**

La prioridad se fundamenta en una brecha real: DT-35 valida readiness del runtime, pero no implementa una estrategia verificable de backup/restauración ni una política operativa de retención. El ID del próximo DT se obtendrá únicamente de Jira cuando se cree formalmente el ticket.
