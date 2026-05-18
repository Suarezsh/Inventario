<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();

$idProducto = (int)($_GET['id_producto'] ?? 0);
$desde = $_GET['desde'] ?? date('Y-01-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$productos = $db->query("SELECT id, codigo, nombre FROM productos WHERE activo = 1 ORDER BY nombre")->fetchAll();

$movimientos = [];
$productoActual = null;
if ($idProducto > 0) {
    $stmt = $db->prepare("SELECT m.*, a.nombre as almacen FROM movimientos_inventario m JOIN almacenes a ON m.id_almacen = a.id WHERE m.id_producto = ? AND date(m.fecha_movimiento) BETWEEN ? AND ? ORDER BY m.fecha_movimiento ASC");
    $stmt->execute([$idProducto, $desde, $hasta]);
    $movimientos = $stmt->fetchAll();

    $stmtP = $db->prepare("SELECT * FROM productos WHERE id = ?");
    $stmtP->execute([$idProducto]);
    $productoActual = $stmtP->fetch();
}

$titulo = 'Kárdex de Producto';
$subtitulo = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Kárdex Valorizado</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Producto</label>
                <select name="id_producto" class="form-select select2">
                    <option value="">Seleccionar producto</option>
                    <?php foreach ($productos as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] === $idProducto ? 'selected' : '' ?>><?= h($p['codigo']) ?> - <?= h($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control flatpickr" value="<?= $desde ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control flatpickr" value="<?= $hasta ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control"><i class="bi bi-search"></i> Consultar</button>
            </div>
        </form>

        <?php if ($productoActual): ?>
        <div class="row mb-3">
            <div class="col-md-3"><strong>Producto:</strong> <?= h($productoActual['nombre']) ?></div>
            <div class="col-md-3"><strong>Código:</strong> <?= h($productoActual['codigo']) ?></div>
            <div class="col-md-3"><strong>Costo Promedio:</strong> <?= money($productoActual['costo_promedio'], config('moneda_simbolo', '$')) ?></div>
            <div class="col-md-3"><strong>Stock Actual:</strong> <?= obtenerStockProducto($idProducto) ?></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm datatable" id="tablaKardex">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th><th>Almacén</th><th>Tipo</th><th>Documento</th>
                        <th class="text-center">Entrada</th><th class="text-center">Salida</th>
                        <th class="text-center">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $saldo = 0; foreach ($movimientos as $m): ?>
                    <?php $saldo += (float)$m['cantidad']; ?>
                    <tr>
                        <td><?= fdatetime($m['fecha_movimiento'], 'd/m/Y') ?></td>
                        <td><?= h($m['almacen']) ?></td>
                        <td><span class="badge bg-<?= $m['tipo_movimiento'] === 'entrada' ? 'success' : 'danger' ?>"><?= h(ucfirst($m['tipo_movimiento'])) ?></span></td>
                        <td><?= h($m['tipo_documento']) ?></td>
                        <td class="text-center text-success"><?= (float)$m['cantidad'] > 0 ? (float)$m['cantidad'] : '-' ?></td>
                        <td class="text-center text-danger"><?= (float)$m['cantidad'] < 0 ? abs((float)$m['cantidad']) : '-' ?></td>
                        <td class="text-center fw-bold"><?= $saldo ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($movimientos)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No hay movimientos en el período seleccionado</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-sm btn-primary" onclick="generarPDF('Kardex_<?= h($productoActual['codigo']) ?>', '#tablaKardex')"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        <?php elseif ($idProducto > 0): ?>
        <div class="alert alert-warning">Producto no encontrado</div>
        <?php else: ?>
        <div class="alert alert-info">Seleccione un producto para ver su kárdex</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
