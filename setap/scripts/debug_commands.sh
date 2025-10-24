#!/bin/bash
# Script de debugging rápido para producción
# Uso: ./debug_commands.sh [comando]
# Ubicación: /ruta/a/tu/proyecto/scripts/debug_commands.sh

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
PROJECT_PATH="/var/www/html/comercial-elroble.cl"  # Ajustar según tu ruta
LOG_PATH="$PROJECT_PATH/logs"
APACHE_LOG="/var/log/apache2"
PHP_LOG="/var/log/php"

echo -e "${BLUE}=== HERRAMIENTAS DE DEBUGGING - COMERCIAL EL ROBLE ===${NC}"
echo "Fecha: $(date)"
echo "Servidor: $(hostname)"
echo ""

# Función para mostrar ayuda
show_help() {
    echo -e "${YELLOW}Comandos disponibles:${NC}"
    echo "  status      - Estado general del sistema"
    echo "  errors      - Ver últimos errores"
    echo "  apache      - Estado de Apache"
    echo "  php         - Información de PHP"
    echo "  memory      - Uso de memoria"
    echo "  database    - Estado de base de datos"
    echo "  logs        - Monitorear todos los logs"
    echo "  perf        - Análisis de rendimiento"
    echo "  clean       - Limpiar logs antiguos"
    echo "  full        - Diagnóstico completo"
    echo "  help        - Mostrar esta ayuda"
}

# Estado general del sistema
status_check() {
    echo -e "${BLUE}=== ESTADO GENERAL ===${NC}"
    
    echo -e "${YELLOW}Sistema:${NC}"
    uptime
    echo ""
    
    echo -e "${YELLOW}Disco:${NC}"
    df -h | grep -E '/dev/'
    echo ""
    
    echo -e "${YELLOW}Memoria:${NC}"
    free -h
    echo ""
    
    echo -e "${YELLOW}CPU:${NC}"
    top -bn1 | head -5
    echo ""
    
    echo -e "${YELLOW}Procesos Apache/PHP:${NC}"
    ps aux | grep -E '(apache2|php)' | head -10
}

# Ver errores
show_errors() {
    echo -e "${BLUE}=== ÚLTIMOS ERRORES ===${NC}"
    
    echo -e "${YELLOW}Errores PHP (últimos 10):${NC}"
    if [ -f "$PHP_LOG/error.log" ]; then
        tail -10 "$PHP_LOG/error.log" | while read line; do
            if [[ $line == *"ERROR"* ]] || [[ $line == *"Fatal error"* ]] || [[ $line == *"Parse error"* ]]; then
                echo -e "${RED}$line${NC}"
            else
                echo "$line"
            fi
        done
    else
        echo -e "${RED}No se encontró log de PHP${NC}"
    fi
    echo ""
    
    echo -e "${YELLOW}Errores de Apache (últimos 10):${NC}"
    if [ -f "$APACHE_LOG/error.log" ]; then
        tail -10 "$APACHE_LOG/error.log" | while read line; do
            if [[ $line == *"error"* ]] || [[ $line == *"ERROR"* ]]; then
                echo -e "${RED}$line${NC}"
            else
                echo "$line"
            fi
        done
    else
        echo -e "${RED}No se encontró log de Apache${NC}"
    fi
    echo ""
    
    echo -e "${YELLOW}Logs de aplicación:${NC}"
    if [ -f "$LOG_PATH/app.log" ]; then
        tail -10 "$LOG_PATH/app.log"
    else
        echo -e "${YELLOW}No se encontró log de aplicación${NC}"
    fi
}

