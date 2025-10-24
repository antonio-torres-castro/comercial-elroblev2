# 🛠️ Guía de Debug Web-Only para Producción

**Autor:** MiniMax Agent  
**Fecha:** 2025-10-24  
**Entorno:** Producción sin acceso a consola (Solo web + phpMyAdmin)

## 📋 Resumen de Limitaciones y Soluciones

### ❌ Lo que NO tienes acceso:
- Consola/terminal del servidor
- Comandos bash/shell
- Acceso a configuración de Apache
- Logs del sistema directamente

### ✅ Lo que SÍ tienes:
- Administrador de archivos web
- phpMyAdmin para la base de datos
- Modificación de archivos `.htaccess`
- Acceso completo a archivos PHP

---

## 🔧 Herramientas Creadas

### 1. **Panel Web de Debug Principal**
**Archivo:** `debug/web_debug_panel.php`  
**URL:** `https://tudominio.com/setap/debug/web_debug_panel.php`

**Características:**
- ✅ Dashboard en tiempo real con estado del sistema
- ✅ Monitoreo de memoria, PHP, base de datos
- ✅ Visualización de logs de aplicación
- ✅ Herramientas de diagnóstico automáticas
- ✅ Panel de pestañas organizado
- ✅ Auto-refresh cada 30 segundos

### 2. **Visor de Logs Web**
**Archivo:** `debug/log_viewer.php`  
**URL:** `https://tudominio.com/setap/debug/log_viewer.php`

**Características:**
- 📝 Lee logs de PHP, Apache y aplicación
- 🔍 Búsqueda en tiempo real
- 🎨 Resaltado de términos
- 📊 Filtrado por número de líneas
- 💾 Descarga de logs

### 3. **Analizador de Base de Datos**
**Archivo:** `debug/db_analyzer.php`  
**URL:** `https://tudominio.com/setap/debug/db_analyzer.php`

**Características:**
- 📊 Estadísticas completas de BD
- 📋 Gestión visual de tablas
- 🔍 Consultas SQL seguras (solo SELECT)
- 👥 Análisis específico de usuarios
- 🏥 Verificación de salud de BD

---

## 🚀 Instalación y Configuración

### Paso 1: Subir Archivos
1. **Sube todos los archivos** de la carpeta `debug/` a tu servidor via administrador de archivos:
   - `web_debug_panel.php`
   - `web_debug_actions.php`
   - `log_viewer.php`
   - `db_analyzer.php`
   - `.htaccess`

2. **Crea el directorio de logs:**
   ```
   /setap/logs/
   ```

3. **Sube el .htaccess** para logs:
   ```
   /setap/logs/.htaccess
   ```

### Paso 2: Configurar IPs de Acceso

**⚠️ IMPORTANTE:** Por seguridad, debes configurar tu IP pública en TODOS los archivos.

**Edita cada archivo y cambia `TU_IP_PUBLICA_AQUI` por tu IP real:**

```php
$allowedIPs = [
    '127.0.0.1',
    'localhost',
    'TU_IP_PUBLICA_AQUI'  // ← Cambia esto
];
```

**Para obtener tu IP pública:**
- Ve a: https://whatismyipaddress.com/
- O busca en Google: "mi ip pública"

### Paso 3: Verificar Permisos

Asegúrate de que el directorio `logs/` tenga permisos de escritura:
```
/setap/logs/ → Permisos 755
```

---

## 📱 Uso de las Herramientas

### Panel Principal de Debug
**URL:** `https://tudominio.com/setap/debug/web_debug_panel.php`

**Pestañas disponibles:**
1. **📊 Dashboard:** Estado general del sistema
2. **📝 Logs:** Visualización de logs de la aplicación
3. **🗄️ Base de Datos:** Estadísticas y análisis de BD
4. **🐘 PHP:** Configuración y estado de PHP
5. **🛠️ Herramientas:** Diagnósticos y utilidades

### Visor de Logs
**URL:** `https://tudominio.com/setap/debug/log_viewer.php`

**Tipos de logs disponibles:**
- **Aplicación Debug:** Logs de la aplicación
- **Errores PHP:** Error log de PHP
- **Errores Apache:** Log de errores del servidor
- **Accesos Apache:** Log de accesos

### Analizador de Base de Datos
**URL:** `https://tudominio.com/setap/debug/db_analyzer.php`

**Secciones disponibles:**
1. **📊 Resumen:** Estadísticas generales
2. **📋 Tablas:** Lista y gestión de tablas
3. **🔍 Consulta:** Ejecutar consultas SQL (solo SELECT)
4. **👥 Usuarios:** Análisis específico de tabla usuarios
5. **🏥 Salud:** Verificación de estado de BD

