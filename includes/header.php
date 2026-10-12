<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../db/conexion.php';
}
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($titulo_pagina) ? sanitizar($titulo_pagina) . ' - ' : ''; ?>Mini Abarrotes Don Luis</title>
  
  <!-- Tipografía Google Fonts Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Hojas de Estilo -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
</head>
<body>

  <!-- Cabecera Obligatoria de la Aplicación -->
  <header class="site-header">
    <div class="header-container">
      <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo">
        <div class="brand-icon">🏪</div>
        <div>
          <span>Don Luis</span>
          <small style="display:block; font-size:0.7rem; color:var(--text-muted); font-weight:500;">Mini Abarrotes & Minimarket</small>
        </div>
      </a>

      <!-- Navegación según roles -->
      <nav>
        <ul class="nav-links">
          <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo ($pagina_actual == 'index.php') ? 'active' : ''; ?>">
              🏠 Inicio
            </a>
          </li>

          <?php if (estaAutenticado()): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pages/ventas.php" class="<?php echo ($pagina_actual == 'ventas.php') ? 'active' : ''; ?>">
                🛒 Ventas Diarias
              </a>
            </li>
            
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pages/caja.php" class="<?php echo ($pagina_actual == 'caja.php') ? 'active' : ''; ?>">
                💰 Módulo de Caja
              </a>
            </li>

            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pages/productos.php" class="<?php echo ($pagina_actual == 'productos.php') ? 'active' : ''; ?>">
                📦 Inventario
              </a>
            </li>

            <?php if (esAdmin()): ?>
              <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>pages/usuarios.php" class="<?php echo ($pagina_actual == 'usuarios.php') ? 'active' : ''; ?>">
                  👥 Usuarios
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>pages/reportes.php" class="<?php echo ($pagina_actual == 'reportes.php') ? 'active' : ''; ?>">
                  📊 Reportes
                </a>
              </li>
            <?php endif; ?>

            <li class="nav-item" style="margin-left: 0.5rem;">
              <div class="user-badge">
                <span>👤 <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
                <span class="role-tag <?php echo $_SESSION['rol'] === 'administrador' ? 'admin' : 'empleado'; ?>">
                  <?php echo strtoupper($_SESSION['rol'] ?? 'EMPLEADO'); ?>
                </span>
                <a href="<?php echo BASE_URL; ?>pages/logout.php" title="Cerrar Sesión" style="color:var(--danger); padding:0.2rem; margin-left:0.3rem;">🚪 Salir</a>
              </div>
            </li>

          <?php else: ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pages/login.php" class="<?php echo ($pagina_actual == 'login.php') ? 'active' : ''; ?>">
                🔐 Iniciar Sesión
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pages/registro.php" class="btn btn-primary btn-sm" style="color:white;">
                📝 Registrarse
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <main class="main-content">
    <div class="container">
