<?php
$titulo_pagina = "Inicio - Dashboard";
require_once __DIR__ . '/db/conexion.php';

// Estadísticas para el dashboard
$total_ventas_hoy = 0;
$cantidad_ventas_hoy = 0;
$caja_actual = null;
$total_productos = 0;
$productos_bajo_stock = 0;

if (isset($pdo)) {
    // Ventas de hoy
    $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) AS total, COUNT(*) AS conteo FROM ventas WHERE DATE(fecha_venta) = CURDATE()");
    $res = $stmt->fetch();
    $total_ventas_hoy = $res['total'] ?? 0;
    $cantidad_ventas_hoy = $res['conteo'] ?? 0;

    // Caja abierta actual
    $stmt = $pdo->query("SELECT * FROM cajas WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
    $caja_actual = $stmt->fetch();

    // Productos y alertas de stock
    $stmt = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) AS bajo_stock FROM productos");
    $res_prod = $stmt->fetch();
    $total_productos = $res_prod['total'] ?? 0;
    $productos_bajo_stock = $res_prod['bajo_stock'] ?? 0;

    // Últimas ventas
    $stmt_ultimas = $pdo->query("SELECT v.*, u.nombre as cajero FROM ventas v JOIN usuarios u ON v.usuario_id = u.id ORDER BY v.id DESC LIMIT 5");
    $ultimas_ventas = $stmt_ultimas->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner Principal -->
<div class="hero-banner">
  <div>
    <span class="badge" style="background: rgba(255,255,255,0.2); color: white; margin-bottom: 0.75rem;">
      ✨ Minimarket & Gestión Integral
    </span>
    <h1 class="hero-title">Mini Abarrotes Don Luis</h1>
    <p class="hero-subtitle">
      Plataforma integral de punto de venta: registre ventas diarias al instante, administre su inventario y mantenga el control riguroso de efectivo y arqueo con el Módulo de Caja.
    </p>
  </div>
  
  <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
    <?php if (estaAutenticado()): ?>
      <a href="<?php echo BASE_URL; ?>pages/ventas.php" class="btn" style="background: white; color: var(--primary); font-weight: 700;">
        🛒 Nueva Venta Diaria
      </a>
      <a href="<?php echo BASE_URL; ?>pages/caja.php" class="btn" style="background: var(--dark); color: white;">
        💰 Control de Caja
      </a>
      <a href="<?php echo BASE_URL; ?>pages/productos.php" class="btn" style="background: rgba(255,255,255,0.2); color: white;">
        📦 Ver Inventario
      </a>
    <?php else: ?>
      <a href="<?php echo BASE_URL; ?>pages/login.php" class="btn" style="background: white; color: var(--primary); font-weight: 700;">
        🔐 Ingresar al Sistema
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'acceso_denegado'): ?>
  <div class="alert alert-danger">
    ⛔ <strong>Acceso Restringido:</strong> No tiene permisos de Administrador para ver esa sección.
  </div>
<?php endif; ?>

<!-- Resumen Estadístico / Métricas Rápidas -->
<div class="grid-cols-4">
  <div class="stat-card">
    <div class="stat-icon green">💵</div>
    <div>
      <div class="stat-value"><?php echo formatoMoneda($total_ventas_hoy); ?></div>
      <div class="stat-label">Ventas Hoy (<?php echo $cantidad_ventas_hoy; ?> transacciones)</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon <?php echo $caja_actual ? 'orange' : 'purple'; ?>">
      <?php echo $caja_actual ? '🟢' : '🔒'; ?>
    </div>
    <div>
      <div class="stat-value" style="font-size: 1.3rem;">
        <?php echo $caja_actual ? 'Caja Abierta' : 'Caja Cerrada'; ?>
      </div>
      <div class="stat-label">
        <?php echo $caja_actual ? 'Fondo: ' . formatoMoneda($caja_actual['monto_inicial']) : 'Requiere apertura'; ?>
      </div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">📦</div>
    <div>
      <div class="stat-value"><?php echo $total_productos; ?></div>
      <div class="stat-label">Productos en Catálogo</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon <?php echo $productos_bajo_stock > 0 ? 'purple' : 'green'; ?>">⚠️</div>
    <div>
      <div class="stat-value"><?php echo $productos_bajo_stock; ?></div>
      <div class="stat-label">Alertas Bajo Stock</div>
    </div>
  </div>
