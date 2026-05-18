<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token de seguridad inválido';
    } else {
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $passActual = $_POST['password_actual'] ?? '';
        $passNueva = $_POST['password_nueva'] ?? '';

        if (empty($nombre)) {
            $error = 'El nombre es obligatorio';
        } else {
            if (!empty($passActual) || !empty($passNueva)) {
                $stmt = $db->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
                $stmt->execute([USUARIO_ID]);
                $hash = $stmt->fetchColumn();
                if (!password_verify($passActual, $hash)) {
                    $error = 'La contraseña actual no es correcta';
                } elseif (strlen($passNueva) < 6) {
                    $error = 'La nueva contraseña debe tener al menos 6 caracteres';
                } else {
                    $db->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, telefono = ?, password_hash = ? WHERE id = ?")->execute([$nombre, $email, $telefono, password_hash($passNueva, PASSWORD_BCRYPT), USUARIO_ID]);
                    $success = 'Perfil actualizado correctamente';
                }
            } else {
                $db->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, telefono = ? WHERE id = ?")->execute([$nombre, $email, $telefono, USUARIO_ID]);
                $success = 'Perfil actualizado correctamente';
            }
        }
    }
}

$titulo = 'Mi Perfil';
$subtitulo = 'Perfil';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Configuración de Perfil</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?= campoCSRF() ?>
            <div class="col-md-6">
                <label class="form-label">Usuario</label>
                <input type="text" class="form-control" value="<?= h(USUARIO_USERNAME) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Rol</label>
                <?php $rol = $db->query("SELECT nombre FROM roles WHERE id = " . USUARIO_ROL)->fetchColumn(); ?>
                <input type="text" class="form-control" value="<?= h($rol) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                <input type="text" name="nombre_completo" class="form-control" value="<?= h(USUARIO_NOMBRE) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= h($usuario['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= h($usuario['telefono'] ?? '') ?>">
            </div>
            <div class="col-12"><hr><h6>Cambiar Contraseña</h6></div>
            <div class="col-md-6">
                <label class="form-label">Contraseña Actual</label>
                <input type="password" name="password_actual" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nueva Contraseña</label>
                <input type="password" name="password_nueva" class="form-control" minlength="6">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
