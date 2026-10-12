<?php
$titulo_pagina = "Punto de Venta - Ventas Diarias";
require_once __DIR__ . '/../db/conexion.php';
requerirLogin();

$mensaje_exito = '';
$mensaje_error = '';

// Verificar si hay caja abierta
$stmt_caja = $pdo->query("SELECT * FROM cajas WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
$caja_activa = $stmt_caja->fetch();

// Inicializar carrito en sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 1. Agregar producto al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $cantidad = max(1, intval($_POST['cantidad'] ?? 1));

    $stmt_p = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt_p->execute([$producto_id]);
    $prod = $stmt_p->fetch();

    if ($prod) {
        if ($prod['stock'] < $cantidad) {
            $mensaje_error = "Stock insuficiente para {$prod['nombre']}. Solo quedan {$prod['stock']} unidades.";
        } else {
            if (isset($_SESSION['carrito'][$producto_id])) {
                $nueva_cant = $_SESSION['carrito'][$producto_id]['cantidad'] + $cantidad;
                if ($nueva_cant > $prod['stock']) {
                    $mensaje_error = "No puede agregar más de {$prod['stock']} unidades disponibles.";
                } else {
                    $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cant;
                    $_SESSION['carrito'][$producto_id]['subtotal'] = $nueva_cant * $prod['precio_venta'];
                    $mensaje_exito = "Se sumaron unidades de {$prod['nombre']}.";
                }
            } else {
                $_SESSION['carrito'][$producto_id] = [
                    'id' => $prod['id'],
                    'codigo' => $prod['codigo_barra'],
                    'nombre' => $prod['nombre'],
                    'precio' => $prod['precio_venta'],
                    'cantidad' => $cantidad,
                    'subtotal' => $cantidad * $prod['precio_venta']
                ];
                $mensaje_exito = "Producto agregado: {$prod['nombre']}.";
            }
        }
    } else {
        $mensaje_error = "Producto no encontrado.";
    }
}

// 2. Quitar producto del carrito
if (isset($_GET['quitar'])) {
    $quitar_id = intval($_GET['quitar']);
    if (isset($_SESSION['carrito'][$quitar_id])) {
        unset($_SESSION['carrito'][$quitar_id]);
        $mensaje_exito = "Producto removido de la venta actual.";
    }
}

// 3. Vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    $mensaje_exito = "Venta cancelada / carrito vaciado.";
}

// 4. Procesar y Guardar Venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'procesar_venta') {
    if (empty($_SESSION['carrito'])) {
        $mensaje_error = "El carrito de compras está vacío.";
    } else {
        $cliente = sanitizar($_POST['cliente_nombre'] ?? 'Cliente General');
        $metodo = sanitizar($_POST['metodo_pago'] ?? 'efectivo');
        $monto_recibido = floatval($_POST['monto_recibido'] ?? 0);
        $total_venta = 0;

        foreach ($_SESSION['carrito'] as $item) {
            $total_venta += $item['subtotal'];
        }

        if ($metodo === 'efectivo' && $monto_recibido < $total_venta) {
            $mensaje_error = "El monto recibido en efectivo (" . formatoMoneda($monto_recibido) . ") es menor que el total (" . formatoMoneda($total_venta) . ").";
        } else {
            $vuelto = ($metodo === 'efectivo') ? ($monto_recibido - $total_venta) : 0;
            $caja_id = $caja_activa ? $caja_activa['id'] : null;

            try {
                $pdo->beginTransaction();

                // Insertar encabezado de venta
                $stmt_v = $pdo->prepare("INSERT INTO ventas (caja_id, usuario_id, cliente_nombre, total, metodo_pago, monto_recibido, vuelto) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_v->execute([$caja_id, $_SESSION['usuario_id'], $cliente, $total_venta, $metodo, $monto_recibido, $vuelto]);
                $venta_id = $pdo->lastInsertId();

                // Insertar detalles y descontar stock
                $stmt_det = $pdo->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt_stock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

                foreach ($_SESSION['carrito'] as $it) {
                    $stmt_det->execute([$venta_id, $it['id'], $it['cantidad'], $it['precio'], $it['subtotal']]);
                    $stmt_stock->execute([$it['cantidad'], $it['id']]);
                }

                // Actualizar acumulados en la caja si el pago fue en efectivo o transferencia
                if ($caja_id) {
                    if ($metodo === 'efectivo') {
                        $pdo->prepare("UPDATE cajas SET ventas_efectivo = ventas_efectivo + ? WHERE id = ?")->execute([$total_venta, $caja_id]);
                    } else {
                        $pdo->prepare("UPDATE cajas SET ventas_transferencia = ventas_transferencia + ? WHERE id = ?")->execute([$total_venta, $caja_id]);
                    }
                }

                $pdo->commit();

                // Vaciar carrito
                $_SESSION['carrito'] = [];
                $mensaje_exito = "¡Venta #{$venta_id} procesada exitosamente! Total: " . formatoMoneda($total_venta) . " | Vuelto: " . formatoMoneda($vuelto);
                
                // Ejecutar sonido interactivo
                echo "<script>document.addEventListener('DOMContentLoaded', () => { reproducirSonido('venta'); });</script>";

            } catch (Exception $e) {
                $pdo->rollBack();
                $mensaje_error = "Error al procesar la venta: " . $e->getMessage();
            }
        }
    }
}

