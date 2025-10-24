<?php
// ============================================
// PÁGINA DE LOGIN ADMINISTRATIVO
// ============================================

require_once '../config/database.php';
require_once '../config/auth.php';

// Se já está logado, redireciona
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = null;

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        if (fazerLogin($email, $senha)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Email ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Reiki Mãe Maria</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-header">
                <h1>🔐 Área Administrativa</h1>
                <p>Reiki Mãe Maria Celestial</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    ⚠️ <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="required">Email</label>
                    <input type="email" name="email" class="form-control" required autofocus
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="seu@email.com">
                </div>

                <div class="form-group">
                    <label class="required">Senha</label>
                    <input type="password" name="senha" class="form-control" required
                           placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Entrar
                </button>
            </form>

            <div class="back-link">
                <a href="../index.php">← Voltar para página inicial</a>
            </div>
        </div>
    </div>
</body>
</html>

