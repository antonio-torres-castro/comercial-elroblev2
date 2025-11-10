# CONFIGURACION APACHE LOCAL - Replicar Produccion

## Fecha: 2025-10-23
## Objetivo: Configurar localhost:8080 para replicar la estructura de produccion

---

## ESTRUCTURA OBJETIVO

```
localhost:8080/          -> Sitio web estatico (index.html)
localhost:8080/setap     -> Aplicacion PHP (login)
localhost:8080/setap/home -> Rutas internas de la aplicacion
```

---

## ARCHIVOS MODIFICADOS

### 1. Apache httpd.conf
**Ubicacion:** `C:\Apache24\conf\httpd.conf`
**Fuente:** `comercial-elroblev2/setap/Apache24/conf/httpd.conf`

**Cambios realizados:**
- DocumentRoot cambiado a: `C:/Users/aseso/source/repos/comercial-elroblev2`
- DirectoryIndex: `index.html index.php`

### 2. Apache httpd-vhosts.conf
**Ubicacion:** `C:\Apache24\conf\extra\httpd-vhosts.conf`
**Fuente:** `comercial-elroblev2/setap/Apache24/conf/extra/httpd-vhosts.conf`

**Cambios realizados:**
- VirtualHost comentado (no es necesario para esta configuracion)

### 3. .htaccess raiz del proyecto
**Ubicacion destino:** `C:/Users/aseso/source/repos/comercial-elroblev2/.htaccess`
**Fuente:** `comercial-elroblev2/_htaccess`

**IMPORTANTE - Manejo de PHP Handlers:**
El archivo contiene AMBAS configuraciones:
```apache
# PHP Handler - Activar segun entorno
<IfModule mime_module>
  # PHP Handler (production - cPanel EasyApache)
  AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
  
  # PHP Handler (local - Apache estandar Windows)
  ##AddHandler application/x-httpd-php .php .php8 .phtml
</IfModule>
```

**Para LOCAL:** Comentar la linea de produccion y descomentar la linea local:
```apache
  ##AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
  AddHandler application/x-httpd-php .php .php8 .phtml
```

**Para PRODUCCION:** Comentar la linea local y descomentar la linea de produccion:
```apache
  AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
  ##AddHandler application/x-httpd-php .php .php8 .phtml
```

### 4. .htaccess en setap/
**Ubicacion destino:** `C:/Users/aseso/source/repos/comercial-elroblev2/setap/.htaccess`
**Fuente:** `comercial-elroblev2/setap/_htaccess`

**IMPORTANTE - Mismo manejo de PHP Handlers:**
Tambien contiene AMBAS configuraciones. Usar el mismo criterio que el .htaccess raiz.

### 5. .htaccess en setap/public/
**Ubicacion:** `C:/Users/aseso/source/repos/comercial-elroblev2/setap/public/.htaccess`
**Ya modificado directamente**

**Cambios realizados:**
- RewriteBase cambiado de `/` a `/setap/`

---

## INSTRUCCIONES DE IMPLEMENTACION

### PASO 1: Detener Apache
```cmd
cd C:\Apache24\bin
httpd.exe -k stop
```

### PASO 2: Respaldar configuracion actual
```cmd
copy C:\Apache24\conf\httpd.conf C:\Apache24\conf\httpd.conf.backup
copy C:\Apache24\conf\extra\httpd-vhosts.conf C:\Apache24\conf\extra\httpd-vhosts.conf.backup
```

### PASO 3: Copiar nuevos archivos de configuracion

#### Desde tu proyecto a Apache:
```cmd
cd C:\Users\aseso\source\repos\comercial-elroblev2

copy setap\Apache24\conf\httpd.conf C:\Apache24\conf\httpd.conf
copy setap\Apache24\conf\extra\httpd-vhosts.conf C:\Apache24\conf\extra\httpd-vhosts.conf
```

### PASO 4: Copiar archivos .htaccess

#### En la raiz del proyecto:
```cmd
cd C:\Users\aseso\source\repos\comercial-elroblev2
copy _htaccess .htaccess
```

#### En setap/:
```cmd
cd C:\Users\aseso\source\repos\comercial-elroblev2\setap
copy _htaccess .htaccess
```

### PASO 5: Ajustar PHP Handlers para LOCAL

**IMPORTANTE:** Edita los .htaccess para activar el handler local:

#### En `.htaccess` raiz:
```apache
# PHP Handler - Activar segun entorno
<IfModule mime_module>
  # PHP Handler (production - cPanel EasyApache)
  ##AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
  
  # PHP Handler (local - Apache estandar Windows)
  AddHandler application/x-httpd-php .php .php8 .phtml
</IfModule>
```

#### En `setap/.htaccess`:
```apache
# PHP Handler - Activar segun entorno
<IfModule mime_module>
  # PHP Handler (production - cPanel EasyApache)
  ##AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
  
  # PHP Handler (local - Apache estandar Windows)
  AddHandler application/x-httpd-php .php .php8 .phtml
</IfModule>
```

### PASO 6: Verificar configuracion de Apache
```cmd
cd C:\Apache24\bin
httpd.exe -t
```

