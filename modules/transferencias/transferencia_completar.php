<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        die('Token inválido');
    }
    $id = (int)($_POST['id'] ?? 0);
} else {
    $id = (int)($_GET['id'] ?? 0);
}

$stmt = $db->prepare("SELECT * FROM transferencias WHERE id = ? AND estado = 'pendiente'");
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t) {
    redirect(BASE_URL . '/modules/transferencias/transferencias.php');
}

$detalles = $db->prepare("SELECT d.*, p.nombre FROM transferencia_detalles d JOIN productos p ON d.id_producto = p.id WHERE d.id_transferencia = ?");
$detalles->execute([$id]);
$items = $detalles->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        foreach ($items as $d) {
            if (!verificarStockSuficiente($d['id_producto'], $t['id_almacen_origen'], $d['cantidad'])) {
                throw new Exception("Stock insuficiente de: {$d['nombre']}");
            }
        }
        foreach ($items as $d) {
            registrarMovimiento($d['id_producto'], $t['id_almacen_origen'], 'transferencia', 'Transferencia Salida', $id, -$d['cantidad'], 0, USUARIO_ID);
            registrarMovimiento($d['id_producto'], $t['id_almacen_destino'], 'transferencia', 'Transferencia Entrada', $id, $d['cantidad'], 0, USUARIO_ID);
        }
        $db->prepare("UPDATE transferencias SET estado = 'completada' WHERE id = ?")->execute([$id]);
        $db->commit();
        registrarAuditoria(USUARIO_ID, 'completar', 'transferencias', $id, "Transferencia completada");
        $success = 'Transferencia completada. Stock actualizado.';
        echo '<script>setTimeout(() => location.href = "' . BASE_URL . '/modules/transferencias/transferencias.php", 1500);</script>';
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

$titulo = 'Completar Transferencia';
$subtitulo = 'Transferencias';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Confirmar Transferencia</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <p>Al completar la transferencia, el stock se descontará del almacén origen y se agregará al almacén destino.</p>
        <form method="POST">
            <?= campoCSRF() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Completar Transferencia</button>
            <a href="<?= BASE_URL ?>/modules/transferencias/transferencias.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
