# Vision y Alcance

## Objetivo
LAAS (Login as a Service) es un sistema educativo de autenticacion y control de acceso en PHP + MariaDB, con:

- login con sesiones y tokens (access + refresh)
- control de acceso por permisos (rol + recurso + accion)
- auditoria (bitacora) de eventos y acciones sensibles
- panel administrativo responsive para gestionar catologos, usuarios y seguridad
- infraestructura dockerizada con cluster de base de datos para tolerancia a fallos

## Usuarios objetivo

- Administrador (`admin`): gestiona usuarios, seguridad, sesiones y auditoria.
- Consulta (`consulta`): acceso de lectura a paneles/metricas segun permisos.
- Ingreso (`ingreso`): perfil basico, principalmente su perfil y lectura limitada.

## Modulos

- Autenticacion y sesiones: login/logout, restauracion por refresh.
- Seguridad: roles, recursos, acciones, permisos.
- Operacion: sesiones activas, tokens, auditoria.
- Administracion: usuarios, perfil, catalogos (`cat_status`, `roles`, `cat_recurso`, `cat_accion`).

## No-alcance (explicitamente fuera)

- Frameworks (Laravel/Symfony), SPA, API REST formal.
- Provisionamiento cloud o Kubernetes.
- SSO, MFA, OAuth externo.
- Alta disponibilidad de capa web (solo se documenta HA de DB).

