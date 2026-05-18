<?php

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/permisos.php';
require_once __DIR__ . '/includes/funciones.php';

$titulo = 'Dashboard';

$db = getDB();

$totalProductos = $db->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();
$totalMovimientosMes = $db->query("SELECT COUNT(*) FROM movimientos_inventario WHERE strftime('%Y-%m', fecha_movimiento) = strftime('%Y-%m', 'now')")->fetchColumn();

$stockBajo = $db->query("SELECT COUNT(*) FROM producto_almacen pa JOIN productos p ON pa.id_producto = p.id WHERE p.activo = 1 AND pa.stock_actual <= p.stock_minimo AND p.stock_minimo > 0")->fetchColumn();

$valorTotal = $db->query("SELECT COALESCE(SUM(pa.stock_actual * p.costo_promedio), 0) FROM producto_almacen pa JOIN productos p ON pa.id_producto = p.id WHERE p.activo = 1")->fetchColumn();

$productosStockBajo = $db->query("SELECT p.codigo, p.nombre, pa.stock_actual, p.stock_minimo FROM producto_almacen pa JOIN productos p ON pa.id_producto = p.id WHERE p.activo = 1 AND pa.stock_actual <= p.stock_minimo AND p.stock_minimo > 0 ORDER BY (pa.stock_actual * 1.0 / p.stock_minimo) ASC LIMIT 10")->fetchAll();

$movimientosRecientes = $db->query("SELECT m.*, p.nombre as producto, a.nombre as almacen FROM movimientos_inventario m JOIN productos p ON m.id_producto = p.id JOIN almacenes a ON m.id_almacen = a.id ORDER BY m.fecha_movimiento DESC LIMIT 10")->fetchAll();

$ventasPorMes = $db->query("SELECT strftime('%Y-%m', fecha_salida) as mes, COUNT(*) as total, COALESCE(SUM(total), 0) as monto FROM salidas WHERE anulado = 0 AND fecha_salida >= date('now', '-12 months') GROUP BY mes ORDER BY mes")->fetchAll();

$comprasPorMes = $db->query("SELECT strftime('%Y-%m', fecha_ingreso) as mes, COUNT(*) as total, COALESCE(SUM(total), 0) as monto FROM ingresos WHERE anulado = 0 AND fecha_ingreso >= date('now', '-12 months') GROUP BY mes ORDER BY mes")->fetchAll();

$productosTop = $db->query("SELECT p.nombre, SUM(sd.cantidad) as vendido FROM salida_detalles sd JOIN salidas s ON sd.id_salida = s.id JOIN productos p ON sd.id_producto = p.id WHERE s.anulado = 0 AND s.fecha_salida >= date('now', '-6 months') GROUP BY p.id ORDER BY vendido DESC LIMIT 10")->fetchAll();

$categorias = $db->query("SELECT c.nombre, COUNT(p.id) as total FROM categorias c LEFT JOIN productos p ON c.id = p.id_categoria AND p.activo = 1 WHERE c.activo = 1 GROUP BY c.id ORDER BY total DESC")->fetchAll();

$labelsVentas = [];
$dataVentas = [];
foreach ($ventasPorMes as $v) {
    $labelsVentas[] = $v['mes'];
    $dataVentas[] = (float) $v['monto'];
}

$labelsCompras = [];
$dataCompras = [];
foreach ($comprasPorMes as $c) {
    $labelsCompras[] = $c['mes'];
    $dataCompras[] = (float) $c['monto'];
}

$labelsTop = [];
$dataTop = [];
foreach ($productosTop as $p) {
    $labelsTop[] = $p['nombre'];
    $dataTop[] = (float) $p['vendido'];
}

