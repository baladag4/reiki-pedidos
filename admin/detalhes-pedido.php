<?php
// ============================================
// DETALHES DO PEDIDO (AJAX)
// ============================================

require_once '../config/database.php';
require_once '../config/auth.php';

verificarLogin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo '<div class="alert alert-danger">Pedido inválido.</div>';
    exit;
}

$pdo = getConnection();

// Buscar pedido
$stmt = $pdo->prepare("
    SELECT p.*, a.nome as admin_nome
    FROM pedidos_reiki p
    LEFT JOIN admin_usuarios a ON p.admin_id = a.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    echo '<div class="alert alert-danger">Pedido não encontrado.</div>';
    exit;
}

// Buscar pessoas
$stmt = $pdo->prepare("
    SELECT * FROM pessoas_atendimento 
    WHERE pedido_id = ? 
    ORDER BY ordem
");
$stmt->execute([$id]);
$pessoas = $stmt->fetchAll();
?>

<div class="detalhe-row">
    <span class="detalhe-label">Status</span>
    <span class="detalhe-value">
        <?php if ($pedido['status'] === 'pendente'): ?>
            <span class="badge badge-pendente">⏳ Pendente</span>
        <?php else: ?>
            <span class="badge badge-concluido">✅ Concluído</span>
        <?php endif; ?>
    </span>
</div>

<div class="detalhe-row">
    <span class="detalhe-label">Solicitante</span>
    <span class="detalhe-value">
        <strong><?= htmlspecialchars($pedido['nome_solicitante']) ?></strong><br>
        Data de Nascimento: <?= date('d/m/Y', strtotime($pedido['data_nascimento_solicitante'])) ?>
    </span>
</div>

<div class="detalhe-row">
    <span class="detalhe-label">Data do Pedido</span>
    <span class="detalhe-value"><?= date('d/m/Y H:i:s', strtotime($pedido['data_envio'])) ?></span>
</div>

<?php if ($pedido['status'] === 'concluido'): ?>
    <div class="detalhe-row">
        <span class="detalhe-label">Data de Conclusão</span>
        <span class="detalhe-value"><?= date('d/m/Y H:i:s', strtotime($pedido['data_conclusao'])) ?></span>
    </div>
    
    <div class="detalhe-row">
        <span class="detalhe-label">Atendido por</span>
        <span class="detalhe-value"><?= htmlspecialchars($pedido['admin_nome'] ?? 'N/A') ?></span>
    </div>
<?php endif; ?>

<div class="detalhe-row">
    <span class="detalhe-label">Necessidade / Motivo</span>
    <span class="detalhe-value"><?= nl2br(htmlspecialchars($pedido['necessidade'])) ?></span>
</div>

<div class="detalhe-row">
    <span class="detalhe-label">Pessoas para Atendimento (<?= count($pessoas) ?>)</span>
    <div class="detalhe-value">
        <?php foreach ($pessoas as $pessoa): ?>
            <div style="background: var(--light); padding: 10px; border-radius: 5px; margin-top: 8px;">
                <strong><?= htmlspecialchars($pessoa['nome']) ?></strong><br>
                <small>
                    📅 Nascimento: <?= date('d/m/Y', strtotime($pessoa['data_nascimento'])) ?><br>
                    📍 Cidade: <?= htmlspecialchars($pessoa['cidade']) ?>
                </small>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($pedido['foto']): ?>
    <div class="detalhe-row">
        <span class="detalhe-label">Foto Enviada</span>
        <div class="detalhe-value">
            <img src="../uploads/<?= htmlspecialchars($pedido['foto']) ?>" 
                 alt="Foto" 
                 style="max-width: 100%; border-radius: 10px; margin-top: 10px;">
        </div>
    </div>
<?php endif; ?>

<?php if ($pedido['ip_origem']): ?>
    <div class="detalhe-row">
        <span class="detalhe-label">IP de Origem</span>
        <span class="detalhe-value"><?= htmlspecialchars($pedido['ip_origem']) ?></span>
    </div>
<?php endif; ?>

