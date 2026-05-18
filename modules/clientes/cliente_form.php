<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$cli = $stmt->fetch();
if (!$cli) redirect(BASE_URL . '/modules/clientes/clientes.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            $error = 'El nombre es obligatorio';
        } else {
            $db->prepare("UPDATE clientes SET tipo_documento = ?, numero_documento = ?, nombre = ?, direccion = ?, telefono = ?, email = ?, tipo_cliente = ?, limite_credito = ?, dias_credito = ?, descuento_especial = ?, notas = ?, activo = ? WHERE id = ?")->execute([
                $_POST['tipo_documento'] ?? 'DNI', trim($_POST['numero_documento'] ?? ''), $nombre,
                trim($_POST['direccion'] ?? ''), trim($_POST['telefono'] ?? ''), trim($_POST['email'] ?? ''),
                $_POST['tipo_cliente'] ?? 'minorista', (float)($_POST['limite_credito'] ?? 0), (int)($_POST['dias_credito'] ?? 0),
                (float)($_POST['descuento_especial'] ?? 0), trim($_POST['notas'] ?? ''), isset($_POST['activo']) ? 1 : 0
            ]);
            registrarAuditoria(USUARIO_ID, 'editar', 'clientes', $cli['id'], "Cliente editado: $nombre");
            $success = 'Cliente actualizado';
        }
    }
}

$titulo = 'Editar Cliente';
$subtitulo = 'Clientes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Editar Cliente</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?><script>setTimeout(() => location.href = '<?= BASE_URL ?>/modules/clientes/clientes.php', 1500);</script></div><?php endif; ?>
        <form method="POST" class="row g-3">
            <?= campoCSRF() ?>
            <div class="col-md-6">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control" value="<?= h($cli['nombre']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo Documento</label>
                <select name="tipo_documento" class="form-select">
                    <?php foreach (['DNI', 'RUC', 'CE', 'Pasaporte'] as $td): ?>
                    <option value="<?= $td ?>" <?= $cli['tipo_documento'] === $td ? 'selected' : '' ?>><?= $td ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">N° Documento</label>
                <input type="text" name="numero_documento" class="form-control" value="<?= h($cli['numero_documento']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" class="form-control" value="<?= h($cli['direccion']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= h($cli['telefono']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= h($cli['email']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo_cliente" class="form-select">
                    <option value="minorista" <?= $cli['tipo_cliente'] === 'minorista' ? 'selected' : '' ?>>Minorista</option>
                    <option value="mayorista" <?= $cli['tipo_cliente'] === 'mayorista' ? 'selected' : '' ?>>Mayorista</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Límite Crédito</label>
                <input type="number" name="limite_credito" class="form-control" step="0.01" value="<?= $cli['limite_credito'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Días Crédito</label>
                <input type="number" name="dias_credito" class="form-control" value="<?= $cli['dias_credito'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Dto. Especial (%)</label>
                <input type="number" name="descuento_especial" class="form-control" step="0.01" value="<?= $cli['descuento_especial'] ?>">
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch mt-4">
                    <input type="checkbox" name="activo" class="form-check-input" id="activo" <?= $cli['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control" rows="2"><?= h($cli['notas']) ?></textarea>
            </div>
            <div class="col-12">
                <hr>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Actualizar</button>
                <a href="<?= BASE_URL ?>/modules/clientes/clientes.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
