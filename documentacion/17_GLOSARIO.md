# Glosario

- `RBAC`: Role-Based Access Control. Acceso segun roles.
- `Permiso`: combinacion de rol + recurso + accion.
- `Recurso`: objeto funcional (usuarios, reportes, configuracion).
- `Accion`: operacion (READ/CREATE/UPDATE/DELETE).
- `PRG`: Post-Redirect-Get. Patron para formularios.
- `Access token`: token corto para requests (15 min).
- `Refresh token`: token largo para renovar access (24 h).
- `Sesion maestra`: registro en DB para operar revocacion y auditoria.
- `Quorum`: mayoria necesaria para considerar el cluster "sano".
- `Split-brain`: dos grupos creen ser el cluster primario (riesgo de inconsistencia).
- `Galera`: replicacion sincronica multi-primary para MariaDB.
- `SST`: State Snapshot Transfer. Copia completa a un nodo que vuelve.
- `IST`: Incremental State Transfer. Transferencia incremental (mas rapida).
- `Failover`: cambio automatico a otro nodo si uno falla.
- `Bootstrap`: arrancar un cluster desde un nodo inicial.

