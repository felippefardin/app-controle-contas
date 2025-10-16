<?php

// --- COLOQUE A SENHA QUE VOCÊ DESEJA USAR AQUI ---
$minha_senha_secreta = 'Fa525658*'; // Troque 'admin' pela sua senha desejada

// Gera o hash da senha de forma segura
$hash_da_senha = password_hash($minha_senha_secreta, PASSWORD_DEFAULT);

// Exibe o hash que você vai copiar para o banco de dados
echo "Criptografia da sua senha:<br>";
echo '<textarea rows="3" cols="80" readonly>' . htmlspecialchars($hash_da_senha) . '</textarea>';
?>