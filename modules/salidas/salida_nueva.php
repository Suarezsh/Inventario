<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$clientes = $db->query("SELECT id, nombre, tipo_cliente FROM clientes WHERE activo = 1 ORDER BY nombre")->fetchAll();
$almacenes = $db->query("SELECT id, nombre FROM almacenes WHERE activo = 1 ORDER BY nombre")->fetchAll();
$formasPago = $db->query("SELECT * FROM formas_pago WHERE activo = 1 ORDER BY nombre")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token de seguridad inválido';
    } else {
        $productos = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio_unitario'] ?? [];
        $descuentos = $_POST['descuento'] ?? [];

        if (empty($productos) || count($productos) === 0) {
            $error = 'Debe agregar al menos un producto';
        } else {
            $idCliente = $_POST['id_cliente'] ?: null;
            $idAlmacen = (int)($_POST['id_almacen'] ?? 0);
            $fechaSalida = $_POST['fecha_salida'] ?? date('Y-m-d');
            $notas = trim($_POST['notas'] ?? '');
            $idFormaPago = $_POST['id_forma_pago'] ?: null;
            $tipoSalida = $_POST['tipo_salida'] ?? 'venta';
            $numeroDoc = trim($_POST['numero_documento'] ?? generarNumeroDocumento('SAL'));

            $subtotal = 0;
            $descuentoGlobal = 0;
            $items = [];
            foreach ($productos as $idx => $prodId) {
                $prodId = (int)$prodId;
                $cant = (float)($cantidades[$idx] ?? 0);
                $precio = (float)($precios[$idx] ?? 0);
                $dto = (float)($descuentos[$idx] ?? 0);
                if ($prodId > 0 && $cant > 0) {
                    if (!verificarStockSuficiente($prodId, $idAlmacen, $cant)) {
                        $error = "Stock insuficiente para el producto seleccionado";
                        break;
                    }
                    $sub = ($cant * $precio) - $dto;
                    $items[] = ['id_producto' => $prodId, 'cantidad' => $cant, 'precio_unitario' => $precio, 'descuento' => $dto, 'subtotal' => $sub];
                    $subtotal += $sub;
                    $descuentoGlobal += $dto;
                }
            }

            if ($error) {
                // keep error
            } elseif (empty($items)) {
                $error = 'Complete los datos de los productos';
            } else {
                $impuestoPct = (float) config('impuesto_porcentaje', 0);
                $impuesto = $subtotal * $impuestoPct / 100;
                $total = $subtotal + $impuesto;

                try {
                    $db->beginTransaction();
                    $stmt = $db->prepare("INSERT INTO salidas (tipo_salida, numero_documento, id_cliente, fecha_salida, id_almacen, subtotal, descuento, impuesto, total, id_forma_pago, notas, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$tipoSalida, $numeroDoc, $idCliente, $fechaSalida, $idAlmacen, $subtotal, $descuentoGlobal, $impuesto, $total, $idFormaPago, $notas, USUARIO_ID]);
                    $idSalida = (int)$db->lastInsertId();

                    $stmtDet = $db->prepare("INSERT INTO salida_detalles (id_salida, id_producto, cantidad, precio_unitario, descuento, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($items as $item) {
                        $stmtDet->execute([$idSalida, $item['id_producto'], $item['cantidad'], $item['precio_unitario'], $item['descuento'], $item['subtotal']]);
                        registrarMovimiento($item['id_producto'], $idAlmacen, 'salida', 'Salida', $idSalida, -$item['cantidad'], $item['precio_unitario'], USUARIO_ID);
                    }

                    $db->commit();
                    registrarAuditoria(USUARIO_ID, 'crear', 'salidas', $idSalida, "Salida N° $numeroDoc creada");
                    $success = 'Salida registrada correctamente';
                    echo '<script>setTimeout(() => location.href = "' . BASE_URL . '/modules/salidas/salidas.php", 1500);</script>';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Error al registrar: ' . $e->getMessage();
                }
            }
        }
    }
}

