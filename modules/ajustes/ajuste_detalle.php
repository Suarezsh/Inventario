<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();
$stmt = $db->prepare("SELECT a.*, al.nombre as almacen, u.nombre_completo as usuario FROM ajustes a JOIN almacenes al ON a.id_almacen = al.id LEFT JOIN usuarios u ON a.creado_por = u.id WHERE a.id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$a = $stmt->fetch();
if (!$a) redirect(BASE_URL . '/modules/ajustes/ajustes.php');

$detalles = $db->prepare("SELECT d.*, p.codigo, p.nombre FROM ajuste_detalles d JOIN productos p ON d.id_producto = p.id WHERE d.id_ajuste = ?");
$detalles->execute([$a['id']]);
$items = $detalles->fetchAll();

$titulo = 'Ajuste #' . $a['id'];
$subtitulo = 'Ajustes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Detalle del Ajuste</h5></div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3"><strong>ID:</strong> #<?= $a['id'] ?></div>
            <div class="col-md-3"><strong>Fecha:</strong> <?= fdate($a['fecha_ajuste'], 'd/m/Y') ?></div>
            <div class="col-md-3"><strong>Tipo:</strong> <span class="badge bg-<?= $a['tipo_ajuste'] === 'entrada' ? 'success' : 'danger' ?>"><?= h(ucfirst($a['tipo_ajuste'])) ?></span></div>
            <div class="col-md-3"><strong>Almacén:</strong> <?= h($a['almacen']) ?></div>
            <div class="col-md-3"><strong>Motivo:</strong> <?= h($a['motivo']) ?></div>
            <div class="col-md-3"><strong>Usuario:</strong> <?= h($a['usuario']) ?></div>
        </div>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th>Código</th><th>Producto</th><th>Cantidad</th><th>Costo Unit.</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $d): ?>
                <tr><td><?= h($d['codigo']) ?></td><td><?= h($d['nombre']) ?></td><td><?= (float)$d['cantidad'] ?></td><td><?= money($d['costo_unitario'], config('moneda_simbolo', '$')) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($a['notas']): ?><p><strong>Notas:</strong> <?= h($a['notas']) ?></p><?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/ajustes/ajustes.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