$labelsCategorias = [];
$dataCategorias = [];
$colorsCategorias = [];
$bgColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#f8f9fc', '#3a3b45', '#224abe'];
$i = 0;
foreach ($categorias as $cat) {
    $labelsCategorias[] = $cat['nombre'];
    $dataCategorias[] = (int) $cat['total'];
    $colorsCategorias[] = $bgColors[$i % count($bgColors)];
    $i++;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-primary border-4 h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted text-uppercase small fw-bold">Productos</div>
                    <div class="fs-3 fw-bold"><?= $totalProductos ?></div>
                </div>
                <i class="bi bi-box fs-1 text-primary opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted text-uppercase small fw-bold">Movimientos del Mes</div>
                    <div class="fs-3 fw-bold"><?= $totalMovimientosMes ?></div>
                </div>
                <i class="bi bi-arrow-left-right fs-1 text-success opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-warning border-4 h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted text-uppercase small fw-bold">Stock Bajo</div>
                    <div class="fs-3 fw-bold text-warning"><?= $stockBajo ?></div>
                </div>
                <i class="bi bi-exclamation-triangle fs-1 text-warning opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-info border-4 h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted text-uppercase small fw-bold">Valor Inventario</div>
                    <div class="fs-3 fw-bold"><?= money($valorTotal, config('moneda_simbolo', '$')) ?></div>
                </div>
                <i class="bi bi-cash-stack fs-1 text-info opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Ventas vs Compras (12 meses)</h5>
            </div>
            <div class="card-body">
                <canvas id="chartVentasCompras" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Productos por Categoría</h5>
            </div>
            <div class="card-body">
                <canvas id="chartCategorias" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 10 Productos Más Vendidos</h5>
            </div>
            <div class="card-body">
                <canvas id="chartTopProductos" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Productos con Stock Bajo</h5>
                <a href="<?= BASE_URL ?>/modules/reportes/stock_bajo.php" class="btn btn-sm btn-warning">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Mínimo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productosStockBajo)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No hay productos con stock bajo</td></tr>
                            <?php else: ?>
                            <?php foreach ($productosStockBajo as $p): ?>
                            <tr>
                                <td><?= h($p['codigo']) ?></td>
                                <td><?= h($p['nombre']) ?></td>
                                <td class="fw-bold text-danger"><?= (float) $p['stock_actual'] ?></td>
                                <td><?= (float) $p['stock_minimo'] ?></td>
                                <td>
                                    <?php $ratio = (float) $p['stock_actual'] / max((float) $p['stock_minimo'], 1); ?>
                                    <?php if ($ratio <= 0.5): ?>
                                    <span class="badge bg-danger">Crítico</span>
                                    <?php elseif ($ratio <= 0.75): ?>
                                    <span class="badge bg-warning text-dark">Bajo</span>
                                    <?php else: ?>
                                    <span class="badge bg-info">Atención</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Últimos Movimientos</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Almacén</th>
                        <th>Tipo</th>
                        <th>Documento</th>
                        <th>Cantidad</th>
                        <th>Stock Anterior</th>
                        <th>Stock Posterior</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientosRecientes)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">No hay movimientos registrados</td></tr>
                    <?php else: ?>
                    <?php foreach ($movimientosRecientes as $m): ?>
                    <tr>
                        <td><?= fdatetime($m['fecha_movimiento'], 'd/m/Y H:i') ?></td>
                        <td><?= h($m['producto']) ?></td>
                        <td><?= h($m['almacen']) ?></td>
                        <td>
                            <?php $tipos = ['entrada' => 'success', 'salida' => 'danger', 'transferencia' => 'info', 'ajuste' => 'warning']; ?>
                            <span class="badge bg-<?= $tipos[$m['tipo_movimiento']] ?? 'secondary'] ?>">
                                <?= h(ucfirst($m['tipo_movimiento'])) ?>
                            </span>
                        </td>
                        <td><?= h($m['tipo_documento']) ?> #<?= $m['id_documento'] ?></td>
                        <td class="fw-bold <?= $m['cantidad'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= ($m['cantidad'] >= 0 ? '+' : '') . (float) $m['cantidad'] ?>
                        </td>
                        <td><?= (float) ($m['stock_anterior'] ?? 0) ?></td>
                        <td><?= (float) ($m['stock_posterior'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    <?php if (!empty($labelsVentas)): ?>
    new Chart(document.getElementById('chartVentasCompras'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labelsVentas) ?>,
            datasets: [
                {
                    label: 'Ventas',
                    data: <?= json_encode($dataVentas) ?>,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Compras',
                    data: <?= json_encode($dataCompras) ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return numeral(value).format('$0,0.00'); }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($labelsCategorias)): ?>
    new Chart(document.getElementById('chartCategorias'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($labelsCategorias) ?>,
            datasets: [{
                data: <?= json_encode($dataCategorias) ?>,
                backgroundColor: <?= json_encode($colorsCategorias) ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, boxWidth: 12 }
                }
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($labelsTop)): ?>
    new Chart(document.getElementById('chartTopProductos'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsTop) ?>,
            datasets: [{
                label: 'Unidades vendidas',
                data: <?= json_encode($dataTop) ?>,
                backgroundColor: '#4e73df',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
