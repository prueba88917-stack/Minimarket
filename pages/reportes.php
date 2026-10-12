<?php
$titulo_pagina = "Reportes y Analíticas";
require_once __DIR__ . '/../db/conexion.php';
requerirAdmin(); // Solo para Administrador

$filtro_fecha_inicio = sanitizar($_GET['inicio'] ?? date('Y-m-01'));
$filtro_fecha_fin = sanitizar($_GET['fin'] ?? date('Y-m-d'));

// 1. Resumen de Ventas en el rango
$stmt_resumen = $pdo->prepare("SELECT 
    COUNT(*) as total_transacciones,
    COALESCE(SUM(total), 0) as total_monto,
    COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as total_efectivo,
    COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END), 0) as total_transferencia,
    COALESCE(SUM(CASE WHEN metodo_pago = 'tarjeta' THEN total ELSE 0 END), 0) as total_tarjeta
    FROM ventas WHERE DATE(fecha_venta) BETWEEN ? AND ?");
$stmt_resumen->execute([$filtro_fecha_inicio, $filtro_fecha_fin]);
$resumen = $stmt_resumen->fetch();

// 2. Productos Más Vendidos
$stmt_top = $pdo->prepare("SELECT 
    p.nombre, p.codigo_barra, 
    SUM(dv.cantidad) as total_vendido, 
    SUM(dv.subtotal) as recaudacion
    FROM detalle_ventas dv
    JOIN productos p ON dv.producto_id = p.id
    JOIN ventas v ON dv.venta_id = v.id
    WHERE DATE(v.fecha_venta) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_vendido DESC LIMIT 6");
$stmt_top->execute([$filtro_fecha_inicio, $filtro_fecha_fin]);
$top_productos = $stmt_top->fetchAll();

// 3. Listado de ventas detalladas
$stmt_listado = $pdo->prepare("SELECT v.*, u.nombre as cajero 
    FROM ventas v 
    JOIN usuarios u ON v.usuario_id = u.id 
    WHERE DATE(v.fecha_venta) BETWEEN ? AND ? 
    ORDER BY v.id DESC");
$stmt_listado->execute([$filtro_fecha_inicio, $filtro_fecha_fin]);
$listado_ventas = $stmt_listado->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2>📊 Reportes y Analíticas de Negocio</h2>
    <p style="color:var(--text-muted); font-size:0.92rem;">
      Balance de ventas diarias, ingresos por método de pago y productos de mayor rotación.
    </p>
  </div>
  <button onclick="window.print()" class="btn btn-outline">
    🖨️ Imprimir / Exportar Reporte
  </button>
</div>

<!-- Filtro de Fechas -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form action="" method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
    <div>
      <label class="form-label">Fecha Desde:</label>
      <input type="date" name="inicio" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
    </div>
    <div>
      <label class="form-label">Fecha Hasta:</label>
      <input type="date" name="fin" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
    </div>
    <div>
      <button type="submit" class="btn btn-primary">Generar Balance</button>
      <a href="<?php echo BASE_URL; ?>pages/reportes.php" class="btn btn-outline">Mes Actual</a>
    </div>
  </form>
</div>

<!-- Resumen de Métricas -->
<div class="grid-cols-4" style="margin-bottom:2rem;">
  <div class="stat-card">
    <div class="stat-icon green">💰</div>
    <div>
      <div class="stat-value"><?php echo formatoMoneda($resumen['total_monto']); ?></div>
      <div class="stat-label">Total Facturado (<?php echo $resumen['total_transacciones']; ?> ventas)</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">💵</div>
    <div>
      <div class="stat-value"><?php echo formatoMoneda($resumen['total_efectivo']); ?></div>
      <div class="stat-label">Ingresos Efectivo (Caja)</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple">📲</div>
    <div>
      <div class="stat-value"><?php echo formatoMoneda($resumen['total_transferencia']); ?></div>
      <div class="stat-label">Transferencias</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon orange">💳</div>
    <div>
      <div class="stat-value"><?php echo formatoMoneda($resumen['total_tarjeta']); ?></div>
      <div class="stat-label">Tarjetas / Débito</div>
    </div>
  </div>
</div>

<div class="grid-cols-2" style="margin-bottom:2rem;">
  <!-- Ranking de Productos -->
  <div class="card">
    <div class="card-header">
      <h4>🏆 Productos Más Vendidos del Período</h4>
    </div>
    <div class="table-responsive">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Uds. Vendidas</th>
            <th>Recaudación</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($top_productos)): ?>
            <?php foreach ($top_productos as $tp): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($tp['nombre']); ?></strong></td>
                <td><span class="badge badge-info"><?php echo $tp['total_vendido']; ?> unidades</span></td>
                <td style="font-weight:700; color:var(--primary);"><?php echo formatoMoneda($tp['recaudacion']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" style="text-align:center; color:var(--text-muted); padding:1.5rem;">
                No hay ventas en este rango de fechas.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Detalle por Método de Pago -->
  <div class="card">
    <div class="card-header">
      <h4>💳 Distribución de Ingresos</h4>
    </div>
    <div style="padding:1rem 0;">
      <?php 
        $total = max(1, $resumen['total_monto']);
        $p_efe = round(($resumen['total_efectivo'] / $total) * 100);
        $p_tra = round(($resumen['total_transferencia'] / $total) * 100);
        $p_tar = round(($resumen['total_tarjeta'] / $total) * 100);
      ?>
      <div style="margin-bottom:1.25rem;">
        <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:0.25rem;">
          <span>💵 Efectivo: <?php echo formatoMoneda($resumen['total_efectivo']); ?></span>
          <strong><?php echo $p_efe; ?>%</strong>
        </div>
        <div style="background:#e2e8f0; border-radius:9999px; height:12px; overflow:hidden;">
          <div style="background:var(--primary); width:<?php echo $p_efe; ?>%; height:100%;"></div>
        </div>
      </div>

      <div style="margin-bottom:1.25rem;">
        <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:0.25rem;">
          <span>📲 Transferencias: <?php echo formatoMoneda($resumen['total_transferencia']); ?></span>
          <strong><?php echo $p_tra; ?>%</strong>
        </div>
        <div style="background:#e2e8f0; border-radius:9999px; height:12px; overflow:hidden;">
          <div style="background:#8b5cf6; width:<?php echo $p_tra; ?>%; height:100%;"></div>
        </div>
      </div>

      <div>
        <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:0.25rem;">
          <span>💳 Tarjetas: <?php echo formatoMoneda($resumen['total_tarjeta']); ?></span>
          <strong><?php echo $p_tar; ?>%</strong>
        </div>
        <div style="background:#e2e8f0; border-radius:9999px; height:12px; overflow:hidden;">
          <div style="background:var(--secondary); width:<?php echo $p_tar; ?>%; height:100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Listado de Ventas del Período -->
<div class="card">
  <div class="card-header">
    <h4>📜 Registro Detallado de Ventas del Período</h4>
  </div>
  <div class="table-responsive">
    <table class="custom-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha y Hora</th>
          <th>Cajero</th>
          <th>Cliente</th>
          <th>Método</th>
          <th>Monto Total</th>
          <th>Vuelto</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($listado_ventas)): ?>
          <?php foreach ($listado_ventas as $lv): ?>
            <tr>
              <td>#<?php echo $lv['id']; ?></td>
              <td><?php echo date('d/m/Y H:i', strtotime($lv['fecha_venta'])); ?></td>
              <td><?php echo htmlspecialchars($lv['cajero']); ?></td>
              <td><?php echo htmlspecialchars($lv['cliente_nombre']); ?></td>
              <td>
                <span class="badge badge-info"><?php echo strtoupper($lv['metodo_pago']); ?></span>
              </td>
              <td style="font-weight:700; color:var(--primary);"><?php echo formatoMoneda($lv['total']); ?></td>
              <td><?php echo formatoMoneda($lv['vuelto']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center; color:var(--text-muted); padding:2rem;">
              No hay ventas registradas en las fechas seleccionadas.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
