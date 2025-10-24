<?php
// ============================================
// GERENCIAMENTO DE USUÁRIOS ADMINISTRADORES
// ============================================

require_once '../config/database.php';
require_once '../config/auth.php';

verificarLogin();

$pdo = getConnection();
$admin = getAdminLogado();

$sucesso = null;
$erro = null;

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Adicionar usuário
    if ($acao === 'adicionar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        
        if (empty($nome) || empty($email) || empty($senha)) {
            $erro = "Preencha todos os campos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Email inválido.";
        } elseif (strlen($senha) < 6) {
            $erro = "A senha deve ter no mínimo 6 caracteres.";
        } else {
            try {
                // Verificar se email já existe
                $stmt = $pdo->prepare("SELECT id FROM admin_usuarios WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $erro = "Este email já está cadastrado.";
                } else {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO admin_usuarios (nome, email, senha) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$nome, $email, $senha_hash]);
                    
                    $sucesso = "✅ Usuário adicionado com sucesso!";
                    registrarLog($_SESSION['admin_id'], 'adicionar_usuario', "Usuário $email adicionado");
                }
            } catch (Exception $e) {
                $erro = "Erro ao adicionar usuário: " . $e->getMessage();
            }
        }
    }
    
    // Desativar usuário
    elseif ($acao === 'desativar') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id === $_SESSION['admin_id']) {
            $erro = "Você não pode desativar sua própria conta.";
        } elseif ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE admin_usuarios SET ativo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                
                $sucesso = "✅ Usuário desativado com sucesso!";
                registrarLog($_SESSION['admin_id'], 'desativar_usuario', "Usuário ID $id desativado");
            } catch (Exception $e) {
                $erro = "Erro ao desativar usuário.";
            }
        }
    }
    
    // Ativar usuário
    elseif ($acao === 'ativar') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE admin_usuarios SET ativo = 1 WHERE id = ?");
                $stmt->execute([$id]);
                
                $sucesso = "✅ Usuário ativado com sucesso!";
                registrarLog($_SESSION['admin_id'], 'ativar_usuario', "Usuário ID $id ativado");
            } catch (Exception $e) {
                $erro = "Erro ao ativar usuário.";
            }
        }
    }
    
    // Alterar senha
    elseif ($acao === 'alterar_senha') {
        $id = (int)($_POST['id'] ?? 0);
        $senha = $_POST['senha'] ?? '';
        
        if (empty($senha)) {
            $erro = "Digite a nova senha.";
        } elseif (strlen($senha) < 6) {
            $erro = "A senha deve ter no mínimo 6 caracteres.";
        } elseif ($id > 0) {
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE admin_usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$senha_hash, $id]);
                
                $sucesso = "✅ Senha alterada com sucesso!";
                registrarLog($_SESSION['admin_id'], 'alterar_senha', "Senha do usuário ID $id alterada");
            } catch (Exception $e) {
                $erro = "Erro ao alterar senha.";
            }
        }
    }
}

// Buscar todos os usuários
$usuarios = $pdo->query("
    SELECT * FROM admin_usuarios 
    ORDER BY ativo DESC, nome ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Reiki Mãe Maria</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <!-- Header -->
    <div class="header-admin">
        <div class="container">
            <h1>✨ Reiki Mãe Maria - Admin</h1>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($admin['nome']) ?></span>
                <a href="dashboard.php" class="btn btn-secondary">📋 Dashboard</a>
                <a href="logout.php" class="btn btn-danger">🚪 Sair</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Mensagens -->
        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <!-- Adicionar Usuário -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Adicionar Novo Usuário</h2>
                <p>Cadastre administradores que terão acesso ao sistema</p>
            </div>

            <form method="POST">
                <input type="hidden" name="acao" value="adicionar">
                
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="required">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="required">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Senha</label>
                    <input type="password" name="senha" class="form-control" required minlength="6">
                    <small class="form-text">Mínimo de 6 caracteres</small>
                </div>

                <button type="submit" class="btn btn-success">➕ Adicionar Usuário</button>
            </form>
        </div>

        <!-- Lista de Usuários -->
        <div class="card">
            <div class="card-header">
                <h2>👥 Usuários Cadastrados (<?= count($usuarios) ?>)</h2>
                <p>Gerencie os administradores do sistema</p>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Último Acesso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($usuario['nome']) ?></strong>
                                    <?php if ($usuario['id'] === $_SESSION['admin_id']): ?>
                                        <span class="badge badge-concluido">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <?php if ($usuario['ativo']): ?>
                                        <span class="badge badge-concluido">✅ Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-pendente">❌ Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ultimo_acesso']): ?>
                                        <?= date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) ?>
                                    <?php else: ?>
                                        <em>Nunca</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['id'] !== $_SESSION['admin_id']): ?>
                                        <!-- Ativar/Desativar -->
                                        <?php if ($usuario['ativo']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao" value="desativar">
                                                <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                                <button type="submit" class="btn btn-danger" 
                                                        onclick="return confirm('Desativar este usuário?')">
                                                    ❌ Desativar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao" value="ativar">
                                                <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                                <button type="submit" class="btn btn-success">
                                                    ✅ Ativar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Alterar Senha -->
                                    <button type="button" class="btn btn-secondary" 
                                            onclick="alterarSenha(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nome']) ?>')">
                                        🔑 Senha
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div class="modal-overlay" id="modalSenha">
        <div class="modal-content">
            <button class="modal-close" onclick="fecharModal()">×</button>
            <div class="modal-header">
                <h3>🔑 Alterar Senha</h3>
                <p id="nomeUsuario"></p>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="alterar_senha">
                <input type="hidden" name="id" id="usuarioId">
                
                <div class="form-group">
                    <label class="required">Nova Senha</label>
                    <input type="password" name="senha" class="form-control" required minlength="6">
                    <small class="form-text">Mínimo de 6 caracteres</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    🔑 Alterar Senha
                </button>
            </form>
        </div>
    </div>

    <script>
        function alterarSenha(id, nome) {
            document.getElementById('usuarioId').value = id;
            document.getElementById('nomeUsuario').textContent = nome;
            document.getElementById('modalSenha').classList.add('active');
        }

        function fecharModal() {
            document.getElementById('modalSenha').classList.remove('active');
        }

        // Fechar modal ao clicar fora
        document.getElementById('modalSenha').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModal();
            }
        });
    </script>
</body>
</html>

