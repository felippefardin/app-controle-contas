<?php
require_once '../../includes/session_init.php';
require_once '../../database.php';
// include '../../includes/header.php'; // Ajuste se necessário para o header do seu admin

// ✅ Conexão via MySQLi (definida no database.php)
$conn = getMasterConnection(); 

// Busca
$busca = $_GET['busca'] ?? '';
$sql = "SELECT cs.*, u.nome as usuario_nome 
        FROM chat_sessions cs 
        JOIN usuarios u ON cs.usuario_id = u.id 
        WHERE cs.status = 'closed'";

$params = [];
$types = "";

if ($busca) {
    $sql .= " AND cs.protocolo LIKE ?";
    $params[] = "%$busca%";
    $types .= "s";
}

$sql .= " ORDER BY cs.data_criacao DESC";

$stmt = $conn->prepare($sql);

if ($busca) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Arquivos de Suporte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #333; }
        
        /* Tabela */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; font-weight: 600; text-transform: uppercase; font-size: 0.9rem; }
        tr:hover { background-color: #f1f1f1; }
        
        /* Botões e Forms */
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; font-size: 14px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: 0.3s; }
        .btn-primary { background-color: #007bff; } .btn-primary:hover { background-color: #0056b3; }
        .btn-danger { background-color: #dc3545; } .btn-danger:hover { background-color: #a71d2a; }
        .btn-secondary { background-color: #6c757d; } .btn-secondary:hover { background-color: #545b62; }
        
        .form-control { padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 300px; font-size: 1rem; }
        .actions-bar { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-archive"></i> Arquivos de Suporte (Protocolos)</h2>
    
    <form method="GET" class="actions-bar">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por Protocolo..." value="<?php echo htmlspecialchars($busca); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>Protocolo</th>
                <th>Usuário</th>
                <th>Data Fim</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($chat = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($chat['protocolo']); ?></strong></td>
                    <td><?php echo htmlspecialchars($chat['usuario_nome']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($chat['closed_at'])); ?></td>
                    <td>
                        <a href="../../actions/gerar_pdf_chat.php?chat_id=<?php echo $chat['id']; ?>" class="btn btn-danger" target="_blank">
                            <i class="fas fa-file-pdf"></i> Baixar PDF
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center; color:#666;">Nenhum arquivo encontrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>