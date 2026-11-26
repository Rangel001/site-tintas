
<?php

require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/conexao.php";

date_default_timezone_set("America/Sao_Paulo");

// Permite backup somente no computador local

if (!in_array($_SERVER["REMOTE_ADDR"] ?? "", [
    "127.0.0.1", "::1"
], true)) {

    http_response_code(403);
    exit("Acesso não permitido.");
}

// Verificação de segurança

if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    !hash_equals(
        $_SESSION["csrf"],
        $_POST["csrf"] ?? ""
    )
) {
    http_response_code(403);
    exit("Solicitação inválida.");
}

// Nome do backup

$nome = "AluStock_Backup_" .
    date("d-m-Y_H-i-s") . ".db";

// Cria um arquivo temporário

$temporario = tempnam(
    sys_get_temp_dir(), "alustock_"
);

if ($temporario === false) {
    exit("Não foi possível criar o backup.");
}

// Remove o arquivo temporário após o download

register_shutdown_function(function () use ($temporario) {

    if (is_file($temporario)) {
        unlink($temporario);
    }

});

// Gera uma cópia completa do banco SQLite

try {

    $pdo->exec(
        "VACUUM INTO " . $pdo->quote($temporario)
    );

} catch (PDOException $e) {

    http_response_code(500);

    exit("Erro ao gerar backup do banco de dados.");

}

// Limpa a saída

while (ob_get_level() > 0) {
    ob_end_clean();
}

// Configura o download

header("Content-Type: application/octet-stream");

header(
    'Content-Disposition: attachment; filename="' .
    $nome . '"'
);

header("Content-Length: " . filesize($temporario));

header("Cache-Control: no-store");

// Baixa o arquivo

readfile($temporario);

exit;
