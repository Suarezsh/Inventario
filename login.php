<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funciones.php';

if (isset($_SESSION['usuario_id'])) {
    redirect(BASE_URL . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            if ($usuario['bloqueado']) {
                $error = 'Cuenta bloqueada. Contacte al administrador.';
            } elseif (password_verify($password, $usuario['password_hash'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $db->prepare("UPDATE usuarios SET ultimo_acceso = datetime('now','localtime'), intentos_fallidos = 0 WHERE id = ?")->execute([$usuario['id']]);
                redirect(BASE_URL . '/index.php');
            } else {
                $intentos = $usuario['intentos_fallidos'] + 1;
                if ($intentos >= 5) {
                    $db->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado = 1 WHERE id = ?")->execute([$intentos, $usuario['id']]);
                    $error = 'Cuenta bloqueada por múltiples intentos fallidos.';
                } else {
                    $db->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?")->execute([$intentos, $usuario['id']]);
                    $error = 'Usuario o contraseña incorrectos.';
                }
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $error = 'Complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(APP_NAME) ?> - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fontsource/nunito@5.0.8/index.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        .login-card .card-body {
            padding: 2.5rem;
        }
        .login-logo {
            font-size: 2rem;
            font-weight: 800;
            color: #4a5568;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-weight: 600;
            color: white;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <div class="text-center mb-4">
                <div class="login-logo">📦 <?= h(APP_NAME) ?></div>
                <p class="text-muted">Ingrese sus credenciales</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= h($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Nombre de usuario" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">v<?= APP_VERSION ?></small>
            </div>
        </div>
    </div>
</body>
</html>
