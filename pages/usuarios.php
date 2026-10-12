<?php
$titulo_pagina = "Gestión de Usuarios";
require_once __DIR__ . '/../db/conexion.php';
requerirAdmin(); // Solo accesible para Administrador

$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_usuario'])) {
    $accion = $_POST['accion_usuario'];

    if ($accion === 'crear') {
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $correo = sanitizar($_POST['correo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $rol = in_array($_POST['rol'], ['administrador', 'empleado']) ? $_POST['rol'] : 'empleado';
        $telefono = sanitizar($_POST['telefono'] ?? '');

        if (empty($nombre) || empty($correo) || empty($password)) {
            $mensaje_error = "Por favor complete nombre, correo y contraseña.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password, rol, telefono) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $correo, $hash, $rol, $telefono]);
                $mensaje_exito = "Usuario '{$nombre}' creado con éxito.";
            } catch (PDOException $e) {
                $mensaje_error = "El correo electrónico ya se encuentra registrado.";
            }
        }
    } elseif ($accion === 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $correo = sanitizar($_POST['correo'] ?? '');
        $rol = in_array($_POST['rol'], ['administrador', 'empleado']) ? $_POST['rol'] : 'empleado';
        $telefono = sanitizar($_POST['telefono'] ?? '');
        $password = trim($_POST['password'] ?? '');

        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ?, rol = ?, telefono = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombre, $correo, $rol, $telefono, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ?, rol = ?, telefono = ? WHERE id = ?");
                $stmt->execute([$nombre, $correo, $rol, $telefono, $id]);
            }
            $mensaje_exito = "Usuario actualizado correctamente.";
        } catch (PDOException $e) {
            $mensaje_error = "Error al actualizar usuario: " . $e->getMessage();
        }
    } elseif ($accion === 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        if ($id === $_SESSION['usuario_id']) {
            $mensaje_error = "No puede eliminar su propio usuario activo.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje_exito = "Usuario eliminado del sistema.";
            } catch (PDOException $e) {
                $mensaje_error = "No se puede eliminar el usuario porque tiene registros de ventas o caja asociados. Puede inactivarlo en su lugar.";
            }
        }
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2>👥 Gestión de Usuarios y Roles</h2>
    <p style="color:var(--text-muted); font-size:0.92rem;">
      Administración de credenciales de acceso, asignación de roles (Administrador y Empleado) y permisos.
    </p>
  </div>
  <button onclick="document.getElementById('modal-usuario').style.display='block'" class="btn btn-primary">
    ➕ Nuevo Usuario
  </button>
</div>

<?php if (!empty($mensaje_exito)): ?>
  <div class="alert alert-success">✅ <?php echo htmlspecialchars($mensaje_exito); ?></div>
<?php endif; ?>
<?php if (!empty($mensaje_error)): ?>
  <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($mensaje_error); ?></div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="custom-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Correo Electrónico</th>
          <th>Rol de Acceso</th>
          <th>Teléfono</th>
          <th>Fecha Registro</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td>#<?php echo $u['id']; ?></td>
            <td><strong><?php echo htmlspecialchars($u['nombre']); ?></strong></td>
            <td><?php echo htmlspecialchars($u['correo']); ?></td>
            <td>
              <span class="role-tag <?php echo $u['rol'] === 'administrador' ? 'admin' : 'empleado'; ?>">
                <?php echo strtoupper($u['rol']); ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($u['telefono'] ?? '-'); ?></td>
            <td><?php echo date('d/m/Y', strtotime($u['creado_en'])); ?></td>
            <td>
              <div style="display:flex; gap:0.4rem;">
                <button class="btn btn-outline btn-sm" onclick='abrirEditarUsuario(<?php echo json_encode($u); ?>)'>✏️</button>
                <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                  <form action="" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro de eliminar este usuario?');">
                    <input type="hidden" name="accion_usuario" value="eliminar">
                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Crear Usuario -->
<div id="modal-usuario" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; overflow-y:auto;">
  <div style="background:white; max-width:500px; margin:4rem auto; padding:2rem; border-radius:12px; box-shadow:var(--shadow-lg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
      <h3>➕ Registrar Nuevo Usuario</h3>
      <button onclick="document.getElementById('modal-usuario').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>

    <form action="" method="POST">
      <input type="hidden" name="accion_usuario" value="crear">

      <div class="form-group">
        <label class="form-label">Nombre Completo:</label>
        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Marcela Gómez">
      </div>

      <div class="form-group">
        <label class="form-label">Correo Electrónico:</label>
        <input type="email" name="correo" class="form-control" required placeholder="Ej: mgomez@donluis.com">
      </div>

      <div class="form-group">
        <label class="form-label">Contraseña:</label>
        <input type="password" name="password" class="form-control" required placeholder="Mínimo 6 caracteres">
      </div>

      <div class="form-group">
        <label class="form-label">Rol de Acceso:</label>
        <select name="rol" class="form-select">
          <option value="empleado">Empleado / Cajero (Ventas, caja y ver stock)</option>
          <option value="administrador">Administrador (Control total del sistema)</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Teléfono:</label>
        <input type="text" name="telefono" class="form-control" placeholder="+56 9 ...">
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <button type="button" onclick="document.getElementById('modal-usuario').style.display='none'" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Usuario</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Usuario -->
<div id="modal-editar-usr" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; overflow-y:auto;">
  <div style="background:white; max-width:500px; margin:4rem auto; padding:2rem; border-radius:12px; box-shadow:var(--shadow-lg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
      <h3>✏️ Modificar Usuario</h3>
      <button onclick="document.getElementById('modal-editar-usr').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>

    <form action="" method="POST">
      <input type="hidden" name="accion_usuario" value="editar">
      <input type="hidden" name="id" id="edit_u_id">

      <div class="form-group">
        <label class="form-label">Nombre Completo:</label>
        <input type="text" name="nombre" id="edit_u_nombre" class="form-control" required>
      </div>

      <div class="form-group">
        <label class="form-label">Correo Electrónico:</label>
        <input type="email" name="correo" id="edit_u_correo" class="form-control" required>
      </div>

      <div class="form-group">
        <label class="form-label">Nueva Contraseña (dejar en blanco para no cambiar):</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••">
      </div>

      <div class="form-group">
        <label class="form-label">Rol de Acceso:</label>
        <select name="rol" id="edit_u_rol" class="form-select">
          <option value="empleado">Empleado / Cajero</option>
          <option value="administrador">Administrador</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Teléfono:</label>
        <input type="text" name="telefono" id="edit_u_telefono" class="form-control">
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <button type="button" onclick="document.getElementById('modal-editar-usr').style.display='none'" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirEditarUsuario(u) {
  document.getElementById('edit_u_id').value = u.id;
  document.getElementById('edit_u_nombre').value = u.nombre;
  document.getElementById('edit_u_correo').value = u.correo;
  document.getElementById('edit_u_rol').value = u.rol;
  document.getElementById('edit_u_telefono').value = u.telefono || '';
  document.getElementById('modal-editar-usr').style.display = 'block';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
