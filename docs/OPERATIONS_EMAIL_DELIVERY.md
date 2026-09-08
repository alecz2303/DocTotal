# DocTotal — Operación de correo productivo

## Objetivo

Este runbook define cómo habilitar, verificar y recuperar la entrega de correo de DocTotal en producción sin acoplar el producto a un proveedor único y sin exponer credenciales, destinatarios, tokens ni contenido clínico en diagnóstico operacional.

## Alcance actual

DocTotal usa correo para flujos de cuenta como verificación de email y recuperación de contraseña, y puede usarlo como canal de comunicaciones transaccionales. El transporte de comunicaciones de pacientes permanece deshabilitado hasta que `DOCTOTAL_EMAIL_COMMUNICATIONS_ENABLED=true` y Production Readiness valide la configuración.

## Configuración mínima

Configurar el mailer real soportado por Laravel mediante variables `MAIL_*`. Para SMTP, usar un host remoto real, puerto válido y las credenciales suministradas por el proveedor. Nunca versionar secretos.

Configurar además:

```env
MAIL_MAILER=smtp
MAIL_HOST=<smtp-real>
MAIL_PORT=587
MAIL_USERNAME=<usuario>
MAIL_PASSWORD=<secreto>
MAIL_FROM_ADDRESS=notificaciones@<dominio-real>
MAIL_FROM_NAME="DocTotal"
DOCTOTAL_EMAIL_COMMUNICATIONS_ENABLED=true
DOCTOTAL_EMAIL_RUNBOOK=docs/OPERATIONS_EMAIL_DELIVERY.md
```

El ejemplo anterior es estructural; los valores reales pertenecen a la infraestructura y secretos de producción.

## Autenticación del dominio

Antes del lanzamiento, validar con el proveedor y el DNS del dominio:

- SPF autorizado para el servicio de envío.
- DKIM activo y firmado por el dominio remitente.
- DMARC publicado con una política compatible con la fase de lanzamiento.
- El dominio de `MAIL_FROM_ADDRESS` debe corresponder al dominio cuya autenticación se validó.

DocTotal no modifica DNS ni almacena claves DKIM en el repositorio.

## Verificación previa al tráfico

1. Configurar secretos y variables de entorno en producción.
2. Ejecutar `php artisan config:clear` y después cachear configuración según el procedimiento de despliegue vigente.
3. Ejecutar `php artisan doctotal:check-production-readiness --probe`.
4. Confirmar que no existan fallos `email.*`.
5. Enviar una prueba controlada a una cuenta operativa autorizada sin datos clínicos ni información real de pacientes.
6. Confirmar recepción, remitente esperado, SPF/DKIM/DMARC y ausencia de clasificación como spam.
7. Probar recuperación de contraseña y verificación de cuenta con usuarios de prueba.
8. Solo entonces habilitar tráfico normal.

## Diagnóstico seguro

Ante una falla de entrega, revisar primero:

- estado del proveedor;
- resolución DNS;
- hostname y puerto configurados;
- expiración o rotación de credenciales;
- reputación/autenticación del dominio;
- métricas agregadas de comunicaciones fallidas.

No registrar ni copiar a tickets operativos:

- contraseñas o API keys;
- tokens de verificación o recuperación;
- cuerpo completo de emails;
- direcciones de pacientes o usuarios reales salvo necesidad operacional autorizada;
- datos clínicos o PHI.

Los errores persistidos por la capa de comunicaciones deben continuar usando sanitización y redacción de secretos.

## Rollback

Si el transporte presenta fallas generalizadas:

1. Cambiar `DOCTOTAL_EMAIL_COMMUNICATIONS_ENABLED=false` para detener comunicaciones transaccionales del módulo de comunicaciones.
2. No cambiar a `log` o `array` como solución productiva permanente.
3. Mantener preservadas las comunicaciones pendientes/fallidas para diagnóstico y reintento controlado.
4. Corregir proveedor, DNS o configuración.
5. Repetir Production Readiness y la prueba controlada antes de reactivar.

Los flujos de verificación y recuperación que usan directamente Laravel Mail también dependen de `MAIL_MAILER`; si el proveedor está caído, deben considerarse degradados hasta restaurar el transporte real.

## Reintentos

No asumir que cualquier comunicación puede reenviarse indiscriminadamente. Antes de reintentar:

- verificar que siga siendo vigente;
- preservar la idempotencia existente;
- evitar duplicar efectos o mensajes ya enviados;
- usar los mecanismos de retry existentes en `CommunicationProcessor` cuando corresponda.

## Criterio de operación saludable

El correo puede considerarse operativo cuando:

- Production Readiness no reporta fallos `email.*`;
- el mailer real responde;
- el remitente usa dominio autenticado;
- una prueba controlada llega correctamente;
- verificación de correo y recuperación de contraseña funcionan con cuentas de prueba;
- no hay un incremento anómalo de comunicaciones fallidas.
