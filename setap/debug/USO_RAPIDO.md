# 🚀 Instrucciones de Uso Rápido - Debug Web-Only


**Fecha:** 2025-10-24

## ⚡ Setup Rápido (5 minutos)

### 1. **Obtener tu IP Pública**
```
https://whatismyipaddress.com/
```
Anota tu IP (ej: 192.168.1.100)

### 2. **Editar Archivos de Configuración**
Edita estos archivos y cambia `TU_IP_PUBLICA_AQUI` por tu IP real:

- ✅ `debug/web_debug_panel.php` (línea ~14)
- ✅ `debug/web_debug_actions.php` (línea ~14)
- ✅ `debug/log_viewer.php` (línea ~14)
- ✅ `debug/db_analyzer.php` (línea ~14)

### 3. **Subir Archivos via Administrador de Archivos**
Sube toda la carpeta `debug/` a `/setap/debug/`

### 4. **Crear Directorio de Logs**
Crear: `/setap/logs/`

### 5. **¡Listo!** 🎉
Accede a: `https://tudominio.com/setap/debug/web_debug_panel.php`

---

## 🔗 URLs Principales

| Herramienta | URL | Función |
|-------------|-----|---------|
| **Panel Principal** | `/setap/debug/web_debug_panel.php` | Dashboard general |
| **Visor de Logs** | `/setap/debug/log_viewer.php` | Leer logs |
| **Analizador BD** | `/setap/debug/db_analyzer.php` | Base de datos |
| **phpMyAdmin** | (URL de tu hosting) | Administración BD |

---

## 🆘 Checklist de Emergencia

### Cuando algo no funciona:

1. **🔍 Ve al Panel Principal** → Revisa errores en Dashboard
2. **📝 Revisa Logs** → Ve a Visor de Logs → Errores PHP
3. **🗄️ Verifica BD** → Analizador BD → Pestaña "Salud"
4. **🔧 Diagnóstico** → Herramientas → "Ejecutar Diagnóstico Completo"

### Problemas comunes:

- **"Acceso Denegado"** → ❌ IP no configurada
- **"Error BD"** → ❌ Credenciales incorrectas
- **"No hay logs"** → ❌ PHP sin error_log configurado

---

## 🛡️ Seguridad

### ✅ Configurado automáticamente:
- Solo tu IP puede acceder
- Solo consultas SELECT permitidas
- Archivos sensibles bloqueados
- Headers de seguridad activos

### ⚠️ Recordatorios:
- Cambia IP antes de subir
- Elimina herramientas después del debugging
- No compartas las URLs

---

## 📋 Flujo de Debug Recomendado

```
1. Problema detectado
   ↓
2. Panel Principal → Dashboard
   ↓
3. Visor de Logs → Buscar errores
   ↓
4. Analizador BD → Verificar datos
   ↓
5. phpMyAdmin → Cambios específicos
   ↓
6. Solución implementada
```

**¡Listo para debuggear en producción sin consola!** 🎯