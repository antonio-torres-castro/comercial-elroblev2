# 🚀 Deployment de SETAP en Producción

## 📌 Inicio Rápido

¿Primera vez haciendo deployment? **Empieza aquí**:

1. 📊 **Lee primero**: <filepath>RESUMEN_EJECUTIVO.md</filepath>
2. ⏱️ **Deployment rápido (35 min)**: <filepath>GUIA_RAPIDA_CPANEL.md</filepath>
3. ✔️ **Imprime y sigue**: <filepath>CHECKLIST_DEPLOYMENT.md</filepath>

¿Necesitas más detalle? **Usa la guía completa**:
- 📚 **Guía detallada**: <filepath>GUIA_DEPLOYMENT_PRODUCCION.md</filepath>

---

## 📚 Documentación Disponible

### 🔴 Documentos Esenciales (LEER PRIMERO)

| Documento | Descripción | Tiempo de Lectura |
|-----------|-------------|-------------------|
| <filepath>RESUMEN_EJECUTIVO.md</filepath> | Visión general completa del deployment | 10 min |
| <filepath>GUIA_RAPIDA_CPANEL.md</filepath> | Guía paso a paso para cPanel | 35 min (hands-on) |
| <filepath>CHECKLIST_DEPLOYMENT.md</filepath> | Lista verificable para imprimir | 5 min |

### 🟡 Documentos de Referencia

| Documento | Descripción | Cuándo Usar |
|-----------|-------------|---------------|
| <filepath>GUIA_DEPLOYMENT_PRODUCCION.md</filepath> | Guía completa con troubleshooting | Para deployment detallado |
| <filepath>TROUBLESHOOTING.md</filepath> | Solución de problemas comunes | Cuando hay errores |
| <filepath>REQUISITOS_SERVIDOR_AJAX.md</filepath> | Requisitos técnicos del servidor | Para verificar compatibilidad |
| <filepath>FLUJO_TECNICO_AJAX.md</filepath> | Arquitectura técnica AJAX | Para entender el sistema |

### 🟢 Archivos de Configuración

| Archivo | Descripción | Acción Requerida |
|---------|-------------|-------------------|
| <filepath>.env.example</filepath> | Plantilla de configuración | Copiar a `.env` y editar |
| <filepath>INSTALACION_BD_COMPLETA.sql</filepath> | Script SQL consolidado | Importar en phpMyAdmin |
| <filepath>verificar_instalacion.php</filepath> | Script de verificación | Ejecutar post-deployment |

---

## 🎯 Proceso de Deployment en 3 Pasos

### Paso 1: Preparación Local (⏱️ 15 min)

```bash
# 1. Crear archivo de configuración
cp .env.example .env
# Editar .env con credenciales de cPanel

# 2. Instalar dependencias
composer install --no-dev --optimize-autoloader

# 3. Comprimir para subir
zip -r setap-deploy.zip . -x "*.git*" "tests/*" "storage/*.sqlite" "node_modules/*" "*"
```

### Paso 2: Configuración en cPanel (⏱️ 10 min)

1. **Crear Base de Datos**:
   - cPanel → MySQL® Databases
   - Crear BD: `bdsetap`
   - Crear usuario: `setap`
   - Asignar con ALL PRIVILEGES
   - **ANOTAR** nombres completos (incluyen prefijo)

2. **Subir Archivos**:
   - cPanel → File Manager → `public_html/setap/`
   - Upload → `setap-deploy.zip`
   - Extract → Verificar estructura

### Paso 3: Instalación y Verificación (⏱️ 10 min)

1. **Instalar Base de Datos**:
   - cPanel → phpMyAdmin
   - Import → `INSTALACION_BD_COMPLETA.sql`
   - Verificar ~20 tablas creadas

2. **Verificar Instalación**:
   - Subir `verificar_instalacion.php`
   - Acceder: `https://www.comercial-elroble.cl/setap/verificar_instalacion.php`
   - Verificar que todo esté ✅
   - **ELIMINAR** archivo de verificación

3. **Acceder a la Aplicación**:
   ```
   https://www.comercial-elroble.cl/setap/public
   ```

---

## ⚠️ Puntos Críticos

### 🔴 MUY IMPORTANTE

