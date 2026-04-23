# Guia de Exposicion (10-15 minutos)

## 1. Problema

- Necesito login + control de acceso real (no solo UI).
- Necesito auditoria.
- Necesito tolerancia a fallos de la base (continuidad).

## 2. Solucion

- PHP server-rendered con panel responsive (Tailwind/DaisyUI).
- RBAC por permisos: rol + recurso + accion.
- Tokens:
  - access 15 min
  - refresh 24 h
  - sesiones como entidad maestra.
- Infra:
  - 3 nodos Galera con quorum 2/3
  - app usa lista de hosts para failover.

## 3. Demo guiada

- Login con `admin`.
- Mostrar menu segun permisos.
- Abrir `Permisos` y explicar matriz.
- Abrir `Recursos` y mostrar acciones permitidas por recurso.
- Revocar una sesion desde `Sesiones`.
- Mostrar auditoria.
- (Infra) apagar `db-node-1`, probar login, volver a levantarlo y mostrar `wsrep_cluster_size=3`.

## 4. Preguntas tipicas y respuestas

- Por que 3 nodos y no 2?
  - quorum: con 3 puedes perder 1.
- Solo ocultas botones?
  - no, enforcement backend con `require_permission()`.
- Como evitas permisos inválidos?
  - `recurso_accion` limita combinaciones.
- Que pasa si cae toda la DB?
  - requiere runbook para bootstrap; caidas parciales son transparentes.

