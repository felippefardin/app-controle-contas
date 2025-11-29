<?php
// Este arquivo deve ser incluído no final de pages/home.php (antes do </body>)

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) return;

$usuario_id_logado = $_SESSION['user_id'];
$hoje = date('Y-m-d');

// Conectar ao Master para buscar mensagens globais
// Assume que a função getMasterConnection() já está disponível via database.php incluído na home
if (!function_exists('getMasterConnection')) {
    // Fallback se não estiver carregado
    if(file_exists(__DIR__ . '/../database.php')) include_once(__DIR__ . '/../database.php');
    else if(file_exists(__DIR__ . '/database.php')) include_once(__DIR__ . '/database.php');
}

$connMaster = getMasterConnection();

// 1. Buscar mensagem ativa para HOJE
$sql_msg = "SELECT * FROM mensagens_home WHERE data_exibicao = '$hoje' LIMIT 1";
$res_msg = $connMaster->query($sql_msg);

if ($res_msg && $res_msg->num_rows > 0) {
    $mensagem = $res_msg->fetch_assoc();
    $mensagem_id = $mensagem['id'];
    $limite_views = intval($mensagem['quantidade_logins']);

    // 2. Verificar quantas vezes este usuário já viu
    $sql_views = "SELECT visualizacoes FROM mensagens_home_visualizacoes 
                  WHERE mensagem_id = $mensagem_id AND usuario_id = $usuario_id_logado";
    $res_views = $connMaster->query($sql_views);
    
    $views_atuais = 0;
    $ja_existe_registro = false;

    if ($res_views && $res_views->num_rows > 0) {
        $row_view = $res_views->fetch_assoc();
        $views_atuais = intval($row_view['visualizacoes']);
        $ja_existe_registro = true;
    }

    // 3. Se views atuais for MENOR que o limite, exibe o modal e incrementa
    if ($views_atuais < $limite_views) {
        
        // Incrementa no banco
        if ($ja_existe_registro) {
            $connMaster->query("UPDATE mensagens_home_visualizacoes SET visualizacoes = visualizacoes + 1, ultima_visualizacao = NOW() WHERE mensagem_id = $mensagem_id AND usuario_id = $usuario_id_logado");
        } else {
            $connMaster->query("INSERT INTO mensagens_home_visualizacoes (mensagem_id, usuario_id, visualizacoes) VALUES ($mensagem_id, $usuario_id_logado, 1)");
        }

        // 4. Renderizar HTML do Modal
        $caminho_imagem = !empty($mensagem['arquivo']) ? '../assets/uploads/mensagens/' . $mensagem['arquivo'] : '';
        ?>
        <style>
            .sys-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); animation: fadeIn 0.3s; }
            .sys-modal { background: #1e1e1e; width: 90%; max-width: 500px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid #333; position: relative; animation: slideUp 0.3s; }
            .sys-modal-img { width: 100%; height: 200px; object-fit: cover; display: block; }
            .sys-modal-body { padding: 25px; color: #eee; text-align: center; }
            .sys-modal-title { color: #00bfff; font-size: 1.5rem; margin-bottom: 15px; font-weight: bold; }
            .sys-modal-text { font-size: 1rem; line-height: 1.5; color: #ccc; margin-bottom: 20px; }
            .sys-btn { background: #00bfff; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.2s; }
            .sys-btn:hover { background: #009acd; }
            .sys-close { position: absolute; top: 10px; right: 15px; font-size: 24px; color: white; cursor: pointer; background: rgba(0,0,0,0.5); width: 30px; height: 30px; border-radius: 50%; line-height: 30px; text-align: center; }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        </style>

        <div class="sys-modal-overlay" id="modalSistema">
            <div class="sys-modal">
                <div class="sys-close" onclick="document.getElementById('modalSistema').style.display='none'">&times;</div>
                
                <?php if($caminho_imagem && file_exists(__DIR__ . '/../assets/uploads/mensagens/' . $mensagem['arquivo'])): ?>
                    <img src="<?= $caminho_imagem ?>" class="sys-modal-img" alt="Aviso">
                <?php endif; ?>

                <div class="sys-modal-body">
                    <div class="sys-modal-title"><?= htmlspecialchars($mensagem['titulo']) ?></div>
                    <div class="sys-modal-text"><?= nl2br(htmlspecialchars($mensagem['mensagem'])) ?></div>
                    
                    <?php if(!empty($mensagem['link_botao'])): ?>
                        <a href="<?= htmlspecialchars($mensagem['link_botao']) ?>" target="_blank" class="sys-btn" onclick="document.getElementById('modalSistema').style.display='none'">
                            <?= htmlspecialchars($mensagem['texto_botao']) ?>
                        </a>
                    <?php else: ?>
                        <button class="sys-btn" onclick="document.getElementById('modalSistema').style.display='none'">
                            <?= htmlspecialchars($mensagem['texto_botao']) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>