1. **Archivo .htaccess en public/**
   - ¿Dónde?: `/public_html/setap/public/.htaccess`
   - ¿Por qué?: Sin él, las rutas NO funcionarán
   - Activar "Show Hidden Files" en File Manager si no lo ves

2. **Prefijos de cPanel**
   - cPanel agrega prefijos automáticamente
   - Ejemplo: `bdsetap` → `comerci3_bdsetap`
   - Usar nombres COMPLETOS en `.env`

3. **APP_DEBUG en Producción**
   - DEBE ser `false` en producción
   - Expone información sensible si está en `true`

4. **Composer Dependencies**
   - Instalar ANTES de subir al servidor
   - Muchos servidores compartidos no tienen Composer

---

## 🔧 Troubleshooting Rápido

### Errores Comunes

| Error | Solución Rápida | Documentación |
|-------|-------------------|---------------|
| **Error 500** | Verificar `.env` existe y tiene credenciales correctas | <filepath>TROUBLESHOOTING.md</filepath> #error-500 |
| **Error 404 en rutas** | Verificar `public/.htaccess` existe | <filepath>TROUBLESHOOTING.md</filepath> #error-404 |
| **No conecta a BD** | Usar nombres COMPLETOS con prefijo en `.env` | <filepath>TROUBLESHOOTING.md</filepath> #error-db |
| **CSS/JS no cargan** | Verificar `APP_URL` en `.env` | <filepath>TROUBLESHOOTING.md</filepath> #assets |

**Ver más**: <filepath>TROUBLESHOOTING.md</filepath>

---

## ✅ Checklist de Verificación

Antes de considerar el deployment exitoso:

- [ ] URL accesible: `https://www.comercial-elroble.cl/setap/public`
- [ ] Página de inicio/login carga correctamente
- [ ] Sin errores 500 o 404
- [ ] Script `verificar_instalacion.php` pasa todas las pruebas
- [ ] Base de datos tiene ~20 tablas
- [ ] `.env` tiene `APP_DEBUG=false`
- [ ] Archivo `verificar_instalacion.php` fue ELIMINADO
- [ ] Permisos correctos: `.env` (600), `.htaccess` (644)

---

## 📊 Información del Sistema

### Stack Tecnológico
- **Servidor Web**: Apache 2.4
- **PHP**: 8.3
- **Base de Datos**: MySQL 8.0
- **Panel**: cPanel con phpMyAdmin 8.0

### Requisitos
- Apache con `mod_rewrite` habilitado
- PHP 8.3+ con extensiones: PDO, PDO_MySQL, JSON, MBString, OpenSSL, Session
- MySQL 8.0+
- Espacio: ~50 MB (aplicación) + ~5 MB (BD inicial)

### URLs
- **Aplicación**: https://www.comercial-elroble.cl/setap/public
- **cPanel**: https://www.comercial-elroble.cl:2083
- **phpMyAdmin**: Accesible desde cPanel

---

## 🚀 Post-Deployment

### Tareas Inmediatas
1. ✅ Crear usuario administrador inicial
2. ✅ Configurar SSL (Let's Encrypt gratuito en cPanel)
3. ✅ Eliminar archivos de verificación
4. ✅ Verificar `APP_DEBUG=false`

### Tareas Recomendadas
1. Configurar backups automáticos (cPanel Backup)
2. Configurar monitoreo de logs
3. Documentar credenciales en lugar seguro
4. Configurar envío de emails (opcional)

---

## 📞 Soporte

### Documentación
- **Deployment Completo**: <filepath>GUIA_DEPLOYMENT_PRODUCCION.md</filepath>
- **Troubleshooting**: <filepath>TROUBLESHOOTING.md</filepath>
- **Arquitectura AJAX**: <filepath>FLUJO_TECNICO_AJAX.md</filepath>

### Recursos Externos
- [Documentación cPanel](https://docs.cpanel.net/)
- [Documentación PHP 8.3](https://www.php.net/docs.php)
- [MySQL 8.0 Reference](https://dev.mysql.com/doc/)

---

## 📝 Notas de Versión

**Versión**: 1.0  
**Fecha**: 2025-10-22  
**Autor**: MiniMax Agent  
**Sistema**: SETAP - Sistema de Gestión  

---

## 🎉 ¡Listo para Empezar!

**Recomendación**: Si es tu primera vez, sigue estos pasos en orden:

1. 📊 Lee: <filepath>RESUMEN_EJECUTIVO.md</filepath> (10 min)
2. 📝 Imprime: <filepath>CHECKLIST_DEPLOYMENT.md</filepath>
3. 🚀 Ejecuta: <filepath>GUIA_RAPIDA_CPANEL.md</filepath> (35 min)
4. ✅ Verifica: Accede a la aplicación
5. 🎉 ¡Deployment completado!

**¿Problemas?** Consulta: <filepath>TROUBLESHOOTING.md</filepath>

---

**¡Buena suerte con tu deployment!** 🚀
