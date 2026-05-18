<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();
$productos = $db->query("SELECT p.codigo, p.nombre, c.nombre as categoria, p.stock_minimo, p.stock_maximo, p.precio_venta, p.costo_promedio, (SELECT SUM(pa.stock_actual) FROM producto_almacen pa WHERE pa.id_producto = p.id) as stock_total FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll();

$valorTotal = 0;
foreach ($productos as $p) {
    $valorTotal += (float)$p['stock_total'] * (float)$p['costo_promedio'];
}

$titulo = 'Reporte de Existencias';
$subtitulo = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Stock Actual</h5>
        <div>
            <span class="me-3"><strong>Valor Total:</strong> <?= money($valorTotal, config('moneda_simbolo', '$')) ?></span>
            <button class="btn btn-sm btn-primary" onclick="generarPDF('Existencias', '#tablaExistencias')"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tablaExistencias">
                <thead>
                    <tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Stock Total</th><th>Stock Mín</th><th>Stock Máx</th><th>Costo Prom.</th><th>Precio Venta</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                    <?php $st = (float)$p['stock_total']; ?>
                    <tr>
                        <td><?= h($p['codigo']) ?></td>
                        <td><?= h($p['nombre']) ?></td>
                        <td><?= h($p['categoria']) ?></td>
                        <td class="fw-bold"><?= $st ?></td>
                        <td><?= (float)$p['stock_minimo'] ?></td>
                        <td><?= (float)$p['stock_maximo'] ?></td>
                        <td><?= money($p['costo_promedio'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= money($p['precio_venta'], config('moneda_simbolo', '$')) ?></td>
                        <td>
                            <?php $min = (float)$p['stock_minimo']; ?>
                            <?php if ($min > 0 && $st <= $min): ?>
                            <span class="badge bg-danger">Bajo</span>
                            <?php elseif ($st <= 0): ?>
                            <span class="badge bg-secondary">Sin Stock</span>
                            <?php else: ?>
                            <span class="badge bg-success">OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
