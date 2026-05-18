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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102,126,234,0.08) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }
        .login-card {
            background: rgba(255,255,255,0.98);
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 25px 80px rgba(0,0,0,0.4);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
        }
        .login-header .logo {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -1px;
        }
        .login-header p {
            opacity: 0.85;
            margin: 0.5rem 0 0;
            font-size: 0.95rem;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
        }
        .input-group-text {
            border-radius: 0.75rem 0 0 0.75rem;
            border: 2px solid #e9ecef;
            border-right: none;
            background: #f8f9fa;
            color: #6c757d;
        }
        .input-group .form-control {
            border-radius: 0 0.75rem 0.75rem 0;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.85rem;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .alert {
            border-radius: 0.75rem;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">📦</div>
                <h2 class="fw-bold mt-2"><?= h(APP_NAME) ?></h2>
                <p>Control de inventario empresarial</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= h($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small text-uppercase">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Ingrese su usuario" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-muted small text-uppercase">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Ingrese su contraseña" required>
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
    </div>
</body>
</html>
