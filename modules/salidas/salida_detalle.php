<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();
$stmt = $db->prepare("SELECT s.*, c.nombre as cliente, a.nombre as almacen, u.nombre_completo as usuario, fp.nombre as forma_pago FROM salidas s LEFT JOIN clientes c ON s.id_cliente = c.id JOIN almacenes a ON s.id_almacen = a.id LEFT JOIN usuarios u ON s.creado_por = u.id LEFT JOIN formas_pago fp ON s.id_forma_pago = fp.id WHERE s.id = ?");
$stmt->execute([$_GET['id'] ?? 0]);
$salida = $stmt->fetch();
if (!$salida) redirect(BASE_URL . '/modules/salidas/salidas.php');

$detalles = $db->prepare("SELECT d.*, pr.codigo, pr.nombre as producto FROM salida_detalles d JOIN productos pr ON d.id_producto = pr.id WHERE d.id_salida = ?");
$detalles->execute([$salida['id']]);
$items = $detalles->fetchAll();

$titulo = 'Salida #' . h($salida['numero_documento']);
$subtitulo = 'Salidas';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Detalle de Salida</h5>
        <button class="btn btn-sm btn-primary" onclick="generarPDF('Salida - <?= h($salida['numero_documento']) ?>', '#tablaDetalle')">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3"><strong>N° Documento:</strong> <?= h($salida['numero_documento']) ?></div>
            <div class="col-md-3"><strong>Fecha:</strong> <?= fdate($salida['fecha_salida'], 'd/m/Y') ?></div>
            <div class="col-md-3"><strong>Cliente:</strong> <?= h($salida['cliente'] ?? 'N/A') ?></div>
            <div class="col-md-3"><strong>Almacén:</strong> <?= h($salida['almacen']) ?></div>
            <div class="col-md-3"><strong>Tipo:</strong> <?= h(ucfirst($salida['tipo_salida'])) ?></div>
            <div class="col-md-3"><strong>Forma Pago:</strong> <?= h($salida['forma_pago'] ?? 'N/A') ?></div>
            <div class="col-md-3"><strong>Usuario:</strong> <?= h($salida['usuario']) ?></div>
            <div class="col-md-3"><strong>Estado:</strong> <?= $salida['anulado'] ? '<span class="badge bg-danger">Anulado</span>' : '<span class="badge bg-success">Activo</span>' ?></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="tablaDetalle">
                <thead class="table-light">
                    <tr><th>Código</th><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Descuento</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $d): ?>
                    <tr>
                        <td><?= h($d['codigo']) ?></td>
                        <td><?= h($d['producto']) ?></td>
                        <td><?= (float)$d['cantidad'] ?></td>
                        <td><?= money($d['precio_unitario'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= money($d['descuento'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= money($d['subtotal'], config('moneda_simbolo', '$')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="5" class="text-end">Subtotal:</th><th><?= money($salida['subtotal'], config('moneda_simbolo', '$')) ?></th></tr>
                    <tr><th colspan="5" class="text-end">Descuento:</th><th><?= money($salida['descuento'], config('moneda_simbolo', '$')) ?></th></tr>
                    <tr><th colspan="5" class="text-end">Impuesto:</th><th><?= money($salida['impuesto'], config('moneda_simbolo', '$')) ?></th></tr>
                    <tr><th colspan="5" class="text-end">Total:</th><th class="fw-bold"><?= money($salida['total'], config('moneda_simbolo', '$')) ?></th></tr>
                </tfoot>
            </table>
        </div>
        <?php if ($salida['notas']): ?>
        <div class="mt-3"><strong>Notas:</strong> <?= h($salida['notas']) ?></div>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/salidas/salidas.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
