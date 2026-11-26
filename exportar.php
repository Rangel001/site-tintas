
<?php

require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/conexao.php";

if (!in_array($_SERVER["REMOTE_ADDR"] ?? "", [
    "127.0.0.1", "::1"
], true)) {
    http_response_code(403);
    exit("Acesso não permitido.");
}

date_default_timezone_set("America/Sao_Paulo");

// Busca todas as tintas cadastradas

$tintas = $pdo->query(
    "SELECT * FROM tintas ORDER BY nome ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Nome do arquivo

$nome = "Estoque_AluStock_" . date("d-m-Y") . ".csv";

// Configuração do download

while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: text/csv; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $nome . '"');
header("Cache-Control: no-store");

// Corrige os acentos no Excel

echo "\xEF\xBB\xBF";

$arquivo = fopen("php://output", "w");

// Cabeçalho da planilha

fputcsv($arquivo, [
    "ID",
    "Nome da tinta",
    "Código",
    "Fabricante",
    "Acabamento",
    "Tipo de tinta",
    "Quantidade (kg)",
    "Estoque mínimo (kg)",
    "Lote",
    "Validade",
    "Localização",
    "Valor por kg (R$)",
    "Valor total (R$)",
    "Situação"
], ";", '"', "");

// Protege os campos de texto

function proteger($valor) {

    $valor = (string) ($valor ?? "");

    if (preg_match('/^[\s]*[=+\-@]/u', $valor)) {
        return "'" . $valor;
    }

    return $valor;
}

// Exporta as tintas

foreach ($tintas as $tinta) {

    $quantidade = (float) $tinta["quantidade"];
    $minimo = (float) $tinta["estoque_minimo"];

    if ($quantidade <= 0) {

        $situacao = "Esgotado";

    } elseif ($quantidade <= $minimo) {

        $situacao = "Estoque baixo";

    } else {

        $situacao = "Disponível";
    }

    $valorKg = $tinta["valor_kg"];

    $valorTotal = $valorKg !== null
        ? $quantidade * (float) $valorKg
        : null;

    fputcsv($arquivo, [

        $tinta["id"],
        proteger($tinta["nome"]),
        proteger($tinta["codigo"]),
        proteger($tinta["fabricante"]),
        proteger($tinta["acabamento"]),
        proteger($tinta["tipo"]),

        number_format($quantidade, 3, ",", ""),
        number_format($minimo, 3, ",", ""),

        proteger($tinta["lote"]),
        proteger($tinta["validade"]),
        proteger($tinta["localizacao"]),

        $valorKg !== null
            ? number_format($valorKg, 2, ",", "")
            : "",

        $valorTotal !== null
            ? number_format($valorTotal, 2, ",", "")
            : "",

        $situacao

    ], ";", '"', "");
}

fclose($arquivo);

exit;
