<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

verificarPermisoO(USUARIO_ROL, 'salidas', 'eliminar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);

$id = (int) ($_POST['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM salidas WHERE id = ? AND anulado = 0");
$stmt->execute([$id]);
$salida = $stmt->fetch();
if (!$salida) jsonResponse(['success' => false, 'error' => 'Salida no encontrada o ya anulada']);

try {
    $db->beginTransaction();

    $detalles = $db->prepare("SELECT * FROM salida_detalles WHERE id_salida = ?");
    $detalles->execute([$id]);
    while ($d = $detalles->fetch()) {
        registrarMovimiento($d['id_producto'], $salida['id_almacen'], 'entrada', 'Anulación Salida', $id, $d['cantidad'], $d['precio_unitario'], USUARIO_ID);
    }

    $db->prepare("UPDATE salidas SET anulado = 1, fecha_anulacion = datetime('now','localtime'), usuario_anulacion = ? WHERE id = ?")->execute([USUARIO_ID, $id]);
    $db->commit();
    registrarAuditoria(USUARIO_ID, 'anular', 'salidas', $id, "Salida N° {$salida['numero_documento']} anulada");
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
