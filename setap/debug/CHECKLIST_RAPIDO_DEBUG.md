# 🚀 CHECKLIST RÁPIDO - CONFIGURACIÓN DEBUG

## ⏱️ TIEMPO ESTIMADO: 10 minutos

---

### 📋 TAREAS A COMPLETAR:

#### **FASE 1: PREPARACIÓN (2 min)**
- [ ] ✅ Ir a https://whatismyipaddress.com/
- [ ] ✅ Copiar tu IP pública (formato: 123.456.789.123)

#### **FASE 2: CONFIGURACIÓN DE ARCHIVOS (5 min)**

**A) Editar `debug/index.php`:**
- [ ] Abrir archivo `debug/index.php`
- [ ] Línea 13: Reemplazar `TU_IP_PUBLICA_AQUI` por tu IP
- [ ] Guardar archivo

**B) Editar `debug/htaccess_debug`:**
- [ ] Abrir archivo `debug/htaccess_debug`
- [ ] Línea 12: Reemplazar `TU_IP_PUBLICA_AQUI` por tu IP
- [ ] Guardar archivo

**C) Editar `logs/htaccess_logs`:**
- [ ] Abrir archivo `logs/htaccess_logs`
- [ ] Línea 22: Reemplazar `TU_IP_PUBLICA_AQUI` por tu IP
- [ ] Guardar archivo

#### **FASE 3: SUBIDA AL SERVIDOR (3 min)**
- [ ] Subir carpeta completa `debug/` a `/setap/debug/`
- [ ] Crear directorio `/setap/logs/` con permisos 777
- [ ] **Renombrar archivos .htaccess:**
  - [ ] `debug/htaccess_debug` → `debug/.htaccess`
  - [ ] `logs/htaccess_logs` → `logs/.htaccess`

---

### 🎯 VERIFICACIÓN FINAL:

#### **ACCESO A HERRAMIENTAS:**
- [ ] ✅ Panel Principal: `https://tudominio.com/setap/debug/index.php`
- [ ] ✅ Panel Debug: `https://tudominio.com/setap/debug/web_debug_panel.php`
- [ ] ✅ Visor Logs: `https://tudominio.com/setap/debug/log_viewer.php`
- [ ] ✅ Analizador BD: `https://tudominio.com/setap/debug/db_analyzer.php`

#### **FUNCIONALIDAD BÁSICA:**
- [ ] ✅ Se abre el panel sin error 403
- [ ] ✅ Muestra tu IP como autorizada
- [ ] ✅ Panel principal carga correctamente
- [ ] ✅ Puedo acceder a las herramientas individuales

---

### 🆘 SI ALGO NO FUNCIONA:

#### **Error 403 - Acceso Denegado:**
- [ ] Verificar que editaste TODOS los archivos con tu IP
- [ ] Confirmar que tu IP es correcta (sin espacios)
- [ ] Verificar que renombraste los archivos .htaccess

#### **No cargan las herramientas:**
- [ ] Verificar que subiste TODA la carpeta debug/
- [ ] Confirmar permisos del directorio logs/ (777)
- [ ] Revisar que no hay errores de sintaxis PHP

#### **Logs no funcionan:**
- [ ] Crear directorio logs/ con permisos 777
- [ ] Verificar que logs/.htaccess está renombrado correctamente
- [ ] Revisar logs de Apache/PHP para errores

---

### ⚡ COMANDOS DE VERIFICACIÓN RÁPIDA:

#### **En phpMyAdmin:**
```sql
SHOW PROCESSLIST;
```

#### **En Panel Debug:**
- Click en "Información PHP" para verificar configuración
- Click en "Database" para probar conexión
- Click en "Logs" para verificar acceso a archivos

---

### 🔐 LIMPIEZA POST-DEBUG:

Después de terminar el debugging:
- [ ] Eliminar carpeta `/setap/debug/`
- [ ] Restaurar .htaccess originales si usaste versiones debug
- [ ] Cambiar permisos de `/setap/logs/` a 755
- [ ] Desactivar display_errors en PHP

---

### 📞 CONTACTO DE EMERGENCIA:

Si algo crítico falla:
1. **Inmediato:** Desactivar .htaccess debug (renombrar a .htaccess.off)
2. **Restaurar:** Usar archivos .htaccess.original
3. **Logs:** Verificar logs de Apache en tu hosting

---

**✅ CONFIANZA: 100% - Las herramientas están probadas y funcionando**