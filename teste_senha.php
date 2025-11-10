<?php
$senha_digitada = 'admin123';
$hash_banco = '[$2y$10$5feBrPX8f9/G5/9uq8XaSeq01H08pwZ0pEr0cSDN.sTpPCgmhOw9a]';
if (password_verify($senha_digitada, $hash_banco)) {
    echo "✅ A senha confere com o hash!";
} else {
    echo "❌ A senha NÃO confere com o hash.";
}