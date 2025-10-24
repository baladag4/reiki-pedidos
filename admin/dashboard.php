<?php
// ============================================
// DASHBOARD ADMINISTRATIVO
// ============================================

require_once '../config/database.php';
require_once '../config/auth.php';

verificarLogin();

$pdo = getConnection();
$admin = getAdminLogado();

$sucesso = null;
$erro = null;

// Sistema de visualização (pendentes ou concluidos)
$view = $_GET['view'] ?? 'pendentes';
$page = (int)($_GET['page'] ?? 1);
$page = max(1, $page);
$limit = 20;
$offset = ($page - 1) * $limit;

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'concluir') {
        $pedidos = $_POST['pedidos'] ?? [];
        
        if (!empty($pedidos)) {
            try {
                $placeholders = str_repeat('?,', count($pedidos) - 1) . '?';
                $stmt = $pdo->prepare("
                    UPDATE pedidos_reiki 
                    SET status = 'concluido', 
                        data_conclusao = NOW(), 
                        admin_id = ? 
                    WHERE id IN ($placeholders) AND status = 'pendente'
                ");
                
                $params = array_merge([$_SESSION['admin_id']], $pedidos);
                $stmt->execute($params);
                
                $total = $stmt->rowCount();
                $sucesso = "✅ $total pedido(s) marcado(s) como concluído(s)!";
                
                registrarLog($_SESSION['admin_id'], 'concluir_pedidos', "$total pedidos concluídos");
                
            } catch (Exception $e) {
                $erro = "Erro ao concluir pedidos: " . $e->getMessage();
            }
        } else {
            $erro = "Selecione pelo menos um pedido.";
        }
    }
}

// Buscar estatísticas
$stats = $pdo->query("SELECT * FROM vw_estatisticas")->fetch();

// Contar total de pedidos para paginação
if ($view === 'pendentes') {
    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos_reiki WHERE status = 'pendente'");
    $total_pedidos = $total_stmt->fetch()['total'];
} else {
    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos_reiki WHERE status = 'concluido'");
    $total_pedidos = $total_stmt->fetch()['total'];
}

$total_pages = ceil($total_pedidos / $limit);

