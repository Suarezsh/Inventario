<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

verificarPermisoO(USUARIO_ROL, 'usuarios', 'crear');

$db = getDB();
$editar = false;
$usuario = [
    'id' => '', 'username' => '', 'nombre_completo' => '', 'email' => '',
    'telefono' => '', 'id_rol' => 3, 'activo' => 1
];

if (isset($_GET['id'])) {
    verificarPermisoO(USUARIO_ROL, 'usuarios', 'editar');
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $usuario = $stmt->fetch();
    if (!$usuario) {
        redirect(BASE_URL . '/modules/usuarios/usuarios.php');
    }
    $editar = true;
}

$roles = $db->query("SELECT * FROM roles WHERE activo = 1 ORDER BY nivel DESC")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token de seguridad inválido';
    } else {
        $username = trim($_POST['username'] ?? '');
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $idRol = (int) ($_POST['id_rol'] ?? 3);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($nombre)) {
            $error = 'Complete los campos obligatorios';
        } else {
            $check = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ? AND id != ?");
            $check->execute([$username, $editar ? $usuario['id'] : 0]);
            if ($check->fetchColumn() > 0) {
                $error = 'El nombre de usuario ya existe';
            } else {
                if ($editar) {
                    $sql = "UPDATE usuarios SET username = ?, nombre_completo = ?, email = ?, telefono = ?, id_rol = ?, activo = ? WHERE id = ?";
                    $params = [$username, $nombre, $email, $telefono, $idRol, $activo, $usuario['id']];
                    if (!empty($password)) {
                        $sql = "UPDATE usuarios SET username = ?, nombre_completo = ?, email = ?, telefono = ?, id_rol = ?, activo = ?, password_hash = ? WHERE id = ?";
                        $params = [$username, $nombre, $email, $telefono, $idRol, $activo, password_hash($password, PASSWORD_BCRYPT), $usuario['id']];
                    }
                    $db->prepare($sql)->execute($params);
                    registrarAuditoria(USUARIO_ID, 'editar', 'usuarios', $usuario['id'], "Usuario editado: $username");
                    $success = 'Usuario actualizado correctamente';
                } else {
                    if (empty($password)) {
                        $error = 'La contraseña es obligatoria';
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("INSERT INTO usuarios (username, password_hash, nombre_completo, email, telefono, id_rol, activo, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$username, $hash, $nombre, $email, $telefono, $idRol, $activo, USUARIO_ID]);
                        registrarAuditoria(USUARIO_ID, 'crear', 'usuarios', (int)$db->lastInsertId(), "Usuario creado: $username");
                        $success = 'Usuario creado correctamente';
                    }
                }
            }
        }
    }
}

$titulo = $editar ? 'Editar Usuario' : 'Nuevo Usuario';
$subtitulo = 'Usuarios';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><?= $titulo ?></h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
        <script>setTimeout(() => location.href = '<?= BASE_URL ?>/modules/usuarios/usuarios.php', 1500);</script>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?= campoCSRF() ?>
            <div class="col-md-6">
                <label class="form-label">Usuario <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" value="<?= h($usuario['username']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                <input type="text" name="nombre_completo" class="form-control" value="<?= h($usuario['nombre_completo']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= h($usuario['email']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= h($usuario['telefono']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Rol</label>
                <select name="id_rol" class="form-select">
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $usuario['id_rol'] ? 'selected' : '' ?>><?= h($r['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <div class="form-check form-switch mt-2">
                    <input type="checkbox" name="activo" class="form-check-input" id="activo" <?= $usuario['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label"><?= $editar ? 'Nueva Contraseña (dejar vacío para mantener)' : 'Contraseña' ?> <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" <?= $editar ? '' : 'required' ?> minlength="6">
            </div>
            <div class="col-12">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?= $editar ? 'Actualizar' : 'Crear' ?> Usuario
                </button>
                <a href="<?= BASE_URL ?>/modules/usuarios/usuarios.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
