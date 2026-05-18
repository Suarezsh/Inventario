<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$compras = $db->prepare("SELECT i.*, p.razon_social as proveedor, a.nombre as almacen, u.nombre_completo as usuario FROM ingresos i LEFT JOIN proveedores p ON i.id_proveedor = p.id JOIN almacenes a ON i.id_almacen = a.id LEFT JOIN usuarios u ON i.creado_por = u.id WHERE i.anulado = 0 AND date(i.fecha_ingreso) BETWEEN ? AND ? ORDER BY i.fecha_ingreso DESC");
$compras->execute([$desde, $hasta]);
$items = $compras->fetchAll();

$totalCompras = 0;
foreach ($items as $i) $totalCompras += (float)$i['total'];

$titulo = 'Reporte de Compras';
$subtitulo = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Compras</h5>
        <span class="fw-bold">Total: <?= money($totalCompras, config('moneda_simbolo', '$')) ?></span>
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
                <button type="button" class="btn btn-outline-primary form-control" onclick="generarPDF('Compras', '#tablaCompras')"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tablaCompras">
                <thead>
                    <tr><th>N° Doc</th><th>Fecha</th><th>Proveedor</th><th>Almacén</th><th>Subtotal</th><th>Impuesto</th><th>Total</th><th>Usuario</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i): ?>
                    <tr>
                        <td><?= h($i['numero_documento']) ?></td>
                        <td><?= fdate($i['fecha_ingreso'], 'd/m/Y') ?></td>
                        <td><?= h($i['proveedor'] ?? 'N/A') ?></td>
                        <td><?= h($i['almacen']) ?></td>
                        <td><?= money($i['subtotal'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= money($i['impuesto'], config('moneda_simbolo', '$')) ?></td>
                        <td class="fw-bold"><?= money($i['total'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= h($i['usuario']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