</div>

<!-- Grid de Módulos y Accesos Principales -->
<div class="grid-cols-2">
  
  <!-- Módulo de Ventas -->
  <div class="card">
    <div class="card-header">
      <h3 style="display:flex; align-items:center; gap:0.5rem;">
        <span>🛒</span> Módulo de Ventas Diarias
      </h3>
      <a href="<?php echo BASE_URL; ?>pages/ventas.php" class="btn btn-outline btn-sm">Abrir POS</a>
    </div>
    <p style="color:var(--text-muted); margin-bottom:1rem; font-size:0.92rem;">
      Registro rápido de compras en mostrador con selección por código o nombre, cálculo instantáneo de totales, formas de pago (efectivo, débito, transferencia) y vuelto en tiempo real.
    </p>
    <div style="background:#f8fafc; padding:1rem; border-radius:8px; border:1px solid var(--border);">
      <h5 style="margin-bottom:0.5rem; font-size:0.85rem; text-transform:uppercase; color:var(--text-muted);">Últimas Ventas Registradas:</h5>
      <?php if (!empty($ultimas_ventas)): ?>
        <ul style="list-style:none; font-size:0.88rem; line-height:1.8;">
          <?php foreach ($ultimas_ventas as $v): ?>
            <li style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding:0.25rem 0;">
              <span><strong>#<?php echo $v['id']; ?></strong> <?php echo htmlspecialchars($v['cliente_nombre']); ?> (<?php echo htmlspecialchars($v['cajero']); ?>)</span>
              <span style="font-weight:700; color:var(--primary);"><?php echo formatoMoneda($v['total']); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p style="color:var(--text-muted); font-size:0.85rem;">No hay ventas registradas aún hoy.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Módulo de Caja y Control de Efectivo -->
  <div class="card">
    <div class="card-header">
      <h3 style="display:flex; align-items:center; gap:0.5rem;">
        <span>💰</span> Módulo de Caja y Efectivo
      </h3>
      <a href="<?php echo BASE_URL; ?>pages/caja.php" class="btn btn-outline btn-sm">Gestionar Caja</a>
    </div>
    <p style="color:var(--text-muted); margin-bottom:1rem; font-size:0.92rem;">
      Control exhaustivo del flujo de dinero físico. Registro de monto base de apertura, egresos para pago de proveedores o gastos del día, e ingresos extraordinarios con arqueo al cierre.
    </p>
    <div style="background:#f8fafc; padding:1rem; border-radius:8px; border:1px solid var(--border);">
      <h5 style="margin-bottom:0.5rem; font-size:0.85rem; text-transform:uppercase; color:var(--text-muted);">Estado Actual del Turno:</h5>
      <?php if ($caja_actual): ?>
        <p style="font-size:0.9rem; margin-bottom:0.3rem;">
          <strong>Apertura:</strong> <?php echo formatoMoneda($caja_actual['monto_inicial']); ?> por ID #<?php echo $caja_actual['usuario_id']; ?>
        </p>
        <p style="font-size:0.9rem; margin-bottom:0.3rem;">
          <strong>Ventas en Efectivo:</strong> <?php echo formatoMoneda($caja_actual['ventas_efectivo']); ?>
        </p>
        <p style="font-size:0.9rem;">
          <strong>Gastos/Egresos:</strong> <span style="color:var(--danger);"><?php echo formatoMoneda($caja_actual['egresos_gastos']); ?></span>
        </p>
      <?php else: ?>
        <p style="color:var(--warning); font-size:0.9rem;">
          ⚠️ Actualmente no hay ninguna caja abierta. Inicie el turno ingresando a Caja.
        </p>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
