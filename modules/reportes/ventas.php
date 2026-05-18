<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$ventas = $db->prepare("SELECT s.*, c.nombre as cliente, a.nombre as almacen, u.nombre_completo as usuario FROM salidas s LEFT JOIN clientes c ON s.id_cliente = c.id JOIN almacenes a ON s.id_almacen = a.id LEFT JOIN usuarios u ON s.creado_por = u.id WHERE s.anulado = 0 AND date(s.fecha_salida) BETWEEN ? AND ? ORDER BY s.fecha_salida DESC");
$ventas->execute([$desde, $hasta]);
$items = $ventas->fetchAll();

$totalVentas = 0;
foreach ($items as $s) $totalVentas += (float)$s['total'];

$titulo = 'Reporte de Ventas';
$subtitulo = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Ventas</h5>
        <span class="fw-bold">Total: <?= money($totalVentas, config('moneda_simbolo', '$')) ?></span>
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
                <button type="button" class="btn btn-outline-primary form-control" onclick="generarPDF('Ventas', '#tablaVentas')"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tablaVentas">
                <thead>
                    <tr><th>N° Doc</th><th>Fecha</th><th>Cliente</th><th>Almacén</th><th>Tipo</th><th>Subtotal</th><th>Impuesto</th><th>Total</th><th>Usuario</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $s): ?>
                    <tr>
                        <td><?= h($s['numero_documento']) ?></td>
                        <td><?= fdate($s['fecha_salida'], 'd/m/Y') ?></td>
                        <td><?= h($s['cliente'] ?? 'N/A') ?></td>
                        <td><?= h($s['almacen']) ?></td>
                        <td><span class="badge bg-success"><?= h(ucfirst($s['tipo_salida'])) ?></span></td>
                        <td><?= money($s['subtotal'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= money($s['impuesto'], config('moneda_simbolo', '$')) ?></td>
                        <td class="fw-bold"><?= money($s['total'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= h($s['usuario']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