---

## 🔍 Diagnóstico Paso a Paso

### Cuando tengas problemas en producción:

#### 1. **Verificar Estado General**
- Ve al Panel de Debug: `debug/web_debug_panel.php`
- Revisa el Dashboard para errores inmediatos
- Verifica el estado de memoria y PHP

#### 2. **Revisar Logs**
- Ve al Visor de Logs: `debug/log_viewer.php`
- Empieza con "Errores PHP" para errores técnicos
- Revisa "Logs de Aplicación" para problemas de negocio
- Usa búsqueda para términos específicos

#### 3. **Analizar Base de Datos**
- Ve al Analizador: `debug/db_analyzer.php`
- Verifica conectividad en la pestaña "Salud"
- Revisa estadísticas de tablas en "Resumen"
- Usa "Consulta" para investigar datos específicos

#### 4. **Ejecutar Diagnóstico Completo**
- En el Panel de Debug, pestaña "Herramientas"
- Usa "Ejecutar Diagnóstico Completo"
- Descarga el reporte para análisis detallado

---

## 🛡️ Seguridad

### Configuración de IPs
- ✅ Solo tu IP puede acceder a las herramientas
- ✅ Acceso denegado por defecto para otras IPs
- ✅ Logs de acceso para auditoría

### Limitaciones Implementadas
- ❌ Solo consultas SELECT permitidas en BD
- ❌ Bloqueado acceso a archivos sensibles
- ❌ Headers de seguridad activos
- ❌ Cache desactivado en herramientas de debug

### Recomendaciones de Seguridad
1. **Cambia tu IP** en todos los archivos antes de subir
2. **Elimina las herramientas** cuando termines el debugging
3. **No compartas** las URLs con nadie más
4. **Usa HTTPS** siempre que sea posible

---

## 🆘 Resolución de Problemas Comunes

### "Error: Acceso Denegado"
**Causa:** Tu IP no está configurada  
**Solución:** Edita los archivos y agrega tu IP real en `$allowedIPs`

### "No se encontró archivo de logs"
**Causa:** PHP no tiene configurado error_log o no tienes permisos  
**Solución:** Revisa la configuración de PHP o usa solo logs de aplicación

### "Error de conexión a base de datos"
**Causa:** Credenciales incorrectas o servidor inaccesible  
**Solución:** Verifica `config/database.php` y usa phpMyAdmin como alternativa

### "Panel no se actualiza"
**Causa:** Auto-refresh deshabilitado  
**Solución:** Activa "Auto-Refresh" en el panel principal

---

## 📊 Integración con phpMyAdmin

### Cómo complementar phpMyAdmin:

1. **Para consultas complejas:** Usa phpMyAdmin
2. **Para monitoreo general:** Usa el Analizador de BD
3. **Para logs:** Usa el Visor de Logs
4. **Para diagnóstico:** Usa el Panel Principal

### Flujo de trabajo recomendado:
```
Problema detectado → Panel Debug → Logs → phpMyAdmin → Analizador BD → Solución
```

---

## 📋 Lista de Verificación Pre-Producción

Antes de subir a producción:

- [ ] Subir todos los archivos de debug
- [ ] Configurar IP pública en todos los archivos
- [ ] Crear directorio logs/ con permisos correctos
- [ ] Probar acceso a cada herramienta
- [ ] Verificar que se pueden leer logs
- [ ] Confirmar conectividad de base de datos
- [ ] Documentar URLs para acceso futuro

---

## 🔗 URLs de Acceso Rápido

Una vez configurado, tendrás acceso a:

- **Panel Principal:** `https://tudominio.com/setap/debug/web_debug_panel.php`
- **Visor de Logs:** `https://tudominio.com/setap/debug/log_viewer.php`
- **Analizador BD:** `https://tudominio.com/setap/debug/db_analyzer.php`
- **phpMyAdmin:** (URL que te proporcione tu hosting)

---

## ⚠️ Recordatorios Importantes

1. **Solo para debugging:** Estas herramientas son para diagnosticar problemas, no para uso diario
2. **Seguridad primero:** Siempre configura tu IP antes de subir
3. **Limpieza:** Elimina las herramientas cuando termines el debugging
4. **Logs:** Los logs se guardan en `/setap/logs/web_debug.log`
5. **Acceso:** Si cambias de IP, actualiza la configuración

---

## 🆘 Soporte

Si tienes problemas:
1. Verifica que tu IP esté correctamente configurada
2. Confirma que el directorio `logs/` existe y tiene permisos
3. Revisa que `config/database.php` sea accesible
4. Usa phpMyAdmin como alternativa para la base de datos

**¡Herramientas listas para usar en tu entorno de producción web-only!** 🎉