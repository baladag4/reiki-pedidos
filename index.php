<?php
// ============================================
// PÁGINA PÚBLICA - ENVIO DE PEDIDOS DE REIKI
// ============================================

require_once 'config/database.php';

$sucesso = false;
$erro = null;

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        
        // Validar dados principais
        $nome_solicitante = trim($_POST['nome_solicitante'] ?? '');
        $data_nascimento_solicitante = $_POST['data_nascimento_solicitante'] ?? '';
        $quantidade_pessoas = (int)($_POST['quantidade_pessoas'] ?? 1);
        $necessidade = trim($_POST['necessidade'] ?? '');
        
        if (empty($nome_solicitante) || empty($data_nascimento_solicitante) || empty($necessidade)) {
            throw new Exception('Por favor, preencha todos os campos obrigatórios.');
        }
        
        if ($quantidade_pessoas < 1 || $quantidade_pessoas > 10) {
            throw new Exception('Quantidade de pessoas deve ser entre 1 e 10.');
        }
        
        // Upload de foto (opcional)
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($extensao, $extensoes_permitidas)) {
                $foto = uniqid() . '.' . $extensao;
                move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto);
            }
        }
        
        // Inserir pedido
        $stmt = $pdo->prepare("
            INSERT INTO pedidos_reiki 
            (nome_solicitante, data_nascimento_solicitante, quantidade_pessoas, necessidade, foto, ip_origem) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nome_solicitante,
            $data_nascimento_solicitante,
            $quantidade_pessoas,
            $necessidade,
            $foto,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        $pedido_id = $pdo->lastInsertId();
        
        // Inserir pessoas para atendimento
        for ($i = 1; $i <= $quantidade_pessoas; $i++) {
            $nome_pessoa = trim($_POST["pessoa_nome_$i"] ?? '');
            $data_nasc_pessoa = $_POST["pessoa_data_$i"] ?? '';
            $cidade_pessoa = trim($_POST["pessoa_cidade_$i"] ?? '');
            
            if (empty($nome_pessoa) || empty($data_nasc_pessoa) || empty($cidade_pessoa)) {
                throw new Exception("Preencha todos os dados da pessoa $i.");
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO pessoas_atendimento (pedido_id, nome, data_nascimento, cidade, ordem) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$pedido_id, $nome_pessoa, $data_nasc_pessoa, $cidade_pessoa, $i]);
        }
        
        $pdo->commit();
        $sucesso = true;
        
        // Limpar POST para não reenviar
        $_POST = [];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiki de Mãe Maria Celestial</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b9d 0%, #c239b3 50%, #4c2b8f 100%);
            position: relative;
            overflow-x: hidden;
        }
        
        /* Flores decorativas */
        .flower {
            position: fixed;
            font-size: 3rem;
            opacity: 0.3;
            animation: float 6s ease-in-out infinite;
            z-index: 1;
            pointer-events: none;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        .flower1 { top: 10%; left: 5%; animation-delay: 0s; }
        .flower2 { top: 20%; right: 8%; animation-delay: 1s; }
        .flower3 { top: 60%; left: 10%; animation-delay: 2s; }
        .flower4 { top: 70%; right: 5%; animation-delay: 1.5s; }
        .flower5 { top: 40%; left: 3%; animation-delay: 2.5s; }
        .flower6 { top: 85%; right: 10%; animation-delay: 0.5s; }
        
        .header-publico {
            background: linear-gradient(135deg, #ff85c0 0%, #de4c8a 50%, #b83280 100%);
            position: relative;
            overflow: hidden;
        }
        
        .header-publico::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
        }
        
        .mae-maria-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
            margin: 0 auto 20px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 192, 203, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff6b9d 0%, #c239b3 100%);
            border: none;
            box-shadow: 0 5px 20px rgba(194, 57, 179, 0.4);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ff85c0 0%, #de4c8a 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(194, 57, 179, 0.6);
        }
        
        .form-control:focus {
            border-color: #ff6b9d;
            box-shadow: 0 0 0 3px rgba(255, 107, 157, 0.2);
        }
        
        .pessoa-item {
            background: linear-gradient(135deg, rgba(255, 192, 203, 0.2) 0%, rgba(255, 182, 193, 0.3) 100%);
            border-left-color: #ff6b9d;
        }
        
        .pessoa-item h4 {
            color: #c239b3;
        }
        
        .card-header h2 {
            color: #c239b3;
        }
    </style>
