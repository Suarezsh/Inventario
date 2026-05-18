<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$almacenes = $db->query("SELECT id, nombre FROM almacenes WHERE activo = 1 ORDER BY nombre")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $idOrigen = (int)($_POST['id_almacen_origen'] ?? 0);
        $idDestino = (int)($_POST['id_almacen_destino'] ?? 0);
        $fecha = $_POST['fecha_transferencia'] ?? date('Y-m-d');
        $notas = trim($_POST['notas'] ?? '');
        $productos = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];

        if ($idOrigen === $idDestino) {
            $error = 'Los almacenes deben ser diferentes';
        } elseif (empty($productos) || count($productos) === 0) {
            $error = 'Agregue al menos un producto';
        } else {
            $items = [];
            foreach ($productos as $idx => $prodId) {
                $prodId = (int)$prodId;
                $cant = (float)($cantidades[$idx] ?? 0);
                if ($prodId > 0 && $cant > 0) {
                    if (!verificarStockSuficiente($prodId, $idOrigen, $cant)) {
                        $error = "Stock insuficiente en origen para uno de los productos";
                        break;
                    }
                    $items[] = ['id_producto' => $prodId, 'cantidad' => $cant];
                }
            }

            if (!$error && !empty($items)) {
                $numeroGuia = 'TRA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                $stmt = $db->prepare("INSERT INTO transferencias (numero_guia, id_almacen_origen, id_almacen_destino, fecha_transferencia, estado, notas, creado_por) VALUES (?, ?, ?, ?, 'pendiente', ?, ?)");
                $stmt->execute([$numeroGuia, $idOrigen, $idDestino, $fecha, $notas, USUARIO_ID]);
                $idTrans = (int)$db->lastInsertId();

                $stmtDet = $db->prepare("INSERT INTO transferencia_detalles (id_transferencia, id_producto, cantidad) VALUES (?, ?, ?)");
                foreach ($items as $item) {
                    $stmtDet->execute([$idTrans, $item['id_producto'], $item['cantidad']]);
                }

                registrarAuditoria(USUARIO_ID, 'crear', 'transferencias', $idTrans, "Transferencia $numeroGuia creada");
                $success = 'Transferencia creada';
                echo '<script>setTimeout(() => location.href = "' . BASE_URL . '/modules/transferencias/transferencias.php", 1500);</script>';
            }
        }
    }
}

$titulo = 'Nueva Transferencia';
$subtitulo = 'Transferencias';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Nueva Transferencia entre Almacenes</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <form method="POST" id="formTransferencia">
            <?= campoCSRF() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Almacén Origen</label>
                    <select name="id_almacen_origen" class="form-select" required>
                        <?php foreach ($almacenes as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= h($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Almacén Destino</label>
                    <select name="id_almacen_destino" class="form-select" required>
                        <?php foreach ($almacenes as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= h($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha_transferencia" class="form-control flatpickr" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notas" class="form-control">
                </div>
            </div>

            <hr>
            <h6>Productos</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaProductos">
                    <thead class="table-light">
                        <tr><th style="width:60%">Producto</th><th style="width:30%">Cantidad</th><th style="width:10%"></th></tr>
                    </thead>
                    <tbody id="productosBody">
                        <tr>
                            <td><select name="producto_id[]" class="form-select select2-producto" required></select></td>
                            <td><input type="number" name="cantidad[]" class="form-control" step="0.01" min="0.01" required></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3"><button type="button" class="btn btn-success btn-sm" onclick="agregarFila()"><i class="bi bi-plus-lg"></i> Agregar Producto</button></td></tr>
                    </tfoot>
                </table>
            </div>

            <hr>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Crear Transferencia</button>
            <a href="<?= BASE_URL ?>/modules/transferencias/transferencias.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<script>
function agregarFila() {
    $('#productosBody').append(`<tr>
        <td><select name="producto_id[]" class="form-select select2-producto" required></select></td>
        <td><input type="number" name="cantidad[]" class="form-control" step="0.01" min="0.01" required></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    </tr>`);
    initSelect2();
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
