<?php
$titulo_pagina = "Módulo de Caja y Control de Efectivo";
require_once __DIR__ . '/../db/conexion.php';
requerirLogin();

$mensaje_exito = '';
$mensaje_error = '';

// Obtener caja abierta actualmente si existe
$stmt = $pdo->query("SELECT c.*, u.nombre as cajero FROM cajas c JOIN usuarios u ON c.usuario_id = u.id WHERE c.estado = 'abierta' ORDER BY c.id DESC LIMIT 1");
$caja_abierta = $stmt->fetch();

// 1. Apertura de Caja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_caja']) && $_POST['accion_caja'] === 'abrir') {
    $monto_inicial = floatval($_POST['monto_inicial'] ?? 0);
    $observaciones = sanitizar($_POST['observaciones'] ?? 'Apertura de turno');

    if ($caja_abierta) {
        $mensaje_error = 'Ya existe una caja abierta en este momento.';
    } elseif ($monto_inicial < 0) {
        $mensaje_error = 'El monto inicial no puede ser negativo.';
    } else {
        $stmt_abrir = $pdo->prepare("INSERT INTO cajas (usuario_id, monto_inicial, estado, observaciones) VALUES (?, ?, 'abierta', ?)");
        $stmt_abrir->execute([$_SESSION['usuario_id'], $monto_inicial, $observaciones]);
        $mensaje_exito = 'Caja abierta exitosamente con fondo base de ' . formatoMoneda($monto_inicial);
        // Refrescar caja abierta
        $stmt = $pdo->query("SELECT c.*, u.nombre as cajero FROM cajas c JOIN usuarios u ON c.usuario_id = u.id WHERE c.estado = 'abierta' ORDER BY c.id DESC LIMIT 1");
        $caja_abierta = $stmt->fetch();
    }
}

// 2. Registro de Movimiento de Efectivo (Ingreso extra o Retiro/Gasto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_caja']) && $_POST['accion_caja'] === 'movimiento') {
    if (!$caja_abierta) {
        $mensaje_error = 'Debe abrir una caja antes de registrar movimientos de efectivo.';
    } else {
        $tipo = $_POST['tipo_movimiento'] ?? 'egreso';
        $concepto = sanitizar($_POST['concepto'] ?? '');
        $monto = floatval($_POST['monto'] ?? 0);

        if ($monto <= 0 || empty($concepto)) {
            $mensaje_error = 'Por favor indique un concepto válido y un monto mayor a cero.';
        } else {
            // Registrar movimiento
            $stmt_mov = $pdo->prepare("INSERT INTO movimientos_caja (caja_id, usuario_id, tipo, concepto, monto) VALUES (?, ?, ?, ?, ?)");
            $stmt_mov->execute([$caja_abierta['id'], $_SESSION['usuario_id'], $tipo, $concepto, $monto]);

            // Actualizar acumulados de la caja
            if ($tipo === 'ingreso') {
                $pdo->prepare("UPDATE cajas SET ingresos_extra = ingresos_extra + ? WHERE id = ?")->execute([$monto, $caja_abierta['id']]);
            } else {
                $pdo->prepare("UPDATE cajas SET egresos_gastos = egresos_gastos + ? WHERE id = ?")->execute([$monto, $caja_abierta['id']]);
            }

            $mensaje_exito = 'Movimiento de ' . strtoupper($tipo) . ' registrado correctamente por ' . formatoMoneda($monto);
            // Refrescar caja
            $stmt = $pdo->query("SELECT c.*, u.nombre as cajero FROM cajas c JOIN usuarios u ON c.usuario_id = u.id WHERE c.id = {$caja_abierta['id']}");
            $caja_abierta = $stmt->fetch();
        }
    }
}

// 3. Cierre y Arqueo de Caja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_caja']) && $_POST['accion_caja'] === 'cerrar') {
    if (!$caja_abierta) {
        $mensaje_error = 'No hay ninguna caja abierta para cerrar.';
    } else {
        $monto_final = floatval($_POST['monto_final'] ?? 0);
        $obs_cierre = sanitizar($_POST['observaciones_cierre'] ?? 'Cierre de turno');

        // Total esperado en caja = Monto Inicial + Ventas Efectivo + Ingresos Extra - Gastos
        $esperado = $caja_abierta['monto_inicial'] + $caja_abierta['ventas_efectivo'] + $caja_abierta['ingresos_extra'] - $caja_abierta['egresos_gastos'];
        $diferencia = $monto_final - $esperado;

        $stmt_cerrar = $pdo->prepare("UPDATE cajas SET monto_final = ?, diferencia = ?, estado = 'cerrada', fecha_cierre = NOW(), observaciones = CONCAT(COALESCE(observaciones,''), ' | Cierre: ', ?) WHERE id = ?");
        $stmt_cerrar->execute([$monto_final, $diferencia, $obs_cierre, $caja_abierta['id']]);

        $mensaje_exito = 'Caja cerrada exitosamente. Saldo físico contado: ' . formatoMoneda($monto_final) . ' (Diferencia arqueo: ' . formatoMoneda($diferencia) . ')';
        $caja_abierta = null;
    }
}

