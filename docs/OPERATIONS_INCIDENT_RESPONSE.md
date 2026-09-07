# DocTotal — Respuesta operacional a incidentes

Este runbook define el procedimiento mínimo para detectar, clasificar, contener, recuperar y verificar incidentes de producción de DocTotal.

## Principios

- No copiar contenido clínico, cuerpos de requests, query strings, cookies, tokens, credenciales ni secretos a tickets o canales de error tracking.
- Utilizar identificadores técnicos mínimos y contexto operacional sanitizado.
- Mantener el aislamiento multi-tenant durante diagnóstico y recuperación.
- No ejecutar acciones destructivas sin una causa identificada y un plan de recuperación.

## Detección

Un incidente puede originarse por excepciones HTTP/runtime, degradación de dependencias, fallos repetidos de procesos críticos, errores de billing o señales de infraestructura.

La observabilidad productiva debe estar habilitada y conectada a un canal con alertamiento operativo real. DocTotal registra excepciones mediante un contexto mínimo compuesto por clase de excepción, código, fingerprint técnico, ubicación de código opcional, método HTTP y nombre de ruta. No se registra el mensaje de la excepción ni parámetros de la petición.

## Clasificación inicial

- **Crítico:** indisponibilidad general, riesgo de pérdida/corrupción de datos, fallo de aislamiento tenant o seguridad.
- **Alto:** función clínica o comercial esencial inutilizable para múltiples usuarios.
- **Medio:** degradación parcial con alternativa operativa disponible.
- **Bajo:** error aislado sin impacto sostenido.

## Diagnóstico inicial

1. Confirmar hora de inicio y alcance aparente.
2. Revisar fingerprint, clase de excepción, ruta y dependencia afectada.
3. Ejecutar `php artisan doctotal:check-production-readiness --probe` cuando sea seguro.
4. Verificar base de datos, cache/locks, backend de colas, logging y durabilidad de datos.
5. Confirmar que no existe mezcla de tenants ni exposición de datos.
6. Evitar reproducir con información clínica real; usar datos sintéticos o entorno aislado.

## Mitigación

- Reducir alcance del fallo antes de intentar una corrección extensa.
- Suspender temporalmente tráfico, scheduler o workers sólo cuando sea necesario y documentando el motivo.
- No borrar expedientes, auditoría, failed jobs ni archivos clínicos como medida de mitigación improvisada.
- Si existe riesgo de integridad de datos, priorizar preservación y recuperación sobre disponibilidad.

## Recuperación

1. Aplicar la corrección o reversión controlada.
2. Validar configuración y dependencias.
3. Ejecutar readiness productivo y checks específicos relacionados con el incidente.
4. Verificar autenticación, aislamiento tenant y flujo afectado.
5. Reactivar componentes de forma controlada.

## Verificación posterior

Un incidente no se considera cerrado hasta confirmar:

- ausencia de nuevos errores equivalentes durante la ventana de observación definida por operación;
- servicio afectado funcionando nuevamente;
- aislamiento multi-tenant preservado;
- no existencia de pérdida o corrupción de información detectada;
- backups y restauración siguen en estado válido si el incidente pudo afectar datos;
- causa y mitigación documentadas sin incluir PHI/PII innecesaria.

## Escalamiento

Los incidentes críticos o de seguridad requieren intervención inmediata del responsable técnico y no deben cerrarse únicamente porque la aplicación vuelva a responder. Debe revisarse integridad, aislamiento y trazabilidad antes del cierre.

## Evolución

Este runbook no define SLA/SLO contractuales ni sustituye un SIEM. La topología de workers/failed jobs se documentará en un bloque posterior y los proveedores externos de monitoreo pueden cambiar sin alterar los principios de privacidad de este documento.
