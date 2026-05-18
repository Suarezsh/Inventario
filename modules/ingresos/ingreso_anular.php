<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

verificarPermisoO(USUARIO_ROL, 'ingresos', 'eliminar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);

$id = (int) ($_POST['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM ingresos WHERE id = ? AND anulado = 0");
$stmt->execute([$id]);
$ingreso = $stmt->fetch();
if (!$ingreso) jsonResponse(['success' => false, 'error' => 'Ingreso no encontrado o ya anulado']);

try {
    $db->beginTransaction();

    $detalles = $db->prepare("SELECT * FROM ingreso_detalles WHERE id_ingreso = ?");
    $detalles->execute([$id]);
    while ($d = $detalles->fetch()) {
        registrarMovimiento($d['id_producto'], $ingreso['id_almacen'], 'salida', 'Anulación Ingreso', $id, -$d['cantidad'], $d['costo_unitario'], USUARIO_ID);
    }

    $db->prepare("UPDATE ingresos SET anulado = 1, fecha_anulacion = datetime('now','localtime'), usuario_anulacion = ? WHERE id = ?")->execute([USUARIO_ID, $id]);
    $db->commit();
    registrarAuditoria(USUARIO_ID, 'anular', 'ingresos', $id, "Ingreso N° {$ingreso['numero_documento']} anulado");
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
