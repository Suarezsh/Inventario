<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM almacenes WHERE id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$a = $stmt->fetch();
if (!$a) redirect(BASE_URL . '/modules/almacenes/almacenes.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $db->prepare("UPDATE almacenes SET codigo = ?, nombre = ?, ubicacion = ?, encargado = ?, telefono = ?, activo = ? WHERE id = ?")->execute([
            trim($_POST['codigo'] ?? ''), trim($_POST['nombre'] ?? ''), trim($_POST['ubicacion'] ?? ''),
            trim($_POST['encargado'] ?? ''), trim($_POST['telefono'] ?? ''), isset($_POST['activo']) ? 1 : 0, $a['id']
        ]);
        registrarAuditoria(USUARIO_ID, 'editar', 'almacenes', $a['id'], "Almacén editado");
        $success = 'Almacén actualizado';
    }
}

$titulo = 'Editar Almacén';
$subtitulo = 'Almacenes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Editar Almacén</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?><script>setTimeout(() => location.href = '<?= BASE_URL ?>/modules/almacenes/almacenes.php', 1500);</script></div><?php endif; ?>
        <form method="POST" class="row g-3">
            <?= campoCSRF() ?>
            <div class="col-md-6">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" value="<?= h($a['codigo']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= h($a['nombre']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ubicación</label>
                <input type="text" name="ubicacion" class="form-control" value="<?= h($a['ubicacion']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Encargado</label>
                <input type="text" name="encargado" class="form-control" value="<?= h($a['encargado']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= h($a['telefono']) ?>">
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch mt-4">
                    <input type="checkbox" name="activo" class="form-check-input" id="activo" <?= $a['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
            </div>
            <div class="col-12">
                <hr>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Actualizar</button>
                <a href="<?= BASE_URL ?>/modules/almacenes/almacenes.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
