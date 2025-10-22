# ✔️ CHECKLIST DE DEPLOYMENT - SETAP

**Imprimir esta página y marcar cada paso al completarlo**

---

## 🔑 FASE 1: OBTENER CREDENCIALES (5 min)

- [ ] **1.1** Acceder a cPanel: `https://www.comercial-elroble.cl:2083`
- [ ] **1.2** Ir a: **MySQL® Databases**
- [ ] **1.3** Crear base de datos: `bdsetap`
  - **Nombre completo creado**: ________________________
- [ ] **1.4** Crear usuario MySQL: `setap`
  - **Usuario completo creado**: ________________________
  - **Contraseña generada**: ________________________
- [ ] **1.5** Asignar usuario a base de datos con **ALL PRIVILEGES**
- [ ] **1.6** Anotar credenciales completas:
  ```
  DB_HOST: localhost
  DB_PORT: 3306
  DB_DATABASE: _______________________
  DB_USERNAME: _______________________
  DB_PASSWORD: _______________________
  ```

---

## 💾 FASE 2: PREPARAR ARCHIVOS LOCALMENTE (10 min)

- [ ] **2.1** Copiar `.env.example` a `.env`
  ```bash
  cp .env.example .env
  ```

- [ ] **2.2** Editar `.env` con las credenciales anotadas:
  - [ ] APP_ENV=production
  - [ ] APP_DEBUG=false
  - [ ] APP_URL=https://www.comercial-elroble.cl/setap
  - [ ] DB_HOST=localhost
  - [ ] DB_DATABASE=_______________ (con prefijo)
  - [ ] DB_USERNAME=_______________ (con prefijo)
  - [ ] DB_PASSWORD=_______________

- [ ] **2.3** Instalar dependencias de Composer:
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
  - [ ] Verificar que se creó carpeta `vendor/`

- [ ] **2.4** Comprimir archivos:
  ```bash
  zip -r setap-deploy.zip . -x "*.git*" "tests/*" "storage/*.sqlite" "node_modules/*" "venv/*"
  ```
  - **Tamaño del archivo**: _____________ MB

---

## 📤 FASE 3: SUBIR ARCHIVOS AL SERVIDOR (10 min)

- [ ] **3.1** Acceder a File Manager en cPanel
- [ ] **3.2** Navegar a: `public_html/setap/`
- [ ] **3.3** Subir archivo: `setap-deploy.zip`
  - **Hora de inicio**: __________
  - **Hora de fin**: __________
- [ ] **3.4** Extraer archivos (Click derecho → Extract)
- [ ] **3.5** Eliminar archivo `.zip`
- [ ] **3.6** Verificar que existen estos archivos:
  - [ ] `.env`
  - [ ] `public/index.php`
  - [ ] `public/.htaccess` ⚠️ CRÍTICO
  - [ ] `vendor/autoload.php`
  - [ ] `src/App/Config/Database.php`

---

## 🗄️ FASE 4: INSTALAR BASE DE DATOS (5 min)

- [ ] **4.1** Acceder a phpMyAdmin desde cPanel
- [ ] **4.2** Seleccionar base de datos: _______________________
- [ ] **4.3** Ir a pestaña **"Import"**
- [ ] **4.4** Subir archivo: `INSTALACION_BD_COMPLETA.sql`
- [ ] **4.5** Click en **"Go"**
- [ ] **4.6** Verificar mensaje de éxito: ✅ Sí / ❌ No
- [ ] **4.7** Verificar que se crearon ~20 tablas:
  - [ ] clientes
  - [ ] personas
  - [ ] usuarios
  - [ ] proyectos
  - [ ] tareas
  - [ ] menu
  - [ ] estado_tipos
  - [ ] usuario_tipos
  - *...y otras*

---

## ⚙️ FASE 5: CONFIGURAR PERMISOS (3 min)

- [ ] **5.1** En File Manager, cambiar permisos de `.env`:
  - Click derecho → Change Permissions → `600`

- [ ] **5.2** Verificar permisos de `public/.htaccess`:
  - Debe ser: `644`

- [ ] **5.3** Si existe carpeta `storage/`:
  - Permisos: `755`

---

## ✅ FASE 6: VERIFICACIÓN FINAL (5 min)

- [ ] **6.1** Subir archivo `verificar_instalacion.php` a la raíz de setap

- [ ] **6.2** Acceder en navegador:
  ```
  https://www.comercial-elroble.cl/setap/verificar_instalacion.php
  ```

- [ ] **6.3** Verificar resultados:
  - **✅ Correctas**: _______
  - **⚠️ Advertencias**: _______
  - **❌ Errores**: _______

- [ ] **6.4** Si todo está OK, acceder a la aplicación:
  ```
  https://www.comercial-elroble.cl/setap/public
  ```
  - ¿Carga correctamente? ✅ Sí / ❌ No

- [ ] **6.5** ⚠️ **ELIMINAR** archivo `verificar_instalacion.php`

---

## 🔧 EN CASO DE ERRORES

### Error 500 - Internal Server Error
- [ ] Verificar que `.env` existe
- [ ] Verificar credenciales de BD en `.env`
- [ ] Revisar logs: cPanel → Metrics → Errors
- **Solución aplicada**: ________________________________

### Error 404 - Rutas no encontradas
- [ ] Verificar que `public/.htaccess` existe
- [ ] Activar "Show Hidden Files" en File Manager
- [ ] Verificar contenido de `.htaccess`
- **Solución aplicada**: ________________________________

### No conecta a Base de Datos
- [ ] Verificar credenciales en `.env` (con prefijos completos)
- [ ] Verificar permisos del usuario en MySQL Databases
- [ ] Probar conexión desde phpMyAdmin
- **Solución aplicada**: ________________________________

### CSS/JS no cargan
- [ ] Verificar que archivos existen en `public/assets/`
- [ ] Verificar rutas en HTML
- [ ] Verificar permisos: 644 en archivos estáticos
- **Solución aplicada**: ________________________________

---

## 🎉 POST-DEPLOYMENT

- [ ] **Crear usuario administrador inicial**
- [ ] **Configurar SSL (Let's Encrypt en cPanel - GRATUITO)**
- [ ] **Configurar backups automáticos de BD**
- [ ] **Verificar APP_DEBUG=false en producción**
- [ ] **Documentar credenciales en lugar seguro**
- [ ] **Configurar monitoreo de logs**

---

## 📝 NOTAS ADICIONALES

**Fecha de deployment**: ___________________________

**Persona responsable**: ___________________________

**Tiempo total empleado**: __________ minutos

**Incidencias encontradas**:

________________________________________________________________

________________________________________________________________

________________________________________________________________

**Observaciones**:

________________________________________________________________

________________________________________________________________

________________________________________________________________

---

## 📞 CONTACTOS DE SOPORTE

**Hosting/cPanel**: ___________________________________

**Desarrollador**: ____________________________________

**Urgencias**: ________________________________________

---

**Última actualización**: 2025-10-22  
**Versión**: 1.0