// Listar movimientos de la caja abierta
$movimientos = [];
if ($caja_abierta) {
    $stmt_m = $pdo->prepare("SELECT m.*, u.nombre as usuario FROM movimientos_caja m JOIN usuarios u ON m.usuario_id = u.id WHERE m.caja_id = ? ORDER BY m.id DESC");
    $stmt_m->execute([$caja_abierta['id']]);
    $movimientos = $stmt_m->fetchAll();
}

// Historial de cajas anteriores
$stmt_hist = $pdo->query("SELECT c.*, u.nombre as cajero FROM cajas c JOIN usuarios u ON c.usuario_id = u.id WHERE c.estado = 'cerrada' ORDER BY c.id DESC LIMIT 8");
$historial_cajas = $stmt_hist->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2>💰 Módulo de Caja y Control de Efectivo</h2>
    <p style="color:var(--text-muted); font-size:0.92rem;">
      Administración de apertura de gaveta, arqueo, ingresos, egresos por gastos menores y cierre de turno.
    </p>
  </div>
  <div>
    <?php if ($caja_abierta): ?>
      <span class="badge badge-success" style="font-size:0.9rem; padding:0.5rem 1rem;">
        🟢 CAJA ABIERTA #<?php echo $caja_abierta['id']; ?> (Por: <?php echo htmlspecialchars($caja_abierta['cajero']); ?>)
      </span>
    <?php else: ?>
      <span class="badge badge-danger" style="font-size:0.9rem; padding:0.5rem 1rem;">
        🔒 CAJA CERRADA
      </span>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($mensaje_exito)): ?>
  <div class="alert alert-success">✅ <?php echo htmlspecialchars($mensaje_exito); ?></div>
<?php endif; ?>
<?php if (!empty($mensaje_error)): ?>
  <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($mensaje_error); ?></div>
<?php endif; ?>

<!-- Si la caja está abierta: mostramos resumen y operaciones -->
<?php if ($caja_abierta): 
  $esperado_actual = $caja_abierta['monto_inicial'] + $caja_abierta['ventas_efectivo'] + $caja_abierta['ingresos_extra'] - $caja_abierta['egresos_gastos'];
