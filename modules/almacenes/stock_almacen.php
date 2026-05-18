<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM almacenes WHERE id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$almacen = $stmt->fetch();
if (!$almacen) redirect(BASE_URL . '/modules/almacenes/almacenes.php');

$productos = $db->prepare("SELECT p.codigo, p.nombre, c.nombre as categoria, pa.stock_actual, p.stock_minimo, p.stock_maximo, p.precio_venta FROM producto_almacen pa JOIN productos p ON pa.id_producto = p.id LEFT JOIN categorias c ON p.id_categoria = c.id WHERE pa.id_almacen = ? AND p.activo = 1 ORDER BY p.nombre");
$productos->execute([$almacen['id']]);
$items = $productos->fetchAll();

$titulo = 'Stock - ' . h($almacen['nombre']);
$subtitulo = 'Almacenes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?= h($almacen['nombre']) ?> - Stock Actual</h5>
        <button class="btn btn-sm btn-primary" onclick="generarPDF('Stock - <?= h($almacen['nombre']) ?>', '#tablaStock')">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tablaStock">
                <thead>
                    <tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Stock Máximo</th><th>Precio Venta</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $p): ?>
                    <tr>
                        <td><?= h($p['codigo']) ?></td>
                        <td><?= h($p['nombre']) ?></td>
                        <td><?= h($p['categoria']) ?></td>
                        <td class="fw-bold"><?= (float)$p['stock_actual'] ?></td>
                        <td><?= (float)$p['stock_minimo'] ?></td>
                        <td><?= (float)$p['stock_maximo'] ?></td>
                        <td><?= money($p['precio_venta'], config('moneda_simbolo', '$')) ?></td>
                        <td>
                            <?php $st = (float)$p['stock_actual']; $min = (float)$p['stock_minimo']; ?>
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
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
