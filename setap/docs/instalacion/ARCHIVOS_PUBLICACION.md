# Archivos y Carpetas para Publicación en Producción

**Servidor:** comercial-elroble.cl/setap
**Fecha:** 2025-10-23

---

## 📦 Contenido del archivo setap-deploy.zip

### Estructura exacta que debe ir a producción:

```
setap-deploy.zip
├── public/
├── src/
├── storage/           (vacía)
├── vendor/            (opcional según Opción A o B)
├── composer.json
└── composer.lock
```

---

## ✅ Carpetas que se publican

### **public/**
- **Descripción:** Punto de entrada de la aplicación
- **Contiene:** index.php, assets, .htaccess
- **Obligatorio:** ✅ SÍ

### **src/**
- **Descripción:** Código fuente completo de la aplicación
- **Contiene:** Controladores, modelos, vistas, configuración
- **Obligatorio:** ✅ SÍ

### **storage/**
- **Descripción:** Directorio para logs, cache y archivos temporales
- **Contiene:** Se sube vacía, se llena en ejecución
- **Obligatorio:** ✅ SÍ
- **Nota:** Debe tener permisos 755 o 777

### **vendor/** (Opcional)
- **Descripción:** Librerías de terceros instaladas por Composer
- **Obligatorio:** ⚠️ DEPENDE
  - **Opción A:** Incluir vendor/ completo en el .zip (recomendado para cPanel)
  - **Opción B:** Excluir vendor/ y ejecutar `composer install` en el servidor

---

## ✅ Archivos que se publican

### **composer.json**
- **Descripción:** Define las dependencias del proyecto
- **Obligatorio:** ✅ SÍ
- **Uso:** Necesario para instalar dependencias con Composer

### **composer.lock**
- **Descripción:** Versiones exactas de las librerías
- **Obligatorio:** ✅ SÍ
- **Uso:** Asegura que se instalen las mismas versiones en todos los entornos

---

## ❌ Carpetas que NO se publican

```
❌ .vscode/              (Configuración del editor)
❌ BdScript/             (Scripts SQL - se ejecutan aparte)
❌ Definicion_Negocio/   (Documentación de desarrollo)
❌ docs/                 (Documentación - no necesaria en servidor)
❌ tests/                (Tests unitarios)
❌                  (Entorno virtual Python)
```

---

## ❌ Archivos que NO se publican

```
❌ .env                  (Se crea NUEVO en el servidor)
❌ .env_p                (Backup local)
❌ .gitignore            (Solo para control de versiones)
❌ composer.phar         (Ejecutable local de Composer)
❌ *.ps1                 (Scripts PowerShell de desarrollo)
❌ detailed_errors.php   (Herramienta de debug)
❌ error_analyzer.php    (Herramienta de debug)
❌ phpunit.xml           (Configuración de tests)
❌ requirements.txt      (Dependencias Python)
❌ run_tests.php         (Tests)
❌ test_report.php       (Tests)
❌ verificar_vscode.ps1  (Script de desarrollo)
```

---

## 📋 Proceso de creación del .zip

### PowerShell (Windows):

```powershell
# Opción 1: Crear .zip con todo y limpiar manualmente
cd C:\ruta\a\comercial-elroblev2\setap
Compress-Archive -Path public,src,storage,vendor,composer.json,composer.lock -DestinationPath ..\setap-deploy.zip -Force
```

### Bash/Linux:

```bash
# Crear .zip solo con los archivos necesarios
zip -r setap-deploy.zip public/ src/ storage/ vendor/ composer.json composer.lock
```

---

## ⚠️ Notas importantes

1. **La carpeta storage/ debe subirse vacía** pero con permisos de escritura (755 o 777)
2. **El archivo .env NO se incluye** - se creará nuevo en el servidor con las credenciales de producción
3. **BdScript/ NO se sube** - los scripts SQL se ejecutan una sola vez desde phpMyAdmin
4. **docs/ NO se necesita en producción** - es solo documentación de instalación
5. **vendor/ es la carpeta más grande** (~10-40 MB) - evalúa si incluirla según tu conexión

---

## 🎯 Tamaño estimado

- **Sin vendor/:** ~2-5 MB
- **Con vendor/:** ~15-50 MB

**Recomendación:** Incluir vendor/ en el .zip si no tienes acceso SSH al servidor.

---

## ✅ Checklist de validación

Antes de crear el .zip, verifica:

- [ ] Carpeta `public/` existe y contiene `index.php`
- [ ] Carpeta `src/` existe y contiene toda la aplicación
- [ ] Carpeta `storage/` existe (puede estar vacía)
- [ ] Archivo `composer.json` existe
- [ ] Archivo `composer.lock` existe
- [ ] Si incluyes `vendor/`, verifica que exista la carpeta `vendor/autoload.php`
- [ ] NO incluiste archivo `.env`
- [ ] NO incluiste carpetas de desarrollo (tests, venv, .vscode)

---

**Fecha de última actualización:** 2025-10-23  
**Autor:** MiniMax Agent
