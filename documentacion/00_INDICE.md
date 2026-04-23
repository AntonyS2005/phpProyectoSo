# Documentacion - Login as a Service (LAAS)

Este directorio describe el sistema de forma profesional: que hace, como esta construido, y como se opera.

## Indice

- `01_VISION_Y_ALCANCE.md`: objetivo del sistema, modulos, alcance, no-alcance.
- `02_ARQUITECTURA.md`: frontend, backend, capas, convenciones, navegacion y permisos.
- `03_MODELO_DATOS.md`: tablas principales, relaciones y reglas de integridad.
- `04_AUTENTICACION_Y_TOKENS.md`: login, sesiones, access/refresh tokens, renovacion, revocacion.
- `05_PERMISOS_Y_SEGURIDAD.md`: roles, recursos, acciones, matriz, enforcement backend, auditoria.
- `06_PANTALLAS_Y_CASOS_DE_USO.md`: pantalla por pantalla con casos de uso, entradas, salidas, validaciones.
- `07_INFRAESTRUCTURA_GALERA.md`: topologia de contenedores, cluster, puertos, bootstrap, failover.
- `08_OPERACION_Y_RUNBOOK.md`: como correr, backups, troubleshooting, pruebas operativas.
- `09_CHECKLIST_QA.md`: checklist de pruebas funcionales, seguridad y responsive.

## Material extendido (aprendizaje)

- `10_DICCIONARIO_DATOS.md`: descripcion de columnas (por tabla) y significados.
- `11_DIAGRAMAS.md`: diagramas ASCII (ERD y flujos) para entender/exponer.
- `12_CONVENCIONES_CODIGO.md`: como estructurar PHP en este repo, patrones y anti-patrones.
- `13_CONTRATOS_DE_RUTAS.md`: rutas/paginas, permisos requeridos y handlers POST.
- `14_AMENAZAS_Y_CONTROLES.md`: threat model basico y controles aplicados.
- `15_RUNBOOK_INCIDENTES.md`: que hacer ante caidas, cluster partido, credenciales, acceso denegado.
- `16_GUIA_EXPOSICION.md`: guion para presentar el sistema (10-15 min) y preguntas tipicas.
- `17_GLOSARIO.md`: terminos (RBAC, quorum, SST/IST, PRG, etc.).
