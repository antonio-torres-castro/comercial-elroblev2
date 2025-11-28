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
$email = $_POST['email'] ?? '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones
    if (empty($email)) {
        $errors[] = 'El email es requerido';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Formato de email inválido';
    }
    
    if (empty($password)) {
        $errors[] = 'La contraseña es requerida';
    }
    
    if (empty($errors)) {
        $result = authenticateUser($email, $password);
        
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? '/mer/public/';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
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
<title>Iniciar Sesión - Mall Virtual</title>
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
  max-width: 400px;
  overflow: hidden;
}

.auth-header {
  background: linear-gradient(135deg, var(--auth-primary) 0%, var(--auth-primary-dark) 100%);
  color: white;
  padding: 2rem;
  text-align: center;
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

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--auth-text);
  font-size: 0.875rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--auth-border);
  border-radius: 0.5rem;
  font-size: 1rem;
  background: var(--auth-input-bg);
  transition: all 0.2s ease;
}

.form-input:focus {
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

.auth-divider {
  margin: 1.5rem 0;
  text-align: center;
  position: relative;
}

.auth-divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--auth-border);
}

.auth-divider span {
  background: var(--auth-card);
  padding: 0 1rem;
  color: var(--auth-text-muted);
  font-size: 0.75rem;
  text-transform: uppercase;
  font-weight: 600;
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

.remember-forgot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.remember-forgot label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--auth-text-muted);
}

.remember-forgot input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
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
}
</style>
</head>
<body>
<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h1>Mall Virtual</h1>
      <p>Inicia sesión en tu cuenta</p>
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
      
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          class="form-input" 
          value="<?= htmlspecialchars($email) ?>"
          required
          placeholder="tu@email.com"
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
          placeholder="Tu contraseña"
        >
        <button type="button" class="password-toggle-btn" onclick="togglePassword()">
          Mostrar
        </button>
      </div>
      
      <div class="remember-forgot">
        <label>
          <input type="checkbox" name="remember" value="1">
          Recordar sesión
        </label>
        <a href="forgot-password.php">¿Olvidaste tu contraseña?</a>
      </div>
      
      <button type="submit" class="auth-btn">
        Iniciar Sesión
      </button>
    </form>
    
    <div class="auth-links">
      <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
      <div class="auth-divider">
        <span>O continúa como</span>
      </div>
      <p>
        <a href="/mer/public/">🏠 Volver al Mall</a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePassword() {
  const passwordField = document.getElementById('password');
  const toggleBtn = document.querySelector('.password-toggle-btn');
  
  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    toggleBtn.textContent = 'Ocultar';
  } else {
    passwordField.type = 'password';
    toggleBtn.textContent = 'Mostrar';
  }
}

// Validación básica del lado cliente
document.querySelector('.auth-form').addEventListener('submit', function(e) {
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  
  if (!email || !password) {
    e.preventDefault();
    alert('Por favor completa todos los campos');
    return;
  }
  
  if (!email.includes('@')) {
    e.preventDefault();
    alert('Por favor ingresa un email válido');
    return;
  }
});

// Mejorar UX con animaciones
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