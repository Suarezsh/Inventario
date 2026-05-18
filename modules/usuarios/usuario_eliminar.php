<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

verificarPermisoO(USUARIO_ROL, 'usuarios', 'eliminar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}

if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
}

$id = (int) ($_POST['id'] ?? 0);
if ($id === USUARIO_ID) {
    jsonResponse(['success' => false, 'error' => 'No puedes eliminarte a ti mismo']);
}

$db = getDB();
$stmt = $db->prepare("SELECT username FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$username = $stmt->fetchColumn();

if (!$username) {
    jsonResponse(['success' => false, 'error' => 'Usuario no encontrado']);
}

$db->prepare("DELETE FROM permisos_roles WHERE id_rol = (SELECT id_rol FROM usuarios WHERE id = ?)")->execute([$id]);
$db->prepare("DELETE FROM auditoria_logs WHERE id_usuario = ?")->execute([$id]);
$db->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);

registrarAuditoria(USUARIO_ID, 'eliminar', 'usuarios', $id, "Usuario eliminado: $username");

jsonResponse(['success' => true]);
