<?php
$titulo_pagina = "Gestión de Productos e Inventario";
require_once __DIR__ . '/../db/conexion.php';
requerirLogin();

$mensaje_exito = '';
$mensaje_error = '';

// Procesar formulario de Guardar / Editar Producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_producto'])) {
    // Si no es admin y trata de crear/eliminar, bloquear
    if (!esAdmin() && ($_POST['accion_producto'] === 'crear' || $_POST['accion_producto'] === 'eliminar')) {
        $mensaje_error = "Solo los Administradores pueden crear o eliminar productos.";
    } else {
        $accion = $_POST['accion_producto'];
        $codigo = sanitizar($_POST['codigo_barra'] ?? '');
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $categoria_id = intval($_POST['categoria_id'] ?? 1);
        $precio_compra = floatval($_POST['precio_compra'] ?? 0);
        $precio_venta = floatval($_POST['precio_venta'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $stock_minimo = intval($_POST['stock_minimo'] ?? 5);
        $unidad = sanitizar($_POST['unidad'] ?? 'unidad');

        if ($accion === 'crear') {
            if (empty($codigo) || empty($nombre) || $precio_venta <= 0) {
                $mensaje_error = "Complete los campos obligatorios y asegure un precio de venta válido.";
            } else {
                try {
                    $stmt_ins = $pdo->prepare("INSERT INTO productos (codigo_barra, nombre, categoria_id, precio_compra, precio_venta, stock, stock_minimo, unidad) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_ins->execute([$codigo, $nombre, $categoria_id, $precio_compra, $precio_venta, $stock, $stock_minimo, $unidad]);
                    $mensaje_exito = "Producto '{$nombre}' registrado con éxito en el catálogo.";
                } catch (PDOException $e) {
                    $mensaje_error = "Error al registrar producto (¿Código de barra duplicado?): " . $e->getMessage();
                }
            }
        } elseif ($accion === 'editar') {
            $id = intval($_POST['id'] ?? 0);
            try {
                $stmt_up = $pdo->prepare("UPDATE productos SET codigo_barra = ?, nombre = ?, categoria_id = ?, precio_compra = ?, precio_venta = ?, stock = ?, stock_minimo = ?, unidad = ? WHERE id = ?");
                $stmt_up->execute([$codigo, $nombre, $categoria_id, $precio_compra, $precio_venta, $stock, $stock_minimo, $unidad, $id]);
                $mensaje_exito = "Producto '{$nombre}' actualizado correctamente.";
            } catch (PDOException $e) {
                $mensaje_error = "Error al actualizar producto: " . $e->getMessage();
            }
        } elseif ($accion === 'eliminar') {
            $id = intval($_POST['id'] ?? 0);
            try {
                $stmt_del = $pdo->prepare("DELETE FROM productos WHERE id = ?");
                $stmt_del->execute([$id]);
                $mensaje_exito = "Producto eliminado correctamente del catálogo.";
            } catch (PDOException $e) {
                $mensaje_error = "No se puede eliminar el producto porque ya tiene ventas asociadas.";
            }
        }
    }
}

// Búsqueda y Filtros
$busqueda = sanitizar($_GET['q'] ?? '');
$filtro_cat = intval($_GET['cat'] ?? 0);
$filtro_alerta = isset($_GET['alerta']) ? 1 : 0;

$sql = "SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";
$params = [];

if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo_barra LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if ($filtro_cat > 0) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $filtro_cat;
}

if ($filtro_alerta === 1) {
    $sql .= " AND p.stock <= p.stock_minimo";
}

$sql .= " ORDER BY p.id DESC";
$stmt_prod = $pdo->prepare($sql);
$stmt_prod->execute($params);
$productos = $stmt_prod->fetchAll();

// Categorías para select
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2>📦 Gestión de Productos e Inventario</h2>
    <p style="color:var(--text-muted); font-size:0.92rem;">
      Catálogo completo, control de entradas, salidas y alertas automáticas de bajo stock.
    </p>
  </div>
  
  <?php if (esAdmin()): ?>
    <button onclick="document.getElementById('modal-crear').style.display='block'" class="btn btn-primary">
      ➕ Nuevo Producto
    </button>
  <?php else: ?>
    <span class="badge badge-info" style="font-size:0.85rem; padding:0.5rem 0.8rem;">
      👁️ Modo Empleado: Solo Lectura y Consulta de Stock
    </span>
  <?php endif; ?>
</div>

<?php if (!empty($mensaje_exito)): ?>
  <div class="alert alert-success">✅ <?php echo htmlspecialchars($mensaje_exito); ?></div>
<?php endif; ?>
<?php if (!empty($mensaje_error)): ?>
  <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($mensaje_error); ?></div>
<?php endif; ?>

<!-- Barra de Filtros y Búsqueda -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form action="" method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
    <div style="flex:2; min-width:200px;">
      <label class="form-label">Buscar Producto:</label>
      <input type="text" name="q" class="form-control" placeholder="Por nombre o código de barra..." value="<?php echo htmlspecialchars($busqueda); ?>">
    </div>

    <div style="flex:1; min-width:180px;">
      <label class="form-label">Categoría:</label>
      <select name="cat" class="form-select">
        <option value="0">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?php echo $c['id']; ?>" <?php echo $filtro_cat == $c['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($c['nombre']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; align-items:center; gap:0.5rem; padding-bottom:0.6rem;">
      <input type="checkbox" name="alerta" id="chk_alerta" value="1" <?php echo $filtro_alerta ? 'checked' : ''; ?>>
      <label for="chk_alerta" style="font-size:0.88rem; font-weight:600; color:var(--danger); cursor:pointer;">⚠️ Solo Bajo Stock</label>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">Filtrar</button>
      <a href="<?php echo BASE_URL; ?>pages/productos.php" class="btn btn-outline">Limpiar</a>
    </div>
  </form>
</div>

<!-- Tabla de Productos -->
<div class="card">
  <div class="table-responsive">
    <table class="custom-table">
      <thead>
        <tr>
          <th>Código</th>
          <th>Nombre del Producto</th>
          <th>Categoría</th>
          <th>P. Compra</th>
          <th>P. Venta</th>
          <th>Stock Actual</th>
          <th>Mínimo</th>
          <th>Estado</th>
          <?php if (esAdmin()): ?>
            <th>Acciones</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($productos)): ?>
          <?php foreach ($productos as $p): 
            $bajo_stock = $p['stock'] <= $p['stock_minimo'];
          ?>
            <tr>
              <td><code><?php echo htmlspecialchars($p['codigo_barra']); ?></code></td>
              <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
              <td><span class="badge badge-info"><?php echo htmlspecialchars($p['categoria_nombre'] ?? 'General'); ?></span></td>
              <td><?php echo formatoMoneda($p['precio_compra']); ?></td>
              <td style="font-weight:700; color:var(--primary);"><?php echo formatoMoneda($p['precio_venta']); ?></td>
              <td style="font-weight:700; font-size:1rem; color:<?php echo $bajo_stock ? 'var(--danger)' : 'inherit'; ?>;">
                <?php echo $p['stock']; ?> <?php echo $p['unidad']; ?>
              </td>
              <td><?php echo $p['stock_minimo']; ?></td>
              <td>
                <?php if ($p['stock'] == 0): ?>
                  <span class="badge badge-danger">AGOTADO</span>
                <?php elseif ($bajo_stock): ?>
                  <span class="badge badge-warning">BAJO STOCK</span>
                <?php else: ?>
                  <span class="badge badge-success">DISPONIBLE</span>
                <?php endif; ?>
              </td>
              <?php if (esAdmin()): ?>
                <td>
                  <div style="display:flex; gap:0.4rem;">
                    <!-- Botón Editar -->
                    <button class="btn btn-outline btn-sm" onclick='abrirModalEditar(<?php echo json_encode($p); ?>)'>✏️</button>
                    <!-- Formulario Eliminar -->
                    <form action="" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro de eliminar este producto?');">
                      <input type="hidden" name="accion_producto" value="eliminar">
                      <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                      <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                    </form>
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="<?php echo esAdmin() ? '9' : '8'; ?>" style="text-align:center; padding:2rem; color:var(--text-muted);">
              No se encontraron productos registrados con los criterios seleccionados.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (esAdmin()): ?>
