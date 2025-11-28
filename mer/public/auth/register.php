<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/auth_functions.php';
require_once __DIR__ . '/../../src/functions.php';

init_secure_session();

// Redirigir si ya está autenticado
if (isLoggedIn()) {
    $redirect = $_SESSION['redirect_after_login'] ?? '/mer/public/';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

$errors = [];
$success = '';
$data = [
    'first_name' => $_POST['first_name'] ?? '',
    'last_name' => $_POST['last_name'] ?? '',
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? '',
];

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['first_name'] = trim($_POST['first_name'] ?? '');
    $data['last_name'] = trim($_POST['last_name'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validaciones
    if (empty($data['first_name'])) {
        $errors[] = 'El nombre es requerido';
    } elseif (strlen($data['first_name']) < 2) {
        $errors[] = 'El nombre debe tener al menos 2 caracteres';
    }
    
    if (empty($data['last_name'])) {
        $errors[] = 'El apellido es requerido';
    } elseif (strlen($data['last_name']) < 2) {
        $errors[] = 'El apellido debe tener al menos 2 caracteres';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'El email es requerido';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Formato de email inválido';
    }
    
    if (empty($password)) {
        $errors[] = 'La contraseña es requerida';
    } elseif (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors[] = 'La contraseña debe incluir al menos una mayúscula, una minúscula y un número';
    }
    
    if (empty($password_confirm)) {
        $errors[] = 'Confirma tu contraseña';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    if (empty($errors)) {
        $result = registerUser($data['email'], $password, $data['first_name'], $data['last_name'], $data['phone']);
        
        if ($result['success']) {
            $success = 'Cuenta creada exitosamente. Revisa tu email para verificar tu cuenta.';
            $data = []; // Limpiar formulario
        } else {
            $errors[] = $result['message'];
        }
    }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registrarse - Mall Virtual</title>
<link rel="stylesheet" href="assets/css/modern.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --auth-primary: #3B82F6;
  --auth-primary-dark: #2563EB;
  --auth-secondary: #64748B;
  --auth-success: #10B981;
  --auth-danger: #EF4444;
  --auth-warning: #F59E0B;
  --auth-background: #F8FAFC;
  --auth-card: #FFFFFF;
  --auth-text: #1E293B;
  --auth-text-muted: #64748B;
  --auth-border: #E2E8F0;
  --auth-input-bg: #FFFFFF;
  --auth-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --auth-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(135deg, var(--auth-background) 0%, #E0F2FE 100%);
  min-height: 100vh;
  color: var(--auth-text);
  line-height: 1.6;
}

.auth-container {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.auth-card {
  background: var(--auth-card);
  border-radius: 1rem;
  box-shadow: var(--auth-shadow-lg);
  width: 100%;
  max-width: 450px;
  overflow: hidden;
  max-height: 90vh;
  overflow-y: auto;
}

.auth-header {
  background: linear-gradient(135deg, var(--auth-primary) 0%, var(--auth-primary-dark) 100%);
  color: white;
  padding: 2rem;
  text-align: center;
  position: sticky;
  top: 0;
  z-index: 10;
}

.auth-header h1 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.auth-header p {
  opacity: 0.9;
  font-size: 0.875rem;
}

.auth-form {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--auth-text);
  font-size: 0.875rem;
}

.form-input,
.form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--auth-border);
  border-radius: 0.5rem;
  font-size: 1rem;
  background: var(--auth-input-bg);
  transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--auth-primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.error {
  border-color: var(--auth-danger);
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.auth-btn {
  width: 100%;
  padding: 0.75rem 1.5rem;
  background: var(--auth-primary);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.auth-btn:hover {
  background: var(--auth-primary-dark);
  transform: translateY(-1px);
}

.auth-btn:active {
  transform: translateY(0);
}

.auth-links {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 0.875rem;
}

.auth-links a {
  color: var(--auth-primary);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s ease;
}

.auth-links a:hover {
  color: var(--auth-primary-dark);
}

.password-toggle {
  position: relative;
}

.password-toggle-btn {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--auth-text-muted);
  cursor: pointer;
  font-size: 0.875rem;
}

.password-toggle-btn:hover {
  color: var(--auth-text);
}

.password-strength {
  margin-top: 0.5rem;
  font-size: 0.75rem;
}

.strength-weak { color: var(--auth-danger); }
.strength-medium { color: var(--auth-warning); }
.strength-strong { color: var(--auth-success); }

.terms-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin: 1rem 0;
}

.terms-checkbox input[type="checkbox"] {
  margin-top: 0.125rem;
  width: 1rem;
  height: 1rem;
}

.terms-checkbox label {
  font-size: 0.875rem;
  color: var(--auth-text-muted);
  line-height: 1.4;
}

.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.alert-error {
  background: #FEF2F2;
  color: #991B1B;
  border: 1px solid #FECACA;
}

.alert-success {
  background: #F0FDF4;
  color: #166534;
  border: 1px solid #BBF7D0;
}

@media (max-width: 480px) {
  .auth-container {
    padding: 0.5rem;
  }
  
  .auth-header,
  .auth-form {
    padding: 1.5rem;
  }
  
  .auth-header h1 {
    font-size: 1.5rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h1>Crear Cuenta</h1>
      <p>Únete al Mall Virtual</p>
    </div>
    
    <form class="auth-form" method="POST" action="">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $error): ?>
            <div><?= htmlspecialchars($error) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="first_name">Nombre</label>
          <input 
            type="text" 
            id="first_name" 
            name="first_name" 
            class="form-input" 
            value="<?= htmlspecialchars($data['first_name']) ?>"
            required
            placeholder="Juan"
          >
        </div>
        
        <div class="form-group">
          <label class="form-label" for="last_name">Apellido</label>
          <input 
            type="text" 
            id="last_name" 
            name="last_name" 
            class="form-input" 
            value="<?= htmlspecialchars($data['last_name']) ?>"
            required
            placeholder="Pérez"
          >
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          class="form-input" 
          value="<?= htmlspecialchars($data['email']) ?>"
          required
          placeholder="juan@email.com"
        >
      </div>
      
      <div class="form-group">
        <label class="form-label" for="phone">Teléfono (opcional)</label>
        <input 
          type="tel" 
          id="phone" 
          name="phone" 
          class="form-input" 
          value="<?= htmlspecialchars($data['phone']) ?>"
          placeholder="+56 9 1234 5678"
        >
      </div>
      
      <div class="form-group password-toggle">
        <label class="form-label" for="password">Contraseña</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="form-input" 
          required
          placeholder="Mínimo 8 caracteres"
          oninput="checkPasswordStrength(this.value)"
        >
        <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
          Mostrar
        </button>
        <div class="password-strength" id="passwordStrength"></div>
      </div>
      
      <div class="form-group password-toggle">
        <label class="form-label" for="password_confirm">Confirmar Contraseña</label>
        <input 
          type="password" 
          id="password_confirm" 
          name="password_confirm" 
          class="form-input" 
          required
          placeholder="Confirma tu contraseña"
          oninput="checkPasswordMatch()"
        >
        <button type="button" class="password-toggle-btn" onclick="togglePassword('password_confirm')">
          Mostrar
        </button>
        <div class="password-strength" id="passwordMatch"></div>
      </div>
      
      <div class="terms-checkbox">
        <input type="checkbox" id="terms" name="terms" required>
        <label for="terms">
          Acepto los <a href="#" target="_blank">Términos y Condiciones</a> y la 
          <a href="#" target="_blank">Política de Privacidad</a>
        </label>
      </div>
      
      <button type="submit" class="auth-btn">
        Crear Cuenta
      </button>
    </form>
    
    <div class="auth-links">
      <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
      <p style="margin-top: 1rem;">
        <a href="/mer/public/">🏠 Volver al Mall</a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePassword(fieldId) {
  const passwordField = document.getElementById(fieldId);
  const toggleBtn = passwordField.nextElementSibling;
  
  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    toggleBtn.textContent = 'Ocultar';
  } else {
    passwordField.type = 'password';
    toggleBtn.textContent = 'Mostrar';
  }
}

function checkPasswordStrength(password) {
  const strengthDiv = document.getElementById('passwordStrength');
  
  if (!password) {
    strengthDiv.innerHTML = '';
    return;
  }
  
  let strength = 0;
  let feedback = [];
  
  if (password.length >= 8) strength++;
  else feedback.push('Mínimo 8 caracteres');
  
  if (/[a-z]/.test(password)) strength++;
  else feedback.push('una minúscula');
  
  if (/[A-Z]/.test(password)) strength++;
  else feedback.push('una mayúscula');
  
  if (/\d/.test(password)) strength++;
  else feedback.push('un número');
  
  if (/[^a-zA-Z\d]/.test(password)) strength++;
  
  let html = '';
  if (strength < 3) {
    html = '<span class="strength-weak">• Contraseña débil: falta ' + feedback.join(', ') + '</span>';
  } else if (strength < 5) {
    html = '<span class="strength-medium">• Contraseña media: mejora agregando ' + feedback.join(', ') + '</span>';
  } else {
    html = '<span class="strength-strong">• Contraseña fuerte ✓</span>';
  }
  
  strengthDiv.innerHTML = html;
}

function checkPasswordMatch() {
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('password_confirm').value;
  const matchDiv = document.getElementById('passwordMatch');
  
  if (!confirmPassword) {
    matchDiv.innerHTML = '';
    return;
  }
  
  if (password === confirmPassword) {
    matchDiv.innerHTML = '<span class="strength-strong">• Las contraseñas coinciden ✓</span>';
  } else {
    matchDiv.innerHTML = '<span class="strength-weak">• Las contraseñas no coinciden</span>';
  }
}

// Validación del formulario
document.querySelector('.auth-form').addEventListener('submit', function(e) {
  const form = e.target;
  const terms = document.getElementById('terms');
  
  if (!terms.checked) {
    e.preventDefault();
    alert('Debes aceptar los términos y condiciones');
    return false;
  }
  
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('password_confirm').value;
  
  if (password !== confirmPassword) {
    e.preventDefault();
    alert('Las contraseñas no coinciden');
    return false;
  }
  
  const strength = document.getElementById('passwordStrength').textContent;
  if (strength.includes('débil')) {
    e.preventDefault();
    alert('Por favor elige una contraseña más fuerte');
    return false;
  }
});

// Animación de carga
document.addEventListener('DOMContentLoaded', function() {
  const card = document.querySelector('.auth-card');
  card.style.opacity = '0';
  card.style.transform = 'translateY(20px)';
  
  setTimeout(() => {
    card.style.transition = 'all 0.3s ease';
    card.style.opacity = '1';
    card.style.transform = 'translateY(0)';
  }, 100);
});
</script>
</body>
</html>