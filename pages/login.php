<?php
$titulo_pagina = "Iniciar Sesión";
require_once __DIR__ . '/../db/conexion.php';

$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($correo) || empty($password)) {
        $mensaje_error = 'Por favor complete todos los campos.';
    } else {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND estado = 1 LIMIT 1");
            $stmt->execute([$correo]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($password, $usuario['password'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['rol'] = $usuario['rol'];

                header("Location: " . BASE_URL . "index.php");
                exit;
            } else {
                $mensaje_error = 'Credenciales inválidas. Compruebe el correo y la contraseña.';
            }
        } else {
            $mensaje_error = 'Error de conexión con la base de datos: ' . ($db_error ?? 'desconocido');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 480px; margin: 2rem auto;">
  <div class="card" style="padding: 2.25rem; box-shadow: var(--shadow-lg);">
    
    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="font-size: 3rem; margin-bottom: 0.5rem;">🏪</div>
      <h2>Iniciar Sesión</h2>
      <p style="color: var(--text-muted); font-size: 0.92rem;">
        Acceso al Sistema Mini Abarrotes Don Luis
      </p>
    </div>

    <?php if (!empty($mensaje_error)): ?>
      <div class="alert alert-danger">
        ⚠️ <?php echo htmlspecialchars($mensaje_error); ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <div class="form-group">
        <label class="form-label" for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" class="form-control" placeholder="ejemplo: admin@donluis.com" required autofocus value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña:</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 1rem; margin-top: 1rem;">
        🔑 Ingresar al Sistema
      </button>
    </form>

    <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px dashed var(--border); font-size: 0.85rem; color: var(--text-muted);">
      <p><strong>Credenciales de Demostración:</strong></p>
      <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; background: #f8fafc; padding: 0.6rem; border-radius: 6px;">
        <div>
          <span class="role-tag admin">Admin</span><br>
          <code>admin@donluis.com</code><br>
          Clave: <code>admin123</code>
        </div>
        <div>
          <span class="role-tag empleado">Empleado</span><br>
          <code>cajero@donluis.com</code><br>
          Clave: <code>empleado123</code>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
