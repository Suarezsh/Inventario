<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();
$stmt = $db->prepare("SELECT t.*, o.nombre as origen, d.nombre as destino, u.nombre_completo as usuario FROM transferencias t JOIN almacenes o ON t.id_almacen_origen = o.id JOIN almacenes d ON t.id_almacen_destino = d.id LEFT JOIN usuarios u ON t.creado_por = u.id WHERE t.id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$t = $stmt->fetch();
if (!$t) redirect(BASE_URL . '/modules/transferencias/transferencias.php');

$detalles = $db->prepare("SELECT d.*, p.codigo, p.nombre FROM transferencia_detalles d JOIN productos p ON d.id_producto = p.id WHERE d.id_transferencia = ?");
$detalles->execute([$t['id']]);
$items = $detalles->fetchAll();

$titulo = 'Transferencia #' . h($t['numero_guia']);
$subtitulo = 'Transferencias';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Detalle de Transferencia</h5></div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3"><strong>Guía:</strong> <?= h($t['numero_guia']) ?></div>
            <div class="col-md-3"><strong>Fecha:</strong> <?= fdate($t['fecha_transferencia'], 'd/m/Y') ?></div>
            <div class="col-md-3"><strong>Origen:</strong> <?= h($t['origen']) ?></div>
            <div class="col-md-3"><strong>Destino:</strong> <?= h($t['destino']) ?></div>
            <div class="col-md-3"><strong>Estado:</strong> <span class="badge bg-<?= $t['estado'] === 'completada' ? 'success' : ($t['estado'] === 'en_transito' ? 'info' : 'warning') ?>"><?= h(ucfirst(str_replace('_', ' ', $t['estado']))) ?></span></div>
            <div class="col-md-3"><strong>Usuario:</strong> <?= h($t['usuario']) ?></div>
        </div>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th>Código</th><th>Producto</th><th>Cantidad</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $d): ?>
                <tr><td><?= h($d['codigo']) ?></td><td><?= h($d['nombre']) ?></td><td><?= (float)$d['cantidad'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($t['notas']): ?><p><strong>Notas:</strong> <?= h($t['notas']) ?></p><?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/transferencias/transferencias.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
