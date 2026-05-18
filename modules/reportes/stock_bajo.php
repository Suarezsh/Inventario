<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();
$productos = $db->query("SELECT p.codigo, p.nombre, c.nombre as categoria, pa.stock_actual, p.stock_minimo, a.nombre as almacen FROM producto_almacen pa JOIN productos p ON pa.id_producto = p.id LEFT JOIN categorias c ON p.id_categoria = c.id JOIN almacenes a ON pa.id_almacen = a.id WHERE p.activo = 1 AND pa.stock_actual <= p.stock_minimo AND p.stock_minimo > 0 ORDER BY (pa.stock_actual * 1.0 / p.stock_minimo) ASC")->fetchAll();

$titulo = 'Productos con Stock Bajo';
$subtitulo = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Alertas de Stock Bajo</h5>
        <button class="btn btn-sm btn-primary" onclick="generarPDF('Stock_Bajo', '#tablaStockBajo')"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
    </div>
    <div class="card-body">
        <?php if (empty($productos)): ?>
        <div class="alert alert-success">No hay productos con stock bajo. <i class="bi bi-check-circle"></i></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tablaStockBajo">
                <thead>
                    <tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Almacén</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Diferencia</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                    <?php $st = (float)$p['stock_actual']; $min = (float)$p['stock_minimo']; $dif = $st - $min; ?>
                    <tr>
                        <td><?= h($p['codigo']) ?></td>
                        <td><?= h($p['nombre']) ?></td>
                        <td><?= h($p['categoria']) ?></td>
                        <td><?= h($p['almacen']) ?></td>
                        <td class="fw-bold text-danger"><?= $st ?></td>
                        <td><?= $min ?></td>
                        <td class="text-danger"><?= $dif ?></td>
                        <td>
                            <?php $ratio = $st / max($min, 1); ?>
                            <?php if ($ratio <= 0.5): ?><span class="badge bg-danger">Crítico</span>
                            <?php elseif ($ratio <= 0.75): ?><span class="badge bg-warning text-dark">Bajo</span>
                            <?php else: ?><span class="badge bg-info">Atención</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
