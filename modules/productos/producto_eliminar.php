<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);

$id = (int) ($_POST['id'] ?? 0);
$db = getDB();
$db->prepare("UPDATE productos SET activo = 0 WHERE id = ?")->execute([$id]);
registrarAuditoria(USUARIO_ID, 'eliminar', 'productos', $id, 'Producto desactivado');
jsonResponse(['success' => true]);
