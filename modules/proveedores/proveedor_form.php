<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$prov = $stmt->fetch();
if (!$prov) redirect(BASE_URL . '/modules/proveedores/proveedores.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $razon = trim($_POST['razon_social'] ?? '');
        if (empty($razon)) {
            $error = 'La razón social es obligatoria';
        } else {
            $db->prepare("UPDATE proveedores SET razon_social = ?, ruc = ?, contacto = ?, telefono = ?, email = ?, direccion = ?, sitio_web = ?, notas = ?, activo = ? WHERE id = ?")->execute([
                $razon, trim($_POST['ruc'] ?? ''), trim($_POST['contacto'] ?? ''), trim($_POST['telefono'] ?? ''),
                trim($_POST['email'] ?? ''), trim($_POST['direccion'] ?? ''), trim($_POST['sitio_web'] ?? ''),
                trim($_POST['notas'] ?? ''), isset($_POST['activo']) ? 1 : 0, $prov['id']
            ]);
            registrarAuditoria(USUARIO_ID, 'editar', 'proveedores', $prov['id'], "Proveedor editado: $razon");
            $success = 'Proveedor actualizado';
        }
    }
}

$titulo = 'Editar Proveedor';
$subtitulo = 'Proveedores';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Editar Proveedor</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?><script>setTimeout(() => location.href = '<?= BASE_URL ?>/modules/proveedores/proveedores.php', 1500);</script></div><?php endif; ?>
        <form method="POST" class="row g-3">
            <?= campoCSRF() ?>
            <div class="col-md-6">
                <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                <input type="text" name="razon_social" class="form-control" value="<?= h($prov['razon_social']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">RUC</label>
                <input type="text" name="ruc" class="form-control" value="<?= h($prov['ruc']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contacto</label>
                <input type="text" name="contacto" class="form-control" value="<?= h($prov['contacto']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= h($prov['telefono']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= h($prov['email']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Dirección</label>
                <textarea name="direccion" class="form-control" rows="2"><?= h($prov['direccion']) ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sitio Web</label>
                <input type="url" name="sitio_web" class="form-control" value="<?= h($prov['sitio_web']) ?>">
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch mt-4">
                    <input type="checkbox" name="activo" class="form-check-input" id="activo" <?= $prov['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control" rows="2"><?= h($prov['notas']) ?></textarea>
            </div>
            <div class="col-12">
                <hr>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Actualizar</button>
                <a href="<?= BASE_URL ?>/modules/proveedores/proveedores.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
