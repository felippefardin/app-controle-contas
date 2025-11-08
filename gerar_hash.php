<?php
// Defina a nova senha que você quer usar
$novaSenha = 'Fa525658***';

$hash = password_hash($novaSenha, PASSWORD_DEFAULT);

echo "Seu novo hash é: <br>";
echo $hash;
// ?>