$titulo = 'Nueva Salida';
$subtitulo = 'Salidas';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Registrar Salida / Venta</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <form method="POST" id="formSalida">
            <?= campoCSRF() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_salida" class="form-select">
                        <option value="venta">Venta</option>
                        <option value="devolucion_proveedor">Devolución a Proveedor</option>
                        <option value="ajuste_negativo">Ajuste Negativo</option>
                        <option value="consumo_interno">Consumo Interno</option>
                        <option value="merma">Merma / Pérdida</option>
                        <option value="donacion">Donación</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">N° Documento</label>
                    <input type="text" name="numero_documento" class="form-control" value="<?= generarNumeroDocumento('SAL') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <select name="id_cliente" class="form-select select2">
                        <option value="">Sin cliente</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= h($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Almacén</label>
                    <select name="id_almacen" class="form-select" required>
                        <?php foreach ($almacenes as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= h($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Salida</label>
                    <input type="date" name="fecha_salida" class="form-control flatpickr" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Forma de Pago</label>
                    <select name="id_forma_pago" class="form-select">
                        <option value="">Seleccionar</option>
                        <?php foreach ($formasPago as $fp): ?>
                        <option value="<?= $fp['id'] ?>"><?= h($fp['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notas" class="form-control">
                </div>
            </div>

            <hr>
            <h6>Productos</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaProductos">
                    <thead class="table-light">
                        <tr>
                            <th style="width:30%">Producto</th>
                            <th style="width:15%">Cantidad</th>
                            <th style="width:15%">Precio Unit.</th>
                            <th style="width:15%">Descuento</th>
                            <th style="width:15%">Subtotal</th>
                            <th style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody id="productosBody">
                        <tr>
                            <td><select name="producto_id[]" class="form-select select2-producto" required></select></td>
                            <td><input type="number" name="cantidad[]" class="form-control cantidad" step="0.01" min="0.01" required oninput="calcularFila(this)"></td>
                            <td><input type="number" name="precio_unitario[]" class="form-control precio" step="0.01" min="0" required oninput="calcularFila(this)"></td>
                            <td><input type="number" name="descuento[]" class="form-control descuento" step="0.01" min="0" value="0" oninput="calcularFila(this)"></td>
                            <td><input type="text" class="form-control subtotal" readonly></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4"><button type="button" class="btn btn-success btn-sm" onclick="agregarFila()"><i class="bi bi-plus-lg"></i> Agregar Producto</button></td>
                            <td class="text-end"><strong>Total:</strong></td>
                            <td><input type="text" id="totalGlobal" class="form-control" readonly></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <hr>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar Salida</button>
            <a href="<?= BASE_URL ?>/modules/salidas/salidas.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<script>
function agregarFila() {
    const html = `<tr>
        <td><select name="producto_id[]" class="form-select select2-producto" required></select></td>
        <td><input type="number" name="cantidad[]" class="form-control cantidad" step="0.01" min="0.01" required oninput="calcularFila(this)"></td>
        <td><input type="number" name="precio_unitario[]" class="form-control precio" step="0.01" min="0" required oninput="calcularFila(this)"></td>
        <td><input type="number" name="descuento[]" class="form-control descuento" step="0.01" min="0" value="0" oninput="calcularFila(this)"></td>
        <td><input type="text" class="form-control subtotal" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    $('#productosBody').append(html);
    initSelect2();
}
function eliminarFila(btn) { $(btn).closest('tr').remove(); calcularTotal(); }
function calcularFila(el) {
    const tr = $(el).closest('tr');
    const cant = parseFloat(tr.find('.cantidad').val()) || 0;
    const precio = parseFloat(tr.find('.precio').val()) || 0;
    const dto = parseFloat(tr.find('.descuento').val()) || 0;
    tr.find('.subtotal').val((cant * precio - dto).toFixed(2));
    calcularTotal();
}
function calcularTotal() {
    let total = 0;
    $('.subtotal').each(function() { total += parseFloat($(this).val()) || 0; });
    $('#totalGlobal').val(total.toFixed(2));
}
function initSelect2() {
    $('.select2-producto').select2({
        theme: 'bootstrap-5', width: '100%',
        ajax: { url: '<?= BASE_URL ?>/modules/productos/buscar.php', dataType: 'json', delay: 250,
            data: function(p) { return { q: p.term }; },
            processResults: function(d) { return { results: d }; }, cache: true },
        minimumInputLength: 1, placeholder: 'Buscar producto...'
    });
}
$(document).ready(initSelect2);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