**Respuesta esperada:** `Syntax OK`

### PASO 7: Iniciar Apache
```cmd
cd C:\Apache24\bin
httpd.exe -k start
```

### PASO 8: Verificar logs en caso de error
```cmd
type C:\Apache24\logs\error.log
```

---

## PRUEBAS

### 1. Sitio web estatico
**URL:** http://localhost:8080/
**Esperado:** Pagina principal de comercial-elroble.cl (index.html)

### 2. Aplicacion SETAP - Login
**URL:** http://localhost:8080/setap
**Esperado:** Formulario de login

### 3. Rutas internas
**URL:** http://localhost:8080/setap/home
**Esperado:** Pagina de inicio (requiere autenticacion)

### 4. Archivos estaticos
**URL:** http://localhost:8080/assets/images/logo.png
**Esperado:** Imagen del logo

---

## MANEJO DE PHP HANDLERS ENTRE ENTORNOS

### ESTRATEGIA:
Todos los archivos .htaccess contienen AMBAS configuraciones de PHP handlers.
Solo necesitas comentar/descomentar segun el entorno.

### ARCHIVOS QUE REQUIEREN AJUSTE:
1. `.htaccess` (raiz del proyecto)
2. `setap/.htaccess`

### NO REQUIEREN AJUSTE:
- `setap/public/.htaccess` (no tiene handler PHP)

### ANTES DE SUBIR A PRODUCCION:
```apache
# PHP Handler - Activar segun entorno
<IfModule mime_module>
  # PHP Handler (production - cPanel EasyApache)
  AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
  
  # PHP Handler (local - Apache estandar Windows)
  ##AddHandler application/x-httpd-php .php .php8 .phtml
</IfModule>
```

### EN DESARROLLO LOCAL:
```apache
# PHP Handler - Activar segun entorno
<IfModule mime_module>
  # PHP Handler (production - cPanel EasyApache)
  ##AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
  
  # PHP Handler (local - Apache estandar Windows)
  AddHandler application/x-httpd-php .php .php8 .phtml
</IfModule>
```

---

## TROUBLESHOOTING

### Problema: Apache no inicia
**Solucion:**
1. Verificar que no haya otro proceso usando el puerto 8080
2. Revisar `C:\Apache24\logs\error.log`
3. Verificar sintaxis: `httpd.exe -t`

### Problema: localhost:8080 muestra error 404
**Solucion:**
1. Verificar que DocumentRoot apunte correctamente a tu proyecto
2. Verificar permisos de la carpeta del proyecto

### Problema: /setap muestra listado de archivos
**Solucion:**
1. Verificar que existe `setap/.htaccess`
2. Verificar que `AllowOverride All` este en httpd.conf

### Problema: /setap/home da error 404
**Solucion:**
1. Verificar que `setap/public/.htaccess` tenga `RewriteBase /setap/`
2. Verificar que `mod_rewrite` este habilitado en httpd.conf

### Problema: PHP no funciona (archivos .php se descargan)
**Solucion:**
1. Verificar que el PHP Handler este activo (sin ##)
2. Verificar que uses el handler correcto para tu entorno
3. En local: `application/x-httpd-php`
4. En produccion: `application/x-httpd-ea-php83`

---

## DIFERENCIAS CRITICAS CON PRODUCCION

### Produccion (cPanel):
- PHP Handler: `application/x-httpd-ea-php83` (EasyApache)
- DocumentRoot: `/home/comerci3/public_html`
- URL base: `https://comercial-elroble.cl`

### Local (Apache 2.4):
- PHP Handler: `application/x-httpd-php` (Apache estandar)
- DocumentRoot: `C:/Users/aseso/source/repos/comercial-elroblev2`
- URL base: `http://localhost:8080`

### Configuracion identica:
- Estructura de carpetas
- Logica de .htaccess (excepto PHP handler)
- RewriteBase en setap/public/
- Proteccion de archivos sensibles

---

## NOTAS IMPORTANTES

1. **CRITICO: Antes de commitear cambios**, verifica que linea de PHP handler esta activa
   - Para desarrollo: Activa `application/x-httpd-php`
   - Para produccion: Activa `application/x-httpd-ea-php83`

2. **Los archivos .htaccess SI se commitean** al repositorio
   - Contienen AMBAS configuraciones
   - Tu decides cual activar segun el entorno

3. **El .env debe estar fuera de public/** (ya lo esta)

4. **RewriteBase debe ser /setap/** tanto en local como en produccion

5. **Archivos _htaccess** son plantillas que se copian a `.htaccess`

---

## ARCHIVOS GENERADOS

Todos los archivos estan listos en:

<filepath>comercial-elroblev2/setap/Apache24/conf/httpd.conf</filepath>
<filepath>comercial-elroblev2/setap/Apache24/conf/extra/httpd-vhosts.conf</filepath>
<filepath>comercial-elroblev2/_htaccess</filepath>
<filepath>comercial-elroblev2/setap/_htaccess</filepath>
<filepath>comercial-elroblev2/setap/public/.htaccess</filepath>

---


**Fecha:** 2025-10-23
