<?php
// ============================================
// FUNÇÕES DE AUTENTICAÇÃO
// ============================================

require_once __DIR__ . '/database.php';

// Verificar se admin está logado
function verificarLogin() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
        header('Location: login.php');
        exit;
    }
}

// Fazer login
function fazerLogin($email, $senha) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM admin_usuarios WHERE email = ? AND ativo = 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($senha, $admin['senha'])) {
        // Login bem-sucedido
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_nome'] = $admin['nome'];
        
        // Atualizar último acesso
        $stmt = $pdo->prepare("UPDATE admin_usuarios SET ultimo_acesso = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        
        // Registrar log
        registrarLog($admin['id'], 'login', 'Login realizado com sucesso');
        
        return true;
    }
    
    return false;
}

// Fazer logout
function fazerLogout() {
    if (isset($_SESSION['admin_id'])) {
        registrarLog($_SESSION['admin_id'], 'logout', 'Logout realizado');
    }
    
    session_destroy();
    header('Location: login.php');
    exit;
}

// Registrar log de ação
function registrarLog($admin_id, $acao, $descricao = null) {
    try {
        $pdo = getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO logs_admin (admin_id, acao, descricao, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_id, $acao, $descricao, $ip]);
    } catch (Exception $e) {
        // Silencioso para não quebrar o fluxo
    }
}

// Obter dados do admin logado
function getAdminLogado() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, nome, email FROM admin_usuarios WHERE id = ? AND ativo = 1");
    $stmt->execute([$_SESSION['admin_id']]);
    
    return $stmt->fetch();
}
?>

