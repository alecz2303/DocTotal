# DocTotal — Operación de durabilidad de datos

## Propósito

Este runbook define la base operativa mínima para respaldar y restaurar DocTotal en producción. No sustituye requisitos legales o regulatorios por jurisdicción y no autoriza borrado automático de expedientes clínicos.

## Alcance del respaldo

Cada ciclo de backup debe incluir, como una unidad operativa recuperable:

1. La base de datos productiva completa de DocTotal.
2. El almacenamiento privado que contiene documentos clínicos y demás archivos no públicos.
3. La información de versión/despliegue necesaria para identificar el commit de aplicación compatible con el respaldo.

Un respaldo que incluya solo la base de datos o solo archivos privados se considera incompleto.

## Responsabilidad del proveedor/mecanismo

`DOCTOTAL_BACKUP_PROVIDER` identifica el mecanismo real utilizado por la infraestructura: por ejemplo, snapshots administrados del proveedor, una plataforma de backups, scripts operativos externos o una combinación documentada de ellos.

DocTotal no intenta ejecutar desde la aplicación un dump genérico con credenciales de producción. La ejecución del backup corresponde a infraestructura; la aplicación valida que la estrategia haya sido declarada y que cubra ambos conjuntos de datos.

## Frecuencia y copias

- `DOCTOTAL_BACKUP_FREQUENCY_HOURS` declara la frecuencia máxima esperada entre ciclos.
- `DOCTOTAL_BACKUP_RETENTION_COPIES` declara el número mínimo de copias que infraestructura conserva.
- Producción exige al menos dos copias y una frecuencia entre 1 y 168 horas.

La frecuencia real debe seleccionarse de acuerdo con el RPO aceptado por la operación antes del lanzamiento.

## Procedimiento de restauración

Una prueba o restauración real debe realizarse en un entorno aislado antes de exponer tráfico:

1. Identificar el punto de restauración y registrar fecha/hora del backup y commit de aplicación asociado.
2. Detener tráfico, scheduler y workers del entorno destino.
3. Restaurar la base de datos usando el mecanismo definido por infraestructura.
4. Restaurar el almacenamiento privado manteniendo rutas y permisos esperados por DocTotal.
5. Desplegar una versión de aplicación compatible con el esquema restaurado.
6. Ejecutar migraciones únicamente si el procedimiento de recuperación lo requiere y existe una ruta segura hacia la versión objetivo.
7. Ejecutar `php artisan doctotal:check-production-readiness --probe`.
8. Ejecutar `php artisan doctotal:check-data-durability`.
9. Verificar acceso autenticado, aislamiento de tenant, consulta de pacientes y apertura/descarga de una muestra controlada de documentos privados.
10. Confirmar que colas, scheduler y logging operan antes de reanudar tráfico.

## Criterios de éxito de una prueba de restauración

La restauración se considera verificada solo cuando:

- la base de datos abre y pasa readiness;
- los archivos privados requeridos están presentes y siguen siendo privados;
- las relaciones entre pacientes y documentos son consistentes;
- no existe evidencia de mezcla de datos entre tenants;
- las dependencias de producción pasan los probes;
- la prueba queda registrada por operación con fecha, punto restaurado y resultado.

## Fallos que bloquean producción

Debe considerarse una brecha de durabilidad si falta cualquiera de estos elementos:

- backup habilitado;
- cobertura de base de datos;
- cobertura de archivos privados;
- proveedor o mecanismo declarado;
- frecuencia válida;
- mínimo de copias;
- este runbook o el runbook configurado;
- verificación de restauración obligatoria.

## Retención y eliminación

La foundation técnica separa dos conceptos:

- **retención de backups:** número de copias operativas conservadas por infraestructura;
- **retención clínica:** cuánto tiempo deben mantenerse expedientes y documentos de pacientes.

DT-43 no define un plazo legal definitivo de retención clínica. `DOCTOTAL_RETENTION_MODE=manual` es el valor seguro por defecto. El modo `policy` solo indica que existe una política operativa aprobada fuera de código; no activa borrado por sí mismo.

`DOCTOTAL_RETENTION_AUTOMATIC_DELETION_ENABLED` debe permanecer en `false`. Un valor `true` bloquea el readiness de durabilidad hasta que un ticket futuro implemente, documente y pruebe explícitamente un proceso destructivo autorizado.

## Revisión periódica

Antes de cada lanzamiento importante y después de cambios de proveedor de base de datos, storage o hosting, operación debe revisar este runbook y repetir una prueba de restauración controlada.