<!-- Modal Crear Producto -->
<div id="modal-crear" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; overflow-y:auto;">
  <div style="background:white; max-width:550px; margin:4rem auto; padding:2rem; border-radius:12px; box-shadow:var(--shadow-lg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
      <h3>➕ Registrar Nuevo Producto</h3>
      <button onclick="document.getElementById('modal-crear').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>

    <form action="" method="POST">
      <input type="hidden" name="accion_producto" value="crear">

      <div class="form-group">
        <label class="form-label">Código de Barra:</label>
        <input type="text" name="codigo_barra" class="form-control" required placeholder="Ej: 78010099">
      </div>

      <div class="form-group">
        <label class="form-label">Nombre del Producto:</label>
        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Café Taragui 250g">
      </div>

      <div class="form-group">
        <label class="form-label">Categoría:</label>
        <select name="categoria_id" class="form-select">
          <?php foreach ($categorias as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label class="form-label">Precio Compra ($):</label>
          <input type="number" step="0.01" name="precio_compra" class="form-control" required value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Precio Venta ($):</label>
          <input type="number" step="0.01" name="precio_venta" class="form-control" required value="1000">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label class="form-label">Stock:</label>
          <input type="number" name="stock" class="form-control" required value="20">
        </div>
        <div class="form-group">
          <label class="form-label">Stock Mínimo:</label>
          <input type="number" name="stock_minimo" class="form-control" required value="5">
        </div>
        <div class="form-group">
          <label class="form-label">Unidad:</label>
          <input type="text" name="unidad" class="form-control" value="unidad">
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <button type="button" onclick="document.getElementById('modal-crear').style.display='none'" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Producto</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Producto -->
<div id="modal-editar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; overflow-y:auto;">
  <div style="background:white; max-width:550px; margin:4rem auto; padding:2rem; border-radius:12px; box-shadow:var(--shadow-lg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
      <h3>✏️ Modificar Producto</h3>
      <button onclick="document.getElementById('modal-editar').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>

    <form action="" method="POST">
      <input type="hidden" name="accion_producto" value="editar">
      <input type="hidden" name="id" id="edit_id">

      <div class="form-group">
        <label class="form-label">Código de Barra:</label>
        <input type="text" name="codigo_barra" id="edit_codigo" class="form-control" required>
      </div>

      <div class="form-group">
        <label class="form-label">Nombre del Producto:</label>
        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
      </div>

      <div class="form-group">
        <label class="form-label">Categoría:</label>
        <select name="categoria_id" id="edit_categoria_id" class="form-select">
          <?php foreach ($categorias as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label class="form-label">Precio Compra ($):</label>
          <input type="number" step="0.01" name="precio_compra" id="edit_precio_compra" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Precio Venta ($):</label>
          <input type="number" step="0.01" name="precio_venta" id="edit_precio_venta" class="form-control" required>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label class="form-label">Stock:</label>
          <input type="number" name="stock" id="edit_stock" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Stock Mínimo:</label>
          <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Unidad:</label>
          <input type="text" name="unidad" id="edit_unidad" class="form-control">
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <button type="button" onclick="document.getElementById('modal-editar').style.display='none'" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModalEditar(p) {
  document.getElementById('edit_id').value = p.id;
  document.getElementById('edit_codigo').value = p.codigo_barra;
  document.getElementById('edit_nombre').value = p.nombre;
  document.getElementById('edit_categoria_id').value = p.categoria_id;
  document.getElementById('edit_precio_compra').value = p.precio_compra;
  document.getElementById('edit_precio_venta').value = p.precio_venta;
  document.getElementById('edit_stock').value = p.stock;
  document.getElementById('edit_stock_minimo').value = p.stock_minimo;
  document.getElementById('edit_unidad').value = p.unidad;
  document.getElementById('modal-editar').style.display = 'block';
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
