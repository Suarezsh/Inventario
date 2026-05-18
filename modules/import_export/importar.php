<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $tipo = $_POST['tipo_importacion'] ?? 'productos';
        $archivo = $_FILES['archivo'];
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx'])) {
            $error = 'Solo archivos CSV o XLSX';
        } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error al subir el archivo';
        } else {
            $importados = 0;
            $errores = [];

            if ($ext === 'csv') {
                $handle = fopen($archivo['tmp_name'], 'r');
                $headers = fgetcsv($handle);
                if ($tipo === 'productos') {
                    while (($row = fgetcsv($handle)) !== false) {
                        try {
                            $data = array_combine($headers, $row);
                            $codigo = trim($data['codigo'] ?? '');
                            $nombre = trim($data['nombre'] ?? '');
                            if (empty($codigo) || empty($nombre)) continue;

                            $check = $db->prepare("SELECT COUNT(*) FROM productos WHERE codigo = ?");
                            $check->execute([$codigo]);
                            if ($check->fetchColumn() > 0) continue;

                            $cat = null;
                            if (!empty($data['categoria'])) {
                                $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
                                $stmtCat->execute([trim($data['categoria'])]);
                                $cat = $stmtCat->fetchColumn();
                            }

                            $stmt = $db->prepare("INSERT INTO productos (codigo, nombre, descripcion, id_categoria, precio_compra, precio_venta, stock_minimo, stock_maximo, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                            $stmt->execute([
                                $codigo, $nombre, trim($data['descripcion'] ?? ''), $cat,
                                (float)($data['precio_compra'] ?? 0), (float)($data['precio_venta'] ?? 0),
                                (float)($data['stock_minimo'] ?? 0), (float)($data['stock_maximo'] ?? 0)
                            ]);

                            $idProd = (int)$db->lastInsertId();
                            $almacenes = $db->query("SELECT id FROM almacenes WHERE activo = 1")->fetchAll();
                            $stmtPa = $db->prepare("INSERT INTO producto_almacen (id_producto, id_almacen, stock_actual) VALUES (?, ?, 0)");
                            foreach ($almacenes as $a) $stmtPa->execute([$idProd, $a['id']]);

                            $importados++;
                        } catch (Exception $e) {
                            $errores[] = $e->getMessage();
                        }
                    }
                }
                fclose($handle);
            }

            if ($importados > 0) {
                registrarAuditoria(USUARIO_ID, 'importar', 'import_export', 0, "$importados productos importados");
                $success = "$importados productos importados correctamente.";
                if (!empty($errores)) $success .= ' Errores: ' . implode(', ', $errores);
            } else {
                $error = 'No se importaron registros. Verifique el formato.';
            }
        }
    }
}

$titulo = 'Importar Datos';
$subtitulo = 'Importar';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Importar Productos</h5></div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
                <p class="text-muted">Sube un archivo CSV o Excel con las siguientes columnas:</p>
                <ul class="small">
                    <li><code>codigo</code> (obligatorio) - Código único del producto</li>
                    <li><code>nombre</code> (obligatorio) - Nombre del producto</li>
                    <li><code>descripcion</code> - Descripción</li>
                    <li><code>categoria</code> - Nombre de la categoría (debe existir)</li>
                    <li><code>precio_compra</code> - Precio de compra</li>
                    <li><code>precio_venta</code> - Precio de venta</li>
                    <li><code>stock_minimo</code> - Stock mínimo</li>
                    <li><code>stock_maximo</code> - Stock máximo</li>
                </ul>
                <form method="POST" enctype="multipart/form-data">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="tipo_importacion" value="productos">
                    <div class="mb-3">
                        <label class="form-label">Archivo CSV / XLSX</label>
                        <input type="file" name="archivo" class="form-control" accept=".csv,.xlsx" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Importar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Exportar</h5></div>
            <div class="card-body">
                <p class="text-muted">Exporte sus datos a Excel desde cualquier tabla usando los botones de exportación integrados en las tablas.</p>
                <div class="list-group">
                    <a href="<?= BASE_URL ?>/modules/productos/productos.php" class="list-group-item list-group-item-action">📦 Exportar Productos</a>
                    <a href="<?= BASE_URL ?>/modules/proveedores/proveedores.php" class="list-group-item list-group-item-action">🚚 Exportar Proveedores</a>
                    <a href="<?= BASE_URL ?>/modules/clientes/clientes.php" class="list-group-item list-group-item-action">👥 Exportar Clientes</a>
                    <a href="<?= BASE_URL ?>/modules/reportes/existencias.php" class="list-group-item list-group-item-action">📊 Exportar Existencias</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
