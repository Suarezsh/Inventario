<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$id = (int) ($_GET['id'] ?? 0);
$db = getDB();
$stmt = $db->prepare("SELECT p.*, c.nombre as categoria, u.nombre as um_nombre, u.abreviatura as um_abrev, i.nombre as impuesto_nombre, i.porcentaje as impuesto_pct FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id LEFT JOIN unidades_medida u ON p.id_unidad_medida = u.id LEFT JOIN impuestos i ON p.id_impuesto = i.id WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) redirect(BASE_URL . '/modules/productos/productos.php');

$stockAlmacenes = $db->prepare("SELECT a.nombre, pa.stock_actual FROM producto_almacen pa JOIN almacenes a ON pa.id_almacen = a.id WHERE pa.id_producto = ?");
$stockAlmacenes->execute([$id]);
$stocks = $stockAlmacenes->fetchAll();

$movimientos = $db->prepare("SELECT m.*, a.nombre as almacen FROM movimientos_inventario m JOIN almacenes a ON m.id_almacen = a.id WHERE m.id_producto = ? ORDER BY m.fecha_movimiento DESC LIMIT 20");
$movimientos->execute([$id]);
$movs = $movimientos->fetchAll();

$proveedores = $db->prepare("SELECT prv.razon_social FROM producto_proveedor pp JOIN proveedores prv ON pp.id_proveedor = prv.id WHERE pp.id_producto = ?");
$proveedores->execute([$id]);
$provs = $proveedores->fetchAll(PDO::FETCH_COLUMN);

$titulo = h($p['nombre']);
$subtitulo = 'Productos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Información General</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><th>Código</th><td><?= h($p['codigo']) ?></td></tr>
                    <tr><th>Código Barras</th><td><?= h($p['codigo_barras']) ?: '-' ?></td></tr>
                    <tr><th>Categoría</th><td><?= h($p['categoria']) ?: '-' ?></td></tr>
                    <tr><th>Unidad Medida</th><td><?= h($p['um_nombre']) ?> (<?= h($p['um_abrev']) ?>)</td></tr>
                    <tr><th>Impuesto</th><td><?= h($p['impuesto_nombre']) ?> (<?= $p['impuesto_pct'] ?>%)</td></tr>
                    <tr><th>Ubicación</th><td><?= h($p['ubicacion_almacen']) ?: '-' ?></td></tr>
                </table>
            </div>
        </div>
        <?php if (!empty($provs)): ?>
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Proveedores</h5></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($provs as $prov): ?>
                    <li><i class="bi bi-truck me-2"></i><?= h($prov) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Precios</h5></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4 border-end">
                        <div class="text-muted small">Compra</div>
                        <div class="fs-4 fw-bold text-danger"><?= money($p['precio_compra'], config('moneda_simbolo', '$')) ?></div>
                    </div>
                    <div class="col-4 border-end">
                        <div class="text-muted small">Venta</div>
                        <div class="fs-4 fw-bold text-success"><?= money($p['precio_venta'], config('moneda_simbolo', '$')) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Mayorista</div>
                        <div class="fs-4 fw-bold text-info"><?= money($p['precio_mayorista'], config('moneda_simbolo', '$')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Stock por Almacén</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Almacén</th><th>Stock Actual</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $s): ?>
                        <tr>
                            <td><?= h($s['nombre']) ?></td>
                            <td class="fw-bold"><?= (float)$s['stock_actual'] ?></td>
                            <td>
                                <?php $st = (float)$s['stock_actual']; $min = (float)$p['stock_minimo']; ?>
                                <?php if ($min > 0 && $st <= $min): ?>
                                <span class="badge bg-danger">Stock Bajo</span>
                                <?php elseif ($st <= 0): ?>
                                <span class="badge bg-secondary">Sin Stock</span>
                                <?php else: ?>
                                <span class="badge bg-success">Disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Últimos Movimientos</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Fecha</th><th>Almacén</th><th>Tipo</th><th>Documento</th><th>Cantidad</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movs)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Sin movimientos</td></tr>
                        <?php else: ?>
                        <?php foreach ($movs as $m): ?>
                        <tr>
                            <td><?= fdatetime($m['fecha_movimiento'], 'd/m/Y H:i') ?></td>
                            <td><?= h($m['almacen']) ?></td>
                            <td><span class="badge bg-<?= $m['tipo_movimiento'] === 'entrada' ? 'success' : ($m['tipo_movimiento'] === 'salida' ? 'danger' : 'info') ?>"><?= h(ucfirst($m['tipo_movimiento'])) ?></span></td>
                            <td><?= h($m['tipo_documento']) ?></td>
                            <td class="fw-bold <?= $m['cantidad'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= ($m['cantidad'] >= 0 ? '+' : '') . (float)$m['cantidad'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
