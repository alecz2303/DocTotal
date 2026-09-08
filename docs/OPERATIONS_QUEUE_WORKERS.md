# Operación de scheduler, colas y workers

## Estado actual de DocTotal V1.0

La auditoría de DT-46 no identificó clases que implementen `ShouldQueue` en el código actual. Los procesos operativos recurrentes de billing y comunicaciones se ejecutan mediante Laravel Scheduler desde `routes/console.php`.

Por esta razón, la topología canónica actual es:

- `DOCTOTAL_QUEUE_MODE=scheduler_only`
- scheduler activo mediante un cron que ejecute `php artisan schedule:run` cada minuto;
- workers de cola deshabilitados;
- backend de cola persistente y almacenamiento de `failed_jobs` disponibles como foundation para trabajo asíncrono futuro;
- monitoreo de `failed_jobs` habilitado.

No debe iniciarse un worker solo porque Laravel tenga soporte de colas. El primer proceso realmente asíncrono deberá introducir explícitamente un job `ShouldQueue`, revisar idempotencia y multi-tenancy, y cambiar la topología a `workers` en un cambio versionado.

## Validación previa a producción

Ejecutar:

```bash
php artisan doctotal:check-production-readiness --probe
php artisan doctotal:check-queue-operations
```

Ambos comandos deben completar correctamente antes de habilitar tráfico o procesos operativos.

## Modo scheduler_only

Requisitos:

- `DOCTOTAL_QUEUE_MODE=scheduler_only`;
- `DOCTOTAL_QUEUE_WORKER_ENABLED=false`;
- `DOCTOTAL_FAILED_JOB_MONITORING_ENABLED=true`;
- `QUEUE_CONNECTION` debe seguir usando un backend persistente según Production Readiness;
- `QUEUE_FAILED_DRIVER` no puede ser `null`;
- cron del servidor: `php artisan schedule:run` cada minuto.

Los comandos programados usan `withoutOverlapping()` donde corresponde. El scheduler es una responsabilidad separada del worker y no debe sustituirse por `queue:work`.

## Activación futura del modo workers

Cuando exista al menos un job asíncrono real:

1. verificar que el job sea seguro frente a reintentos o que implemente idempotencia explícita;
2. asegurar que el tenant correcto pueda reconstruirse de forma segura durante la ejecución;
3. evitar incluir PHI, secretos, tokens o credenciales innecesarios en el payload serializado;
4. cambiar `DOCTOTAL_QUEUE_MODE=workers`;
5. cambiar `DOCTOTAL_QUEUE_WORKER_ENABLED=true`;
6. declarar cola, intentos y timeout;
7. asegurar que `retry_after` sea mayor que el timeout del worker cuando el driver lo soporte;
8. ejecutar el worker bajo un supervisor de procesos de la infraestructura objetivo.

Ejemplo operacional genérico:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=60
```

El supervisor concreto depende de la infraestructura y queda fuera del código de aplicación.

## Failed jobs

El monitoreo canónico usa:

```bash
php artisan doctotal:check-queue-operations
```

El comando solamente reporta:

- modo operativo;
- cantidad de jobs pendientes;
- cantidad de jobs fallidos.

No imprime payload, excepción serializada, datos clínicos, identificadores sensibles, tokens ni credenciales.

Si la cantidad de failed jobs alcanza `DOCTOTAL_FAILED_JOB_ALERT_THRESHOLD`, el comando termina con estado de fallo para que la infraestructura pueda alertar.

## Diagnóstico y retry

Un failed job nunca debe reintentarse de forma automática solo por existir en `failed_jobs`.

Antes de un retry:

1. identificar la clase/tipo de trabajo sin copiar contenido sensible a canales externos;
2. confirmar la causa técnica;
3. verificar si el efecto pudo completarse parcialmente;
4. comprobar idempotencia;
5. comprobar tenant y alcance de datos;
6. corregir primero la causa si el fallo es persistente;
7. ejecutar el retry de forma controlada;
8. verificar el resultado y que no haya duplicación de efectos.

Los trabajos relacionados con cobros, comunicaciones, generación de documentos o cualquier operación con efectos externos requieren especial cuidado antes del retry.

## Escalamiento

Se considera incidente operativo cuando:

- failed jobs alcanza o supera el umbral configurado;
- el backend de cola no puede consultarse;
- el scheduler deja de ejecutar procesos esperados;
- un worker entra en ciclo de fallos/reintentos;
- existe riesgo de duplicación de cobros, comunicaciones o escrituras clínicas.

Seguir además `docs/OPERATIONS_INCIDENT_RESPONSE.md` para clasificación y respuesta general.

## Privacidad y multi-tenancy

- no enviar payloads completos de jobs a logs o alertas;
- no incluir PHI/PII innecesaria en contexto operativo;
- no registrar secretos, tokens o credenciales;
- cada job futuro que toque datos tenant-scoped debe reconstruir y limpiar correctamente el contexto de tenant;
- el monitoreo global solo debe exponer métricas agregadas y contexto técnico mínimo.
