# 🚀 Guía de Uso Rápido - Herramientas de Debugging

## 📁 Archivos Creados

He creado un conjunto completo de herramientas de debugging para tu proyecto:

### 📊 **Herramientas Web (Acceso desde navegador)**

1. **Panel Simple:** `debug/simple_debug_panel.php`
   - **URL:** `http://tu-dominio.com/debug/simple_debug_panel.php`
   - **Uso:** Panel web visual para monitoreo rápido
   - **Características:** Estado en tiempo real, logs, métricas

2. **Herramienta Completa:** `debug/production_debug_tool.php`
   - **URL:** `http://tu-dominio.com/debug/production_debug_tool.php`
   - **Uso:** Diagnóstico técnico completo
   - **Características:** Análisis profundo del sistema

### 🖥️ **Herramientas de Línea de Comandos**

3. **Scripts de Comandos:** `scripts/debug_commands.sh`
   - **Uso:** `./debug_commands.sh [comando]`
   - **Características:** Comandos rápidos desde terminal

4. **Documentación:** `debug/GUIA_DEBUG_PRODUCCION.md`
   - **Uso:** Referencia completa de todas las técnicas

---

## ⚡ **Uso Inmediato - Inicio Rápido**

### 🌐 **Opción 1: Panel Web (Más Fácil)**

```bash
# 1. Abrir en navegador:
http://tu-dominio.com/debug/simple_debug_panel.php

# 2. Ver estado en tiempo real
# 3. Hacer clic en "Ejecutar Diagnóstico Completo"
```

### 💻 **Opción 2: Línea de Comandos**

```bash
# Hacer ejecutable el script
chmod +x scripts/debug_commands.sh

# Ver estado general
./scripts/debug_commands.sh status

# Ver errores recientes
./scripts/debug_commands.sh errors

# Diagnóstico completo
./scripts/debug_commands.sh full
```

### 🔧 **Opción 3: PHP Directo**

```bash
# Ejecutar desde terminal
php debug/production_debug_tool.php

# Generar reporte técnico
php debug/production_debug_tool.php --report
```

---

## 🎯 **Casos de Uso Comunes**

### 🚨 **Cuando tienes Error 500**

```bash
# 1. Ver errores inmediatamente
./scripts/debug_commands.sh errors

# 2. Verificar Apache
./scripts/debug_commands.sh apache

# 3. Ejecutar diagnóstico completo
./scripts/debug_commands.sh full
```

### 🐌 **Cuando la página carga lento**

```bash
# 1. Verificar rendimiento
./scripts/debug_commands.sh perf

# 2. Ver uso de memoria
./scripts/debug_commands.sh memory

# 3. Monitorear logs en tiempo real
./scripts/debug_commands.sh logs
```

### 🗄️ **Problemas de Base de Datos**

```bash
# 1. Verificar conexión DB
./scripts/debug_commands.sh database

# 2. Ver todos los errores
./scripts/debug_commands.sh errors
```

### 💾 **Problemas de Memoria**

```bash
# 1. Analizar memoria
./scripts/debug_commands.sh memory

# 2. Limpiar logs antiguos
./scripts/debug_commands.sh clean
```

---

## 🛠️ **Configuración Inicial**

### 1. **Seguridad - Restringir Acceso**

Edita el archivo `debug/simple_debug_panel.php` y agrega tu IP:

```php
$allowedIPs = [
    '127.0.0.1',
    'localhost',
    'TU_IP_PUBLICA_AQUI'  // ← Agregar tu IP
];
```

### 2. **Permisos de Archivos**

```bash
# Dar permisos de ejecución
chmod +x scripts/debug_commands.sh

# Permisos de escritura para logs
chmod 755 debug/
chmod 755 logs/ 2>/dev/null || mkdir -p logs
```

### 3. **Configurar URLs de Acceso**

Para usar desde navegador, asegúrate que Apache permita acceso al directorio debug:

```apache
# En tu .htaccess o virtual host
<Directory "/ruta/a/tu/proyecto/debug">
    Require ip 127.0.0.1
    Require ip TU_IP_PUBLICA
</Directory>
```

---

## 📋 **Comandos de Referencia Rápida**

### **Scripts de Línea de Comandos**

```bash
./scripts/debug_commands.sh help          # Ver todos los comandos
./scripts/debug_commands.sh status        # Estado general
./scripts/debug_commands.sh errors        # Últimos errores
./scripts/debug_commands.sh apache        # Estado de Apache
./scripts/debug_commands.sh php           # Información PHP
./scripts/debug_commands.sh memory        # Uso de memoria
./scripts/debug_commands.sh database      # Estado DB
./scripts/debug_commands.sh logs          # Monitorear logs
./scripts/debug_commands.sh perf          # Análisis rendimiento
./scripts/debug_commands.sh clean         # Limpiar logs
./scripts/debug_commands.sh full          # Diagnóstico completo
```

### **PHP Scripts**

```bash
# Herramienta completa
php debug/production_debug_tool.php

# Generar reporte técnico
php debug/production_debug_tool.php --report

# Desde navegador
http://tu-dominio.com/debug/simple_debug_panel.php
http://tu-dominio.com/debug/production_debug_tool.php
```

---

## 🔍 **Interpretación de Resultados**

### **Estados de Color**

- 🟢 **Verde (✅):** Todo funcionando correctamente
- 🟡 **Amarillo (⚠️):** Advertencia - Revisar
- 🔴 **Rojo (❌):** Error - Requiere atención

### **Métricas Importantes**

- **Memoria:** Menos del 70% = OK, 70-85% = Advertencia, +85% = Crítico
- **Tiempo de respuesta:** Menos de 2s = OK, 2-5s = Lento, +5s = Crítico
- **Errores:** Revisar logs si aparecen errores frecuentemente

---

## 🚨 **Emergencias - Problemas Críticos**

### **Si el sitio está completamente caído:**

```bash
# 1. Verificar estado de servicios
./scripts/debug_commands.sh status

# 2. Reiniciar Apache si es necesario
sudo systemctl restart apache2

# 3. Ver errores inmediatamente
./scripts/debug_commands.sh errors

# 4. Verificar logs en tiempo real
./scripts/debug_commands.sh logs
```

### **Si hay error de base de datos:**

```bash
# 1. Verificar MySQL
sudo systemctl status mysql

# 2. Reiniciar si es necesario
sudo systemctl restart mysql

# 3. Verificar conexión
./scripts/debug_commands.sh database
```

---

## 💡 **Consejos Adicionales**

### **1. Prevención**
- Ejecuta `./scripts/debug_commands.sh full` semanalmente
- Revisa el panel web diariamente
- Configura alertas automáticas

### **2. Mantenimiento**
- Limpia logs mensualmente: `./scripts/debug_commands.sh clean`
- Monitorea el uso de disco
- Actualiza PHP y extensiones regularmente

### **3. Seguridad**
- Solo accede desde IPs autorizadas
- No mantengas las herramientas de debug en producción indefinidamente
- Usa HTTPS para acceso al panel

---

## 📞 **¿Necesitas Ayuda?**

Si encuentras problemas específicos:

1. **Ejecuta el diagnóstico completo:** `./scripts/debug_commands.sh full`
2. **Revisa la documentación:** `debug/GUIA_DEBUG_PRODUCCION.md`
3. **Verifica los logs:** `./scripts/debug_commands.sh errors`

¡Con estas herramientas podrás identificar y resolver cualquier problema en producción de manera eficiente!