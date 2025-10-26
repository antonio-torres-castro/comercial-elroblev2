<?php

namespace App\Helpers\Security;

use App\Helpers\Logger;

/**
 * Validador de seguridad especializado
 * Responsabilidad única: Validaciones de seguridad
 */
class SecurityValidator
{
    /**
     * Sanitizar entrada de datos
     */
    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validar RUT chileno
     */
    public static function validateRut(string $rut): bool
    {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 2) {
            return false;
        }

        $dv = strtoupper(substr($rut, -1));
        $numero = substr($rut, 0, -1);

        $suma = 0;
        $multiplicador = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += $numero[$i] * $multiplicador;
            $multiplicador = $multiplicador == 7 ? 2 : $multiplicador + 1;
        }

        $resto = $suma % 11;
        $dvCalculado = $resto == 0 ? '0' : ($resto == 1 ? 'K' : (string)(11 - $resto));

        return $dv === $dvCalculado;
    }

    /**
     * Validar email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validar fortaleza de contraseña
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una mayúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial';
        }

        return $errors;
    }

    /**
     * Validar URL
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validar número de teléfono
     */
    public static function validatePhone(string $phone): bool
    {
        // Acepta formatos como +56912345678, 912345678, +56 9 1234 5678
        $pattern = '/^[\+]?[0-9\s\-\(\)]{8,15}$/';
        return preg_match($pattern, $phone);
    }

    /**
     * Validar IP
     */
    public static function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validar formato de fecha
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validar que un string contenga solo letras
     */
    public static function validateAlpha(string $input): bool
    {
        return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $input);
    }

    /**
     * Validar que un string contenga solo números
     */
    public static function validateNumeric(string $input): bool
    {
        return is_numeric($input);
    }

    /**
     * Validar que un string contenga solo letras y números
     */
    public static function validateAlphaNumeric(string $input): bool
    {
        return preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/', $input);
    }

    /**
     * Validar longitud mínima
     */
    public static function validateMinLength(string $input, int $minLength): bool
    {
        return strlen($input) >= $minLength;
    }

    /**
     * Validar longitud máxima
     */
    public static function validateMaxLength(string $input, int $maxLength): bool
    {
        return strlen($input) <= $maxLength;
    }

    /**
     * Validar que un valor esté en una lista permitida
     */
    public static function validateInArray($value, array $allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }

    /**
     * Validar archivo subido
     */
    public static function validateFile(array $file, array $options = []): array
    {
        $errors = [];

        // Verificar que no haya errores en la subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error en la subida del archivo';
            return $errors;
        }

        // Validar tamaño máximo
        $maxSize = $options['max_size'] ?? 2097152; // 2MB por defecto
        if ($file['size'] > $maxSize) {
            $errors[] = 'El archivo excede el tamaño máximo permitido';
        }

        // Validar tipos MIME permitidos
        if (isset($options['allowed_types'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $options['allowed_types'])) {
                $errors[] = 'Tipo de archivo no permitido';
            }
        }

        // Validar extensiones permitidas
        if (isset($options['allowed_extensions'])) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $options['allowed_extensions'])) {
                $errors[] = 'Extensión de archivo no permitida';
            }
        }

        return $errors;
    }

    /**
     * Detectar posibles ataques XSS
     */
    public static function detectXss(string $input): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detectar posibles ataques SQL injection
     */
    public static function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";]/',
            '/--\s*/',
            '/\/\*.*?\*\//',
            '/\bexec\b|\bexecute\b/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generar password seguro
     */
    public static function generateSecurePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }

    /**
     * Generar token aleatorio
     */
    public static function generateRandomToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validar token de formato hexadecimal
     */
    public static function validateHexToken(string $token, int $expectedLength = 64): bool
    {
        return preg_match('/^[a-f0-9]{' . $expectedLength . '}$/', $token);
    }

    /**
     * Configurar headers de seguridad
     */
    public static function setSecurityHeaders(): void
    {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self' https://cdn.jsdelivr.net;");

        // HSTS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

        // X-Frame-Options
        header('X-Frame-Options: DENY');

        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');

        // X-XSS-Protection
        header('X-XSS-Protection: 1; mode=block');

        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Forzar HTTPS
     */
    public static function requireHttps(): void
    {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirectURL");
            exit;
        }
    }

    /**
     * Registrar evento de seguridad
     */
    public static function logSecurityEvent(string $event, array $data = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'data' => $data
        ];

        Logger::error("SECURITY_EVENT: " . json_encode($logData));
    }
}