# Estado de Apache
apache_status() {
    echo -e "${BLUE}=== ESTADO DE APACHE ===${NC}"
    
    echo -e "${YELLOW}Estado del servicio:${NC}"
    if systemctl is-active apache2 >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Apache2 está ejecutándose${NC}"
    else
        echo -e "${RED}❌ Apache2 NO está ejecutándose${NC}"
    fi
    echo ""
    
    echo -e "${YELLOW}Módulos cargados:${NC}"
    apache2ctl -M 2>/dev/null | grep -E '(rewrite|ssl|deflate|headers)'
    echo ""
    
    echo -e "${YELLOW}Configuración:${NC}"
    if apache2ctl configtest 2>/dev/null; then
        echo -e "${GREEN}✅ Configuración válida${NC}"
    else
        echo -e "${RED}❌ Error en configuración${NC}"
    fi
    echo ""
    
    echo -e "${YELLOW}Sitios configurados:${NC}"
    ls -la /etc/apache2/sites-enabled/
    echo ""
    
    echo -e "${YELLOW}Conexiones activas:${NC}"
    netstat -tuln | grep :80
    netstat -tuln | grep :443
}

# Información de PHP
php_info() {
    echo -e "${BLUE}=== INFORMACIÓN DE PHP ===${NC}"
    
    echo -e "${YELLOW}Versión:${NC}"
    php -v
    echo ""
    
    echo -e "${YELLOW}Extensiones cargadas:${NC}"
    php -m | grep -E '(pdo|curl|openssl|mbstring|gd|json|session)'
    echo ""
    
    echo -e "${YELLOW}Configuración crítica:${NC}"
    echo "memory_limit: $(php -r 'echo ini_get("memory_limit");')"
    echo "max_execution_time: $(php -r 'echo ini_get("max_execution_time");')"
    echo "display_errors: $(php -r 'echo ini_get("display_errors");')"
    echo "log_errors: $(php -r 'echo ini_get("log_errors");')"
    echo "error_log: $(php -r 'echo ini_get("error_log");')"
    echo ""
    
    echo -e "${YELLOW}Archivos de configuración:${NC}"
    php --ini | grep "Loaded Configuration File"
}

# Uso de memoria
memory_usage() {
    echo -e "${BLUE}=== USO DE MEMORIA ===${NC}"
    
    echo -e "${YELLOW}Memoria del sistema:${NC}"
    free -h
    echo ""
    
    echo -e "${YELLOW}Procesos PHP/Apache por uso de memoria:${NC}"
    ps aux --sort=-%mem | grep -E '(apache2|php)' | head -10
    echo ""
    
    echo -e "${YELLOW}Archivos más grandes en el proyecto:${NC}"
    find $PROJECT_PATH -type f -exec du -h {} + 2>/dev/null | sort -hr | head -10
}

# Estado de base de datos
database_status() {
    echo -e "${BLUE}=== ESTADO DE BASE DE DATOS ===${NC}"
    
    echo -e "${YELLOW}Estado de MySQL/MariaDB:${NC}"
    if systemctl is-active mysql >/dev/null 2>&1 || systemctl is-active mariadb >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Base de datos está ejecutándose${NC}"
    else
        echo -e "${RED}❌ Base de datos NO está ejecutándose${NC}"
        return
    fi
    echo ""
    
    echo -e "${YELLOW}Conexiones activas:${NC}"
    mysql -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null || echo "No se pudo conectar"
    mysql -e "SHOW STATUS LIKE 'Queries';" 2>/dev/null || echo "No se pudo obtener estadísticas"
    echo ""
    
    echo -e "${YELLOW}Tablas del proyecto:${NC}"
    mysql -e "SHOW TABLES FROM comercial_elroble;" 2>/dev/null || echo "No se pudo acceder a la base de datos"
}

# Monitorear logs
monitor_logs() {
    echo -e "${BLUE}=== MONITOREO DE LOGS ===${NC}"
    echo "Presiona Ctrl+C para salir del monitoreo"
    echo ""
    
    echo -e "${YELLOW}Monitoreando logs en tiempo real...${NC}"
    echo "------------------------------------------"
    
    # Monitorear múltiples logs
    tail -f "$APACHE_LOG/error.log" "$PHP_LOG/error.log" "$LOG_PATH/app.log" 2>/dev/null | \
    while read line; do
        timestamp=$(date '+%Y-%m-%d %H:%M:%S')
        if [[ $line == *"error"* ]] || [[ $line == *"ERROR"* ]] || [[ $line == *"Fatal"* ]]; then
            echo -e "${RED}[$timestamp] $line${NC}"
        else
            echo -e "${GREEN}[$timestamp] $line${NC}"
        fi
    done
}

