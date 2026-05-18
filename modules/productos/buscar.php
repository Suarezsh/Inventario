<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    jsonResponse([]);
}

$db = getDB();
$stmt = $db->prepare("SELECT id, codigo, nombre, precio_venta, precio_compra FROM productos WHERE activo = 1 AND (codigo LIKE ? OR nombre LIKE ? OR codigo_barras LIKE ?) LIMIT 20");
$like = "%$q%";
$stmt->execute([$like, $like, $like]);
$results = [];
while ($row = $stmt->fetch()) {
    $results[] = [
        'id' => $row['id'],
        'text' => $row['codigo'] . ' - ' . $row['nombre'],
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'precio_venta' => $row['precio_venta'],
        'precio_compra' => $row['precio_compra'],
    ];
}
jsonResponse(['results' => $results]);
