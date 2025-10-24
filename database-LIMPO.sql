-- ============================================
-- SISTEMA DE REIKI - MÃE MARIA CELESTIAL
-- Banco de Dados LIMPO (Sem dados de teste)
-- ============================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS reiki_mae_maria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reiki_mae_maria;

-- ============================================
-- TABELA DE ADMINISTRADORES
-- ============================================
CREATE TABLE IF NOT EXISTS admin_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir administrador padrão (senha: admin123)
INSERT INTO admin_usuarios (nome, email, senha) VALUES 
('Administrador', 'admin@reiki.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================
-- TABELA DE PEDIDOS DE REIKI
-- ============================================
CREATE TABLE IF NOT EXISTS pedidos_reiki (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Dados do solicitante
    nome_solicitante VARCHAR(150) NOT NULL,
    data_nascimento_solicitante DATE NOT NULL,
    
    -- Quantidade de pessoas
    quantidade_pessoas INT NOT NULL DEFAULT 1,
    
    -- Necessidade/Motivo
    necessidade TEXT NOT NULL,
    
    -- Foto opcional
    foto VARCHAR(255) NULL,
    
    -- Status e controle
    status ENUM('pendente', 'concluido') DEFAULT 'pendente',
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_conclusao TIMESTAMP NULL,
    admin_id INT NULL,
    
    -- IP e controle
    ip_origem VARCHAR(45) NULL,
    
    INDEX idx_status (status),
    INDEX idx_data_envio (data_envio),
    INDEX idx_data_conclusao (data_conclusao),
    FOREIGN KEY (admin_id) REFERENCES admin_usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE PESSOAS PARA ATENDIMENTO
-- ============================================
CREATE TABLE IF NOT EXISTS pessoas_atendimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    
    -- Dados da pessoa
    nome VARCHAR(150) NOT NULL,
    data_nascimento DATE NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    
    -- Ordem no formulário
    ordem INT NOT NULL DEFAULT 1,
    
    INDEX idx_pedido (pedido_id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos_reiki(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE LOG DE AÇÕES ADMINISTRATIVAS
-- ============================================
CREATE TABLE IF NOT EXISTS logs_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_admin (admin_id),
    INDEX idx_data (data_hora),
    FOREIGN KEY (admin_id) REFERENCES admin_usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS ÚTEIS
-- ============================================

-- View de pedidos pendentes com contagem
CREATE OR REPLACE VIEW vw_pedidos_pendentes AS
SELECT 
    p.*,
    COUNT(pa.id) as total_pessoas,
    DATEDIFF(CURRENT_DATE, DATE(p.data_envio)) as dias_aguardando
FROM pedidos_reiki p
LEFT JOIN pessoas_atendimento pa ON p.id = pa.pedido_id
WHERE p.status = 'pendente'
GROUP BY p.id
ORDER BY p.data_envio ASC;

-- View de pedidos concluídos
CREATE OR REPLACE VIEW vw_pedidos_concluidos AS
SELECT 
    p.*,
    a.nome as admin_nome,
    COUNT(pa.id) as total_pessoas
FROM pedidos_reiki p
LEFT JOIN admin_usuarios a ON p.admin_id = a.id
LEFT JOIN pessoas_atendimento pa ON p.id = pa.pedido_id
WHERE p.status = 'concluido'
GROUP BY p.id
ORDER BY p.data_conclusao DESC;

-- View de estatísticas
CREATE OR REPLACE VIEW vw_estatisticas AS
SELECT 
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
    SUM(CASE WHEN DATE(data_envio) = CURRENT_DATE THEN 1 ELSE 0 END) as hoje
FROM pedidos_reiki;

-- ============================================
-- INFORMAÇÕES IMPORTANTES
-- ============================================
-- 
-- USUÁRIO ADMIN PADRÃO:
-- Email: admin@reiki.com
-- Senha: admin123
-- 
-- ⚠️ ALTERE A SENHA APÓS PRIMEIRO LOGIN!
-- 
-- ============================================

SELECT 'Banco de dados criado com sucesso! Sistema pronto para uso!' as status;