?>
  
  <div class="grid-cols-4" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div class="stat-icon green">🏦</div>
      <div>
        <div class="stat-value"><?php echo formatoMoneda($caja_abierta['monto_inicial']); ?></div>
        <div class="stat-label">Fondo de Apertura</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon blue">🛒</div>
      <div>
        <div class="stat-value"><?php echo formatoMoneda($caja_abierta['ventas_efectivo']); ?></div>
        <div class="stat-label">Ventas en Efectivo</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon orange">💸</div>
      <div>
        <div class="stat-value" style="color:var(--danger);"><?php echo formatoMoneda($caja_abierta['egresos_gastos']); ?></div>
        <div class="stat-label">Egresos / Gastos Menores</div>
      </div>
    </div>

    <div class="stat-card" style="border: 2px solid var(--primary);">
      <div class="stat-icon green">💼</div>
      <div>
        <div class="stat-value" style="color:var(--primary);"><?php echo formatoMoneda($esperado_actual); ?></div>
        <div class="stat-label"><strong>Efectivo Teórico en Gaveta</strong></div>
      </div>
    </div>
  </div>

  <div class="grid-cols-2" style="margin-bottom:2rem;">
    
    <!-- Formulario de Movimiento de Efectivo -->
    <div class="card">
      <div class="card-header">
        <h4>📝 Registrar Movimiento de Efectivo</h4>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="accion_caja" value="movimiento">
        
        <div class="form-group">
          <label class="form-label">Tipo de Movimiento:</label>
          <select name="tipo_movimiento" class="form-select" required>
            <option value="egreso">🔴 Egreso / Retiro (Pago a proveedor, compra insumos, adelanto)</option>
            <option value="ingreso">🟢 Ingreso Extra (Aporte de cambio, ajuste positivo)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Concepto / Motivo:</label>
          <input type="text" name="concepto" class="form-control" placeholder="Ej: Pago de pan diario o retiro de sencillo" required>
        </div>

        <div class="form-group">
          <label class="form-label">Monto ($):</label>
          <input type="number" step="0.01" min="1" name="monto" class="form-control" placeholder="Ej: 5000" required>
        </div>

        <button type="submit" class="btn btn-secondary" style="width:100%;">
          💾 Guardar Movimiento
        </button>
      </form>
    </div>

    <!-- Formulario de Cierre de Caja / Arqueo -->
    <div class="card">
      <div class="card-header">
        <h4>🔒 Cierre y Arqueo de Caja</h4>
      </div>
      <p style="font-size:0.88rem; color:var(--text-muted); margin-bottom:1rem;">
        Cuente el dinero físico presente en la gaveta e ingréselo a continuación para conciliar con el saldo teórico de <strong><?php echo formatoMoneda($esperado_actual); ?></strong>.
      </p>

      <form action="" method="POST">
        <input type="hidden" name="accion_caja" value="cerrar">

        <div class="form-group">
          <label class="form-label">Monto Físico Contado ($):</label>
          <input type="number" step="0.01" min="0" name="monto_final" class="form-control" placeholder="Ej: <?php echo $esperado_actual; ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Observaciones de Cierre:</label>
          <input type="text" name="observaciones_cierre" class="form-control" placeholder="Ej: Turno entregado conforme sin discrepancias">
        </div>

        <button type="submit" class="btn btn-danger confirm-action" data-confirm="¿Está seguro de realizar el arqueo y cerrar esta caja?" style="width:100%;">
          🔒 Realizar Arqueo y Cerrar Caja
        </button>
      </form>
    </div>

  </div>

  <!-- Movimientos de esta caja -->
  <div class="card" style="margin-bottom:2rem;">
    <div class="card-header">
      <h4>📋 Movimientos de Efectivo Registrados en Este Turno</h4>
    </div>
    <div class="table-responsive">
      <table class="custom-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Hora</th>
            <th>Tipo</th>
            <th>Concepto</th>
            <th>Registrado Por</th>
            <th>Monto</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($movimientos)): ?>
            <?php foreach ($movimientos as $m): ?>
              <tr>
                <td>#<?php echo $m['id']; ?></td>
                <td><?php echo date('H:i', strtotime($m['fecha'])); ?></td>
                <td>
                  <span class="badge <?php echo $m['tipo'] === 'ingreso' ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo strtoupper($m['tipo']); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($m['concepto']); ?></td>
                <td><?php echo htmlspecialchars($m['usuario']); ?></td>
                <td style="font-weight:700; color:<?php echo $m['tipo'] === 'ingreso' ? 'var(--primary)' : 'var(--danger)'; ?>">
                  <?php echo ($m['tipo'] === 'ingreso' ? '+' : '-') . formatoMoneda($m['monto']); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align:center; color:var(--text-muted); padding:1.5rem;">
                No se han registrado movimientos extraordinarios de efectivo en este turno.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>

  <!-- Estado Caja Cerrada: Formulario de Apertura -->
  <div class="card" style="max-width: 600px; margin: 1rem auto 2.5rem auto;">
    <div class="card-header">
      <h4>🟢 Abrir Nueva Caja (Comenzar Turno)</h4>
    </div>
    <form action="" method="POST">
      <input type="hidden" name="accion_caja" value="abrir">

      <div class="form-group">
        <label class="form-label">Monto de Apertura / Fondo Base ($):</label>
        <input type="number" step="0.01" min="0" name="monto_inicial" class="form-control" placeholder="Ej: 50000" value="50000" required>
        <small style="color:var(--text-muted);">Monto físico disponible en monedas y billetes para dar cambio.</small>
      </div>

      <div class="form-group">
        <label class="form-label">Observaciones o Notas:</label>
        <input type="text" name="observaciones" class="form-control" placeholder="Ej: Apertura turno mañana, cajero Don Luis">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">
        🚀 Iniciar Turno y Abrir Caja
      </button>
    </form>
  </div>

<?php endif; ?>

<!-- Historial de Cierres de Caja Anteriores -->
<div class="card">
  <div class="card-header">
    <h4>📜 Historial de Turnos y Arqueos de Caja Cerrados</h4>
  </div>
  <div class="table-responsive">
    <table class="custom-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Cajero</th>
          <th>Apertura</th>
          <th>Cierre</th>
          <th>Fondo Base</th>
          <th>Ventas Efectivo</th>
          <th>Egresos</th>
          <th>Monto Final</th>
          <th>Diferencia</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($historial_cajas)): ?>
          <?php foreach ($historial_cajas as $hc): ?>
            <tr>
              <td>#<?php echo $hc['id']; ?></td>
              <td><?php echo htmlspecialchars($hc['cajero']); ?></td>
              <td><?php echo date('d/m/Y H:i', strtotime($hc['fecha_apertura'])); ?></td>
              <td><?php echo $hc['fecha_cierre'] ? date('d/m/Y H:i', strtotime($hc['fecha_cierre'])) : '-'; ?></td>
              <td><?php echo formatoMoneda($hc['monto_inicial']); ?></td>
              <td style="color:var(--primary); font-weight:600;"><?php echo formatoMoneda($hc['ventas_efectivo']); ?></td>
              <td style="color:var(--danger);"><?php echo formatoMoneda($hc['egresos_gastos']); ?></td>
              <td style="font-weight:700;"><?php echo formatoMoneda($hc['monto_final']); ?></td>
              <td>
                <?php if ($hc['diferencia'] == 0): ?>
                  <span class="badge badge-success">Exacto ($0)</span>
                <?php elseif ($hc['diferencia'] > 0): ?>
                  <span class="badge badge-info">Sobrante (+<?php echo formatoMoneda($hc['diferencia']); ?>)</span>
                <?php else: ?>
                  <span class="badge badge-danger">Faltante (<?php echo formatoMoneda($hc['diferencia']); ?>)</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" style="text-align:center; color:var(--text-muted); padding:1.5rem;">
              No hay registros históricos de cajas cerradas aún.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