// Búsqueda y listado de productos disponibles
$busqueda = sanitizar($_GET['q'] ?? '');
if (!empty($busqueda)) {
    $stmt_art = $pdo->prepare("SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE (p.nombre LIKE ? OR p.codigo_barra LIKE ?) AND p.stock > 0 ORDER BY p.nombre ASC LIMIT 16");
    $stmt_art->execute(["%$busqueda%", "%$busqueda%"]);
} else {
    $stmt_art = $pdo->query("SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.stock > 0 ORDER BY p.nombre ASC LIMIT 16");
}
$catalogo_productos = $stmt_art->fetchAll();

// Calcular total del carrito actual
$total_carrito = 0;
foreach ($_SESSION['carrito'] as $it) {
    $total_carrito += $it['subtotal'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2>🛒 Módulo de Ventas Diarias (Punto de Venta)</h2>
    <p style="color:var(--text-muted); font-size:0.92rem;">
      Registro ágil de compras, control de stock automático y vinculación directa al arqueo de caja.
    </p>
  </div>
  <?php if (!$caja_activa): ?>
    <div class="alert alert-warning" style="margin-bottom:0; padding:0.5rem 1rem;">
      ⚠️ No hay caja abierta. Las ventas no se sumarán a un arqueo activo. <a href="<?php echo BASE_URL; ?>pages/caja.php" style="text-decoration:underline; font-weight:700;">Abrir caja</a>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($mensaje_exito)): ?>
  <div class="alert alert-success">✅ <?php echo htmlspecialchars($mensaje_exito); ?></div>
<?php endif; ?>
<?php if (!empty($mensaje_error)): ?>
  <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($mensaje_error); ?></div>
<?php endif; ?>

<div class="grid-cols-2" style="grid-template-columns: 1.15fr 0.85fr; align-items: flex-start;">
  
  <!-- Catálogo y Buscador de Productos -->
  <div class="card">
    <div class="card-header">
      <h4>🔍 Catálogo de Productos para la Venta</h4>
    </div>

    <!-- Barra de búsqueda rápida -->
    <form action="" method="GET" style="display:flex; gap:0.5rem; margin-bottom:1.25rem;">
      <input type="text" name="q" class="form-control" placeholder="Buscar por código de barra o nombre de producto..." value="<?php echo htmlspecialchars($busqueda); ?>">
      <button type="submit" class="btn btn-primary">Buscar</button>
      <?php if (!empty($busqueda)): ?>
        <a href="<?php echo BASE_URL; ?>pages/ventas.php" class="btn btn-outline">Limpiar</a>
      <?php endif; ?>
    </form>

    <!-- Grilla de productos rápidos -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem; max-height:550px; overflow-y:auto; padding-right:5px;">
      <?php if (!empty($catalogo_productos)): ?>
        <?php foreach ($catalogo_productos as $p): ?>
          <div style="background:#f8fafc; border:1px solid var(--border); border-radius:10px; padding:0.85rem; display:flex; flex-direction:column; justify-content:space-between; transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
            <div>
              <span class="badge badge-info" style="font-size:0.7rem;"><?php echo htmlspecialchars($p['categoria'] ?? 'General'); ?></span>
              <h5 style="margin:0.4rem 0 0.2rem 0; font-size:0.95rem; line-height:1.2;"><?php echo htmlspecialchars($p['nombre']); ?></h5>
              <small style="color:var(--text-muted); display:block; font-size:0.75rem;">Cód: <?php echo htmlspecialchars($p['codigo_barra']); ?></small>
              <div style="font-size:1.15rem; font-weight:800; color:var(--primary); margin:0.3rem 0;">
                <?php echo formatoMoneda($p['precio_venta']); ?>
              </div>
              <small style="color:<?php echo $p['stock'] <= $p['stock_minimo'] ? 'var(--danger)' : 'var(--text-muted)'; ?>; font-weight:600;">
                Stock: <?php echo $p['stock']; ?> <?php echo $p['unidad']; ?>
              </small>
            </div>

            <form action="" method="POST" style="margin-top:0.75rem; display:flex; gap:0.4rem;">
              <input type="hidden" name="accion" value="agregar">
              <input type="hidden" name="producto_id" value="<?php echo $p['id']; ?>">
              <input type="number" name="cantidad" value="1" min="1" max="<?php echo $p['stock']; ?>" style="width:55px; padding:0.35rem; border:1px solid var(--border); border-radius:6px; text-align:center;">
              <button type="submit" class="btn btn-primary btn-sm" style="flex:1;">+ Agregar</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column:1/-1; text-align:center; color:var(--text-muted); padding:2rem;">
          No se encontraron productos disponibles con ese criterio.
        </p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ticket de Venta / Carrito -->
  <div class="card" style="box-shadow:var(--shadow-md);">
    <div class="card-header">
      <h4>🧾 Ticket de Compra Actual</h4>
      <?php if (!empty($_SESSION['carrito'])): ?>
        <a href="<?php echo BASE_URL; ?>pages/ventas.php?vaciar=1" class="btn btn-outline btn-sm confirm-action" data-confirm="¿Vaciar la venta actual?">🗑️ Limpiar</a>
      <?php endif; ?>
    </div>

    <div style="min-height: 220px; max-height: 280px; overflow-y: auto; margin-bottom: 1.25rem;">
      <?php if (!empty($_SESSION['carrito'])): ?>
        <table class="custom-table" style="font-size:0.85rem;">
          <thead>
            <tr>
              <th>Prod</th>
              <th>Cant</th>
              <th>P.Unit</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($_SESSION['carrito'] as $it): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($it['nombre']); ?></strong></td>
                <td><?php echo $it['cantidad']; ?></td>
                <td><?php echo formatoMoneda($it['precio']); ?></td>
                <td style="font-weight:700;"><?php echo formatoMoneda($it['subtotal']); ?></td>
                <td>
                  <a href="<?php echo BASE_URL; ?>pages/ventas.php?quitar=<?php echo $it['id']; ?>" style="color:var(--danger); font-weight:bold;">&times;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
          <div style="font-size:2.5rem; margin-bottom:0.5rem;">🛍️</div>
          <p>El ticket está vacío. Seleccione productos del catálogo para agregar a la venta.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Formulario de Cobro -->
    <div style="border-top:2px dashed var(--border); padding-top:1.25rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <span style="font-size:1.1rem; font-weight:700;">TOTAL A PAGAR:</span>
        <span style="font-size:1.8rem; font-weight:800; color:var(--primary);"><?php echo formatoMoneda($total_carrito); ?></span>
      </div>

      <?php if (!empty($_SESSION['carrito'])): ?>
        <form action="" method="POST">
          <input type="hidden" name="accion" value="procesar_venta">
          <input type="hidden" id="total_venta_hidden" value="<?php echo $total_carrito; ?>">

          <div class="form-group">
            <label class="form-label">Cliente (Opcional):</label>
            <input type="text" name="cliente_nombre" class="form-control" value="Cliente General">
          </div>

          <div class="form-group">
            <label class="form-label">Método de Pago:</label>
            <select name="metodo_pago" id="metodo_pago" class="form-select">
              <option value="efectivo">💵 Efectivo</option>
              <option value="transferencia">📲 Transferencia / Débito</option>
              <option value="tarjeta">💳 Tarjeta de Crédito</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Monto Recibido ($):</label>
            <input type="number" step="0.01" min="0" name="monto_recibido" id="monto_recibido" class="form-control" placeholder="Ej: <?php echo $total_carrito; ?>" value="<?php echo $total_carrito; ?>" required>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1.25rem;">
            <span style="font-weight:600; font-size:0.95rem;">Vuelto / Cambio a entregar:</span>
            <span id="vuelto_display" style="font-size:1.3rem; font-weight:800; color:var(--primary);">$0</span>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%; padding:0.9rem; font-size:1.05rem;">
            💳 Confirmar y Registrar Venta
          </button>
        </form>
      <?php endif; ?>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
