<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();
$stmt = $db->prepare("SELECT i.*, p.razon_social as proveedor, a.nombre as almacen, u.nombre_completo as usuario, fp.nombre as forma_pago FROM ingresos i LEFT JOIN proveedores p ON i.id_proveedor = p.id JOIN almacenes a ON i.id_almacen = a.id LEFT JOIN usuarios u ON i.creado_por = u.id LEFT JOIN formas_pago fp ON i.id_forma_pago = fp.id WHERE i.id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$ingreso = $stmt->fetch();
if (!$ingreso) redirect(BASE_URL . '/modules/ingresos/ingresos.php');

$detalles = $db->prepare("SELECT d.*, pr.codigo, pr.nombre as producto FROM ingreso_detalles d JOIN productos pr ON d.id_producto = pr.id WHERE d.id_ingreso = ?");
$detalles->execute([$ingreso['id']]);
$items = $detalles->fetchAll();

$titulo = 'Ingreso #' . h($ingreso['numero_documento']);
$subtitulo = 'Ingresos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Detalle del Ingreso</h5>
        <button class="btn btn-sm btn-primary" onclick="generarPDF('Ingreso - <?= h($ingreso['numero_documento']) ?>', '#tablaDetalle')">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3"><strong>N° Documento:</strong> <?= h($ingreso['numero_documento']) ?></div>
            <div class="col-md-3"><strong>Fecha:</strong> <?= fdate($ingreso['fecha_ingreso'], 'd/m/Y') ?></div>
            <div class="col-md-3"><strong>Proveedor:</strong> <?= h($ingreso['proveedor'] ?? 'N/A') ?></div>
            <div class="col-md-3"><strong>Almacén:</strong> <?= h($ingreso['almacen']) ?></div>
            <div class="col-md-3"><strong>Tipo:</strong> <?= h(ucfirst($ingreso['tipo_ingreso'])) ?></div>
            <div class="col-md-3"><strong>Forma Pago:</strong> <?= h($ingreso['forma_pago'] ?? 'N/A') ?></div>
            <div class="col-md-3"><strong>Usuario:</strong> <?= h($ingreso['usuario']) ?></div>
            <div class="col-md-3"><strong>Estado:</strong> <?= $ingreso['anulado'] ? '<span class="badge bg-danger">Anulado</span>' : '<span class="badge bg-success">Activo</span>' ?></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="tablaDetalle">
                <thead class="table-light">
                    <tr><th>Código</th><th>Producto</th><th>Cantidad</th><th>Costo Unit.</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $d): ?>
                    <tr>
                        <td><?= h($d['codigo']) ?></td>
                        <td><?= h($d['producto']) ?></td>
                        <td><?= (float)$d['cantidad'] ?></td>
                        <td><?= money($d['costo_unitario'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= money($d['subtotal'], config('moneda_simbolo', '$')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="4" class="text-end">Subtotal:</th><th><?= money($ingreso['subtotal'], config('moneda_simbolo', '$')) ?></th></tr>
                    <tr><th colspan="4" class="text-end">Impuesto:</th><th><?= money($ingreso['impuesto'], config('moneda_simbolo', '$')) ?></th></tr>
                    <tr><th colspan="4" class="text-end">Total:</th><th class="fw-bold"><?= money($ingreso['total'], config('moneda_simbolo', '$')) ?></th></tr>
                </tfoot>
            </table>
        </div>
        <?php if ($ingreso['notas']): ?>
        <div class="mt-3"><strong>Notas:</strong> <?= h($ingreso['notas']) ?></div>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/ingresos/ingresos.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
