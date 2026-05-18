<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$movimientos = $db->prepare("SELECT m.*, p.nombre as producto, a.nombre as almacen, u.nombre_completo as usuario FROM movimientos_inventario m JOIN productos p ON m.id_producto = p.id JOIN almacenes a ON m.id_almacen = a.id LEFT JOIN usuarios u ON m.usuario = u.id WHERE date(m.fecha_movimiento) BETWEEN ? AND ? ORDER BY m.fecha_movimiento DESC");
$movimientos->execute([$desde, $hasta]);
$items = $movimientos->fetchAll();

$titulo = 'Reporte de Movimientos';
$subtitulo = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Movimientos de Inventario</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-auto">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= $desde ?>">
            </div>
            <div class="col-auto">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= $hasta ?>">
            </div>
            <div class="col-auto">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control"><i class="bi bi-filter"></i> Filtrar</button>
            </div>
            <div class="col-auto">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-outline-primary form-control" onclick="generarPDF('Movimientos', '#tablaMovimientos')"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tablaMovimientos">
                <thead>
                    <tr><th>Fecha</th><th>Producto</th><th>Almacén</th><th>Tipo</th><th>Documento</th><th>Cantidad</th><th>Stock Anterior</th><th>Stock Posterior</th><th>Usuario</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $m): ?>
                    <tr>
                        <td><?= fdatetime($m['fecha_movimiento'], 'd/m/Y H:i') ?></td>
                        <td><?= h($m['producto']) ?></td>
                        <td><?= h($m['almacen']) ?></td>
                        <td><span class="badge bg-<?= $m['tipo_movimiento'] === 'entrada' ? 'success' : ($m['tipo_movimiento'] === 'salida' ? 'danger' : 'info') ?>"><?= h(ucfirst($m['tipo_movimiento'])) ?></span></td>
                        <td><?= h($m['tipo_documento']) ?></td>
                        <td class="fw-bold <?= $m['cantidad'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= ($m['cantidad'] >= 0 ? '+' : '') . (float)$m['cantidad'] ?></td>
                        <td><?= (float)$m['stock_anterior'] ?></td>
                        <td><?= (float)$m['stock_posterior'] ?></td>
                        <td><?= h($m['usuario']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
