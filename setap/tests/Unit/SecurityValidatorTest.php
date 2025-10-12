<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\Security\SecurityValidator;

class SecurityValidatorTest extends TestCase
{
    public function testSanitizeInput()
    {
        $input = '<script>alert("xss")</script>Test';
        $result = SecurityValidator::sanitizeInput($input);
        
        $this->assertIsString($result, 'sanitizeInput debe retornar string');
        $this->assertStringNotContainsString('<script>', $result, 'sanitizeInput debe remover tags script');
        $this->assertStringContainsString('Test', $result, 'sanitizeInput debe preservar contenido limpio');
    }

    public function testValidateRutWithValidRut()
    {
        // RUT válido conocido
        $validRuts = ['12345678-5', '11111111-1', '22222222-2'];
        
        foreach ($validRuts as $rut) {
            $result = SecurityValidator::validateRut($rut);
            $this->assertIsBool($result, 'validateRut debe retornar boolean');
        }
    }

    public function testValidateRutWithInvalidRut()
    {
        $invalidRuts = ['12345678-9', 'invalid-rut', '123', ''];
        
        foreach ($invalidRuts as $rut) {
            $result = SecurityValidator::validateRut($rut);
            $this->assertFalse($result, "validateRut debe retornar false para RUT inválido: $rut");
        }
    }

    public function testValidateEmail()
    {
        $validEmails = ['test@example.com', 'user.name@domain.org', 'admin@test.cl'];
        $invalidEmails = ['invalid-email', '@domain.com', 'test@', 'test..email@domain.com'];
        
        foreach ($validEmails as $email) {
            $result = SecurityValidator::validateEmail($email);
            $this->assertTrue($result, "validateEmail debe retornar true para email válido: $email");
        }
        
        foreach ($invalidEmails as $email) {
            $result = SecurityValidator::validateEmail($email);
            $this->assertFalse($result, "validateEmail debe retornar false para email inválido: $email");
        }
    }

    public function testValidatePasswordStrength()
    {
        $weakPassword = '123';
        $strongPassword = 'MyStrongP@ssw0rd123';
        
        $weakResult = SecurityValidator::validatePasswordStrength($weakPassword);
        $strongResult = SecurityValidator::validatePasswordStrength($strongPassword);
        
        $this->assertIsArray($weakResult, 'validatePasswordStrength debe retornar array');
        $this->assertIsArray($strongResult, 'validatePasswordStrength debe retornar array');
        
        $this->assertArrayHasKey('valid', $weakResult, 'Resultado debe tener clave "valid"');
        $this->assertArrayHasKey('valid', $strongResult, 'Resultado debe tener clave "valid"');
        
        $this->assertIsBool($weakResult['valid'], 'La clave "valid" debe ser boolean');
        $this->assertIsBool($strongResult['valid'], 'La clave "valid" debe ser boolean');
    }

    public function testValidateUrl()
    {
        $validUrls = ['https://example.com', 'http://test.org', 'https://domain.cl/path'];
        $invalidUrls = ['not-a-url', 'ftp://invalid', '', 'http://'];
        
        foreach ($validUrls as $url) {
            $result = SecurityValidator::validateUrl($url);
            $this->assertTrue($result, "validateUrl debe retornar true para URL válida: $url");
        }
        
        foreach ($invalidUrls as $url) {
            $result = SecurityValidator::validateUrl($url);
            $this->assertFalse($result, "validateUrl debe retornar false para URL inválida: $url");
        }
    }

    public function testValidatePhone()
    {
        $validPhones = ['+56912345678', '912345678', '+1234567890'];
        $invalidPhones = ['123', 'not-a-phone', '', 'abc123'];
        
        foreach ($validPhones as $phone) {
            $result = SecurityValidator::validatePhone($phone);
            $this->assertIsBool($result, "validatePhone debe retornar boolean para: $phone");
        }
        
        foreach ($invalidPhones as $phone) {
            $result = SecurityValidator::validatePhone($phone);
            $this->assertFalse($result, "validatePhone debe retornar false para teléfono inválido: $phone");
        }
    }

    public function testValidateIp()
    {
        $validIps = ['192.168.1.1', '127.0.0.1', '255.255.255.255'];
        $invalidIps = ['999.999.999.999', 'not-an-ip', '', '192.168.1'];
        
        foreach ($validIps as $ip) {
            $result = SecurityValidator::validateIp($ip);
            $this->assertTrue($result, "validateIp debe retornar true para IP válida: $ip");
        }
        
        foreach ($invalidIps as $ip) {
            $result = SecurityValidator::validateIp($ip);
            $this->assertFalse($result, "validateIp debe retornar false para IP inválida: $ip");
        }
    }

    public function testValidateDate()
    {
        $validDates = ['2025-01-01', '2024-12-31', '2023-06-15'];
        $invalidDates = ['2025-13-01', '2025-01-32', 'not-a-date', ''];
        
        foreach ($validDates as $date) {
            $result = SecurityValidator::validateDate($date);
            $this->assertTrue($result, "validateDate debe retornar true para fecha válida: $date");
        }
        
        foreach ($invalidDates as $date) {
            $result = SecurityValidator::validateDate($date);
            $this->assertFalse($result, "validateDate debe retornar false para fecha inválida: $date");
        }
    }

    public function testValidateAlpha()
    {
        $validAlpha = ['abc', 'ABC', 'Test'];
        $invalidAlpha = ['abc123', '123', 'test@domain', ''];
        
        foreach ($validAlpha as $input) {
            $result = SecurityValidator::validateAlpha($input);
            $this->assertTrue($result, "validateAlpha debe retornar true para entrada alfabética: $input");
        }
        
        foreach ($invalidAlpha as $input) {
            $result = SecurityValidator::validateAlpha($input);
            $this->assertFalse($result, "validateAlpha debe retornar false para entrada no alfabética: $input");
        }
    }