# Análisis de rendimiento
performance_analysis() {
    echo -e "${BLUE}=== ANÁLISIS DE RENDIMIENTO ===${NC}"
    
    echo -e "${YELLOW}Load promedio:${NC}"
    uptime
    echo ""
    
    echo -e "${YELLOW}Top procesos por CPU:${NC}"
    ps aux --sort=-%cpu | head -10
    echo ""
    
    echo -e "${YELLOW}Top procesos por memoria:${NC}"
    ps aux --sort=-%mem | head -10
    echo ""
    
    echo -e "${YELLOW}I/O de disco:${NC}"
    if command -v iostat >/dev/null 2>&1; then
        iostat -x 1 1
    else
        echo "iostat no está disponible"
    fi
    echo ""
    
    echo -e "${YELLOW}Conexiones de red:${NC}"
    netstat -an | grep :80 | wc -l
    echo "Conexiones en puerto 80: $(netstat -an | grep :80 | wc -l)"
    echo "Conexiones en puerto 443: $(netstat -an | grep :443 | wc -l)"
}

# Limpiar logs
clean_logs() {
    echo -e "${BLUE}=== LIMPIEZA DE LOGS ===${NC}"
    
    echo -e "${YELLOW}Tamaño actual de logs:${NC}"
    du -sh "$LOG_PATH" 2>/dev/null || echo "Directorio de logs no encontrado"
    echo ""
    
    echo -e "${YELLOW}Archivos de log a limpiar (más de 7 días):${NC}"
    find "$LOG_PATH" -type f -mtime +7 -exec ls -lh {} \; 2>/dev/null
    echo ""
    
    read -p "¿Continuar con la limpieza? (y/N): " confirm
    if [[ $confirm == [yY] ]]; then
        find "$LOG_PATH" -type f -mtime +7 -delete 2>/dev/null
        echo -e "${GREEN}✅ Logs antiguos eliminados${NC}"
        
        # Limpiar también logs del sistema
        find /var/log -name "*.log" -type f -mtime +7 -exec ls -lh {} \; 2>/dev/null | head -5
    else
        echo "Limpieza cancelada"
    fi
}

# Diagnóstico completo
full_diagnostic() {
    echo -e "${BLUE}=== DIAGNÓSTICO COMPLETO ===${NC}"
    echo "Generando reporte completo..."
    echo ""
    
    # Ejecutar diagnóstico usando el script PHP
    if [ -f "$PROJECT_PATH/debug/production_debug_tool.php" ]; then
        php "$PROJECT_PATH/debug/production_debug_tool.php" > "$LOG_PATH/diagnostic_$(date +%Y%m%d_%H%M%S).txt"
        echo -e "${GREEN}✅ Diagnóstico guardado en logs/diagnostic_$(date +%Y%m%d_%H%M%S).txt${NC}"
        echo ""
        cat "$LOG_PATH/diagnostic_$(date +%Y%m%d_%H%M%S).txt"
    else
        echo -e "${YELLOW}Ejecutando diagnóstico básico...${NC}"
        status_check
        echo ""
        show_errors
        echo ""
        apache_status
        echo ""
        php_info
    fi
}

# Script principal
case "${1:-help}" in
    status)
        status_check
        ;;
    errors)
        show_errors
        ;;
    apache)
        apache_status
        ;;
    php)
        php_info
        ;;
    memory)
        memory_usage
        ;;
    database)
        database_status
        ;;
    logs)
        monitor_logs
        ;;
    perf)
        performance_analysis
        ;;
    clean)
        clean_logs
        ;;
    full)
        full_diagnostic
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo -e "${RED}Comando desconocido: $1${NC}"
        echo ""
        show_help
        ;;
esac