// Buscar pedidos conforme a view
if ($view === 'pendentes') {
    $pedidos = $pdo->query("
        SELECT p.*, COUNT(pa.id) as total_pessoas
        FROM pedidos_reiki p
        LEFT JOIN pessoas_atendimento pa ON p.id = pa.pedido_id
        WHERE p.status = 'pendente'
        GROUP BY p.id
        ORDER BY p.data_envio ASC
        LIMIT $limit OFFSET $offset
    ")->fetchAll();
} else {
    $pedidos = $pdo->query("
        SELECT p.*, a.nome as admin_nome, COUNT(pa.id) as total_pessoas
        FROM pedidos_reiki p
        LEFT JOIN admin_usuarios a ON p.admin_id = a.id
        LEFT JOIN pessoas_atendimento pa ON p.id = pa.pedido_id
        WHERE p.status = 'concluido'
        GROUP BY p.id
        ORDER BY p.data_conclusao DESC
        LIMIT $limit OFFSET $offset
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Reiki Mãe Maria</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Estilo WhatsApp */
        .chat-container {
            background: #e5ddd5;
            padding: 15px;
            border-radius: 10px;
            max-height: 800px;
            overflow-y: auto;
        }
        
        .chat-message {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            transition: all 0.3s;
        }
        
        .chat-message:hover {
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .chat-checkbox {
            display: flex;
            align-items: flex-start;
            padding-top: 5px;
        }
        
        .chat-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .chat-avatar {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder {
            font-size: 2rem;
        }
        
        .chat-content {
            flex: 1;
        }
        
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .chat-name {
            color: #075e54;
            font-size: 1.1rem;
        }
        
        .chat-time {
            color: #667781;
            font-size: 0.85rem;
        }
        
        .chat-badge {
            color: #667781;
            font-size: 0.9rem;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .chat-pessoas {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 0.9rem;
        }
        
        .pessoa-tag {
            background: white;
            padding: 8px 12px;
            border-radius: 20px;
            margin: 5px 0;
            display: inline-block;
            margin-right: 5px;
            border: 1px solid #ddd;
        }
        
        .chat-text {
            color: #303030;
            line-height: 1.6;
            background: #dcf8c6;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .chat-text strong {
            color: #075e54;
        }
        
        /* Scrollbar personalizada */
        .chat-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .chat-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .chat-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            .chat-message {
                flex-direction: column;
            }
            
            .chat-checkbox {
                order: -1;
            }
            
            .chat-avatar {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-admin">
        <div class="container">
            <h1>✨ Reiki Mãe Maria - Admin</h1>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($admin['nome']) ?></span>
                <a href="usuarios.php" class="btn btn-secondary">👥 Usuários</a>
                <a href="logout.php" class="btn btn-danger">🚪 Sair</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navegação entre Pendentes e Concluídos -->
        <div class="nav-admin">
            <a href="?view=pendentes" class="<?= $view === 'pendentes' ? 'active' : '' ?>">
                ⏳ Pendentes (<?= $stats['pendentes'] ?>)
            </a>
            <a href="?view=concluidos" class="<?= $view === 'concluidos' ? 'active' : '' ?>">
                ✅ Concluídos (<?= $stats['concluidos'] ?>)
            </a>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $stats['hoje'] ?></div>
                <div class="label">Hoje</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats['pendentes'] ?></div>
                <div class="label">Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats['concluidos'] ?></div>
                <div class="label">Concluídos</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats['total_pedidos'] ?></div>
                <div class="label">Total</div>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <!-- Lista de Pedidos -->
        <div class="card">
            <div class="card-header">
                <?php if ($view === 'pendentes'): ?>
                    <h2>💬 Pedidos Pendentes</h2>
                    <p>Atendimentos aguardando realização (Página <?= $page ?> de <?= $total_pages ?>)</p>
                <?php else: ?>
                    <h2>✅ Atendimentos Concluídos</h2>
                    <p>Histórico de atendimentos realizados (Página <?= $page ?> de <?= $total_pages ?>)</p>
                <?php endif; ?>
            </div>

            <?php if (empty($pedidos)): ?>
                <div class="alert alert-info">
                    <?= $view === 'pendentes' ? '✅ Não há pedidos pendentes no momento!' : 'Nenhum atendimento concluído ainda.' ?>
                </div>
            <?php else: ?>
                <?php if ($view === 'pendentes'): ?>
                    <!-- Botões para pedidos pendentes -->
                    <form method="POST" id="formPendentes">
                        <input type="hidden" name="acao" value="concluir">
                        
                        <div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" onclick="selecionarTodos()">
                                Selecionar Todos
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="desmarcarTodos()">
                                Desmarcar Todos
                            </button>
                            <button type="submit" class="btn btn-success" onclick="return confirm('Confirma conclusão dos atendimentos selecionados?')">
                                ✅ Marcar como Concluído
                            </button>
                        </div>
                <?php endif; ?>

                <!-- Estilo WhatsApp -->
                <div class="chat-container">
                    <?php foreach ($pedidos as $pedido): ?>
                            <?php
                            // Buscar pessoas do pedido
                            $stmt_pessoas = $pdo->prepare("SELECT * FROM pessoas_atendimento WHERE pedido_id = ? ORDER BY ordem");
                            $stmt_pessoas->execute([$pedido['id']]);
                            $pessoas = $stmt_pessoas->fetchAll();
                            ?>
                            
                            <div class="chat-message" <?= $view === 'concluidos' ? 'style="opacity: 0.8;"' : '' ?>>
                                <?php if ($view === 'pendentes'): ?>
                                    <div class="chat-checkbox">
                                        <input type="checkbox" name="pedidos[]" value="<?= $pedido['id'] ?>" class="checkbox-pedido">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="chat-avatar">
                                    <?php if ($pedido['foto']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($pedido['foto']) ?>" alt="Foto">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?= $view === 'concluidos' ? '✅' : '🙏' ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="chat-content">
                                    <div class="chat-header">
                                        <strong class="chat-name"><?= htmlspecialchars($pedido['nome_solicitante']) ?></strong>
                                        <?php if ($view === 'pendentes'): ?>
                                            <span class="chat-time"><?= date('d/m/Y H:i', strtotime($pedido['data_envio'])) ?></span>
                                        <?php else: ?>
                                            <span class="chat-time">Concluído: <?= date('d/m/Y H:i', strtotime($pedido['data_conclusao'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="chat-badge">
                                        👥 <?= $pedido['total_pessoas'] ?> pessoa(s) · 
                                        <?php if ($view === 'pendentes'): ?>
                                            📅 <?= date('d/m/Y', strtotime($pedido['data_nascimento_solicitante'])) ?>
                                        <?php else: ?>
                                            👨‍💼 Atendido por: <?= htmlspecialchars($pedido['admin_nome'] ?? 'N/A') ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($pessoas)): ?>
                                        <div class="chat-pessoas">
                                            <strong><?= $view === 'pendentes' ? 'Pessoas para atendimento:' : 'Pessoas atendidas:' ?></strong>
                                            <?php foreach ($pessoas as $pessoa): ?>
                                                <div class="pessoa-tag">
                                                    👤 <?= htmlspecialchars($pessoa['nome']) ?> 
                                                    <?php if ($view === 'pendentes'): ?>
                                                        (<?= date('d/m/Y', strtotime($pessoa['data_nascimento'])) ?>) 
                                                    <?php endif; ?>
                                                    - 📍 <?= htmlspecialchars($pessoa['cidade']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="chat-text" <?= $view === 'concluidos' ? 'style="background: #e8f5e9;"' : '' ?>>
                                        <strong><?= $view === 'pendentes' ? 'Necessidade:' : 'Necessidade atendida:' ?></strong><br>
                                        <?php if ($pedido['foto']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($pedido['foto']) ?>" 
                                                 alt="Foto enviada" 
                                                 style="max-width: 100%; max-height: 300px; border-radius: 10px; margin: 10px 0; display: block;">
                                        <?php endif; ?>
                                        <?= nl2br(htmlspecialchars($pedido['necessidade'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php if ($view === 'pendentes'): ?>
                    </form>
                <?php endif; ?>

                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 20px; text-align: center; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="?view=<?= $view ?>&page=<?= $page - 1 ?>" class="btn btn-secondary">← Anterior</a>
                        <?php endif; ?>
                        
                        <span style="padding: 10px 20px; background: #f0f0f0; border-radius: 8px; font-weight: bold;">
                            Página <?= $page ?> de <?= $total_pages ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?view=<?= $view ?>&page=<?= $page + 1 ?>" class="btn btn-secondary">Próxima →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Selecionar todos os checkboxes
        function selecionarTodos() {
            document.querySelectorAll('.checkbox-pedido').forEach(cb => cb.checked = true);
        }

        // Desmarcar todos os checkboxes
        function desmarcarTodos() {
            document.querySelectorAll('.checkbox-pedido').forEach(cb => cb.checked = false);
        }
    </script>
</body>
</html>