</head>
<body>
    <!-- Flores decorativas -->
    <div class="flower flower1">🌸</div>
    <div class="flower flower2">🌺</div>
    <div class="flower flower3">🌷</div>
    <div class="flower flower4">🌹</div>
    <div class="flower flower5">🌸</div>
    <div class="flower flower6">🌺</div>

    <div class="header-publico">
        <div class="header-content">
            <div class="mae-maria-image">
                🙏
            </div>
            <h1>✨ Reiki de Mãe Maria Celestial ✨</h1>
            <p>🌹 Receba a energia de cura e amor da Mãe Maria 🌹</p>
            <p style="font-size: 0.95rem; margin-top: 10px; opacity: 0.9;">
                Envie seu pedido com fé e amor
            </p>
        </div>
    </div>

    <div class="container">
        <?php if ($sucesso): ?>
            <div class="card fade-in">
                <div class="alert alert-success">
                    <strong>✅ Pedido enviado com sucesso!</strong><br>
                    Seu pedido foi recebido e será atendido em breve com todo amor e luz.<br>
                    Que a energia da Mãe Maria te abençoe! 🙏
                </div>
                <a href="index.php" class="btn btn-primary">Enviar outro pedido</a>
            </div>
        <?php else: ?>
            <div class="card fade-in">
                <div class="card-header">
                    <h2>Solicitar Atendimento de Reiki</h2>
                    <p>Preencha o formulário com seus dados e necessidades</p>
                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger">
                        ⚠️ <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="formReiki">
                    <!-- Dados do Solicitante -->
                    <div class="form-group">
                        <label class="required">Seu Nome Completo</label>
                        <input type="text" name="nome_solicitante" class="form-control" required 
                               value="<?= htmlspecialchars($_POST['nome_solicitante'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Sua Data de Nascimento</label>
                        <input type="date" name="data_nascimento_solicitante" class="form-control" required
                               value="<?= htmlspecialchars($_POST['data_nascimento_solicitante'] ?? '') ?>">
                    </div>

                    <!-- Quantidade de Pessoas -->
                    <div class="form-group">
                        <label class="required">Para quantas pessoas deseja enviar o atendimento?</label>
                        <select name="quantidade_pessoas" id="quantidade_pessoas" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="1">1 pessoa</option>
                            <option value="2">2 pessoas</option>
                            <option value="3">3 pessoas</option>
                            <option value="4">4 pessoas</option>
                            <option value="5">5 pessoas</option>
                            <option value="6">6 pessoas</option>
                            <option value="7">7 pessoas</option>
                            <option value="8">8 pessoas</option>
                            <option value="9">9 pessoas</option>
                            <option value="10">10 pessoas</option>
                        </select>
                        <small class="form-text">Escolha quantas pessoas receberão o Reiki</small>
                    </div>

                    <!-- Container para pessoas -->
                    <div id="pessoasContainer" class="pessoas-repeater" style="display: none;">
                        <!-- Pessoas serão adicionadas dinamicamente aqui -->
                    </div>

                    <!-- Necessidade -->
                    <div class="form-group">
                        <label class="required">Relate sua necessidade</label>
                        <textarea name="necessidade" class="form-control" required 
                                  placeholder="Descreva sua necessidade, pedido ou situação que deseja que seja trabalhada no Reiki..."><?= htmlspecialchars($_POST['necessidade'] ?? '') ?></textarea>
                        <small class="form-text">Seja sincero(a) e detalhado(a) em sua necessidade</small>
                    </div>

                    <!-- Foto Opcional -->
                    <div class="form-group">
                        <label>Foto (Opcional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/*" id="inputFoto">
                        <small class="form-text">Você pode enviar uma foto se desejar (opcional)</small>
                        <img id="previewFoto" class="foto-preview" alt="Preview">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="btnEnviar" disabled>
                        ✨ Enviar Pedido de Reiki ✨
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // Elementos
        const quantidadeSelect = document.getElementById('quantidade_pessoas');
        const pessoasContainer = document.getElementById('pessoasContainer');
        const btnEnviar = document.getElementById('btnEnviar');
        const inputFoto = document.getElementById('inputFoto');
        const previewFoto = document.getElementById('previewFoto');

        // Gerar formulários de pessoas
        quantidadeSelect.addEventListener('change', function() {
            const quantidade = parseInt(this.value);
            
            if (quantidade > 0) {
                pessoasContainer.style.display = 'block';
                pessoasContainer.innerHTML = '<h3 style="color: var(--primary); margin-bottom: 20px;">Dados das Pessoas para Atendimento</h3>';
                
                for (let i = 1; i <= quantidade; i++) {
                    const pessoaDiv = document.createElement('div');
                    pessoaDiv.className = 'pessoa-item fade-in';
                    pessoaDiv.innerHTML = `
                        <h4>Pessoa ${i}</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">Nome Completo</label>
                                <input type="text" name="pessoa_nome_${i}" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="required">Data de Nascimento</label>
                                <input type="date" name="pessoa_data_${i}" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="required">Cidade</label>
                                <input type="text" name="pessoa_cidade_${i}" class="form-control" required 
                                       placeholder="Ex: São Paulo">
                            </div>
                        </div>
                    `;
                    pessoasContainer.appendChild(pessoaDiv);
                }
                
                btnEnviar.disabled = false;
            } else {
                pessoasContainer.style.display = 'none';
                pessoasContainer.innerHTML = '';
                btnEnviar.disabled = true;
            }
        });

        // Preview da foto
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewFoto.src = e.target.result;
                    previewFoto.classList.add('show');
                }
                reader.readAsDataURL(file);
            } else {
                previewFoto.classList.remove('show');
            }
        });

        // Validação antes de enviar
        document.getElementById('formReiki').addEventListener('submit', function(e) {
            btnEnviar.disabled = true;
            btnEnviar.textContent = '⏳ Enviando...';
        });
    </script>
</body>
</html>

