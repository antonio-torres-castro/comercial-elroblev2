<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso Denegado - 403 - Mall Virtual</title>
<link rel="stylesheet" href="assets/css/modern.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --error-primary: #EF4444;
  --error-secondary: #64748B;
  --error-background: #FEF2F2;
  --error-card: #FFFFFF;
  --error-text: #1E293B;
  --error-text-muted: #64748B;
  --error-border: #FECACA;
  --error-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(135deg, var(--error-background) 0%, #FFFFFF 100%);
  min-height: 100vh;
  color: var(--error-text);
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1.6;
}

.error-container {
  text-align: center;
  max-width: 500px;
  padding: 2rem;
}

.error-card {
  background: var(--error-card);
  border-radius: 1rem;
  padding: 3rem 2rem;
  box-shadow: var(--error-shadow);
  border: 1px solid var(--error-border);
}

.error-icon {
  width: 80px;
  height: 80px;
  background: var(--error-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 auto 2rem;
}

.error-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--error-text);
  margin-bottom: 1rem;
}

.error-code {
  font-size: 1.25rem;
  color: var(--error-primary);
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.error-description {
  font-size: 1rem;
  color: var(--error-text-muted);
  margin-bottom: 2rem;
  line-height: 1.6;
}

.error-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: var(--error-primary);
  color: white;
}

.btn-primary:hover {
  background: #DC2626;
}

.btn-outline {
  background: transparent;
  color: var(--error-primary);
  border: 1px solid var(--error-primary);
}

.btn-outline:hover {
  background: var(--error-primary);
  color: white;
}

@media (max-width: 480px) {
  .error-container {
    padding: 1rem;
  }
  
  .error-card {
    padding: 2rem 1.5rem;
  }
  
  .error-title {
    font-size: 1.5rem;
  }
  
  .error-actions {
    flex-direction: column;
  }
}
</style>
</head>
<body>
<div class="error-container">
  <div class="error-card">
    <div class="error-icon">403</div>
    
    <h1 class="error-title">Acceso Denegado</h1>
    <p class="error-code">Error 403</p>
    
    <p class="error-description">
      Lo sentimos, pero no tienes los permisos necesarios para acceder a esta página. 
      Este contenido está restringido y requiere un nivel de autorización específico.
    </p>
    
    <div class="error-actions">
      <a href="/mer/public/" class="btn btn-primary">
        🏠 Volver al Inicio
      </a>
      <a href="javascript:history.back()" class="btn btn-outline">
        ← Página Anterior
      </a>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Animación de entrada
  const card = document.querySelector('.error-card');
  card.style.opacity = '0';
  card.style.transform = 'translateY(20px)';
  
  setTimeout(() => {
    card.style.transition = 'all 0.5s ease';
    card.style.opacity = '1';
    card.style.transform = 'translateY(0)';
  }, 100);
});
</script>
</body>
</html>