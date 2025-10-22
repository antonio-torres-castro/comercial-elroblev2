# 🚀 Guía Completa de Deployment - SETAP en Producción

## 📋 Tabla de Contenidos
1. [Requisitos Previos](#requisitos-previos)
2. [Obtener Credenciales de cPanel](#obtener-credenciales-de-cpanel)
3. [Preparación de Archivos](#preparación-de-archivos)
4. [Crear Base de Datos](#crear-base-de-datos)
5. [Subir Archivos al Servidor](#subir-archivos-al-servidor)
6. [Configuración del Entorno](#configuración-del-entorno)
7. [Ejecutar Scripts de Base de Datos](#ejecutar-scripts-de-base-de-datos)
8. [Configuración de Apache](#configuración-de-apache)
9. [Verificación Final](#verificación-final)
10. [Solución de Problemas](#solución-de-problemas)

---

## ✅ Requisitos Previos

### Servidor (Ya instalado según tu información)
- ✓ Linux (distribución desconocida)
- ✓ Apache 2.4 con `mod_rewrite` habilitado
- ✓ PHP 8.3
- ✓ MySQL 8
- ✓ phpMyAdmin 8 (accesible desde cPanel)

### Extensiones PHP Necesarias
Verifica que estén instaladas (generalmente ya vienen en cPanel):
- `pdo`
- `pdo_mysql`
- `json`
- `mbstring`
- `openssl`
- `session`

---

## 🔑 Obtener Credenciales de cPanel

### Paso 1: Acceder a cPanel
1. Accede a tu cPanel en: `https://www.comercial-elroble.cl:2083`
2. Ingresa con tus credenciales de hosting

### Paso 2: Crear Usuario de Base de Datos

#### 2.1 Ir a MySQL® Databases
1. En cPanel, busca la sección **"Databases"** (Bases de Datos)
2. Haz clic en **"MySQL® Databases"**

#### 2.2 Crear Nueva Base de Datos
1. En la sección **"Create New Database"** (Crear Nueva Base de Datos):
   - **Nombre**: `bdsetap` (el sistema automáticamente agregará el prefijo de tu cuenta)
   - El nombre completo será algo como: `comerci3_bdsetap`
2. Haz clic en **"Create Database"**
3. **⚠️ IMPORTANTE**: Anota el nombre completo de la base de datos

#### 2.3 Crear Usuario de MySQL
1. En la sección **"MySQL Users"** (Usuarios MySQL):
   - **Username**: `setap` (se agregará prefijo automáticamente)
   - **Password**: Genera una contraseña segura o usa el generador
   - El usuario completo será algo como: `comerci3_setap`
2. Haz clic en **"Create User"**
3. **⚠️ IMPORTANTE**: Anota el usuario y contraseña

#### 2.4 Asignar Usuario a Base de Datos
1. En la sección **"Add User To Database"**:
   - Selecciona el usuario que creaste
   - Selecciona la base de datos que creaste
2. Haz clic en **"Add"**
3. En la pantalla de privilegios, marca **"ALL PRIVILEGES"** (Todos los privilegios)
4. Haz clic en **"Make Changes"**

#### 2.5 Anotar Información de Conexión

**IMPORTANTE**: Guarda esta información, la necesitarás para el archivo `.env`:

```
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=comerci3_bdsetap  (nombre completo con prefijo)
DB_USERNAME=comerci3_setap    (usuario completo con prefijo)
DB_PASSWORD=tu_contraseña_generada
```

---

## 📦 Preparación de Archivos

### Paso 1: Crear archivo .env

1. En tu computadora local, copia el archivo `.env.example` a `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edita el archivo `.env` con los datos que anotaste de cPanel:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_NAME="SETAP - Sistema de Gestión"
   APP_URL=https://www.comercial-elroble.cl/setap

   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=comerci3_bdsetap
   DB_USERNAME=comerci3_setap
   DB_PASSWORD=tu_contraseña_aqui

   PASSWORD_MIN_LENGTH=8
   SESSION_LIFETIME=3600
   TIMEZONE=America/Santiago
   LOCALE=es_CL
   ```

### Paso 2: Instalar Dependencias de Composer

**IMPORTANTE**: Ejecuta esto en tu computadora local ANTES de subir al servidor:

```bash
cd /ruta/a/comercial-elroblev2/setap
composer install --no-dev --optimize-autoloader
```

Esto instalará solo las dependencias de producción y optimizará el autoloader.

### Paso 3: Verificar Permisos de Archivos

Asegúrate de que no incluyas archivos innecesarios:

**NO subir al servidor:**
- `.git/` (carpeta de control de versiones)
- `node_modules/` (si existe)
- `tests/` (pruebas unitarias)
- `storage/*.sqlite` (bases de datos de desarrollo)
- Archivos de configuración local

---

## 📤 Subir Archivos al Servidor

### Opción 1: File Manager de cPanel (Recomendado para principiantes)

1. **Acceder a File Manager**:
   - En cPanel, busca **"Files"** → **"File Manager"**
   - Navega a `public_html/setap/`

2. **Comprimir archivos localmente**:
   ```bash
   # En tu computadora local, desde la carpeta setap
   zip -r setap.zip . -x "*.git*" "tests/*" "storage/*.sqlite" "node_modules/*"
   ```

3. **Subir archivo comprimido**:
   - En File Manager, haz clic en **"Upload"**
   - Selecciona `setap.zip`
   - Espera a que se complete la carga

4. **Extraer archivos**:
   - Haz clic derecho en `setap.zip`
   - Selecciona **"Extract"**
   - Elimina el archivo `.zip` después de extraer

### Opción 2: FTP (Más rápido para actualizaciones)

1. **Obtener credenciales FTP de cPanel**:
   - En cPanel → **"FTP Accounts"**
   - Anota el servidor FTP, usuario y puerto

2. **Usar cliente FTP** (FileZilla, WinSCP, etc.):
   ```
   Host: ftp.comercial-elroble.cl
   Usuario: tu_usuario_cpanel
   Contraseña: tu_contraseña_cpanel
   Puerto: 21
   ```

3. **Subir todos los archivos** a `/public_html/setap/`

### Estructura de carpetas en el servidor

Después de subir, la estructura debe verse así:
```
/public_html/setap/
├── .env                    # ⚠️ IMPORTANTE: Verificar que existe
├── .htaccess               # Debe estar en la raíz
├── composer.json
├── public/
│   ├── index.php
│   ├── .htaccess          # ⚠️ CRÍTICO para funcionamiento
│   ├── assets/
│   ├── css/
│   └── js/
├── src/
│   └── App/
│       ├── Config/
│       ├── Controllers/
│       ├── Models/
│       └── Views/
├── vendor/                 # Creado por composer
└── BdScript/              # Scripts SQL (temporal)
```

---

## 🗄️ Ejecutar Scripts de Base de Datos

### Método 1: Usando phpMyAdmin (Recomendado)

#### Paso 1: Acceder a phpMyAdmin
1. En cPanel → **"Databases"** → **"phpMyAdmin"**
2. Selecciona tu base de datos `comerci3_bdsetap` en el panel izquierdo

#### Paso 2: Ejecutar Scripts en el Orden Correcto

**⚠️ IMPORTANTE**: Los scripts deben ejecutarse en este orden exacto:

1. **Crear Tablas Principales**:
   - Haz clic en la pestaña **"SQL"**
   - Abre el archivo `BdScript/CreationScript/Creacion_Tablas.sql`
   - **MODIFICA** la primera línea: Cambia `use comerci3_bdsetap;` por el nombre de TU base de datos
   - Copia y pega el contenido
   - Haz clic en **"Go"** (Continuar)

2. **Poblar Tipos Iniciales**:
   - Ejecuta `BdScript/CreationScript/Poblar_Tablas_Tipos.sql`
   - Luego `BdScript/CreationScript/Poblar_Tablas_Tipos_Faltantes.sql`

3. **Poblar Menús**:
   - Ejecuta `BdScript/CreationScript/Poblar_Menu_MenusIniciales.sql`

4. **Agregar Tablas Adicionales** (si es necesario):
   - `BdScript/CreationScript/CREATE TABLE proyecto_feriados.sql`
   - `BdScript/CreationScript/CREATE TABLE menu_grupo.sql`
   - `BdScript/CreationScript/Tabla usuario_tipo_permisos.sql`

5. **Modificaciones de Tablas**:
   - `BdScript/CreationScript/ALTER_Tabla_Menu_Add_url_icono_orden.sql`
   - `BdScript/CreationScript/ALTER_Tabla_Menu_Add_display.sql`

#### Paso 3: Verificar Creación de Tablas

En phpMyAdmin, verifica que se crearon todas las tablas:
```
- clientes
- cliente_contrapartes
- estado_tipos
- historial_tareas
- menu
- notificacion_medios
- notificacion_tipos
- permiso_tipos
- personas
- proyecto_feriados
- proyecto_tareas
- proyectos
- tareas
- tarea_fotos
- tarea_tipos
- usuario_notificaciones
- usuario_tipo_menus
- usuario_tipo_permisos
- usuario_tipos
- usuarios
```

### Método 2: Usando Script Consolidado

He creado un script SQL consolidado que ejecuta todo en el orden correcto:

1. Sube el archivo `INSTALACION_BD_COMPLETA.sql` a través de File Manager
2. En phpMyAdmin → **"Import"**
3. Selecciona el archivo `INSTALACION_BD_COMPLETA.sql`
4. Haz clic en **"Go"**

---

## ⚙️ Configuración de Apache

### Verificar .htaccess

#### Archivo 1: `/public_html/setap/public/.htaccess`

**⚠️ CRÍTICO**: Este archivo DEBE existir en la carpeta `public/`

```apache
RewriteEngine On

# Permitir acceso directo a archivos y directorios que existen
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Redirigir todo lo demás a index.php
RewriteRule ^ index.php [L,QSA]
```

**Verificación en cPanel**:
1. File Manager → `public_html/setap/public/`
2. Asegúrate de que `.htaccess` existe
3. Si no lo ves, activa **"Show Hidden Files"** en Settings

#### Configurar DocumentRoot (Si aplica)

Si quieres que `www.comercial-elroble.cl/setap` apunte directamente a la carpeta `public/`:

**Opción A: Crear subdirectorio en cPanel**
1. cPanel → **"Domains"** → **"Subdomains"**
2. No es necesario si solo usas `/setap`

**Opción B: Usar .htaccess en raíz de setap**

Crea `/public_html/setap/.htaccess` con:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### Verificar mod_rewrite

En la mayoría de servidores cPanel, `mod_rewrite` ya está habilitado. Para verificar:

1. Crea un archivo `info.php` en `public_html/setap/public/`:
   ```php
   <?php
   phpinfo();
   ```

2. Accede a `https://www.comercial-elroble.cl/setap/info.php`
3. Busca `mod_rewrite` en la página
4. **⚠️ ELIMINA** el archivo `info.php` después de verificar

---

## 🔒 Configurar Permisos de Archivos

### Permisos Recomendados

En File Manager de cPanel:

```bash
# Carpetas
storage/          → 755 (rwxr-xr-x)
public/           → 755 (rwxr-xr-x)
public/assets/    → 755 (rwxr-xr-x)

# Archivos
.env              → 600 (rw-------) ⚠️ IMPORTANTE: Solo lectura del propietario
archivos .php     → 644 (rw-r--r--)
archivos .htaccess → 644 (rw-r--r--)
```

**Para cambiar permisos en File Manager**:
1. Haz clic derecho en el archivo/carpeta
2. Selecciona **"Change Permissions"**
3. Establece los valores numéricos arriba indicados

### Permisos de storage/ (Si existe)

Si tienes una carpeta `storage/` para logs o archivos temporales:

```bash
chmod 755 storage/
chmod 644 storage/*.log
```

---

## ✅ Verificación Final

### Checklist Pre-Producción

- [ ] **Archivo .env existe y está configurado correctamente**
  ```bash
  # Verificar en File Manager: /public_html/setap/.env
  ```

- [ ] **Base de datos creada y poblada**
  ```sql
  -- En phpMyAdmin, ejecutar:
  SHOW TABLES;
  -- Debe mostrar ~20 tablas
  ```

- [ ] **Dependencias de Composer instaladas**
  ```bash
  # Verificar que existe: /public_html/setap/vendor/autoload.php
  ```

- [ ] **.htaccess en public/ existe**
  ```bash
  # Verificar: /public_html/setap/public/.htaccess
  ```

- [ ] **Permisos correctos en .env (600)**

- [ ] **APP_DEBUG=false en producción**

### Prueba de Funcionamiento

1. **Acceder a la aplicación**:
   ```
   https://www.comercial-elroble.cl/setap
   ```
   o
   ```
   https://www.comercial-elroble.cl/setap/public
   ```

2. **Verificar que carga la página de login/inicio**

3. **Probar una ruta AJAX** (desde DevTools del navegador):
   ```javascript
   fetch('/setap/users/list')
     .then(r => r.json())
     .then(console.log)
   ```

---

## 🔧 Solución de Problemas

### Error 500 - Internal Server Error

**Causa 1: Archivo .env no existe o mal configurado**
```bash
# Solución:
1. Verificar que /public_html/setap/.env existe
2. Verificar que tiene los datos correctos de DB
3. Verificar permisos: chmod 600 .env
```

**Causa 2: Error en .htaccess**
```bash
# Solución:
1. Verificar que /public_html/setap/public/.htaccess existe
2. Verificar sintaxis del archivo
3. Verificar que mod_rewrite está habilitado
```

**Causa 3: Errores PHP**
```bash
# Solución:
1. Revisar logs de error en cPanel → "Errors"
2. Verificar compatibilidad PHP 8.3
3. Verificar que todas las extensiones están instaladas
```

### Error 404 - Rutas no encontradas

**Problema**: Las rutas como `/users/list` dan 404

```bash
# Solución:
1. Verificar .htaccess en public/
2. Verificar AllowOverride en configuración Apache
3. Probar acceder con: /setap/public/index.php/users/list
```

### Error de Conexión a Base de Datos

**Problema**: "Error de conexión a la base de datos"

```bash
# Solución:
1. Verificar credenciales en .env:
   DB_HOST=localhost
   DB_DATABASE=comerci3_bdsetap (nombre completo con prefijo)
   DB_USERNAME=comerci3_setap (usuario completo con prefijo)
   DB_PASSWORD=tu_contraseña

2. Verificar que el usuario tiene permisos en la BD:
   - En cPanel → MySQL Databases → Current Databases
   - Verificar que el usuario está asociado con ALL PRIVILEGES

3. Probar conexión desde phpMyAdmin con las mismas credenciales
```

### Archivos CSS/JS no cargan (404)

**Problema**: Los estilos no se aplican

```bash
# Solución:
1. Verificar rutas en las vistas:
   - Deben ser relativas: /setap/public/assets/...
   - O absolutas: <?= APP_URL ?>/public/assets/...

2. Verificar que los archivos existen en:
   /public_html/setap/public/assets/
   /public_html/setap/public/css/
   /public_html/setap/public/js/

3. Verificar permisos: chmod 644 en archivos estáticos
```

### AJAX devuelve HTML en lugar de JSON

**Problema**: Las llamadas AJAX reciben HTML de error

```bash
# Solución:
1. Activar temporalmente APP_DEBUG=true en .env
2. Ver el error completo en la respuesta
3. Revisar logs del servidor
4. Desactivar APP_DEBUG=false después de resolver
```

---

## 📞 Contacto y Soporte

### Logs de Error

Para diagnosticar problemas:

1. **Error Log de Apache** (cPanel):
   - cPanel → **"Metrics"** → **"Errors"**

2. **Error Log de PHP**:
   - Ubicación: `/home/usuario/public_html/setap/error_log`

3. **Activar logs de aplicación** (temporal):
   ```php
   // En .env
   APP_DEBUG=true
   ```

### Recursos Adicionales

- **Documentación cPanel**: https://docs.cpanel.net/
- **Documentación PHP**: https://www.php.net/docs.php
- **Documentación Apache mod_rewrite**: https://httpd.apache.org/docs/current/mod/mod_rewrite.html

---

## 🎉 ¡Deployment Exitoso!

Si has completado todos los pasos, tu aplicación SETAP debería estar funcionando en:

```
https://www.comercial-elroble.cl/setap
```

**Próximos pasos recomendados**:
1. Crear usuario administrador inicial
2. Configurar backups automáticos de la base de datos
3. Configurar certificado SSL (Let's Encrypt gratuito en cPanel)
4. Monitorear logs regularmente
5. Implementar sistema de backups

---

**Última actualización**: 2025-10-22  
**Versión**: 1.0
