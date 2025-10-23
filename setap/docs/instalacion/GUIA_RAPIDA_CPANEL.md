# 🚀 Guía Rápida de Deployment en cPanel

## 🔑 Paso 1: Obtener Credenciales de Base de Datos (5 minutos)

### A. Acceder a cPanel
```
URL: https://www.comercial-elroble.cl:2083
```

### B. Crear Base de Datos MySQL

1. **Buscar**: `MySQL® Databases`

2. **Crear Base de Datos**:
   - Nombre: `bdsetap`
   - Sistema creará: `comerci3_bdsetap` ✅
   - **ANOTAR** el nombre completo

3. **Crear Usuario**:
   - Usuario: `setap`
   - Sistema creará: `comerci3_setap` ✅
   - Contraseña: *Generar una segura*
   - **ANOTAR** usuario y contraseña

4. **Asignar Usuario a BD**:
   - Usuario: `comerci3_setap`
   - BD: `comerci3_bdsetap`
   - Privilegios: **ALL PRIVILEGES** ✅

### C. Anotar Credenciales

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=comerci3_bdsetap    # ⚠️ Con prefijo
DB_USERNAME=comerci3_setap      # ⚠️ Con prefijo
DB_PASSWORD=tu_contraseña_aqui
```

---

## 💾 Paso 2: Preparar Archivos Localmente (10 minutos)

### A. Crear archivo .env

```bash
# En tu computadora, en la carpeta del proyecto
cp .env.example .env
```

Editar `.env` con las credenciales anotadas:

```env
APP_ENV=production
APP_DEBUG=false
APP_NAME="SETAP - Sistema de Gestión"
APP_URL=https://www.comercial-elroble.cl/setap

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=comerci3_bdsetap
DB_USERNAME=comerci3_setap
DB_PASSWORD=TU_CONTRASEÑA

PASSWORD_MIN_LENGTH=8
SESSION_LIFETIME=3600
TIMEZONE=America/Santiago
LOCALE=es_CL
```

### B. Instalar Dependencias de Composer

```bash
cd comercial-elroblev2/setap
composer install --no-dev --optimize-autoloader
```

**Verificar**: Que se creó la carpeta `vendor/`

### C. Comprimir Archivos para Subir

```bash
# En la carpeta setap
zip -r setap-deploy.zip . -x "*.git*" "tests/*" "storage/*.sqlite" "node_modules/*" "*"
```

---

## 📤 Paso 3: Subir Archivos al Servidor (10 minutos)

### A. Acceder a File Manager

1. cPanel → **"Files"** → **"File Manager"**
2. Navegar a: `public_html/`
3. Crear carpeta: `setap/` (si no existe)

### B. Subir Archivo Comprimido

1. Entrar a `public_html/setap/`
2. Click en **"Upload"**
3. Seleccionar `setap-deploy.zip`
4. Esperar que complete la carga

### C. Extraer Archivos

1. Click derecho en `setap-deploy.zip`
2. **"Extract"**
3. Verificar que se extrajo todo
4. Eliminar el archivo `.zip`

### D. Verificar Estructura

Debe verse así:
```
public_html/setap/
├── .env              ✅
├── public/
│   ├── index.php     ✅
│   └── .htaccess     ✅ IMPORTANTE
├── vendor/           ✅
└── src/
```

---

## 🗄️ Paso 4: Instalar Base de Datos (5 minutos)

### A. Acceder a phpMyAdmin

1. cPanel → **"Databases"** → **"phpMyAdmin"**
2. Click en tu BD: `comerci3_bdsetap` (panel izquierdo)

### B. Ejecutar Script de Instalación
**OPCIÓN 1: Importar archivo SQL** (Recomendado)

1. Subir `INSTALACION_BD_COMPLETA.sql` a File Manager
2. En phpMyAdmin → Pestaña **"Import"**
3. **"Choose File"** → Seleccionar `INSTALACION_BD_COMPLETA.sql`
4. Click **"Go"**
5. Verificar mensaje de éxito

**OPCIÓN 2: Copiar y pegar SQL**

1. Abrir `INSTALACION_BD_COMPLETA.sql` en un editor
2. **MODIFICAR** la línea 13: `USE comerci3_bdsetap;` con TU nombre de BD
3. Copiar TODO el contenido
4. En phpMyAdmin → Pestaña **"SQL"**
5. Pegar el contenido
6. Click **"Go"**

### C. Verificar Instalación

En phpMyAdmin, en el panel izquierdo deberías ver ~20 tablas:

✅ clientes  
✅ cliente_contrapartes  
✅ estado_tipos  
✅ historial_tareas  
✅ menu  
✅ personas  
✅ proyectos  
✅ tareas  
✅ usuarios  
*...y otras*

---

## ⚙️ Paso 5: Configurar Permisos (3 minutos)

### En File Manager

1. Click derecho en `.env` → **"Change Permissions"**
   - Valor: `600` ✅

2. Verificar permisos de `public/.htaccess`
   - Valor: `644` ✅

3. Verificar carpeta `storage/` (si existe)
   - Valor: `755` ✅

---

## ✅ Paso 6: Verificación Final (2 minutos)

### A. Verificar .htaccess

Asegurarse que `public/.htaccess` existe y contiene:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L,QSA]
```

### B. Acceder a la Aplicación

Abrir navegador:
```
https://www.comercial-elroble.cl/setap/public
```

Debería cargar la página de inicio/login 🎉

### C. Verificar Logs si Hay Errores

cPanel → **"Metrics"** → **"Errors"**

---

## ⚠️ Solución de Problemas Comunes

### Error 500

1. **Verificar .env existe**: `public_html/setap/.env`
2. **Verificar credenciales BD** en `.env`
3. **Revisar logs**: cPanel → Errors

### Error 404 en rutas

1. **Verificar .htaccess**: `public_html/setap/public/.htaccess`
2. **Activar archivos ocultos**: File Manager Settings → "Show Hidden Files"

### No se conecta a la BD

1. **Verificar credenciales** en `.env`:
   ```env
   DB_DATABASE=comerci3_bdsetap  # Con prefijo completo
   DB_USERNAME=comerci3_setap    # Con prefijo completo
   ```

2. **Verificar permisos del usuario** en MySQL Databases

### CSS/JS no cargan

1. Verificar que existen en: `public_html/setap/public/assets/`
2. Verificar rutas en el código HTML

---

## 🎉 ¡Listo!

Tu aplicación SETAP debería estar funcionando en:

```
https://www.comercial-elroble.cl/setap/public
```

### Próximos Pasos Recomendados:

1. ✅ Crear usuario administrador inicial
2. ✅ Configurar SSL (Let's Encrypt en cPanel es gratuito)
3. ✅ Configurar backups automáticos de BD
4. ✅ Cambiar `APP_DEBUG=false` en producción
---

**Tiempo total estimado**: ~35 minutos  
**Fecha**: 2025-10-22
