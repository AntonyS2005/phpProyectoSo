# Login as a Service

Sistema de autenticación y privilegios con PHP y MySQL.

## 🚀 Instalación Local

### Requisitos
- PHP 8.2+
- MySQL 5.7+ o MariaDB
- Composer (opcional)

### Pasos

1. **Clonar el repositorio**
   ```bash
   git clone <tu-repo>
   cd "progrema con php"
   ```

2. **Crear archivo `.env`**
   ```bash
   cp .env.example .env
   ```
   
   Edita `.env` con tus credenciales locales:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=userDB
   DB_USER=root
   DB_PASS=tu_contraseña
   ```

3. **Crear la base de datos**
   
   **Opción A: Con línea de comandos**
   ```bash
   mysql -u root -p < schema.sql
   mysql -u root -p userDB < seed.sql
   ```
   
   **Opción B: Con MySQL Workbench o phpMyAdmin**
   - Abre el archivo `schema.sql` y ejecuta
   - Luego abre el archivo `seed.sql` y ejecuta

4. **Iniciar el servidor PHP**
   ```bash
   cd app
   php -S localhost:8000
   ```

5. **Acceder a la aplicación**
   - http://localhost:8000

## 🐳 Instalación con Docker

### Requisitos
- Docker
- Docker Compose

### Pasos

1. **Crear archivo `.env`**
   ```bash
   cp .env.docker .env
   ```
   O edita `.env` con:
   ```
   DB_HOST=mysql
   DB_PORT=3306
   DB_NAME=userDB
   DB_USER=root
   DB_PASS=contra123
   ```

2. **Construir e iniciar contenedores**
   ```bash
   docker compose up -d
   ```

3. **Crear la base de datos en MySQL**
   ```bash
   docker exec practica1 mysql -u root -pcontra123 < schema.sql
   docker exec practica1 mysql -u root -pcontra123 userDB < seed.sql
   ```

4. **Acceder a la aplicación**
   - http://localhost:8080

## 🔁 Alta disponibilidad Docker (Galera real)

El proyecto ahora puede correr sobre un clúster real de base de datos con:

- `php-app`
- `db-node-1`
- `db-node-2`
- `db-node-3`

Puertos publicados:

- App: `8080`
- DB nodo 1: `3306`
- DB nodo 2: `3307`
- DB nodo 3: `3308`

### Arranque recomendado

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\setup-galera-cluster.ps1
```

Ese script:

1. baja restos del stack anterior
2. levanta los 3 nodos Galera y la app
3. inicializa `schema.sql` y `seed.sql` en `db-node-1` si el cluster está vacío
4. valida tamaño del cluster (`wsrep_cluster_size`)

### Cómo funciona

- Los 3 nodos forman un clúster Galera multi-primary.
- Las escrituras se replican de forma síncrona entre nodos.
- La app usa una lista de hosts (`DB_HOSTS`) y prueba automáticamente `db-node-1`, `db-node-2` y `db-node-3`.
- Si un nodo cae, el usuario puede seguir trabajando sin cambiar URL.
- Cuando un nodo vuelve, Galera lo resincroniza con SST/IST según corresponda.

### Límite real que sí conviene saber

Si se cae **todo** el clúster al mismo tiempo, la recuperación segura todavía requiere decidir desde qué nodo se bootstrappea el quorum.  
Para caídas parciales y rejoin de nodos, el comportamiento sí queda transparente.

## 📝 Credenciales de prueba

- **Usuario**: admin
- **Contraseña**: Test1234!

Otros usuarios disponibles: `consulta`, `ingreso`

## 🔑 Variables de Entorno

| Variable | Local | Docker |
|----------|-------|--------|
| DB_HOST | 127.0.0.1 | mysql |
| DB_PORT | 3306 | 3306 |
| DB_NAME | userDB | userDB |
| DB_USER | root | root |
| DB_PASS | contra123 | contra123 |

Variables extra para Docker HA:

- `DB_HOSTS=db-node-1:3306,db-node-2:3306,db-node-3:3306`
- `DB_HOST_PRIMARY=db-node-1`
- `DB_PORT_PRIMARY=3306`
- `DB_HOST_SECONDARY=db-node-2`
- `DB_PORT_SECONDARY=3306`
- `DB_CLUSTER_MODE=galera`

## 🔐 Autenticación y sesiones

El sistema usa:

- `access_token`: 15 minutos
- `refresh_token`: 24 horas
- `sesiones`: registro maestro de la sesion activa

Flujo:

1. Al iniciar sesion se crea una fila en `sesiones`.
2. Esa sesion emite un `refresh_token` y uno o varios `access_tokens`.
3. Si el `access_token` expira, se renueva desde el `refresh_token`.
4. Si la sesion o el refresh se revocan, el acceso se corta y se fuerza login.

### Migración para bases existentes

Si ya tienes la BD creada, aplica solo las nuevas tablas:

```bash
mysql -u root -p userDB < token_migration.sql
```

En Docker:

```bash
docker exec -i mysql-db mysql -u root -pcontra123 < token_migration.sql
```

## 📁 Estructura

```
app/
├── index.php
├── login.php
├── logout.php
├── style.css
├── dashboard/
│   ├── overview.php
│   ├── users.php
│   ├── profile.php
│   ├── statuses.php
│   ├── roles.php
│   ├── resources.php
│   ├── actions.php
│   ├── permissions.php
│   ├── sessions.php
│   ├── audit.php
│   ├── admin.php          # redirect legacy
│   ├── consulta.php       # redirect legacy
│   └── ingreso.php        # redirect legacy
└── includes/
    ├── app.php
    ├── auth.php
    ├── db.php
    ├── layout.php
    ├── repository.php
    └── services.php
    
schema.sql                 # Estructura de base de datos
seed.sql                   # Datos iniciales
.env                       # Variables de entorno (NO SUBIR A GIT)
.env.example              # Plantilla de .env
.env.docker               # Configuración para Docker
```

## ⚙️ Modulos principales

- `overview.php`: KPIs, sesiones activas, usuarios recientes y auditoria reciente.
- `users.php`: CRUD completo de usuarios y `usuarios_perfil`.
- `statuses.php`, `roles.php`, `resources.php`, `actions.php`: catalogos base del sistema.
- `permissions.php`: matriz rol x recurso x accion.
- `sessions.php`: revocacion operativa de sesiones.
- `audit.php`: bitacora filtrable.
- `profile.php`: gestion de perfil propio y sesiones propias.

## 🐛 Solución de problemas

### "Table 'MySQL.usuarios' doesn't exist"

**Solución:**
```bash
# Crear la BD desde cero
mysql -u root -p < schema.sql
mysql -u root -p userDB < seed.sql
```

### "No se pudo conectar a la base de datos"

1. Verifica que MySQL está corriendo
2. Comprueba las credenciales en `.env`
3. Verifica que `DB_NAME=userDB` (no `MySQL`)
4. Para Docker, asegúrate de que los contenedores estén en la misma red

### PHP CLI

Para ejecutar comandos:
```bash
php -S 127.0.0.1:8000
```

Acceso: http://127.0.0.1:8000

## 🎨 Frontend (Tailwind + DaisyUI)

El proyecto ahora incluye Tailwind CSS v4 + DaisyUI para una UI dinámica.

1. Instalar dependencias:
```bash
npm install
```

2. Compilar estilos una vez:
```bash
npm run build:css
```

3. Modo desarrollo (watch):
```bash
npm run dev:css
```

Archivo fuente: `app/assets/css/tailwind.css`  
Archivo generado: `app/assets/css/app.css`

## 📄 Licencia

Proyecto educativo.

