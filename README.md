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
   docker-compose up -d
   ```

3. **Crear la base de datos en MySQL**
   ```bash
   docker exec practica1 mysql -u root -pcontra123 < schema.sql
   docker exec practica1 mysql -u root -pcontra123 userDB < seed.sql
   ```

4. **Acceder a la aplicación**
   - http://localhost:8080

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

## 📁 Estructura

```
app/
├── index.php              # Inicio
├── login.php              # Formulario de login
├── logout.php             # Cierre de sesión
├── style.css              # Estilos
├── dashboard/             # Dashboards por rol
│   ├── admin.php
│   ├── consulta.php
│   └── ingreso.php
└── includes/
    ├── auth.php           # Funciones de autenticación
    ├── config.php         # Cargar variables de entorno
    ├── db.php             # Conexión a BD
    └── layout.php         # Componentes HTML comunes
    
schema.sql                 # Estructura de base de datos
seed.sql                   # Datos iniciales
.env                       # Variables de entorno (NO SUBIR A GIT)
.env.example              # Plantilla de .env
.env.docker               # Configuración para Docker
```

## ⚙️ Configuración

El archivo `config.php` carga automáticamente las variables del `.env`. No necesitas hacer nada especial.

**Flujo de carga:**
```
login.php
   ↓
auth.php (require db.php)
   ↓
db.php (require config.php)
   ↓
config.php (lee .env desde raíz del proyecto)
   ↓
getenv() obtiene DB_HOST, DB_USER, etc.
   ↓
Conexión a MySQL ✓
```

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

## 📄 Licencia

Proyecto educativo.

