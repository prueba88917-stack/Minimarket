<?php
$titulo_pagina = "Registrarse";
require_once __DIR__ . '/../db/conexion.php';

$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $telefono = sanitizar($_POST['telefono'] ?? '');
    $rol = 'empleado'; // Registro público por defecto con rol de empleado/cajero

    if (empty($nombre) || empty($correo) || empty($password)) {
        $mensaje_error = 'Por favor complete todos los campos obligatorios.';
    } elseif (strlen($password) < 6) {
        $mensaje_error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        if (isset($pdo)) {
            // Verificar si el correo ya existe
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
            $check->execute([$correo]);
            if ($check->fetch()) {
                $mensaje_error = 'El correo electrónico ya está registrado. Intente iniciar sesión.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password, rol, telefono, estado) VALUES (?, ?, ?, ?, ?, 1)");
                if ($stmt->execute([$nombre, $correo, $hash, $rol, $telefono])) {
                    $nuevo_id = $pdo->lastInsertId();
                    
                    // Iniciar sesión automáticamente
                    $_SESSION['usuario_id'] = $nuevo_id;
                    $_SESSION['usuario_nombre'] = $nombre;
                    $_SESSION['correo'] = $correo;
                    $_SESSION['rol'] = $rol;

                    header("Location: " . BASE_URL . "index.php");
                    exit;
                } else {
                    $mensaje_error = 'No fue posible registrar la cuenta. Intente nuevamente.';
                }
            }
        } else {
            $mensaje_error = 'Error de conexión con la base de datos: ' . ($db_error ?? 'desconocido');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 520px; margin: 2rem auto;">
  <div class="card" style="padding: 2.25rem; box-shadow: var(--shadow-lg);">
    
    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="font-size: 3rem; margin-bottom: 0.5rem;">📝</div>
      <h2>Crear Cuenta</h2>
      <p style="color: var(--text-muted); font-size: 0.92rem;">
        Registro de nuevo usuario para Mini Abarrotes Don Luis
      </p>
    </div>

    <?php if (!empty($mensaje_error)): ?>
      <div class="alert alert-danger">
        ⚠️ <?php echo htmlspecialchars($mensaje_error); ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <div class="form-group">
        <label class="form-label" for="nombre">Nombre Completo:</label>
        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Marcela Gómez" required autofocus value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" class="form-control" placeholder="ejemplo: mgomez@donluis.com" required value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="telefono">Teléfono (Opcional):</label>
        <input type="text" id="telefono" name="telefono" class="form-control" placeholder="+56 9 1234 5678" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña:</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 1rem; margin-top: 1rem;">
        ✅ Registrarse e Ingresar
      </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
      ¿Ya tienes una cuenta? <a href="<?php echo BASE_URL; ?>pages/login.php" style="font-weight: 700;">Inicia Sesión aquí</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
