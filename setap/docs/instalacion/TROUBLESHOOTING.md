# 🔧 Guía de Troubleshooting - SETAP

## 📌 Índice de Problemas Comunes

- [Error 500 - Internal Server Error](#error-500---internal-server-error)
- [Error 404 - Rutas No Encontradas](#error-404---rutas-no-encontradas)
- [Error de Conexión a Base de Datos](#error-de-conexión-a-base-de-datos)
- [Página en Blanco](#página-en-blanco)
- [CSS/JavaScript No Cargan](#cssjavascript-no-cargan)
- [AJAX Devuelve HTML en Lugar de JSON](#ajax-devuelve-html-en-lugar-de-json)
- [Sesiones No Funcionan](#sesiones-no-funcionan)
- [Permisos Denegados](#permisos-denegados)
- [Caracteres Extraños en Textos](#caracteres-extraños-en-textos)
- [Memoria Agotada](#memoria-agotada)

---

## Error 500 - Internal Server Error

### Síntoma
Al acceder a cualquier página, aparece:
```
Internal Server Error
The server encountered an internal error or misconfiguration...
```

### Causas Comunes

#### 1. Archivo .env No Existe o Está Mal Configurado

**Verificación**:
```bash
# En File Manager de cPanel
# Navegar a: /public_html/setap/
# Verificar que existe: .env
```

**Solución**:
1. Si no existe, copiar desde `.env.example`
2. Editar con credenciales correctas:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   DB_HOST=localhost
   DB_DATABASE=comerci3_bdsetap  # Nombre COMPLETO con prefijo
   DB_USERNAME=comerci3_setap    # Usuario COMPLETO con prefijo
   DB_PASSWORD=tu_contraseña
   ```
3. Verificar permisos: `chmod 600 .env`

**Activar modo debug temporalmente** (solo para diagnóstico):
```env
APP_DEBUG=true  # SOLO TEMPORAL
```
Acceder nuevamente para ver el error específico, luego volver a `false`.

#### 2. Error en .htaccess

**Verificación**:
```bash
# Verificar que existe: /public_html/setap/public/.htaccess
# Activar "Show Hidden Files" en File Manager Settings
```

**Contenido correcto**:
```apache
RewriteEngine On

# Permitir acceso directo a archivos y directorios que existen
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Redirigir todo lo demás a index.php
RewriteRule ^ index.php [L,QSA]
```

**Solución**:
1. Si no existe, crear el archivo con el contenido de arriba
2. Verificar sintaxis (sin espacios extra, sin caracteres extraños)
3. Permisos: `chmod 644 .htaccess`

#### 3. Errores de Sintaxis PHP

**Verificación**:
```bash
# En cPanel → Metrics → Errors
# Buscar mensajes como:
# "PHP Parse error: syntax error..."
```

**Solución**:
1. Revisar el archivo mencionado en el error
2. Corregir sintaxis PHP
3. Verificar que todos los archivos se subieron correctamente

#### 4. Vendor/Autoload.php No Existe

**Verificación**:
```bash
# Verificar que existe: /public_html/setap/vendor/autoload.php
```

**Solución**:
```bash
# En tu computadora local, ANTES de subir:
cd comercial-elroblev2/setap
composer install --no-dev --optimize-autoloader

# Luego subir todo nuevamente incluyendo la carpeta vendor/
```

### Diagnóstico General

**Revisar logs de error**:
1. cPanel → **Metrics** → **Errors**
2. O revisar archivo: `/home/usuario/public_html/setap/error_log`

**Activar error reporting** (temporal):
En `public/index.php`, agregar al inicio:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
⚠️ **ELIMINAR** después de diagnosticar.

---

## Error 404 - Rutas No Encontradas

### Síntoma
La página principal carga, pero rutas como `/users/list` dan 404:
```
Not Found
The requested URL was not found on this server.
```

### Causas Comunes

#### 1. Archivo .htaccess Faltante

**Causa**: El archivo `.htaccess` en `public/` no existe o está vacío.

**Solución**:
1. Activar "Show Hidden Files" en File Manager:
   - Settings (esquina superior derecha) → Marcar "Show Hidden Files"
2. Verificar en: `/public_html/setap/public/.htaccess`
3. Si no existe, crearlo con:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^ index.php [L,QSA]
   ```
4. Permisos: `chmod 644 .htaccess`

#### 2. mod_rewrite No Habilitado

**Verificación**:
1. Crear archivo `info.php` en `public/`:
   ```php
   <?php phpinfo(); ?>
   ```
2. Acceder: `https://www.comercial-elroble.cl/setap/public/info.php`
3. Buscar "mod_rewrite" en la página
4. **ELIMINAR** `info.php` después

**Solución**:
En la mayoría de cPanel, mod_rewrite ya está habilitado. Si no:
- Contactar al proveedor de hosting para habilitarlo

#### 3. AllowOverride No Configurado

**Causa**: El servidor no permite que `.htaccess` sobrescriba configuraciones.

**Solución**:
Contactar al proveedor de hosting para verificar que `AllowOverride All` esté configurado.

#### 4. Ruta Base Incorrecta

**Verificación**:
Si accedes con `/setap/public/users/list`, verifica la configuración base.

**Solución temporal**:
Acceder con la ruta completa:
```
https://www.comercial-elroble.cl/setap/public/index.php/users/list
```

Si esto funciona, el problema es el `.htaccess`.

---

## Error de Conexión a Base de Datos

### Síntoma
```
Error de conexión a la base de datos
RuntimeException: Error de conexión a la base de datos: ...
```

### Causas Comunes

#### 1. Credenciales Incorrectas en .env

**Problema más común**: No usar el nombre completo con prefijo.

**Verificación**:
1. cPanel → MySQL® Databases
2. Sección "Current Databases"
3. Anotar el nombre COMPLETO de la base de datos (incluye prefijo)
4. Sección "Current Users"
5. Anotar el nombre COMPLETO del usuario (incluye prefijo)

**Solución**:
Editar `.env` con nombres COMPLETOS:
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=comerci3_bdsetap    # ⚠️ CON PREFIJO
DB_USERNAME=comerci3_setap      # ⚠️ CON PREFIJO
DB_PASSWORD=tu_contraseña_exacta
```

#### 2. Usuario No Tiene Permisos

**Verificación**:
1. cPanel → MySQL® Databases
2. Sección "Current Databases"
3. Verificar que el usuario está listado con la BD

**Solución**:
1. En "Add User To Database"
2. Seleccionar usuario y base de datos
3. Click "Add"
4. Marcar **ALL PRIVILEGES**
5. Click "Make Changes"

#### 3. Base de Datos No Existe

**Verificación**:
1. cPanel → phpMyAdmin
2. Verificar que la base de datos aparece en el panel izquierdo

**Solución**:
1. Si no existe, crear en cPanel → MySQL® Databases
2. Ejecutar script: `INSTALACION_BD_COMPLETA.sql`

#### 4. Contraseña con Caracteres Especiales

**Problema**: Algunos caracteres especiales pueden causar problemas.

**Solución**:
1. Si la contraseña tiene caracteres especiales como `@`, `$`, `&`, etc.
2. Probar cambiar la contraseña en cPanel por una sin caracteres especiales
3. Actualizar en `.env`

#### 5. Host Incorrecto

**Verificación**:
En la mayoría de cPanel, el host es `localhost`.

**Solución alternativa**:
Algunos servidores usan:
```env
DB_HOST=127.0.0.1
# o
DB_HOST=localhost:3306
```

### Probar Conexión Manualmente

Crear archivo `test_db.php` en la raíz:
```php
<?php
$host = 'localhost';
$db   = 'comerci3_bdsetap';  // Tu nombre real
$user = 'comerci3_setap';    // Tu usuario real
$pass = 'tu_contraseña';      // Tu contraseña real

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "\u2705 Conexión exitosa!";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

Acceder: `https://www.comercial-elroble.cl/setap/test_db.php`

⚠️ **ELIMINAR** este archivo después de probar.

---

## Página en Blanco

### Síntoma
La página carga pero no muestra nada (completamente en blanco).

### Causas Comunes

#### 1. Error Fatal de PHP

**Diagnóstico**:
1. Ver logs: cPanel → Metrics → Errors
2. Buscar "Fatal error" o "Parse error"

**Solución temporal para ver el error**:
En `public/index.php`, agregar al inicio:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**Errores comunes**:
- `Class not found`: Problema con autoload de Composer
- `Call to undefined function`: Falta extensión PHP
- `Parse error`: Error de sintaxis

#### 2. Memoria Agotada

**Síntoma en logs**:
```
PHP Fatal error: Allowed memory size of X bytes exhausted
```

**Solución**:
Ver sección [Memoria Agotada](#memoria-agotada)

#### 3. Output Buffering

**Causa**: Error antes de que se envíe contenido al navegador.

**Solución**:
1. Revisar logs de error
2. Verificar que no hay espacios o caracteres antes de `<?php` en archivos PHP

---

## CSS/JavaScript No Cargan

### Síntoma
La página carga pero sin estilos (solo texto plano) o sin funcionalidad JavaScript.

### Causas Comunes

#### 1. Archivos No Existen

**Verificación**:
```bash
# En File Manager, verificar que existen:
/public_html/setap/public/assets/
/public_html/setap/public/css/
/public_html/setap/public/js/
```

**Solución**:
Verificar que se subieron todos los archivos correctamente.

#### 2. Rutas Incorrectas

**Verificación**:
1. Abrir DevTools del navegador (F12)
2. Ir a pestaña "Network"
3. Recargar página
4. Buscar archivos CSS/JS con estado 404

**Solución**:
En los archivos PHP de vistas, verificar rutas:
```php
<!-- INCORRECTO -->
<link href="/assets/css/style.css">

<!-- CORRECTO -->
<link href="/setap/public/assets/css/style.css">

<!-- O MEJOR (usando variable de entorno) -->
<link href="<?= $_ENV['APP_URL'] ?? '' ?>/public/assets/css/style.css">
```

#### 3. APP_URL Incorrecta en .env

**Verificación**:
```env
# En .env, debe ser:
APP_URL=https://www.comercial-elroble.cl/setap
```

**Solución**:
Corregir `APP_URL` en `.env` sin barra final.

#### 4. Permisos de Archivos

**Verificación**:
Archivos CSS/JS deben tener permisos `644`.

**Solución**:
En File Manager:
1. Seleccionar archivos CSS/JS
2. Change Permissions → `644`

#### 5. Bloqueado por .htaccess

**Verificación**:
En `public/.htaccess`, verificar que permite acceso a archivos estáticos:
```apache
RewriteCond %{REQUEST_FILENAME} !-f  # ← Esta línea es importante
RewriteCond %{REQUEST_FILENAME} !-d
```

---

## AJAX Devuelve HTML en Lugar de JSON

### Síntoma
Las llamadas AJAX fallan y en la consola se ve HTML en lugar de JSON.

### Causas Comunes

#### 1. Error PHP en el Controller

**Diagnóstico**:
1. Abrir DevTools (F12) → Network
2. Hacer la llamada AJAX
3. Click en la request
4. Ver la respuesta completa

**Solución temporal**:
```env
# En .env (SOLO PARA DIAGNÓSTICO)
APP_DEBUG=true
```

Ver el error completo en la respuesta, corregir, y volver a `false`.

#### 2. Ruta AJAX Incorrecta

**Verificación**:
En JavaScript:
```javascript
// INCORRECTO
fetch('/users/delete')  // Falta prefijo de ruta

// CORRECTO
fetch('/setap/users/delete')

// O MEJOR
const baseUrl = '<?= $_ENV['APP_URL'] ?? '' ?>';
fetch(`${baseUrl}/users/delete`)
```

#### 3. Devolviendo Vista en Lugar de JSON

**Problema**: El controller devuelve una vista HTML en lugar de JSON.

**Verificación en Controller**:
```php
// INCORRECTO
public function delete() {
    // ...
    return $this->view('success');
}

// CORRECTO
public function delete() {
    // ...
    $this->jsonSuccess('Usuario eliminado');
}
```

#### 4. Error Antes de la Respuesta JSON

**Problema**: Hay un error/warning de PHP que imprime HTML antes del JSON.

**Solución**:
1. Revisar logs de error
2. Corregir warnings/notices
3. Asegurarse que `APP_DEBUG=false` en producción

---

## Sesiones No Funcionan

### Síntoma
El usuario se desloguea constantemente o no puede iniciar sesión.

### Causas Comunes

#### 1. Permisos de Carpeta de Sesiones

**Solución**:
```bash
# Verificar que la carpeta de sesiones tiene permisos correctos
# Generalmente en: /tmp o configurado en php.ini
```

Contactar al hosting si persiste el problema.

#### 2. Dominio/Subdirectorio en session_path

**Verificación**:
Si la aplicación está en `/setap`, configurar:
```php
// En bootstrap o configuración de sesiones
session_set_cookie_params([
    'path' => '/setap',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

#### 3. SESSION_LIFETIME Muy Corto

**Verificación en .env**:
```env
SESSION_LIFETIME=3600  # 1 hora en segundos
```

**Solución**:
Aumentar el valor si es necesario.

---

## Permisos Denegados

### Síntoma
```
Permission denied
Warning: file_put_contents(...): failed to open stream: Permission denied
```

### Solución

#### Carpetas que Necesitan Escritura

```bash
storage/          → 755
storage/logs/     → 755
public/uploads/   → 755 (si existe)
```

**En File Manager**:
1. Click derecho en carpeta
2. Change Permissions
3. Establecer `755`

#### Archivos de Configuración
```bash
.env              → 600 (importante para seguridad)
public/.htaccess  → 644
```

---

## Caracteres Extraños en Textos

### Síntoma
Aparece texto como: `Ã¡`, `Ã±`, `Ã©` en lugar de `á`, `ñ`, `é`

### Causas

#### 1. Charset Incorrecto en BD

**Verificación en phpMyAdmin**:
1. Seleccionar BD
2. Operations
3. Verificar "Collation": debe ser `utf8mb4_unicode_ci`

**Solución**:
```sql
ALTER DATABASE comerci3_bdsetap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 2. Charset en Conexión PHP

**Verificación en Database.php**:
```php
$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4"; // ← Debe tener charset
```

#### 3. Meta Tag en HTML

**Verificación en vistas**:
```html
<meta charset="UTF-8">  <!-- Debe estar en el <head> -->
```

---

## Memoria Agotada

### Síntoma
```
Fatal error: Allowed memory size of X bytes exhausted
```

### Solución

#### 1. Aumentar Límite en .htaccess

En `public/.htaccess`, agregar:
```apache
php_value memory_limit 256M
```

#### 2. Optimizar Consultas

- Evitar cargar muchos registros a la vez
- Usar paginación
- Optimizar queries SQL

#### 3. Contactar al Hosting

Si el problema persiste, el límite puede estar establecido a nivel de servidor.

---

## 🔍 Herramientas de Diagnóstico

### Script de Verificación
```bash
# Subir y ejecutar:
verificar_instalacion.php
```

### Revisar Logs

1. **Error Log de Apache**:
   - cPanel → Metrics → Errors

2. **Error Log de PHP**:
   - `/home/usuario/public_html/setap/error_log`

3. **MySQL Error Log**:
   - cPanel → phpMyAdmin → Status → Monitor

### DevTools del Navegador

1. **Consola** (F12 → Console):
   - Errores JavaScript
   - Errores AJAX

2. **Network** (F12 → Network):
   - Requests fallidos
   - Respuestas de servidor
   - Tiempos de carga

---

## 📞 Cuando Pedir Ayuda

Si después de revisar esta guía el problema persiste, recopila:

1. **Mensaje de error completo** (screenshot)
2. **Logs de error** (cPanel → Errors)
3. **Configuración**:
   - Versión de PHP
   - Contenido de `.env` (SIN contraseñas)
   - Contenido de `.htaccess`
4. **Pasos para reproducir el error**
5. **Resultado del script** `verificar_instalacion.php`

---

**Última actualización**: 2025-10-22  
**Versión**: 1.0