    public function testValidateNumeric()
    {
        $validNumeric = ['123', '0', '999'];
        $invalidNumeric = ['abc', '123abc', 'test', ''];
        
        foreach ($validNumeric as $input) {
            $result = SecurityValidator::validateNumeric($input);
            $this->assertTrue($result, "validateNumeric debe retornar true para entrada numérica: $input");
        }
        
        foreach ($invalidNumeric as $input) {
            $result = SecurityValidator::validateNumeric($input);
            $this->assertFalse($result, "validateNumeric debe retornar false para entrada no numérica: $input");
        }
    }

    public function testValidateAlphaNumeric()
    {
        $validAlphaNumeric = ['abc123', 'Test123', 'USER1'];
        $invalidAlphaNumeric = ['test@domain', 'test!', '', '***'];
        
        foreach ($validAlphaNumeric as $input) {
            $result = SecurityValidator::validateAlphaNumeric($input);
            $this->assertTrue($result, "validateAlphaNumeric debe retornar true para entrada alfanumérica: $input");
        }
        
        foreach ($invalidAlphaNumeric as $input) {
            $result = SecurityValidator::validateAlphaNumeric($input);
            $this->assertFalse($result, "validateAlphaNumeric debe retornar false para entrada no alfanumérica: $input");
        }
    }

    public function testValidateMinLength()
    {
        $input = 'test';
        
        $result1 = SecurityValidator::validateMinLength($input, 3);
        $this->assertTrue($result1, 'validateMinLength debe retornar true cuando cumple mínimo');
        
        $result2 = SecurityValidator::validateMinLength($input, 5);
        $this->assertFalse($result2, 'validateMinLength debe retornar false cuando no cumple mínimo');
    }

    public function testValidateMaxLength()
    {
        $input = 'test';
        
        $result1 = SecurityValidator::validateMaxLength($input, 5);
        $this->assertTrue($result1, 'validateMaxLength debe retornar true cuando cumple máximo');
        
        $result2 = SecurityValidator::validateMaxLength($input, 3);
        $this->assertFalse($result2, 'validateMaxLength debe retornar false cuando excede máximo');
    }

    public function testValidateInArray()
    {
        $allowedValues = ['admin', 'user', 'guest'];
        
        $result1 = SecurityValidator::validateInArray('admin', $allowedValues);
        $this->assertTrue($result1, 'validateInArray debe retornar true para valor permitido');
        
        $result2 = SecurityValidator::validateInArray('invalid', $allowedValues);
        $this->assertFalse($result2, 'validateInArray debe retornar false para valor no permitido');
    }

    public function testDetectXss()
    {
        $cleanInput = 'This is clean text';
        $xssInput = '<script>alert("xss")</script>';
        
        $result1 = SecurityValidator::detectXss($cleanInput);
        $this->assertFalse($result1, 'detectXss debe retornar false para texto limpio');
        
        $result2 = SecurityValidator::detectXss($xssInput);
        $this->assertTrue($result2, 'detectXss debe retornar true para contenido XSS');
    }

    public function testDetectSqlInjection()
    {
        $cleanInput = 'normal user input';
        $sqlInput = "'; DROP TABLE users; --";
        
        $result1 = SecurityValidator::detectSqlInjection($cleanInput);
        $this->assertFalse($result1, 'detectSqlInjection debe retornar false para texto limpio');
        
        $result2 = SecurityValidator::detectSqlInjection($sqlInput);
        $this->assertTrue($result2, 'detectSqlInjection debe retornar true para contenido SQL malicioso');
    }

    public function testGenerateSecurePassword()
    {
        $password = SecurityValidator::generateSecurePassword();
        
        $this->assertIsString($password, 'generateSecurePassword debe retornar string');
        $this->assertEquals(12, strlen($password), 'Contraseña debe tener longitud por defecto de 12');
        
        $password16 = SecurityValidator::generateSecurePassword(16);
        $this->assertEquals(16, strlen($password16), 'Contraseña debe tener longitud especificada');
    }

    public function testGenerateRandomToken()
    {
        $token = SecurityValidator::generateRandomToken();
        
        $this->assertIsString($token, 'generateRandomToken debe retornar string');
        $this->assertEquals(64, strlen($token), 'Token debe tener longitud por defecto de 64 caracteres hex');
        
        $token16 = SecurityValidator::generateRandomToken(16);
        $this->assertEquals(32, strlen($token16), 'Token de 16 bytes debe tener 32 caracteres hex');
    }

    public function testValidateHexToken()
    {
        $validToken = str_repeat('a', 64); // Token hex válido de 64 caracteres
        $invalidToken = 'not-hex-token';
        
        $result1 = SecurityValidator::validateHexToken($validToken);
        $this->assertTrue($result1, 'validateHexToken debe retornar true para token hex válido');
        
        $result2 = SecurityValidator::validateHexToken($invalidToken);
        $this->assertFalse($result2, 'validateHexToken debe retornar false para token inválido');
    }

    public function testValidateFileStructure()
    {
        // Test básico de estructura sin archivo real
        $fakeFile = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 1024,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK
        ];
        
        $result = SecurityValidator::validateFile($fakeFile);
        $this->assertIsArray($result, 'validateFile debe retornar array');
        $this->assertArrayHasKey('valid', $result, 'Resultado debe tener clave "valid"');
        $this->assertIsBool($result['valid'], 'La clave "valid" debe ser boolean');
    }
}
